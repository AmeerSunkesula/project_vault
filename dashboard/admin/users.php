<?php
/**
 * Admin Users Management
 * Project Vault - Dr. YC James Yen Government Polytechnic, Kuppam
 */

require_once '../../config/config.php';
require_once '../../config/database.php';

// Require admin
require_admin();

$user_id = $_SESSION['user_id'];
$message = '';
$error_message = '';

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $target_user_id = $_POST['user_id'] ?? '';
    
    try {
        switch ($action) {
            case 'approve_staff':
                $query = "UPDATE users SET status = 'active' WHERE id = :user_id AND role = 'staff'";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':user_id', $target_user_id);
                $stmt->execute();
                $message = 'Staff user approved successfully.';
                break;
                
            case 'reject_staff':
                $query = "DELETE FROM users WHERE id = :user_id AND role = 'staff' AND status = 'pending'";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':user_id', $target_user_id);
                $stmt->execute();
                $message = 'Staff user rejected and deleted.';
                break;
                
            case 'deactivate_user':
                $query = "UPDATE users SET status = 'rejected' WHERE id = :user_id AND id != :admin_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':user_id', $target_user_id);
                $stmt->bindParam(':admin_id', $user_id);
                $stmt->execute();
                $message = 'User deactivated successfully.';
                break;
                
            case 'activate_user':
                $query = "UPDATE users SET status = 'active' WHERE id = :user_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':user_id', $target_user_id);
                $stmt->execute();
                $message = 'User activated successfully.';
                break;
                
            case 'delete_user':
                // Only allow deletion of students and their projects
                $check_query = "SELECT role FROM users WHERE id = :user_id";
                $check_stmt = $db->prepare($check_query);
                $check_stmt->bindParam(':user_id', $target_user_id);
                $check_stmt->execute();
                $target_user = $check_stmt->fetch();
                
                if ($target_user && $target_user['role'] === 'student') {
                    $db->beginTransaction();
                    
                    // Delete user's projects and related data
                    $delete_projects = "DELETE FROM projects WHERE created_by = :user_id";
                    $stmt = $db->prepare($delete_projects);
                    $stmt->bindParam(':user_id', $target_user_id);
                    $stmt->execute();
                    
                    // Delete user
                    $delete_user = "DELETE FROM users WHERE id = :user_id";
                    $stmt = $db->prepare($delete_user);
                    $stmt->bindParam(':user_id', $target_user_id);
                    $stmt->execute();
                    
                    $db->commit();
                    $message = 'Student user and their projects deleted successfully.';
                } else {
                    $error_message = 'Only student users can be deleted.';
                }
                break;
        }
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollback();
        }
        $error_message = 'An error occurred: ' . $e->getMessage();
    }
}

// Get all users with statistics
$users = [];
try {
    $query = "SELECT u.*, 
                     (SELECT COUNT(*) FROM projects p WHERE p.created_by = u.id) as project_count,
                     (SELECT COUNT(*) FROM project_collaborators pc WHERE pc.user_id = u.id AND pc.status = 'accepted') as collaboration_count
              FROM users u 
              ORDER BY u.created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $users = $stmt->fetchAll();
} catch (Exception $e) {
    $error_message = 'Error loading users.';
}

// Get statistics
$stats = [
    'total_users' => count($users),
    'active_users' => count(array_filter($users, fn($u) => $u['status'] === 'active')),
    'pending_staff' => count(array_filter($users, fn($u) => $u['role'] === 'staff' && $u['status'] === 'pending')),
    'students' => count(array_filter($users, fn($u) => $u['role'] === 'student')),
    'staff' => count(array_filter($users, fn($u) => $u['role'] === 'staff')),
    'admins' => count(array_filter($users, fn($u) => $u['role'] === 'admin'))
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="dashboard-body">
    <!-- Header -->
    <header class="dashboard-header">
        <div class="container">
            <div class="header-content">
                <div class="logo-section">
                    <img src="../../assets/images/polytechnic_logo.jpg" alt="College Logo" class="logo">
                    <div class="logo-text">
                        <h1><?php echo APP_NAME; ?></h1>
                        <p>Admin - Manage Users</p>
                    </div>
                </div>
                
                <nav class="nav">
                    <ul class="nav-links">
                        <li><a href="../../index.php">Home</a></li>
                        <li><a href="../../projects/">Projects</a></li>
                        <li><a href="../../projects/">Projects</a></li>
                        <li><a href="../index.php">Dashboard</a></li>
                        <li><a href="index.php">Admin Panel</a></li>
                    </ul>
                    
                    <div class="user-menu">
                        <div class="user-dropdown">
                            <button class="user-button">
                                <i class="fas fa-user-circle"></i>
                                <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="dropdown-menu">
                                <a href="../profile.php"><i class="fas fa-user"></i> My Profile</a>
                                <a href="../settings.php"><i class="fas fa-cog"></i> Settings</a>
                                <a href="../../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                            </div>
                        </div>
                    </div>
                </nav>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="dashboard-main">
        <div class="container">
            <!-- Page Header -->
            <div class="page-header">
                <h1>Manage Users</h1>
                <p>Manage user accounts, approve staff requests, and monitor user activity</p>
            </div>
            
            <!-- Messages -->
            <?php if ($message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <!-- Statistics -->
            <div class="dashboard-stats">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $stats['total_users']; ?></div>
                        <div class="stat-label">Total Users</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $stats['active_users']; ?></div>
                        <div class="stat-label">Active Users</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-clock"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $stats['pending_staff']; ?></div>
                        <div class="stat-label">Pending Staff</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $stats['students']; ?></div>
                        <div class="stat-label">Students</div>
                    </div>
                </div>
            </div>
            
            <!-- Users Table -->
            <div class="admin-section">
                <div class="section-header">
                    <h2>All Users</h2>
                    <div class="section-actions">
                        <button class="btn btn-primary" onclick="refreshUsers()">
                            <i class="fas fa-sync-alt"></i>
                            Refresh
                        </button>
                    </div>
                </div>
                
                <div class="table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Projects</th>
                                <th>Collaborations</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <div class="user-info">
                                        <div class="user-avatar">
                                            <i class="fas fa-user-circle"></i>
                                        </div>
                                        <div class="user-details">
                                            <div class="user-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
                                            <div class="user-username">@<?php echo htmlspecialchars($user['username']); ?></div>
                                            <div class="user-email"><?php echo htmlspecialchars($user['email']); ?></div>
                                            <?php if ($user['role'] === 'student' && !empty($user['roll_number'])): ?>
                                            <div class="user-roll">Roll: <?php echo htmlspecialchars($user['roll_number']); ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($user['branch'])): ?>
                                            <div class="user-branch"><?php echo $user['branch']; ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="role-badge role-<?php echo $user['role']; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $user['status']; ?>">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="stat-number"><?php echo $user['project_count']; ?></span>
                                </td>
                                <td>
                                    <span class="stat-number"><?php echo $user['collaboration_count']; ?></span>
                                </td>
                                <td>
                                    <span class="date"><?php echo date('M j, Y', strtotime($user['created_at'])); ?></span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if ($user['role'] === 'staff' && $user['status'] === 'pending'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="approve_staff">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-success" 
                                                        onclick="return confirm('Approve this staff user?')">
                                                    <i class="fas fa-check"></i>
                                                    Approve
                                                </button>
                                            </form>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="reject_staff">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" 
                                                        onclick="return confirm('Reject and delete this staff user?')">
                                                    <i class="fas fa-times"></i>
                                                    Reject
                                                </button>
                                            </form>
                                        <?php elseif ($user['id'] != $user_id): ?>
                                            <?php if ($user['status'] === 'active'): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="deactivate_user">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-warning" 
                                                            onclick="return confirm('Deactivate this user?')">
                                                        <i class="fas fa-user-slash"></i>
                                                        Deactivate
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="activate_user">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-success" 
                                                            onclick="return confirm('Activate this user?')">
                                                        <i class="fas fa-user-check"></i>
                                                        Activate
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <?php if ($user['role'] === 'student'): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="delete_user">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger" 
                                                            onclick="return confirm('Delete this student and all their projects? This cannot be undone!')">
                                                        <i class="fas fa-trash"></i>
                                                        Delete
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">Current User</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script src="../../assets/js/main.js"></script>
    <script>
        function refreshUsers() {
            location.reload();
        }
    </script>
</body>
</html>
