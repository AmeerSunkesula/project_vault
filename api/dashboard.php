<?php
/**
 * Dashboard API
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
$user_role = $_SESSION['user_role'];
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_stats':
            getDashboardStats($db, $user_id, $user_role);
            break;
            
        case 'get_recent_projects':
            getRecentProjects($db, $user_id, $user_role);
            break;
            
        case 'get_recent_notifications':
            getRecentNotifications($db, $user_id);
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
 * Get dashboard statistics
 */
function getDashboardStats($db, $user_id, $user_role) {
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
        if ($user_role === 'staff' || $user_role === 'admin') {
            $query = "SELECT COUNT(*) as count FROM projects WHERE status = 'active'";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $stats['total_projects'] = $stmt->fetch()['count'];
        }
        
        echo json_encode(['success' => true, 'stats' => $stats]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error fetching stats']);
    }
}

/**
 * Get recent projects
 */
function getRecentProjects($db, $user_id, $user_role) {
    $limit = $_GET['limit'] ?? 5;
    
    try {
        if ($user_role === 'staff' || $user_role === 'admin') {
            // Staff can see all projects
            $query = "SELECT p.*, u.full_name as creator_name, u.branch as creator_branch 
                      FROM projects p 
                      JOIN users u ON p.created_by = u.id 
                      WHERE p.status = 'active' 
                      ORDER BY p.created_at DESC 
                      LIMIT :limit";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        } else {
            // Students see their own projects and collaborations
            $query = "SELECT DISTINCT p.*, u.full_name as creator_name, u.branch as creator_branch 
                      FROM projects p 
                      JOIN users u ON p.created_by = u.id 
                      LEFT JOIN project_collaborators pc ON p.id = pc.project_id 
                      WHERE p.status = 'active' 
                      AND (p.created_by = :user_id OR pc.user_id = :user_id AND pc.status = 'accepted')
                      ORDER BY p.created_at DESC 
                      LIMIT :limit";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        $projects = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'projects' => $projects]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error fetching projects']);
    }
}

/**
 * Get recent notifications
 */
function getRecentNotifications($db, $user_id) {
    $limit = $_GET['limit'] ?? 5;
    
    try {
        $query = "SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC LIMIT :limit";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $notifications = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'notifications' => $notifications]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error fetching notifications']);
    }
}
?>
