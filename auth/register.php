<?php
require_once '../config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $full_name  = trim($_POST['full_name']);
    $email      = trim($_POST['email']);
    $phone      = trim($_POST['phone']);
    $language   = $_POST['preferred_language'];
    $password   = $_POST['password'];
    $confirm    = $_POST['confirm_password'];

    // Basic validation
    if (empty($full_name) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';

    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';

    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';

    } else {
        // Check if email already exists
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = 'An account with this email already exists.';
        } else {
            // Hash password and insert
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO users 
                (full_name, email, phone, preferred_language, password) 
                VALUES (?, ?, ?, ?, ?)");

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
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

    <header class="navbar">
        <div class="logo">Samburu Health</div>
        <nav>
            <a href="../index.php">Home</a>
            <a href="../index.php#about">About</a>
            <a href="../index.php#services">Health Information</a>
            <a href="login.php" class="nav-login">Login</a>
        </nav>
    </header>

    <section class="auth-section">
        <div class="auth-card">

            <h2>Create Account</h2>
            <p class="auth-subtitle">Join the Samburu Health Platform</p>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($success) ?>
                    <br><a href="login.php">Click here to log in</a>
                </div>
            <?php endif; ?>

            <form method="POST" action="">

                <div class="form-group">
                    <label for="full_name">Full Name <span class="required">*</span></label>
                    <input 
                        type="text" 
                        id="full_name" 
                        name="full_name" 
                        placeholder="Enter your full name"
                        value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>"
                        required>
                </div>

                <div class="form-group">
                    <label for="email">Email Address <span class="required">*</span></label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        placeholder="Enter your email"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        required>
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input 
                        type="tel" 
                        id="phone" 
                        name="phone" 
                        placeholder="e.g. 0712 345 678"
                        value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="preferred_language">Preferred Language <span class="required">*</span></label>
                    <select id="preferred_language" name="preferred_language">
                        <option value="english"  <?= (($_POST['preferred_language'] ?? '') === 'english')   ? 'selected' : '' ?>>English</option>
                        <option value="kiswahili" <?= (($_POST['preferred_language'] ?? '') === 'kiswahili') ? 'selected' : '' ?>>Kiswahili</option>
                        <option value="maa"       <?= (($_POST['preferred_language'] ?? '') === 'maa')       ? 'selected' : '' ?>>Maa</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="password">Password <span class="required">*</span></label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="Minimum 6 characters"
                        required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password <span class="required">*</span></label>
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        placeholder="Repeat your password"
                        required>
                </div>

                <button type="submit" class="btn primary-btn full-width">
                    Create Account
                </button>

            </form>

            <p class="auth-footer">
                Already have an account? <a href="login.php">Log in here</a>
            </p>

        </div>
    </section>

    <footer>
        <p>© 2026 Samburu Health Platform</p>
        <p>Health education and basic telemedicine guidance</p>
    </footer>

</body>
</html>