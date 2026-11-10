<?php
session_start();
include '../config/database.php';
include '../config/translate.php';
include '../config/activity.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$uid  = (int)$_SESSION['user_id'];
$lang = $_SESSION['language'] ?? 'english';
$days = report_days();
activity_table($conn);

$since = "created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";

// Totals
$totals = ['symptom_check' => 0, 'myth_check' => 0, 'article_view' => 0];
foreach (report_rows($conn,
    "SELECT activity_type, COUNT(*) AS n FROM activity_log WHERE user_id = ? AND $since GROUP BY activity_type",
    'ii', $uid, $days) as $r) {
    $totals[$r['activity_type']] = (int)$r['n'];
}

// Symptom checks
$symptoms = report_rows($conn,
    "SELECT detail, result, created_at FROM activity_log
     WHERE user_id = ? AND activity_type = 'symptom_check' AND $since
     ORDER BY created_at DESC LIMIT 20",
    'ii', $uid, $days);

// Myth checks
$myths = report_rows($conn,
    "SELECT detail, result, created_at FROM activity_log
     WHERE user_id = ? AND activity_type = 'myth_check' AND $since
     ORDER BY created_at DESC LIMIT 20",
    'ii', $uid, $days);

// Articles read
$articles = report_rows($conn,
    "SELECT h.*, COUNT(a.id) AS n, MAX(a.created_at) AS last_read
     FROM activity_log a JOIN health_education h ON h.id = a.ref_id
     WHERE a.user_id = ? AND a.activity_type = 'article_view' AND a.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
     GROUP BY h.id ORDER BY last_read DESC LIMIT 20",
    'ii', $uid, $days);

$urgency_label = ['high' => __('High'), 'medium' => __('Medium'), 'low' => __('Low')];
$verdict_label = ['myth' => __('Myth'), 'fact' => __('Fact')];
?>
<!DOCTYPE html>
<html lang="<?= $lang === 'english' ? 'en' : 'sw' ?>" translate="no">
<head>
<meta charset="UTF-8">
<meta name="google" content="notranslate">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#1a3d2b">
<title><?= __('My Activity') ?> — Samburu Health</title>
<link rel="manifest" href="../manifest.json">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/reports.css">
</head>
<body>
<?php include '../admin/_back_strip.php'; ?>

<header class="navbar">
    <a href="index.php" class="logo">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#52b788" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
        </svg>
        Samburu Health
    </a>
    <nav class="nav-links">
        <a href="index.php"><?= __('Dashboard') ?></a>
        <a href="../health/index.php"><?= __('Health Education') ?></a>
        <a href="../myth/index.php"><?= __('Myth vs Fact') ?></a>
        <a href="../symptom/index.php"><?= __('Symptoms') ?></a>
        <div class="lang-switcher">
            <a href="language.php?lang=english&redirect=<?= urlencode('my_activity.php') ?>" class="lang-btn <?= $lang === 'english' ? 'active' : '' ?>">EN</a>
            <a href="language.php?lang=kiswahili&redirect=<?= urlencode('my_activity.php') ?>" class="lang-btn <?= $lang === 'kiswahili' ? 'active' : '' ?>">SW</a>
            <a href="language.php?lang=maa&redirect=<?= urlencode('my_activity.php') ?>" class="lang-btn <?= $lang === 'maa' ? 'active' : '' ?>">MAA</a>
        </div>
        <a href="../auth/logout.php" class="nav-logout"><?= __('Logout') ?></a>
    </nav>
</header>
<?= maa_notice() ?>

<div class="page-hero">
    <p class="hero-eyebrow"><?= __('My Activity') ?></p>
    <h1><?= __('Your health activity') ?></h1>
    <p><?= __('A record of your symptom checks, myth checks and the articles you have read.') ?></p>
</div>

<div class="content-wrap">

    <div class="toolbar">
        <div class="period">
            <?php foreach ([7, 30, 90, 365] as $d): ?>
                <a href="?days=<?= $d ?>" class="<?= $days === $d ? 'active' : '' ?>"><?= sprintf(__('Last %d days'), $d) ?></a>
            <?php endforeach; ?>
        </div>
        <p class="note"><?= __('Only you can see this detailed list.') ?></p>
    </div>

    <div class="stats">
        <div class="stat">
            <div class="stat-num"><?= $totals['symptom_check'] ?></div>
            <div class="stat-label"><?= __('Symptom Checks') ?></div>
        </div>
        <div class="stat">
            <div class="stat-num"><?= $totals['myth_check'] ?></div>
            <div class="stat-label"><?= __('Myth Checks') ?></div>
        </div>
        <div class="stat">
            <div class="stat-num"><?= $totals['article_view'] ?></div>
            <div class="stat-label"><?= __('Articles Read') ?></div>
        </div>
    </div>

    <section>
        <h2 class="section-title"><?= __('Symptom Checks') ?></h2>
        <?php if (!$symptoms): ?>
            <div class="table-wrap"><p class="empty"><?= __('No symptom checks in this period.') ?></p></div>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th><?= __('Date') ?></th><th><?= __('Symptom') ?></th><th><?= __('Urgency') ?></th></tr></thead>
                    <tbody>
                    <?php foreach ($symptoms as $s): $u = $s['result']; ?>
                        <tr>
                            <td class="muted"><?= date('d M Y, H:i', strtotime($s['created_at'])) ?></td>
                            <td><?= htmlspecialchars(__($s['detail'])) ?></td>
                            <td><span class="badge <?= isset($urgency_label[$u]) ? $u : 'none' ?>"><?= htmlspecialchars($urgency_label[$u] ?? __('Unknown')) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section>
        <h2 class="section-title"><?= __('Myth Checks') ?></h2>
        <?php if (!$myths): ?>
            <div class="table-wrap"><p class="empty"><?= __('No myth checks in this period.') ?></p></div>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th><?= __('Date') ?></th><th><?= __('Statement') ?></th><th><?= __('Result') ?></th></tr></thead>
                    <tbody>
                    <?php foreach ($myths as $m): $v = $m['result']; ?>
                        <tr>
                            <td class="muted"><?= date('d M Y, H:i', strtotime($m['created_at'])) ?></td>
                            <td><?= htmlspecialchars($m['detail']) ?></td>
                            <td><span class="badge <?= isset($verdict_label[$v]) ? $v : 'none' ?>"><?= htmlspecialchars($verdict_label[$v] ?? __('Unknown')) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section>
        <h2 class="section-title"><?= __('Articles Read') ?></h2>
        <?php if (!$articles): ?>
            <div class="table-wrap"><p class="empty"><?= __('No articles read in this period.') ?></p></div>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th><?= __('Article') ?></th><th><?= __('Times Read') ?></th><th><?= __('Last Read') ?></th></tr></thead>
                    <tbody>
                    <?php foreach ($articles as $a): ?>
                        <tr>
                            <td><a href="../health/index.php" style="color:inherit"><?= htmlspecialchars(t($a, 'title')) ?></a></td>
                            <td><?= (int)$a['n'] ?></td>
                            <td class="muted"><?= date('d M Y', strtotime($a['last_read'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

</div>

<footer>
    <p>© 2026 Samburu Health Platform</p>
    <p><?= __('Health education and basic telemedicine guidance for rural communities in Kenya') ?></p>
</footer>
<script src="../pwa.js"></script>
</body>
</html>
