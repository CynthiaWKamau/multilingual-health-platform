<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$user_id   = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];
$role      = $_SESSION['role'];
$language  = $_SESSION['language'] ?? 'english';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — Samburu Health</title>

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
        --bg:        #0d1f14;
        --surface:   #152a1e;
        --fg:        #e8f0e9;
        --text-muted:#8fa892;
        --border:    #2a4035;
        --cream-dark:#1a2e22;
        --card-shadow: 0 2px 16px rgba(0,0,0,0.35);
        color-scheme: dark;
    }
}
:root[data-theme="dark"] {
    --bg:        #0d1f14;
    --surface:   #152a1e;
    --fg:        #e8f0e9;
    --text-muted:#8fa892;
    --border:    #2a4035;
    --cream-dark:#1a2e22;
    --card-shadow: 0 2px 16px rgba(0,0,0,0.35);
    color-scheme: dark;
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: 'Poppins', sans-serif;
    background: var(--bg);
    color: var(--fg);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

/* ── Navbar ── */
.navbar {
    position: sticky; top: 0; z-index: 100;
    background: var(--green-deep);
    display: flex; align-items: center; justify-content: space-between;
    padding: 0 clamp(1rem, 5vw, 3rem);
    height: 64px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.25);
}
.logo {
    font-family: 'Lora', serif;
    font-size: 1.2rem; font-weight: 700;
    color: var(--white);
    display: flex; align-items: center; gap: 10px;
    text-decoration: none;
}
.nav-links { display: flex; align-items: center; gap: 1.25rem; }
.nav-links a {
    color: rgba(255,255,255,0.82);
    text-decoration: none;
    font-size: 0.85rem; font-weight: 500;
    transition: color 0.2s;
}
.nav-links a:hover { color: var(--white); }
.nav-logout {
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.22);
    color: var(--white) !important;
    padding: 6px 16px;
    border-radius: 6px;
    font-weight: 600 !important;
    transition: background 0.2s !important;
}
.nav-logout:hover { background: rgba(255,255,255,0.2) !important; }

/* ── Hero banner ── */
.hero {
    background: linear-gradient(135deg, var(--green-deep) 0%, var(--green-mid) 60%, #40916c 100%);
    padding: 3.5rem clamp(1.5rem, 7%, 5rem);
    position: relative; overflow: hidden;
}
.hero::before {
    content: '';
    position: absolute;
    width: 500px; height: 500px; border-radius: 50%;
    background: radial-gradient(circle, rgba(82,183,136,0.15), transparent 65%);
    top: -120px; right: -80px;
    pointer-events: none;
}
.hero::after {
    content: '';
    position: absolute;
    width: 320px; height: 320px; border-radius: 50%;
    background: radial-gradient(circle, rgba(201,145,61,0.1), transparent 65%);
    bottom: -80px; left: 10%;
    pointer-events: none;
}
.hero-inner {
    position: relative; z-index: 1;
    max-width: 700px;
}
.hero-eyebrow {
    font-size: 0.72rem; font-weight: 700;
    letter-spacing: 0.14em; text-transform: uppercase;
    color: var(--green-light);
    margin-bottom: 0.6rem;
}
.hero h1 {
    font-family: 'Lora', serif;
    font-size: clamp(1.75rem, 4vw, 2.6rem);
    font-weight: 700;
    color: var(--white);
    line-height: 1.25;
    margin-bottom: 0.75rem;
}
.hero-sub {
    font-size: 1rem; color: rgba(255,255,255,0.78);
    line-height: 1.7; max-width: 520px;
}
.hero-lang-badge {
    display: inline-flex; align-items: center; gap: 6px;
    background: rgba(255,255,255,0.12);
    border: 1px solid rgba(255,255,255,0.2);
    color: rgba(255,255,255,0.92);
    font-size: 0.8rem; font-weight: 500;
    padding: 5px 14px; border-radius: 20px;
    margin-top: 1.25rem;
}

/* ── Section wrapper ── */
.section {
    padding: 3rem clamp(1.5rem, 7%, 5rem);
}
.section-head {
    text-align: center;
    margin-bottom: 2rem;
}
.section-label {
    font-size: 0.72rem; font-weight: 700;
    letter-spacing: 0.14em; text-transform: uppercase;
    color: var(--gold);
    margin-bottom: 0.5rem;
}
.section-head h2 {
    font-family: 'Lora', serif;
    font-size: clamp(1.3rem, 3vw, 1.75rem);
    font-weight: 700;
    color: var(--green-deep);
}
@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) .section-head h2 { color: var(--green-light); }
}
:root[data-theme="dark"] .section-head h2 { color: var(--green-light); }

.section-desc {
    color: var(--text-muted); font-size: 0.9rem;
    margin-top: 0.4rem; line-height: 1.7;
}

/* ── Resource cards grid ── */
.resource-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1.25rem;
}
.res-card {
    display: flex; flex-direction: column;
    background: var(--surface);
    border: 1.5px solid var(--border);
    border-radius: 14px;
    padding: 1.75rem 1.5rem;
    text-decoration: none;
    color: var(--fg);
    transition: border-color 0.2s, box-shadow 0.2s, transform 0.2s;
}
.res-card:hover {
    border-color: var(--green-mid);
    box-shadow: 0 6px 24px rgba(45,106,79,0.14);
    transform: translateY(-3px);
}
.res-icon {
    width: 52px; height: 52px; border-radius: 12px;
    background: linear-gradient(135deg, #e8f5ee, #d0f0e0);
    display: flex; align-items: center; justify-content: center;
    margin-bottom: 1rem; flex-shrink: 0;
}
@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) .res-icon { background: linear-gradient(135deg, #1a3d2b, #2d6a4f); }
}
:root[data-theme="dark"] .res-icon { background: linear-gradient(135deg, #1a3d2b, #2d6a4f); }

.res-card h3 {
    font-family: 'Lora', serif;
    font-size: 1rem; font-weight: 600;
    color: var(--green-deep);
    margin-bottom: 0.45rem;
}
@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) .res-card h3 { color: var(--green-light); }
}
:root[data-theme="dark"] .res-card h3 { color: var(--green-light); }

.res-card p {
    font-size: 0.835rem; color: var(--text-muted);
    line-height: 1.65; flex: 1;
}
.res-arrow {
    margin-top: 1rem; align-self: flex-start;
    display: flex; align-items: center; gap: 4px;
    font-size: 0.8rem; font-weight: 600;
    color: var(--green-mid);
}
.res-arrow svg { transition: transform 0.2s; }
.res-card:hover .res-arrow svg { transform: translateX(3px); }

/* ── Account section ── */
.account-section { background: var(--cream-dark); }
@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) .account-section { background: #0f2518; }
}
:root[data-theme="dark"] .account-section { background: #0f2518; }

.account-card {
    max-width: 560px; margin: 0 auto;
    background: var(--surface);
    border: 1.5px solid var(--border);
    border-radius: 14px;
    overflow: hidden;
    box-shadow: var(--card-shadow);
}
.account-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--border);
}
.account-row:last-child { border-bottom: none; }
.acct-label {
    font-size: 0.8rem; font-weight: 600;
    color: var(--text-muted); text-transform: uppercase;
    letter-spacing: 0.05em;
}
.acct-value {
    font-size: 0.9rem; font-weight: 500; color: var(--fg);
}
.acct-badge {
    display: inline-block;
    background: linear-gradient(135deg, #e8f5ee, #d0f0e0);
    color: var(--green-mid);
    font-size: 0.78rem; font-weight: 600;
    padding: 3px 10px; border-radius: 20px;
}
@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) .acct-badge { background: rgba(45,106,79,0.25); color: var(--green-light); }
}
:root[data-theme="dark"] .acct-badge { background: rgba(45,106,79,0.25); color: var(--green-light); }

/* ── Quick actions strip ── */
.quick-strip {
    display: flex; gap: 0.85rem; flex-wrap: wrap;
    margin-top: 1.75rem; justify-content: center;
}
.quick-btn {
    display: inline-flex; align-items: center; gap: 8px;
    background: var(--surface);
    border: 1.5px solid var(--border);
    color: var(--fg);
    font-family: 'Poppins', sans-serif;
    font-size: 0.85rem; font-weight: 500;
    padding: 0.65rem 1.25rem; border-radius: 8px;
    text-decoration: none;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.quick-btn:hover {
    border-color: var(--green-mid);
    box-shadow: 0 2px 10px rgba(45,106,79,0.12);
}
.quick-btn.gold {
    background: var(--gold);
    border-color: var(--gold);
    color: var(--white);
}
.quick-btn.gold:hover { background: #b8802d; border-color: #b8802d; }

/* ── Footer ── */
footer {
    background: var(--ink);
    color: rgba(255,255,255,0.65);
    text-align: center;
    padding: 1.25rem 1rem;
    font-size: 0.8rem;
    line-height: 1.8;
    margin-top: auto;
}

/* ── Responsive ── */
@media (max-width: 900px) {
    .resource-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 540px) {
    .resource-grid { grid-template-columns: 1fr; }
    .hero { padding: 2.25rem 1.25rem; }
}
</style>
</head>
<body>

<header class="navbar">
    <a href="index.php" class="logo">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#52b788" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
        </svg>
        Samburu Health
    </a>
    <nav class="nav-links">
        <a href="../health/index.php">Health Education</a>
        <a href="../myth/index.php">Myth vs Fact</a>
        <a href="../symptom/index.php">Symptoms</a>
        <a href="language.php">Language</a>
        <a href="../auth/logout.php" class="nav-logout">Logout</a>
    </nav>
</header>

<!-- Hero banner -->
<section class="hero">
    <div class="hero-inner">
        <p class="hero-eyebrow">Welcome back</p>
        <h1>Hello, <?= htmlspecialchars($full_name) ?> 👋</h1>
        <p class="hero-sub">Access health education, myth-busting facts, and symptom guidance — all available in your language.</p>
        <div class="hero-lang-badge">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/>
                <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
            </svg>
            <?= ucfirst(htmlspecialchars($language)) ?>
        </div>
    </div>
</section>

<!-- Health resources -->
<section class="section">
    <div class="section-head">
        <p class="section-label">Health Resources</p>
        <h2>What would you like to access today?</h2>
        <p class="section-desc">Tap any card to explore that section of the platform.</p>
    </div>

    <div class="resource-grid">

        <a href="../health/index.php" class="res-card">
            <div class="res-icon">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#2d6a4f" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                </svg>
            </div>
            <h3>Health Education</h3>
            <p>Maternal health, child nutrition, immunization schedules, and common disease prevention — in your language.</p>
            <span class="res-arrow">
                Explore
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
                </svg>
            </span>
        </a>

        <a href="../myth/index.php" class="res-card">
            <div class="res-icon">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#2d6a4f" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
            </div>
            <h3>Myth vs Fact</h3>
            <p>Separate health myths from evidence-based facts, with context for local beliefs and cultural practices.</p>
            <span class="res-arrow">
                Explore
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
                </svg>
            </span>
        </a>

        <a href="../symptom/index.php" class="res-card">
            <div class="res-icon">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#2d6a4f" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
                </svg>
            </div>
            <h3>Symptom Guidance</h3>
            <p>Describe your symptoms and get guidance on whether to seek care at a clinic or manage at home.</p>
            <span class="res-arrow">
                Explore
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
                </svg>
            </span>
        </a>

        <a href="language.php" class="res-card">
            <div class="res-icon">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#2d6a4f" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/>
                    <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                </svg>
            </div>
            <h3>Change Language</h3>
            <p>Switch between English, Kiswahili, and Maa to read all content in your preferred language.</p>
            <span class="res-arrow">
                Switch
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
                </svg>
            </span>
        </a>

    </div>
</section>

<!-- Account details -->
<section class="section account-section">
    <div class="section-head">
        <p class="section-label">Your Account</p>
        <h2>Account Details</h2>
    </div>

    <div class="account-card">
        <div class="account-row">
            <span class="acct-label">Full Name</span>
            <span class="acct-value"><?= htmlspecialchars($full_name) ?></span>
        </div>
        <div class="account-row">
            <span class="acct-label">Role</span>
            <span class="acct-badge"><?= ucfirst(str_replace('_', ' ', $role)) ?></span>
        </div>
        <div class="account-row">
            <span class="acct-label">Preferred Language</span>
            <span class="acct-value"><?= ucfirst(htmlspecialchars($language)) ?></span>
        </div>
    </div>

    <div class="quick-strip">
        <a href="language.php" class="quick-btn">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/>
                <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
            </svg>
            Change Language
        </a>
        <a href="../auth/logout.php" class="quick-btn gold">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
            Logout
        </a>
    </div>
</section>

<footer>
    <p>© 2026 Samburu Health Platform</p>
    <p>Health education and basic telemedicine guidance for rural communities in Kenya</p>
</footer>

</body>
</html>