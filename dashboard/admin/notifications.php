<?php
/**
 * Admin Notifications Management
 * Project Vault - Dr. YC James Yen Government Polytechnic, Kuppam
 */

require_once '../../config/config.php';
require_once '../../config/database.php';

// Require admin
require_admin();

$message = '';
$error_message = '';

// Handle notification actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $notification_id = $_POST['notification_id'] ?? '';
    
    try {
        switch ($action) {
            case 'mark_read':
                $query = "UPDATE notifications SET is_read = 1 WHERE id = :notification_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':notification_id', $notification_id);
                $stmt->execute();
                $message = 'Notification marked as read.';
                break;
                
            case 'mark_unread':
                $query = "UPDATE notifications SET is_read = 0 WHERE id = :notification_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':notification_id', $notification_id);
                $stmt->execute();
                $message = 'Notification marked as unread.';
                break;
                
            case 'delete_notification':
                $query = "DELETE FROM notifications WHERE id = :notification_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':notification_id', $notification_id);
                $stmt->execute();
                $message = 'Notification deleted successfully.';
                break;
                
            case 'mark_all_read':
                $query = "UPDATE notifications SET is_read = 1";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $message = 'All notifications marked as read.';
                break;
                
            case 'delete_all_read':
                $query = "DELETE FROM notifications WHERE is_read = 1";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $message = 'All read notifications deleted.';
                break;
        }
    } catch (Exception $e) {
        $error_message = 'An error occurred: ' . $e->getMessage();
    }
}

// Get search and filter parameters
$type = isset($_GET['type']) ? sanitize_input($_GET['type']) : '';
$read_status = isset($_GET['read_status']) ? sanitize_input($_GET['read_status']) : '';
$sort = isset($_GET['sort']) ? sanitize_input($_GET['sort']) : 'newest';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Build query
$where_conditions = ["1=1"];
$params = [];

if (!empty($type)) {
    $where_conditions[] = "n.type = :type";
    $params[':type'] = $type;
}

if ($read_status !== '') {
    $where_conditions[] = "n.is_read = :read_status";
    $params[':read_status'] = ($read_status === 'read') ? 1 : 0;
}

$where_clause = implode(' AND ', $where_conditions);

// Sort options
$sort_options = [
    'newest' => 'n.created_at DESC',
    'oldest' => 'n.created_at ASC',
    'type' => 'n.type ASC',
    'user' => 'u.full_name ASC'
];

$order_by = $sort_options[$sort] ?? $sort_options['newest'];

// Get notifications
$notifications = [];
$total_notifications = 0;

try {
    // Count total notifications
    $count_query = "SELECT COUNT(*) as total FROM notifications n 
                    JOIN users u ON n.user_id = u.id 
                    WHERE {$where_clause}";
    $count_stmt = $db->prepare($count_query);
    foreach ($params as $key => $value) {
        $count_stmt->bindValue($key, $value);
    }
    $count_stmt->execute();
    $total_notifications = $count_stmt->fetch()['total'];
    
    // Get notifications with pagination
    $query = "SELECT n.*, u.full_name as user_name, u.username as user_username, u.email as user_email
              FROM notifications n 
              JOIN users u ON n.user_id = u.id 
              WHERE {$where_clause}
              ORDER BY {$order_by}
              LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $notifications = $stmt->fetchAll();
} catch (Exception $e) {
    $error_message = 'Error loading notifications.';
}

// Calculate pagination
$total_pages = ceil($total_notifications / $limit);
$has_prev = $page > 1;
$has_next = $page < $total_pages;

// Get statistics
$stats = [
    'total_notifications' => $total_notifications,
    'unread_notifications' => 0,
    'read_notifications' => 0,
    'collaboration_requests' => 0,
    'project_approvals' => 0,
    'password_resets' => 0
];

foreach ($notifications as $notification) {
    if ($notification['is_read']) {
        $stats['read_notifications']++;
    } else {
        $stats['unread_notifications']++;
    }
    
    switch ($notification['type']) {
        case 'collaboration_request':
        case 'collaboration_response':
            $stats['collaboration_requests']++;
            break;
        case 'project_approval':
            $stats['project_approvals']++;
            break;
        case 'password_reset':
            $stats['password_resets']++;
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Notifications - <?php echo APP_NAME; ?></title>
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
                        <p>Admin - Manage Notifications</p>
                    </div>
                </div>
                
                <nav class="nav">
                    <ul class="nav-links">
                        <li><a href="../../index.php">Home</a></li>
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
                <h1>Manage Notifications</h1>
                <p>Monitor and manage all system notifications</p>
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
                        <i class="fas fa-bell"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $stats['total_notifications']; ?></div>
                        <div class="stat-label">Total Notifications</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-bell-slash"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $stats['unread_notifications']; ?></div>
                        <div class="stat-label">Unread</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $stats['collaboration_requests']; ?></div>
                        <div class="stat-label">Collaborations</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-key"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $stats['password_resets']; ?></div>
                        <div class="stat-label">Password Resets</div>
                    </div>
                </div>
            </div>
            
            <!-- Bulk Actions -->
            <div class="bulk-actions">
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="mark_all_read">
                    <button type="submit" class="btn btn-secondary" 
                            onclick="return confirm('Mark all notifications as read?')">
                        <i class="fas fa-check-double"></i>
                        Mark All Read
                    </button>
                </form>
                
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete_all_read">
                    <button type="submit" class="btn btn-danger" 
                            onclick="return confirm('Delete all read notifications? This cannot be undone!')">
                        <i class="fas fa-trash"></i>
                        Delete All Read
                    </button>
                </form>
            </div>
            
            <!-- Filters -->
            <div class="search-filters">
                <form method="GET" class="search-form">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="type">Type</label>
                            <select id="type" name="type">
                                <option value="">All Types</option>
                                <option value="collaboration_request" <?php echo ($type === 'collaboration_request') ? 'selected' : ''; ?>>Collaboration Request</option>
                                <option value="collaboration_response" <?php echo ($type === 'collaboration_response') ? 'selected' : ''; ?>>Collaboration Response</option>
                                <option value="project_approval" <?php echo ($type === 'project_approval') ? 'selected' : ''; ?>>Project Approval</option>
                                <option value="password_reset" <?php echo ($type === 'password_reset') ? 'selected' : ''; ?>>Password Reset</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="read_status">Read Status</label>
                            <select id="read_status" name="read_status">
                                <option value="">All</option>
                                <option value="unread" <?php echo ($read_status === 'unread') ? 'selected' : ''; ?>>Unread</option>
                                <option value="read" <?php echo ($read_status === 'read') ? 'selected' : ''; ?>>Read</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="sort">Sort By</label>
                            <select id="sort" name="sort">
                                <option value="newest" <?php echo ($sort === 'newest') ? 'selected' : ''; ?>>Newest First</option>
                                <option value="oldest" <?php echo ($sort === 'oldest') ? 'selected' : ''; ?>>Oldest First</option>
                                <option value="type" <?php echo ($sort === 'type') ? 'selected' : ''; ?>>Type</option>
                                <option value="user" <?php echo ($sort === 'user') ? 'selected' : ''; ?>>User</option>
                            </select>
                        </div>
                        
                        <div class="filter-actions">
                            <button type="submit" class="btn btn-secondary">
                                <i class="fas fa-filter"></i>
                                Apply Filters
                            </button>
                            <a href="notifications.php" class="btn btn-outline">
                                <i class="fas fa-times"></i>
                                Clear
                            </a>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Notifications Table -->
            <div class="admin-section">
                <div class="section-header">
                    <h2>All Notifications</h2>
                    <div class="section-actions">
                        <button class="btn btn-primary" onclick="refreshNotifications()">
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
                                <th>Type</th>
                                <th>Title</th>
                                <th>Message</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($notifications as $notification): ?>
                            <tr class="<?php echo $notification['is_read'] ? '' : 'unread-row'; ?>">
                                <td>
                                    <div class="user-info">
                                        <div class="user-name"><?php echo htmlspecialchars($notification['user_name']); ?></div>
                                        <div class="user-username">@<?php echo htmlspecialchars($notification['user_username']); ?></div>
                                        <div class="user-email"><?php echo htmlspecialchars($notification['user_email']); ?></div>
                                    </div>
                                </td>
                                <td>
                                    <span class="type-badge type-<?php echo $notification['type']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $notification['type'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="notification-title">
                                        <?php echo htmlspecialchars($notification['title']); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="notification-message">
                                        <?php echo htmlspecialchars(substr($notification['message'], 0, 100)) . '...'; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $notification['is_read'] ? 'read' : 'unread'; ?>">
                                        <?php echo $notification['is_read'] ? 'Read' : 'Unread'; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="date"><?php echo date('M j, Y H:i', strtotime($notification['created_at'])); ?></span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if ($notification['is_read']): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="mark_unread">
                                                <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-warning">
                                                    <i class="fas fa-envelope"></i>
                                                    Mark Unread
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="mark_read">
                                                <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-success">
                                                    <i class="fas fa-envelope-open"></i>
                                                    Mark Read
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="delete_notification">
                                            <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" 
                                                    onclick="return confirm('Delete this notification?')">
                                                <i class="fas fa-trash"></i>
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($has_prev): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
                           class="btn btn-secondary">
                            <i class="fas fa-chevron-left"></i>
                            Previous
                        </a>
                    <?php endif; ?>
                    
                    <div class="pagination-info">
                        Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                    </div>
                    
                    <?php if ($has_next): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                           class="btn btn-secondary">
                            Next
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="../../assets/js/main.js"></script>
    <script>
        function refreshNotifications() {
            location.reload();
        }
    </script>
</body>
</html>
