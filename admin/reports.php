<?php
session_start();
include '../config/database.php';
include '../config/activity.php';
include '../config/charts.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

$days = report_days();
activity_table($conn);

$since      = "created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
$role_names = ['community_member' => 'Community members', 'chw' => 'Health workers', 'admin' => 'Admins'];
$lang_names = ['english' => 'English', 'kiswahili' => 'Kiswahili', 'maa' => 'Maa'];
$type_names = ['symptom_check' => 'Symptom checks', 'myth_check' => 'Myth checks', 'article_view' => 'Article views'];

// Users
$by_role = [];
foreach (report_rows($conn, "SELECT role, COUNT(*) AS n FROM users GROUP BY role ORDER BY n DESC") as $r) {
    $by_role[] = ['label' => $role_names[$r['role']] ?? ucfirst($r['role']), 'n' => $r['n']];
}
$by_lang = [];
foreach (report_rows($conn, "SELECT COALESCE(preferred_language, 'english') AS lang, COUNT(*) AS n FROM users GROUP BY lang ORDER BY n DESC") as $r) {
    $by_lang[] = ['label' => $lang_names[$r['lang']] ?? ucfirst($r['lang']), 'n' => $r['n']];
}
$registrations = report_rows($conn,
    "SELECT DATE_FORMAT(created_at, '%b %Y') AS label, COUNT(*) AS n FROM users
     WHERE created_at >= DATE_SUB(DATE_FORMAT(NOW(), '%Y-%m-01'), INTERVAL 5 MONTH)
     GROUP BY DATE_FORMAT(created_at, '%Y-%m'), label ORDER BY DATE_FORMAT(created_at, '%Y-%m')");
$total_users = array_sum(array_column($by_role, 'n'));
$new_users   = (int)(report_rows($conn, "SELECT COUNT(*) AS n FROM users WHERE $since", 'i', $days)[0]['n'] ?? 0);

// Activity
$by_type = [];
$totals  = ['symptom_check' => 0, 'myth_check' => 0, 'article_view' => 0];
foreach (report_rows($conn, "SELECT activity_type, COUNT(*) AS n FROM activity_log WHERE $since GROUP BY activity_type", 'i', $days) as $r) {
    $totals[$r['activity_type']] = (int)$r['n'];
}
foreach ($totals as $type => $n) {
    $by_type[] = ['label' => $type_names[$type], 'n' => $n];
}
$active_users = (int)(report_rows($conn, "SELECT COUNT(DISTINCT user_id) AS n FROM activity_log WHERE $since", 'i', $days)[0]['n'] ?? 0);

// Activity over time
$buckets = trend_buckets($days);
$trend = trend_fill(report_rows($conn,
    "SELECT {$buckets['expr']} AS k, activity_type AS g, COUNT(*) AS n FROM activity_log
     WHERE $since GROUP BY k, g",
    'i', $days), $buckets['keys'], ['symptom_check', 'myth_check', 'article_view']);

// Health trends
$top_symptoms = report_rows($conn,
    "SELECT detail, COUNT(*) AS n FROM activity_log WHERE activity_type = 'symptom_check' AND $since
     GROUP BY detail ORDER BY n DESC LIMIT 8", 'i', $days);
$urgency = [];
foreach (report_rows($conn,
    "SELECT COALESCE(result, 'unknown') AS result, COUNT(*) AS n FROM activity_log
     WHERE activity_type = 'symptom_check' AND $since GROUP BY result ORDER BY FIELD(result, 'low', 'medium', 'high') DESC, n DESC",
    'i', $days) as $r) {
    $urgency[] = ['label' => ucfirst($r['result']), 'n' => $r['n']];
}
$verdicts = report_rows($conn,
    "SELECT CASE result WHEN 'myth' THEN 'Myth' WHEN 'fact' THEN 'Fact' ELSE 'Not classified' END AS label, COUNT(*) AS n
     FROM activity_log WHERE activity_type = 'myth_check' AND $since GROUP BY label ORDER BY n DESC", 'i', $days);
$top_articles = report_rows($conn,
    "SELECT h.title, COUNT(a.id) AS n FROM activity_log a JOIN health_education h ON h.id = a.ref_id
     WHERE a.activity_type = 'article_view' AND a.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
     GROUP BY h.id, h.title ORDER BY n DESC LIMIT 8", 'i', $days);
$activity_lang = [];
foreach (report_rows($conn, "SELECT language, COUNT(*) AS n FROM activity_log WHERE $since GROUP BY language ORDER BY n DESC", 'i', $days) as $r) {
    $activity_lang[] = ['label' => $lang_names[$r['language']] ?? ucfirst($r['language']), 'n' => $r['n']];
}
$top_users = report_rows($conn,
    "SELECT u.full_name, u.role, COUNT(a.id) AS n FROM activity_log a JOIN users u ON u.id = a.user_id
     WHERE a.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
     GROUP BY u.id, u.full_name, u.role ORDER BY n DESC LIMIT 5", 'i', $days);

// Content
$content = [
    ['label' => 'Health articles',     'n' => report_rows($conn, "SELECT COUNT(*) AS n FROM health_education")[0]['n'] ?? 0],
    ['label' => 'Myth vs fact entries', 'n' => report_rows($conn, "SELECT COUNT(*) AS n FROM myth_facts")[0]['n'] ?? 0],
    ['label' => 'Symptom guides',      'n' => report_rows($conn, "SELECT COUNT(*) AS n FROM symptom_guidance")[0]['n'] ?? 0],
];
$suggestions = [];
foreach (report_rows($conn, "SELECT status, COUNT(*) AS n FROM content_suggestions GROUP BY status ORDER BY FIELD(status, 'pending', 'approved', 'rejected')") as $r) {
    $suggestions[] = ['label' => ucfirst($r['status']), 'n' => $r['n']];
}

// CSV export
if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="samburu-health-report-' . date('Y-m-d') . "-{$days}days.csv\"");
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['Samburu Health Platform Report', "Last $days days", 'Generated ' . date('d M Y H:i')]);
    fputcsv($out, []);
    fputcsv($out, ['Section', 'Item', 'Count']);

    $sections = [
        'Users by role'              => $by_role,
        'Users by preferred language' => $by_lang,
        'Registrations by month'     => $registrations,
        'Activity by type'           => $by_type,
        'Activity by language'       => $activity_lang,
        'Top symptoms'               => array_map(fn($r) => ['label' => $r['detail'], 'n' => $r['n']], $top_symptoms),
        'Symptom urgency'            => $urgency,
        'Myth checker results'       => $verdicts,
        'Most read articles'         => array_map(fn($r) => ['label' => $r['title'], 'n' => $r['n']], $top_articles),
        'Content'                    => $content,
        'Content suggestions'        => $suggestions,
    ];
    fputcsv($out, ['Summary', 'Total users', $total_users]);
    fputcsv($out, ['Summary', "New users (last $days days)", $new_users]);
    fputcsv($out, ['Summary', "Active users (last $days days)", $active_users]);
    foreach ($sections as $section => $rows) {
        foreach ($rows as $r) {
            fputcsv($out, [$section, $r['label'], $r['n']]);
        }
    }
    foreach ($buckets['keys'] as $i => $k) {
        $n = $trend['symptom_check'][$k] + $trend['myth_check'][$k] + $trend['article_view'][$k];
        fputcsv($out, ['Activity over time', $buckets['labels'][$i], $n]);
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
<title>Platform Report — Samburu Health</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/reports.css">
</head>
<body class="theme-admin">

<header class="navbar">
    <a href="index.php" class="logo">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#9fa8da" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/>
        </svg>
        Samburu Health
        <span class="role-pill">Admin</span>
    </a>
    <nav class="nav-links">
        <a href="index.php">Dashboard</a>
        <a href="users.php">Users</a>
        <a href="content.php">Manage Content</a>
        <a href="suggestions.php">Suggestions</a>
        <a href="reports.php">Reports</a>
        <a href="../chw/report.php">Community Report</a>
        <a href="../auth/logout.php" class="nav-logout">Logout</a>
    </nav>
</header>

<div class="page-hero">
    <p class="hero-eyebrow">Platform Report</p>
    <h1>How the platform is being used</h1>
    <p>Users, activity, health trends and content across the whole platform.</p>
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

    <div class="stats">
        <div class="stat"><div class="stat-num"><?= $total_users ?></div><div class="stat-label">Total Users</div></div>
        <div class="stat"><div class="stat-num"><?= $new_users ?></div><div class="stat-label">New Users</div></div>
        <div class="stat"><div class="stat-num"><?= $active_users ?></div><div class="stat-label">Active Users</div></div>
        <div class="stat"><div class="stat-num"><?= $totals['symptom_check'] ?></div><div class="stat-label">Symptom Checks</div></div>
        <div class="stat"><div class="stat-num"><?= $totals['myth_check'] ?></div><div class="stat-label">Myth Checks</div></div>
        <div class="stat"><div class="stat-num"><?= $totals['article_view'] ?></div><div class="stat-label">Article Views</div></div>
    </div>

    <section>
        <h2 class="section-title">Activity</h2>
        <div class="grid-2 cols-2">
            <div class="panel wide">
                <h3>Platform activity over time</h3>
                <?= trend_chart($buckets['labels'], [
                    ['label' => 'Symptom checks', 'data' => $trend['symptom_check'], 'color' => '#2d6a4f'],
                    ['label' => 'Myth checks',    'data' => $trend['myth_check'],    'color' => '#c9913d'],
                    ['label' => 'Article views',  'data' => $trend['article_view'],  'color' => '#52b788'],
                ], 'No activity in this period.', 300) ?>
            </div>
            <div class="panel">
                <h3>Activity by type</h3>
                <?= chart('doughnut', $by_type, 'label', 'No activity in this period.') ?>
            </div>
            <div class="panel">
                <h3>Activity by language</h3>
                <?= chart('pie', $activity_lang, 'label', 'No activity in this period.') ?>
            </div>
        </div>
    </section>

    <section>
        <h2 class="section-title">Health trends</h2>
        <div class="grid-2 cols-2">
            <div class="panel"><h3>Most checked symptoms</h3><?= chart('hbar', $top_symptoms, 'detail', 'No symptom checks in this period.', ['series' => 'Checks', 'height' => 300]) ?></div>
            <div class="panel"><h3>Symptom urgency</h3><?= chart('doughnut', $urgency, 'label', 'No symptom checks in this period.', ['semantic' => true, 'height' => 300]) ?></div>
            <div class="panel"><h3>Myth checker results</h3><?= chart('doughnut', $verdicts, 'label', 'No myth checks in this period.', ['semantic' => true]) ?></div>
            <div class="panel"><h3>Most read articles</h3><?= bar_chart($top_articles, 'title', 'No articles read in this period.') ?></div>
        </div>
    </section>

    <section>
        <h2 class="section-title">Users</h2>
        <div class="grid-2 cols-2">
            <div class="panel"><h3>Users by role</h3><?= chart('doughnut', $by_role, 'label', 'No users yet.') ?></div>
            <div class="panel"><h3>Preferred language</h3><?= chart('pie', $by_lang, 'label', 'No users yet.') ?></div>
            <div class="panel"><h3>New registrations (last 6 months)</h3><?= chart('bar', $registrations, 'label', 'No registrations in this period.', ['series' => 'New users']) ?></div>
            <div class="panel">
                <h3>Most active users</h3>
                <?php if (!$top_users): ?>
                    <p class="empty">No activity in this period.</p>
                <?php else: ?>
                    <?= bar_chart(array_map(fn($u) => ['label' => $u['full_name'] . ' (' . ucfirst(str_replace('_', ' ', $u['role'])) . ')', 'n' => $u['n']], $top_users), 'label', '') ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section>
        <h2 class="section-title">Content</h2>
        <div class="grid-2 cols-2">
            <div class="panel"><h3>Content library</h3><?= chart('bar', $content, 'label', 'No content yet.', ['series' => 'Items']) ?></div>
            <div class="panel"><h3>CHW content suggestions</h3><?= chart('doughnut', $suggestions, 'label', 'No suggestions yet.', ['semantic' => true]) ?></div>
        </div>
    </section>

</div>

<footer>
    <p>© 2026 Samburu Health Platform — Admin Panel</p>
</footer>
<?= chart_scripts() ?>
</body>
</html>
