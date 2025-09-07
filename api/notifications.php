<?php
/**
 * Notifications API
 * Project Vault - Dr. YC James Yen Government Polytechnic, Kuppam
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_count':
            getNotificationCount($db, $user_id);
            break;
            
        case 'get_all':
            getAllNotifications($db, $user_id);
            break;
            
        case 'mark_read':
            markNotificationAsRead($db, $user_id);
            break;
            
        case 'mark_all_read':
            markAllNotificationsAsRead($db, $user_id);
            break;
            
        case 'delete':
            deleteNotification($db, $user_id);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}

/**
 * Get notification count
 */
function getNotificationCount($db, $user_id) {
    $query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = :user_id AND is_read = 0";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $count = $stmt->fetch()['count'];
    
    echo json_encode(['success' => true, 'count' => $count]);
}

/**
 * Get all notifications
 */
function getAllNotifications($db, $user_id) {
    $limit = $_GET['limit'] ?? 10;
    $offset = $_GET['offset'] ?? 0;
    
    $query = "SELECT * FROM notifications 
              WHERE user_id = :user_id 
              ORDER BY created_at DESC 
              LIMIT :limit OFFSET :offset";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $notifications = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'notifications' => $notifications]);
}

/**
 * Mark notification as read
 */
function markNotificationAsRead($db, $user_id) {
    $notification_id = $_POST['notification_id'] ?? '';
    
    if (empty($notification_id)) {
        echo json_encode(['success' => false, 'message' => 'Notification ID required']);
        return;
    }
    
    $query = "UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $notification_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Notification marked as read']);
}

/**
 * Mark all notifications as read
 */
function markAllNotificationsAsRead($db, $user_id) {
    $query = "UPDATE notifications SET is_read = 1 WHERE user_id = :user_id AND is_read = 0";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'All notifications marked as read']);
}

/**
 * Delete notification
 */
function deleteNotification($db, $user_id) {
    $notification_id = $_POST['notification_id'] ?? '';
    
    if (empty($notification_id)) {
        echo json_encode(['success' => false, 'message' => 'Notification ID required']);
        return;
    }
    
    $query = "DELETE FROM notifications WHERE id = :id AND user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $notification_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Notification deleted']);
}
?>
