<?php
/**
 * Dashboard Home
 * Project Vault - Dr. YC James Yen Government Polytechnic, Kuppam
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Require user to be logged in
require_login();

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// Get user information
try {
    $query = "SELECT * FROM users WHERE id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user = $stmt->fetch();
} catch (Exception $e) {
    $error_message = 'Error loading user information.';
}

// Get dashboard statistics
$stats = [
    'my_projects' => 0,
    'collaborations' => 0,
    'notifications' => 0,
    'total_projects' => 0
];

try {
    // My projects count
    $query = "SELECT COUNT(*) as count FROM projects WHERE created_by = :user_id AND status = 'active'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $stats['my_projects'] = $stmt->fetch()['count'];
    
    // Collaborations count
    $query = "SELECT COUNT(*) as count FROM project_collaborators WHERE user_id = :user_id AND status = 'accepted'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $stats['collaborations'] = $stmt->fetch()['count'];
    
    // Unread notifications count
    $query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = :user_id AND is_read = 0";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $stats['notifications'] = $stmt->fetch()['count'];
    
    // Total projects (for staff/admin)
    if (is_staff()) {
        $query = "SELECT COUNT(*) as count FROM projects WHERE status = 'active'";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $stats['total_projects'] = $stmt->fetch()['count'];
    }
} catch (Exception $e) {
    // Handle error silently
}

// Get recent projects
$recent_projects = [];
try {
    if (is_staff()) {
        // Staff can see all projects
        $query = "SELECT p.*, u.full_name as creator_name, u.branch as creator_branch 
                  FROM projects p 
                  JOIN users u ON p.created_by = u.id 
                  WHERE p.status = 'active' 
                  ORDER BY p.created_at DESC 
                  LIMIT 5";
    } else {
        // Students see their own projects and collaborations
        $query = "SELECT DISTINCT p.*, u.full_name as creator_name, u.branch as creator_branch 
                  FROM projects p 
                  JOIN users u ON p.created_by = u.id 
                  LEFT JOIN project_collaborators pc ON p.id = pc.project_id 
                  WHERE p.status = 'active' 
                  AND (p.created_by = :user_id OR pc.user_id = :user_id AND pc.status = 'accepted')
                  ORDER BY p.created_at DESC 
                  LIMIT 5";
    }
    
    $stmt = $db->prepare($query);
    if (!is_staff()) {
        $stmt->bindParam(':user_id', $user_id);
    }
    $stmt->execute();
    $recent_projects = $stmt->fetchAll();
} catch (Exception $e) {
    // Handle error silently
}

// Get recent notifications
$recent_notifications = [];
try {
    $query = "SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $recent_notifications = $stmt->fetchAll();
} catch (Exception $e) {
    // Handle error silently
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="dashboard-body">
    <!-- Header -->
    <header class="dashboard-header">
        <div class="container">
            <div class="header-content">
                <div class="logo-section">
                    <img src="../assets/images/polytechnic_logo.jpg" alt="College Logo" class="logo">
                    <div class="logo-text">
                        <h1><?php echo APP_NAME; ?></h1>
                        <p>Dashboard</p>
                    </div>
                </div>
                
                <nav class="nav">
                    <ul class="nav-links">
                        <li><a href="../index.php">Home</a></li>
                        <li><a href="../projects/">Projects</a></li>
                        <li><a href="projects/">My Projects</a></li>
                        <?php if (is_staff()): ?>
                        <li><a href="admin/">Admin Panel</a></li>
                        <?php endif; ?>
                    </ul>
                    
                    <div class="user-menu">


                    <div class="user-dropdown">
                            <button class="user-button">
                                <i class="fas fa-user-circle"></i>
                                <?php echo htmlspecialchars($user['full_name']); ?>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="dropdown-menu">
                                <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                                <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                                <a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
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
            <!-- Welcome Section -->
            <div class="welcome-section">
                <h1>Welcome back, <?php echo htmlspecialchars($user['full_name']); ?>!</h1>
                <p>Here's what's happening with your projects and collaborations.</p>
            </div>

            <!-- Statistics Cards -->
            <div class="dashboard-stats">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-folder"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $stats['my_projects']; ?></div>
                        <div class="stat-label">My Projects</div>
                    </div>
                    <a href="projects/index.php" class="stat-link">
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $stats['collaborations']; ?></div>
                        <div class="stat-label">Collaborations</div>
                    </div>
                    <a href="collaborations.php" class="stat-link">
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-bell"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $stats['notifications']; ?></div>
                        <div class="stat-label">Notifications</div>
                    </div>
                    <a href="notifications.php" class="stat-link">
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                
                <?php if (is_staff()): ?>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-globe"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $stats['total_projects']; ?></div>
                        <div class="stat-label">Total Projects</div>
                    </div>
                    <a href="admin/projects.php" class="stat-link">
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <h2>Quick Actions</h2>
                <div class="action-buttons">
                    <a href="projects/add.php" class="action-btn">
                        <i class="fas fa-plus"></i>
                        <span>Add New Project</span>
                    </a>
                    
                    <a href="projects/index.php" class="action-btn">
                        <i class="fas fa-folder"></i>
                        <span>My Projects</span>
                    </a>
                    
                    <a href="../projects/" class="action-btn">
                        <i class="fas fa-search"></i>
                        <span>Explore Projects</span>
                    </a>
                    
                    <a href="collaborations.php" class="action-btn">
                        <i class="fas fa-handshake"></i>
                        <span>Collaborations</span>
                    </a>
                    
                    <a href="profile.php" class="action-btn">
                        <i class="fas fa-user"></i>
                        <span>My Profile</span>
                    </a>
                    
                    <?php if (is_staff()): ?>
                    <a href="admin/users.php" class="action-btn">
                        <i class="fas fa-users-cog"></i>
                        <span>Manage Users</span>
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="row two-col-fixed">
                <div class="col-8">
                    <div class="card">
                        <div class="card-header">
                            <h3>Recent Projects</h3>
                            <a href="projects/" class="btn btn-sm btn-primary">View All</a>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($recent_projects)): ?>
                                <?php foreach ($recent_projects as $project): ?>
                                <div class="project-item">
                                    <div class="project-info">
                                        <h4><?php echo htmlspecialchars($project['title']); ?></h4>
                                        <p><?php echo htmlspecialchars(substr($project['short_description'], 0, 100)) . '...'; ?></p>
                                        <div class="project-meta">
                                            <span><i class="fas fa-user"></i> <a href="../projects/user.php?id=<?php echo $project['created_by']; ?>" class="creator-link"><?php echo htmlspecialchars($project['creator_name']); ?></a></span>
                                            <span><i class="fas fa-graduation-cap"></i> <?php echo $project['branch']; ?></span>
                                            <span><i class="fas fa-calendar"></i> <?php echo date('M j, Y', strtotime($project['created_at'])); ?></span>
                                        </div>
                                    </div>
                                    <div class="project-actions">
                                        <a href="../project.php?id=<?php echo $project['id']; ?>" class="btn btn-sm btn-primary">View</a>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-folder-open"></i>
                                    <p>No recent projects found.</p>
                                    <a href="projects/add.php" class="btn btn-primary">Add Your First Project</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-4">
                    <div class="card">
                        <div class="card-header">
                            <h3>Recent Notifications</h3>
                            <a href="notifications.php" class="btn btn-sm btn-primary">View All</a>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($recent_notifications)): ?>
                                <?php foreach (array_slice($recent_notifications, 0, 3) as $notification): ?>
                                <div class="notification-preview <?php echo $notification['is_read'] ? '' : 'unread'; ?>">
                                    <div class="notification-title"><?php echo htmlspecialchars($notification['title']); ?></div>
                                    <div class="notification-message"><?php echo htmlspecialchars(substr($notification['message'], 0, 60)) . '...'; ?></div>
                                    <div class="notification-time"><?php echo date('M j, g:i A', strtotime($notification['created_at'])); ?></div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-bell-slash"></i>
                                    <p>No notifications</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/dashboard.js"></script>
</body>
</html>
