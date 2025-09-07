<?php
/**
 * Branches API
 * Project Vault - Dr. YC James Yen Government Polytechnic, Kuppam
 */

require_once '../config/config.php';

// Set JSON header
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$branch = $_GET['branch'] ?? '';

try {
    switch ($action) {
        case 'get_types':
            getProjectTypes($branch);
            break;
            
        case 'get_all':
            getAllBranches();
            break;
            
        default:
            // Default: get project types for specific branch
            getProjectTypes($branch);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}

/**
 * Get project types for a specific branch
 */
function getProjectTypes($branch) {
    global $branches;
    
    if (empty($branch)) {
        echo json_encode(['success' => false, 'message' => 'Branch required']);
        return;
    }
    
    if (!isset($branches[$branch])) {
        echo json_encode(['success' => false, 'message' => 'Invalid branch']);
        return;
    }
    
    echo json_encode([
        'success' => true,
        'branch' => $branch,
        'types' => $branches[$branch]['types']
    ]);
}

/**
 * Get all branches
 */
function getAllBranches() {
    global $branches;
    
    echo json_encode([
        'success' => true,
        'branches' => $branches
    ]);
}
?>
