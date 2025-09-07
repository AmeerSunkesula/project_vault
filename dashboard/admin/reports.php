<?php
/**
 * Admin Reports and Analytics
 * Project Vault - Dr. YC James Yen Government Polytechnic, Kuppam
 */

require_once '../../config/config.php';
require_once '../../config/database.php';

// Require admin
require_admin();

$user_id = $_SESSION['user_id'];

// Get date range for reports
$date_range = isset($_GET['date_range']) ? sanitize_input($_GET['date_range']) : '30';
$start_date = date('Y-m-d', strtotime("-{$date_range} days"));
$end_date = date('Y-m-d');

// Get comprehensive statistics
$stats = [
    'total_users' => 0,
    'total_projects' => 0,
    'total_collaborations' => 0,
    'total_comments' => 0,
    'total_votes' => 0,
    'total_stars' => 0,
    'active_users' => 0,
    'pending_staff' => 0,
    'archived_projects' => 0,
    'recent_users' => 0,
    'recent_projects' => 0,
    'recent_collaborations' => 0
];

try {
    // Basic counts
    $queries = [
        'total_users' => "SELECT COUNT(*) as count FROM users",
        'total_projects' => "SELECT COUNT(*) as count FROM projects",
        'total_collaborations' => "SELECT COUNT(*) as count FROM project_collaborators",
        'total_comments' => "SELECT COUNT(*) as count FROM comments",
        'total_votes' => "SELECT COUNT(*) as count FROM project_votes",
        'total_stars' => "SELECT COUNT(*) as count FROM project_stars",
        'active_users' => "SELECT COUNT(*) as count FROM users WHERE status = 'active'",
        'pending_staff' => "SELECT COUNT(*) as count FROM users WHERE role = 'staff' AND status = 'pending'",
        'archived_projects' => "SELECT COUNT(*) as count FROM projects WHERE status = 'archived'",
        'recent_users' => "SELECT COUNT(*) as count FROM users WHERE created_at >= '{$start_date}'",
        'recent_projects' => "SELECT COUNT(*) as count FROM projects WHERE created_at >= '{$start_date}'",
        'recent_collaborations' => "SELECT COUNT(*) as count FROM project_collaborators WHERE requested_at >= '{$start_date}'"
    ];
    
    foreach ($queries as $key => $query) {
        $stmt = $db->prepare($query);
        $stmt->execute();
        $stats[$key] = $stmt->fetch()['count'];
    }
} catch (Exception $e) {
    // Handle error silently
}

// Get user statistics by role
$user_stats = [];
try {
    $query = "SELECT role, status, COUNT(*) as count FROM users GROUP BY role, status ORDER BY role, status";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $user_stats = $stmt->fetchAll();
} catch (Exception $e) {
    // Handle error silently
}

// Get project statistics by branch
$project_stats = [];
try {
    $query = "SELECT branch, COUNT(*) as count FROM projects GROUP BY branch ORDER BY count DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $project_stats = $stmt->fetchAll();
} catch (Exception $e) {
    // Handle error silently
}

// Get collaboration statistics
$collaboration_stats = [];
try {
    $query = "SELECT status, COUNT(*) as count FROM project_collaborators GROUP BY status ORDER BY status";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $collaboration_stats = $stmt->fetchAll();
} catch (Exception $e) {
    // Handle error silently
}

// Get recent activity
$recent_activity = [];
try {
    $query = "SELECT 'user' as type, username, full_name, created_at FROM users WHERE created_at >= '{$start_date}' ORDER BY created_at DESC LIMIT 10";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $recent_users = $stmt->fetchAll();
    
    $query = "SELECT 'project' as type, title, created_at FROM projects WHERE created_at >= '{$start_date}' ORDER BY created_at DESC LIMIT 10";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $recent_projects = $stmt->fetchAll();
    
    $recent_activity = array_merge($recent_users, $recent_projects);
    usort($recent_activity, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    $recent_activity = array_slice($recent_activity, 0, 15);
} catch (Exception $e) {
    // Handle error silently
}

// Get top projects by votes
$top_projects = [];
try {
    $query = "SELECT p.title, p.branch, u.full_name as creator, 
                     (SELECT COUNT(*) FROM project_votes pv WHERE pv.project_id = p.id AND pv.vote_type = 'upvote') as upvotes,
                     (SELECT COUNT(*) FROM project_stars ps WHERE ps.project_id = p.id) as stars
              FROM projects p 
              JOIN users u ON p.created_by = u.id 
              WHERE p.status = 'active'
              ORDER BY upvotes DESC, stars DESC 
              LIMIT 10";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $top_projects = $stmt->fetchAll();
} catch (Exception $e) {
    // Handle error silently
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - <?php echo APP_NAME; ?></title>
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
                        <p>Admin - Reports & Analytics</p>
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
                <h1>Reports & Analytics</h1>
                <p>Comprehensive system statistics and performance metrics</p>
                
                <!-- Date Range Filter -->
                <div class="date-filter">
                    <form method="GET" class="filter-form">
                        <label for="date_range">Report Period:</label>
                        <select id="date_range" name="date_range" onchange="this.form.submit()">
                            <option value="7" <?php echo ($date_range === '7') ? 'selected' : ''; ?>>Last 7 days</option>
                            <option value="30" <?php echo ($date_range === '30') ? 'selected' : ''; ?>>Last 30 days</option>
                            <option value="90" <?php echo ($date_range === '90') ? 'selected' : ''; ?>>Last 90 days</option>
                            <option value="365" <?php echo ($date_range === '365') ? 'selected' : ''; ?>>Last year</option>
                        </select>
                    </form>
                </div>
            </div>
            
            <!-- Overview Statistics -->
            <div class="dashboard-stats">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $stats['total_users']; ?></div>
                        <div class="stat-label">Total Users</div>
                        <div class="stat-change">+<?php echo $stats['recent_users']; ?> this period</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-folder"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $stats['total_projects']; ?></div>
                        <div class="stat-label">Total Projects</div>
                        <div class="stat-change">+<?php echo $stats['recent_projects']; ?> this period</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $stats['total_collaborations']; ?></div>
                        <div class="stat-label">Collaborations</div>
                        <div class="stat-change">+<?php echo $stats['recent_collaborations']; ?> this period</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $stats['total_comments']; ?></div>
                        <div class="stat-label">Comments</div>
                    </div>
                </div>
            </div>
            
            <!-- Detailed Statistics -->
            <div class="row">
                <div class="col-6">
                    <div class="card">
                        <div class="card-header">
                            <h3>User Statistics</h3>
                        </div>
                        <div class="card-body">
                            <div class="stats-table">
                                <table class="admin-table">
                                    <thead>
                                        <tr>
                                            <th>Role</th>
                                            <th>Status</th>
                                            <th>Count</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($user_stats as $stat): ?>
                                        <tr>
                                            <td>
                                                <span class="role-badge role-<?php echo $stat['role']; ?>">
                                                    <?php echo ucfirst($stat['role']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?php echo $stat['status']; ?>">
                                                    <?php echo ucfirst($stat['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $stat['count']; ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-6">
                    <div class="card">
                        <div class="card-header">
                            <h3>Project Statistics by Branch</h3>
                        </div>
                        <div class="card-body">
                            <div class="stats-table">
                                <table class="admin-table">
                                    <thead>
                                        <tr>
                                            <th>Branch</th>
                                            <th>Projects</th>
                                            <th>Percentage</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $total_projects = $stats['total_projects'];
                                        foreach ($project_stats as $stat): 
                                            $percentage = $total_projects > 0 ? round(($stat['count'] / $total_projects) * 100, 1) : 0;
                                        ?>
                                        <tr>
                                            <td>
                                                <span class="tag branch-tag"><?php echo $stat['branch']; ?></span>
                                            </td>
                                            <td><?php echo $stat['count']; ?></td>
                                            <td><?php echo $percentage; ?>%</td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Collaboration Statistics -->
            <div class="row">
                <div class="col-6">
                    <div class="card">
                        <div class="card-header">
                            <h3>Collaboration Status</h3>
                        </div>
                        <div class="card-body">
                            <div class="stats-table">
                                <table class="admin-table">
                                    <thead>
                                        <tr>
                                            <th>Status</th>
                                            <th>Count</th>
                                            <th>Percentage</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $total_collaborations = $stats['total_collaborations'];
                                        foreach ($collaboration_stats as $stat): 
                                            $percentage = $total_collaborations > 0 ? round(($stat['count'] / $total_collaborations) * 100, 1) : 0;
                                        ?>
                                        <tr>
                                            <td>
                                                <span class="status-badge status-<?php echo $stat['status']; ?>">
                                                    <?php echo ucfirst($stat['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $stat['count']; ?></td>
                                            <td><?php echo $percentage; ?>%</td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-6">
                    <div class="card">
                        <div class="card-header">
                            <h3>Top Projects by Popularity</h3>
                        </div>
                        <div class="card-body">
                            <div class="stats-table">
                                <table class="admin-table">
                                    <thead>
                                        <tr>
                                            <th>Project</th>
                                            <th>Creator</th>
                                            <th>Upvotes</th>
                                            <th>Stars</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($top_projects as $project): ?>
                                        <tr>
                                            <td>
                                                <div class="project-title">
                                                    <?php echo htmlspecialchars($project['title']); ?>
                                                </div>
                                                <div class="project-meta">
                                                    <span class="tag branch-tag"><?php echo $project['branch']; ?></span>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($project['creator']); ?></td>
                                            <td>
                                                <span class="stat-number">
                                                    <i class="fas fa-thumbs-up"></i>
                                                    <?php echo $project['upvotes']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="stat-number">
                                                    <i class="fas fa-star"></i>
                                                    <?php echo $project['stars']; ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3>Recent Activity</h3>
                            <span class="badge">Last <?php echo $date_range; ?> days</span>
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
                                    <i class="fas fa-chart-line"></i>
                                    <p>No recent activity for the selected period</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="../../assets/js/main.js"></script>
    <script>
        // Auto-refresh reports every 5 minutes
        setInterval(function() {
            location.reload();
        }, 300000);
    </script>
</body>
</html>
