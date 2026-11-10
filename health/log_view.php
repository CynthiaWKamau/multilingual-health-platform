<?php
session_start();
include '../config/database.php';
include '../config/activity.php';

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(204);
    exit();
}

// Article view
$id   = (int)($_POST['id'] ?? 0);
$rows = report_rows($conn, "SELECT id, title, category FROM health_education WHERE id = ?", 'i', $id);

if ($rows) {
    log_activity($conn, 'article_view', $rows[0]['title'], $rows[0]['category'], (int)$rows[0]['id']);
}

http_response_code(204);
