<?php
session_start();
include '../config/database.php';
include '../config/translate.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$lang     = $_SESSION['language'] ?? 'english';
$category = $_GET['category'] ?? 'all';
$search   = trim($_GET['search'] ?? '');

if ($category !== 'all' && !empty($search)) {
    $stmt = $conn->prepare("SELECT * FROM health_education WHERE category = ? AND (title LIKE ? OR content LIKE ?) ORDER BY title");
    $like = '%' . $search . '%';
    $stmt->bind_param("sss", $category, $like, $like);
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
        return ['title' => $article['title_sw'] ?? $article['title'], 'content' => $article['content_sw']];
    } elseif ($lang === 'maa' && !empty($article['content_maa'])) {
        return ['title' => $article['title_maa'] ?? $article['title'], 'content' => $article['content_maa']];
    }
    return ['title' => $article['title'], 'content' => $article['content']];
}

// Category meta
$cat_meta = [
    'maternal' => ['label' => 'Maternal Health',   'color' => '#b5446e', 'bg' => '#fdf0f5', 'bg_dark' => '#2a1020'],
    'child'    => ['label' => 'Child Health',       'color' => '#1a5ca8', 'bg' => '#eef3fc', 'bg_dark' => '#0e1e38'],
    'disease'  => ['label' => 'Common Diseases',    'color' => '#c45c10', 'bg' => '#fdf3ed', 'bg_dark' => '#2a1608'],
    'nutrition'=> ['label' => 'Nutrition',          'color' => '#2d7d46', 'bg' => '#edf7f0', 'bg_dark' => '#0e2418'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Health Education &mdash; Samburu Health</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

<style>
/* ── Design tokens ── */
:root {
    --green-deep:  #1a3d2b;
    --green-mid:   #2d6a4f;
    --green-light: #52b788;
    --cream:       #f8f4ec;
    --cream-dark:  #ede8dc;
    --gold:        #c9913d;
    --gold-light:  #e8b96a;
    --ink:         #0e1a13;
    --white:       #ffffff;
    --text-body:   #2c3e2d;
    --text-muted:  #6b7c6d;
    --border:      #d4cfc5;

    --bg:      var(--cream);
    --surface: var(--white);
    --fg:      var(--text-body);
}
@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) {
        --bg: #0d1f14; --surface: #152a1e; --fg: #e8f0e9;
        --text-muted: #8fa892; --border: #2a4035;
        color-scheme: dark;
    }
}
:root[data-theme="dark"] {
    --bg: #0d1f14; --surface: #152a1e; --fg: #e8f0e9;
    --text-muted: #8fa892; --border: #2a4035;
    color-scheme: dark;
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'Poppins', sans-serif;
    background: var(--bg); color: var(--fg);
    min-height: 100vh; display: flex; flex-direction: column;
}

/* ── Navbar ── */
.navbar {
    position: sticky; top: 0; z-index: 200;
    background: var(--green-deep);
    display: flex; align-items: center; justify-content: space-between;
    padding: 0 clamp(1rem, 5vw, 3rem); height: 64px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.25);
}
.logo {
    font-family: 'Lora', serif; font-size: 1.2rem; font-weight: 700;
    color: var(--white); display: flex; align-items: center; gap: 10px;
    text-decoration: none; flex-shrink: 0;
}
.nav-links { display: flex; align-items: center; gap: 1.1rem; }
.nav-links a {
    color: rgba(255,255,255,0.82); text-decoration: none;
    font-size: 0.83rem; font-weight: 500; white-space: nowrap;
    transition: color 0.2s;
}
.nav-links a:hover { color: var(--white); }
.nav-logout {
    background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.22);
    color: var(--white) !important; padding: 5px 14px; border-radius: 6px; font-weight: 600 !important;
}
.nav-logout:hover { background: rgba(255,255,255,0.2) !important; }

/* Language switcher in nav */
.lang-switcher { display: flex; align-items: center; gap: 4px; }
.lang-btn {
    padding: 4px 10px; border-radius: 5px;
    border: 1px solid rgba(255,255,255,0.25);
    color: rgba(255,255,255,0.78); font-size: 0.75rem; font-weight: 600;
    text-decoration: none; transition: all 0.2s; white-space: nowrap;
}
.lang-btn:hover { border-color: rgba(255,255,255,0.6); color: var(--white); }
.lang-btn.active { background: var(--green-light); border-color: var(--green-light); color: var(--green-deep); }

/* ── Page hero ── */
.page-hero {
    background: linear-gradient(135deg, var(--green-deep) 0%, var(--green-mid) 60%, #40916c 100%);
    padding: 3rem clamp(1.5rem, 7%, 5rem);
    position: relative; overflow: hidden;
}
.page-hero::before {
    content: ''; position: absolute;
    width: 420px; height: 420px; border-radius: 50%;
    background: radial-gradient(circle, rgba(82,183,136,0.14), transparent 65%);
    top: -100px; right: -60px; pointer-events: none;
}
.hero-inner { position: relative; z-index: 1; max-width: 660px; }
.hero-eyebrow {
    font-size: 0.72rem; font-weight: 700; letter-spacing: 0.14em;
    text-transform: uppercase; color: var(--green-light); margin-bottom: 0.5rem;
}
.page-hero h1 {
    font-family: 'Lora', serif;
    font-size: clamp(1.6rem, 3.5vw, 2.4rem);
    font-weight: 700; color: var(--white); line-height: 1.25; margin-bottom: 0.65rem;
}
.page-hero p { font-size: 0.95rem; color: rgba(255,255,255,0.78); line-height: 1.7; max-width: 500px; }

/* ── Main content ── */
.content-wrap {
    flex: 1;
    padding: 2.5rem clamp(1.5rem, 7%, 5rem);
}

/* ── Filter bar ── */
.filter-bar {
    display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap;
    margin-bottom: 2rem;
}
.filter-btn {
    padding: 7px 18px; border-radius: 20px;
    border: 1.5px solid var(--border);
    background: var(--surface); color: var(--text-muted);
    font-family: 'Poppins', sans-serif; font-size: 0.82rem; font-weight: 500;
    text-decoration: none; cursor: pointer;
    transition: all 0.2s; white-space: nowrap;
}
.filter-btn:hover { border-color: var(--green-mid); color: var(--green-mid); }
.filter-btn.active {
    background: var(--green-mid); border-color: var(--green-mid);
    color: var(--white);
}
.search-form { margin-left: auto; display: flex; gap: 6px; }
.search-form input {
    padding: 7px 14px; border: 1.5px solid var(--border); border-radius: 8px;
    font-family: 'Poppins', sans-serif; font-size: 0.85rem;
    background: var(--surface); color: var(--fg); width: 210px;
    outline: none; transition: border-color 0.2s;
}
.search-form input:focus { border-color: var(--green-mid); }
.search-form button {
    padding: 7px 16px; background: var(--green-mid); color: var(--white);
    border: none; border-radius: 8px; font-family: 'Poppins', sans-serif;
    font-size: 0.85rem; font-weight: 600; cursor: pointer;
    transition: background 0.2s; display: flex; align-items: center; gap: 5px;
}
.search-form button:hover { background: var(--green-deep); }

/* ── Results info ── */
.results-info {
    font-size: 0.82rem; color: var(--text-muted); margin-bottom: 1.25rem;
}

/* ── Articles grid ── */
.articles-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.25rem;
}
.article-card {
    background: var(--surface);
    border: 1.5px solid var(--border);
    border-radius: 14px;
    overflow: hidden;
    cursor: pointer;
    transition: border-color 0.2s, box-shadow 0.2s, transform 0.2s;
    display: flex; flex-direction: column;
}
.article-card:hover {
    border-color: var(--green-mid);
    box-shadow: 0 6px 24px rgba(45,106,79,0.13);
    transform: translateY(-3px);
}
.article-top {
    padding: 1.25rem 1.25rem 0.85rem;
}
.cat-pill {
    display: inline-block;
    font-size: 0.7rem; font-weight: 700; letter-spacing: 0.08em;
    text-transform: uppercase; padding: 3px 10px; border-radius: 20px;
    margin-bottom: 0.7rem;
}
.article-card h3 {
    font-family: 'Lora', serif; font-size: 1rem; font-weight: 600;
    color: var(--green-deep); line-height: 1.45;
}
@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) .article-card h3 { color: var(--green-light); }
}
:root[data-theme="dark"] .article-card h3 { color: var(--green-light); }

.article-divider { height: 1px; background: var(--border); margin: 0 1.25rem; }
.article-bottom { padding: 0.85rem 1.25rem 1.25rem; flex: 1; display: flex; flex-direction: column; }
.article-bottom p {
    font-size: 0.83rem; color: var(--text-muted); line-height: 1.65; flex: 1;
}
.read-link {
    display: inline-flex; align-items: center; gap: 4px;
    margin-top: 0.85rem; font-size: 0.8rem; font-weight: 600;
    color: var(--green-mid);
}
.read-link svg { transition: transform 0.2s; }
.article-card:hover .read-link svg { transform: translateX(3px); }

/* Category pill colours */
.cat-maternal  { color: #b5446e; background: #fdf0f5; }
.cat-child     { color: #1a5ca8; background: #eef3fc; }
.cat-disease   { color: #c45c10; background: #fdf3ed; }
.cat-nutrition { color: #2d7d46; background: #edf7f0; }
@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) .cat-maternal  { background: #2a1020; }
    :root:not([data-theme="light"]) .cat-child     { background: #0e1e38; }
    :root:not([data-theme="light"]) .cat-disease   { background: #2a1608; }
    :root:not([data-theme="light"]) .cat-nutrition { background: #0e2418; }
}
:root[data-theme="dark"] .cat-maternal  { background: #2a1020; }
:root[data-theme="dark"] .cat-child     { background: #0e1e38; }
:root[data-theme="dark"] .cat-disease   { background: #2a1608; }
:root[data-theme="dark"] .cat-nutrition { background: #0e2418; }

/* ── No results ── */
.no-results {
    text-align: center; padding: 4rem 1rem; color: var(--text-muted);
}
.no-results svg { margin-bottom: 1rem; opacity: 0.4; }
.no-results h3 { font-family: 'Lora', serif; font-size: 1.2rem; margin-bottom: 0.5rem; color: var(--fg); }
.no-results p  { font-size: 0.875rem; }

/* ── Modal ── */
.modal-overlay {
    display: none; position: fixed;
    inset: 0; background: rgba(0,0,0,0.55);
    z-index: 500; align-items: center; justify-content: center;
    padding: 1.25rem;
}
.modal-overlay.active { display: flex; }
.modal {
    background: var(--surface);
    border-radius: 16px;
    max-width: 680px; width: 100%;
    max-height: 85vh; overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0,0,0,0.25);
}
.modal-header {
    padding: 1.5rem 1.75rem 1.1rem;
    border-bottom: 1px solid var(--border);
    display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem;
    position: sticky; top: 0; background: var(--surface); z-index: 1;
}
.modal-cat { margin-bottom: 0.5rem; }
.modal-header h2 {
    font-family: 'Lora', serif; font-size: 1.3rem; font-weight: 700;
    color: var(--green-deep); line-height: 1.35;
}
@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) .modal-header h2 { color: var(--green-light); }
}
:root[data-theme="dark"] .modal-header h2 { color: var(--green-light); }

.modal-close {
    background: var(--bg); border: 1.5px solid var(--border);
    width: 34px; height: 34px; border-radius: 50%;
    cursor: pointer; color: var(--text-muted);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; transition: border-color 0.2s, color 0.2s;
}
.modal-close:hover { border-color: var(--green-mid); color: var(--fg); }
.modal-body {
    padding: 1.5rem 1.75rem 2rem;
    font-size: 0.925rem; color: var(--fg); line-height: 1.85;
    white-space: pre-wrap;
}

/* ── Footer ── */
footer {
    background: var(--ink); color: rgba(255,255,255,0.65);
    text-align: center; padding: 1.25rem 1rem;
    font-size: 0.8rem; line-height: 1.8;
}

/* ── Responsive ── */
@media (max-width: 900px) { .articles-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 600px) {
    .articles-grid { grid-template-columns: 1fr; }
    .filter-bar { flex-direction: column; align-items: stretch; }
    .search-form { margin-left: 0; }
    .search-form input { width: 100%; }
    .nav-links .nav-links-desktop { display: none; }
}
</style>
</head>
<body>
<?php include '../admin/_back_strip.php'; ?>

<header class="navbar">
    <a href="../dashboard/index.php" class="logo">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#52b788" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
        </svg>
        Samburu Health
    </a>
    <nav class="nav-links">
        <a href="../dashboard/index.php">Dashboard</a>
        <a href="index.php">Health Education</a>
        <a href="../myth/index.php">Myth vs Fact</a>
        <a href="../symptom/index.php">Symptoms</a>
        <div class="lang-switcher">
            <a href="../dashboard/language.php?lang=english&redirect=<?= urlencode('../health/index.php') ?>"
               class="lang-btn <?= $lang === 'english'   ? 'active' : '' ?>">EN</a>
            <a href="../dashboard/language.php?lang=kiswahili&redirect=<?= urlencode('../health/index.php') ?>"
               class="lang-btn <?= $lang === 'kiswahili' ? 'active' : '' ?>">SW</a>
            <a href="../dashboard/language.php?lang=maa&redirect=<?= urlencode('../health/index.php') ?>"
               class="lang-btn <?= $lang === 'maa'       ? 'active' : '' ?>">MAA</a>
        </div>
        <a href="../auth/logout.php" class="nav-logout">Logout</a>
    </nav>
</header>

<!-- Hero -->
<div class="page-hero">
    <div class="hero-inner">
        <p class="hero-eyebrow">Health Education</p>
        <h1>Trusted Health Information</h1>
        <p>Evidence-based resources based on WHO and Kenya Ministry of Health guidelines, available in English, Kiswahili, and Maa.</p>
    </div>
</div>

<!-- Content -->
<div class="content-wrap">

    <!-- Filter + search -->
    <form method="GET" action="">
        <div class="filter-bar">
            <a href="?category=all<?= !empty($search) ? '&search='.urlencode($search) : '' ?>"
               class="filter-btn <?= $category === 'all' ? 'active' : '' ?>">All Topics</a>
            <a href="?category=maternal<?= !empty($search) ? '&search='.urlencode($search) : '' ?>"
               class="filter-btn <?= $category === 'maternal' ? 'active' : '' ?>">Maternal Health</a>
            <a href="?category=child<?= !empty($search) ? '&search='.urlencode($search) : '' ?>"
               class="filter-btn <?= $category === 'child' ? 'active' : '' ?>">Child Health</a>
            <a href="?category=disease<?= !empty($search) ? '&search='.urlencode($search) : '' ?>"
               class="filter-btn <?= $category === 'disease' ? 'active' : '' ?>">Common Diseases</a>
            <a href="?category=nutrition<?= !empty($search) ? '&search='.urlencode($search) : '' ?>"
               class="filter-btn <?= $category === 'nutrition' ? 'active' : '' ?>">Nutrition</a>
            <div class="search-form">
                <input type="text" name="search" placeholder="Search articles&hellip;"
                    value="<?= htmlspecialchars($search) ?>">
                <input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>">
                <button type="submit">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    Search
                </button>
            </div>
        </div>
    </form>

    <?php if (!empty($search) || $category !== 'all'): ?>
        <p class="results-info">
            <?= count($articles) ?> article<?= count($articles) !== 1 ? 's' : '' ?> found
            <?= $category !== 'all' ? 'in <strong>' . htmlspecialchars(ucfirst($category)) . '</strong>' : '' ?>
            <?= !empty($search) ? 'for "<strong>' . htmlspecialchars($search) . '</strong>"' : '' ?>
        </p>
    <?php endif; ?>

    <?php if (empty($articles)): ?>
        <div class="no-results">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <h3>No articles found</h3>
            <p>Try a different search term or browse all categories.</p>
        </div>
    <?php else: ?>
        <div class="articles-grid">
           <?php foreach ($articles as $article):
                  $c         = get_content($article, $lang);
                 if ($article['id'] == 1) { error_log("CARD1: lang=$lang title_sw=" . ($article['title_sw']??'NULL') . " title_maa=" . ($article['title_maa']??'NULL')); }
                $preview   = substr(strip_tags($c['content']), 0, 130);
                if (strlen(strip_tags($c['content'])) > 130) $preview .= '…';
                $cat       = $article['category'] ?? 'general';
                $cat_label = $cat_meta[$cat]['label'] ?? ucfirst($cat);
            ?>
                <div class="article-card"
                     data-title="<?= htmlspecialchars($c['title'], ENT_QUOTES) ?>"
                     data-content="<?= htmlspecialchars($c['content'], ENT_QUOTES) ?>"
                     data-cat="<?= htmlspecialchars($cat_label, ENT_QUOTES) ?>"
                     onclick="openModal(this)">
                    <div class="article-top">
                        <span class="cat-pill cat-<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat_label) ?></span>
                        <h3><?= htmlspecialchars($c['title']) ?></h3>
                    </div>
                    <div class="article-divider"></div>
                    <div class="article-bottom">
                        <p><?= htmlspecialchars($preview) ?></p>
                        <span class="read-link">
                            Read more
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
                            </svg>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<!-- Article modal -->
<div class="modal-overlay" id="modalOverlay" onclick="closeModalOutside(event)">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
        <div class="modal-header">
            <div>
                <div class="modal-cat"><span class="cat-pill" id="modalCat"></span></div>
                <h2 id="modalTitle"></h2>
            </div>
            <button class="modal-close" onclick="closeModal()" aria-label="Close">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <div class="modal-body" id="modalBody"></div>
    </div>
</div>

<footer>
    <p>&copy; 2026 Samburu Health Platform</p>
    <p>Health education and basic telemedicine guidance for rural communities in Kenya</p>
</footer>

<script>
function openModal(el) {
    document.getElementById('modalTitle').textContent = el.dataset.title;
    document.getElementById('modalBody').textContent  = el.dataset.content;
    const catEl = document.getElementById('modalCat');
    catEl.textContent = el.dataset.cat;
    catEl.className   = 'cat-pill';
    document.getElementById('modalOverlay').classList.add('active');
    document.body.style.overflow = 'hidden';
}
function closeModal() {
    document.getElementById('modalOverlay').classList.remove('active');
    document.body.style.overflow = '';
}
function closeModalOutside(e) {
    if (e.target === document.getElementById('modalOverlay')) closeModal();
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
</script>
</body>
</html>