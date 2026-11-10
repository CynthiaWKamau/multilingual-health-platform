<?php
session_start();
include '../config/database.php';
include '../config/translate.php';
include '../config/activity.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['chw', 'admin'], true)) {
    header('Location: ../auth/login.php');
    exit();
}

$days = report_days();
activity_table($conn);

$since = "created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";

// Summary
$sum = report_rows($conn,
    "SELECT
        SUM(activity_type = 'symptom_check') AS symptoms,
        SUM(activity_type = 'symptom_check' AND result = 'high') AS high,
        SUM(activity_type = 'myth_check') AS myths,
        SUM(activity_type = 'article_view') AS views,
        COUNT(DISTINCT user_id) AS people
     FROM activity_log WHERE $since",
    'i', $days)[0] ?? [];

// Urgency breakdown
$urgency_names = ['high' => 'High — seek care immediately', 'medium' => 'Medium — visit facility soon', 'low' => 'Low — manage at home', 'unknown' => 'Not classified'];
$urgency = [];
foreach (report_rows($conn,
    "SELECT COALESCE(result, 'unknown') AS result, COUNT(*) AS n FROM activity_log
     WHERE activity_type = 'symptom_check' AND $since GROUP BY result ORDER BY FIELD(result, 'low', 'medium', 'high') DESC, n DESC",
    'i', $days) as $r) {
    $urgency[] = ['label' => $urgency_names[$r['result']] ?? ucfirst($r['result']), 'n' => $r['n']];
}

// Top symptoms
$top_symptoms = report_rows($conn,
    "SELECT detail, COUNT(*) AS n FROM activity_log
     WHERE activity_type = 'symptom_check' AND $since GROUP BY detail ORDER BY n DESC LIMIT 8",
    'i', $days);

// Recent high-urgency checks
$high_cases = report_rows($conn,
    "SELECT detail, language, created_at FROM activity_log
     WHERE activity_type = 'symptom_check' AND result = 'high' AND $since
     ORDER BY created_at DESC LIMIT 15",
    'i', $days);

// Myths
$verdicts = report_rows($conn,
    "SELECT CASE result WHEN 'myth' THEN 'Identified as myth' WHEN 'fact' THEN 'Identified as fact' ELSE 'Not classified' END AS label, COUNT(*) AS n
     FROM activity_log WHERE activity_type = 'myth_check' AND $since GROUP BY label ORDER BY n DESC",
    'i', $days);
$top_myths = report_rows($conn,
    "SELECT LOWER(detail) AS detail, COUNT(*) AS n FROM activity_log
     WHERE activity_type = 'myth_check' AND result = 'myth' AND $since GROUP BY LOWER(detail) ORDER BY n DESC LIMIT 8",
    'i', $days);

// Articles
$top_articles = report_rows($conn,
    "SELECT h.title, COUNT(a.id) AS n FROM activity_log a JOIN health_education h ON h.id = a.ref_id
     WHERE a.activity_type = 'article_view' AND a.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
     GROUP BY h.id, h.title ORDER BY n DESC LIMIT 8",
    'i', $days);

// Languages
$lang_names = ['english' => 'English', 'kiswahili' => 'Kiswahili', 'maa' => 'Maa'];
$languages = [];
foreach (report_rows($conn,
    "SELECT language, COUNT(*) AS n FROM activity_log WHERE $since GROUP BY language ORDER BY n DESC",
    'i', $days) as $r) {
    $languages[] = ['label' => $lang_names[$r['language']] ?? ucfirst($r['language']), 'n' => $r['n']];
}

$back = $_SESSION['role'] === 'admin' ? '../admin/index.php' : 'index.php';

// CSV export
if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="community-health-report-' . date('Y-m-d') . "-{$days}days.csv\"");
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['Community Health Report', "Last $days days", 'Generated ' . date('d M Y H:i')]);
    fputcsv($out, []);
    fputcsv($out, ['Section', 'Item', 'Count']);
    fputcsv($out, ['Summary', 'Active users', (int)($sum['people'] ?? 0)]);
    fputcsv($out, ['Summary', 'Symptom checks', (int)($sum['symptoms'] ?? 0)]);
    fputcsv($out, ['Summary', 'High urgency', (int)($sum['high'] ?? 0)]);
    fputcsv($out, ['Summary', 'Myth checks', (int)($sum['myths'] ?? 0)]);
    fputcsv($out, ['Summary', 'Articles read', (int)($sum['views'] ?? 0)]);
    $sections = [
        'Most checked symptoms'    => array_map(fn($r) => ['label' => $r['detail'], 'n' => $r['n']], $top_symptoms),
        'Symptom urgency'          => $urgency,
        'Identified as myths'      => array_map(fn($r) => ['label' => $r['detail'], 'n' => $r['n']], $top_myths),
        'Myth checker results'     => $verdicts,
        'Most read articles'       => array_map(fn($r) => ['label' => $r['title'], 'n' => $r['n']], $top_articles),
        'Languages used'           => $languages,
    ];
    foreach ($sections as $section => $rows) {
        foreach ($rows as $r) {
            fputcsv($out, [$section, $r['label'], $r['n']]);
        }
    }
    fputcsv($out, []);
    fputcsv($out, ['High-urgency checks', 'Date', 'Symptom', 'Language']);
    foreach ($high_cases as $c) {
        fputcsv($out, ['', date('Y-m-d H:i', strtotime($c['created_at'])), $c['detail'], $lang_names[$c['language']] ?? $c['language']]);
    }
    fclose($out);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#1a3d2b">
<title>Community Health Report — Samburu Health</title>
<link rel="manifest" href="../manifest.json">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/reports.css">
</head>
<body>
<?php include '../admin/_back_strip.php'; ?>

<header class="navbar">
    <a href="<?= $back ?>" class="logo">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#52b788" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
        </svg>
        Samburu Health
        <span class="role-pill">CHW</span>
    </a>
    <nav class="nav-links">
        <a href="<?= $back ?>">Dashboard</a>
        <a href="report.php">Community Report</a>
        <a href="../health/index.php">Health Education</a>
        <a href="../symptom/index.php">Symptoms</a>
        <a href="../auth/logout.php" class="nav-logout">Logout</a>
    </nav>
</header>

<div class="page-hero">
    <p class="hero-eyebrow">Community Health Report</p>
    <h1>What your community is asking about</h1>
    <p>Anonymous summary of symptom checks, myth checks and articles read on the platform, to help you plan community visits and health talks.</p>
</div>

<div class="content-wrap">

    <div class="toolbar">
        <div class="period">
            <?php foreach ([7, 30, 90, 365] as $d): ?>
                <a href="?days=<?= $d ?>" class="<?= $days === $d ? 'active' : '' ?>">Last <?= $d ?> days</a>
            <?php endforeach; ?>
        </div>
        <div class="actions">
            <a class="btn" href="?days=<?= $days ?>&export=csv"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg> Download for Excel</a>
            <button type="button" class="btn" onclick="window.print()"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg> Save as PDF</button>
        </div>
    </div>

    <p class="note">No names are shown. Individual users cannot be identified from this report.</p>

    <div class="stats">
        <div class="stat"><div class="stat-num"><?= (int)($sum['people'] ?? 0) ?></div><div class="stat-label">Active Users</div></div>
        <div class="stat"><div class="stat-num"><?= (int)($sum['symptoms'] ?? 0) ?></div><div class="stat-label">Symptom Checks</div></div>
        <div class="stat high"><div class="stat-num"><?= (int)($sum['high'] ?? 0) ?></div><div class="stat-label">High Urgency</div></div>
        <div class="stat"><div class="stat-num"><?= (int)($sum['myths'] ?? 0) ?></div><div class="stat-label">Myth Checks</div></div>
        <div class="stat"><div class="stat-num"><?= (int)($sum['views'] ?? 0) ?></div><div class="stat-label">Articles Read</div></div>
    </div>

    <section>
        <h2 class="section-title">Symptoms</h2>
        <div class="grid-2">
            <div class="panel">
                <h3>Most checked symptoms</h3>
                <?= bar_chart($top_symptoms, 'detail', 'No symptom checks in this period.') ?>
            </div>
            <div class="panel">
                <h3>Urgency of symptom checks</h3>
                <?= bar_chart($urgency, 'label', 'No symptom checks in this period.') ?>
            </div>
        </div>
    </section>

    <section>
        <h2 class="section-title">Recent high-urgency checks</h2>
        <?php if (!$high_cases): ?>
            <div class="table-wrap"><p class="empty">No high-urgency symptom checks in this period.</p></div>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Date</th><th>Symptom</th><th>Language</th></tr></thead>
                    <tbody>
                    <?php foreach ($high_cases as $c): ?>
                        <tr>
                            <td class="muted"><?= date('d M Y, H:i', strtotime($c['created_at'])) ?></td>
                            <td><span class="badge high">High</span> <?= htmlspecialchars($c['detail']) ?></td>
                            <td class="muted"><?= htmlspecialchars($lang_names[$c['language']] ?? $c['language']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section>
        <h2 class="section-title">Myths and education</h2>
        <div class="grid-2">
            <div class="panel">
                <h3>Statements most often identified as myths</h3>
                <?= bar_chart($top_myths, 'detail', 'No myths identified in this period.') ?>
            </div>
            <div class="panel">
                <h3>Myth checker results</h3>
                <?= bar_chart($verdicts, 'label', 'No myth checks in this period.') ?>
            </div>
            <div class="panel">
                <h3>Most read articles</h3>
                <?= bar_chart($top_articles, 'title', 'No articles read in this period.') ?>
            </div>
            <div class="panel">
                <h3>Languages used</h3>
                <?= bar_chart($languages, 'label', 'No activity in this period.') ?>
            </div>
        </div>
    </section>

</div>

<footer>
    <p>© 2026 Samburu Health Platform</p>
    <p>Community Health Worker Portal</p>
</footer>
<script src="../pwa.js"></script>
</body>
</html>
