<?php
// Activity log table
function activity_table(mysqli $conn): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    $conn->query("
        CREATE TABLE IF NOT EXISTS activity_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            activity_type ENUM('symptom_check', 'myth_check', 'article_view') NOT NULL,
            detail VARCHAR(255) NOT NULL,
            result VARCHAR(50) NULL,
            ref_id INT NULL,
            language VARCHAR(20) NOT NULL DEFAULT 'english',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user (user_id),
            INDEX idx_type_date (activity_type, created_at),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    $ready = true;
}

// Record activity
function log_activity(mysqli $conn, string $type, string $detail, ?string $result = null, ?int $ref_id = null): void
{
    if (empty($_SESSION['user_id'])) {
        return;
    }

    activity_table($conn);

    $uid    = (int)$_SESSION['user_id'];
    $lang   = $_SESSION['language'] ?? 'english';
    $detail = mb_substr(trim($detail), 0, 255);

    $stmt = $conn->prepare(
        "INSERT INTO activity_log (user_id, activity_type, detail, result, ref_id, language) VALUES (?, ?, ?, ?, ?, ?)"
    );
    if ($stmt) {
        $stmt->bind_param('isssis', $uid, $type, $detail, $result, $ref_id, $lang);
        $stmt->execute();
        $stmt->close();
    }
}

// Report period in days
function report_days(): int
{
    $days = (int)($_GET['days'] ?? 30);
    return in_array($days, [7, 30, 90, 365], true) ? $days : 30;
}

// Prepared query rows
function report_rows(mysqli $conn, string $sql, string $types = '', ...$params): array
{
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

// Horizontal bar chart
function bar_chart(array $rows, string $label_key, string $empty_text, string $count_key = 'n'): string
{
    if (!$rows) {
        return '<p class="empty">' . htmlspecialchars($empty_text) . '</p>';
    }

    $max  = max(array_map('intval', array_column($rows, $count_key))) ?: 1;
    $html = '<div class="bars">';
    foreach ($rows as $r) {
        $n    = (int)$r[$count_key];
        $pct  = max(2, round($n / $max * 100));
        $html .= '<div class="bar-row">'
               . '<span class="bar-label">' . htmlspecialchars((string)$r[$label_key]) . '</span>'
               . '<span class="bar-track"><span class="bar-fill" style="width:' . $pct . '%"></span></span>'
               . '<span class="bar-num">' . $n . '</span>'
               . '</div>';
    }
    return $html . '</div>';
}
