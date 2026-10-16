<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

$success = '';
$error   = '';

// Handle role change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_role'])) {
    $target_id = (int)$_POST['user_id'];
    $new_role  = $_POST['new_role'];
    $allowed   = ['community_member', 'chw', 'admin'];

    if (in_array($new_role, $allowed)) {
        $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->bind_param("si", $new_role, $target_id);
        $stmt->execute();
        $success = 'User role updated successfully.';
    }
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $target_id = (int)$_POST['user_id'];
    if ($target_id !== $_SESSION['user_id']) {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $target_id);
        $stmt->execute();
        $success = 'User deleted successfully.';
    } else {
        $error = 'You cannot delete your own account.';
    }
}

// Search / list
$search = $_GET['search'] ?? '';
if (!empty($search)) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE full_name LIKE ? OR email LIKE ? ORDER BY created_at DESC");
    $like = '%' . $search . '%';
    $stmt->bind_param("ss", $like, $like);
} else {
    $stmt = $conn->prepare("SELECT * FROM users ORDER BY created_at DESC");
}
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Users — Admin</title>

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
.hero-eyebrow { font-size: 0.72rem; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; color: #9fa8da; margin-bottom: 0.5rem; }
.page-hero h1 {
    font-family: 'Lora', serif; font-size: clamp(1.5rem, 3.5vw, 2rem);
    font-weight: 700; color: var(--white); line-height: 1.25; margin-bottom: 0.5rem;
}
.page-hero p { font-size: 0.9rem; color: rgba(255,255,255,0.75); }

/* ── Main ── */
.content-wrap { flex: 1; padding: 2.5rem clamp(1.5rem, 7%, 5rem); }

/* ── Alerts ── */
.alert {
    padding: 0.8rem 1rem; border-radius: 8px; font-size: 0.875rem;
    margin-bottom: 1.25rem; display: flex; align-items: center; gap: 8px;
}
.alert-success { background: #f0fff6; border: 1px solid #a3e4c0; color: #1a7c4a; }
.alert-error   { background: #fff0f3; border: 1px solid #f4b8c4; color: #b5294a; }
@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) .alert-success { background: #0e2418; border-color: #1a5430; }
    :root:not([data-theme="light"]) .alert-error   { background: #2a0e16; border-color: #5a2030; }
}
:root[data-theme="dark"] .alert-success { background: #0e2418; border-color: #1a5430; }
:root[data-theme="dark"] .alert-error   { background: #2a0e16; border-color: #5a2030; }

/* ── Top bar ── */
.top-bar {
    display: flex; justify-content: space-between; align-items: center;
    gap: 1rem; margin-bottom: 1.25rem; flex-wrap: wrap;
}
.user-count { font-size: 0.83rem; color: var(--text-muted); }
.search-form { display: flex; gap: 8px; }
.search-form input {
    padding: 0.6rem 0.9rem;
    border: 1.5px solid var(--border); border-radius: 8px;
    font-family: 'Poppins', sans-serif; font-size: 0.85rem;
    background: var(--surface); color: var(--fg);
    outline: none; transition: border-color 0.2s; width: 230px;
}
.search-form input:focus { border-color: var(--navy-light); }
.search-form input::placeholder { color: var(--text-muted); }
.btn-search {
    padding: 0.6rem 1rem; background: var(--navy); color: var(--white);
    border: none; border-radius: 8px;
    font-family: 'Poppins', sans-serif; font-size: 0.85rem; font-weight: 600;
    cursor: pointer; display: flex; align-items: center; gap: 5px; transition: background 0.2s;
}
.btn-search:hover { background: var(--navy-mid); }

/* ── Table ── */
.table-wrap {
    background: var(--surface); border: 1.5px solid var(--border);
    border-radius: 14px; overflow: hidden; overflow-x: auto;
}
.users-table { width: 100%; border-collapse: collapse; min-width: 700px; }
.users-table thead th {
    background: rgba(26,35,126,0.07);
    color: var(--text-muted); padding: 0.75rem 1rem;
    text-align: left; font-size: 0.7rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.08em;
    border-bottom: 1px solid var(--border);
}
.users-table tbody td {
    padding: 0.85rem 1rem; font-size: 0.83rem; color: var(--fg);
    border-bottom: 1px solid var(--border); vertical-align: middle;
}
.users-table tbody tr:last-child td { border-bottom: none; }
.users-table tbody tr:hover { background: rgba(26,35,126,0.03); }

/* ── Role badges ── */
.role-badge {
    display: inline-block; padding: 3px 9px; border-radius: 20px;
    font-size: 0.68rem; font-weight: 700; text-transform: capitalize;
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

/* ── Action controls ── */
.action-row { display: inline-flex; align-items: center; gap: 6px; flex-wrap: nowrap; }
.role-select {
    padding: 5px 8px;
    border: 1.5px solid var(--border); border-radius: 6px;
    font-family: 'Poppins', sans-serif; font-size: 0.78rem; color: var(--fg);
    background: var(--bg); outline: none; cursor: pointer;
    transition: border-color 0.2s;
}
.role-select:focus { border-color: var(--navy-light); }
.btn-sm {
    padding: 5px 12px; border: none; border-radius: 6px;
    font-family: 'Poppins', sans-serif; font-size: 0.75rem; font-weight: 600;
    cursor: pointer; transition: opacity 0.2s; white-space: nowrap;
}
.btn-save   { background: var(--navy); color: var(--white); }
.btn-save:hover { opacity: 0.85; }
.btn-delete { background: #fff0f3; color: #b5294a; border: 1px solid #f4b8c4; }
.btn-delete:hover { background: #ffe0e6; }
@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) .btn-delete { background: #2a0e16; border-color: #5a2030; color: #f48fb1; }
}
:root[data-theme="dark"] .btn-delete { background: #2a0e16; border-color: #5a2030; color: #f48fb1; }

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
        <p class="hero-eyebrow">Admin — User Management</p>
        <h1>Manage Users</h1>
        <p>View, search, change roles, and manage all platform users.</p>
    </div>
</div>

<div class="content-wrap">

    <?php if ($success): ?>
        <div class="alert alert-success">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><polyline points="9 12 11 14 15 10"/></svg>
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="top-bar">
        <p class="user-count"><?= count($users) ?> user(s) found</p>
        <form method="GET" class="search-form">
            <input type="text" name="search"
                placeholder="Search by name or email…"
                value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn-search">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                Search
            </button>
        </form>
    </div>

    <div class="table-wrap">
        <table class="users-table">
            <thead>
                <tr>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Current Role</th>
                    <th>Language</th>
                    <th>Registered</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= htmlspecialchars($user['full_name']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><?= htmlspecialchars($user['phone'] ?? '—') ?></td>
                    <td>
                        <span class="role-badge role-<?= $user['role'] === 'community_member' ? 'member' : htmlspecialchars($user['role']) ?>">
                            <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $user['role']))) ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars(ucfirst($user['preferred_language'] ?? '—')) ?></td>
                    <td><?= date('d M Y', strtotime($user['created_at'])) ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                            <div class="action-row">
                                <select name="new_role" class="role-select">
                                    <option value="community_member" <?= $user['role'] === 'community_member' ? 'selected' : '' ?>>Member</option>
                                    <option value="chw"              <?= $user['role'] === 'chw'              ? 'selected' : '' ?>>CHW</option>
                                    <option value="admin"            <?= $user['role'] === 'admin'            ? 'selected' : '' ?>>Admin</option>
                                </select>
                                <button type="submit" name="change_role" class="btn-sm btn-save">Save</button>
                                <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                <button type="submit" name="delete_user" class="btn-sm btn-delete"
                                    onclick="return confirm('Delete <?= htmlspecialchars(addslashes($user['full_name'])) ?>? This cannot be undone.')">Delete</button>
                                <?php endif; ?>
                            </div>
                        </form>
                    </td>
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