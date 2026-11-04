<?php

if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    exit('Local use only.');
}

set_time_limit(0);
require __DIR__ . '/../config/database.php';

// Show progress line by line instead of waiting for the end.
header('Content-Type: text/html; charset=utf-8');
while (ob_get_level()) ob_end_flush();
ob_implicit_flush(true);

$tables = [
    'health_education' => ['title', 'content'],
    'myth_facts'       => ['myth', 'fact'],
    'symptom_guidance' => ['symptom', 'possible_cause', 'self_care', 'when_to_seek_care'],
];

// Ask Groq for a Kiswahili translation. Returns [text, error].
function groq_sw(string $text): array
{
    $payload = [
        'model' => GROQ_MODEL,
        'messages' => [
            [
                'role' => 'system',
                'content' => 'You are a professional English-to-Kiswahili medical translator for community health '
                           . 'materials in Kenya. Translate the user\'s text into clear, simple standard Kiswahili '
                           . 'as used by the Kenya Ministry of Health. Keep numbers, medicine names and abbreviations '
                           . 'such as HIV, ORS and WHO unchanged. Return ONLY the Kiswahili translation, with no notes, '
                           . 'quotes or explanations.',
            ],
            ['role' => 'user', 'content' => $text],
        ],
        'temperature' => 0.2,
        'max_tokens'  => 2048,
    ];

    for ($attempt = 1; $attempt <= 3; $attempt++) {
        $ch = curl_init(GROQ_API_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . GROQ_API_KEY,
            ],
            CURLOPT_TIMEOUT => 60,
        ]);
        $res  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $cerr = curl_error($ch);
        curl_close($ch);

        if ($code === 429) {            // rate limited: wait and retry
            sleep(15);
            continue;
        }
        if ($cerr)          return ['', 'cURL error: ' . $cerr];
        if ($code !== 200)  return ['', "HTTP $code: " . substr((string)$res, 0, 200)];

        $data = json_decode($res, true);
        $out  = $data['choices'][0]['message']['content'] ?? '';
        $out  = preg_replace('/<think>.*?<\/think>/s', '', $out);   // strip reasoning blocks
        return [trim($out, " \t\n\r\"'"), ''];
    }
    return ['', 'Rate limited 3 times, skipped'];
}

// Reject empty, untranslated or repetitive ("nyorok-nyorok-nyorok") output.
function looks_broken(string $out, string $in): bool
{
    if ($out === '') return true;
    if (strcasecmp(trim($out), trim($in)) === 0) return true;

    $words = preg_split('/\s+/u', mb_strtolower($out), -1, PREG_SPLIT_NO_EMPTY);
    if (count($words) >= 8 && count(array_unique($words)) / count($words) < 0.35) return true;
    if (preg_match('/(\b\S+\b)(?:[\s-]+\1\b){3,}/u', $out)) return true;

    $ratio = mb_strlen($out) / max(1, mb_strlen($in));
    return $ratio < 0.4 || $ratio > 3;
}

$done = $skipped = $failed = 0;
echo '<pre style="font:14px/1.5 monospace">';

foreach ($tables as $table => $fields) {
    echo "\n=== $table ===\n";
    $rows = $conn->query("SELECT * FROM `$table` ORDER BY id")->fetch_all(MYSQLI_ASSOC);

    foreach ($rows as $row) {
        foreach ($fields as $f) {
            $en = trim($row[$f] ?? '');
            $sw = trim($row[$f . '_sw'] ?? '');

            if ($en === '') continue;
            if ($sw !== '' && $sw !== $en) { $skipped++; continue; }   // already translated

            [$out, $err] = groq_sw($en);

            if ($err !== '') {
                echo "✗ #{$row['id']} $f — $err\n";
                $failed++;
                continue;
            }
            if (looks_broken($out, $en)) {
                echo "✗ #{$row['id']} $f — rejected bad output, kept English\n";
                $failed++;
                continue;
            }

            $col  = $f . '_sw';
            $stmt = $conn->prepare("UPDATE `$table` SET `$col` = ? WHERE id = ?");
            $stmt->bind_param('si', $out, $row['id']);
            $stmt->execute();
            $stmt->close();

            echo "✓ #{$row['id']} $f → " . htmlspecialchars(mb_substr($out, 0, 70)) . "\n";
            $done++;
            sleep(2);   // stay under Groq's free-tier rate limit
        }
    }
}

echo "\nFinished. Translated: $done | Already done: $skipped | Failed/rejected: $failed\n";
if ($failed) echo "Re-run this page to retry the failed ones.\n";
echo '</pre>';