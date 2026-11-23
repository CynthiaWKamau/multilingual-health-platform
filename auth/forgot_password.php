<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';
include '../config/database.php';
session_start();

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email']);

    if (empty($email)) {
        $error = 'Please enter your email address.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';

    } else {
        $stmt = $conn->prepare("SELECT id, full_name FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 0) {
            $error = 'No account found with that email address.';
        } else {
            $stmt->bind_result($id, $full_name);
            $stmt->fetch();

            // Generate token
            $token      = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Save token
            $save = $conn->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
            $save->bind_param("iss", $id, $token, $expires_at);
            $save->execute();

            $reset_link = "http://localhost/multilingual-health-platform/auth/reset_password.php?token=" . $token;

            // Send email via PHPMailer
            $mail = new PHPMailer(true);

            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'ck697315@gmail.com';     
                $mail->Password   = 'pusq bjiz qzcm ytlr';       
                $mail->SMTPSecure = 'tls';
                $mail->Port       = 587;

                $mail->setFrom('ck697315@gmail.com', 'Samburu Health Platform');
                $mail->addAddress($email, $full_name);

                $mail->isHTML(true);
                $mail->Subject = 'Reset Your Password - Samburu Health';
                $mail->CharSet = 'UTF-8';
                $mail->Body    = '
                    <div style="font-family: Arial, sans-serif; max-width: 500px; margin: auto;">
                        <h2 style="color: #1b4332;">Samburu Health Platform</h2>
                        <p>Hello ' . htmlspecialchars($full_name) . ',</p>
                        <p>We received a request to reset your password. Click the button below:</p>
                        <a href="' . $reset_link . '"
                            style="display:inline-block; padding: 12px 24px; background:#2e7d32;
                            color:white; border-radius:6px; text-decoration:none; margin: 20px 0;">
                            Reset My Password
                        </a>
                        <p>This link expires in <strong>1 hour</strong>.</p>
                        <p>If you did not request this, ignore this email.</p>
                        <br>
                        <p style="color:#78909c; font-size:13px;">
                            Samburu Health Platform — Health education for rural communities
                        </p>
                    </div>
                ';

                $mail->send();
                $success = 'A password reset link has been sent to ' . htmlspecialchars($email) . '. Please check your inbox.';

            } catch (Exception $e) {
                $error = 'Email could not be sent. Error: ' . $mail->ErrorInfo;
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
    <title>Forgot Password — Samburu Health</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
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
    .nav-links { display: flex; align-items: center; gap: 1.1rem; flex-wrap: wrap; }
    .nav-links a {
        color: rgba(255,255,255,0.82); text-decoration: none;
        font-size: 0.83rem; font-weight: 500; white-space: nowrap; transition: color 0.2s;
    }
    .nav-links a:hover { color: var(--white); }
    .nav-login {
        background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.22);
        color: var(--white) !important; padding: 5px 14px; border-radius: 6px; font-weight: 600 !important;
    }
    .nav-login:hover { background: rgba(255,255,255,0.2) !important; }

    /* ── Auth section ── */
    .auth-section {
        flex: 1; display: flex; align-items: center; justify-content: center;
        padding: 3rem 1.25rem; position: relative; overflow: hidden;
        background: linear-gradient(135deg, var(--green-deep) 0%, var(--green-mid) 60%, #40916c 100%);
    }
    .auth-section::before {
        content: ''; position: absolute;
        width: 420px; height: 420px; border-radius: 50%;
        background: radial-gradient(circle, rgba(82,183,136,0.14), transparent 65%);
        top: -100px; right: -60px; pointer-events: none;
    }
    .auth-card {
        position: relative; z-index: 1;
        width: 100%; max-width: 440px;
        background: var(--surface); border: 1.5px solid var(--border);
        border-radius: 16px; padding: 2.25rem 2rem;
        box-shadow: 0 12px 40px rgba(0,0,0,0.22);
    }
    .auth-card h2 {
        font-family: 'Lora', serif; font-size: 1.5rem; font-weight: 700;
        color: var(--green-deep); margin-bottom: 0.35rem;
    }
    @media (prefers-color-scheme: dark) {
        :root:not([data-theme="light"]) .auth-card h2 { color: var(--green-light); }
    }
    :root[data-theme="dark"] .auth-card h2 { color: var(--green-light); }
    .auth-subtitle {
        font-size: 0.85rem; color: var(--text-muted);
        line-height: 1.6; margin-bottom: 1.5rem;
    }

    /* ── Alerts ── */
    .alert {
        padding: 0.8rem 1rem; border-radius: 8px;
        font-size: 0.875rem; line-height: 1.5; margin-bottom: 1.25rem;
    }
    .alert a { color: inherit; font-weight: 600; }
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

    /* ── Form ── */
    .form-group { display: flex; flex-direction: column; gap: 5px; margin-bottom: 1rem; }
    .form-group label { font-size: 0.8rem; font-weight: 600; color: var(--fg); }
    .required { color: #b5294a; }
    .form-group input {
        padding: 0.7rem 0.9rem;
        border: 1.5px solid var(--border); border-radius: 8px;
        font-family: 'Poppins', sans-serif; font-size: 0.875rem;
        background: var(--bg); color: var(--fg);
        outline: none; transition: border-color 0.2s;
        width: 100%;
    }
    .form-group input:focus { border-color: var(--green-mid); }

    .btn {
        padding: 0.75rem 1.75rem; border: none; border-radius: 8px;
        font-family: 'Poppins', sans-serif; font-size: 0.9rem; font-weight: 600;
        cursor: pointer; transition: background 0.2s; margin-top: 0.5rem;
    }
    .primary-btn { background: var(--green-mid); color: var(--white); }
    .primary-btn:hover { background: var(--green-deep); }
    .full-width { width: 100%; }

    .auth-footer {
        margin-top: 1.5rem; padding-top: 1.1rem;
        border-top: 1px solid var(--border);
        text-align: center; font-size: 0.83rem; color: var(--text-muted);
    }
    .auth-footer a { color: var(--green-mid); font-weight: 600; text-decoration: none; }
    .auth-footer a:hover { text-decoration: underline; }
    @media (prefers-color-scheme: dark) {
        :root:not([data-theme="light"]) .auth-footer a { color: var(--green-light); }
    }
    :root[data-theme="dark"] .auth-footer a { color: var(--green-light); }

    /* ── Footer ── */
    footer {
        background: var(--ink); color: rgba(255,255,255,0.65);
        text-align: center; padding: 1.25rem 1rem;
        font-size: 0.8rem; line-height: 1.8;
    }

    /* ── Responsive ── */
    @media (max-width: 600px) {
        .auth-card { padding: 1.75rem 1.25rem; }
    }
    </style>
</head>
<body>

    <header class="navbar">
        <a href="../index.php" class="logo">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#52b788" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
            </svg>
            Samburu Health
        </a>
        <nav class="nav-links">
            <a href="../index.php">Home</a>
            <a href="login.php" class="nav-login">Login</a>
        </nav>
    </header>

    <section class="auth-section">
        <div class="auth-card">

            <h2>Forgot Password</h2>
            <p class="auth-subtitle">
                Enter your email and we will send you a reset link.
            </p>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <?php if (!$success): ?>
            <form method="POST" action="">

                <div class="form-group">
                    <label for="email">Email Address <span class="required">*</span></label>
                    <input type="email" id="email" name="email"
                        placeholder="Enter your registered email"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        required>
                </div>

                <button type="submit" class="btn primary-btn full-width">
                    Send Reset Link
                </button>

            </form>
            <?php endif; ?>

            <p class="auth-footer">
                Remembered your password? <a href="login.php">Log in here</a>
            </p>

        </div>
    </section>

    <footer>
        <p>© 2026 Samburu Health Platform</p>
        <p>Health education and basic telemedicine guidance</p>
    </footer>

</body>
</html>