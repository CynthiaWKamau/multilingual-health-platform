<?php
session_start();
include '../config/database.php';
include '../config/translate.php';
include '../config/activity.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$user_id   = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];
$role      = $_SESSION['role'];
$language  = $_SESSION['language'] ?? 'english';

// Live stats 
function db_count($conn, $table) {
    $r = $conn->query("SELECT COUNT(*) AS n FROM `$table`");
    return ($r && $row = $r->fetch_assoc()) ? (int)$row['n'] : 0;
}
$stat_articles = db_count($conn, 'health_education');
$stat_myths    = db_count($conn, 'myth_facts');
$stat_symptoms = db_count($conn, 'symptom_guidance');

// Recent articles
$recent_arts = [];
$ra = $conn->query(
    "SELECT id, title, title_sw, title_maa, category, created_at
     FROM health_education
     ORDER BY created_at DESC
     LIMIT 3"
);
if ($ra) $recent_arts = $ra->fetch_all(MYSQLI_ASSOC);

function art_title($a, $lang) {
    return t($a, 'title');
}

$cat_color = [
    'maternal'  => '#b5446e',
    'child'     => '#1a5ca8',
    'disease'   => '#c45c10',
    'nutrition' => '#2d7d46',
];

$lang_label = ['english' => 'English', 'kiswahili' => 'Kiswahili', 'maa' => 'Maa'];

// Recent activity
activity_table($conn);
$my_recent = report_rows($conn,
    "SELECT a.activity_type, a.detail, a.result, a.created_at, h.title, h.title_sw, h.title_maa
     FROM activity_log a LEFT JOIN health_education h ON h.id = a.ref_id
     WHERE a.user_id = ? ORDER BY a.created_at DESC LIMIT 5",
    'i', $user_id);
$my_counts = ['symptom_check' => 0, 'myth_check' => 0, 'article_view' => 0];
foreach (report_rows($conn,
    "SELECT activity_type, COUNT(*) AS n FROM activity_log
     WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY activity_type",
    'i', $user_id) as $r) {
    $my_counts[$r['activity_type']] = (int)$r['n'];
}
$activity_meta = [
    'symptom_check' => ['label' => __('Symptom check'), 'color' => '#1a5ca8'],
    'myth_check'    => ['label' => __('Myth check'),    'color' => '#b5446e'],
    'article_view'  => ['label' => __('Article read'),  'color' => '#2d7d46'],
];
$result_meta = [
    'high'   => [__('High'),   '#b5294a'],
    'medium' => [__('Medium'), '#b56a00'],
    'low'    => [__('Low'),    '#1a7c4a'],
    'myth'   => [__('Myth'),   '#b5294a'],
    'fact'   => [__('Fact'),   '#1a7c4a'],
];
?>
<!DOCTYPE html>
<html lang="<?= $language === 'english' ? 'en' : 'sw' ?>" translate="no">
<head>
<meta charset="UTF-8">
<meta name="google" content="notranslate">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#1a3d2b">
<link rel="manifest" href="../manifest.json">
<title><?= __('Dashboard') ?> — Samburu Health</title>

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
    --card-shadow: 0 2px 16px rgba(26,61,43,0.08);
    --bg:      var(--cream);
    --surface: var(--white);
    --fg:      var(--text-body);
}
@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) {
        --bg: #0d1f14; --surface: #152a1e; --fg: #e8f0e9;
        --text-muted: #8fa892; --border: #2a4035;
        --cream-dark: #1a2e22;
        --card-shadow: 0 2px 16px rgba(0,0,0,0.35);
        color-scheme: dark;
    }
}
:root[data-theme="dark"] {
    --bg: #0d1f14; --surface: #152a1e; --fg: #e8f0e9;
    --text-muted: #8fa892; --border: #2a4035;
    --cream-dark: #1a2e22;
    --card-shadow: 0 2px 16px rgba(0,0,0,0.35);
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
    padding: 0 clamp(1rem, 5vw, 3rem); min-height: 64px; flex-wrap: wrap; gap: 0.5rem 1rem; padding-block: 0.5rem;
    box-shadow: 0 2px 12px rgba(0,0,0,0.25);
}
.logo {
    font-family: 'Lora', serif; font-size: 1.2rem; font-weight: 700;
    color: var(--white); display: flex; align-items: center; gap: 10px;
    text-decoration: none; flex-shrink: 0;
}
.nav-links { display: flex; align-items: center; gap: 0.6rem 1.1rem; flex-wrap: wrap; }
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
.hero {
    background: linear-gradient(135deg, var(--green-deep) 0%, var(--green-mid) 60%, #40916c 100%);
    padding: 3.5rem clamp(1.5rem, 7%, 5rem);
    position: relative; overflow: hidden;
}
.hero::before {
    content: ''; position: absolute;
    width: 500px; height: 500px; border-radius: 50%;
    background: radial-gradient(circle, rgba(82,183,136,0.15), transparent 65%);
    top: -120px; right: -80px; pointer-events: none;
}
.hero::after {
    content: ''; position: absolute;
    width: 320px; height: 320px; border-radius: 50%;
    background: radial-gradient(circle, rgba(201,145,61,0.1), transparent 65%);
    bottom: -80px; left: 10%; pointer-events: none;
}
.hero-inner { position: relative; z-index: 1; max-width: 700px; }
.hero-eyebrow {
    font-size: 0.72rem; font-weight: 700; letter-spacing: 0.14em;
    text-transform: uppercase; color: var(--green-light); margin-bottom: 0.6rem;
}
.hero h1 {
    font-family: 'Lora', serif;
    font-size: clamp(1.75rem, 4vw, 2.6rem);
    font-weight: 700; color: var(--white); line-height: 1.25; margin-bottom: 0.75rem;
}
.hero-sub { font-size: 1rem; color: rgba(255,255,255,0.78); line-height: 1.7; max-width: 520px; }

/* Language switcher in hero */
.hero-lang {
    display: flex; align-items: center; gap: 8px; margin-top: 1.4rem; flex-wrap: wrap;
}
.hero-lang-label {
    font-size: 0.75rem; color: rgba(255,255,255,0.6); font-weight: 500;
}
.hlang-btn {
    padding: 5px 14px; border-radius: 20px;
    border: 1px solid rgba(255,255,255,0.3);
    color: rgba(255,255,255,0.78); font-size: 0.78rem; font-weight: 600;
    text-decoration: none; transition: all 0.2s; white-space: nowrap;
}
.hlang-btn:hover { border-color: rgba(255,255,255,0.65); color: var(--white); }
.hlang-btn.active {
    background: var(--green-light); border-color: var(--green-light);
    color: var(--green-deep);
}

/* ── Stats strip ── */
.stats-strip {
    display: flex; gap: 0; border-bottom: 1px solid var(--border);
    background: var(--surface);
}
.stat-cell {
    flex: 1; padding: 1.1rem 1.5rem; text-align: center;
    border-right: 1px solid var(--border);
}
.stat-cell:last-child { border-right: none; }
.stat-num {
    font-family: 'Lora', serif; font-size: 1.7rem; font-weight: 700;
    color: var(--green-mid); line-height: 1;
}
.stat-label {
    font-size: 0.72rem; font-weight: 600; text-transform: uppercase;
    letter-spacing: 0.08em; color: var(--text-muted); margin-top: 4px;
}

/* ── Section wrapper ── */
.section { padding: 2.75rem clamp(1.5rem, 7%, 5rem); }
.section-head { margin-bottom: 1.75rem; }
.section-label {
    font-size: 0.72rem; font-weight: 700; letter-spacing: 0.14em;
    text-transform: uppercase; color: var(--gold); margin-bottom: 0.4rem;
}
.section-head h2 {
    font-family: 'Lora', serif;
    font-size: clamp(1.25rem, 2.5vw, 1.65rem);
    font-weight: 700; color: var(--green-deep);
}
@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) .section-head h2 { color: var(--green-light); }
}
:root[data-theme="dark"] .section-head h2 { color: var(--green-light); }
.section-desc { color: var(--text-muted); font-size: 0.875rem; margin-top: 0.35rem; line-height: 1.7; }

/* ── Resource cards grid ── */
.resource-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 1.2rem;
}
.res-card {
    display: flex; flex-direction: column;
    background: var(--surface); border: 1.5px solid var(--border);
    border-radius: 14px; padding: 1.6rem 1.4rem;
    text-decoration: none; color: var(--fg);
    transition: border-color 0.2s, box-shadow 0.2s, transform 0.2s;
}
.res-card:hover {
    border-color: var(--green-mid);
    box-shadow: 0 6px 24px rgba(45,106,79,0.14);
    transform: translateY(-3px);
}
/* role-specific card accents */
.res-card.admin-card { border-left: 3px solid #5c6bc0; }
.res-card.chw-card   { border-left: 3px solid var(--gold); }

.res-icon {
    width: 48px; height: 48px; border-radius: 12px;
    background: linear-gradient(135deg, #e8f5ee, #d0f0e0);
    display: flex; align-items: center; justify-content: center;
    margin-bottom: 0.9rem; flex-shrink: 0;
}
.res-icon.purple { background: linear-gradient(135deg, #ede7f6, #d1c4e9); }
.res-icon.gold   { background: linear-gradient(135deg, #fff8e1, #ffecb3); }
@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) .res-icon         { background: linear-gradient(135deg, #1a3d2b, #2d6a4f); }
    :root:not([data-theme="light"]) .res-icon.purple  { background: linear-gradient(135deg, #1a1040, #312060); }
    :root:not([data-theme="light"]) .res-icon.gold    { background: linear-gradient(135deg, #2a1c00, #4a3000); }
}
:root[data-theme="dark"] .res-icon         { background: linear-gradient(135deg, #1a3d2b, #2d6a4f); }
:root[data-theme="dark"] .res-icon.purple  { background: linear-gradient(135deg, #1a1040, #312060); }
:root[data-theme="dark"] .res-icon.gold    { background: linear-gradient(135deg, #2a1c00, #4a3000); }

.res-card h3 {
    font-family: 'Lora', serif; font-size: 0.975rem; font-weight: 600;
    color: var(--green-deep); margin-bottom: 0.4rem;
}
@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) .res-card h3 { color: var(--green-light); }
}
:root[data-theme="dark"] .res-card h3 { color: var(--green-light); }
.res-card p { font-size: 0.82rem; color: var(--text-muted); line-height: 1.65; flex: 1; }
.res-arrow {
    margin-top: 0.9rem; display: inline-flex; align-items: center; gap: 4px;
    font-size: 0.78rem; font-weight: 600; color: var(--green-mid);
}
.res-arrow svg { transition: transform 0.2s; }
.res-card:hover .res-arrow svg { transform: translateX(3px); }

/* ── Recent articles ── */
.alt-section { background: var(--cream-dark); }
@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) .alt-section { background: #0f2518; }
}
:root[data-theme="dark"] .alt-section { background: #0f2518; }

.recent-list { display: flex; flex-direction: column; gap: 0.75rem; }
.recent-item {
    display: flex; align-items: center; gap: 1rem;
    background: var(--surface); border: 1.5px solid var(--border);
    border-radius: 10px; padding: 1rem 1.25rem;
    text-decoration: none; color: var(--fg);
    transition: border-color 0.2s, box-shadow 0.2s;
}
.recent-item:hover { border-color: var(--green-mid); box-shadow: 0 3px 14px rgba(45,106,79,0.1); }
.recent-dot {
    width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0;
}
.recent-title {
    font-size: 0.9rem; font-weight: 500; flex: 1;
}
.recent-cat {
    font-size: 0.7rem; font-weight: 700; letter-spacing: 0.06em;
    text-transform: uppercase; padding: 2px 8px; border-radius: 10px;
    flex-shrink: 0;
}
.recent-date { font-size: 0.75rem; color: var(--text-muted); flex-shrink: 0; }

.activity-summary {
    display: flex; gap: 0.6rem; flex-wrap: wrap; margin-bottom: 1rem;
}
.activity-chip {
    background: var(--surface); border: 1.5px solid var(--border);
    border-radius: 20px; padding: 5px 14px; font-size: 0.8rem; color: var(--text-muted);
}
.activity-chip strong { color: var(--green-mid); font-size: 0.9rem; margin-right: 3px; }
.recent-type {
    font-size: 0.7rem; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase;
    flex-shrink: 0; min-width: 96px;
}
.recent-detail { flex: 1; min-width: 0; font-size: 0.9rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.view-all {
    display: inline-flex; align-items: center; gap: 5px; margin-top: 1rem;
    font-size: 0.84rem; font-weight: 600; color: var(--green-mid); text-decoration: none;
}
.view-all:hover { text-decoration: underline; }
.activity-links { display: flex; gap: 1.5rem; flex-wrap: wrap; }
@media (max-width: 640px) {
    .recent-type { min-width: 0; }
    .recent-item.activity { flex-wrap: wrap; }
    .recent-item.activity .recent-detail { flex-basis: 100%; order: 3; white-space: normal; }
}

.no-recent {
    background: var(--surface); border: 1.5px dashed var(--border);
    border-radius: 10px; padding: 1.5rem; text-align: center;
    font-size: 0.875rem; color: var(--text-muted);
}

/* ── Account card ── */
.account-card {
    max-width: 480px;
    background: var(--surface); border: 1.5px solid var(--border);
    border-radius: 14px; overflow: hidden; box-shadow: var(--card-shadow);
}
.account-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: 0.9rem 1.4rem; border-bottom: 1px solid var(--border);
}
.account-row:last-child { border-bottom: none; }
.acct-label {
    font-size: 0.78rem; font-weight: 600; color: var(--text-muted);
    text-transform: uppercase; letter-spacing: 0.05em;
}
.acct-value { font-size: 0.875rem; font-weight: 500; color: var(--fg); }
.acct-badge {
    display: inline-block;
    background: linear-gradient(135deg, #e8f5ee, #d0f0e0);
    color: var(--green-mid); font-size: 0.76rem; font-weight: 600;
    padding: 3px 10px; border-radius: 20px;
}
@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) .acct-badge { background: rgba(45,106,79,0.25); color: var(--green-light); }
}
:root[data-theme="dark"] .acct-badge { background: rgba(45,106,79,0.25); color: var(--green-light); }
.acct-badge.admin { background: rgba(92,107,192,0.15); color: #5c6bc0; }
.acct-badge.chw   { background: rgba(201,145,61,0.18); color: var(--gold); }
@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) .acct-badge.admin { background: rgba(92,107,192,0.3); color: #9fa8da; }
    :root:not([data-theme="light"]) .acct-badge.chw   { background: rgba(201,145,61,0.25); color: var(--gold-light); }
}
:root[data-theme="dark"] .acct-badge.admin { background: rgba(92,107,192,0.3); color: #9fa8da; }
:root[data-theme="dark"] .acct-badge.chw   { background: rgba(201,145,61,0.25); color: var(--gold-light); }

/* ── Quick actions ── */
.quick-strip {
    display: flex; gap: 0.8rem; flex-wrap: wrap; margin-top: 1.5rem;
}
.quick-btn {
    display: inline-flex; align-items: center; gap: 8px;
    background: var(--surface); border: 1.5px solid var(--border);
    color: var(--fg); font-family: 'Poppins', sans-serif;
    font-size: 0.84rem; font-weight: 500;
    padding: 0.6rem 1.2rem; border-radius: 8px; text-decoration: none;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.quick-btn:hover { border-color: var(--green-mid); box-shadow: 0 2px 10px rgba(45,106,79,0.12); }
.quick-btn.danger {
    background: var(--gold); border-color: var(--gold); color: var(--white);
}
.quick-btn.danger:hover { background: #b8802d; border-color: #b8802d; }

/* ── Footer ── */
footer {
    background: var(--ink); color: rgba(255,255,255,0.65);
    text-align: center; padding: 1.25rem 1rem;
    font-size: 0.8rem; line-height: 1.8; margin-top: auto;
}

/* ── Responsive ── */
@media (max-width: 640px) {
    .stats-strip { flex-wrap: wrap; }
    .stat-cell   { flex: 1 1 33%; border-bottom: 1px solid var(--border); }
    .recent-date { display: none; }
}
@media (max-width: 400px) {
    .resource-grid { grid-template-columns: 1fr; }
}
</style>
</head>
<body>
<?php include '../admin/_back_strip.php'; ?>

<header class="navbar">
    <a href="index.php" class="logo">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#52b788" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
        </svg>
        Samburu Health
    </a>
    <nav class="nav-links">
        <a href="../health/index.php"><?= __('Health Education') ?></a>
        <a href="../myth/index.php"><?= __('Myth vs Fact') ?></a>
        <a href="../symptom/index.php"><?= __('Symptoms') ?></a>
        <a href="my_activity.php"><?= __('My Activity') ?></a>
        <a href="../auth/logout.php" class="nav-logout"><?= __('Logout') ?></a>
    </nav>
</header>
<?= maa_notice() ?>

<!-- Hero -->
<section class="hero">
    <div class="hero-inner">
        <p class="hero-eyebrow"><?= __('Welcome back') ?></p>
        <h1><?= sprintf(__('Hello, %s'), htmlspecialchars($full_name)) ?></h1>
        <p class="hero-sub"><?= __('Access health education, myth-busting facts, and symptom guidance — available in English, Kiswahili, and Maa.') ?></p>
        <div class="hero-lang">
            <span class="hero-lang-label"><?= __('Language:') ?></span>
            <a href="language.php?lang=english&redirect=<?= urlencode('index.php') ?>"
               class="hlang-btn <?= $language === 'english'   ? 'active' : '' ?>">EN</a>
            <a href="language.php?lang=kiswahili&redirect=<?= urlencode('index.php') ?>"
               class="hlang-btn <?= $language === 'kiswahili' ? 'active' : '' ?>">SW</a>
            <a href="language.php?lang=maa&redirect=<?= urlencode('index.php') ?>"
               class="hlang-btn <?= $language === 'maa'       ? 'active' : '' ?>">MAA</a>
        </div>
    </div>
</section>

<!-- Stats strip -->
<div class="stats-strip">
    <div class="stat-cell">
        <div class="stat-num"><?= $stat_articles ?></div>
        <div class="stat-label"><?= __('Health Articles') ?></div>
    </div>
    <div class="stat-cell">
        <div class="stat-num"><?= $stat_myths ?></div>
        <div class="stat-label"><?= __('Myth vs Fact Entries') ?></div>
    </div>
    <div class="stat-cell">
        <div class="stat-num"><?= $stat_symptoms ?></div>
        <div class="stat-label"><?= __('Symptom Guides') ?></div>
    </div>
    <div class="stat-cell">
        <div class="stat-num">3</div>
        <div class="stat-label"><?= __('Languages Supported') ?></div>
    </div>
</div>

<!-- Health resources -->
<section class="section">
    <div class="section-head">
        <p class="section-label"><?= __('Health Resources') ?></p>
        <h2><?= __('What would you like to access today?') ?></h2>
        <p class="section-desc"><?= __('Tap any card to explore that section of the platform.') ?></p>
    </div>

    <div class="resource-grid">

        <a href="../health/index.php" class="res-card">
            <div class="res-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#2d6a4f" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                </svg>
            </div>
            <h3><?= __('Health Education') ?></h3>
            <p><?= __('Maternal health, child nutrition, immunization, and disease prevention — evidence-based and in your language.') ?></p>
            <span class="res-arrow"><?= __('Explore') ?>
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                    <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
                </svg>
            </span>
        </a>

        <a href="../myth/index.php" class="res-card">
            <div class="res-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#2d6a4f" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
            </div>
            <h3><?= __('Myth vs Fact') ?></h3>
            <p><?= __('Use our AI model to check health beliefs, and browse evidence-based corrections verified against WHO guidelines.') ?></p>
            <span class="res-arrow"><?= __('Explore') ?>
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                    <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
                </svg>
            </span>
        </a>

        <a href="../symptom/index.php" class="res-card">
            <div class="res-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#2d6a4f" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
                </svg>
            </div>
            <h3><?= __('Symptom Guidance') ?></h3>
            <p><?= __('Describe your symptoms and get ML-powered guidance on whether to seek care or manage at home safely.') ?></p>
            <span class="res-arrow"><?= __('Explore') ?>
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                    <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
                </svg>
            </span>
        </a>

        <a href="my_activity.php" class="res-card">
            <div class="res-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#2d6a4f" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
                </svg>
            </div>
            <h3><?= __('My Activity') ?></h3>
            <p><?= __('See your past symptom checks, myth checks and the articles you have read.') ?></p>
            <span class="res-arrow"><?= __('Explore') ?>
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                    <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
                </svg>
            </span>
        </a>

        <?php if ($role === 'admin'): ?>
        <a href="../admin/index.php" class="res-card admin-card">
            <div class="res-icon purple">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#5c6bc0" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
            </div>
            <h3><?= __('Admin Dashboard') ?></h3>
            <p><?= __('Manage platform content, review user suggestions, and monitor the health education library.') ?></p>
            <span class="res-arrow" style="color:#5c6bc0"><?= __('Go to Admin') ?>
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                    <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
                </svg>
            </span>
        </a>
        <?php endif; ?>

        <?php if ($role === 'chw'): ?>
        <a href="../chw/report.php" class="res-card chw-card">
            <div class="res-icon gold">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#c9913d" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 21H3V3"/><polyline points="7 14 11 10 14 13 20 7"/>
                </svg>
            </div>
            <h3><?= __('Community Report') ?></h3>
            <p><?= __('See what your community is checking: common symptoms, urgent cases and myths.') ?></p>
            <span class="res-arrow" style="color:var(--gold)"><?= __('Open Report') ?>
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                    <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
                </svg>
            </span>
        </a>
        <a href="../chw/index.php" class="res-card chw-card">
            <div class="res-icon gold">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#c9913d" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
            </div>
            <h3><?= __('CHW Portal') ?></h3>
            <p><?= __('Access your Community Health Worker tools, patient guidance notes, and field support resources.') ?></p>
            <span class="res-arrow" style="color:var(--gold)"><?= __('Go to CHW Portal') ?>
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                    <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
                </svg>
            </span>
        </a>
        <?php endif; ?>

    </div>
</section>

<!-- Recent activity -->
<section class="section">
    <div class="section-head">
        <p class="section-label"><?= __('My Activity') ?></p>
        <h2><?= __('Your Recent Activity') ?></h2>
        <p class="section-desc"><?= __('Your latest symptom checks, myth checks and articles read.') ?></p>
    </div>

    <div class="activity-summary">
        <span class="activity-chip"><strong><?= $my_counts['symptom_check'] ?></strong> <?= __('Symptom Checks') ?></span>
        <span class="activity-chip"><strong><?= $my_counts['myth_check'] ?></strong> <?= __('Myth Checks') ?></span>
        <span class="activity-chip"><strong><?= $my_counts['article_view'] ?></strong> <?= __('Articles Read') ?></span>
        <span class="activity-chip"><?= sprintf(__('Last %d days'), 30) ?></span>
    </div>

    <?php if (empty($my_recent)): ?>
        <div class="no-recent"><?= __('No activity yet. Try the symptom checker or read a health article.') ?></div>
    <?php else: ?>
        <div class="recent-list">
            <?php foreach ($my_recent as $act):
                $meta   = $activity_meta[$act['activity_type']];
                $detail = $act['activity_type'] === 'article_view' && $act['title'] !== null ? t($act, 'title') : __($act['detail']);
                $res    = $result_meta[$act['result']] ?? null;
            ?>
                <a href="my_activity.php" class="recent-item activity">
                    <span class="recent-dot" style="background:<?= $meta['color'] ?>"></span>
                    <span class="recent-type" style="color:<?= $meta['color'] ?>"><?= htmlspecialchars($meta['label']) ?></span>
                    <span class="recent-detail"><?= htmlspecialchars($detail) ?></span>
                    <?php if ($res): ?>
                        <span class="recent-cat" style="color:<?= $res[1] ?>;background:<?= $res[1] ?>18"><?= htmlspecialchars($res[0]) ?></span>
                    <?php endif; ?>
                    <span class="recent-date"><?= date('d M Y', strtotime($act['created_at'])) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="activity-links">
        <a href="my_activity.php" class="view-all"><?= __('View all activity') ?>
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
            </svg>
        </a>
        <a href="my_activity.php?days=30&export=csv" class="view-all"><?= __('Download for Excel') ?>
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
            </svg>
        </a>
    </div>
</section>

<!-- Recently added articles -->
<section class="section alt-section">
    <div class="section-head">
        <p class="section-label"><?= __('Latest Content') ?></p>
        <h2><?= __('Recently Added Articles') ?></h2>
        <p class="section-desc"><?= __('The newest health education content on the platform.') ?></p>
    </div>

    <?php if (empty($recent_arts)): ?>
        <div class="no-recent"><?= __('No articles have been added yet. Check back soon.') ?></div>
    <?php else: ?>
        <div class="recent-list">
            <?php foreach ($recent_arts as $art):
                $cat   = $art['category'] ?? 'general';
                $color = $cat_color[$cat] ?? '#6b7c6d';
                $title = art_title($art, $language);
                $date  = date('d M Y', strtotime($art['created_at']));
            ?>
                <a href="../health/index.php" class="recent-item">
                    <span class="recent-dot" style="background:<?= $color ?>"></span>
                    <span class="recent-title"><?= htmlspecialchars($title) ?></span>
                    <span class="recent-cat" style="color:<?= $color ?>;background:<?= $color ?>18">
                        <?= htmlspecialchars(__(ucfirst($cat))) ?>
                    </span>
                    <span class="recent-date"><?= $date ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<!-- Account details -->
<section class="section">
    <div class="section-head">
        <p class="section-label"><?= __('Your Account') ?></p>
        <h2><?= __('Account Details') ?></h2>
    </div>

    <div class="account-card">
        <div class="account-row">
            <span class="acct-label"><?= __('Full Name') ?></span>
            <span class="acct-value"><?= htmlspecialchars($full_name) ?></span>
        </div>
        <div class="account-row">
            <span class="acct-label"><?= __('Role') ?></span>
            <?php
            $badge_class = match($role) {
                'admin' => 'acct-badge admin',
                'chw'   => 'acct-badge chw',
                default => 'acct-badge',
            };
            ?>
            <span class="<?= $badge_class ?>"><?= htmlspecialchars(__(ucfirst(str_replace('_', ' ', $role)))) ?></span>
        </div>
        <div class="account-row">
            <span class="acct-label"><?= __('Preferred Language') ?></span>
            <span class="acct-value"><?= htmlspecialchars(__($lang_label[$language] ?? ucfirst($language))) ?></span>
        </div>
    </div>

    <div class="quick-strip">
        <a href="../auth/logout.php" class="quick-btn danger">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                <polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
            <?= __('Logout') ?>
        </a>
    </div>
</section>

<footer>
    <p>© 2026 Samburu Health Platform</p>
    <p><?= __('Health education and basic telemedicine guidance for rural communities in Kenya') ?></p>
</footer>

<script src="../pwa.js"></script>
</body>
</html>