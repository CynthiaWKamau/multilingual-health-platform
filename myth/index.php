<?php
session_start();
include '../config/database.php';
include '../config/translate.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$lang     = $_SESSION['language'] ?? 'english';
$category = $_GET['category'] ?? 'all';
$search   = trim($_GET['search'] ?? '');

// ML Myth Detection
$ml_result   = null;
$ml_matches  = [];
$myth_search = $_POST['myth_input'] ?? '';
$myth_done   = false;

function call_ml_api($endpoint, $text) {
    $url  = 'http://127.0.0.1:5000/' . $endpoint;
    $data = json_encode(['text' => $text]);
    $ch   = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response ? json_decode($response, true) : null;
}

// Handle myth detection form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($myth_search)) {
    $myth_done = true;
    $ml_result = call_ml_api('detect-myth', $myth_search);

    // Search database for related entries
    $words   = explode(' ', $myth_search);
    $keyword = $words[array_key_last($words)];
    $stmt    = $conn->prepare("SELECT * FROM myth_facts WHERE myth LIKE ? OR fact LIKE ? LIMIT 3");
    $like    = '%' . $keyword . '%';
    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();
    $ml_matches = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Get all myth entries for browsing
if ($category !== 'all' && !empty($search)) {
    $stmt = $conn->prepare("SELECT * FROM myth_facts WHERE category = ? AND (myth LIKE ? OR fact LIKE ? OR myth_sw LIKE ? OR fact_sw LIKE ?) ORDER BY id");
    $like = '%' . $search . '%';
    $stmt->bind_param("sssss", $category, $like, $like, $like, $like);
} elseif ($category !== 'all') {
    $stmt = $conn->prepare("SELECT * FROM myth_facts WHERE category = ? ORDER BY id");
    $stmt->bind_param("s", $category);
} elseif (!empty($search)) {
    $stmt = $conn->prepare("SELECT * FROM myth_facts WHERE myth LIKE ? OR fact LIKE ? OR myth_sw LIKE ? OR fact_sw LIKE ? ORDER BY id");
    $like = '%' . $search . '%';
    $stmt->bind_param("ssss", $like, $like, $like, $like);
} else {
    $stmt = $conn->prepare("SELECT * FROM myth_facts ORDER BY category, id");
}
$stmt->execute();
$entries = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

function get_myth_content($entry, $lang) {
    if ($lang === 'kiswahili') {
        return [
            'myth' => !empty($entry['myth_sw'])  ? $entry['myth_sw']  : $entry['myth'],
            'fact' => !empty($entry['fact_sw'])  ? $entry['fact_sw']  : $entry['fact'],
        ];
    } elseif ($lang === 'maa') {
        return [
            'myth' => !empty($entry['myth_maa']) ? $entry['myth_maa'] : $entry['myth'],
            'fact' => !empty($entry['fact_maa']) ? $entry['fact_maa'] : $entry['fact'],
        ];
    }
    return ['myth' => $entry['myth'], 'fact' => $entry['fact']];
}

$cat_stmt = $conn->prepare("SELECT DISTINCT category FROM myth_facts ORDER BY category");
$cat_stmt->execute();
$categories = $cat_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="<?= $lang === 'kiswahili' ? 'sw' : 'en' ?>" translate="no">
<head>
<meta charset="UTF-8">
<meta name="google" content="notranslate">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= __('Myth vs Fact') ?> &mdash; Samburu Health</title>

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

    /* Semantic */
    --myth-color:   #b5294a;
    --myth-bg:      #fff0f3;
    --fact-color:   #1a7c4a;
    --fact-bg:      #f0fff6;
    --myth-border:  #f4b8c4;
    --fact-border:  #a3e4c0;

    --bg:      var(--cream);
    --surface: var(--white);
    --fg:      var(--text-body);
}
@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) {
        --bg: #0d1f14; --surface: #152a1e; --fg: #e8f0e9;
        --text-muted: #8fa892; --border: #2a4035;
        --myth-bg: #2a0e16; --myth-border: #5a2030;
        --fact-bg: #0e2418; --fact-border: #1a5430;
        color-scheme: dark;
    }
}
:root[data-theme="dark"] {
    --bg: #0d1f14; --surface: #152a1e; --fg: #e8f0e9;
    --text-muted: #8fa892; --border: #2a4035;
    --myth-bg: #2a0e16; --myth-border: #5a2030;
    --fact-bg: #0e2418; --fact-border: #1a5430;
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
    font-family: 'Lora', serif; font-size: 1.2rem; font-weight: 700;
    color: var(--white); display: flex; align-items: center; gap: 10px;
    text-decoration: none; flex-shrink: 0;
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
.lang-switcher { display: flex; align-items: center; gap: 4px; }
.lang-btn {
    padding: 4px 10px; border-radius: 5px; border: 1px solid rgba(255,255,255,0.25);
    color: rgba(255,255,255,0.78); font-size: 0.75rem; font-weight: 600;
    text-decoration: none; transition: all 0.2s; white-space: nowrap;
}
.lang-btn:hover  { border-color: rgba(255,255,255,0.6); color: var(--white); }
.lang-btn.active { background: var(--green-light); border-color: var(--green-light); color: var(--green-deep); }

/* ── Page hero ── */
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
.hero-inner { position: relative; z-index: 1; max-width: 660px; }
.hero-eyebrow {
    font-size: 0.72rem; font-weight: 700; letter-spacing: 0.14em;
    text-transform: uppercase; color: var(--green-light); margin-bottom: 0.5rem;
}
.page-hero h1 {
    font-family: 'Lora', serif;
    font-size: clamp(1.6rem, 3.5vw, 2.4rem);
    font-weight: 700; color: var(--white); line-height: 1.25; margin-bottom: 0.65rem;
}
.page-hero p { font-size: 0.95rem; color: rgba(255,255,255,0.78); line-height: 1.7; max-width: 520px; }

/* ── Main layout ── */
.content-wrap { flex: 1; padding: 2.5rem clamp(1.5rem, 7%, 5rem); }

/* ── AI Detector card ── */
.detector-card {
    background: var(--surface);
    border: 1.5px solid var(--border);
    border-radius: 16px;
    padding: 2rem;
    margin-bottom: 2.5rem;
}
.detector-header {
    display: flex; align-items: flex-start; gap: 1rem; margin-bottom: 1.25rem;
}
.detector-icon {
    width: 48px; height: 48px; border-radius: 12px; flex-shrink: 0;
    background: linear-gradient(135deg, var(--green-deep), var(--green-mid));
    display: flex; align-items: center; justify-content: center;
}
.detector-header h2 {
    font-family: 'Lora', serif; font-size: 1.2rem; font-weight: 700;
    color: var(--green-deep); margin-bottom: 3px;
}
@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) .detector-header h2 { color: var(--green-light); }
}
:root[data-theme="dark"] .detector-header h2 { color: var(--green-light); }
.detector-header p { font-size: 0.83rem; color: var(--text-muted); line-height: 1.6; }

.detect-row {
    display: flex; gap: 8px;
}
.detect-row input {
    flex: 1; padding: 0.75rem 1rem;
    border: 1.5px solid var(--border); border-radius: 8px;
    font-family: 'Poppins', sans-serif; font-size: 0.9rem;
    background: var(--bg); color: var(--fg);
    outline: none; transition: border-color 0.2s;
}
.detect-row input:focus { border-color: var(--green-mid); }
.detect-row button {
    padding: 0.75rem 1.25rem; background: var(--green-deep); color: var(--white);
    border: none; border-radius: 8px; font-family: 'Poppins', sans-serif;
    font-size: 0.875rem; font-weight: 600; cursor: pointer; white-space: nowrap;
    transition: background 0.2s;
}
.detect-row button:hover { background: var(--green-mid); }

/* ML Result */
.ml-result {
    margin-top: 1.25rem; border-radius: 12px; overflow: hidden;
    border: 2px solid;
}
.ml-result.is-myth { border-color: var(--myth-border); }
.ml-result.is-fact  { border-color: var(--fact-border); }

.ml-result-head {
    padding: 1rem 1.25rem;
    display: flex; align-items: center; justify-content: space-between; gap: 0.75rem;
}
.ml-result.is-myth .ml-result-head { background: var(--myth-bg); }
.ml-result.is-fact  .ml-result-head { background: var(--fact-bg); }

.ml-verdict {
    display: flex; align-items: center; gap: 8px;
    font-size: 1rem; font-weight: 700;
}
.ml-result.is-myth .ml-verdict { color: var(--myth-color); }
.ml-result.is-fact  .ml-verdict { color: var(--fact-color); }

.ml-conf-badge {
    font-size: 0.75rem; font-weight: 700; padding: 3px 10px; border-radius: 20px;
}
.ml-result.is-myth .ml-conf-badge { background: rgba(181,41,74,0.12); color: var(--myth-color); }
.ml-result.is-fact  .ml-conf-badge { background: rgba(26,124,74,0.12); color: var(--fact-color); }

.ml-result-body { padding: 1.1rem 1.25rem; }
.ml-result-body > p { font-size: 0.875rem; color: var(--text-muted); margin-bottom: 1rem; }

.ml-matches-label {
    font-size: 0.7rem; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase;
    color: var(--text-muted); margin-bottom: 0.65rem;
}
.ml-match {
    background: var(--bg); border: 1px solid var(--border); border-radius: 8px;
    padding: 0.9rem; margin-bottom: 0.6rem;
}
.ml-match-myth { font-size: 0.83rem; color: var(--myth-color); margin-bottom: 6px; }
.ml-match-fact  { font-size: 0.83rem; color: var(--fact-color); }

/* ML unavailable */
.ml-offline {
    margin-top: 1rem; padding: 0.75rem 1rem; border-radius: 8px;
    background: var(--cream-dark); border: 1px solid var(--border);
    font-size: 0.83rem; color: var(--text-muted);
    display: flex; align-items: center; gap: 8px;
}
@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) .ml-offline { background: rgba(255,255,255,0.05); }
}
:root[data-theme="dark"] .ml-offline { background: rgba(255,255,255,0.05); }

/* ── Divider ── */
.section-divider {
    border: none; border-top: 2px solid var(--border); margin: 0 0 2rem;
}

/* ── Filter bar ── */
.filter-bar {
    display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap;
    margin-bottom: 1.5rem;
}
.filter-btn {
    padding: 7px 18px; border-radius: 20px; border: 1.5px solid var(--border);
    background: var(--surface); color: var(--text-muted);
    font-family: 'Poppins', sans-serif; font-size: 0.82rem; font-weight: 500;
    text-decoration: none; cursor: pointer; transition: all 0.2s; white-space: nowrap;
}
.filter-btn:hover { border-color: var(--green-mid); color: var(--green-mid); }
.filter-btn.active { background: var(--green-mid); border-color: var(--green-mid); color: var(--white); }
.search-form { margin-left: auto; display: flex; gap: 6px; }
.search-form input {
    padding: 7px 14px; border: 1.5px solid var(--border); border-radius: 8px;
    font-family: 'Poppins', sans-serif; font-size: 0.85rem;
    background: var(--surface); color: var(--fg); width: 200px;
    outline: none; transition: border-color 0.2s;
}
.search-form input:focus { border-color: var(--green-mid); }
.search-form button {
    padding: 7px 14px; background: var(--green-mid); color: var(--white);
    border: none; border-radius: 8px; font-family: 'Poppins', sans-serif;
    font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: background 0.2s;
}
.search-form button:hover { background: var(--green-deep); }

/* Results count */
.results-info { font-size: 0.82rem; color: var(--text-muted); margin-bottom: 1.25rem; }

/* ── Myth cards grid ── */
.myth-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.25rem;
}
.myth-card {
    background: var(--surface); border: 1.5px solid var(--border);
    border-radius: 14px; overflow: hidden;
    transition: box-shadow 0.2s, transform 0.2s;
    display: flex; flex-direction: column;
}
.myth-card:hover {
    box-shadow: 0 6px 24px rgba(0,0,0,0.10);
    transform: translateY(-2px);
}

/* Myth half */
.myth-half {
    padding: 1.1rem 1.25rem;
    border-bottom: 1px solid var(--border);
    background: var(--myth-bg);
    flex: 1;
}
.myth-badge, .fact-badge {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: 0.68rem; font-weight: 700; letter-spacing: 0.08em;
    text-transform: uppercase; padding: 3px 9px;
    border-radius: 20px; margin-bottom: 0.6rem;
}
.myth-badge { color: var(--myth-color); background: rgba(181,41,74,0.1); }
.fact-badge  { color: var(--fact-color); background: rgba(26,124,74,0.1); }

.myth-half p, .fact-half p {
    font-size: 0.875rem; line-height: 1.65; color: var(--fg);
}

/* Fact half */
.fact-half {
    padding: 1.1rem 1.25rem;
    background: var(--fact-bg);
    flex: 1;
}

/* Card footer */
.myth-card-footer {
    padding: 0.7rem 1.25rem;
    background: var(--surface); border-top: 1px solid var(--border);
    display: flex; justify-content: space-between; align-items: center;
}
.cat-tag {
    font-size: 0.72rem; font-weight: 600; color: var(--text-muted);
    text-transform: capitalize;
}
.source-tag { font-size: 0.72rem; color: var(--text-muted); }

/* No results */
.no-results {
    text-align: center; padding: 4rem 1rem; color: var(--text-muted);
}
.no-results svg { margin-bottom: 1rem; opacity: 0.4; }
.no-results h3 { font-family: 'Lora', serif; font-size: 1.2rem; margin-bottom: 0.4rem; color: var(--fg); }
.no-results p  { font-size: 0.875rem; }

/* ── Footer ── */
footer {
    background: var(--ink); color: rgba(255,255,255,0.65);
    text-align: center; padding: 1.25rem 1rem;
    font-size: 0.8rem; line-height: 1.8;
}

/* ── Responsive ── */
@media (max-width: 900px) { .myth-grid { grid-template-columns: 1fr; } }
@media (max-width: 640px) {
    .detect-row { flex-direction: column; }
    .filter-bar { flex-direction: column; align-items: stretch; }
    .search-form { margin-left: 0; }
    .search-form input { width: 100%; }
}
</style>
</head>
<body>
<?php include '../admin/_back_strip.php'; ?>

<header class="navbar">
    <a href="../dashboard/index.php" class="logo">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#52b788" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
        </svg>
        Samburu Health
    </a>
    <nav class="nav-links">
        <a href="../dashboard/index.php"><?= __('Dashboard') ?></a>
        <a href="../health/index.php"><?= __('Health Education') ?></a>
        <a href="index.php"><?= __('Myth vs Fact') ?></a>
        <a href="../symptom/index.php"><?= __('Symptoms') ?></a>
        <div class="lang-switcher">
            <a href="../dashboard/language.php?lang=english&redirect=<?= urlencode('../myth/index.php') ?>"
               class="lang-btn <?= $lang === 'english'   ? 'active' : '' ?>">EN</a>
            <a href="../dashboard/language.php?lang=kiswahili&redirect=<?= urlencode('../myth/index.php') ?>"
               class="lang-btn <?= $lang === 'kiswahili' ? 'active' : '' ?>">SW</a>
            <a href="../dashboard/language.php?lang=maa&redirect=<?= urlencode('../myth/index.php') ?>"
               class="lang-btn <?= $lang === 'maa'       ? 'active' : '' ?>">MAA</a>
        </div>
        <a href="../auth/logout.php" class="nav-logout"><?= __('Logout') ?></a>
    </nav>
</header>

<!-- Hero -->
<div class="page-hero">
    <div class="hero-inner">
        <p class="hero-eyebrow"><?= __('Myth vs Fact') ?></p>
        <h1><?= __('Separate Truth from Myth') ?></h1>
        <p><?= __('Use our AI model to check health beliefs, and browse evidence-based corrections verified against WHO and Kenya Ministry of Health guidelines.') ?></p>
    </div>
</div>

<div class="content-wrap">

    <!-- AI Detector -->
    <div class="detector-card">
        <div class="detector-header">
            <div class="detector-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#52b788" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 2a10 10 0 1 0 10 10"/><path d="M12 8v4l3 3"/><circle cx="18" cy="6" r="3" fill="#52b788"/>
                </svg>
            </div>
            <div>
                <h2><?= __('AI Myth Detector') ?></h2>
                <p><?= __('Type a health statement you have heard and our ML model will analyse whether it is a myth or a fact.') ?></p>
            </div>
        </div>

        <form method="POST" action="" id="myth-form">
            <div class="detect-row">
                <input type="text" name="myth_input" id="myth-input"
                    placeholder="<?= htmlspecialchars(__('e.g. Vaccines cause autism in children…')) ?>"
                    value="<?= htmlspecialchars($myth_search) ?>"
                    autocomplete="off">
                <button type="submit"><?= __('Check Statement') ?></button>
            </div>
        </form>

        <?php if ($myth_done && $ml_result): ?>
            <div class="ml-result <?= $ml_result['is_myth'] ? 'is-myth' : 'is-fact' ?>">
                <div class="ml-result-head">
                    <span class="ml-verdict">
                        <?php if ($ml_result['is_myth']): ?>
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                            <?= __('This is likely a MYTH') ?>
                        <?php else: ?>
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><polyline points="9 12 11 14 15 10"/></svg>
                            <?= __('This appears to be a FACT') ?>
                        <?php endif; ?>
                    </span>
                    <span class="ml-conf-badge"><?= sprintf(__('%d%% confidence'), (int)$ml_result['confidence']) ?></span>
                </div>
                <div class="ml-result-body">
                    <p><?= htmlspecialchars(__($ml_result['message'])) ?></p>

                    <?php if (!empty($ml_matches)): ?>
                        <p class="ml-matches-label"><?= __('Related entries from our database') ?></p>
                        <?php foreach ($ml_matches as $match):
                            $mc = get_myth_content($match, $lang);
                        ?>
                            <div class="ml-match">
                                <p class="ml-match-myth">
                                    <strong><?= __('Myth') ?>:</strong> <?= htmlspecialchars($mc['myth']) ?>
                                </p>
                                <p class="ml-match-fact">
                                    <strong><?= __('Fact') ?>:</strong> <?= htmlspecialchars($mc['fact']) ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php elseif ($myth_done && !$ml_result): ?>
            <div class="ml-offline">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <?= __('ML API is currently unavailable. Make sure the Flask server is running on port 5000.') ?>
            </div>
        <?php endif; ?>
    </div>

    <hr class="section-divider">

    <!-- Filter bar -->
    <form method="GET" action="">
        <div class="filter-bar">
            <a href="?category=all<?= !empty($search) ? '&search='.urlencode($search) : '' ?>"
               class="filter-btn <?= $category === 'all' ? 'active' : '' ?>"><?= __('All') ?></a>
            <?php foreach ($categories as $cat): ?>
                <a href="?category=<?= urlencode($cat['category']) ?><?= !empty($search) ? '&search='.urlencode($search) : '' ?>"
                   class="filter-btn <?= $category === $cat['category'] ? 'active' : '' ?>">
                    <?= htmlspecialchars(__(ucfirst($cat['category']))) ?>
                </a>
            <?php endforeach; ?>
            <div class="search-form">
                <input type="text" name="search" placeholder="<?= htmlspecialchars(__('Search myths…')) ?>"
                    value="<?= htmlspecialchars($search) ?>">
                <input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>">
                <button type="submit">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <span style="position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0)"><?= __('Search') ?></span>
                </button>
            </div>
        </div>
    </form>

    <?php if (!empty($search) || $category !== 'all'): ?>
        <p class="results-info">
            <?= sprintf(__(count($entries) === 1 ? '%d entry found' : '%d entries found'), count($entries)) ?>
            <?= $category !== 'all' ? sprintf(__('in %s'), '<strong>' . htmlspecialchars(__(ucfirst($category))) . '</strong>') : '' ?>
            <?= !empty($search)     ? sprintf(__('for "%s"'), '<strong>' . htmlspecialchars($search) . '</strong>') : '' ?>
        </p>
    <?php endif; ?>

    <!-- Myth cards -->
    <?php if (empty($entries)): ?>
        <div class="no-results">
            <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <h3><?= __('No entries found') ?></h3>
            <p><?= __('Try a different search term or browse all categories.') ?></p>
        </div>
    <?php else: ?>
        <div class="myth-grid">
            <?php foreach ($entries as $entry):
                $c = get_myth_content($entry, $lang);
            ?>
                <div class="myth-card">
                    <div class="myth-half">
                        <span class="myth-badge">
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            <?= __('Myth') ?>
                        </span>
                        <p><?= htmlspecialchars($c['myth']) ?></p>
                    </div>
                    <div class="fact-half">
                        <span class="fact-badge">
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
                            <?= __('Fact') ?>
                        </span>
                        <p><?= htmlspecialchars($c['fact']) ?></p>
                    </div>
                    <div class="myth-card-footer">
                        <span class="cat-tag"><?= htmlspecialchars(__(ucfirst($entry['category']))) ?></span>
                        <span class="source-tag"><?= __('Source: WHO / Kenya MoH') ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<footer>
    <p>&copy; 2026 Samburu Health Platform</p>
    <p><?= __('Health education and basic telemedicine guidance for rural communities in Kenya') ?></p>
</footer>

</body>
</html>