<?php
/**
 * Admin Panel
 * Project Vault - Dr. YC James Yen Government Polytechnic, Kuppam
 */

require_once '../../config/config.php';
require_once '../../config/database.php';

// Check if user is logged in and is admin
if (!is_logged_in() || !is_admin()) {
    redirect('auth/login.php');
}

$user_id = $_SESSION['user_id'];

// Get admin statistics
$stats = [
    'total_users' => 0,
    'total_projects' => 0,
    'pending_staff' => 0,
    'pending_collaborations' => 0
];

try {
    // Total users
    $query = "SELECT COUNT(*) as count FROM users WHERE status = 'active'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['total_users'] = $stmt->fetch()['count'];
    
    // Total projects
    $query = "SELECT COUNT(*) as count FROM projects WHERE status = 'active'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['total_projects'] = $stmt->fetch()['count'];
    
    // Pending staff approvals
    $query = "SELECT COUNT(*) as count FROM users WHERE role = 'staff' AND status = 'pending'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['pending_staff'] = $stmt->fetch()['count'];
    
    // Pending collaborations
    $query = "SELECT COUNT(*) as count FROM project_collaborators WHERE status = 'pending'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['pending_collaborations'] = $stmt->fetch()['count'];
} catch (Exception $e) {
    // Handle error silently
}

// Get recent activity
$recent_activity = [];
try {
    $query = "SELECT 'user' as type, username, full_name, created_at FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) ORDER BY created_at DESC LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $recent_users = $stmt->fetchAll();
    
    $query = "SELECT 'project' as type, title, created_at FROM projects WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) ORDER BY created_at DESC LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $recent_projects = $stmt->fetchAll();
    
    $recent_activity = array_merge($recent_users, $recent_projects);
    usort($recent_activity, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    $recent_activity = array_slice($recent_activity, 0, 10);
} catch (Exception $e) {
    // Handle error silently
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - <?php echo APP_NAME; ?></title>
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
                        <p>Admin Panel</p>
                    </div>
                </div>
                
                <nav class="nav">
                    <ul class="nav-links">
                        <li><a href="../../index.php">Home</a></li>
                        <li><a href="../">Dashboard</a></li>
                        <li><a href="index.php" class="active">Admin Panel</a></li>
                    </ul>
                    
                    <div class="user-menu">
                        <div class="user-dropdown">
                            <button class="user-button">
                                <i class="fas fa-user-circle"></i>
                                <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="dropdown-menu">
                                <a href="../profile.php"><i class="fas fa-user"></i> Profile</a>
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
            <!-- Admin Header -->
            <div class="admin-header">
                <h1>Admin Panel</h1>
                <p>Manage users, projects, and system settings</p>
            </div>

            <!-- Admin Statistics -->
            <div class="admin-stats">
                <div class="stat-card admin-stat">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $stats['total_users']; ?></div>
                        <div class="stat-label">Total Users</div>
                    </div>
                    <a href="users.php" class="stat-link">
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                
                <div class="stat-card admin-stat">
                    <div class="stat-icon">
                        <i class="fas fa-folder"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $stats['total_projects']; ?></div>
                        <div class="stat-label">Total Projects</div>
                    </div>
                    <a href="projects.php" class="stat-link">
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                
                <div class="stat-card admin-stat">
                    <div class="stat-icon">
                        <i class="fas fa-user-clock"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $stats['pending_staff']; ?></div>
                        <div class="stat-label">Pending Staff</div>
                    </div>
                    <a href="users.php?filter=pending" class="stat-link">
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                
                <div class="stat-card admin-stat">
                    <div class="stat-icon">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $stats['pending_collaborations']; ?></div>
                        <div class="stat-label">Pending Collaborations</div>
                    </div>
                    <a href="collaborations.php" class="stat-link">
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="admin-actions">
                <h2>Quick Actions</h2>
                <div class="action-grid">
                    <a href="users.php" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-users-cog"></i>
                        </div>
                        <h3>Manage Users</h3>
                        <p>View, edit, and manage user accounts</p>
                    </a>
                    
                    <a href="projects.php" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-folder-open"></i>
                        </div>
                        <h3>Manage Projects</h3>
                        <p>Review and manage all projects</p>
                    </a>
                    
                    <a href="collaborations.php" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-handshake"></i>
                        </div>
                        <h3>Collaborations</h3>
                        <p>Manage collaboration requests</p>
                    </a>
                    
                    <a href="notifications.php" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-bell"></i>
                        </div>
                        <h3>Notifications</h3>
                        <p>View system notifications</p>
                    </a>
                    
                    
                    <a href="reports.php" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <h3>Reports</h3>
                        <p>View system reports and analytics</p>
                    </a>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="row two-col-fixed">
                <div class="col-8">
                    <div class="card">
                        <div class="card-header">
                            <h3>Recent Activity</h3>
                            <span class="badge">Last 7 Days</span>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($recent_activity)): ?>
                                <div class="activity-list">
                                    <?php foreach ($recent_activity as $activity): ?>
                                    <div class="activity-item">
                                        <div class="activity-icon">
                                            <i class="fas fa-<?php echo $activity['type'] === 'user' ? 'user-plus' : 'folder-plus'; ?>"></i>
                                        </div>
                                        <div class="activity-content">
                                            <div class="activity-title">
                                                <?php if ($activity['type'] === 'user'): ?>
                                                    New user registered: <?php echo htmlspecialchars($activity['full_name']); ?>
                                                <?php else: ?>
                                                    New project created: <?php echo htmlspecialchars($activity['title']); ?>
                                                <?php endif; ?>
                                            </div>
                                            <div class="activity-time">
                                                <?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-clock"></i>
                                    <p>No recent activity</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-4">
                    <div class="card">
                        <div class="card-header">
                            <h3>System Status</h3>
                        </div>
                        <div class="card-body">
                            <div class="status-list">
                                <div class="status-item">
                                    <i class="fas fa-database status-ok"></i>
                                    <span>Database</span>
                                    <span class="status-text">Online</span>
                                </div>
                                <div class="status-item">
                                    <i class="fas fa-server status-ok"></i>
                                    <span>Server</span>
                                    <span class="status-text">Running</span>
                                </div>
                                <div class="status-item">
                                    <i class="fas fa-users status-ok"></i>
                                    <span>Users</span>
                                    <span class="status-text"><?php echo $stats['total_users']; ?> Active</span>
                                </div>
                                <div class="status-item">
                                    <i class="fas fa-folder status-ok"></i>
                                    <span>Projects</span>
                                    <span class="status-text"><?php echo $stats['total_projects']; ?> Active</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="../../assets/js/main.js"></script>
    <script src="../../assets/js/dashboard.js"></script>
</body>
</html>
