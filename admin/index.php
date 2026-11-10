<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

$full_name = $_SESSION['full_name'];

// Stats
$total_users    = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
$total_members  = $conn->query("SELECT COUNT(*) as c FROM users WHERE role = 'community_member'")->fetch_assoc()['c'];
$total_chw      = $conn->query("SELECT COUNT(*) as c FROM users WHERE role = 'chw'")->fetch_assoc()['c'];
$total_articles = $conn->query("SELECT COUNT(*) as c FROM health_education")->fetch_assoc()['c'];
$total_myths    = $conn->query("SELECT COUNT(*) as c FROM myth_facts")->fetch_assoc()['c'];
$total_symptoms = $conn->query("SELECT COUNT(*) as c FROM symptom_guidance")->fetch_assoc()['c'];

// Ensure suggestions table exists
$conn->query("
    CREATE TABLE IF NOT EXISTS content_suggestions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(200) NOT NULL,
        content TEXT NOT NULL,
        category VARCHAR(80),
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )
");
$pending_suggestions = $conn->query("SELECT COUNT(*) as c FROM content_suggestions WHERE status = 'pending'")->fetch_assoc()['c'];

// Recent users
$recent_users = $conn->query("SELECT full_name, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard — Samburu Health</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

<style>
/* ── Admin design tokens ── */
:root {
    /* Admin accent: navy / slate */
    --navy:        #1a237e;
    --navy-mid:    #283593;
    --navy-light:  #5c6bc0;
    --slate:       #eceff1;
    --slate-dark:  #cfd8dc;
    --gold:        #c9913d;
    --gold-light:  #e8b96a;
    --ink:         #0d0f1a;
    --white:       #ffffff;
    --text-body:   #263040;
    --text-muted:  #607d8b;
    --border:      #d8dde6;

    --bg:      #f4f6fb;
    --surface: var(--white);
    --fg:      var(--text-body);

    /* Shared status colours */
    --status-pending-bg:  #fff8ed; --status-pending-fg:  #b56a00;
    --status-approved-bg: #f0fff6; --status-approved-fg: #1a7c4a;
    --status-rejected-bg: #fff0f3; --status-rejected-fg: #b5294a;
}
@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) {
        --bg: #0d0f1a; --surface: #131828; --fg: #e8eaf6;
        --text-muted: #8fa0b5; --border: #222d4a;
        --status-pending-bg: #2a1800; --status-approved-bg: #0e2418; --status-rejected-bg: #2a0e16;
        color-scheme: dark;
    }
}
:root[data-theme="dark"] {
    --bg: #0d0f1a; --surface: #131828; --fg: #e8eaf6;
    --text-muted: #8fa0b5; --border: #222d4a;
    --status-pending-bg: #2a1800; --status-approved-bg: #0e2418; --status-rejected-bg: #2a0e16;
    color-scheme: dark;
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'Poppins', sans-serif;
    background: var(--bg); color: var(--fg);
    min-height: 100vh; display: flex; flex-direction: column;
}

/* ── Admin navbar ── */
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
    position: relative;
}
.nav-links a:hover { color: var(--white); }
.nav-logout {
    background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.22);
    color: var(--white) !important; padding: 5px 14px; border-radius: 6px; font-weight: 600 !important;
}
.nav-logout:hover { background: rgba(255,255,255,0.2) !important; }
.nav-badge {
    position: absolute; top: -7px; right: -10px;
    background: #e53935; color: #fff;
    font-size: 0.6rem; font-weight: 700;
    padding: 1px 5px; border-radius: 10px; min-width: 16px; text-align: center;
}

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
    text-transform: uppercase; color: var(--navy-light); color: #9fa8da;
    margin-bottom: 0.5rem;
}
.page-hero h1 {
    font-family: 'Lora', serif;
    font-size: clamp(1.5rem, 3.5vw, 2.2rem);
    font-weight: 700; color: var(--white); line-height: 1.25; margin-bottom: 0.5rem;
}
.page-hero p { font-size: 0.9rem; color: rgba(255,255,255,0.75); }

/* ── Main ── */
.content-wrap { flex: 1; padding: 2.5rem clamp(1.5rem, 7%, 5rem); }

/* ── Section header ── */
.section-header {
    font-family: 'Lora', serif; font-size: 1.05rem; font-weight: 700;
    color: var(--navy); margin-bottom: 1.1rem;
    padding-bottom: 0.6rem; border-bottom: 2px solid var(--border);
    display: flex; align-items: center; gap: 8px;
}
@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) .section-header { color: #9fa8da; }
}
:root[data-theme="dark"] .section-header { color: #9fa8da; }

/* ── Stats grid ── */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem; margin-bottom: 2.5rem;
}
.stat-card {
    background: var(--surface); border: 1.5px solid var(--border);
    border-radius: 14px; padding: 1.5rem 1.1rem;
    text-align: center; position: relative; overflow: hidden;
}
.stat-card::after {
    content: ''; position: absolute;
    left: 0; bottom: 0; width: 100%; height: 3px;
    background: linear-gradient(90deg, var(--navy), var(--navy-light));
    opacity: 0.6;
}
.stat-icon-wrap {
    width: 44px; height: 44px; border-radius: 11px;
    background: rgba(26,35,126,0.08);
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 0.65rem;
}
.stat-number {
    font-size: 2rem; font-weight: 700; color: var(--navy);
    font-family: 'Lora', serif; line-height: 1;
    margin-bottom: 0.25rem;
}
@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) .stat-number { color: #9fa8da; }
    :root:not([data-theme="light"]) .stat-icon-wrap { background: rgba(92,107,192,0.15); }
}
:root[data-theme="dark"] .stat-number { color: #9fa8da; }
:root[data-theme="dark"] .stat-icon-wrap { background: rgba(92,107,192,0.15); }
.stat-label {
    font-size: 0.7rem; font-weight: 700; color: var(--text-muted);
    text-transform: uppercase; letter-spacing: 0.08em;
}

/* ── Quick-action cards ── */
.admin-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem; margin-bottom: 2.5rem;
}
.admin-card {
    background: var(--surface); border: 1.5px solid var(--border);
    border-radius: 14px; padding: 1.6rem 1.3rem;
    text-decoration: none; color: inherit;
    transition: border-color 0.2s, box-shadow 0.2s, transform 0.2s;
    display: flex; flex-direction: column; gap: 0.5rem;
    position: relative;
}
.admin-card:hover {
    border-color: var(--navy-light);
    box-shadow: 0 6px 20px rgba(26,35,126,0.13);
    transform: translateY(-3px);
}
.admin-card-icon {
    width: 46px; height: 46px; border-radius: 12px;
    background: rgba(26,35,126,0.08);
    display: flex; align-items: center; justify-content: center;
    margin-bottom: 0.2rem;
}
@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) .admin-card-icon { background: rgba(92,107,192,0.15); }
}
:root[data-theme="dark"] .admin-card-icon { background: rgba(92,107,192,0.15); }
.admin-card h3 {
    font-size: 0.93rem; font-weight: 600;
    color: var(--navy); display: flex; align-items: center; gap: 6px;
}
@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) .admin-card h3 { color: #9fa8da; }
}
:root[data-theme="dark"] .admin-card h3 { color: #9fa8da; }
.admin-card p { font-size: 0.8rem; color: var(--text-muted); line-height: 1.55; }
.card-badge {
    display: inline-block; background: #e53935; color: #fff;
    font-size: 0.68rem; font-weight: 700; padding: 1px 7px; border-radius: 10px;
}

/* ── Recent users table ── */
.table-wrap {
    background: var(--surface); border: 1.5px solid var(--border);
    border-radius: 14px; overflow: hidden;
}
.admin-table { width: 100%; border-collapse: collapse; }
.admin-table thead th {
    background: rgba(26,35,126,0.07);
    color: var(--text-muted); padding: 0.75rem 1.1rem;
    text-align: left; font-size: 0.72rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.08em;
    border-bottom: 1px solid var(--border);
}
.admin-table tbody td {
    padding: 0.85rem 1.1rem; font-size: 0.85rem; color: var(--fg);
    border-bottom: 1px solid var(--border);
}
.admin-table tbody tr:last-child td { border-bottom: none; }
.admin-table tbody tr:hover { background: rgba(26,35,126,0.03); }

/* ── Role badges ── */
.role-badge {
    display: inline-block; padding: 3px 10px; border-radius: 20px;
    font-size: 0.7rem; font-weight: 700; text-transform: capitalize;
}
.role-admin   { background: rgba(26,35,126,0.1);  color: var(--navy); }
.role-chw     { background: rgba(45,106,79,0.12);  color: #2d6a4f; }
.role-member  { background: rgba(201,145,61,0.12); color: #8a6020; }
@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) .role-admin  { background: rgba(92,107,192,0.2); color: #9fa8da; }
    :root:not([data-theme="light"]) .role-chw    { background: rgba(82,183,136,0.15); color: #52b788; }
    :root:not([data-theme="light"]) .role-member { background: rgba(232,185,106,0.15); color: #e8b96a; }
}
:root[data-theme="dark"] .role-admin  { background: rgba(92,107,192,0.2); color: #9fa8da; }
:root[data-theme="dark"] .role-chw    { background: rgba(82,183,136,0.15); color: #52b788; }
:root[data-theme="dark"] .role-member { background: rgba(232,185,106,0.15); color: #e8b96a; }

/* ── Footer ── */
footer {
    background: var(--ink); color: rgba(255,255,255,0.55);
    text-align: center; padding: 1.25rem 1rem;
    font-size: 0.8rem; line-height: 1.8;
}

/* ── Responsive ── */
@media (max-width: 1000px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 600px) {
    .admin-grid { grid-template-columns: 1fr; }
    .admin-table thead th:nth-child(2),
    .admin-table tbody td:nth-child(2) { display: none; }
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
        <a href="content.php">Manage Content</a>
        <a href="suggestions.php" style="position:relative;">
            Suggestions
            <?php if ($pending_suggestions > 0): ?>
                <span class="nav-badge"><?= $pending_suggestions ?></span>
            <?php endif; ?>
        </a>
        <a href="reports.php">Reports</a>
        <a href="../health/index.php">View Platform</a>
        <a href="../auth/logout.php" class="nav-logout">Logout</a>
    </nav>
</header>

<!-- Hero -->
<div class="page-hero">
    <div class="hero-inner">
        <p class="hero-eyebrow">Admin Control Panel</p>
        <h1>Welcome back, <?= htmlspecialchars($full_name) ?></h1>
        <p>Manage users, content, and platform settings from here.</p>
    </div>
</div>

<div class="content-wrap">

    <!-- Stats -->
    <p class="section-header">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/>
            <line x1="6" y1="20" x2="6" y2="14"/>
        </svg>
        Platform Overview
    </p>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon-wrap">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#5c6bc0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
            </div>
            <div class="stat-number"><?= $total_users ?></div>
            <div class="stat-label">Total Users</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon-wrap">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#5c6bc0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                </svg>
            </div>
            <div class="stat-number"><?= $total_members ?></div>
            <div class="stat-label">Community Members</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon-wrap">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#5c6bc0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                </svg>
            </div>
            <div class="stat-number"><?= $total_chw ?></div>
            <div class="stat-label">Health Workers</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon-wrap">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#5c6bc0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
                </svg>
            </div>
            <div class="stat-number"><?= $total_articles ?></div>
            <div class="stat-label">Health Articles</div>
        </div>
    </div>

    <!-- Quick actions -->
    <p class="section-header">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
            <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
        </svg>
        Quick Actions
    </p>
    <div class="admin-grid">
        <a href="users.php" class="admin-card">
            <div class="admin-card-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#5c6bc0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
            </div>
            <h3>Manage Users</h3>
            <p>View all users, change roles, and manage accounts.</p>
        </a>
        <a href="content.php" class="admin-card">
            <div class="admin-card-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#5c6bc0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
            </div>
            <h3>Manage Content</h3>
            <p>Add, edit, or remove health articles, myth entries, and symptom guidance.</p>
        </a>
        <a href="suggestions.php" class="admin-card">
            <div class="admin-card-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#5c6bc0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                </svg>
            </div>
            <h3>
                Content Suggestions
                <?php if ($pending_suggestions > 0): ?>
                    <span class="card-badge"><?= $pending_suggestions ?> pending</span>
                <?php endif; ?>
            </h3>
            <p>Review content suggestions submitted by Community Health Workers.</p>
        </a>
        <a href="reports.php" class="admin-card">
            <div class="admin-card-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#5c6bc0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 21H3V3"/><polyline points="7 14 11 10 14 13 20 7"/>
                </svg>
            </div>
            <h3>Platform Reports</h3>
            <p>Users, activity, health trends and content, with Excel export.</p>
        </a>
        <a href="../chw/report.php" class="admin-card">
            <div class="admin-card-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#5c6bc0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                </svg>
            </div>
            <h3>Community Health Report</h3>
            <p>The anonymous report Community Health Workers see.</p>
        </a>
        <a href="../health/index.php" class="admin-card">
            <div class="admin-card-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#5c6bc0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="2" y1="12" x2="22" y2="12"/>
                    <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                </svg>
            </div>
            <h3>View Platform</h3>
            <p>See the platform as a community member would see it.</p>
        </a>
    </div>

    <!-- Recent users -->
    <p class="section-header">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
        </svg>
        Recently Registered Users
    </p>
    <div class="table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Registered</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_users as $user): ?>
                <tr>
                    <td><?= htmlspecialchars($user['full_name']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td>
                        <span class="role-badge role-<?= $user['role'] === 'community_member' ? 'member' : htmlspecialchars($user['role']) ?>">
                            <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $user['role']))) ?>
                        </span>
                    </td>
                    <td><?= date('d M Y', strtotime($user['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>

<footer>
    <p>© 2026 Samburu Health Platform — Admin Panel</p>
</footer>

</body>
</html>