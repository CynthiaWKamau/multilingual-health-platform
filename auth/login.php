<?php
// I start the session and handle login logic before any HTML output
session_start();
include '../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = 'Please enter your email and password.';
    } else {
        $stmt = $conn->prepare("SELECT id, full_name, password, role, preferred_language FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 0) {
            $error = 'No account found with that email address.';
        } else {
            $stmt->bind_result($id, $full_name, $hashed, $role, $preferred_language);
            $stmt->fetch();

            if (!password_verify($password, $hashed)) {
                $error = 'Incorrect password. Please try again.';
            } else {
                // Set session and redirect based on role
                $_SESSION['user_id']   = $id;
                $_SESSION['full_name'] = $full_name;
                $_SESSION['role']      = $role;
                $_SESSION['language']  = $preferred_language ?: 'english';

                if ($role === 'admin') {
                    header("Location: ../admin/index.php");
                } elseif ($role === 'chw') {
                    header("Location: ../chw/index.php");
                } else {
                    header("Location: ../dashboard/index.php");
                }
                exit;
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
<title>Sign In — Samburu Health</title>

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

    /* Page colors */
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
        --cream-dark:  #1a2e22;
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
    --cream-dark:  #1a2e22;
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

.logo svg { flex-shrink: 0; }

.nav-links { display: flex; align-items: center; gap: 1.5rem; }

.nav-links a {
    color: rgba(255,255,255,0.82);
    text-decoration: none;
    font-size: 0.88rem; font-weight: 500;
    transition: color 0.2s;
}
.nav-links a:hover { color: var(--white); }

.nav-register {
    background: var(--gold);
    color: var(--white) !important;
    padding: 7px 18px;
    border-radius: 6px;
    font-weight: 600 !important;
    transition: background 0.2s !important;
}
.nav-register:hover { background: var(--gold-light) !important; }

/* ── Main layout ── */
.auth-wrapper {
    flex: 1;
    display: grid;
    grid-template-columns: 1fr 1fr;
    min-height: calc(100vh - 64px - 60px);
}

/* Left panel — decorative */
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
    width: 400px; height: 400px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(82,183,136,0.18), transparent 70%);
    top: -80px; right: -80px;
}
.auth-panel::after {
    content: '';
    position: absolute;
    width: 300px; height: 300px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(201,145,61,0.12), transparent 70%);
    bottom: -60px; left: -60px;
}

.panel-logo {
    font-family: 'Lora', serif;
    font-size: 1.6rem; font-weight: 700;
    color: var(--white);
    display: flex; align-items: center; gap: 12px;
    margin-bottom: 2.5rem;
    position: relative; z-index: 1;
}

.panel-illus {
    position: relative; z-index: 1;
    width: 200px; height: 200px;
    border-radius: 50%;
    background: rgba(255,255,255,0.07);
    border: 2px solid rgba(255,255,255,0.15);
    display: flex; align-items: center; justify-content: center;
    margin-bottom: 2rem;
}

.panel-heading {
    font-family: 'Lora', serif;
    font-size: 1.6rem; font-weight: 700;
    color: var(--white);
    text-align: center;
    line-height: 1.4;
    position: relative; z-index: 1;
    margin-bottom: 1rem;
}

.panel-sub {
    color: rgba(255,255,255,0.72);
    font-size: 0.9rem;
    text-align: center;
    line-height: 1.7;
    position: relative; z-index: 1;
    max-width: 300px;
}

.panel-badges {
    display: flex; gap: 0.75rem; flex-wrap: wrap;
    justify-content: center;
    margin-top: 2rem;
    position: relative; z-index: 1;
}

.badge {
    background: rgba(255,255,255,0.12);
    border: 1px solid rgba(255,255,255,0.2);
    color: rgba(255,255,255,0.9);
    font-size: 0.75rem; font-weight: 500;
    padding: 5px 12px; border-radius: 20px;
}

/* ── Auth card ── */
.auth-card-wrap {
    display: flex; align-items: center; justify-content: center;
    padding: 2.5rem clamp(1.5rem, 4vw, 3.5rem);
    background: var(--bg);
}

.auth-card {
    width: 100%; max-width: 420px;
}

.auth-card h2 {
    font-family: 'Lora', serif;
    font-size: 1.75rem; font-weight: 700;
    color: var(--green-deep);
    margin-bottom: 0.35rem;
}

@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) .auth-card h2 { color: var(--green-light); }
}
:root[data-theme="dark"] .auth-card h2 { color: var(--green-light); }

.auth-subtitle {
    color: var(--text-muted);
    font-size: 0.9rem;
    margin-bottom: 1.75rem;
}

/* ── Alerts ── */
.alert {
    padding: 0.85rem 1rem;
    border-radius: 8px;
    font-size: 0.875rem;
    margin-bottom: 1.25rem;
    line-height: 1.6;
}
.alert-error   { background: var(--error-bg);   color: var(--error-text);   border-left: 4px solid var(--error-text); }
.alert-success { background: var(--success-bg); color: var(--success-text); border-left: 4px solid var(--success-text); }

/* ── Form ── */
.form-group { margin-bottom: 1.15rem; }

.form-group label {
    display: block;
    font-size: 0.825rem; font-weight: 600;
    color: var(--text-body);
    margin-bottom: 0.4rem;
    letter-spacing: 0.01em;
}
@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) .form-group label { color: var(--fg); }
}
:root[data-theme="dark"] .form-group label { color: var(--fg); }

.required { color: var(--gold); }

/* ── Input with icon ── */
.input-wrap {
    display: flex;
    align-items: center;
    border: 1.5px solid var(--border);
    border-radius: 8px;
    background: var(--surface);
    transition: border-color 0.2s, box-shadow 0.2s;
}
.input-wrap:focus-within {
    border-color: var(--green-mid);
    box-shadow: 0 0 0 3px rgba(45,106,79,0.14);
}
.input-wrap .icon-left {
    flex-shrink: 0;
    width: 18px; height: 18px;
    margin-left: 12px;
    color: var(--text-muted);
    pointer-events: none;
}
.input-wrap input {
    flex: 1;
    border: none !important;
    outline: none !important;
    box-shadow: none !important;
    background: transparent;
    padding: 0.75rem 0.5rem 0.75rem 0.6rem;
    font-family: 'Poppins', sans-serif;
    font-size: 0.9rem;
    color: var(--fg);
    min-width: 0;
}
/* ── Primary button ── */
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

/* ── Links row ── */
.form-meta {
    display: flex; justify-content: flex-end;
    margin-bottom: 1.25rem;
}
.form-meta a {
    font-size: 0.825rem;
    color: var(--green-mid);
    text-decoration: none;
    font-weight: 500;
}
.form-meta a:hover { text-decoration: underline; }

.auth-footer {
    text-align: center;
    margin-top: 1.5rem;
    font-size: 0.875rem;
    color: var(--text-muted);
}
.auth-footer a { color: var(--gold); text-decoration: none; font-weight: 600; }
.auth-footer a:hover { text-decoration: underline; }

/* ── Divider ── */
.divider {
    display: flex; align-items: center; gap: 0.75rem;
    margin: 1.25rem 0; color: var(--text-muted); font-size: 0.8rem;
}
.divider::before, .divider::after {
    content: ''; flex: 1; height: 1px; background: var(--border);
}

/* ── Footer ── */
footer {
    background: var(--ink);
    color: rgba(255,255,255,0.72);
    text-align: center;
    padding: 1rem;
    font-size: 0.8rem;
}

/* ── Responsive: stack on mobile ── */
@media (max-width: 768px) {
    .auth-wrapper { grid-template-columns: 1fr; }
    .auth-panel   { display: none; }
    .auth-card-wrap { padding: 2rem 1.25rem; }
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
        <a href="../index.php#services">Services</a>
        <a href="register.php" class="nav-register">Create Account</a>
    </nav>
</header>

<div class="auth-wrapper">

    <!-- Left decorative panel -->
    <div class="auth-panel">
        <div class="panel-logo">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#52b788" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
            </svg>
            Samburu Health
        </div>

        <div class="panel-illus">
            <svg width="90" height="90" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.85)" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 2a5 5 0 1 1 0 10A5 5 0 0 1 12 2z"/>
                <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
            </svg>
        </div>

        <h2 class="panel-heading">Welcome back to<br>Samburu Health</h2>
        <p class="panel-sub">Access health education, consult community workers, and track your wellbeing — all in your language.</p>

        <div class="panel-badges">
            <span class="badge">English</span>
            <span class="badge">Kiswahili</span>
            <span class="badge">Maa</span>
        </div>
    </div>

    <!-- Right: login form -->
    <div class="auth-card-wrap">
        <div class="auth-card">

            <h2>Sign In</h2>
            <p class="auth-subtitle">Access your health dashboard</p>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="">

                <div class="form-group">
                    <label for="email">Email Address <span class="required">*</span></label>
                    <div class="input-wrap">
                        <svg class="icon-left" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>
                        </svg>
                        <input type="email" id="email" name="email"
                            placeholder="you@example.com"
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password <span class="required">*</span></label>
                    <div class="input-wrap">
                        <svg class="icon-left" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    </div>
                </div>

                <div class="form-meta">
                    <a href="forgot_password.php">Forgot password?</a>
                </div>

                <button type="submit" class="btn-primary">Sign In</button>

            </form>

            <div class="auth-footer">
                Don't have an account? <a href="register.php">Create one here</a>
            </div>

        </div>
    </div>
</div>

<footer>
    <p>© 2026 Samburu Health Platform · Health education for rural communities</p>
</footer>

</body>
</html>