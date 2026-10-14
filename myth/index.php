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

// ML Myth Detection
$ml_result   = null;
$ml_matches  = [];
$myth_search = $_POST['myth_input'] ?? '';
$myth_done   = false;

function call_ml_api($endpoint, $text) {
    $url  = 'http://127.0.0.1:5000/' . $endpoint;
    $data = json_encode(['text' => $text]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    curl_close($ch);

    return $response ? json_decode($response, true) : null;
}

// Handle myth detection form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($myth_search)) {
    $myth_done = true;
    $ml_result = call_ml_api('detect-myth', $myth_search);

    // Search database for related entries
    $words = explode(' ', $myth_search);
    $keyword = $words[array_key_last($words)];
    $stmt = $conn->prepare("SELECT * FROM myth_facts WHERE myth LIKE ? OR fact LIKE ? LIMIT 3");
    $like = '%' . $keyword . '%';
    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();
    $ml_matches = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Get all myth entries for browsing
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
$entries = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

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
    return ['myth' => $entry['myth'], 'fact' => $entry['fact']];
}

$cat_stmt = $conn->prepare("SELECT DISTINCT category FROM myth_facts ORDER BY category");
$cat_stmt->execute();
$categories = $cat_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Myth vs Fact — Samburu Health</title>
    <link rel="stylesheet" href="/multilingual-health-platform/assets/css/style.css">
    <style>
        .page-banner { background:#1b4332; padding:50px 7%; color:white; }
        .page-banner h1 { font-size:36px; color:white; margin-bottom:8px; }
        .page-banner p  { color:#c8e6c9; font-size:16px; }
        .myth-section  { padding:50px 7%; }

        /* ML Detector Card */
        .ml-detect-card { background:white; border:1px solid #e0e0e0; border-radius:12px; padding:28px; margin-bottom:36px; }
        .ml-detect-card h2 { font-size:18px; color:#1b4332; margin-bottom:6px; }
        .ml-detect-card > p { font-size:14px; color:#78909c; margin-bottom:18px; }
        .detect-input-row { display:flex; gap:12px; }
        .detect-input-row input { flex:1; padding:12px 16px; border:1.5px solid #ddd; border-radius:8px; font-size:15px; }
        .detect-input-row input:focus { outline:none; border-color:#2e7d32; }
        .detect-input-row button { padding:12px 22px; background:#1b4332; color:white; border:none; border-radius:8px; font-size:14px; font-weight:bold; cursor:pointer; white-space:nowrap; }

        /* ML Result */
        .ml-result-box { margin-top:20px; border-radius:10px; overflow:hidden; border:2px solid; }
        .ml-result-box.is-myth { border-color:#c62828; }
        .ml-result-box.is-fact { border-color:#2e7d32; }
        .ml-result-header { padding:14px 20px; display:flex; align-items:center; justify-content:space-between; }
        .ml-result-header.is-myth { background:#ffebee; }
        .ml-result-header.is-fact { background:#e8f5e9; }
        .ml-verdict { font-size:18px; font-weight:bold; }
        .ml-verdict.is-myth { color:#c62828; }
        .ml-verdict.is-fact { color:#2e7d32; }
        .ml-conf { font-size:12px; padding:4px 10px; border-radius:10px; font-weight:bold; }
        .ml-conf.is-myth { background:#ffcdd2; color:#c62828; }
        .ml-conf.is-fact { background:#c8e6c9; color:#2e7d32; }
        .ml-result-body { padding:16px 20px; background:white; }
        .ml-result-body p { font-size:14px; color:#37474f; margin-bottom:12px; }
        .ml-matches-label { font-size:12px; font-weight:bold; color:#78909c; text-transform:uppercase; margin-bottom:10px; }
        .ml-match-card { background:#f8f9fa; border-radius:8px; padding:14px; margin-bottom:10px; }
        .ml-match-myth { font-size:13px; color:#c62828; margin-bottom:6px; }
        .ml-match-fact { font-size:13px; color:#2e7d32; }

        /* Filter + Search */
        .filter-bar { display:flex; gap:12px; flex-wrap:wrap; align-items:center; margin-bottom:35px; }
        .filter-btn { padding:9px 20px; border-radius:20px; border:1.5px solid #ddd; background:white; color:#607d8b; font-size:14px; text-decoration:none; transition:all 0.2s; }
        .filter-btn:hover, .filter-btn.active { background:#2e7d32; color:white; border-color:#2e7d32; }
        .search-box { margin-left:auto; display:flex; gap:8px; }
        .search-box input { padding:9px 14px; border:1.5px solid #ddd; border-radius:6px; font-size:14px; width:220px; }
        .search-box input:focus { outline:none; border-color:#2e7d32; }
        .search-box button { padding:9px 16px; background:#2e7d32; color:white; border:none; border-radius:6px; cursor:pointer; font-size:14px; }

        /* Myth Cards */
        .myth-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:24px; }
        .myth-card { border-radius:12px; overflow:hidden; border:1px solid #e0e0e0; background:white; transition:all 0.2s; }
        .myth-card:hover { box-shadow:0 4px 16px rgba(0,0,0,0.08); transform:translateY(-2px); }
        .myth-part { padding:20px 24px; }
        .myth-label { display:inline-block; font-size:11px; font-weight:bold; letter-spacing:1px; padding:4px 10px; border-radius:4px; margin-bottom:10px; text-transform:uppercase; }
        .label-myth { background:#ffebee; color:#c62828; }
        .label-fact { background:#e8f5e9; color:#2e7d32; }
        .myth-part p { font-size:15px; line-height:1.6; color:#37474f; }
        .myth-divider { height:1px; background:#f0f0f0; margin:0 24px; }
        .myth-footer { padding:12px 24px; background:#fafafa; display:flex; justify-content:space-between; align-items:center; }
        .myth-category-tag { font-size:12px; color:#78909c; text-transform:capitalize; font-weight:bold; }
        .no-results { text-align:center; padding:60px; color:#78909c; }
        .counter-bar { background:#e8f5e9; border-radius:8px; padding:14px 20px; margin-bottom:28px; font-size:14px; color:#2e7d32; font-weight:bold; }
        .lang-switcher-links { display:flex; align-items:center; gap:6px; }
        .lang-link { padding:6px 10px; border:1.5px solid #ddd; border-radius:6px; color:#607d8b; font-size:12px; font-weight:bold; text-decoration:none; transition:all 0.2s; }
        .lang-link:hover { border-color:#2e7d32; color:#2e7d32; }
        .lang-active { background:#2e7d32; color:white !important; border-color:#2e7d32; }
        .section-divider { border:none; border-top:2px solid #e8f5e9; margin:40px 0; }
        @media (max-width:768px) {
            .myth-grid { grid-template-columns:1fr; }
            .filter-bar { flex-direction:column; align-items:flex-start; }
            .search-box { margin-left:0; width:100%; }
            .search-box input { width:100%; }
            .detect-input-row { flex-direction:column; }
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
            <div class="lang-switcher-links">
                <a href="../dashboard/language.php?lang=english&redirect=../myth/index.php"
                   class="lang-link <?= $lang === 'english'   ? 'lang-active' : '' ?>">🌍 EN</a>
                <a href="../dashboard/language.php?lang=kiswahili&redirect=../myth/index.php"
                   class="lang-link <?= $lang === 'kiswahili' ? 'lang-active' : '' ?>">🇰🇪 SW</a>
                <a href="../dashboard/language.php?lang=maa&redirect=../myth/index.php"
                   class="lang-link <?= $lang === 'maa'       ? 'lang-active' : '' ?>">🐄 MAA</a>
            </div>
            <a href="../auth/logout.php" class="nav-login">Logout</a>
        </nav>
    </header>

    <div class="page-banner">
        <h1>⚖️ Myth vs Fact</h1>
        <p>Detect health myths using AI and browse evidence-based corrections from WHO and Kenya MoH.</p>
    </div>

    <div class="myth-section">

        <!-- ML Myth Detector -->
        <div class="ml-detect-card">
            <h2>🧠 AI Myth Detector</h2>
            <p>Type something you have heard about health and our ML model will tell you if it is a myth or a fact.</p>

            <form method="POST" action="" id="myth-form">
                <div class="detect-input-row">
                    <input type="text" name="myth_input" id="myth-input"
                        placeholder="e.g. Vaccines cause autism in children..."
                        value="<?= htmlspecialchars($myth_search) ?>"
                        autocomplete="off">
                    <button type="submit">Check Statement</button>
                </div>
            </form>

            <?php if ($myth_done && $ml_result): ?>
            <div class="ml-result-box <?= $ml_result['is_myth'] ? 'is-myth' : 'is-fact' ?>">
                <div class="ml-result-header <?= $ml_result['is_myth'] ? 'is-myth' : 'is-fact' ?>">
                    <span class="ml-verdict <?= $ml_result['is_myth'] ? 'is-myth' : 'is-fact' ?>">
                        <?= $ml_result['is_myth'] ? '❌ This is likely a MYTH' : '✅ This appears to be a FACT' ?>
                    </span>
                    <span class="ml-conf <?= $ml_result['is_myth'] ? 'is-myth' : 'is-fact' ?>">
                        <?= $ml_result['confidence'] ?>% confidence
                    </span>
                </div>
                <div class="ml-result-body">
                    <p><?= htmlspecialchars($ml_result['message']) ?></p>

                    <?php if (!empty($ml_matches)): ?>
                        <p class="ml-matches-label">Related entries from our database:</p>
                        <?php foreach ($ml_matches as $match):
                            $c = get_myth_content($match, $lang);
                        ?>
                        <div class="ml-match-card">
                            <p class="ml-match-myth">❌ <?= htmlspecialchars($c['myth']) ?></p>
                            <p class="ml-match-fact">✅ <?= htmlspecialchars($c['fact']) ?></p>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php elseif ($myth_done && !$ml_result): ?>
                <div class="alert alert-error" style="margin-top:16px;">
                    ML API unavailable. Make sure the Flask server is running.
                </div>
            <?php endif; ?>
        </div>

        <hr class="section-divider">

        <!-- Browse All Myths -->
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
                    <input type="text" name="search" placeholder="Search myths..."
                        value="<?= htmlspecialchars($search) ?>">
                    <input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>">
                    <button type="submit">Search</button>
                </div>
            </div>
        </form>

        <?php if (!empty($entries)): ?>
        <div class="counter-bar">
            ⚖️ <?= count($entries) ?> myth<?= count($entries) > 1 ? 's' : '' ?> found
        </div>
        <?php endif; ?>

        <?php if (empty($entries)): ?>
            <div class="no-results"><h3>No entries found</h3></div>
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
                            <span class="myth-category-tag">📂 <?= ucfirst($entry['category']) ?></span>
                            <span style="font-size:12px;color:#b0bec5;">Source: WHO / Kenya MoH</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>

    <footer>
        <p>© 2026 Samburu Health Platform</p>
        <p>Health education and basic telemedicine guidance</p>
    </footer>

    <script>
        function setInput(val) {
            document.getElementById('symptom-input').value = val;
            document.getElementById('symptom-form').submit();
        }
    </script>

</body>
</html>