<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$user_id  = $_SESSION['user_id'];
$success  = '';
$allowed  = ['english', 'kiswahili', 'maa'];

// Handle save (POST only — keep GET just for pre-selecting via ?lang=)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $language = $_POST['preferred_language'] ?? '';
    if (in_array($language, $allowed)) {
        $stmt = $conn->prepare("UPDATE users SET preferred_language = ? WHERE id = ?");
        $stmt->bind_param("si", $language, $user_id);
        $stmt->execute();
        $_SESSION['language'] = $language;
        $success = 'Language updated successfully.';
    }
}

$current_language = $_SESSION['language'] ?? 'english';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Language — Samburu Health</title>

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
    --success-bg:  #f0fff4;
    --success-text:#1a7c4a;

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
        --success-bg:#122a1c;
        --success-text:#52b788;
        color-scheme: dark;
    }
}
:root[data-theme="dark"] {
    --bg:        #0d1f14;
    --surface:   #152a1e;
    --fg:        #e8f0e9;
    --text-muted:#8fa892;
    --border:    #2a4035;
    --success-bg:#122a1c;
    --success-text:#52b788;
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
}
.nav-logout:hover { background: rgba(255,255,255,0.2) !important; }

/* ── Centered layout ── */
.page-wrap {
    flex: 1;
    display: flex; align-items: center; justify-content: center;
    padding: 3rem 1.25rem;
}

/* ── Card ── */
.lang-card {
    background: var(--surface);
    border: 1.5px solid var(--border);
    border-radius: 16px;
    box-shadow: 0 4px 32px rgba(0,0,0,0.07);
    padding: 2.75rem 2.5rem;
    width: 100%; max-width: 480px;
}

/* Icon */
.card-icon {
    width: 60px; height: 60px; border-radius: 50%;
    background: linear-gradient(135deg, #e8f5ee, #d0f0e0);
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 1.5rem;
}
@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) .card-icon { background: linear-gradient(135deg, #1a3d2b, #2d6a4f); }
}
:root[data-theme="dark"] .card-icon { background: linear-gradient(135deg, #1a3d2b, #2d6a4f); }

.lang-card h2 {
    font-family: 'Lora', serif;
    font-size: 1.6rem; font-weight: 700;
    color: var(--green-deep);
    text-align: center;
    margin-bottom: 0.4rem;
}
@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) .lang-card h2 { color: var(--green-light); }
}
:root[data-theme="dark"] .lang-card h2 { color: var(--green-light); }

.lang-card .subtitle {
    color: var(--text-muted);
    font-size: 0.875rem;
    text-align: center;
    margin-bottom: 1.75rem;
    line-height: 1.6;
}

/* Alert */
.alert-success {
    background: var(--success-bg);
    color: var(--success-text);
    border-left: 4px solid var(--success-text);
    padding: 0.8rem 1rem;
    border-radius: 8px;
    font-size: 0.875rem;
    margin-bottom: 1.25rem;
    display: flex; align-items: center; gap: 8px;
}

/* ── Language options ── */
.lang-options { display: flex; flex-direction: column; gap: 0.85rem; margin-bottom: 1.5rem; }

.lang-option {
    display: flex; align-items: center; gap: 1rem;
    padding: 1rem 1.25rem;
    border: 1.5px solid var(--border);
    border-radius: 10px;
    cursor: pointer;
    transition: border-color 0.2s, background 0.2s, box-shadow 0.2s;
    user-select: none;
}
.lang-option:hover {
    border-color: var(--green-mid);
    background: rgba(45,106,79,0.05);
}
.lang-option.selected {
    border-color: var(--green-mid);
    background: rgba(45,106,79,0.07);
    box-shadow: 0 0 0 3px rgba(45,106,79,0.1);
}

/* Hide native radio; custom circle */
.lang-option input[type="radio"] { display: none; }

.radio-circle {
    width: 20px; height: 20px; border-radius: 50%;
    border: 2px solid var(--border);
    background: var(--bg);
    flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    transition: border-color 0.2s;
}
.lang-option.selected .radio-circle {
    border-color: var(--green-mid);
    background: var(--green-mid);
}
.radio-circle::after {
    content: '';
    width: 7px; height: 7px; border-radius: 50%;
    background: var(--white);
    opacity: 0;
    transition: opacity 0.15s;
}
.lang-option.selected .radio-circle::after { opacity: 1; }

.lang-flag {
    font-size: 1.6rem; flex-shrink: 0; line-height: 1;
}
.lang-info { flex: 1; }
.lang-info h3 {
    font-size: 0.95rem; font-weight: 600;
    color: var(--fg); margin-bottom: 2px;
}
.lang-info p {
    font-size: 0.8rem; color: var(--text-muted); margin: 0;
}
.lang-current-tag {
    font-size: 0.72rem; font-weight: 600;
    color: var(--green-mid);
    background: rgba(45,106,79,0.1);
    padding: 2px 8px; border-radius: 10px;
    flex-shrink: 0;
}
@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) .lang-current-tag { color: var(--green-light); background: rgba(82,183,136,0.15); }
}
:root[data-theme="dark"] .lang-current-tag { color: var(--green-light); background: rgba(82,183,136,0.15); }

/* ── Save button ── */
.btn-primary {
    display: block; width: 100%;
    padding: 0.85rem;
    background: var(--green-mid);
    color: var(--white);
    border: none; border-radius: 8px;
    font-family: 'Poppins', sans-serif;
    font-size: 0.95rem; font-weight: 600;
    cursor: pointer;
    transition: background 0.2s, transform 0.1s;
}
.btn-primary:hover  { background: var(--green-deep); }
.btn-primary:active { transform: scale(0.98); }

.back-link {
    display: block; text-align: center;
    margin-top: 1.25rem;
    font-size: 0.875rem;
    color: var(--gold);
    text-decoration: none;
    font-weight: 600;
}
.back-link:hover { text-decoration: underline; }

/* ── Footer ── */
footer {
    background: var(--ink);
    color: rgba(255,255,255,0.65);
    text-align: center;
    padding: 1.25rem 1rem;
    font-size: 0.8rem;
    line-height: 1.8;
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
        <a href="index.php">Dashboard</a>
        <a href="../health/index.php">Health Education</a>
        <a href="../myth/index.php">Myth vs Fact</a>
        <a href="../symptom/index.php">Symptoms</a>
        <a href="../auth/logout.php" class="nav-logout">Logout</a>
    </nav>
</header>

<div class="page-wrap">
    <div class="lang-card">

        <!-- Globe icon -->
        <div class="card-icon">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#2d6a4f" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <line x1="2" y1="12" x2="22" y2="12"/>
                <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
            </svg>
        </div>

        <h2>Choose Your Language</h2>
        <p class="subtitle">Select the language you would like to use for health content across the platform.</p>

        <?php if ($success): ?>
            <div class="alert-success">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="lang-form">
            <div class="lang-options">

                <label class="lang-option <?= $current_language === 'maa' ? 'selected' : '' ?>" id="opt-maa">
                    <input type="radio" name="preferred_language" value="maa"
                        <?= $current_language === 'maa' ? 'checked' : '' ?>>
                    <span class="radio-circle"></span>
                    <span class="lang-flag">🌍</span>
                    <div class="lang-info">
                        <h3>Maa</h3>
                        <p>Local community language of Samburu</p>
                    </div>
                    <?php if ($current_language === 'maa'): ?>
                        <span class="lang-current-tag">Current</span>
                    <?php endif; ?>
                </label>

                <label class="lang-option <?= $current_language === 'kiswahili' ? 'selected' : '' ?>" id="opt-kiswahili">
                    <input type="radio" name="preferred_language" value="kiswahili"
                        <?= $current_language === 'kiswahili' ? 'checked' : '' ?>>
                    <span class="radio-circle"></span>
                    <span class="lang-flag">🇰🇪</span>
                    <div class="lang-info">
                        <h3>Kiswahili</h3>
                        <p>National language of Kenya</p>
                    </div>
                    <?php if ($current_language === 'kiswahili'): ?>
                        <span class="lang-current-tag">Current</span>
                    <?php endif; ?>
                </label>

                <label class="lang-option <?= $current_language === 'english' ? 'selected' : '' ?>" id="opt-english">
                    <input type="radio" name="preferred_language" value="english"
                        <?= $current_language === 'english' ? 'checked' : '' ?>>
                    <span class="radio-circle"></span>
                    <span class="lang-flag">🇬🇧</span>
                    <div class="lang-info">
                        <h3>English</h3>
                        <p>English health resources</p>
                    </div>
                    <?php if ($current_language === 'english'): ?>
                        <span class="lang-current-tag">Current</span>
                    <?php endif; ?>
                </label>

            </div>

            <button type="submit" class="btn-primary">Save Language</button>
        </form>

        <a href="index.php" class="back-link">← Back to Dashboard</a>

    </div>
</div>

<footer>
    <p>© 2026 Samburu Health Platform</p>
    <p>Health education and basic telemedicine guidance for rural communities in Kenya</p>
</footer>

<script>
// Highlight selected option as user clicks
document.querySelectorAll('.lang-option').forEach(label => {
    label.addEventListener('click', () => {
        document.querySelectorAll('.lang-option').forEach(l => l.classList.remove('selected'));
        label.classList.add('selected');
    });
});
</script>
</body>
</html>