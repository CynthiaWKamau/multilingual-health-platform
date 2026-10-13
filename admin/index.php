<?php
session_start();
include '../config/database.php';

// Only admin can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

$full_name = $_SESSION['full_name'];

// Get stats
$total_users    = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
$total_members  = $conn->query("SELECT COUNT(*) as c FROM users WHERE role = 'community_member'")->fetch_assoc()['c'];
$total_chw      = $conn->query("SELECT COUNT(*) as c FROM users WHERE role = 'chw'")->fetch_assoc()['c'];
$total_articles = $conn->query("SELECT COUNT(*) as c FROM health_education")->fetch_assoc()['c'];
$total_myths    = $conn->query("SELECT COUNT(*) as c FROM myth_facts")->fetch_assoc()['c'];
$total_symptoms = $conn->query("SELECT COUNT(*) as c FROM symptom_guidance")->fetch_assoc()['c'];

// Get pending suggestions count
$conn->query("
    CREATE TABLE IF NOT EXISTS content_suggestions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(200) NOT NULL,
        content TEXT NOT NULL,
        category VARCHAR(80),
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )
");
$pending_suggestions = $conn->query("SELECT COUNT(*) as c FROM content_suggestions WHERE status = 'pending'")->fetch_assoc()['c'];

// Get recent users
$recent_users = $conn->query("SELECT full_name, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — Samburu Health</title>
    <link rel="stylesheet" href="/multilingual-health-platform/assets/css/style.css">
    <style>
        .admin-banner {
            background: #1a237e;
            padding: 40px 7%;
            color: white;
        }
        .admin-banner h1 { font-size: 32px; color: white; margin-bottom: 6px; }
        .admin-banner p  { color: #c5cae9; font-size: 15px; }
        .admin-section   { padding: 40px 7%; }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 40px;
        }
        .stat-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 24px 20px;
            text-align: center;
        }
        .stat-number {
            font-size: 36px;
            font-weight: bold;
            color: #1a237e;
            margin-bottom: 4px;
        }
        .stat-label {
            font-size: 13px;
            color: #78909c;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .stat-icon { font-size: 28px; margin-bottom: 10px; }
        .admin-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 40px;
        }
        .admin-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 28px 24px;
            text-decoration: none;
            color: #263238;
            transition: all 0.2s;
            display: block;
        }
        .admin-card:hover {
            border-color: #1a237e;
            box-shadow: 0 4px 16px rgba(26,35,126,0.1);
            transform: translateY(-2px);
        }
        .admin-card-icon { font-size: 32px; margin-bottom: 14px; }
        .admin-card h3   { font-size: 16px; color: #1a237e; margin-bottom: 8px; }
        .admin-card p    { font-size: 13px; color: #607d8b; line-height: 1.5; }
        .badge {
            display: inline-block;
            background: #c62828;
            color: white;
            font-size: 11px;
            font-weight: bold;
            padding: 2px 8px;
            border-radius: 10px;
            margin-left: 6px;
        }
        .section-title {
            font-size: 18px;
            color: #1a237e;
            font-weight: bold;
            margin-bottom: 16px;
            padding-bottom: 8px;
            border-bottom: 2px solid #e8eaf6;
        }
        .recent-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid #e0e0e0;
        }
        .recent-table th {
            background: #e8eaf6;
            color: #1a237e;
            padding: 12px 16px;
            text-align: left;
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .recent-table td {
            padding: 12px 16px;
            font-size: 14px;
            color: #37474f;
            border-bottom: 1px solid #f0f0f0;
        }
        .recent-table tr:last-child td { border-bottom: none; }
        .role-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: bold;
            text-transform: capitalize;
        }
        .role-admin   { background: #e8eaf6; color: #1a237e; }
        .role-chw     { background: #e8f5e9; color: #2e7d32; }
        .role-member  { background: #fff3e0; color: #e65100; }
        .navbar-admin { background: #1a237e !important; }
        .navbar-admin .logo { color: #c5cae9 !important; }
        .navbar-admin nav a { color: rgba(255,255,255,0.7) !important; }
        .navbar-admin nav a:hover { color: white !important; }
        .navbar-admin .nav-login {
            border-color: rgba(255,255,255,0.5) !important;
            color: white !important;
        }
        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .admin-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <header class="navbar navbar-admin">
        <div class="logo">Samburu Health — Admin</div>
        <nav>
            <a href="index.php">Dashboard</a>
            <a href="users.php">Users</a>
            <a href="content.php">Health Content</a>
            <a href="myths.php">Myth vs Fact</a>
            <a href="symptoms.php">Symptoms</a>
            <a href="suggestions.php">
                Suggestions
                <?php if ($pending_suggestions > 0): ?>
                    <span class="badge"><?= $pending_suggestions ?></span>
                <?php endif; ?>
            </a>
            <a href="../auth/logout.php" class="nav-login">Logout</a>
        </nav>
    </header>

    <div class="admin-banner">
        <h1>⚙️ Admin Dashboard</h1>
        <p>Welcome back, <?= htmlspecialchars($full_name) ?>. Manage your platform from here.</p>
    </div>

    <div class="admin-section">

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-number"><?= $total_users ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">👤</div>
                <div class="stat-number"><?= $total_members ?></div>
                <div class="stat-label">Community Members</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🏥</div>
                <div class="stat-number"><?= $total_chw ?></div>
                <div class="stat-label">Health Workers</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📚</div>
                <div class="stat-number"><?= $total_articles ?></div>
                <div class="stat-label">Health Articles</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <p class="section-title">Quick Actions</p>
        <div class="admin-grid">

            <a href="users.php" class="admin-card">
                <div class="admin-card-icon">👥</div>
                <h3>Manage Users</h3>
                <p>View all users, change roles, and manage accounts.</p>
            </a>

            <a href="content.php" class="admin-card">
                <div class="admin-card-icon">📚</div>
                <h3>Health Content</h3>
                <p>Add, edit, or delete health education articles.</p>
            </a>

            <a href="myths.php" class="admin-card">
                <div class="admin-card-icon">⚖️</div>
                <h3>Myth vs Fact</h3>
                <p>Manage myth and fact entries for community health misconceptions.</p>
            </a>

            <a href="symptoms.php" class="admin-card">
                <div class="admin-card-icon">🩺</div>
                <h3>Symptom Guidance</h3>
                <p>Add and update symptom guidance entries.</p>
            </a>

            <a href="suggestions.php" class="admin-card">
                <div class="admin-card-icon">💬</div>
                <h3>
                    Content Suggestions
                    <?php if ($pending_suggestions > 0): ?>
                        <span class="badge"><?= $pending_suggestions ?> pending</span>
                    <?php endif; ?>
                </h3>
                <p>Review content suggestions submitted by Community Health Workers.</p>
            </a>

            <a href="../health/index.php" class="admin-card">
                <div class="admin-card-icon">🌐</div>
                <h3>View Platform</h3>
                <p>See the platform as a community member would see it.</p>
            </a>

        </div>

        <!-- Recent Users -->
        <p class="section-title">Recently Registered Users</p>
        <table class="recent-table">
            <thead>
                <tr>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Registered</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_users as $user): ?>
                <tr>
                    <td><?= htmlspecialchars($user['full_name']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td>
                        <span class="role-badge role-<?= $user['role'] === 'community_member' ? 'member' : $user['role'] ?>">
                            <?= ucfirst(str_replace('_', ' ', $user['role'])) ?>
                        </span>
                    </td>
                    <td><?= date('d M Y', strtotime($user['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    </div>

    <footer>
        <p>© 2026 Samburu Health Platform</p>
        <p>Admin Panel</p>
    </footer>

</body>
</html>