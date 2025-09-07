<?php
/**
 * Projects API
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
        case 'upvote':
            handleVote($db, $user_id, 'upvote');
            break;
            
        case 'downvote':
            handleVote($db, $user_id, 'downvote');
            break;
            
        case 'star':
            handleStar($db, $user_id);
            break;
            
        case 'unstar':
            handleUnstar($db, $user_id);
            break;
            
        case 'get_stats':
            getProjectStats($db, $user_id);
            break;
            
        case 'delete':
            handleDeleteProject($db, $user_id);
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
 * Handle voting (upvote/downvote)
 */
function handleVote($db, $user_id, $vote_type) {
    $project_id = $_POST['project_id'] ?? '';
    
    if (empty($project_id)) {
        echo json_encode(['success' => false, 'message' => 'Project ID required']);
        return;
    }
    
    // Check if user already voted
    $check_query = "SELECT vote_type FROM project_votes WHERE project_id = :project_id AND user_id = :user_id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':project_id', $project_id);
    $check_stmt->bindParam(':user_id', $user_id);
    $check_stmt->execute();
    $existing_vote = $check_stmt->fetch();
    
    if ($existing_vote) {
        if ($existing_vote['vote_type'] === $vote_type) {
            // Remove vote if same type
            $delete_query = "DELETE FROM project_votes WHERE project_id = :project_id AND user_id = :user_id";
            $delete_stmt = $db->prepare($delete_query);
            $delete_stmt->bindParam(':project_id', $project_id);
            $delete_stmt->bindParam(':user_id', $user_id);
            $delete_stmt->execute();
            
            $is_active = false;
        } else {
            // Update vote type
            $update_query = "UPDATE project_votes SET vote_type = :vote_type WHERE project_id = :project_id AND user_id = :user_id";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(':vote_type', $vote_type);
            $update_stmt->bindParam(':project_id', $project_id);
            $update_stmt->bindParam(':user_id', $user_id);
            $update_stmt->execute();
            
            $is_active = true;
        }
    } else {
        // Add new vote
        $insert_query = "INSERT INTO project_votes (project_id, user_id, vote_type) VALUES (:project_id, :user_id, :vote_type)";
        $insert_stmt = $db->prepare($insert_query);
        $insert_stmt->bindParam(':project_id', $project_id);
        $insert_stmt->bindParam(':user_id', $user_id);
        $insert_stmt->bindParam(':vote_type', $vote_type);
        $insert_stmt->execute();
        
        $is_active = true;
    }
    
    // Get updated count
    $count_query = "SELECT COUNT(*) as count FROM project_votes WHERE project_id = :project_id AND vote_type = :vote_type";
    $count_stmt = $db->prepare($count_query);
    $count_stmt->bindParam(':project_id', $project_id);
    $count_stmt->bindParam(':vote_type', $vote_type);
    $count_stmt->execute();
    $count = $count_stmt->fetch()['count'];
    
    echo json_encode([
        'success' => true, 
        'count' => $count,
        'is_active' => $is_active,
        'message' => $is_active ? 'Vote recorded' : 'Vote removed'
    ]);
}

/**
 * Handle starring
 */
function handleStar($db, $user_id) {
    $project_id = $_POST['project_id'] ?? '';
    
    if (empty($project_id)) {
        echo json_encode(['success' => false, 'message' => 'Project ID required']);
        return;
    }
    
    // Check if already starred
    $check_query = "SELECT id FROM project_stars WHERE project_id = :project_id AND user_id = :user_id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':project_id', $project_id);
    $check_stmt->bindParam(':user_id', $user_id);
    $check_stmt->execute();
    
    if ($check_stmt->fetch()) {
        // Remove star
        $delete_query = "DELETE FROM project_stars WHERE project_id = :project_id AND user_id = :user_id";
        $delete_stmt = $db->prepare($delete_query);
        $delete_stmt->bindParam(':project_id', $project_id);
        $delete_stmt->bindParam(':user_id', $user_id);
        $delete_stmt->execute();
        
        $is_active = false;
    } else {
        // Add star
        $insert_query = "INSERT INTO project_stars (project_id, user_id) VALUES (:project_id, :user_id)";
        $insert_stmt = $db->prepare($insert_query);
        $insert_stmt->bindParam(':project_id', $project_id);
        $insert_stmt->bindParam(':user_id', $user_id);
        $insert_stmt->execute();
        
        $is_active = true;
    }
    
    // Get updated count
    $count_query = "SELECT COUNT(*) as count FROM project_stars WHERE project_id = :project_id";
    $count_stmt = $db->prepare($count_query);
    $count_stmt->bindParam(':project_id', $project_id);
    $count_stmt->execute();
    $count = $count_stmt->fetch()['count'];
    
    echo json_encode([
        'success' => true, 
        'count' => $count,
        'is_active' => $is_active,
        'message' => $is_active ? 'Project starred' : 'Star removed'
    ]);
}

/**
 * Handle unstarring
 */
function handleUnstar($db, $user_id) {
    $project_id = $_POST['project_id'] ?? '';
    
    if (empty($project_id)) {
        echo json_encode(['success' => false, 'message' => 'Project ID required']);
        return;
    }
    
    $delete_query = "DELETE FROM project_stars WHERE project_id = :project_id AND user_id = :user_id";
    $delete_stmt = $db->prepare($delete_query);
    $delete_stmt->bindParam(':project_id', $project_id);
    $delete_stmt->bindParam(':user_id', $user_id);
    $delete_stmt->execute();
    
    // Get updated count
    $count_query = "SELECT COUNT(*) as count FROM project_stars WHERE project_id = :project_id";
    $count_stmt = $db->prepare($count_query);
    $count_stmt->bindParam(':project_id', $project_id);
    $count_stmt->execute();
    $count = $count_stmt->fetch()['count'];
    
    echo json_encode([
        'success' => true, 
        'count' => $count,
        'is_active' => false,
        'message' => 'Star removed'
    ]);
}

/**
 * Get project statistics
 */
function getProjectStats($db, $user_id) {
    $project_id = $_GET['project_id'] ?? '';
    
    if (empty($project_id)) {
        echo json_encode(['success' => false, 'message' => 'Project ID required']);
        return;
    }
    
    // Get vote counts
    $upvote_query = "SELECT COUNT(*) as count FROM project_votes WHERE project_id = :project_id AND vote_type = 'upvote'";
    $upvote_stmt = $db->prepare($upvote_query);
    $upvote_stmt->bindParam(':project_id', $project_id);
    $upvote_stmt->execute();
    $upvotes = $upvote_stmt->fetch()['count'];
    
    $downvote_query = "SELECT COUNT(*) as count FROM project_votes WHERE project_id = :project_id AND vote_type = 'downvote'";
    $downvote_stmt = $db->prepare($downvote_query);
    $downvote_stmt->bindParam(':project_id', $project_id);
    $downvote_stmt->execute();
    $downvotes = $downvote_stmt->fetch()['count'];
    
    // Get star count
    $star_query = "SELECT COUNT(*) as count FROM project_stars WHERE project_id = :project_id";
    $star_stmt = $db->prepare($star_query);
    $star_stmt->bindParam(':project_id', $project_id);
    $star_stmt->execute();
    $stars = $star_stmt->fetch()['count'];
    
    // Get comment count
    $comment_query = "SELECT COUNT(*) as count FROM comments WHERE project_id = :project_id";
    $comment_stmt = $db->prepare($comment_query);
    $comment_stmt->bindParam(':project_id', $project_id);
    $comment_stmt->execute();
    $comments = $comment_stmt->fetch()['count'];
    
    // Check user's interactions
    $user_upvote_query = "SELECT id FROM project_votes WHERE project_id = :project_id AND user_id = :user_id AND vote_type = 'upvote'";
    $user_upvote_stmt = $db->prepare($user_upvote_query);
    $user_upvote_stmt->bindParam(':project_id', $project_id);
    $user_upvote_stmt->bindParam(':user_id', $user_id);
    $user_upvote_stmt->execute();
    $user_upvoted = $user_upvote_stmt->fetch() ? true : false;
    
    $user_downvote_query = "SELECT id FROM project_votes WHERE project_id = :project_id AND user_id = :user_id AND vote_type = 'downvote'";
    $user_downvote_stmt = $db->prepare($user_downvote_query);
    $user_downvote_stmt->bindParam(':project_id', $project_id);
    $user_downvote_stmt->bindParam(':user_id', $user_id);
    $user_downvote_stmt->execute();
    $user_downvoted = $user_downvote_stmt->fetch() ? true : false;
    
    $user_star_query = "SELECT id FROM project_stars WHERE project_id = :project_id AND user_id = :user_id";
    $user_star_stmt = $db->prepare($user_star_query);
    $user_star_stmt->bindParam(':project_id', $project_id);
    $user_star_stmt->bindParam(':user_id', $user_id);
    $user_star_stmt->execute();
    $user_starred = $user_star_stmt->fetch() ? true : false;
    
    echo json_encode([
        'success' => true,
        'stats' => [
            'upvotes' => $upvotes,
            'downvotes' => $downvotes,
            'stars' => $stars,
            'comments' => $comments,
            'user_upvoted' => $user_upvoted,
            'user_downvoted' => $user_downvoted,
            'user_starred' => $user_starred
        ]
    ]);
}

/**
 * Handle project deletion
 */
function handleDeleteProject($db, $user_id) {
    $project_id = $_POST['project_id'] ?? '';
    
    if (empty($project_id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Project ID is required']);
        return;
    }
    
    // Verify project ownership
    $check_query = "SELECT id FROM projects WHERE id = :project_id AND created_by = :user_id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':project_id', $project_id);
    $check_stmt->bindParam(':user_id', $user_id);
    $check_stmt->execute();
    
    if (!$check_stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You can only delete your own projects']);
        return;
    }
    
    try {
        // Start transaction
        $db->beginTransaction();
        
        // Delete related data first (foreign key constraints)
        $delete_votes = "DELETE FROM project_votes WHERE project_id = :project_id";
        $stmt = $db->prepare($delete_votes);
        $stmt->bindParam(':project_id', $project_id);
        $stmt->execute();
        
        $delete_stars = "DELETE FROM project_stars WHERE project_id = :project_id";
        $stmt = $db->prepare($delete_stars);
        $stmt->bindParam(':project_id', $project_id);
        $stmt->execute();
        
        $delete_collaborators = "DELETE FROM project_collaborators WHERE project_id = :project_id";
        $stmt = $db->prepare($delete_collaborators);
        $stmt->bindParam(':project_id', $project_id);
        $stmt->execute();
        
        $delete_comments = "DELETE FROM comments WHERE project_id = :project_id";
        $stmt = $db->prepare($delete_comments);
        $stmt->bindParam(':project_id', $project_id);
        $stmt->execute();
        
        // Delete the project
        $delete_project = "DELETE FROM projects WHERE id = :project_id AND created_by = :user_id";
        $stmt = $db->prepare($delete_project);
        $stmt->bindParam(':project_id', $project_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        // Commit transaction
        $db->commit();
        
        echo json_encode(['success' => true, 'message' => 'Project deleted successfully']);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $db->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to delete project']);
    }
}
?>
