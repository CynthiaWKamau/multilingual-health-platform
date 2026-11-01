<?php
$is_cli = (php_sapi_name() === 'cli');

if (!$is_cli) {
    session_start();
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        http_response_code(403); exit('Access denied.');
    }
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Kiswahili Translation</title>
    <style>body{font-family:monospace;padding:2rem;background:#f8fdf9;color:#1a3d2b;}
    .ok{color:#2d6a4f;}.skip{color:#888;}.err{color:#c0392b;}</style></head><body>
    <h2>Kiswahili Bulk Translation — Google Translate</h2>';
    ob_start();
}

include_once __DIR__ . '/../config/database.php';
include_once __DIR__ . '/../config/translate.php';

if (!defined('GOOGLE_TRANSLATE_API_KEY') || GOOGLE_TRANSLATE_API_KEY === 'YOUR_GOOGLE_API_KEY_HERE') {
    $msg = 'GOOGLE_TRANSLATE_API_KEY is not set in config/database.php.';
    $is_cli ? print($msg . PHP_EOL) : print('<p class="err">' . $msg . '</p></body></html>') && ob_end_flush();
    exit(1);
}

$tables = [
    'health_education' => [
        ['title',   'title_sw'],
        ['content', 'content_sw'],
    ],
    'myth_facts' => [
        ['myth', 'myth_sw'],
        ['fact', 'fact_sw'],
    ],
    'symptom_guidance' => [
        ['symptom',           'symptom_sw'],
        ['possible_cause',    'possible_cause_sw'],
        ['self_care',         'self_care_sw'],
        ['when_to_seek_care', 'when_to_seek_care_sw'],
    ],
];

function log_sw(string $msg, string $type = ''): void
{
    global $is_cli;
    if ($is_cli) { echo $msg . PHP_EOL; return; }
    echo '<div class="' . $type . '">' . htmlspecialchars($msg) . '</div>';
    ob_flush(); flush();
}

function ensure_sw_col(mysqli $conn, string $table, string $col): void
{
    $stmt = $conn->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    $stmt->bind_param('ss', $table, $col);
    $stmt->execute();
    $stmt->bind_result($n);
    $stmt->fetch();
    $stmt->close();
    if (!$n) $conn->query("ALTER TABLE `{$table}` ADD COLUMN `{$col}` TEXT NULL DEFAULT NULL");
}

$translated = $skipped = $errors = 0;

foreach ($tables as $table => $fields) {
    log_sw("=== {$table} ===");
    foreach ($fields as [$eng, $sw]) ensure_sw_col($conn, $table, $sw);

    $where = implode(' OR ', array_map(fn($f) => "(`{$f[1]}` IS NULL OR `{$f[1]}` = '')", $fields));
    $cols  = implode(', ', array_map(fn($f) => "`{$f[0]}`", $fields));
    $rows  = $conn->query("SELECT id, {$cols} FROM `{$table}` WHERE {$where} LIMIT 500")->fetch_all(MYSQLI_ASSOC);

    if (empty($rows)) { log_sw('  All done — nothing to translate.', 'skip'); continue; }
    log_sw('  ' . count($rows) . ' row(s) to translate.');

    foreach ($rows as $row) {
        $id = (int)$row['id'];
        $updates = []; $params = []; $types = '';

        foreach ($fields as [$eng, $sw]) {
            if (!empty($row[$sw])) { $skipped++; continue; }
            $text = trim($row[$eng] ?? '');
            if (!$text) { $skipped++; continue; }

            log_sw("  Row {$id} — {$eng} → {$sw}...");
            $result = google_translate_to_sw($text);

            if (!$result) { log_sw("  Row {$id} — {$sw}: empty response.", 'err'); $errors++; continue; }

            $updates[] = "`{$sw}` = ?";
            $params[]  = $result;
            $types    .= 's';
            log_sw("  Row {$id} — {$sw}: OK", 'ok');
            $translated++;
            usleep(200000);
        }

        if ($updates) {
            $stmt = $conn->prepare("UPDATE `{$table}` SET " . implode(', ', $updates) . " WHERE id = ?");
            $params[] = $id; $types .= 'i';
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $stmt->close();
        }
    }
}

$summary = "Translated: {$translated} | Skipped: {$skipped} | Errors: {$errors}";
$is_cli ? print($summary . PHP_EOL) : print('<pre style="margin-top:1rem;background:#e8f5e9;padding:1rem">' . $summary . '</pre></body></html>') && ob_end_flush();