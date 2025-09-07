<?php
/**
 * Comments API
 * Project Vault - Dr. YC James Yen Government Polytechnic, Kuppam
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Set JSON header
header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get':
            getComments($db);
            break;
            
        case 'add':
            addComment($db);
            break;
            
        case 'edit':
            editComment($db);
            break;
            
        case 'delete':
            deleteComment($db);
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
 * Get comments for a project
 */
function getComments($db) {
    $project_id = $_GET['project_id'] ?? '';
    
    if (empty($project_id)) {
        echo json_encode(['success' => false, 'message' => 'Project ID required']);
        return;
    }
    
    $query = "SELECT c.*, u.full_name as author_name, u.username as author_username
              FROM comments c 
              JOIN users u ON c.user_id = u.id 
              WHERE c.project_id = :project_id 
              ORDER BY c.created_at ASC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':project_id', $project_id);
    $stmt->execute();
    $comments = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'comments' => $comments]);
}

/**
 * Add a new comment
 */
function addComment($db) {
    // Check if user is logged in
    if (!is_logged_in()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    $project_id = $_POST['project_id'] ?? '';
    $content = sanitize_input($_POST['content'] ?? '');
    $parent_id = $_POST['parent_id'] ?? null;
    $user_id = $_SESSION['user_id'];
    
    if (empty($project_id) || empty($content)) {
        echo json_encode(['success' => false, 'message' => 'Project ID and content are required']);
        return;
    }
    
    // Verify project exists
    $project_query = "SELECT id FROM projects WHERE id = :project_id AND status = 'active'";
    $project_stmt = $db->prepare($project_query);
    $project_stmt->bindParam(':project_id', $project_id);
    $project_stmt->execute();
    
    if (!$project_stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Project not found']);
        return;
    }
    
    // Insert comment
    $query = "INSERT INTO comments (project_id, user_id, parent_id, content) 
              VALUES (:project_id, :user_id, :parent_id, :content)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':project_id', $project_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':parent_id', $parent_id);
    $stmt->bindParam(':content', $content);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Comment added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add comment']);
    }
}

/**
 * Edit a comment
 */
function editComment($db) {
    // Check if user is logged in
    if (!is_logged_in()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    $comment_id = $_POST['comment_id'] ?? '';
    $content = sanitize_input($_POST['content'] ?? '');
    $user_id = $_SESSION['user_id'];
    
    if (empty($comment_id) || empty($content)) {
        echo json_encode(['success' => false, 'message' => 'Comment ID and content are required']);
        return;
    }
    
    // Verify comment ownership
    $check_query = "SELECT id FROM comments WHERE id = :comment_id AND user_id = :user_id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':comment_id', $comment_id);
    $check_stmt->bindParam(':user_id', $user_id);
    $check_stmt->execute();
    
    if (!$check_stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Comment not found or access denied']);
        return;
    }
    
    // Update comment
    $query = "UPDATE comments SET content = :content, updated_at = CURRENT_TIMESTAMP WHERE id = :comment_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':content', $content);
    $stmt->bindParam(':comment_id', $comment_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Comment updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update comment']);
    }
}

/**
 * Delete a comment
 */
function deleteComment($db) {
    // Check if user is logged in
    if (!is_logged_in()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    
    $comment_id = $_POST['comment_id'] ?? '';
    $user_id = $_SESSION['user_id'];
    
    if (empty($comment_id)) {
        echo json_encode(['success' => false, 'message' => 'Comment ID required']);
        return;
    }
    
    // Verify comment ownership
    $check_query = "SELECT id FROM comments WHERE id = :comment_id AND user_id = :user_id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':comment_id', $comment_id);
    $check_stmt->bindParam(':user_id', $user_id);
    $check_stmt->execute();
    
    if (!$check_stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Comment not found or access denied']);
        return;
    }
    
    // Delete comment (this will also delete replies due to foreign key constraint)
    $query = "DELETE FROM comments WHERE id = :comment_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':comment_id', $comment_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Comment deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete comment']);
    }
}
?>
