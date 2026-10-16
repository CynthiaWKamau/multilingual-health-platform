<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

$success = '';
$error   = '';
$tab     = $_GET['tab'] ?? 'health';   // health | myths | symptoms

// ── Health Education CRUD ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['add_health'])) {
        $title     = trim($_POST['title']);
        $title_sw  = trim($_POST['title_sw']);
        $title_maa = trim($_POST['title_maa']);
        $content   = trim($_POST['content']);
        $content_sw  = trim($_POST['content_sw']);
        $content_maa = trim($_POST['content_maa']);
        $category  = $_POST['category'];

        if (empty($title) || empty($content)) {
            $error = 'Title and English content are required.';
        } else {
            $stmt = $conn->prepare("INSERT INTO health_education
                (title, title_sw, title_maa, content, content_sw, content_maa, category)
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssss", $title, $title_sw, $title_maa, $content, $content_sw, $content_maa, $category);
            $stmt->execute();
            $success = 'Health article added successfully.';
        }
    }

    if (isset($_POST['delete_health'])) {
        $id = (int)$_POST['item_id'];
        $stmt = $conn->prepare("DELETE FROM health_education WHERE id = ?");
        $stmt->bind_param("i", $id); $stmt->execute();
        $success = 'Article deleted.';
    }

    // ── Myth vs Fact CRUD ────────────────────────────────────────────────
    if (isset($_POST['add_myth'])) {
        $myth      = trim($_POST['myth']);
        $fact      = trim($_POST['fact']);
        $myth_sw   = trim($_POST['myth_sw']);
        $fact_sw   = trim($_POST['fact_sw']);
        $myth_maa  = trim($_POST['myth_maa']);
        $fact_maa  = trim($_POST['fact_maa']);
        $category  = $_POST['myth_category'];

        if (empty($myth) || empty($fact)) {
            $error = 'Myth and Fact (English) are required.';
        } else {
            $stmt = $conn->prepare("INSERT INTO myth_facts
                (myth, fact, myth_sw, fact_sw, myth_maa, fact_maa, category)
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssss", $myth, $fact, $myth_sw, $fact_sw, $myth_maa, $fact_maa, $category);
            $stmt->execute();
            $success = 'Myth vs Fact entry added.';
        }
    }

    if (isset($_POST['delete_myth'])) {
        $id = (int)$_POST['item_id'];
        $stmt = $conn->prepare("DELETE FROM myth_facts WHERE id = ?");
        $stmt->bind_param("i", $id); $stmt->execute();
        $success = 'Myth entry deleted.';
    }

    // ── Symptom Guidance CRUD ────────────────────────────────────────────
    if (isset($_POST['add_symptom'])) {
        $symptom        = trim($_POST['symptom']);
        $symptom_sw     = trim($_POST['symptom_sw']);
        $symptom_maa    = trim($_POST['symptom_maa']);
        $possible_cause = trim($_POST['possible_cause']);
        $self_care      = trim($_POST['self_care']);
        $when_to_seek   = trim($_POST['when_to_seek_care']);
        $urgency        = $_POST['urgency'];

        if (empty($symptom) || empty($possible_cause)) {
            $error = 'Symptom name and Possible Cause are required.';
        } else {
            $stmt = $conn->prepare("INSERT INTO symptom_guidance
                (symptom, symptom_sw, symptom_maa, possible_cause, self_care, when_to_seek_care, urgency)
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssss", $symptom, $symptom_sw, $symptom_maa, $possible_cause, $self_care, $when_to_seek, $urgency);
            $stmt->execute();
            $success = 'Symptom guidance entry added.';
        }
    }

    if (isset($_POST['delete_symptom'])) {
        $id = (int)$_POST['item_id'];
        $stmt = $conn->prepare("DELETE FROM symptom_guidance WHERE id = ?");
        $stmt->bind_param("i", $id); $stmt->execute();
        $success = 'Symptom entry deleted.';
    }

    // Keep the tab after POST
    $tab = $_POST['current_tab'] ?? $tab;
    header("Location: content.php?tab=" . urlencode($tab) . ($success ? "&msg=" . urlencode($success) : ""));
    exit();
}

// Pick up flash message from redirect
if (isset($_GET['msg'])) $success = htmlspecialchars($_GET['msg']);

// ── Fetch data for current tab ────────────────────────────────────────────
// Helper: run query and return rows, or empty array + set $error on failure
function safe_fetch(mysqli $db, string $sql, string &$err): array {
    $res = $db->query($sql);
    if ($res === false) {
        $err = 'DB error (' . htmlspecialchars($db->error) . '). '
             . 'Check that all three tables exist — see the SQL below.';
        return [];
    }
    return $res->fetch_all(MYSQLI_ASSOC);
}

$health_articles = safe_fetch($conn, "SELECT id, title, category, created_at FROM health_education ORDER BY created_at DESC", $error);
$myth_entries    = safe_fetch($conn, "SELECT id, myth, fact, category, created_at FROM myth_facts ORDER BY created_at DESC", $error);
$symptom_entries = safe_fetch($conn, "SELECT id, symptom, urgency, created_at FROM symptom_guidance ORDER BY created_at DESC", $error);

$counts = [
    'health'   => count($health_articles),
    'myths'    => count($myth_entries),
    'symptoms' => count($symptom_entries),
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Content — Admin</title>

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
    /* Category accents (matches user-facing pages) */
    --cat-maternal: #b5446e;
    --cat-child:    #1a5ca8;
    --cat-disease:  #c45c10;
    --cat-nutrition:#2d7d46;
    --cat-myth:     #7b3fa0;
    --cat-other:    #607d8b;
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
.nav-links a:hover, .nav-links a.active { color: var(--white); }
.nav-links a.active { border-bottom: 2px solid rgba(255,255,255,0.6); padding-bottom: 2px; }
.nav-logout {
    background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.22);
    color: var(--white) !important; padding: 5px 14px; border-radius: 6px;
    font-weight: 600 !important; border-bottom: none !important;
}
.nav-logout:hover { background: rgba(255,255,255,0.2) !important; }

/* ── Hero ── */
.page-hero {
    background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 55%, #3949ab 100%);
    padding: 2.5rem clamp(1.5rem, 7%, 5rem); position: relative; overflow: hidden;
}
.page-hero::before {
    content: ''; position: absolute;
    width: 380px; height: 380px; border-radius: 50%;
    background: radial-gradient(circle, rgba(92,107,192,0.18), transparent 65%);
    top: -80px; right: -50px; pointer-events: none;
}
.hero-inner { position: relative; z-index: 1; }
.hero-eyebrow { font-size: 0.72rem; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; color: #9fa8da; margin-bottom: 0.4rem; }
.page-hero h1 {
    font-family: 'Lora', serif; font-size: clamp(1.4rem, 3vw, 1.9rem);
    font-weight: 700; color: var(--white); line-height: 1.25; margin-bottom: 0.4rem;
}
.page-hero p { font-size: 0.875rem; color: rgba(255,255,255,0.72); }

/* ── Main ── */
.content-wrap { flex: 1; padding: 2rem clamp(1.5rem, 7%, 5rem); }

/* ── Alerts ── */
.alert {
    padding: 0.75rem 1rem; border-radius: 8px; font-size: 0.85rem;
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

/* ── Tab switcher ── */
.tab-bar {
    display: flex; gap: 0; margin-bottom: 2rem;
    background: var(--surface); border: 1.5px solid var(--border);
    border-radius: 12px; overflow: hidden;
}
.tab-btn {
    flex: 1; padding: 0.75rem 1rem; text-align: center;
    font-size: 0.83rem; font-weight: 600; text-decoration: none;
    color: var(--text-muted); background: transparent;
    border-right: 1px solid var(--border);
    transition: background 0.2s, color 0.2s;
    display: flex; align-items: center; justify-content: center; gap: 6px;
}
.tab-btn:last-child { border-right: none; }
.tab-btn:hover { background: rgba(26,35,126,0.05); color: var(--navy-light); }
.tab-btn.active {
    background: var(--navy); color: var(--white);
}
@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) .tab-btn.active { background: var(--navy-mid); }
}
:root[data-theme="dark"] .tab-btn.active { background: var(--navy-mid); }
.tab-count {
    font-size: 0.7rem; padding: 1px 7px; border-radius: 10px;
    background: rgba(255,255,255,0.2); color: inherit;
}
.tab-btn:not(.active) .tab-count { background: var(--bg); }

/* ── Two-column layout: form + list ── */
.panel { display: grid; grid-template-columns: 380px 1fr; gap: 1.5rem; align-items: start; }
@media (max-width: 900px) { .panel { grid-template-columns: 1fr; } }

/* ── Add-form card ── */
.form-card {
    background: var(--surface); border: 1.5px solid var(--border);
    border-radius: 14px; padding: 1.5rem; position: sticky; top: 80px;
}
.form-card h3 {
    font-family: 'Lora', serif; font-size: 1rem; font-weight: 700;
    color: var(--navy); margin-bottom: 0.2rem;
}
@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) .form-card h3 { color: #9fa8da; }
}
:root[data-theme="dark"] .form-card h3 { color: #9fa8da; }
.form-card > p { font-size: 0.78rem; color: var(--text-muted); margin-bottom: 1.1rem; line-height: 1.5; }

.form-group { display: flex; flex-direction: column; gap: 4px; margin-bottom: 0.85rem; }
.form-group label { font-size: 0.75rem; font-weight: 600; color: var(--fg); }
.form-group input,
.form-group select,
.form-group textarea {
    padding: 0.6rem 0.85rem;
    border: 1.5px solid var(--border); border-radius: 7px;
    font-family: 'Poppins', sans-serif; font-size: 0.83rem;
    background: var(--bg); color: var(--fg);
    outline: none; transition: border-color 0.2s; width: 100%;
}
.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus { border-color: var(--navy-light); }
.form-group textarea { resize: vertical; min-height: 80px; }
.lang-group {
    background: rgba(26,35,126,0.04); border: 1px solid var(--border);
    border-radius: 8px; padding: 0.75rem 0.85rem; margin-bottom: 0.85rem;
}
@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) .lang-group { background: rgba(92,107,192,0.07); }
}
:root[data-theme="dark"] .lang-group { background: rgba(92,107,192,0.07); }
.lang-group-label {
    font-size: 0.68rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase;
    color: var(--navy-light); margin-bottom: 0.5rem;
    display: flex; align-items: center; gap: 5px;
}
@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) .lang-group-label { color: #9fa8da; }
}
:root[data-theme="dark"] .lang-group-label { color: #9fa8da; }
.lang-group .form-group { margin-bottom: 0.6rem; }
.lang-group .form-group:last-child { margin-bottom: 0; }
.required { color: #b5294a; }

.btn-add {
    width: 100%; padding: 0.7rem; background: var(--navy); color: var(--white);
    border: none; border-radius: 8px;
    font-family: 'Poppins', sans-serif; font-size: 0.875rem; font-weight: 600;
    cursor: pointer; transition: background 0.2s; margin-top: 0.25rem;
    display: flex; align-items: center; justify-content: center; gap: 6px;
}
.btn-add:hover { background: var(--navy-mid); }

/* ── Content list ── */
.list-card {
    background: var(--surface); border: 1.5px solid var(--border);
    border-radius: 14px; overflow: hidden;
}
.list-header {
    padding: 1rem 1.2rem; border-bottom: 1px solid var(--border);
    display: flex; align-items: center; justify-content: space-between;
}
.list-header h4 {
    font-size: 0.85rem; font-weight: 700; color: var(--text-muted);
    text-transform: uppercase; letter-spacing: 0.06em;
}
.content-table { width: 100%; border-collapse: collapse; }
.content-table thead th {
    background: rgba(26,35,126,0.06);
    color: var(--text-muted); padding: 0.65rem 1.1rem;
    text-align: left; font-size: 0.68rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.07em;
    border-bottom: 1px solid var(--border);
}
.content-table tbody td {
    padding: 0.8rem 1.1rem; font-size: 0.83rem; color: var(--fg);
    border-bottom: 1px solid var(--border); vertical-align: middle;
}
.content-table tbody tr:last-child td { border-bottom: none; }
.content-table tbody tr:hover { background: rgba(26,35,126,0.025); }

/* truncate long text */
.cell-title { max-width: 240px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.cell-excerpt { max-width: 300px; font-size: 0.78rem; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

/* Category pills */
.cat-pill {
    display: inline-block; padding: 2px 9px; border-radius: 20px;
    font-size: 0.67rem; font-weight: 700; white-space: nowrap;
}
.cat-maternal  { background: rgba(181,68,110,0.1);  color: var(--cat-maternal); }
.cat-child     { background: rgba(26,92,168,0.1);   color: var(--cat-child); }
.cat-disease   { background: rgba(196,92,16,0.1);   color: var(--cat-disease); }
.cat-nutrition { background: rgba(45,125,70,0.1);   color: var(--cat-nutrition); }
.cat-myth      { background: rgba(123,63,160,0.1);  color: var(--cat-myth); }
.cat-other, .cat- { background: rgba(96,125,139,0.1); color: var(--cat-other); }

/* Urgency */
.urgency-high   { color: #b5294a; font-weight: 700; }
.urgency-medium { color: #b56a00; font-weight: 600; }
.urgency-low    { color: #2d7d46; font-weight: 600; }

/* Delete btn */
.btn-del {
    padding: 4px 12px; background: transparent; border: 1px solid var(--border);
    border-radius: 6px; font-size: 0.75rem; font-weight: 600; color: #b5294a;
    cursor: pointer; transition: background 0.2s, border-color 0.2s;
}
.btn-del:hover { background: #fff0f3; border-color: #f4b8c4; }
@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) .btn-del:hover { background: #2a0e16; border-color: #5a2030; }
}
:root[data-theme="dark"] .btn-del:hover { background: #2a0e16; border-color: #5a2030; }

.empty-row td { text-align: center; color: var(--text-muted); padding: 2.5rem !important; font-size: 0.85rem; }

/* ── Footer ── */
footer {
    background: var(--ink); color: rgba(255,255,255,0.55);
    text-align: center; padding: 1.25rem 1rem; font-size: 0.8rem; line-height: 1.8;
}

@media (max-width: 600px) {
    .tab-btn span.tab-label { display: none; }
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
        <a href="content.php" class="active">Manage Content</a>
        <a href="suggestions.php">Suggestions</a>
        <a href="../health/index.php">View Platform</a>
        <a href="../auth/logout.php" class="nav-logout">Logout</a>
    </nav>
</header>

<div class="page-hero">
    <div class="hero-inner">
        <p class="hero-eyebrow">Admin — Content Management</p>
        <h1>Manage Content</h1>
        <p>Add, review, and remove health articles, myth corrections, and symptom guidance.</p>
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

    <!-- Tab switcher -->
    <div class="tab-bar">
        <a href="?tab=health" class="tab-btn <?= $tab === 'health'   ? 'active' : '' ?>">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
            <span class="tab-label">Health Articles</span>
            <span class="tab-count"><?= $counts['health'] ?></span>
        </a>
        <a href="?tab=myths" class="tab-btn <?= $tab === 'myths'    ? 'active' : '' ?>">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="8" y1="12" x2="16" y2="12"/><line x1="12" y1="8" x2="12" y2="16"/></svg>
            <span class="tab-label">Myth vs Fact</span>
            <span class="tab-count"><?= $counts['myths'] ?></span>
        </a>
        <a href="?tab=symptoms" class="tab-btn <?= $tab === 'symptoms' ? 'active' : '' ?>">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            <span class="tab-label">Symptom Guidance</span>
            <span class="tab-count"><?= $counts['symptoms'] ?></span>
        </a>
    </div>

    <!-- ══ HEALTH ARTICLES tab ══ -->
    <?php if ($tab === 'health'): ?>
    <div class="panel">

        <!-- Add form -->
        <div class="form-card">
            <h3>Add Health Article</h3>
            <p>Fill in English first — Kiswahili and Maa are optional but recommended.</p>
            <form method="POST">
                <input type="hidden" name="current_tab" value="health">

                <div class="form-group">
                    <label>Category</label>
                    <select name="category">
                        <option value="maternal">Maternal Health</option>
                        <option value="child">Child Health</option>
                        <option value="disease">Common Diseases</option>
                        <option value="nutrition">Nutrition</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <div class="lang-group">
                    <p class="lang-group-label">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                        English <span class="required">*</span>
                    </p>
                    <div class="form-group">
                        <label>Title</label>
                        <input type="text" name="title" placeholder="Article title" required>
                    </div>
                    <div class="form-group">
                        <label>Content</label>
                        <textarea name="content" rows="4" placeholder="Full article content…" required></textarea>
                    </div>
                </div>

                <div class="lang-group">
                    <p class="lang-group-label">Kiswahili (optional)</p>
                    <div class="form-group">
                        <label>Kichwa (Title)</label>
                        <input type="text" name="title_sw" placeholder="Kichwa cha makala">
                    </div>
                    <div class="form-group">
                        <label>Maudhui (Content)</label>
                        <textarea name="content_sw" rows="3" placeholder="Maudhui kamili…"></textarea>
                    </div>
                </div>

                <div class="lang-group">
                    <p class="lang-group-label">Maa (optional)</p>
                    <div class="form-group">
                        <label>Title (Maa)</label>
                        <input type="text" name="title_maa" placeholder="Title in Maa">
                    </div>
                    <div class="form-group">
                        <label>Content (Maa)</label>
                        <textarea name="content_maa" rows="3" placeholder="Content in Maa…"></textarea>
                    </div>
                </div>

                <button type="submit" name="add_health" class="btn-add">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Add Article
                </button>
            </form>
        </div>

        <!-- List -->
        <div class="list-card">
            <div class="list-header">
                <h4>All Health Articles (<?= $counts['health'] ?>)</h4>
            </div>
            <div style="overflow-x:auto;">
            <table class="content-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Added</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($health_articles)): ?>
                        <tr class="empty-row"><td colspan="4">No health articles yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($health_articles as $a): ?>
                        <tr>
                            <td class="cell-title"><?= htmlspecialchars($a['title']) ?></td>
                            <td><span class="cat-pill cat-<?= htmlspecialchars($a['category']) ?>"><?= htmlspecialchars(ucfirst($a['category'])) ?></span></td>
                            <td style="white-space:nowrap;color:var(--text-muted);font-size:0.78rem;"><?= date('d M Y', strtotime($a['created_at'])) ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="current_tab" value="health">
                                    <input type="hidden" name="item_id" value="<?= $a['id'] ?>">
                                    <button type="submit" name="delete_health" class="btn-del"
                                        onclick="return confirm('Delete this article?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ══ MYTH VS FACT tab ══ -->
    <?php if ($tab === 'myths'): ?>
    <div class="panel">

        <div class="form-card">
            <h3>Add Myth vs Fact</h3>
            <p>Enter the misconception and the correct fact. English is required.</p>
            <form method="POST">
                <input type="hidden" name="current_tab" value="myths">

                <div class="form-group">
                    <label>Category</label>
                    <select name="myth_category">
                        <option value="maternal">Maternal Health</option>
                        <option value="child">Child Health</option>
                        <option value="disease">Common Diseases</option>
                        <option value="nutrition">Nutrition</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <div class="lang-group">
                    <p class="lang-group-label">English <span class="required">*</span></p>
                    <div class="form-group">
                        <label>Myth (the misconception)</label>
                        <textarea name="myth" rows="2" placeholder="e.g. Malaria is caused by cold weather" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Fact (the correction)</label>
                        <textarea name="fact" rows="2" placeholder="e.g. Malaria is caused by Plasmodium parasites…" required></textarea>
                    </div>
                </div>

                <div class="lang-group">
                    <p class="lang-group-label">Kiswahili (optional)</p>
                    <div class="form-group">
                        <label>Imani potofu (Myth)</label>
                        <textarea name="myth_sw" rows="2" placeholder="Imani potofu kwa Kiswahili…"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Ukweli (Fact)</label>
                        <textarea name="fact_sw" rows="2" placeholder="Ukweli kwa Kiswahili…"></textarea>
                    </div>
                </div>

                <div class="lang-group">
                    <p class="lang-group-label">Maa (optional)</p>
                    <div class="form-group">
                        <label>Myth (Maa)</label>
                        <textarea name="myth_maa" rows="2" placeholder="Myth in Maa…"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Fact (Maa)</label>
                        <textarea name="fact_maa" rows="2" placeholder="Fact in Maa…"></textarea>
                    </div>
                </div>

                <button type="submit" name="add_myth" class="btn-add">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Add Entry
                </button>
            </form>
        </div>

        <div class="list-card">
            <div class="list-header">
                <h4>All Myth vs Fact Entries (<?= $counts['myths'] ?>)</h4>
            </div>
            <div style="overflow-x:auto;">
            <table class="content-table">
                <thead>
                    <tr>
                        <th>Myth</th>
                        <th>Fact</th>
                        <th>Category</th>
                        <th>Added</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($myth_entries)): ?>
                        <tr class="empty-row"><td colspan="5">No myth entries yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($myth_entries as $m): ?>
                        <tr>
                            <td class="cell-excerpt" style="color:#b5294a;"><?= htmlspecialchars($m['myth']) ?></td>
                            <td class="cell-excerpt" style="color:#1a7c4a;"><?= htmlspecialchars($m['fact']) ?></td>
                            <td><span class="cat-pill cat-<?= htmlspecialchars($m['category']) ?>"><?= htmlspecialchars(ucfirst($m['category'] ?: 'other')) ?></span></td>
                            <td style="white-space:nowrap;color:var(--text-muted);font-size:0.78rem;"><?= date('d M Y', strtotime($m['created_at'])) ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="current_tab" value="myths">
                                    <input type="hidden" name="item_id" value="<?= $m['id'] ?>">
                                    <button type="submit" name="delete_myth" class="btn-del"
                                        onclick="return confirm('Delete this myth entry?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ══ SYMPTOM GUIDANCE tab ══ -->
    <?php if ($tab === 'symptoms'): ?>
    <div class="panel">

        <div class="form-card">
            <h3>Add Symptom Guidance</h3>
            <p>Fill in the symptom name, what likely causes it, self-care steps, and when to seek care. English is required.</p>
            <form method="POST">
                <input type="hidden" name="current_tab" value="symptoms">

                <div class="form-group">
                    <label>Urgency Level</label>
                    <select name="urgency">
                        <option value="low">Low — Monitor at home</option>
                        <option value="medium" selected>Medium — Visit clinic soon</option>
                        <option value="high">High — Seek care immediately</option>
                    </select>
                </div>

                <!-- Symptom names (all three languages) -->
                <div class="lang-group">
                    <p class="lang-group-label">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                        Symptom Name <span class="required">*</span>
                    </p>
                    <div class="form-group">
                        <label>English</label>
                        <input type="text" name="symptom" placeholder="e.g. Persistent fever" required>
                    </div>
                    <div class="form-group">
                        <label>Kiswahili (optional)</label>
                        <input type="text" name="symptom_sw" placeholder="e.g. Homa inayoendelea">
                    </div>
                    <div class="form-group">
                        <label>Maa (optional)</label>
                        <input type="text" name="symptom_maa" placeholder="Symptom name in Maa">
                    </div>
                </div>

                <!-- Guidance fields -->
                <div class="lang-group">
                    <p class="lang-group-label">Possible Cause <span class="required">*</span></p>
                    <div class="form-group">
                        <textarea name="possible_cause" rows="2" placeholder="What commonly causes this symptom…" required></textarea>
                    </div>
                </div>

                <div class="lang-group">
                    <p class="lang-group-label">Self-Care Steps</p>
                    <div class="form-group">
                        <textarea name="self_care" rows="2" placeholder="What the person can do at home…"></textarea>
                    </div>
                </div>

                <div class="lang-group">
                    <p class="lang-group-label">When to Seek Care</p>
                    <div class="form-group">
                        <textarea name="when_to_seek_care" rows="2" placeholder="Warning signs that need a clinic or hospital…"></textarea>
                    </div>
                </div>

                <button type="submit" name="add_symptom" class="btn-add">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Add Symptom
                </button>
            </form>
        </div>

        <div class="list-card">
            <div class="list-header">
                <h4>All Symptom Guidance Entries (<?= $counts['symptoms'] ?>)</h4>
            </div>
            <div style="overflow-x:auto;">
            <table class="content-table">
                <thead>
                    <tr>
                        <th>Symptom</th>
                        <th>Urgency</th>
                        <th>Added</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($symptom_entries)): ?>
                        <tr class="empty-row"><td colspan="4">No symptom entries yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($symptom_entries as $s): ?>
                        <tr>
                            <td class="cell-title"><?= htmlspecialchars($s['symptom']) ?></td>
                            <td>
                                <span class="urgency-<?= htmlspecialchars($s['urgency']) ?>">
                                    <?= htmlspecialchars(ucfirst($s['urgency'])) ?>
                                </span>
                            </td>
                            <td style="white-space:nowrap;color:var(--text-muted);font-size:0.78rem;"><?= date('d M Y', strtotime($s['created_at'])) ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="current_tab" value="symptoms">
                                    <input type="hidden" name="item_id" value="<?= $s['id'] ?>">
                                    <button type="submit" name="delete_symptom" class="btn-del"
                                        onclick="return confirm('Delete this symptom entry?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

<footer>
    <p>© 2026 Samburu Health Platform — Admin Panel</p>
</footer>

</body>
</html>