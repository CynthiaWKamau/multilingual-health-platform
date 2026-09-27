<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$user_id   = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];
$role      = $_SESSION['role'];
$language  = $_SESSION['language'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Samburu Health</title>
    <link rel="stylesheet" href="/multilingual-health-platform/assets/css/style.css">
    <style>
        .dashboard-banner {
            background: #1b4332;
            padding: 60px 7%;
        }
        .dashboard-welcome-text {
            color: #a5d6a7;
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 2px;
            margin-bottom: 10px;
        }
        .dashboard-banner h1 {
            font-size: 42px;
            color: white;
            margin-bottom: 12px;
        }
        .banner-sub {
            font-size: 17px;
            color: #c8e6c9;
        }
        .dashboard-section {
            padding: 60px 7%;
            text-align: center;
        }
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-top: 35px;
        }
        .dash-card {
            display: block;
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 30px 20px;
            text-decoration: none;
            color: #263238;
            text-align: center;
            transition: all 0.2s;
        }
        .dash-card:hover {
            border-color: #2e7d32;
            box-shadow: 0 4px 16px rgba(46,125,50,0.1);
            transform: translateY(-3px);
        }
        .dash-icon {
            font-size: 36px;
            margin-bottom: 15px;
        }
        .dash-card h3 {
            font-size: 16px;
            margin-bottom: 10px;
            color: #1b4332;
        }
        .dash-card p {
            font-size: 13px;
            color: #607d8b;
            line-height: 1.5;
        }
        .account-section {
            background: #fafafa;
        }
        .account-card {
            max-width: 550px;
            margin: 30px auto 0;
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
        }
        .account-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 24px;
            border-bottom: 1px solid #f0f0f0;
        }
        .account-row:last-child {
            border-bottom: none;
        }
        .account-label {
            font-size: 14px;
            color: #78909c;
            font-weight: bold;
        }
        .account-value {
            font-size: 14px;
            color: #263238;
        }
        @media (max-width: 768px) {
            .dashboard-banner h1 { font-size: 28px; }
            .dashboard-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 480px) {
            .dashboard-grid { grid-template-columns: 1fr; }
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

    <section class="dashboard-banner">
        <div class="banner-content">
            <p class="dashboard-welcome-text">WELCOME BACK</p>
            <h1>Hello, <?= htmlspecialchars($full_name) ?> 👋</h1>
            <p class="banner-sub">
                Your preferred language is
                <strong><?= ucfirst(htmlspecialchars($language)) ?></strong>.
                Access your health resources below.
            </p>
        </div>
    </section>

    <section class="dashboard-section">
        <p class="section-label">HEALTH RESOURCES</p>
        <h2>What would you like to access today?</h2>

        <div class="dashboard-grid">

            <a href="../health/index.php" class="dash-card">
                <div class="dash-icon">📚</div>
                <h3>Health Education</h3>
                <p>Learn about maternal health, child health, immunization, nutrition, and common diseases.</p>
            </a>

            <a href="../myth/index.php" class="dash-card">
                <div class="dash-icon">⚖️</div>
                <h3>Myth vs Fact</h3>
                <p>Explore common health misconceptions and get the facts behind them.</p>
            </a>

            <a href="../symptom/index.php" class="dash-card">
                <div class="dash-icon">🩺</div>
                <h3>Symptom Guidance</h3>
                <p>Check common symptoms and learn when to seek professional medical care.</p>
            </a>

            <a href="language.php" class="dash-card">
                <div class="dash-icon">🌍</div>
                <h3>Change Language</h3>
                <p>Switch between Maa, Kiswahili, and English at any time.</p>
            </a>

        </div>
    </section>

    <section class="dashboard-section account-section">
        <p class="section-label">YOUR ACCOUNT</p>
        <h2>Account Details</h2>

        <div class="account-card">
            <div class="account-row">
                <span class="account-label">Full Name</span>
                <span class="account-value"><?= htmlspecialchars($full_name) ?></span>
            </div>
            <div class="account-row">
                <span class="account-label">Role</span>
                <span class="account-value"><?= ucfirst(str_replace('_', ' ', $role)) ?></span>
            </div>
            <div class="account-row">
                <span class="account-label">Preferred Language</span>
                <span class="account-value"><?= ucfirst(htmlspecialchars($language)) ?></span>
            </div>
        </div>
    </section>

    <footer>
        <p>© 2026 Samburu Health Platform</p>
        <p>Health education and basic telemedicine guidance</p>
    </footer>

</body>
</html>