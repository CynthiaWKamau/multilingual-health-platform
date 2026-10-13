<?php
include '../config/database.php';
session_start();

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
            $stmt->bind_result($id, $full_name, $hashed_password, $role, $language);
            $stmt->fetch();

            if (!password_verify($password, $hashed_password)) {
                $error = 'Incorrect password. Please try again.';
            } else {
                // Set session variables
                $_SESSION['user_id']   = $id;
                $_SESSION['full_name'] = $full_name;
                $_SESSION['role']      = $role;
                $_SESSION['language']  = $language;

                // Redirect based on role
                    if ($role === 'admin') {
            header('Location: ../admin/index.php');
            } elseif ($role === 'chw') {
            header('Location: ../chw/index.php');
            } else {
            header('Location: ../dashboard/index.php');
            }
                exit();
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
    <title>Login — Samburu Health</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

    <header class="navbar">
        <div class="logo">Samburu Health</div>
        <nav>
            <a href="../index.php">Home</a>
            <a href="../index.php#about">About</a>
            <a href="../index.php#services">Health Information</a>
            <a href="register.php" class="nav-login">Create Account</a>
        </nav>
    </header>

    <section class="auth-section">
        <div class="auth-card">

            <h2>Welcome Back</h2>
            <p class="auth-subtitle">Log in to your Samburu Health account</p>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="">

                <div class="form-group">
                    <label for="email">Email Address <span class="required">*</span></label>
                    <input type="email" id="email" name="email"
                        placeholder="Enter your email"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        required>
                </div>

                <div class="form-group">
                    <label for="password">Password <span class="required">*</span></label>
                    <input type="password" id="password" name="password"
                        placeholder="Enter your password"
                        required>
                </div>

                <div class="form-group forgot-row">
                    <a href="forgot_password.php" class="forgot-link">Forgot password?</a>
                </div>

                <button type="submit" class="btn primary-btn full-width">
                    Log In
                </button>

            </form>

            <p class="auth-footer">
                Don't have an account? <a href="register.php">Create one here</a>
            </p>

        </div>
    </section>

    <footer>
        <p>© 2026 Samburu Health Platform</p>
        <p>Health education and basic telemedicine guidance</p>
    </footer>

</body>
</html>