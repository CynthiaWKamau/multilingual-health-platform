<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle both GET and POST
$language = $_POST['preferred_language'] ?? $_GET['lang'] ?? '';
$redirect = $_POST['redirect'] ?? $_GET['redirect'] ?? 'index.php';

if (!empty($language)) {
    // Validate language
    $allowed = ['english', 'kiswahili', 'maa'];
    if (in_array($language, $allowed)) {
        $stmt = $conn->prepare("UPDATE users SET preferred_language = ? WHERE id = ?");
        $stmt->bind_param("si", $language, $user_id);
        $stmt->execute();
        $_SESSION['language'] = $language;
    }
}

header('Location: ' . $redirect);
exit();
?>

$current_language = $_SESSION['language'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Language — Samburu Health</title>
    <link rel="stylesheet" href="/multilingual-health-platform/assets/css/style.css">
    <style>
        .language-section {
            min-height: calc(100vh - 160px);
            padding: 60px 7%;
            background: #eef7ee;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .language-box {
            background: white;
            border-radius: 12px;
            border: 1px solid #dde8dd;
            padding: 45px 40px;
            width: 100%;
            max-width: 500px;
        }
        .language-box h2 {
            font-size: 26px;
            color: #1b4332;
            margin-bottom: 6px;
        }
        .language-box p {
            color: #78909c;
            font-size: 15px;
            margin-bottom: 28px;
        }
        .lang-option {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 18px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            margin-bottom: 14px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .lang-option:hover {
            border-color: #2e7d32;
            background: #f1f8f1;
        }
        .lang-option.selected {
            border-color: #2e7d32;
            background: #e8f5e9;
        }
        .lang-option input[type="radio"] {
            accent-color: #2e7d32;
            width: 18px;
            height: 18px;
        }
        .lang-info h3 {
            font-size: 16px;
            color: #263238;
            margin-bottom: 2px;
        }
        .lang-info p {
            font-size: 13px;
            color: #78909c;
            margin-bottom: 0;
        }
        .save-btn {
            width: 100%;
            padding: 13px;
            background: #2e7d32;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 10px;
        }
        .save-btn:hover {
            background: #256628;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #2e7d32;
            font-size: 14px;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <header class="navbar">
        <div class="logo">Samburu Health</div>
        <nav>
            <a href="index.php">Home</a>
            <a href="language.php">Language</a>
            <a href="../health/index.php">Health Education</a>
            <a href="../myth/index.php">Myth vs Fact</a>
            <a href="../symptom/index.php">Symptom Guidance</a>
            <a href="../auth/logout.php" class="nav-login">Logout</a>
        </nav>
    </header>

    <section class="language-section">
        <div class="language-box">

            <h2>Choose Your Language</h2>
            <p>Select the language you would like to use for health content.</p>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="POST" action="">

                <label class="lang-option <?= $current_language === 'maa' ? 'selected' : '' ?>">
                    <input type="radio" name="preferred_language" value="maa"
                        <?= $current_language === 'maa' ? 'checked' : '' ?>>
                    <div class="lang-info">
                        <h3>🌍 Maa</h3>
                        <p>Local community language of Samburu</p>
                    </div>
                </label>

                <label class="lang-option <?= $current_language === 'kiswahili' ? 'selected' : '' ?>">
                    <input type="radio" name="preferred_language" value="kiswahili"
                        <?= $current_language === 'kiswahili' ? 'checked' : '' ?>>
                    <div class="lang-info">
                        <h3>🇰🇪 Kiswahili</h3>
                        <p>National language of Kenya</p>
                    </div>
                </label>

                <label class="lang-option <?= $current_language === 'english' ? 'selected' : '' ?>">
                    <input type="radio" name="preferred_language" value="english"
                        <?= $current_language === 'english' ? 'checked' : '' ?>>
                    <div class="lang-info">
                        <h3>🇬🇧 English</h3>
                        <p>English health resources</p>
                    </div>
                </label>

                <button type="submit" class="save-btn">Save Language</button>

            </form>

            <a href="index.php" class="back-link">← Back to Dashboard</a>

        </div>
    </section>

    <footer>
        <p>© 2026 Samburu Health Platform</p>
        <p>Health education and basic telemedicine guidance</p>
    </footer>

</body>
</html>