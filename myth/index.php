<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$lang     = $_SESSION['language'] ?? 'english';
$category = $_GET['category'] ?? 'all';
$search   = $_GET['search'] ?? '';

// Build query
if ($category !== 'all' && !empty($search)) {
    $stmt = $conn->prepare("SELECT * FROM myth_facts WHERE category = ? AND (myth LIKE ? OR fact LIKE ?) ORDER BY id");
    $like = '%' . $search . '%';
    $stmt->bind_param("sss", $category, $like, $like);
} elseif ($category !== 'all') {
    $stmt = $conn->prepare("SELECT * FROM myth_facts WHERE category = ? ORDER BY id");
    $stmt->bind_param("s", $category);
} elseif (!empty($search)) {
    $stmt = $conn->prepare("SELECT * FROM myth_facts WHERE myth LIKE ? OR fact LIKE ? ORDER BY id");
    $like = '%' . $search . '%';
    $stmt->bind_param("ss", $like, $like);
} else {
    $stmt = $conn->prepare("SELECT * FROM myth_facts ORDER BY category, id");
}

$stmt->execute();
$result = $stmt->get_result();
$entries = $result->fetch_all(MYSQLI_ASSOC);

// Get content in correct language
function get_myth_content($entry, $lang) {
    if ($lang === 'kiswahili') {
        return [
            'myth' => !empty($entry['myth_sw']) ? $entry['myth_sw'] : $entry['myth'],
            'fact' => !empty($entry['fact_sw']) ? $entry['fact_sw'] : $entry['fact']
        ];
    } elseif ($lang === 'maa') {
        return [
            'myth' => !empty($entry['myth_maa']) ? $entry['myth_maa'] : $entry['myth'],
            'fact' => !empty($entry['fact_maa']) ? $entry['fact_maa'] : $entry['fact']
        ];
    }
    return [
        'myth' => $entry['myth'],
        'fact' => $entry['fact']
    ];
}

// Get unique categories
$cat_stmt = $conn->prepare("SELECT DISTINCT category FROM myth_facts ORDER BY category");
$cat_stmt->execute();
$cat_result = $cat_stmt->get_result();
$categories = $cat_result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Myth vs Fact — Samburu Health</title>
    <link rel="stylesheet" href="/multilingual-health-platform/assets/css/style.css">
    <style>
        .page-banner {
            background: #1b4332;
            padding: 50px 7%;
            color: white;
        }
        .page-banner h1 {
            font-size: 36px;
            color: white;
            margin-bottom: 8px;
        }
        .page-banner p {
            color: #c8e6c9;
            font-size: 16px;
        }
        .myth-section {
            padding: 50px 7%;
        }
        .filter-bar {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
            margin-bottom: 35px;
        }
        .filter-btn {
            padding: 9px 20px;
            border-radius: 20px;
            border: 1.5px solid #ddd;
            background: white;
            color: #607d8b;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }
        .filter-btn:hover,
        .filter-btn.active {
            background: #2e7d32;
            color: white;
            border-color: #2e7d32;
        }
        .search-box {
            margin-left: auto;
            display: flex;
            gap: 8px;
        }
        .search-box input {
            padding: 9px 14px;
            border: 1.5px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            width: 220px;
        }
        .search-box input:focus {
            outline: none;
            border-color: #2e7d32;
        }
        .search-box button {
            padding: 9px 16px;
            background: #2e7d32;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }
        .myth-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
        }
        .myth-card {
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #e0e0e0;
            background: white;
            transition: all 0.2s;
        }
        .myth-card:hover {
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
            transform: translateY(-2px);
        }
        .myth-part {
            padding: 20px 24px;
        }
        .myth-label {
            display: inline-block;
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 1px;
            padding: 4px 10px;
            border-radius: 4px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        .label-myth {
            background: #ffebee;
            color: #c62828;
        }
        .label-fact {
            background: #e8f5e9;
            color: #2e7d32;
        }
        .myth-part p {
            font-size: 15px;
            line-height: 1.6;
            color: #37474f;
        }
        .myth-divider {
            height: 1px;
            background: #f0f0f0;
            margin: 0 24px;
        }
        .myth-footer {
            padding: 12px 24px;
            background: #fafafa;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .myth-category-tag {
            font-size: 12px;
            color: #78909c;
            text-transform: capitalize;
            font-weight: bold;
        }
        .expand-btn {
            background: none;
            border: none;
            color: #2e7d32;
            font-size: 13px;
            font-weight: bold;
            cursor: pointer;
        }
        .no-results {
            text-align: center;
            padding: 60px;
            color: #78909c;
        }
        .no-results h3 {
            font-size: 20px;
            margin-bottom: 8px;
        }
        .counter-bar {
            background: #e8f5e9;
            border-radius: 8px;
            padding: 14px 20px;
            margin-bottom: 28px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            color: #2e7d32;
            font-weight: bold;
        }
        /* LANG SWITCHER */
        .lang-switcher {
            display: flex;
            align-items: center;
        }
        .lang-dropdown {
            padding: 8px 12px;
            border: 1px solid #2e7d32;
            border-radius: 6px;
            color: #2e7d32;
            font-size: 14px;
            font-weight: bold;
            background: white;
            cursor: pointer;
            outline: none;
        }
        @media (max-width: 768px) {
            .myth-grid { grid-template-columns: 1fr; }
            .filter-bar { flex-direction: column; align-items: flex-start; }
            .search-box { margin-left: 0; width: 100%; }
            .search-box input { width: 100%; }
        }
    </style>
</head>
<body>

    <header class="navbar">
        <div class="logo">Samburu Health</div>
        <nav>
            <a href="../dashboard/index.php">Home</a>
            <a href="../health/index.php">Health Education</a>
            <a href="index.php">Myth vs Fact</a>
            <a href="../symptom/index.php">Symptom Guidance</a>
            <form method="POST" action="../dashboard/language.php" class="lang-switcher">
                <select name="preferred_language" onchange="this.form.submit()" class="lang-dropdown">
                    <option value="english"   <?= $lang === 'english'   ? 'selected' : '' ?>>🌍 English</option>
                    <option value="kiswahili" <?= $lang === 'kiswahili' ? 'selected' : '' ?>>🇰🇪 Kiswahili</option>
                    <option value="maa"       <?= $lang === 'maa'       ? 'selected' : '' ?>>🐄 Maa</option>
                </select>
                <input type="hidden" name="redirect" value="../myth/index.php">
            </form>
            <a href="../auth/logout.php" class="nav-login">Logout</a>
        </nav>
    </header>

    <div class="page-banner">
        <h1>⚖️ Myth vs Fact</h1>
        <p>Correcting common health misconceptions with evidence-based information from WHO and Kenya MoH.</p>
    </div>

    <section class="myth-section">

        <!-- Filter Bar -->
        <form method="GET" action="">
            <div class="filter-bar">
                <a href="?category=all" class="filter-btn <?= $category === 'all' ? 'active' : '' ?>">All</a>
                <?php foreach ($categories as $cat): ?>
                    <a href="?category=<?= $cat['category'] ?>"
                       class="filter-btn <?= $category === $cat['category'] ? 'active' : '' ?>">
                        <?= ucfirst($cat['category']) ?>
                    </a>
                <?php endforeach; ?>
                <div class="search-box">
                    <input type="text" name="search"
                        placeholder="Search myths..."
                        value="<?= htmlspecialchars($search) ?>">
                    <input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>">
                    <button type="submit">Search</button>
                </div>
            </div>
        </form>

        <!-- Counter -->
        <?php if (!empty($entries)): ?>
        <div class="counter-bar">
            ⚖️ <?= count($entries) ?> myth<?= count($entries) > 1 ? 's' : '' ?> found
            <?= $category !== 'all' ? ' in ' . ucfirst($category) : '' ?>
            <?= !empty($search) ? ' matching "' . htmlspecialchars($search) . '"' : '' ?>
        </div>
        <?php endif; ?>

        <!-- Myth Cards -->
        <?php if (empty($entries)): ?>
            <div class="no-results">
                <h3>No entries found</h3>
                <p>Try a different search term or category.</p>
            </div>
        <?php else: ?>
            <div class="myth-grid">
                <?php foreach ($entries as $entry):
                    $c = get_myth_content($entry, $lang);
                ?>
                    <div class="myth-card">

                        <div class="myth-part">
                            <span class="myth-label label-myth">❌ Myth</span>
                            <p><?= htmlspecialchars($c['myth']) ?></p>
                        </div>

                        <div class="myth-divider"></div>

                        <div class="myth-part">
                            <span class="myth-label label-fact">✅ Fact</span>
                            <p><?= htmlspecialchars($c['fact']) ?></p>
                        </div>

                        <div class="myth-footer">
                            <span class="myth-category-tag">
                                📂 <?= ucfirst($entry['category']) ?>
                            </span>
                            <span style="font-size:12px; color:#b0bec5;">
                                Source: WHO / Kenya MoH
                            </span>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </section>

    <footer>
        <p>© 2026 Samburu Health Platform</p>
        <p>Health education and basic telemedicine guidance</p>
    </footer>

    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/multilingual-health-platform/service-worker.js');
            });
        }
    </script>

</body>
</html>