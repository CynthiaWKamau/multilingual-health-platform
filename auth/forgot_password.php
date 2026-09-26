<?php
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
        // Check if email exists
        $stmt = $conn->prepare("SELECT id, full_name FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 0) {
            $error = 'No account found with that email address.';
        } else {
            $stmt->bind_result($id, $full_name);
            $stmt->fetch();

            // Generate a reset token
            $token      = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Save token to database
            $save = $conn->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
            $save->bind_param("iss", $id, $token, $expires_at);
            $save->execute();

            // Build reset link
            $reset_link = "http://localhost/multilingual-health-platform/auth/reset_password.php?token=" . $token;

            $success = 'A password reset link has been generated. 
                        In a live system this would be emailed to you. 
                        For now, use the link below to reset your password.';

            // For development — show the link directly
            $dev_link = $reset_link;
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
                Enter your email address and we will send you a link to reset your password.
            </p>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($success) ?>
                    <br><br>
                    <a href="<?= $dev_link ?>">Click here to reset your password</a>
                </div>
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