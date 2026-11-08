<?php
session_start();
include '../config/database.php';
include '../config/translate.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$lang        = $_SESSION['language'] ?? 'english';
$ai_response = '';
$user_input  = '';
$error       = '';
$db_results  = [];
$ml_result   = null;
$search_done = false;

// ── Call ML API ───────────────────────────────────────────────────────────────
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

// ── Call Groq AI ──────────────────────────────────────────────────────────────
function ask_groq($question, $lang) {
    $api_key = GROQ_API_KEY;
    $url     = 'https://api.groq.com/openai/v1/chat/completions';

    $lang_instruction = match($lang) {
        'kiswahili' => 'Respond in Kiswahili.',
        'maa'       => 'Respond in simple English.',
        default     => 'Respond in English.'
    };

    $system_prompt = "You are a health education assistant for rural communities in Samburu County, Kenya.
You ONLY provide health information based on WHO guidelines and Kenya Ministry of Health recommendations.
You do NOT diagnose conditions. You do NOT prescribe medicine.
Keep responses clear, simple, and short — users may have low literacy.
Always recommend seeing a Community Health Worker or visiting a health facility for serious symptoms.
If asked anything outside health topics, politely decline.
" . $lang_instruction;

    $data = json_encode([
        'model'       => GROQ_MODEL,
        'messages'    => [
            ['role' => 'system', 'content' => $system_prompt],
            ['role' => 'user',   'content' => $question]
        ],
        'max_tokens'  => 400,
        'temperature' => 0.3
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);
    return $result['choices'][0]['message']['content'] ?? null;
}

// ── Handle form submission ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['symptom_input'])) {
    $user_input  = trim($_POST['symptom_input']);
    $search_done = true;

    if (empty($user_input)) {
        $error = __('Please describe your symptom or health question.');
    } else {
        // 1. Call ML classifier
        $ml_result = call_ml_api('classify-symptom', $user_input);

        // 2. Search database using ML prediction term (or raw input as fallback)
        $search_term = $ml_result['db_search_term'] ?? $user_input;
        $stmt = $conn->prepare(
            "SELECT * FROM symptom_guidance
             WHERE symptom LIKE ? OR possible_cause LIKE ? OR symptom_sw LIKE ? OR possible_cause_sw LIKE ?
             ORDER BY urgency DESC"
        );
        $like = '%' . $search_term . '%';
        $stmt->bind_param("ssss", $like, $like, $like, $like);
        $stmt->execute();
        $db_results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // 3. Ask Groq AI
        $ai_response = ask_groq($user_input, $lang);
    }
}

function urgency_style($urgency) {
    return match($urgency) {
        'high'   => ['color' => '#b5294a', 'bg' => '--urgency-high-bg',   'label' => 'Seek care immediately'],
        'medium' => ['color' => '#b56a00', 'bg' => '--urgency-med-bg',    'label' => 'Visit health facility soon'],
        default  => ['color' => '#1a7c4a', 'bg' => '--urgency-low-bg',    'label' => 'Can manage at home'],
    };
}
?>
<!DOCTYPE html>
<html lang="<?= $lang === 'kiswahili' ? 'sw' : 'en' ?>" translate="no">
<head>
<meta charset="UTF-8">
<meta name="google" content="notranslate">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= __('Symptom Guidance') ?> — Samburu Health</title>

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

    /* Urgency */
    --urgency-high-bg: #fff0f3;
    --urgency-med-bg:  #fff7ed;
    --urgency-low-bg:  #f0fff6;

    /* ML classifier blue */
    --ml-blue:    #1a56a0;
    --ml-blue-bg: #eef3fc;
    --ml-blue-border: #bdd1f7;

    --bg:      var(--cream);
    --surface: var(--white);
    --fg:      var(--text-body);
}
@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) {
        --bg: #0d1f14; --surface: #152a1e; --fg: #e8f0e9;
        --text-muted: #8fa892; --border: #2a4035;
        --urgency-high-bg: #2a0e16;
        --urgency-med-bg:  #2a1800;
        --urgency-low-bg:  #0e2418;
        --ml-blue-bg: #0e1e38; --ml-blue-border: #1e3a6a;
        color-scheme: dark;
    }
}
:root[data-theme="dark"] {
    --bg: #0d1f14; --surface: #152a1e; --fg: #e8f0e9;
    --text-muted: #8fa892; --border: #2a4035;
    --urgency-high-bg: #2a0e16;
    --urgency-med-bg:  #2a1800;
    --urgency-low-bg:  #0e2418;
    --ml-blue-bg: #0e1e38; --ml-blue-border: #1e3a6a;
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

/* ── Main content ── */
.content-wrap {
    flex: 1;
    padding: 2.5rem clamp(1.5rem, 7%, 5rem);
    max-width: 900px; width: 100%; margin: 0 auto;
}

/* ── Search card ── */
.search-card {
    background: var(--surface); border: 1.5px solid var(--border);
    border-radius: 16px; padding: 2rem; margin-bottom: 1.75rem;
}
.search-card h2 {
    font-family: 'Lora', serif; font-size: 1.25rem; font-weight: 700;
    color: var(--green-deep); margin-bottom: 0.35rem;
}
@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) .search-card h2 { color: var(--green-light); }
}
:root[data-theme="dark"] .search-card h2 { color: var(--green-light); }
.search-card > p { font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.25rem; }

.input-row { display: flex; gap: 8px; }
.input-row input {
    flex: 1; padding: 0.8rem 1rem;
    border: 1.5px solid var(--border); border-radius: 8px;
    font-family: 'Poppins', sans-serif; font-size: 0.9rem;
    background: var(--bg); color: var(--fg);
    outline: none; transition: border-color 0.2s;
}
.input-row input:focus { border-color: var(--green-mid); }
.input-row button {
    padding: 0.8rem 1.4rem; background: var(--green-mid); color: var(--white);
    border: none; border-radius: 8px;
    font-family: 'Poppins', sans-serif; font-size: 0.875rem; font-weight: 600;
    cursor: pointer; white-space: nowrap; transition: background 0.2s;
}
.input-row button:hover { background: var(--green-deep); }

/* Quick-select pills */
.quick-label {
    font-size: 0.75rem; color: var(--text-muted); margin: 1rem 0 0.5rem;
}
.quick-pills { display: flex; flex-wrap: wrap; gap: 8px; }
.quick-btn {
    padding: 6px 14px; border: 1.5px solid var(--border);
    border-radius: 20px; background: var(--bg); color: var(--text-muted);
    font-family: 'Poppins', sans-serif; font-size: 0.8rem;
    cursor: pointer; transition: all 0.2s;
}
.quick-btn:hover { border-color: var(--green-mid); color: var(--green-mid); background: var(--surface); }

/* Disclaimer banner */
.disclaimer {
    margin-top: 1.1rem; padding: 0.75rem 1rem;
    background: #fffbf0; border-left: 3px solid var(--gold);
    border-radius: 6px; font-size: 0.8rem; color: #7a5500;
    line-height: 1.6;
}
@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) .disclaimer { background: #1e1600; color: var(--gold-light); border-color: var(--gold); }
}
:root[data-theme="dark"] .disclaimer { background: #1e1600; color: var(--gold-light); border-color: var(--gold); }

/* Error banner */
.error-banner {
    background: #fff0f3; border: 1.5px solid #f4b8c4;
    color: #b5294a; border-radius: 8px;
    padding: 0.8rem 1rem; font-size: 0.875rem; margin-bottom: 1.25rem;
    display: flex; align-items: center; gap: 8px;
}
@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) .error-banner { background: #2a0e16; border-color: #5a2030; }
}
:root[data-theme="dark"] .error-banner { background: #2a0e16; border-color: #5a2030; }

/* Offline warning */
.offline-banner {
    background: var(--cream-dark); border: 1px solid var(--border);
    border-radius: 8px; padding: 0.75rem 1rem;
    font-size: 0.83rem; color: var(--text-muted); margin-bottom: 1.25rem;
    display: flex; align-items: center; gap: 8px;
}
@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) .offline-banner { background: rgba(255,255,255,0.05); }
}
:root[data-theme="dark"] .offline-banner { background: rgba(255,255,255,0.05); }

/* ── ML Classifier card ── */
.ml-card {
    background: var(--ml-blue-bg); border: 1.5px solid var(--ml-blue-border);
    border-radius: 14px; overflow: hidden; margin-bottom: 1.25rem;
}
.ml-card-header {
    background: var(--ml-blue); padding: 0.9rem 1.25rem;
    display: flex; align-items: center; justify-content: space-between; gap: 0.5rem;
}
.ml-card-header h3 { color: var(--white); font-size: 0.9rem; font-weight: 600; }
.ml-tech-badge {
    font-size: 0.7rem; padding: 3px 8px; border-radius: 4px;
    background: rgba(255,255,255,0.18); color: var(--white); font-weight: 600; white-space: nowrap;
}
.ml-card-body { padding: 1.25rem; }
.ml-prediction {
    font-family: 'Lora', serif; font-size: 1.1rem; font-weight: 700;
    color: var(--ml-blue); margin-bottom: 0.5rem;
}
@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) .ml-prediction { color: #7aaaee; }
}
:root[data-theme="dark"] .ml-prediction { color: #7aaaee; }

.ml-conf-text { font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.6rem; }
.conf-bar-track {
    height: 7px; background: var(--border); border-radius: 4px;
    overflow: hidden; margin-bottom: 1rem;
}
.conf-bar-fill {
    height: 100%; background: var(--ml-blue); border-radius: 4px; transition: width 0.5s ease;
}
.top3-label { font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: var(--text-muted); margin-bottom: 0.5rem; }
.top3-pills { display: flex; flex-wrap: wrap; gap: 7px; }
.top3-pill {
    padding: 4px 12px; border-radius: 20px;
    background: var(--surface); border: 1px solid var(--ml-blue-border);
    font-size: 0.78rem; color: var(--ml-blue); font-weight: 600;
}
@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) .top3-pill { color: #7aaaee; border-color: #1e3a6a; }
}
:root[data-theme="dark"] .top3-pill { color: #7aaaee; border-color: #1e3a6a; }

/* ── AI response card ── */
.ai-card {
    background: var(--surface); border: 1.5px solid var(--border);
    border-radius: 14px; overflow: hidden; margin-bottom: 1.25rem;
}
.ai-card-header {
    background: var(--green-mid); padding: 0.9rem 1.25rem;
    display: flex; align-items: center; gap: 10px;
}
.ai-card-header h3 { color: var(--white); font-size: 0.9rem; font-weight: 600; }
.ai-badge {
    font-size: 0.7rem; padding: 3px 8px; border-radius: 4px;
    background: rgba(255,255,255,0.18); color: var(--white); font-weight: 600;
}
.ai-card-body {
    padding: 1.35rem 1.5rem;
    font-size: 0.9rem; color: var(--fg); line-height: 1.85; white-space: pre-wrap;
}

/* ── DB Results ── */
.db-results-label {
    font-size: 0.72rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.1em; color: var(--text-muted); margin-bottom: 1rem;
}
.symptom-card {
    background: var(--surface); border: 1.5px solid var(--border);
    border-radius: 14px; overflow: hidden; margin-bottom: 1.1rem;
}
.symptom-card-header {
    padding: 1rem 1.25rem;
    display: flex; justify-content: space-between; align-items: center;
    gap: 0.75rem; border-bottom: 1px solid var(--border);
}
.symptom-card-header h3 {
    font-family: 'Lora', serif; font-size: 1rem; font-weight: 700;
    color: var(--green-deep);
}
@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) .symptom-card-header h3 { color: var(--green-light); }
}
:root[data-theme="dark"] .symptom-card-header h3 { color: var(--green-light); }

.urgency-badge {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: 0.72rem; font-weight: 700; padding: 4px 10px;
    border-radius: 20px; white-space: nowrap; flex-shrink: 0;
}
.symptom-card-body { padding: 1.1rem 1.25rem; }
.info-row { margin-bottom: 0.9rem; }
.info-row:last-child { margin-bottom: 0; }
.info-row-label {
    font-size: 0.68rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.08em; color: var(--text-muted); margin-bottom: 4px;
}
.info-row-value { font-size: 0.875rem; color: var(--fg); line-height: 1.65; }

/* No results */
.no-results-card {
    background: var(--surface); border: 1.5px solid var(--border);
    border-radius: 14px; padding: 2rem; text-align: center;
    color: var(--text-muted); font-size: 0.875rem;
}
.no-results-card p + p { margin-top: 0.4rem; }

/* ── Footer ── */
footer {
    background: var(--ink); color: rgba(255,255,255,0.65);
    text-align: center; padding: 1.25rem 1rem;
    font-size: 0.8rem; line-height: 1.8;
}

/* ── Responsive ── */
@media (max-width: 640px) {
    .input-row { flex-direction: column; }
    .symptom-card-header { flex-direction: column; align-items: flex-start; }
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
        <a href="../myth/index.php"><?= __('Myth vs Fact') ?></a>
        <a href="index.php"><?= __('Symptoms') ?></a>
        <div class="lang-switcher">
            <a href="../dashboard/language.php?lang=english&redirect=<?= urlencode('../symptom/index.php') ?>"
               class="lang-btn <?= $lang === 'english'   ? 'active' : '' ?>">EN</a>
            <a href="../dashboard/language.php?lang=kiswahili&redirect=<?= urlencode('../symptom/index.php') ?>"
               class="lang-btn <?= $lang === 'kiswahili' ? 'active' : '' ?>">SW</a>
            <a href="../dashboard/language.php?lang=maa&redirect=<?= urlencode('../symptom/index.php') ?>"
               class="lang-btn <?= $lang === 'maa'       ? 'active' : '' ?>">MAA</a>
        </div>
        <a href="../auth/logout.php" class="nav-logout"><?= __('Logout') ?></a>
    </nav>
</header>

<!-- Hero -->
<div class="page-hero">
    <div class="hero-inner">
        <p class="hero-eyebrow"><?= __('Symptom Guidance') ?></p>
        <h1><?= __('AI-Powered Health Guidance') ?></h1>
        <p><?= __('Describe your symptoms and get evidence-based advice from our ML classifier and AI health assistant, grounded in WHO and Kenya Ministry of Health guidelines.') ?></p>
    </div>
</div>

<div class="content-wrap">

    <!-- Search card -->
    <div class="search-card">
        <h2><?= __('What symptoms are you experiencing?') ?></h2>
        <p><?= __('Describe your symptom or health question below. You can write in English or Kiswahili.') ?></p>

        <form method="POST" action="index.php" id="symptom-form">
            <div class="input-row">
                <input type="text" name="symptom_input" id="symptom-input"
                    placeholder="<?= htmlspecialchars(__('e.g. My child has fever and vomiting…')) ?>"
                    value="<?= htmlspecialchars($user_input) ?>"
                    autocomplete="off">
                <button type="submit"><?= __('Get Guidance') ?></button>
            </div>
        </form>

        <p class="quick-label"><?= __('Quick select:') ?></p>
        <div class="quick-pills">
            <button type="button" class="quick-btn" onclick="setInput('Fever')"><?= __('Fever') ?></button>
            <button type="button" class="quick-btn" onclick="setInput('Diarrhoea')"><?= __('Diarrhoea') ?></button>
            <button type="button" class="quick-btn" onclick="setInput('Cough')"><?= __('Cough') ?></button>
            <button type="button" class="quick-btn" onclick="setInput('Headache')"><?= __('Headache') ?></button>
            <button type="button" class="quick-btn" onclick="setInput('Chest pain')"><?= __('Chest Pain') ?></button>
            <button type="button" class="quick-btn" onclick="setInput('Vomiting')"><?= __('Vomiting') ?></button>
            <button type="button" class="quick-btn" onclick="setInput('Abdominal pain')"><?= __('Abdominal Pain') ?></button>
        </div>

        <div class="disclaimer">
            <strong><?= __('Note:') ?></strong> <?= __('This tool provides health education only — it does not diagnose conditions. Always visit a health facility or contact a Community Health Worker for medical advice.') ?>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="error-banner">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($search_done && !$error): ?>

        <!-- ML Classifier result -->
        <?php if ($ml_result): ?>
            <div class="ml-card">
                <div class="ml-card-header">
                    <h3><?= __('ML Symptom Classifier') ?></h3>
                    <span class="ml-tech-badge">TF-IDF + Naive Bayes</span>
                </div>
                <div class="ml-card-body">
                    <p class="ml-prediction">
                        <?= sprintf(__('Identified: %s'), htmlspecialchars(__(ucwords(str_replace('_', ' ', $ml_result['predicted']))))) ?>
                    </p>
                    <p class="ml-conf-text"><?= sprintf(__('Confidence: %d%%'), (int)$ml_result['confidence']) ?></p>
                    <div class="conf-bar-track">
                        <div class="conf-bar-fill" style="width:<?= min(100, (int)$ml_result['confidence']) ?>%"></div>
                    </div>
                    <?php if (!empty($ml_result['top_3'])): ?>
                        <p class="top3-label"><?= __('Top predictions') ?></p>
                        <div class="top3-pills">
                            <?php foreach ($ml_result['top_3'] as $pred): ?>
                                <span class="top3-pill">
                                    <?= htmlspecialchars(__(ucwords(str_replace('_', ' ', $pred['symptom'])))) ?>
                                    (<?= (int)$pred['confidence'] ?>%)
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Groq AI response -->
        <?php if ($ai_response): ?>
            <div class="ai-card">
                <div class="ai-card-header">
                    <h3><?= __('AI Health Assistant') ?></h3>
                    <span class="ai-badge"><?= __('Based on WHO guidelines') ?></span>
                </div>
                <div class="ai-card-body"><?= htmlspecialchars($ai_response) ?></div>
            </div>
        <?php else: ?>
            <div class="offline-banner">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <?= __('AI assistant is currently unavailable. Showing database results only.') ?>
            </div>
        <?php endif; ?>

        <!-- Database results -->
        <?php if (!empty($db_results)): ?>
            <p class="db-results-label"><?= __('Related guidance from our database') ?></p>
            <?php foreach ($db_results as $entry):
                $u = urgency_style($entry['urgency']);
            ?>
                <div class="symptom-card">
    <div class="symptom-card-header">
        <h3><?= htmlspecialchars(t($entry, 'symptom')) ?></h3>
        <span class="urgency-badge" style="color:<?= $u['color'] ?>;background:var(<?= $u['bg'] ?>)">
            <?php if ($entry['urgency'] === 'high'): ?>
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            <?php elseif ($entry['urgency'] === 'medium'): ?>
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <?php else: ?>
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><polyline points="9 12 11 14 15 10"/></svg>
            <?php endif; ?>
            <?= __($u['label']) ?>
        </span>
    </div>
    <div class="symptom-card-body">
        <div class="info-row">
            <p class="info-row-label"><?= __('Possible Causes') ?></p>
            <p class="info-row-value"><?= htmlspecialchars(t($entry, 'possible_cause')) ?></p>
        </div>
        <div class="info-row">
            <p class="info-row-label"><?= __('Self Care at Home') ?></p>
            <p class="info-row-value"><?= htmlspecialchars(t($entry, 'self_care')) ?></p>
        </div>
        <div class="info-row">
            <p class="info-row-label"><?= __('When to Seek Care') ?></p>
            <p class="info-row-value"><?= htmlspecialchars(t($entry, 'when_to_seek_care')) ?></p>
        </div>
    </div>
</div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-results-card">
                <p><?= __('No specific database entry found for this symptom.') ?></p>
                <p><?= __('Please refer to the AI response above or visit your nearest health facility.') ?></p>
            </div>
        <?php endif; ?>

    <?php endif; ?>

</div>

<footer>
    <p>© 2026 Samburu Health Platform</p>
    <p><?= __('Health education and basic telemedicine guidance for rural communities in Kenya') ?></p>
</footer>

<script>
function setInput(val) {
    document.getElementById('symptom-input').value = val;
    document.getElementById('symptom-form').submit();
}
</script>
</body>
</html>