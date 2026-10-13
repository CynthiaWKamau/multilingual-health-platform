<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'chw') {
    header('Location: ../auth/login.php');
    exit();
}

$user_id   = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];
$lang      = $_SESSION['language'] ?? 'english';
$success   = '';
$error     = '';

// Handle suggestion submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_suggestion'])) {
    $title    = trim($_POST['title']);
    $content  = trim($_POST['content']);
    $category = $_POST['category'];

    if (empty($title) || empty($content)) {
        $error = 'Please fill in the title and content.';
    } else {
        $stmt = $conn->prepare("INSERT INTO content_suggestions (user_id, title, content, category) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user_id, $title, $content, $category);
        $stmt->execute();
        $success = 'Your suggestion has been submitted for Admin review.';
    }
}

// Get CHW's own suggestions
$stmt = $conn->prepare("SELECT * FROM content_suggestions WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$my_suggestions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CHW Dashboard — Samburu Health</title>
    <link rel="stylesheet" href="/multilingual-health-platform/assets/css/style.css">
    <style>
        .chw-banner {
            background: #1b5e20;
            padding: 50px 7%;
            color: white;
        }
        .chw-banner h1 { font-size: 32px; color: white; margin-bottom: 8px; }
        .chw-banner p  { color: #c8e6c9; font-size: 15px; }
        .chw-section   { padding: 50px 7%; }
        .chw-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 40px;
        }
        .chw-card {
            display: block;
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 28px 20px;
            text-decoration: none;
            color: #263238;
            text-align: center;
            transition: all 0.2s;
        }
        .chw-card:hover {
            border-color: #2e7d32;
            box-shadow: 0 4px 16px rgba(46,125,50,0.1);
            transform: translateY(-2px);
        }
        .chw-card-icon { font-size: 32px; margin-bottom: 12px; }
        .chw-card h3   { font-size: 15px; color: #1b4332; margin-bottom: 6px; }
        .chw-card p    { font-size: 13px; color: #607d8b; line-height: 1.5; }
        .section-title {
            font-size: 18px;
            color: #1b5e20;
            font-weight: bold;
            margin-bottom: 16px;
            padding-bottom: 8px;
            border-bottom: 2px solid #e8f5e9;
        }
        .suggest-form {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 28px;
            margin-bottom: 36px;
        }
        .suggest-form h3 { font-size: 17px; color: #1b4332; margin-bottom: 6px; }
        .suggest-form p  { font-size: 14px; color: #78909c; margin-bottom: 20px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .suggestions-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid #e0e0e0;
        }
        .suggestions-table th {
            background: #e8f5e9;
            color: #1b5e20;
            padding: 12px 16px;
            text-align: left;
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .suggestions-table td {
            padding: 12px 16px;
            font-size: 14px;
            color: #37474f;
            border-bottom: 1px solid #f0f0f0;
        }
        .suggestions-table tr:last-child td { border-bottom: none; }
        .status-badge { padding: 4px 12px; border-radius: 10px; font-size: 12px; font-weight: bold; }
        .status-pending  { background: #fff3e0; color: #e65100; }
        .status-approved { background: #e8f5e9; color: #2e7d32; }
        .status-rejected { background: #ffebee; color: #c62828; }
        .navbar-chw { background: #1b5e20 !important; }
        .navbar-chw .logo { color: #c8e6c9 !important; }
        .navbar-chw nav a { color: rgba(255,255,255,0.7) !important; }
        .navbar-chw nav a:hover { color: white !important; }
        .navbar-chw .nav-login { border-color: rgba(255,255,255,0.5) !important; color: white !important; }
        .lang-switcher-links { display: flex; align-items: center; gap: 6px; }
        .lang-link {
            padding: 6px 10px;
            border: 1.5px solid rgba(255,255,255,0.3);
            border-radius: 6px;
            color: rgba(255,255,255,0.7);
            font-size: 12px;
            font-weight: bold;
            text-decoration: none;
            transition: all 0.2s;
        }
        .lang-link:hover { background: rgba(255,255,255,0.1); color: white; }
        .lang-active { background: rgba(255,255,255,0.2); color: white !important; }
        @media (max-width: 768px) {
            .chw-grid  { grid-template-columns: repeat(2, 1fr); }
            .form-row  { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <header class="navbar navbar-chw">
        <div class="logo">Samburu Health — CHW</div>
        <nav>
            <a href="index.php">Dashboard</a>
            <a href="../health/index.php">Health Education</a>
            <a href="../myth/index.php">Myth vs Fact</a>
            <a href="../symptom/index.php">Symptom Guidance</a>
            <div class="lang-switcher-links">
                <a href="../dashboard/language.php?lang=english&redirect=../chw/index.php"
                   class="lang-link <?= $lang === 'english'   ? 'lang-active' : '' ?>">🌍 EN</a>
                <a href="../dashboard/language.php?lang=kiswahili&redirect=../chw/index.php"
                   class="lang-link <?= $lang === 'kiswahili' ? 'lang-active' : '' ?>">🇰🇪 SW</a>
                <a href="../dashboard/language.php?lang=maa&redirect=../chw/index.php"
                   class="lang-link <?= $lang === 'maa'       ? 'lang-active' : '' ?>">🐄 MAA</a>
            </div>
            <a href="../auth/logout.php" class="nav-login">Logout</a>
        </nav>
    </header>

    <div class="chw-banner">
        <h1>🏥 Community Health Worker Dashboard</h1>
        <p>Welcome back, <?= htmlspecialchars($full_name) ?>. Access health resources and submit content suggestions.</p>
    </div>

    <div class="chw-section">

        <!-- Quick Access -->
        <p class="section-title">Health Resources</p>
        <div class="chw-grid">
            <a href="../health/index.php" class="chw-card">
                <div class="chw-card-icon">📚</div>
                <h3>Health Education</h3>
                <p>Access and share health education articles with community members.</p>
            </a>
            <a href="../myth/index.php" class="chw-card">
                <div class="chw-card-icon">⚖️</div>
                <h3>Myth vs Fact</h3>
                <p>Reference myth corrections during community health visits.</p>
            </a>
            <a href="../symptom/index.php" class="chw-card">
                <div class="chw-card-icon">🩺</div>
                <h3>Symptom Guidance</h3>
                <p>Use symptom guidance to support community members.</p>
            </a>
            <a href="#suggest" class="chw-card">
                <div class="chw-card-icon">💬</div>
                <h3>Submit Suggestion</h3>
                <p>Suggest new health content based on community needs.</p>
            </a>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Submit Suggestion -->
        <div class="suggest-form" id="suggest">
            <h3>💬 Submit a Content Suggestion</h3>
            <p>Based on your community visits, suggest health topics or corrections that would help your community.</p>

            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label>Suggestion Title <span class="required">*</span></label>
                        <input type="text" name="title" placeholder="e.g. Add content about snakebite first aid" required>
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category">
                            <option value="">Select category</option>
                            <option value="maternal">Maternal Health</option>
                            <option value="child">Child Health</option>
                            <option value="disease">Common Diseases</option>
                            <option value="myth">Myth vs Fact</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Details <span class="required">*</span></label>
                    <textarea name="content" rows="5"
                        style="width:100%;padding:12px 14px;border:1.5px solid #cfd8dc;border-radius:6px;font-size:15px;resize:vertical;"
                        placeholder="Describe what content you would like added or changed, and why it would help your community..."
                        required></textarea>
                </div>
                <button type="submit" name="submit_suggestion" class="btn primary-btn">
                    Submit Suggestion
                </button>
            </form>
        </div>

        <!-- My Suggestions -->
        <p class="section-title">My Submitted Suggestions</p>
        <?php if (empty($my_suggestions)): ?>
            <p style="color:#78909c;font-size:14px;">You have not submitted any suggestions yet.</p>
        <?php else: ?>
            <table class="suggestions-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Submitted</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($my_suggestions as $s): ?>
                    <tr>
                        <td><?= htmlspecialchars($s['title']) ?></td>
                        <td><?= $s['category'] ? ucfirst($s['category']) : '—' ?></td>
                        <td>
                            <span class="status-badge status-<?= $s['status'] ?>">
                                <?= ucfirst($s['status']) ?>
                            </span>
                        </td>
                        <td><?= date('d M Y', strtotime($s['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

    </div>

    <footer>
        <p>© 2026 Samburu Health Platform</p>
        <p>Community Health Worker Portal</p>
    </footer>

</body>
</html>