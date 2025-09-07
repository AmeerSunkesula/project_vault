<?php
/**
 * Notifications Dashboard
 * Project Vault - Dr. YC James Yen Government Polytechnic, Kuppam
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Require user to be logged in
require_login();

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// Handle notification actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'mark_read':
            markNotificationAsRead($db, $_POST['notification_id']);
            break;
        case 'mark_all_read':
            markAllNotificationsAsRead($db, $user_id);
            break;
        case 'delete_notification':
            deleteNotification($db, $_POST['notification_id']);
            break;
    }
}

/**
 * Mark notification as read
 */
function markNotificationAsRead($db, $notification_id) {
    global $user_id;
    
    try {
        $query = "UPDATE notifications SET is_read = TRUE WHERE id = :notification_id AND user_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':notification_id', $notification_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $_SESSION['success_message'] = 'Notification marked as read.';
    } catch (Exception $e) {
        $_SESSION['error_message'] = 'Failed to mark notification as read.';
    }
    
    redirect('notifications.php');
}

/**
 * Mark all notifications as read
 */
function markAllNotificationsAsRead($db, $user_id) {
    try {
        $query = "UPDATE notifications SET is_read = TRUE WHERE user_id = :user_id AND is_read = FALSE";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $_SESSION['success_message'] = 'All notifications marked as read.';
    } catch (Exception $e) {
        $_SESSION['error_message'] = 'Failed to mark all notifications as read.';
    }
    
    redirect('notifications.php');
}

/**
 * Delete notification
 */
function deleteNotification($db, $notification_id) {
    global $user_id;
    
    try {
        $query = "DELETE FROM notifications WHERE id = :notification_id AND user_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':notification_id', $notification_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $_SESSION['success_message'] = 'Notification deleted.';
    } catch (Exception $e) {
        $_SESSION['error_message'] = 'Failed to delete notification.';
    }
    
    redirect('notifications.php');
}

// Get filter parameters
$filter = $_GET['filter'] ?? 'all';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Build query
$where_conditions = ["user_id = :user_id"];
$params = [':user_id' => $user_id];

if ($filter === 'unread') {
    $where_conditions[] = "is_read = FALSE";
} elseif ($filter === 'read') {
    $where_conditions[] = "is_read = TRUE";
}

$where_clause = implode(' AND ', $where_conditions);

// Get notifications
$notifications = [];
$total_notifications = 0;

try {
    // Count total notifications
    $count_query = "SELECT COUNT(*) as total FROM notifications WHERE {$where_clause}";
    $count_stmt = $db->prepare($count_query);
    foreach ($params as $key => $value) {
        $count_stmt->bindValue($key, $value);
    }
    $count_stmt->execute();
    $total_notifications = $count_stmt->fetch()['total'];
    
    // Get notifications with pagination
    $query = "SELECT * FROM notifications 
              WHERE {$where_clause}
              ORDER BY created_at DESC
              LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $notifications = $stmt->fetchAll();
    
    // Get unread count
    $unread_query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = :user_id AND is_read = FALSE";
    $unread_stmt = $db->prepare($unread_query);
    $unread_stmt->bindParam(':user_id', $user_id);
    $unread_stmt->execute();
    $unread_count = $unread_stmt->fetch()['count'];
    
} catch (Exception $e) {
    $error_message = 'Error loading notifications.';
}

// Calculate pagination
$total_pages = ceil($total_notifications / $limit);
$has_prev = $page > 1;
$has_next = $page < $total_pages;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - <?php echo APP_NAME; ?></title>
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
                        <p>Notifications</p>
                    </div>
                </div>
                
                <nav class="nav">
                    <ul class="nav-links">
                        <li><a href="../index.php">Home</a></li>
                        <li><a href="../projects/">Projects</a></li>
                        <li><a href="index.php">Dashboard</a></li>
                        <li><a href="notifications.php" class="active">Notifications</a></li>
                    </ul>
                    
                    <div class="user-menu">
                        <div class="user-dropdown">
                            <button class="user-button">
                                <i class="fas fa-user-circle"></i>
                                <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="dropdown-menu">
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
            <div class="page-header">
                <h1>Notifications</h1>
                <p>Stay updated with your project activities and collaborations</p>
            </div>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                </div>
            <?php endif; ?>
            
            <!-- Notification Filters -->
            <div class="notification-filters">
                <div class="filter-tabs">
                    <a href="?filter=all" class="filter-tab <?php echo $filter === 'all' ? 'active' : ''; ?>">
                        <i class="fas fa-bell"></i>
                        All (<?php echo $total_notifications; ?>)
                    </a>
                    <a href="?filter=unread" class="filter-tab <?php echo $filter === 'unread' ? 'active' : ''; ?>">
                        <i class="fas fa-bell-slash"></i>
                        Unread (<?php echo $unread_count ?? 0; ?>)
                    </a>
                    <a href="?filter=read" class="filter-tab <?php echo $filter === 'read' ? 'active' : ''; ?>">
                        <i class="fas fa-check"></i>
                        Read
                    </a>
                </div>
                
                <?php if ($unread_count > 0): ?>
                <div class="notification-actions">
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="mark_all_read">
                        <button type="submit" class="btn btn-secondary btn-sm">
                            <i class="fas fa-check-double"></i>
                            Mark All as Read
                        </button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Notifications List -->
            <?php if (!empty($notifications)): ?>
            <div class="notifications-list">
                <?php foreach ($notifications as $notification): ?>
                <div class="notification-item <?php echo $notification['is_read'] ? 'read' : 'unread'; ?>">
                    <div class="notification-icon">
                        <?php
                        $icon_class = 'fas fa-bell';
                        switch ($notification['type']) {
                            case 'collaboration_request':
                                $icon_class = 'fas fa-handshake';
                                break;
                            case 'collaboration_response':
                                $icon_class = 'fas fa-check-circle';
                                break;
                            case 'project_approval':
                                $icon_class = 'fas fa-thumbs-up';
                                break;
                            case 'password_reset':
                                $icon_class = 'fas fa-key';
                                break;
                        }
                        ?>
                        <i class="<?php echo $icon_class; ?>"></i>
                    </div>
                    
                    <div class="notification-content">
                        <div class="notification-header">
                            <h4><?php echo htmlspecialchars($notification['title']); ?></h4>
                            <span class="notification-time">
                                <?php echo timeAgo($notification['created_at']); ?>
                            </span>
                        </div>
                        
                        <p class="notification-message">
                            <?php echo htmlspecialchars($notification['message']); ?>
                        </p>
                        
                        <?php if ($notification['related_id']): ?>
                        <div class="notification-actions">
                            <?php if ($notification['type'] === 'collaboration_request' || $notification['type'] === 'collaboration_response'): ?>
                                <a href="collaborations.php" class="btn btn-sm btn-primary">
                                    <i class="fas fa-handshake"></i>
                                    View Collaborations
                                </a>
                            <?php elseif ($notification['type'] === 'project_approval'): ?>
                                <a href="../project.php?id=<?php echo $notification['related_id']; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye"></i>
                                    View Project
                                </a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="notification-controls">
                        <?php if (!$notification['is_read']): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="mark_read">
                            <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-outline" title="Mark as read">
                                <i class="fas fa-check"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                        
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this notification?')">
                            <input type="hidden" name="action" value="delete_notification">
                            <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-danger" title="Delete notification">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
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
            
            <?php else: ?>
            <!-- Empty State -->
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-bell-slash"></i>
                </div>
                <h3>No Notifications</h3>
                <p>
                    <?php if ($filter === 'unread'): ?>
                        You have no unread notifications.
                    <?php elseif ($filter === 'read'): ?>
                        You have no read notifications.
                    <?php else: ?>
                        You don't have any notifications yet.
                    <?php endif; ?>
                </p>
                <div class="empty-state-actions">
                    <a href="../projects/" class="btn btn-primary">
                        <i class="fas fa-folder"></i>
                        Browse Projects
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="../assets/js/main.js"></script>
</body>
</html>

<?php
/**
 * Helper function to calculate time ago
 */
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) {
        return 'Just now';
    } elseif ($time < 3600) {
        $minutes = floor($time / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($time < 86400) {
        $hours = floor($time / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($time < 2592000) {
        $days = floor($time / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y', strtotime($datetime));
    }
}
?>
