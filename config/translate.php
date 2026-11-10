<?php

// Load database configuration if the Groq API key is not defined.
if (!defined('GROQ_API_KEY')) {
    include_once __DIR__ . '/database.php';
}

// Return text in the selected language.
function t(array $row, string $field, bool $lazy_maa = false, string $table = '', int $row_id = 0): string
{
    $lang = $_SESSION['language'] ?? 'english';

    // Columns to check
    $suffixes = match ($lang) {
        'kiswahili' => ['_sw'],
        'maa'       => ['_maa', '_sw'],
        default     => [],
    };

    $col_lang = $field . '_maa';
    $col_english = $field;

    // Return first available translation
    foreach ($suffixes as $suffix) {
        $value = $row[$field . $suffix] ?? '';
        if ($value !== '' && $value !== ($row[$col_english] ?? '')) {
            return $value;
        }
    }

    // Generate and save a missing Maa translation.
    if ($lang === 'maa' && $lazy_maa && $table !== '' && $row_id > 0) {
        $english_text = $row[$col_english] ?? '';

        if ($english_text !== '') {
            $translated = groq_translate_to_maa($english_text);

            if ($translated !== '') {
                groq_save_maa_to_db($table, $col_lang, $translated, $row_id);
                return $translated;
            }
        }
    }

    // Use English if the translation is unavailable.
    return $row[$col_english] ?? '';
}

// Translate English text into Maa using the Groq API.
function groq_translate_to_maa(string $text): string
{
    if (empty($text)) {
        return '';
    }

    // Prepare the translation request.
    $payload = [
        'model' => GROQ_MODEL,
        'messages' => [
            [
                'role' => 'system',
                'content' => 'You are an expert translator for the Maa language spoken in Kenya and Tanzania. Translate the following health-related English text into Maa. Return ONLY the translated text with no explanations.',
            ],
            [
                'role' => 'user',
                'content' => $text,
            ],
        ],
        'temperature' => 0.2,
        'max_tokens' => 2048,
    ];

    // Send the request to Groq.
    $ch = curl_init(GROQ_API_URL);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . GROQ_API_KEY,
        ],
        CURLOPT_TIMEOUT => 20,
    ]);

    $response = curl_exec($ch);
    $err = curl_error($ch);

    curl_close($ch);

    // Return an empty string if the request fails.
    if ($err || !$response) {
        return '';
    }

    // Extract and return the translated text.
    $data = json_decode($response, true);

    return trim($data['choices'][0]['message']['content'] ?? '');
}

// Save a Maa translation in the database.
function groq_save_maa_to_db(string $table, string $column, string $value, int $id): void
{
    global $conn;

    // Allow updates only to approved tables and columns.
    $allowed_tables = [
        'health_education',
        'myth_facts',
        'symptom_guidance'
    ];

    $allowed_columns = [
        'title_maa',
        'content_maa',
        'myth_maa',
        'fact_maa',
        'symptom_maa',
        'possible_cause_maa',
        'self_care_maa',
        'when_to_seek_care_maa'
    ];

    if (
        !in_array($table, $allowed_tables, true) ||
        !in_array($column, $allowed_columns, true)
    ) {
        return;
    }

    // Update the record using a prepared statement.
    $stmt = $conn->prepare(
        "UPDATE `{$table}` SET `{$column}` = ? WHERE id = ?"
    );

    if ($stmt) {
        $stmt->bind_param('si', $value, $id);
        $stmt->execute();
        $stmt->close();
    }
}

// Translate English text into Kiswahili using Google Translate.
function google_translate_to_sw(string $text): string
{
    if (empty($text)) {
        return '';
    }

    // Check whether the Google API key is configured.
    if (
        !defined('GOOGLE_TRANSLATE_API_KEY') ||
        GOOGLE_TRANSLATE_API_KEY === 'YOUR_GOOGLE_API_KEY_HERE'
    ) {
        return '';
    }

    // Prepare the Google Translation API request.
    $url = 'https://translation.googleapis.com/language/translate/v2?key=' . GOOGLE_TRANSLATE_API_KEY;

    $body = json_encode([
        'q' => $text,
        'source' => 'en',
        'target' => 'sw',
        'format' => 'text'
    ]);

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json'
        ],
        CURLOPT_TIMEOUT => 15,
    ]);

    $response = curl_exec($ch);
    $err = curl_error($ch);

    curl_close($ch);

    // Return an empty string if the request fails.
    if ($err || !$response) {
        return '';
    }

    // Extract and decode the translated text.
    $data = json_decode($response, true);

    return html_entity_decode(
        $data['data']['translations'][0]['translatedText'] ?? '',
        ENT_QUOTES | ENT_HTML5,
        'UTF-8'
    );
}
// Interface text translation
function __(string $text): string
{
    static $dicts = [];

    $lang  = $_SESSION['language'] ?? 'english';
    $order = [
        'kiswahili' => ['lang_sw.php'],
        'maa'       => ['lang_maa.php', 'lang_sw.php'],
    ];

    foreach ($order[$lang] ?? [] as $file) {
        if (!isset($dicts[$file])) {
            $path = __DIR__ . '/' . $file;
            $dicts[$file] = file_exists($path) ? require $path : [];
        }
        if (isset($dicts[$file][$text])) {
            return $dicts[$file][$text];
        }
    }

    return $text;
}

// Maa notice banner
function maa_notice(): string
{
    if (($_SESSION['language'] ?? '') !== 'maa') {
        return '';
    }

    return '<div style="background:#c9913d;color:#fff;padding:10px max(16px,5vw);font-size:0.85rem;line-height:1.55">'
         . 'Karibu. Tunaendelea kuandaa maudhui kwa Kimaa. Kwa sasa unaweza kusoma kwa Kiswahili.'
         . '<br><span style="opacity:.85">Welcome. We are still preparing content in Maa. For now, you can read it in Kiswahili.</span></div>';
}
