<?php
include '../config/database.php';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $full_name = trim($_POST['full_name']);
    $email     = trim($_POST['email']);
    $phone     = trim($_POST['phone']);
    $language  = $_POST['preferred_language'];
    $password  = $_POST['password'];
    $confirm   = $_POST['confirm_password'];

    if (empty($full_name) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = 'An account with this email already exists.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt   = $conn->prepare("INSERT INTO users (full_name, email, phone, preferred_language, password) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $full_name, $email, $phone, $language, $hashed);

            if ($stmt->execute()) {
                $success = 'Account created successfully. You can now log in.';
            } else {
                $error = 'Something went wrong. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Account — Samburu Health</title>

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
    --error-bg:    #fff0f0;
    --error-text:  #c0392b;
    --success-bg:  #f0fff4;
    --success-text:#1a7c4a;

    --bg:      var(--cream);
    --surface: var(--white);
    --fg:      var(--text-body);
}

@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) {
        --bg:      #0d1f14;
        --surface: #152a1e;
        --fg:      #e8f0e9;
        --text-muted:  #8fa892;
        --border:      #2a4035;
        --error-bg:    #2a1515;
        --error-text:  #f08080;
        --success-bg:  #122a1c;
        --success-text:#52b788;
        color-scheme: dark;
    }
}
:root[data-theme="dark"] {
    --bg:      #0d1f14;
    --surface: #152a1e;
    --fg:      #e8f0e9;
    --text-muted:  #8fa892;
    --border:      #2a4035;
    --error-bg:    #2a1515;
    --error-text:  #f08080;
    --success-bg:  #122a1c;
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
    font-size: 1.25rem; font-weight: 700;
    color: var(--white);
    display: flex; align-items: center; gap: 10px;
    text-decoration: none;
}
.nav-links { display: flex; align-items: center; gap: 1.5rem; }
.nav-links a {
    color: rgba(255,255,255,0.82);
    text-decoration: none;
    font-size: 0.88rem; font-weight: 500;
    transition: color 0.2s;
}
.nav-links a:hover { color: var(--white); }
.nav-login {
    background: rgba(255,255,255,0.12);
    border: 1px solid rgba(255,255,255,0.25);
    color: var(--white) !important;
    padding: 7px 18px;
    border-radius: 6px;
    font-weight: 600 !important;
    transition: background 0.2s !important;
}
.nav-login:hover { background: rgba(255,255,255,0.2) !important; }

/* ── Two-column layout ── */
.auth-wrapper {
    flex: 1;
    display: grid;
    grid-template-columns: 1fr 1fr;
    min-height: calc(100vh - 64px - 60px);
}

/* Decorative left panel */
.auth-panel {
    background: linear-gradient(160deg, var(--green-deep) 0%, var(--green-mid) 100%);
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    padding: 3rem 2.5rem;
    position: relative; overflow: hidden;
}
.auth-panel::before {
    content: '';
    position: absolute;
    width: 380px; height: 380px; border-radius: 50%;
    background: radial-gradient(circle, rgba(82,183,136,0.16), transparent 70%);
    top: -60px; left: -80px;
}
.auth-panel::after {
    content: '';
    position: absolute;
    width: 280px; height: 280px; border-radius: 50%;
    background: radial-gradient(circle, rgba(201,145,61,0.14), transparent 70%);
    bottom: -40px; right: -60px;
}

.panel-logo {
    font-family: 'Lora', serif;
    font-size: 1.5rem; font-weight: 700;
    color: var(--white);
    display: flex; align-items: center; gap: 12px;
    margin-bottom: 2.5rem;
    position: relative; z-index: 1;
}
.panel-illus {
    position: relative; z-index: 1;
    width: 190px; height: 190px; border-radius: 50%;
    background: rgba(255,255,255,0.07);
    border: 2px solid rgba(255,255,255,0.15);
    display: flex; align-items: center; justify-content: center;
    margin-bottom: 2rem;
}
.panel-heading {
    font-family: 'Lora', serif;
    font-size: 1.55rem; font-weight: 700;
    color: var(--white); text-align: center;
    line-height: 1.4; position: relative; z-index: 1;
    margin-bottom: 1rem;
}
.panel-sub {
    color: rgba(255,255,255,0.72);
    font-size: 0.875rem; text-align: center;
    line-height: 1.7; position: relative; z-index: 1;
    max-width: 290px;
}

/* Step indicators */
.steps {
    display: flex; flex-direction: column; gap: 0.85rem;
    margin-top: 2rem; position: relative; z-index: 1;
    width: 100%; max-width: 270px;
}
.step {
    display: flex; align-items: center; gap: 0.85rem;
    color: rgba(255,255,255,0.85);
    font-size: 0.85rem;
}
.step-num {
    width: 28px; height: 28px; border-radius: 50%;
    background: rgba(82,183,136,0.3);
    border: 1.5px solid rgba(82,183,136,0.6);
    color: var(--white);
    display: flex; align-items: center; justify-content: center;
    font-size: 0.75rem; font-weight: 700; flex-shrink: 0;
}

/* ── Auth form area ── */
.auth-card-wrap {
    display: flex; align-items: flex-start; justify-content: center;
    padding: 2.5rem clamp(1.5rem, 4vw, 3.5rem);
    background: var(--bg);
    overflow-y: auto;
}
.auth-card {
    width: 100%; max-width: 440px;
    padding-top: 0.5rem;
}
.auth-card h2 {
    font-family: 'Lora', serif;
    font-size: 1.7rem; font-weight: 700;
    color: var(--green-deep);
    margin-bottom: 0.35rem;
}
@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) .auth-card h2 { color: var(--green-light); }
}
:root[data-theme="dark"] .auth-card h2 { color: var(--green-light); }

.auth-subtitle { color: var(--text-muted); font-size: 0.88rem; margin-bottom: 1.5rem; }

/* ── Alerts ── */
.alert {
    padding: 0.85rem 1rem; border-radius: 8px;
    font-size: 0.875rem; margin-bottom: 1.25rem; line-height: 1.6;
}
.alert-error   { background: var(--error-bg);   color: var(--error-text);   border-left: 4px solid var(--error-text); }
.alert-success { background: var(--success-bg); color: var(--success-text); border-left: 4px solid var(--success-text); }
.alert-success a { color: var(--success-text); font-weight: 600; }

/* ── Form layout ── */
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0 1rem;
}
.form-group { margin-bottom: 1.1rem; }
.form-group label {
    display: block;
    font-size: 0.82rem; font-weight: 600;
    color: var(--fg);
    margin-bottom: 0.4rem; letter-spacing: 0.01em;
}
.required { color: var(--gold); }

.form-group input,
.form-group select {
    width: 100%;
    padding: 0.72rem 1rem;
    border: 1.5px solid var(--border); border-radius: 8px;
    font-family: 'Poppins', sans-serif;
    font-size: 0.88rem;
    background: var(--surface); color: var(--fg);
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
    -webkit-appearance: none;
}
.form-group input:focus,
.form-group select:focus {
    border-color: var(--green-mid);
    box-shadow: 0 0 0 3px rgba(45,106,79,0.14);
}

/* Input with icon */
.input-wrap { position: relative; }
.input-wrap > svg {
    position: absolute; left: 12px; top: 50%;
    transform: translateY(-50%);
    color: var(--text-muted); pointer-events: none;
    width: 17px; height: 17px;
}
.input-wrap input { padding-left: 2.4rem; }
/* Select custom arrow */
.select-wrap { position: relative; }
.select-wrap svg {
    position: absolute; right: 12px; top: 50%;
    transform: translateY(-50%);
    pointer-events: none; color: var(--text-muted);
    width: 16px; height: 16px;
}
.select-wrap select { padding-right: 2.2rem; }

/* Password hint */
.pw-hint {
    font-size: 0.75rem; color: var(--text-muted);
    margin-top: 0.35rem;
}
/* Strength bar */
.strength-bar {
    height: 4px; border-radius: 4px;
    background: var(--border);
    margin-top: 0.45rem; overflow: hidden;
}
.strength-fill {
    height: 100%; border-radius: 4px;
    width: 0; transition: width 0.3s, background 0.3s;
}

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
    margin-top: 0.5rem;
}
.btn-primary:hover  { background: var(--green-deep); }
.btn-primary:active { transform: scale(0.98); }

.auth-footer {
    text-align: center; margin-top: 1.5rem;
    font-size: 0.875rem; color: var(--text-muted);
}
.auth-footer a { color: var(--gold); text-decoration: none; font-weight: 600; }
.auth-footer a:hover { text-decoration: underline; }

/* Terms note */
.terms-note {
    font-size: 0.775rem; color: var(--text-muted);
    text-align: center; margin-top: 0.85rem; line-height: 1.6;
}

footer {
    background: var(--ink);
    color: rgba(255,255,255,0.72);
    text-align: center;
    padding: 1rem; font-size: 0.8rem;
}

/* ── Mobile ── */
@media (max-width: 768px) {
    .auth-wrapper { grid-template-columns: 1fr; }
    .auth-panel   { display: none; }
    .auth-card-wrap { padding: 2rem 1.25rem; }
    .form-row { grid-template-columns: 1fr; }
}
</style>
</head>
<body>

<header class="navbar">
    <a href="../index.php" class="logo">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#52b788" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
        </svg>
        Samburu Health
    </a>
    <nav class="nav-links">
        <a href="../index.php">Home</a>
        <a href="../index.php#about">About</a>
        <a href="login.php" class="nav-login">Sign In</a>
    </nav>
</header>

<div class="auth-wrapper">

    <!-- Left decorative panel -->
    <div class="auth-panel">
        <div class="panel-logo">
            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#52b788" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
            </svg>
            Samburu Health
        </div>

        <div class="panel-illus">
            <svg width="85" height="85" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.85)" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
                <!-- Shield with heart — community care -->
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                <path d="M9 12l2 2 4-4"/>
            </svg>
        </div>

        <h2 class="panel-heading">Join Samburu Health<br>Community</h2>
        <p class="panel-sub">Get trusted health information, connect with community health workers, and access services in your language.</p>

        <div class="steps">
            <div class="step"><span class="step-num">1</span> Create your free account</div>
            <div class="step"><span class="step-num">2</span> Choose your preferred language</div>
            <div class="step"><span class="step-num">3</span> Access health resources anytime</div>
        </div>
    </div>

    <!-- Right: registration form -->
    <div class="auth-card-wrap">
        <div class="auth-card">

            <h2>Create Account</h2>
            <p class="auth-subtitle">Join the Samburu Health Platform — it's free</p>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($success) ?>
                    &nbsp;<a href="login.php">Log in now →</a>
                </div>
            <?php endif; ?>

            <?php if (!$success): ?>
            <form method="POST" action="" id="reg-form">

                <!-- Full name -->
                <div class="form-group">
                    <label for="full_name">Full Name <span class="required">*</span></label>
                    <div class="input-wrap">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                        </svg>
                        <input type="text" id="full_name" name="full_name"
                            placeholder="Your full name"
                            value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required>
                    </div>
                </div>

                <!-- Email + Phone side by side on wider screens -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email <span class="required">*</span></label>
                        <div class="input-wrap">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>
                            </svg>
                            <input type="email" id="email" name="email"
                                placeholder="you@example.com"
                                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <div class="input-wrap">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.35 2 2 0 0 1 3.6 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.6a16 16 0 0 0 6 6l.96-.96a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21.54 16l.38.92z"/>
                            </svg>
                            <input type="tel" id="phone" name="phone"
                                placeholder="0712 345 678"
                                value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <!-- Preferred language -->
                <div class="form-group">
                    <label for="preferred_language">Preferred Language <span class="required">*</span></label>
                    <div class="select-wrap">
                        <select id="preferred_language" name="preferred_language">
                            <option value="english"   <?= (($_POST['preferred_language'] ?? '') === 'english')   ? 'selected' : '' ?>>English</option>
                            <option value="kiswahili" <?= (($_POST['preferred_language'] ?? '') === 'kiswahili') ? 'selected' : '' ?>>Kiswahili</option>
                            <option value="maa"       <?= (($_POST['preferred_language'] ?? '') === 'maa')       ? 'selected' : '' ?>>Maa</option>
                        </select>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9"/>
                        </svg>
                    </div>
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label for="password">Password <span class="required">*</span></label>
                    <div class="input-wrap">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                        <input type="password" id="password" name="password"
                            placeholder="Minimum 6 characters" required
                            oninput="checkStrength(this.value)">
                    </div>
                    <div class="strength-bar"><div class="strength-fill" id="strength-fill"></div></div>
                    <div class="pw-hint" id="pw-hint">Use at least 6 characters</div>
                </div>

                <!-- Confirm password -->
                <div class="form-group">
                    <label for="confirm_password">Confirm Password <span class="required">*</span></label>
                    <div class="input-wrap">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0 1 12 2.944a11.955 11.955 0 0 1-8.618 3.04A12.02 12.02 0 0 0 3 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        <input type="password" id="confirm_password" name="confirm_password"
                            placeholder="Repeat your password" required>
                    </div>
                </div>

                <button type="submit" class="btn-primary">Create Account</button>

                <p class="terms-note">
                    By creating an account you agree to our
                    <a href="#" style="color:var(--gold);text-decoration:none;">Terms of Service</a>
                    and
                    <a href="#" style="color:var(--gold);text-decoration:none;">Privacy Policy</a>.
                </p>

            </form>
            <?php endif; ?>

            <div class="auth-footer">
                Already have an account? <a href="login.php">Sign in here</a>
            </div>

        </div>
    </div>
</div>

<footer>
    <p>© 2026 Samburu Health Platform · Health education for rural communities</p>
</footer>

<script>
// Password strength indicator
function checkStrength(val) {
    const fill = document.getElementById('strength-fill');
    const hint = document.getElementById('pw-hint');
    let score = 0;
    if (val.length >= 6)  score++;
    if (val.length >= 10) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    const levels = [
        { pct: '0%',   color: 'transparent', label: 'Use at least 6 characters' },
        { pct: '25%',  color: '#e74c3c',      label: 'Weak' },
        { pct: '50%',  color: '#e67e22',      label: 'Fair' },
        { pct: '75%',  color: '#f1c40f',      label: 'Good' },
        { pct: '100%', color: '#27ae60',      label: 'Strong' },
    ];
    const l = levels[Math.min(score, 4)];
    fill.style.width      = l.pct;
    fill.style.background = l.color;
    hint.textContent      = l.label;
}
</script>
</body>
</html>