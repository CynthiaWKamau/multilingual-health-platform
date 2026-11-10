<?php
// Translates interface text and health content into Maa (Samburu dialect) using the Groq API.
// Each translation passes automatic checks and a back-translation review before it is saved.
// Every attempt is logged to maa_review.csv; rejected items keep their English text.

if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    exit('Local use only.');
}

set_time_limit(0);
require __DIR__ . '/../config/database.php';

header('Content-Type: text/html; charset=utf-8');
while (ob_get_level()) ob_end_flush();
ob_implicit_flush(true);

// Minimum back-translation score (1–5) required to accept a translation.
const MIN_SCORE = 4;

// Models tried in order; the first one that responds is used.
$candidate_models = ['openai/gpt-oss-120b', 'llama-3.3-70b-versatile', GROQ_MODEL];

$sw_dict_file  = __DIR__ . '/../config/lang_sw.php';
$maa_dict_file = __DIR__ . '/../config/lang_maa.php';
$review_file   = __DIR__ . '/maa_review.csv';
$rejected_file = __DIR__ . '/maa_rejected.json';

$part  = $_GET['part'] ?? '';
$limit = max(1, (int)($_GET['limit'] ?? 20));
$retry = isset($_GET['retry']);

$tables = [
    'health_education' => ['title', 'content'],
    'myth_facts'       => ['myth', 'fact'],
    'symptom_guidance' => ['symptom', 'possible_cause', 'self_care', 'when_to_seek_care'],
];

// Sends one chat request to Groq and returns [text, error].
function groq_chat(string $model, string $system, string $user, float $temperature = 0.2): array
{
    $payload = json_encode([
        'model'       => $model,
        'messages'    => [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user',   'content' => $user],
        ],
        'temperature' => $temperature,
        'max_tokens'  => 4096,
    ]);

    for ($attempt = 1; $attempt <= 3; $attempt++) {
        $ch = curl_init(GROQ_API_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . GROQ_API_KEY,
            ],
            CURLOPT_TIMEOUT => 90,
        ]);
        $res  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $cerr = curl_error($ch);
        curl_close($ch);

        if ($code === 429) {
            sleep(20);
            continue;
        }
        if ($cerr)         return ['', 'cURL error: ' . $cerr];
        if ($code !== 200) return ['', "HTTP $code: " . substr((string)$res, 0, 200)];

        $data = json_decode($res, true);
        $text = $data['choices'][0]['message']['content'] ?? '';
        $text = preg_replace('/<think>.*?<\/think>/s', '', $text);
        return [trim($text, " \t\n\r\"'"), ''];
    }
    return ['', 'Rate limited 3 times'];
}

// Returns the first candidate model that responds, or an error listing each failure.
function pick_model(array $models): array
{
    $errors = [];
    foreach (array_unique($models) as $model) {
        [, $err] = groq_chat($model, 'Reply with the word OK.', 'Test');
        if ($err === '') return [$model, ''];
        $errors[] = "$model → $err";
    }
    return ['', implode("\n", $errors)];
}

// Returns a reason the Maa output is unusable, or '' if it passes the automatic checks.
function maa_problem(string $maa, string $en): string
{
    if ($maa === '') return 'empty output';
    if (strcasecmp(trim($maa), trim($en)) === 0) return 'returned unchanged English';

    $words = preg_split('/[\s,.;:!?()]+/u', mb_strtolower($maa), -1, PREG_SPLIT_NO_EMPTY);
    $count = count($words);

    if ($count >= 8 && count(array_unique($words)) / $count < 0.35) return 'repetitive output';
    if (preg_match('/(\b\S+\b)(?:[\s-]+\1\b){3,}/u', $maa)) return 'repeated word loop';

    if (mb_strlen($en) >= 20) {
        $ratio = mb_strlen($maa) / mb_strlen($en);
        if ($ratio < 0.4 || $ratio > 3.5) return 'length very different from source';
    }

    $swahili = ['ya', 'wa', 'kwa', 'ni', 'katika', 'kuwa', 'cha', 'za', 'hii', 'hiyo', 'pia', 'sana',
                'wakati', 'mtoto', 'watoto', 'afya', 'kituo', 'ugonjwa', 'magonjwa', 'au', 'kama',
                'lakini', 'tafadhali', 'zaidi', 'yako', 'wako', 'hapa', 'dalili', 'matibabu'];
    if ($count >= 4) {
        $hits = count(array_filter($words, fn($w) => in_array($w, $swahili, true)));
        if ($hits / $count > 0.25) return 'output looks like Kiswahili, not Maa';
    }

    preg_match_all('/%[sd%]/', $en,  $p_en);
    preg_match_all('/%[sd%]/', $maa, $p_maa);
    if ($p_en[0] !== $p_maa[0]) return 'placeholders (%s / %d) changed';

    return '';
}

// Translates one English text into Maa, back-translates it and scores the round trip.
// Returns ['maa' => ..., 'back' => ..., 'score' => int, 'reason' => '' if accepted].
function process_text(string $model, string $en): array
{
    $result = ['maa' => '', 'back' => '', 'score' => 0, 'reason' => ''];

    [$maa, $err] = groq_chat($model,
        'You translate public health education material for Samburu communities in Kenya into the Samburu '
      . 'dialect of the Maa language. Use simple everyday Samburu Maa. Keep medical terms and abbreviations '
      . 'that have no common Maa word (for example malaria, HIV, TB, ORS, WHO, numbers) in their original form. '
      . 'Keep placeholders such as %s and %d exactly as they are. Do not answer in Kiswahili or English. '
      . 'Return only the Maa translation, with no notes or explanations.',
        $en);
    if ($err !== '') return ['reason' => "translation request failed ($err)"] + $result;

    $result['maa'] = $maa;
    $problem = maa_problem($maa, $en);
    if ($problem !== '') return ['reason' => $problem] + $result;

    [$back, $err] = groq_chat($model,
        'Translate the following Maa (Samburu) text into English as literally as possible. '
      . 'Return only the English translation.',
        $maa, 0.0);
    if ($err !== '') return ['reason' => "back-translation failed ($err)"] + $result;
    $result['back'] = $back;

    [$verdict, $err] = groq_chat($model,
        'You check translations of health information. Compare the ORIGINAL English text with the '
      . 'BACK-TRANSLATION. Rate from 1 to 5 how well the back-translation preserves the full meaning, '
      . 'including every medical instruction, number, warning and who should do what. '
      . '5 = same meaning, 4 = minor wording differences only, 3 = some meaning lost or changed, '
      . '2 = mostly different, 1 = unrelated. Respond only with JSON like {"score": 4}.',
        "ORIGINAL:\n$en\n\nBACK-TRANSLATION:\n$back", 0.0);
    if ($err !== '') return ['reason' => "scoring failed ($err)"] + $result;

    if (preg_match('/"score"\s*:\s*([1-5])/', $verdict, $m) || preg_match('/\b([1-5])\b/', $verdict, $m)) {
        $result['score'] = (int)$m[1];
    }
    if ($result['score'] < MIN_SCORE) {
        $result['reason'] = 'back-translation score ' . $result['score'] . '/5';
    }
    return $result;
}

// Appends one row to the review log, creating the file with a header row if needed.
function log_review(string $file, string $source, string $en, array $r): void
{
    $new = !file_exists($file);
    $fh  = fopen($file, 'a');
    if ($new) {
        fwrite($fh, "\xEF\xBB\xBF");
        fputcsv($fh, ['date', 'source', 'english', 'maa', 'back_translation', 'score', 'status']);
    }
    fputcsv($fh, [
        date('Y-m-d H:i'), $source, $en, $r['maa'], $r['back'], $r['score'],
        $r['reason'] === '' ? 'accepted' : 'rejected: ' . $r['reason'],
    ]);
    fclose($fh);
}

// Writes the Maa interface dictionary as a PHP file.
function save_maa_dict(string $file, array $dict): void
{
    file_put_contents($file,
        "<?php\n// Maa (Samburu) translations of the interface text, keyed by the English text.\n"
      . "// Generated by scripts/translate_maa.php; review details are in scripts/maa_review.csv.\n\n"
      . 'return ' . var_export($dict, true) . ";\n");
}

$rejected = file_exists($rejected_file) ? (json_decode(file_get_contents($rejected_file), true) ?: []) : [];
$save_rejected = fn() => file_put_contents($rejected_file, json_encode($rejected, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo '<pre style="font:14px/1.55 monospace;white-space:pre-wrap">';

if ($part !== 'ui' && $part !== 'content') {
    echo "Maa translation tool\n\n";
    echo "<a href=\"?part=ui\">Translate interface text (menus, headings, buttons)</a>\n";
    echo "<a href=\"?part=content\">Translate health content (articles, myths, symptoms)</a>\n\n";
    echo "Each run handles $limit items. Reload the page to continue with the next batch.\n";
    echo "Add &retry=1 to the address to try previously rejected items again.\n";
    echo '</pre>';
    exit;
}

[$model, $model_err] = pick_model($candidate_models);
if ($model === '') {
    echo "No Groq model responded:\n" . htmlspecialchars($model_err) . "\n</pre>";
    exit;
}
echo "Model: $model | Batch size: $limit | Minimum score: " . MIN_SCORE . "/5\n\n";

$accepted = $failed = $pending = $done = 0;

// Shows one result line and returns whether it was accepted.
$report = function (string $label, array $r) use (&$accepted, &$failed): bool {
    if ($r['reason'] === '') {
        echo '✓ ' . htmlspecialchars($label) . ' [' . $r['score'] . '/5] → ' . htmlspecialchars(mb_substr($r['maa'], 0, 60)) . "\n";
        $accepted++;
        return true;
    }
    echo '✗ ' . htmlspecialchars($label) . ' — ' . htmlspecialchars($r['reason']) . ", kept English\n";
    $failed++;
    return false;
};

if ($part === 'ui') {
    $sw  = require $sw_dict_file;
    $maa = file_exists($maa_dict_file) ? require $maa_dict_file : [];

    foreach (array_keys($sw) as $en) {
        $key = 'ui:' . md5($en);
        if (isset($maa[$en]) || (isset($rejected[$key]) && !$retry)) continue;
        if ($done >= $limit) { $pending++; continue; }

        $r = process_text($model, $en);
        log_review($review_file, 'interface', $en, $r);
        if ($report('UI "' . mb_substr($en, 0, 40) . '"', $r)) {
            $maa[$en] = $r['maa'];
            save_maa_dict($maa_dict_file, $maa);
            unset($rejected[$key]);
        } else {
            $rejected[$key] = $r['reason'];
        }
        $save_rejected();
        $done++;
        sleep(2);
    }
}

if ($part === 'content') {
    foreach ($tables as $table => $fields) {
        $rows = $conn->query("SELECT * FROM `$table` ORDER BY id")->fetch_all(MYSQLI_ASSOC);

        foreach ($rows as $row) {
            foreach ($fields as $f) {
                $en  = trim($row[$f] ?? '');
                $cur = trim($row[$f . '_maa'] ?? '');
                $key = "$table.$f.{$row['id']}";

                if ($en === '' || ($cur !== '' && $cur !== $en)) continue;
                if (isset($rejected[$key]) && !$retry) continue;
                if ($done >= $limit) { $pending++; continue; }

                $r = process_text($model, $en);
                log_review($review_file, $key, $en, $r);
                if ($report("$table #{$row['id']} $f", $r)) {
                    $col  = $f . '_maa';
                    $stmt = $conn->prepare("UPDATE `$table` SET `$col` = ? WHERE id = ?");
                    $stmt->bind_param('si', $r['maa'], $row['id']);
                    $stmt->execute();
                    $stmt->close();
                    unset($rejected[$key]);
                } else {
                    $rejected[$key] = $r['reason'];
                }
                $save_rejected();
                $done++;
                sleep(2);
            }
        }
    }
}

echo "\nAccepted: $accepted | Rejected: $failed | Still to do: $pending\n";
echo $pending > 0 ? "Reload this page to continue.\n" : "This part is finished.\n";
echo "Full log: scripts/maa_review.csv\n</pre>";