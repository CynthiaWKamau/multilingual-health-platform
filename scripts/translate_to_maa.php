<?php
$is_cli = (php_sapi_name() === 'cli');

if (!$is_cli) {
    session_start();
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        http_response_code(403); exit('Access denied.');
    }
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Maa Translation</title>
    <style>body{font-family:monospace;padding:2rem;background:#f8fdf9;color:#1a3d2b;}
    .ok{color:#2d6a4f;}.skip{color:#888;}.err{color:#c0392b;}</style></head><body>
    <h2>Maa Bulk Translation — Groq</h2>';
    ob_start();
}

include_once __DIR__ . '/../config/database.php';
include_once __DIR__ . '/../config/translate.php';

$tables = [
    'health_education' => [
        ['title',   'title_maa'],
        ['content', 'content_maa'],
    ],
    'myth_facts' => [
        ['myth', 'myth_maa'],
        ['fact', 'fact_maa'],
    ],
    'symptom_guidance' => [
        ['symptom',           'symptom_maa'],
        ['possible_cause',    'possible_cause_maa'],
        ['self_care',         'self_care_maa'],
        ['when_to_seek_care', 'when_to_seek_care_maa'],
    ],
];

function log_line(string $msg, string $type = ''): void
{
    global $is_cli;
    if ($is_cli) { echo $msg . PHP_EOL; return; }
    echo '<div class="' . $type . '">' . htmlspecialchars($msg) . '</div>';
    ob_flush(); flush();
}

function ensure_col(mysqli $conn, string $table, string $col): void
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
    log_line("=== {$table} ===");
    foreach ($fields as [$eng, $maa]) ensure_col($conn, $table, $maa);

    $where = implode(' OR ', array_map(fn($f) => "(`{$f[1]}` IS NULL OR `{$f[1]}` = '')", $fields));
    $cols  = implode(', ', array_map(fn($f) => "`{$f[0]}`", $fields));
    $rows  = $conn->query("SELECT id, {$cols} FROM `{$table}` WHERE {$where} LIMIT 200")->fetch_all(MYSQLI_ASSOC);

    if (empty($rows)) { log_line('  All done — nothing to translate.', 'skip'); continue; }
    log_line('  ' . count($rows) . ' row(s) to translate.');

    foreach ($rows as $row) {
        $id = (int)$row['id'];
        $updates = []; $params = []; $types = '';

        foreach ($fields as [$eng, $maa]) {
            if (!empty($row[$maa])) { $skipped++; continue; }
            $text = trim($row[$eng] ?? '');
            if (!$text) { $skipped++; continue; }

            log_line("  Row {$id} — {$eng} → {$maa}...");
            $result = groq_translate_to_maa($text);

            if (!$result) { log_line("  Row {$id} — {$maa}: empty response.", 'err'); $errors++; continue; }

            $updates[] = "`{$maa}` = ?";
            $params[]  = $result;
            $types    .= 's';
            log_line("  Row {$id} — {$maa}: OK", 'ok');
            $translated++;
            sleep(3);
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