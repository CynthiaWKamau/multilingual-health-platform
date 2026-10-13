<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

$success = '';
$error   = '';

// Handle role change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_role'])) {
    $target_id = (int)$_POST['user_id'];
    $new_role  = $_POST['new_role'];
    $allowed   = ['community_member', 'chw', 'admin'];

    if (in_array($new_role, $allowed)) {
        $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->bind_param("si", $new_role, $target_id);
        $stmt->execute();
        $success = 'User role updated successfully.';
    }
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $target_id = (int)$_POST['user_id'];
    if ($target_id !== $_SESSION['user_id']) {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $target_id);
        $stmt->execute();
        $success = 'User deleted successfully.';
    } else {
        $error = 'You cannot delete your own account.';
    }
}

// Search
$search = $_GET['search'] ?? '';
if (!empty($search)) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE full_name LIKE ? OR email LIKE ? ORDER BY created_at DESC");
    $like = '%' . $search . '%';
    $stmt->bind_param("ss", $like, $like);
} else {
    $stmt = $conn->prepare("SELECT * FROM users ORDER BY created_at DESC");
}
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users — Admin</title>
    <link rel="stylesheet" href="/multilingual-health-platform/assets/css/style.css">
    <style>
        .admin-banner { background: #1a237e; padding: 40px 7%; color: white; }
        .admin-banner h1 { font-size: 28px; color: white; margin-bottom: 6px; }
        .admin-banner p  { color: #c5cae9; }
        .admin-section   { padding: 40px 7%; }
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            gap: 16px;
        }
        .search-form { display: flex; gap: 8px; }
        .search-form input {
            padding: 9px 14px;
            border: 1.5px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            width: 240px;
        }
        .search-form input:focus { outline: none; border-color: #1a237e; }
        .search-form button {
            padding: 9px 16px;
            background: #1a237e;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }
        .users-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid #e0e0e0;
        }
        .users-table th {
            background: #e8eaf6;
            color: #1a237e;
            padding: 12px 16px;
            text-align: left;
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .users-table td {
            padding: 12px 16px;
            font-size: 14px;
            color: #37474f;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: middle;
        }
        .users-table tr:last-child td { border-bottom: none; }
        .role-select {
            padding: 6px 10px;
            border: 1.5px solid #ddd;
            border-radius: 6px;
            font-size: 13px;
            color: #263238;
        }
        .btn-sm {
            padding: 6px 14px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: bold;
            cursor: pointer;
        }
        .btn-save   { background: #1a237e; color: white; }
        .btn-delete { background: #ffebee; color: #c62828; margin-left: 6px; }
        .navbar-admin { background: #1a237e !important; }
        .navbar-admin .logo { color: #c5cae9 !important; }
        .navbar-admin nav a { color: rgba(255,255,255,0.7) !important; }
        .navbar-admin nav a:hover { color: white !important; }
        .navbar-admin .nav-login { border-color: rgba(255,255,255,0.5) !important; color: white !important; }
        .role-badge { display: inline-block; padding: 3px 10px; border-radius: 10px; font-size: 11px; font-weight: bold; }
        .role-admin  { background: #e8eaf6; color: #1a237e; }
        .role-chw    { background: #e8f5e9; color: #2e7d32; }
        .role-member { background: #fff3e0; color: #e65100; }
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
            <a href="suggestions.php">Suggestions</a>
            <a href="../auth/logout.php" class="nav-login">Logout</a>
        </nav>
    </header>

    <div class="admin-banner">
        <h1>👥 Manage Users</h1>
        <p>View, search, change roles, and manage all platform users.</p>
    </div>

    <div class="admin-section">

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="top-bar">
            <p style="font-size:14px;color:#78909c;"><?= count($users) ?> user(s) found</p>
            <form method="GET" class="search-form">
                <input type="text" name="search" placeholder="Search by name or email..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit">Search</button>
            </form>
        </div>

        <table class="users-table">
            <thead>
                <tr>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Current Role</th>
                    <th>Language</th>
                    <th>Registered</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= htmlspecialchars($user['full_name']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><?= htmlspecialchars($user['phone'] ?? '—') ?></td>
                    <td>
                        <span class="role-badge role-<?= $user['role'] === 'community_member' ? 'member' : $user['role'] ?>">
                            <?= ucfirst(str_replace('_', ' ', $user['role'])) ?>
                        </span>
                    </td>
                    <td><?= ucfirst($user['preferred_language']) ?></td>
                    <td><?= date('d M Y', strtotime($user['created_at'])) ?></td>
                    <td>
                        <form method="POST" style="display:inline-flex;gap:6px;align-items:center;">
                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                            <select name="new_role" class="role-select">
                                <option value="community_member" <?= $user['role'] === 'community_member' ? 'selected' : '' ?>>Member</option>
                                <option value="chw"              <?= $user['role'] === 'chw'              ? 'selected' : '' ?>>CHW</option>
                                <option value="admin"            <?= $user['role'] === 'admin'            ? 'selected' : '' ?>>Admin</option>
                            </select>
                            <button type="submit" name="change_role" class="btn-sm btn-save">Save</button>
                            <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                            <button type="submit" name="delete_user" class="btn-sm btn-delete"
                                onclick="return confirm('Delete this user?')">Delete</button>
                            <?php endif; ?>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    </div>

    <footer>
        <p>© 2026 Samburu Health Platform — Admin Panel</p>
    </footer>

</body>
</html>