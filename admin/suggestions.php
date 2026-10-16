<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

$success = '';

// Handle approve / reject
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
$stmt = $conn->prepare("
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

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

<style>
/* ── Admin design tokens ── */
:root {
    --navy:        #1a237e;
    --navy-mid:    #283593;
    --navy-light:  #5c6bc0;
    --ink:         #0d0f1a;
    --white:       #ffffff;
    --text-body:   #263040;
    --text-muted:  #607d8b;
    --border:      #d8dde6;
    --bg:          #f4f6fb;
    --surface:     var(--white);
    --fg:          var(--text-body);
}
@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) {
        --bg: #0d0f1a; --surface: #131828; --fg: #e8eaf6;
        --text-muted: #8fa0b5; --border: #222d4a;
        color-scheme: dark;
    }
}
:root[data-theme="dark"] {
    --bg: #0d0f1a; --surface: #131828; --fg: #e8eaf6;
    --text-muted: #8fa0b5; --border: #222d4a;
    color-scheme: dark;
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'Poppins', sans-serif;
    background: var(--bg); color: var(--fg);
    min-height: 100vh; display: flex; flex-direction: column;
}

/* ── Navbar ── */
.navbar {
    position: sticky; top: 0; z-index: 200;
    background: var(--navy);
    display: flex; align-items: center; justify-content: space-between;
    padding: 0 clamp(1rem, 5vw, 3rem); height: 64px;
    box-shadow: 0 2px 14px rgba(26,35,126,0.35);
}
.logo {
    font-family: 'Lora', serif; font-size: 1.05rem; font-weight: 700;
    color: var(--white); display: flex; align-items: center; gap: 10px;
    text-decoration: none; flex-shrink: 0;
}
.admin-role-pill {
    font-size: 0.62rem; font-weight: 700; letter-spacing: 0.1em;
    text-transform: uppercase; padding: 2px 8px; border-radius: 20px;
    background: var(--navy-light); color: var(--white);
}
.nav-links { display: flex; align-items: center; gap: 0.9rem; flex-wrap: wrap; }
.nav-links a {
    color: rgba(255,255,255,0.78); text-decoration: none;
    font-size: 0.82rem; font-weight: 500; white-space: nowrap; transition: color 0.2s;
}
.nav-links a:hover { color: var(--white); }
.nav-logout {
    background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.22);
    color: var(--white) !important; padding: 5px 14px; border-radius: 6px; font-weight: 600 !important;
}
.nav-logout:hover { background: rgba(255,255,255,0.2) !important; }

/* ── Hero ── */
.page-hero {
    background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 55%, #3949ab 100%);
    padding: 3rem clamp(1.5rem, 7%, 5rem); position: relative; overflow: hidden;
}
.page-hero::before {
    content: ''; position: absolute;
    width: 420px; height: 420px; border-radius: 50%;
    background: radial-gradient(circle, rgba(92,107,192,0.18), transparent 65%);
    top: -100px; right: -60px; pointer-events: none;
}
.hero-inner { position: relative; z-index: 1; }
.hero-eyebrow {
    font-size: 0.72rem; font-weight: 700; letter-spacing: 0.14em;
    text-transform: uppercase; color: #9fa8da; margin-bottom: 0.5rem;
}
.page-hero h1 {
    font-family: 'Lora', serif;
    font-size: clamp(1.5rem, 3.5vw, 2rem);
    font-weight: 700; color: var(--white); line-height: 1.25; margin-bottom: 0.5rem;
}
.page-hero p { font-size: 0.9rem; color: rgba(255,255,255,0.75); }

/* ── Main ── */
.content-wrap { flex: 1; padding: 2.5rem clamp(1.5rem, 7%, 5rem); }

/* ── Alert ── */
.alert {
    padding: 0.8rem 1rem; border-radius: 8px;
    font-size: 0.875rem; margin-bottom: 1.25rem;
    display: flex; align-items: center; gap: 8px;
}
.alert-success { background: #f0fff6; border: 1px solid #a3e4c0; color: #1a7c4a; }
@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) .alert-success { background: #0e2418; border-color: #1a5430; }
}
:root[data-theme="dark"] .alert-success { background: #0e2418; border-color: #1a5430; }

/* ── Filter tabs ── */
.filter-tabs { display: flex; gap: 0.6rem; margin-bottom: 1.5rem; flex-wrap: wrap; }
.filter-tab {
    padding: 0.5rem 1.1rem; border-radius: 30px;
    border: 1.5px solid var(--border);
    background: var(--surface); color: var(--text-muted);
    font-size: 0.82rem; font-weight: 600;
    text-decoration: none; transition: all 0.2s;
    display: flex; align-items: center; gap: 5px;
}
.filter-tab:hover { border-color: var(--navy-light); color: var(--navy-light); }
.filter-tab.active { background: var(--navy); border-color: var(--navy); color: var(--white); }
.tab-count {
    background: rgba(255,255,255,0.22); color: inherit;
    font-size: 0.7rem; padding: 1px 6px; border-radius: 10px;
}
.filter-tab:not(.active) .tab-count { background: var(--bg); }
.filter-tab.active .tab-count { background: rgba(255,255,255,0.22); color: var(--white); }

/* Pending count highlight */
.filter-tab.tab-pending.active { background: var(--navy); }
.filter-tab.tab-pending:not(.active) .tab-count { background: #fff7ed; color: #b56a00; }

/* ── Suggestion cards ── */
.suggestion-card {
    background: var(--surface); border: 1.5px solid var(--border);
    border-radius: 14px; padding: 1.5rem; margin-bottom: 1rem;
    transition: border-color 0.2s;
}
.suggestion-card:hover { border-color: var(--navy-light); }
.suggestion-header {
    display: flex; justify-content: space-between; align-items: flex-start;
    gap: 1rem; margin-bottom: 0.5rem;
}
.suggestion-header h3 {
    font-size: 0.95rem; font-weight: 600; color: var(--navy);
}
@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) .suggestion-header h3 { color: #9fa8da; }
}
:root[data-theme="dark"] .suggestion-header h3 { color: #9fa8da; }
.suggestion-meta { font-size: 0.78rem; color: var(--text-muted); margin-bottom: 0.85rem; line-height: 1.7; }
.suggestion-meta strong { color: var(--fg); }
.suggestion-body {
    font-size: 0.85rem; color: var(--fg); line-height: 1.7;
    background: rgba(26,35,126,0.04); border-left: 3px solid var(--navy-light);
    padding: 0.75rem 1rem; border-radius: 0 8px 8px 0;
    margin-bottom: 1rem; white-space: pre-wrap;
}
@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) .suggestion-body { background: rgba(92,107,192,0.08); }
}
:root[data-theme="dark"] .suggestion-body { background: rgba(92,107,192,0.08); }
.suggestion-actions { display: flex; gap: 0.6rem; }
.btn-approve, .btn-reject {
    padding: 0.55rem 1.1rem;
    border: none; border-radius: 7px;
    font-family: 'Poppins', sans-serif;
    font-size: 0.82rem; font-weight: 600; cursor: pointer;
    display: flex; align-items: center; gap: 5px; transition: opacity 0.2s;
}
.btn-approve { background: #1a7c4a; color: var(--white); }
.btn-approve:hover { opacity: 0.88; }
.btn-reject  { background: #fff0f3; color: #b5294a; border: 1px solid #f4b8c4; }
.btn-reject:hover { background: #ffe0e6; }
@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) .btn-reject { background: #2a0e16; border-color: #5a2030; color: #f48fb1; }
}
:root[data-theme="dark"] .btn-reject { background: #2a0e16; border-color: #5a2030; color: #f48fb1; }

/* ── Status badges ── */
.status-badge {
    display: inline-block; padding: 3px 10px; border-radius: 20px;
    font-size: 0.7rem; font-weight: 700; white-space: nowrap;
}
.status-pending  { background: #fff8ed; color: #b56a00; }
.status-approved { background: #f0fff6; color: #1a7c4a; }
.status-rejected { background: #fff0f3; color: #b5294a; }
@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) .status-pending  { background: #2a1800; }
    :root:not([data-theme="light"]) .status-approved { background: #0e2418; }
    :root:not([data-theme="light"]) .status-rejected { background: #2a0e16; }
}
:root[data-theme="dark"] .status-pending  { background: #2a1800; }
:root[data-theme="dark"] .status-approved { background: #0e2418; }
:root[data-theme="dark"] .status-rejected { background: #2a0e16; }

/* ── Empty state ── */
.empty-state {
    background: var(--surface); border: 1.5px dashed var(--border);
    border-radius: 14px; padding: 3.5rem 2rem; text-align: center;
}
.empty-state p { font-size: 0.9rem; color: var(--text-muted); }

/* ── Footer ── */
footer {
    background: var(--ink); color: rgba(255,255,255,0.55);
    text-align: center; padding: 1.25rem 1rem; font-size: 0.8rem; line-height: 1.8;
}
</style>
</head>
<body>

<header class="navbar">
    <a href="index.php" class="logo">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#9fa8da" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/>
        </svg>
        Samburu Health
        <span class="admin-role-pill">Admin</span>
    </a>
    <nav class="nav-links">
        <a href="index.php">Dashboard</a>
        <a href="users.php">Users</a>
        <a href="content.php">Health Content</a>
        <a href="myths.php">Myth vs Fact</a>
        <a href="symptoms.php">Symptoms</a>
        <a href="suggestions.php">Suggestions</a>
        <a href="../auth/logout.php" class="nav-logout">Logout</a>
    </nav>
</header>

<div class="page-hero">
    <div class="hero-inner">
        <p class="hero-eyebrow">Admin — Content Review</p>
        <h1>Content Suggestions</h1>
        <p>Review and act on content suggestions submitted by Community Health Workers.</p>
    </div>
</div>

<div class="content-wrap">

    <?php if ($success): ?>
        <div class="alert alert-success">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><polyline points="9 12 11 14 15 10"/></svg>
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <!-- Filter tabs -->
    <div class="filter-tabs">
        <a href="?filter=pending"
           class="filter-tab tab-pending <?= $filter === 'pending'  ? 'active' : '' ?>">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12"/></svg>
            Pending
            <span class="tab-count"><?= $counts['pending'] ?></span>
        </a>
        <a href="?filter=approved"
           class="filter-tab <?= $filter === 'approved' ? 'active' : '' ?>">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><polyline points="9 12 11 14 15 10"/></svg>
            Approved
            <span class="tab-count"><?= $counts['approved'] ?></span>
        </a>
        <a href="?filter=rejected"
           class="filter-tab <?= $filter === 'rejected' ? 'active' : '' ?>">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            Rejected
            <span class="tab-count"><?= $counts['rejected'] ?></span>
        </a>
    </div>

    <?php if (empty($suggestions)): ?>
        <div class="empty-state">
            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="var(--border)" stroke-width="1.5" stroke-linecap="round" style="margin-bottom:0.75rem;">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
            <p>No <?= htmlspecialchars($filter) ?> suggestions found.</p>
        </div>
    <?php else: ?>
        <?php foreach ($suggestions as $s): ?>
        <div class="suggestion-card">
            <div class="suggestion-header">
                <h3><?= htmlspecialchars($s['title']) ?></h3>
                <span class="status-badge status-<?= htmlspecialchars($s['status']) ?>"><?= ucfirst($s['status']) ?></span>
            </div>
            <p class="suggestion-meta">
                Submitted by <strong><?= htmlspecialchars($s['full_name']) ?></strong>
                (<?= htmlspecialchars($s['email']) ?>) ·
                <?= date('d M Y', strtotime($s['created_at'])) ?>
                <?php if ($s['category']): ?>
                    · Category: <strong><?= htmlspecialchars(ucfirst($s['category'])) ?></strong>
                <?php endif; ?>
            </p>
            <div class="suggestion-body"><?= htmlspecialchars($s['content']) ?></div>
            <?php if ($s['status'] === 'pending'): ?>
            <div class="suggestion-actions">
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="suggestion_id" value="<?= $s['id'] ?>">
                    <input type="hidden" name="action" value="approved">
                    <button type="submit" class="btn-approve">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
                        Approve
                    </button>
                </form>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="suggestion_id" value="<?= $s['id'] ?>">
                    <input type="hidden" name="action" value="rejected">
                    <button type="submit" class="btn-reject">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        Reject
                    </button>
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