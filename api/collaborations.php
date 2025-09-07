<?php
/**
 * Collaborations API
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
        case 'request':
            requestCollaboration($db, $user_id);
            break;
            
        case 'respond':
            respondToCollaboration($db, $user_id);
            break;
            
        case 'get_requests':
            getCollaborationRequests($db, $user_id);
            break;
            
        case 'get_sent':
            getSentCollaborationRequests($db, $user_id);
            break;
            
        case 'cancel':
            cancelCollaborationRequest($db, $user_id);
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
 * Request collaboration on a project
 */
function requestCollaboration($db, $user_id) {
    $project_id = $_POST['project_id'] ?? '';
    
    if (empty($project_id)) {
        echo json_encode(['success' => false, 'message' => 'Project ID required']);
        return;
    }
    
    // Check if project exists and user is not the creator
    $project_query = "SELECT created_by FROM projects WHERE id = :project_id AND status = 'active'";
    $project_stmt = $db->prepare($project_query);
    $project_stmt->bindParam(':project_id', $project_id);
    $project_stmt->execute();
    $project = $project_stmt->fetch();
    
    if (!$project) {
        echo json_encode(['success' => false, 'message' => 'Project not found']);
        return;
    }
    
    if ($project['created_by'] == $user_id) {
        echo json_encode(['success' => false, 'message' => 'You cannot collaborate on your own project']);
        return;
    }
    
    // Check if collaboration request already exists
    $check_query = "SELECT id FROM project_collaborators WHERE project_id = :project_id AND user_id = :user_id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':project_id', $project_id);
    $check_stmt->bindParam(':user_id', $user_id);
    $check_stmt->execute();
    
    if ($check_stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Collaboration request already exists']);
        return;
    }
    
    // Insert collaboration request
    $query = "INSERT INTO project_collaborators (project_id, user_id, status) VALUES (:project_id, :user_id, 'pending')";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':project_id', $project_id);
    $stmt->bindParam(':user_id', $user_id);
    
    if ($stmt->execute()) {
        // Create notification for project creator
        $notification_query = "INSERT INTO notifications (user_id, type, title, message, related_id) 
                              VALUES (:creator_id, 'collaboration_request', 'New Collaboration Request', 
                                     :message, :project_id)";
        $notification_stmt = $db->prepare($notification_query);
        $message = $_SESSION['full_name'] . ' has requested to collaborate on your project';
        $notification_stmt->bindParam(':creator_id', $project['created_by']);
        $notification_stmt->bindParam(':message', $message);
        $notification_stmt->bindParam(':project_id', $project_id);
        $notification_stmt->execute();
        
        echo json_encode(['success' => true, 'message' => 'Collaboration request sent successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send collaboration request']);
    }
}

/**
 * Respond to collaboration request
 */
function respondToCollaboration($db, $user_id) {
    $collaboration_id = $_POST['collaboration_id'] ?? '';
    $response = $_POST['response'] ?? ''; // 'accept' or 'reject'
    
    if (empty($collaboration_id) || empty($response)) {
        echo json_encode(['success' => false, 'message' => 'Collaboration ID and response are required']);
        return;
    }
    
    if (!in_array($response, ['accept', 'reject'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid response']);
        return;
    }
    
    // Get collaboration details
    $collab_query = "SELECT pc.*, p.title as project_title, u.full_name as requester_name 
                     FROM project_collaborators pc 
                     JOIN projects p ON pc.project_id = p.id 
                     JOIN users u ON pc.user_id = u.id 
                     WHERE pc.id = :collaboration_id AND p.created_by = :user_id AND pc.status = 'pending'";
    $collab_stmt = $db->prepare($collab_query);
    $collab_stmt->bindParam(':collaboration_id', $collaboration_id);
    $collab_stmt->bindParam(':user_id', $user_id);
    $collab_stmt->execute();
    $collaboration = $collab_stmt->fetch();
    
    if (!$collaboration) {
        echo json_encode(['success' => false, 'message' => 'Collaboration request not found']);
        return;
    }
    
    // Update collaboration status
    $status = $response === 'accept' ? 'accepted' : 'rejected';
    $update_query = "UPDATE project_collaborators SET status = :status, responded_at = CURRENT_TIMESTAMP WHERE id = :collaboration_id";
    $update_stmt = $db->prepare($update_query);
    $update_stmt->bindParam(':status', $status);
    $update_stmt->bindParam(':collaboration_id', $collaboration_id);
    
    if ($update_stmt->execute()) {
        // Create notification for requester
        $notification_query = "INSERT INTO notifications (user_id, type, title, message, related_id) 
                              VALUES (:requester_id, 'collaboration_response', 'Collaboration Request Response', 
                                     :message, :project_id)";
        $notification_stmt = $db->prepare($notification_query);
        $message = $response === 'accept' 
            ? 'Your collaboration request for "' . $collaboration['project_title'] . '" has been accepted!'
            : 'Your collaboration request for "' . $collaboration['project_title'] . '" has been rejected.';
        $notification_stmt->bindParam(':requester_id', $collaboration['user_id']);
        $notification_stmt->bindParam(':message', $message);
        $notification_stmt->bindParam(':project_id', $collaboration['project_id']);
        $notification_stmt->execute();
        
        echo json_encode(['success' => true, 'message' => 'Collaboration request ' . $response . 'ed successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to respond to collaboration request']);
    }
}

/**
 * Get collaboration requests for user's projects
 */
function getCollaborationRequests($db, $user_id) {
    $query = "SELECT pc.*, p.title as project_title, u.full_name as requester_name, u.username as requester_username
              FROM project_collaborators pc 
              JOIN projects p ON pc.project_id = p.id 
              JOIN users u ON pc.user_id = u.id 
              WHERE p.created_by = :user_id AND pc.status = 'pending'
              ORDER BY pc.requested_at DESC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $requests = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'requests' => $requests]);
}

/**
 * Get sent collaboration requests by user
 */
function getSentCollaborationRequests($db, $user_id) {
    $query = "SELECT pc.*, p.title as project_title, u.full_name as creator_name
              FROM project_collaborators pc 
              JOIN projects p ON pc.project_id = p.id 
              JOIN users u ON p.created_by = u.id 
              WHERE pc.user_id = :user_id
              ORDER BY pc.requested_at DESC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $requests = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'requests' => $requests]);
}

/**
 * Cancel collaboration request
 */
function cancelCollaborationRequest($db, $user_id) {
    $collaboration_id = $_POST['collaboration_id'] ?? '';
    
    if (empty($collaboration_id)) {
        echo json_encode(['success' => false, 'message' => 'Collaboration ID required']);
        return;
    }
    
    // Verify ownership of request
    $check_query = "SELECT id FROM project_collaborators WHERE id = :collaboration_id AND user_id = :user_id AND status = 'pending'";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':collaboration_id', $collaboration_id);
    $check_stmt->bindParam(':user_id', $user_id);
    $check_stmt->execute();
    
    if (!$check_stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Collaboration request not found']);
        return;
    }
    
    // Delete collaboration request
    $query = "DELETE FROM project_collaborators WHERE id = :collaboration_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':collaboration_id', $collaboration_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Collaboration request cancelled successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to cancel collaboration request']);
    }
}
?>
