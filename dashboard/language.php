<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$allowed = ['english', 'kiswahili', 'maa'];
$lang    = $_GET['lang'] ?? $_POST['lang'] ?? '';

if (in_array($lang, $allowed)) {
    // Update session
    $_SESSION['language'] = $lang;

    // Persist to DB so it survives new sessions
    $uid  = (int)$_SESSION['user_id'];
    $stmt = $conn->prepare("UPDATE users SET preferred_language = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param('si', $lang, $uid);
        $stmt->execute();
        $stmt->close();
    }
}

// Redirect back to wherever they came from, defaulting to dashboard
$redirect = $_GET['redirect'] ?? $_POST['redirect'] ?? '../dashboard/index.php';

// Safety: only allow relative redirects (no external URLs)
if (preg_match('/^https?:\/\//i', $redirect)) {
    $redirect = '../dashboard/index.php';
}

header('Location: ' . $redirect);
exit();
