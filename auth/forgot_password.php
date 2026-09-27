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
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

    <header class="navbar">
        <div class="logo">Samburu Health</div>
        <nav>
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