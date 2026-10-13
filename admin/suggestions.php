<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

$success = '';

// Handle approve/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $suggestion_id = (int)$_POST['suggestion_id'];
    $action        = $_POST['action'];

    if (in_array($action, ['approved', 'rejected'])) {
        $stmt = $conn->prepare("UPDATE content_suggestions SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $action, $suggestion_id);
        $stmt->execute();
        $success = 'Suggestion ' . $action . ' successfully.';
    }
}

$filter = $_GET['filter'] ?? 'pending';
$stmt   = $conn->prepare("
    SELECT cs.*, u.full_name, u.email
    FROM content_suggestions cs
    JOIN users u ON cs.user_id = u.id
    WHERE cs.status = ?
    ORDER BY cs.created_at DESC
");
$stmt->bind_param("s", $filter);
$stmt->execute();
$suggestions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$counts = [
    'pending'  => $conn->query("SELECT COUNT(*) as c FROM content_suggestions WHERE status = 'pending'")->fetch_assoc()['c'],
    'approved' => $conn->query("SELECT COUNT(*) as c FROM content_suggestions WHERE status = 'approved'")->fetch_assoc()['c'],
    'rejected' => $conn->query("SELECT COUNT(*) as c FROM content_suggestions WHERE status = 'rejected'")->fetch_assoc()['c'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Content Suggestions — Admin</title>
    <link rel="stylesheet" href="/multilingual-health-platform/assets/css/style.css">
    <style>
        .admin-banner { background: #1a237e; padding: 40px 7%; color: white; }
        .admin-banner h1 { font-size: 28px; color: white; margin-bottom: 6px; }
        .admin-banner p  { color: #c5cae9; }
        .admin-section   { padding: 40px 7%; }
        .filter-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 28px;
        }
        .filter-tab {
            padding: 9px 20px;
            border-radius: 20px;
            border: 1.5px solid #ddd;
            background: white;
            color: #607d8b;
            font-size: 14px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.2s;
        }
        .filter-tab:hover, .filter-tab.active {
            background: #1a237e;
            color: white;
            border-color: #1a237e;
        }
        .suggestion-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 24px;
            margin-bottom: 18px;
        }
        .suggestion-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }
        .suggestion-header h3 { font-size: 16px; color: #1a237e; }
        .suggestion-meta { font-size: 13px; color: #78909c; margin-bottom: 12px; }
        .suggestion-content {
            font-size: 14px;
            color: #37474f;
            line-height: 1.6;
            background: #f8f9fa;
            padding: 14px;
            border-radius: 6px;
            margin-bottom: 16px;
        }
        .suggestion-actions { display: flex; gap: 10px; }
        .btn-approve {
            padding: 8px 20px;
            background: #2e7d32;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: bold;
            cursor: pointer;
        }
        .btn-reject {
            padding: 8px 20px;
            background: #ffebee;
            color: #c62828;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: bold;
            cursor: pointer;
        }
        .status-badge {
            padding: 4px 12px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-pending  { background: #fff3e0; color: #e65100; }
        .status-approved { background: #e8f5e9; color: #2e7d32; }
        .status-rejected { background: #ffebee; color: #c62828; }
        .no-results { text-align: center; padding: 60px; color: #78909c; }
        .navbar-admin { background: #1a237e !important; }
        .navbar-admin .logo { color: #c5cae9 !important; }
        .navbar-admin nav a { color: rgba(255,255,255,0.7) !important; }
        .navbar-admin nav a:hover { color: white !important; }
        .navbar-admin .nav-login { border-color: rgba(255,255,255,0.5) !important; color: white !important; }
    </style>
</head>
<body>

    <header class="navbar navbar-admin">
        <div class="logo">Samburu Health — Admin</div>
        <nav>
            <a href="index.php">Dashboard</a>
            <a href="users.php">Users</a>
            <a href="content.php">Health Content</a>
            <a href="myths.php">Myth vs Fact</a>
            <a href="symptoms.php">Symptoms</a>
            <a href="suggestions.php">Suggestions</a>
            <a href="../auth/logout.php" class="nav-login">Logout</a>
        </nav>
    </header>

    <div class="admin-banner">
        <h1>💬 Content Suggestions</h1>
        <p>Review content suggestions submitted by Community Health Workers.</p>
    </div>

    <div class="admin-section">

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <div class="filter-tabs">
            <a href="?filter=pending"  class="filter-tab <?= $filter === 'pending'  ? 'active' : '' ?>">⏳ Pending (<?= $counts['pending'] ?>)</a>
            <a href="?filter=approved" class="filter-tab <?= $filter === 'approved' ? 'active' : '' ?>">✅ Approved (<?= $counts['approved'] ?>)</a>
            <a href="?filter=rejected" class="filter-tab <?= $filter === 'rejected' ? 'active' : '' ?>">❌ Rejected (<?= $counts['rejected'] ?>)</a>
        </div>

        <?php if (empty($suggestions)): ?>
            <div class="no-results">
                <p>No <?= $filter ?> suggestions found.</p>
            </div>
        <?php else: ?>
            <?php foreach ($suggestions as $s): ?>
            <div class="suggestion-card">
                <div class="suggestion-header">
                    <h3><?= htmlspecialchars($s['title']) ?></h3>
                    <span class="status-badge status-<?= $s['status'] ?>"><?= ucfirst($s['status']) ?></span>
                </div>
                <p class="suggestion-meta">
                    Submitted by <strong><?= htmlspecialchars($s['full_name']) ?></strong>
                    (<?= htmlspecialchars($s['email']) ?>) ·
                    <?= date('d M Y', strtotime($s['created_at'])) ?>
                    <?php if ($s['category']): ?>
                        · Category: <strong><?= htmlspecialchars($s['category']) ?></strong>
                    <?php endif; ?>
                </p>
                <div class="suggestion-content">
                    <?= htmlspecialchars($s['content']) ?>
                </div>
                <?php if ($s['status'] === 'pending'): ?>
                <div class="suggestion-actions">
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="suggestion_id" value="<?= $s['id'] ?>">
                        <input type="hidden" name="action" value="approved">
                        <button type="submit" class="btn-approve">✅ Approve</button>
                    </form>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="suggestion_id" value="<?= $s['id'] ?>">
                        <input type="hidden" name="action" value="rejected">
                        <button type="submit" class="btn-reject">❌ Reject</button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </div>

    <footer>
        <p>© 2026 Samburu Health Platform — Admin Panel</p>
    </footer>

</body>
</html>