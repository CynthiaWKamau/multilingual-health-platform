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

if ($category !== 'all' && !empty($search)) {
    $stmt = $conn->prepare("SELECT * FROM health_education WHERE category = ? AND title LIKE ? ORDER BY title");
    $like = '%' . $search . '%';
    $stmt->bind_param("ss", $category, $like);
} elseif ($category !== 'all') {
    $stmt = $conn->prepare("SELECT * FROM health_education WHERE category = ? ORDER BY title");
    $stmt->bind_param("s", $category);
} elseif (!empty($search)) {
    $stmt = $conn->prepare("SELECT * FROM health_education WHERE title LIKE ? OR content LIKE ? ORDER BY title");
    $like = '%' . $search . '%';
    $stmt->bind_param("ss", $like, $like);
} else {
    $stmt = $conn->prepare("SELECT * FROM health_education ORDER BY category, title");
}

$stmt->execute();
$result   = $stmt->get_result();
$articles = $result->fetch_all(MYSQLI_ASSOC);

function get_content($article, $lang) {
    if ($lang === 'kiswahili' && !empty($article['content_sw'])) {
        return ['title' => $article['title'], 'content' => $article['content_sw']];
    } elseif ($lang === 'maa' && !empty($article['content_maa'])) {
        return ['title' => $article['title'], 'content' => $article['content_maa']];
    }
    return ['title' => $article['title'], 'content' => $article['content']];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Education — Samburu Health</title>
    <link rel="stylesheet" href="/multilingual-health-platform/assets/css/style.css">
    <style>
        .page-banner {
            background: #1b4332;
            padding: 50px 7%;
            color: white;
        }
        .page-banner h1 { font-size: 36px; color: white; margin-bottom: 8px; }
        .page-banner p  { color: #c8e6c9; font-size: 16px; }
        .health-section { padding: 50px 7%; }
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
        .filter-btn:hover, .filter-btn.active {
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
        .search-box input:focus { outline: none; border-color: #2e7d32; }
        .search-box button {
            padding: 9px 16px;
            background: #2e7d32;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }
        .articles-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
        }
        .article-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.2s;
            cursor: pointer;
        }
        .article-card:hover {
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
            transform: translateY(-2px);
        }
        .article-header { padding: 20px 22px 14px; border-bottom: 1px solid #f0f0f0; }
        .article-category {
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 1px;
            margin-bottom: 8px;
            text-transform: uppercase;
        }
        .cat-maternal { color: #c2185b; }
        .cat-child    { color: #1565c0; }
        .cat-disease  { color: #e65100; }
        .article-card h3 { font-size: 16px; color: #1b4332; line-height: 1.4; }
        .article-body { padding: 16px 22px 20px; }
        .article-body p { font-size: 14px; color: #607d8b; line-height: 1.6; }
        .read-more {
            display: inline-block;
            margin-top: 14px;
            color: #2e7d32;
            font-size: 13px;
            font-weight: bold;
        }
        .no-results { text-align: center; padding: 60px; color: #78909c; }
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .modal-overlay.active { display: flex; }
        .modal {
            background: white;
            border-radius: 12px;
            max-width: 680px;
            width: 100%;
            max-height: 80vh;
            overflow-y: auto;
        }
        .modal-header {
            padding: 24px 28px 18px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            position: sticky;
            top: 0;
            background: white;
        }
        .modal-header h2 { font-size: 22px; color: #1b4332; line-height: 1.3; }
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #78909c;
            flex-shrink: 0;
        }
        .modal-body {
            padding: 24px 28px;
            font-size: 15px;
            color: #37474f;
            line-height: 1.8;
        }
        .lang-switcher-links { display: flex; align-items: center; gap: 6px; }
        .lang-link {
            padding: 6px 10px;
            border: 1.5px solid #ddd;
            border-radius: 6px;
            color: #607d8b;
            font-size: 12px;
            font-weight: bold;
            text-decoration: none;
            transition: all 0.2s;
        }
        .lang-link:hover { border-color: #2e7d32; color: #2e7d32; }
        .lang-active { background: #2e7d32; color: white !important; border-color: #2e7d32; }
        @media (max-width: 768px) {
            .articles-grid { grid-template-columns: 1fr; }
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
            <a href="index.php">Health Education</a>
            <a href="../myth/index.php">Myth vs Fact</a>
            <a href="../symptom/index.php">Symptom Guidance</a>
            <div class="lang-switcher-links">
                <a href="../dashboard/language.php?lang=english&redirect=../health/index.php"
                   class="lang-link <?= $lang === 'english'   ? 'lang-active' : '' ?>">🌍 EN</a>
                <a href="../dashboard/language.php?lang=kiswahili&redirect=../health/index.php"
                   class="lang-link <?= $lang === 'kiswahili' ? 'lang-active' : '' ?>">🇰🇪 SW</a>
                <a href="../dashboard/language.php?lang=maa&redirect=../health/index.php"
                   class="lang-link <?= $lang === 'maa'       ? 'lang-active' : '' ?>">🐄 MAA</a>
            </div>
            <a href="../auth/logout.php" class="nav-login">Logout</a>
        </nav>
    </header>

    <div class="page-banner">
        <h1>📚 Health Education</h1>
        <p>Reliable health information based on WHO and Kenya Ministry of Health guidelines.</p>
    </div>

    <section class="health-section">
        <form method="GET" action="">
            <div class="filter-bar">
                <a href="?category=all"      class="filter-btn <?= $category === 'all'      ? 'active' : '' ?>">All</a>
                <a href="?category=maternal" class="filter-btn <?= $category === 'maternal' ? 'active' : '' ?>">🤰 Maternal Health</a>
                <a href="?category=child"    class="filter-btn <?= $category === 'child'    ? 'active' : '' ?>">👶 Child Health</a>
                <a href="?category=disease"  class="filter-btn <?= $category === 'disease'  ? 'active' : '' ?>">🏥 Common Diseases</a>
                <div class="search-box">
                    <input type="text" name="search" placeholder="Search articles..." value="<?= htmlspecialchars($search) ?>">
                    <input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>">
                    <button type="submit">Search</button>
                </div>
            </div>
        </form>

        <?php if (empty($articles)): ?>
            <div class="no-results">
                <h3>No articles found</h3>
                <p>Try a different search term or category.</p>
            </div>
        <?php else: ?>
            <div class="articles-grid">
                <?php foreach ($articles as $article):
                    $c       = get_content($article, $lang);
                    $preview = substr(strip_tags($c['content']), 0, 120) . '...';
                ?>
                    <div class="article-card"
                         onclick="openModal(`<?= htmlspecialchars(addslashes($c['title'])) ?>`, `<?= htmlspecialchars(addslashes($c['content'])) ?>`)">
                        <div class="article-header">
                            <p class="article-category cat-<?= $article['category'] ?>"><?= ucfirst($article['category']) ?></p>
                            <h3><?= htmlspecialchars($c['title']) ?></h3>
                        </div>
                        <div class="article-body">
                            <p><?= htmlspecialchars($preview) ?></p>
                            <span class="read-more">Read more →</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <div class="modal-overlay" id="modalOverlay" onclick="closeModal(event)">
        <div class="modal">
            <div class="modal-header">
                <h2 id="modalTitle"></h2>
                <button class="modal-close" onclick="closeModalBtn()">✕</button>
            </div>
            <div class="modal-body" id="modalBody"></div>
        </div>
    </div>

    <footer>
        <p>© 2026 Samburu Health Platform</p>
        <p>Health education and basic telemedicine guidance</p>
    </footer>

    <script>
        function openModal(title, content) {
            document.getElementById('modalTitle').textContent = title;
            document.getElementById('modalBody').textContent  = content;
            document.getElementById('modalOverlay').classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        function closeModalBtn() {
            document.getElementById('modalOverlay').classList.remove('active');
            document.body.style.overflow = '';
        }
        function closeModal(e) {
            if (e.target === document.getElementById('modalOverlay')) closeModalBtn();
        }
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModalBtn(); });
    </script>

</body>
</html>