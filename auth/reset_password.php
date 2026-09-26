<?php
include '../config/database.php';
session_start();

$error   = '';
$success = '';
$valid   = false;
$token   = $_GET['token'] ?? '';

// Validate token
if (!empty($token)) {
    $stmt = $conn->prepare("SELECT pr.id, pr.user_id, pr.expires_at FROM password_resets pr WHERE pr.token = ? AND pr.used = 0");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $error = 'This reset link is invalid or has already been used.';
    } else {
        $row        = $result->fetch_assoc();
        $expires_at = $row['expires_at'];
        $reset_id   = $row['id'];
        $user_id    = $row['user_id'];

        if (strtotime($expires_at) < time()) {
            $error = 'This reset link has expired. Please request a new one.';
        } else {
            $valid = true;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid) {

    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];

    if (empty($password)) {
        $error = 'Please enter a new password.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        // Update password
        $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $update->bind_param("si", $hashed, $user_id);
        $update->execute();

        // Mark token as used
        $mark = $conn->prepare("UPDATE password_resets SET used = 1 WHERE id = ?");
        $mark->bind_param("i", $reset_id);
        $mark->execute();

        $success = 'Your password has been reset successfully.';
        $valid   = false;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password — Samburu Health</title>
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

            <h2>Reset Password</h2>
            <p class="auth-subtitle">Enter your new password below.</p>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($error) ?>
                    <br><br>
                    <a href="forgot_password.php">Request a new reset link</a>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($success) ?>
                    <br><br>
                    <a href="login.php">Click here to log in</a>
                </div>
            <?php endif; ?>

            <?php if ($valid): ?>
            <form method="POST" action="">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                <div class="form-group">
                    <label for="password">New Password <span class="required">*</span></label>
                    <input type="password" id="password" name="password"
                        placeholder="Minimum 6 characters" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password <span class="required">*</span></label>
                    <input type="password" id="confirm_password" name="confirm_password"
                        placeholder="Repeat your new password" required>
                </div>

                <button type="submit" class="btn primary-btn full-width">
                    Reset Password
                </button>

            </form>
            <?php endif; ?>

            <p class="auth-footer">
                <a href="login.php">Back to Login</a>
            </p>

        </div>
    </section>

    <footer>
        <p>© 2026 Samburu Health Platform</p>
        <p>Health education and basic telemedicine guidance</p>
    </footer>

</body>
</html>