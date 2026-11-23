<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'chw') {
    header('Location: ../auth/login.php');
    exit();
}

$user_id   = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];
$lang      = $_SESSION['language'] ?? 'english';
$success   = '';
$error     = '';

// Handle suggestion submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_suggestion'])) {
    $title    = trim($_POST['title']);
    $content  = trim($_POST['content']);
    $category = $_POST['category'];

    if (empty($title) || empty($content)) {
        $error = 'Please fill in the title and content.';
    } else {
        $stmt = $conn->prepare("INSERT INTO content_suggestions (user_id, title, content, category) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user_id, $title, $content, $category);
        $stmt->execute();
        $success = 'Your suggestion has been submitted for Admin review.';
    }
}

// Get CHW's own suggestions
$stmt = $conn->prepare("SELECT * FROM content_suggestions WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$my_suggestions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#1a3d2b">
<link rel="manifest" href="../manifest.json">
<title>CHW Dashboard — Samburu Health</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

<style>
/* ── Design tokens ── */
:root {
    --green-deep:  #1a3d2b;
    --green-mid:   #2d6a4f;
    --green-light: #52b788;
    --cream:       #f8f4ec;
    --cream-dark:  #ede8dc;
    --gold:        #c9913d;
    --gold-light:  #e8b96a;
    --ink:         #0e1a13;
    --white:       #ffffff;
    --text-body:   #2c3e2d;
    --text-muted:  #6b7c6d;
    --border:      #d4cfc5;

    --bg:      var(--cream);
    --surface: var(--white);
    --fg:      var(--text-body);
}
@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) {
        --bg: #0d1f14; --surface: #152a1e; --fg: #e8f0e9;
        --text-muted: #8fa892; --border: #2a4035;
        color-scheme: dark;
    }
}
:root[data-theme="dark"] {
    --bg: #0d1f14; --surface: #152a1e; --fg: #e8f0e9;
    --text-muted: #8fa892; --border: #2a4035;
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
    background: var(--green-deep);
    display: flex; align-items: center; justify-content: space-between;
    padding: 0 clamp(1rem, 5vw, 3rem); height: 64px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.25);
}
.logo {
    font-family: 'Lora', serif; font-size: 1.1rem; font-weight: 700;
    color: var(--white); display: flex; align-items: center; gap: 10px;
    text-decoration: none; flex-shrink: 0;
}
.chw-role-pill {
    font-size: 0.65rem; font-weight: 700; letter-spacing: 0.08em;
    text-transform: uppercase; padding: 2px 8px; border-radius: 20px;
    background: var(--green-light); color: var(--green-deep);
}
.nav-links { display: flex; align-items: center; gap: 1.1rem; flex-wrap: wrap; }
.nav-links a {
    color: rgba(255,255,255,0.82); text-decoration: none;
    font-size: 0.83rem; font-weight: 500; white-space: nowrap; transition: color 0.2s;
}
.nav-links a:hover { color: var(--white); }
.nav-logout {
    background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.22);
    color: var(--white) !important; padding: 5px 14px; border-radius: 6px; font-weight: 600 !important;
}
.nav-logout:hover { background: rgba(255,255,255,0.2) !important; }

/* ── Hero ── */
.page-hero {
    background: linear-gradient(135deg, var(--green-deep) 0%, var(--green-mid) 60%, #40916c 100%);
    padding: 3rem clamp(1.5rem, 7%, 5rem); position: relative; overflow: hidden;
}
.page-hero::before {
    content: ''; position: absolute;
    width: 420px; height: 420px; border-radius: 50%;
    background: radial-gradient(circle, rgba(82,183,136,0.14), transparent 65%);
    top: -100px; right: -60px; pointer-events: none;
}
.hero-inner { position: relative; z-index: 1; }
.hero-eyebrow {
    font-size: 0.72rem; font-weight: 700; letter-spacing: 0.14em;
    text-transform: uppercase; color: var(--green-light); margin-bottom: 0.5rem;
}
.page-hero h1 {
    font-family: 'Lora', serif;
    font-size: clamp(1.5rem, 3.5vw, 2.2rem);
    font-weight: 700; color: var(--white); line-height: 1.25; margin-bottom: 0.5rem;
}
.page-hero p { font-size: 0.9rem; color: rgba(255,255,255,0.78); }

/* ── Main content ── */
.content-wrap { flex: 1; padding: 2.5rem clamp(1.5rem, 7%, 5rem); }

/* ── Section header ── */
.section-header {
    font-family: 'Lora', serif; font-size: 1.1rem; font-weight: 700;
    color: var(--green-deep); margin-bottom: 1.1rem;
    padding-bottom: 0.6rem; border-bottom: 2px solid var(--border);
    display: flex; align-items: center; gap: 8px;
}
@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) .section-header { color: var(--green-light); }
}
:root[data-theme="dark"] .section-header { color: var(--green-light); }

/* ── Quick-access cards ── */
.resource-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1rem; margin-bottom: 2.5rem;
}
.resource-card {
    background: var(--surface); border: 1.5px solid var(--border);
    border-radius: 14px; padding: 1.5rem 1.1rem;
    text-decoration: none; color: inherit;
    text-align: center;
    transition: border-color 0.2s, box-shadow 0.2s, transform 0.2s;
    display: flex; flex-direction: column; align-items: center; gap: 0.6rem;
}
.resource-card:hover {
    border-color: var(--green-mid);
    box-shadow: 0 6px 20px rgba(45,106,79,0.13);
    transform: translateY(-3px);
}
.resource-icon {
    width: 48px; height: 48px; border-radius: 12px;
    background: linear-gradient(135deg, rgba(45,106,79,0.12), rgba(82,183,136,0.15));
    display: flex; align-items: center; justify-content: center;
}
.resource-card h3 {
    font-size: 0.9rem; font-weight: 600; color: var(--green-deep); margin: 0;
}
@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) .resource-card h3 { color: var(--green-light); }
}
:root[data-theme="dark"] .resource-card h3 { color: var(--green-light); }
.resource-card p { font-size: 0.78rem; color: var(--text-muted); line-height: 1.5; margin: 0; }

/* ── Alerts ── */
.alert {
    padding: 0.8rem 1rem; border-radius: 8px;
    font-size: 0.875rem; margin-bottom: 1.25rem;
    display: flex; align-items: center; gap: 8px;
}
.alert-success {
    background: #f0fff6; border: 1px solid #a3e4c0; color: #1a7c4a;
}
.alert-error {
    background: #fff0f3; border: 1px solid #f4b8c4; color: #b5294a;
}
@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) .alert-success { background: #0e2418; border-color: #1a5430; }
    :root:not([data-theme="light"]) .alert-error   { background: #2a0e16; border-color: #5a2030; }
}
:root[data-theme="dark"] .alert-success { background: #0e2418; border-color: #1a5430; }
:root[data-theme="dark"] .alert-error   { background: #2a0e16; border-color: #5a2030; }

/* ── Suggestion form ── */
.suggest-card {
    background: var(--surface); border: 1.5px solid var(--border);
    border-radius: 16px; padding: 2rem; margin-bottom: 2.5rem;
}
.suggest-card h3 {
    font-family: 'Lora', serif; font-size: 1.05rem; font-weight: 700;
    color: var(--green-deep); margin-bottom: 0.3rem;
}
@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) .suggest-card h3 { color: var(--green-light); }
}
:root[data-theme="dark"] .suggest-card h3 { color: var(--green-light); }
.suggest-card > p { font-size: 0.83rem; color: var(--text-muted); margin-bottom: 1.35rem; line-height: 1.6; }

.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem; }
.form-group { display: flex; flex-direction: column; gap: 5px; }
.form-group label {
    font-size: 0.8rem; font-weight: 600; color: var(--fg);
}
.required { color: #b5294a; }
.form-group input,
.form-group select,
.form-group textarea {
    padding: 0.7rem 0.9rem;
    border: 1.5px solid var(--border); border-radius: 8px;
    font-family: 'Poppins', sans-serif; font-size: 0.875rem;
    background: var(--bg); color: var(--fg);
    outline: none; transition: border-color 0.2s;
    width: 100%;
}
.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus { border-color: var(--green-mid); }
.form-group textarea { resize: vertical; min-height: 110px; }

.btn-submit {
    padding: 0.75rem 1.75rem; background: var(--green-mid); color: var(--white);
    border: none; border-radius: 8px;
    font-family: 'Poppins', sans-serif; font-size: 0.9rem; font-weight: 600;
    cursor: pointer; transition: background 0.2s; margin-top: 0.5rem;
}
.btn-submit:hover { background: var(--green-deep); }

/* ── Suggestions table ── */
.table-wrap {
    background: var(--surface); border: 1.5px solid var(--border);
    border-radius: 14px; overflow: hidden;
}
.suggestions-table { width: 100%; border-collapse: collapse; }
.suggestions-table thead th {
    background: rgba(45,106,79,0.08);
    color: var(--text-muted); padding: 0.75rem 1.1rem;
    text-align: left; font-size: 0.72rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.08em;
    border-bottom: 1px solid var(--border);
}
.suggestions-table tbody td {
    padding: 0.85rem 1.1rem; font-size: 0.85rem; color: var(--fg);
    border-bottom: 1px solid var(--border);
}
.suggestions-table tbody tr:last-child td { border-bottom: none; }
.suggestions-table tbody tr:hover { background: rgba(45,106,79,0.04); }

.status-badge {
    display: inline-block; padding: 3px 10px; border-radius: 20px;
    font-size: 0.72rem; font-weight: 700;
}
.status-pending  { background: #fff7ed; color: #b56a00; }
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

.no-suggestions {
    padding: 2.5rem; text-align: center;
    font-size: 0.875rem; color: var(--text-muted);
}

/* ── Footer ── */
footer {
    background: var(--ink); color: rgba(255,255,255,0.65);
    text-align: center; padding: 1.25rem 1rem;
    font-size: 0.8rem; line-height: 1.8;
}

/* ── Responsive ── */
@media (max-width: 600px) {
    .resource-grid { grid-template-columns: repeat(2, 1fr); }
    .form-row { grid-template-columns: 1fr; }
    .suggestions-table thead th:nth-child(2),
    .suggestions-table tbody td:nth-child(2) { display: none; }
}
</style>
</head>
<body>

<header class="navbar">
    <a href="index.php" class="logo">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#52b788" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
        </svg>
        Samburu Health
        <span class="chw-role-pill">CHW</span>
    </a>
    <nav class="nav-links">
        <a href="index.php">Dashboard</a>
        <a href="report.php">Community Report</a>
        <a href="../health/index.php">Health Education</a>
        <a href="../myth/index.php">Myth vs Fact</a>
        <a href="../symptom/index.php">Symptoms</a>
        <a href="../auth/logout.php" class="nav-logout">Logout</a>
    </nav>
</header>

<!-- Hero -->
<div class="page-hero">
    <div class="hero-inner">
        <p class="hero-eyebrow">Community Health Worker Portal</p>
        <h1>Welcome back, <?= htmlspecialchars($full_name) ?></h1>
        <p>Access health resources and submit content suggestions based on your community visits.</p>
    </div>
</div>

<div class="content-wrap">

    <!-- Resource cards -->
    <p class="section-header">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
        </svg>
        Health Resources
    </p>
    <div class="resource-grid">
        <a href="../health/index.php" class="resource-card">
            <div class="resource-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#2d6a4f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
                </svg>
            </div>
            <h3>Health Education</h3>
            <p>Access and share health education articles with community members.</p>
        </a>
        <a href="../myth/index.php" class="resource-card">
            <div class="resource-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#2d6a4f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/><line x1="8" y1="12" x2="16" y2="12"/><line x1="12" y1="8" x2="12" y2="16"/>
                </svg>
            </div>
            <h3>Myth vs Fact</h3>
            <p>Reference myth corrections during community health visits.</p>
        </a>
        <a href="../symptom/index.php" class="resource-card">
            <div class="resource-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#2d6a4f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                </svg>
            </div>
            <h3>Symptom Guidance</h3>
            <p>Use symptom guidance to support community members.</p>
        </a>
        <a href="report.php" class="resource-card">
            <div class="resource-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#2d6a4f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 21H3V3"/><polyline points="7 14 11 10 14 13 20 7"/>
                </svg>
            </div>
            <h3>Community Report</h3>
            <p>Common symptoms, urgent cases and myths in your community.</p>
        </a>
        <a href="#suggest" class="resource-card">
            <div class="resource-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#2d6a4f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                </svg>
            </div>
            <h3>Submit Suggestion</h3>
            <p>Suggest new health content based on community needs.</p>
        </a>
    </div>

    <!-- Alerts -->
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

    <!-- Submit suggestion -->
    <div class="suggest-card" id="suggest">
        <h3>Submit a Content Suggestion</h3>
        <p>Based on your community visits, suggest health topics or corrections that would help your community. All suggestions go to the admin for review.</p>

        <form method="POST" action="">
            <div class="form-row">
                <div class="form-group">
                    <label>Suggestion Title <span class="required">*</span></label>
                    <input type="text" name="title"
                        placeholder="e.g. Add content about snakebite first aid" required>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select name="category">
                        <option value="">Select category</option>
                        <option value="maternal">Maternal Health</option>
                        <option value="child">Child Health</option>
                        <option value="disease">Common Diseases</option>
                        <option value="myth">Myth vs Fact</option>
                        <option value="other">Other</option>
                    </select>
                </div>
            </div>
            <div class="form-group" style="margin-bottom:1rem;">
                <label>Details <span class="required">*</span></label>
                <textarea name="content" rows="5"
                    placeholder="Describe what content you would like added or changed, and why it would help your community…"
                    required></textarea>
            </div>
            <button type="submit" name="submit_suggestion" class="btn-submit">Submit Suggestion</button>
        </form>
    </div>

    <!-- My suggestions -->
    <p class="section-header">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/>
            <line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/>
            <line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>
        </svg>
        My Submitted Suggestions
    </p>

    <?php if (empty($my_suggestions)): ?>
        <div class="table-wrap">
            <div class="no-suggestions">You have not submitted any suggestions yet.</div>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="suggestions-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Submitted</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($my_suggestions as $s): ?>
                        <tr>
                            <td><?= htmlspecialchars($s['title']) ?></td>
                            <td><?= $s['category'] ? htmlspecialchars(ucfirst($s['category'])) : '—' ?></td>
                            <td>
                                <span class="status-badge status-<?= htmlspecialchars($s['status']) ?>">
                                    <?= htmlspecialchars(ucfirst($s['status'])) ?>
                                </span>
                            </td>
                            <td><?= date('d M Y', strtotime($s['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

</div>

<footer>
    <p>© 2026 Samburu Health Platform</p>
    <p>Community Health Worker Portal</p>
</footer>

<script src="../pwa.js"></script>
</body>
</html>