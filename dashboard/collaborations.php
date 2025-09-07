<?php
/**
 * Collaborations Dashboard
 * Project Vault - Dr. YC James Yen Government Polytechnic, Kuppam
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('/');
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// Handle collaboration actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'accept_collaboration':
            acceptCollaboration($db, $_POST['collaboration_id']);
            break;
        case 'reject_collaboration':
            rejectCollaboration($db, $_POST['collaboration_id']);
            break;
        case 'request_collaboration':
            requestCollaboration($db, $_POST['project_id'], $_POST['collaborator_username']);
            break;
    }
}

/**
 * Accept collaboration request
 */
function acceptCollaboration($db, $collaboration_id) {
    global $user_id;
    
    try {
        $query = "UPDATE project_collaborators 
                  SET status = 'accepted', responded_at = NOW() 
                  WHERE id = :collaboration_id AND user_id = :user_id AND status = 'pending'";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':collaboration_id', $collaboration_id);
        $stmt->bindParam(':user_id', $user_id);
        
        if ($stmt->execute() && $stmt->rowCount() > 0) {
            // Get collaboration details for notification
            $collab_query = "SELECT pc.*, p.title as project_title, u.full_name as requester_name 
                            FROM project_collaborators pc 
                            JOIN projects p ON pc.project_id = p.id 
                            JOIN users u ON pc.user_id = u.id 
                            WHERE pc.id = :collaboration_id";
            $collab_stmt = $db->prepare($collab_query);
            $collab_stmt->bindParam(':collaboration_id', $collaboration_id);
            $collab_stmt->execute();
            $collaboration = $collab_stmt->fetch();
            
            if ($collaboration) {
                // Create notification for requester
                $notification_query = "INSERT INTO notifications (user_id, type, title, message, related_id) 
                                      VALUES (:user_id, 'collaboration_response', 'Collaboration Accepted', 
                                             :message, :project_id)";
                $notification_stmt = $db->prepare($notification_query);
                $message = "Your collaboration request for '{$collaboration['project_title']}' has been accepted!";
                $notification_stmt->bindParam(':user_id', $collaboration['user_id']);
                $notification_stmt->bindParam(':message', $message);
                $notification_stmt->bindParam(':project_id', $collaboration['project_id']);
                $notification_stmt->execute();
            }
            
            $_SESSION['success_message'] = 'Collaboration request accepted successfully!';
        } else {
            $_SESSION['error_message'] = 'Failed to accept collaboration request.';
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = 'An error occurred while accepting the request.';
    }
    
    redirect('collaborations.php');
}

/**
 * Reject collaboration request
 */
function rejectCollaboration($db, $collaboration_id) {
    global $user_id;
    
    try {
        $query = "UPDATE project_collaborators 
                  SET status = 'rejected', responded_at = NOW() 
                  WHERE id = :collaboration_id AND user_id = :user_id AND status = 'pending'";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':collaboration_id', $collaboration_id);
        $stmt->bindParam(':user_id', $user_id);
        
        if ($stmt->execute() && $stmt->rowCount() > 0) {
            // Get collaboration details for notification
            $collab_query = "SELECT pc.*, p.title as project_title, u.full_name as requester_name 
                            FROM project_collaborators pc 
                            JOIN projects p ON pc.project_id = p.id 
                            JOIN users u ON pc.user_id = u.id 
                            WHERE pc.id = :collaboration_id";
            $collab_stmt = $db->prepare($collab_query);
            $collab_stmt->bindParam(':collaboration_id', $collaboration_id);
            $collab_stmt->execute();
            $collaboration = $collab_stmt->fetch();
            
            if ($collaboration) {
                // Create notification for requester
                $notification_query = "INSERT INTO notifications (user_id, type, title, message, related_id) 
                                      VALUES (:user_id, 'collaboration_response', 'Collaboration Rejected', 
                                             :message, :project_id)";
                $notification_stmt = $db->prepare($notification_query);
                $message = "Your collaboration request for '{$collaboration['project_title']}' has been rejected.";
                $notification_stmt->bindParam(':user_id', $collaboration['user_id']);
                $notification_stmt->bindParam(':message', $message);
                $notification_stmt->bindParam(':project_id', $collaboration['project_id']);
                $notification_stmt->execute();
            }
            
            $_SESSION['success_message'] = 'Collaboration request rejected.';
        } else {
            $_SESSION['error_message'] = 'Failed to reject collaboration request.';
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = 'An error occurred while rejecting the request.';
    }
    
    redirect('collaborations.php');
}

/**
 * Request collaboration
 */
function requestCollaboration($db, $project_id, $collaborator_username) {
    global $user_id;
    
    try {
        // Get collaborator user ID
        $user_query = "SELECT id FROM users WHERE username = :username AND status = 'active'";
        $user_stmt = $db->prepare($user_query);
        $user_stmt->bindParam(':username', $collaborator_username);
        $user_stmt->execute();
        $collaborator = $user_stmt->fetch();
        
        if (!$collaborator) {
            $_SESSION['error_message'] = 'User not found.';
            redirect('collaborations.php');
            return;
        }
        
        $collaborator_id = $collaborator['id'];
        
        // Check if collaboration already exists
        $check_query = "SELECT id FROM project_collaborators 
                        WHERE project_id = :project_id AND user_id = :collaborator_id";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':project_id', $project_id);
        $check_stmt->bindParam(':collaborator_id', $collaborator_id);
        $check_stmt->execute();
        
        if ($check_stmt->fetch()) {
            $_SESSION['error_message'] = 'Collaboration request already exists.';
            redirect('collaborations.php');
            return;
        }
        
        // Create collaboration request
        $insert_query = "INSERT INTO project_collaborators (project_id, user_id, status) 
                         VALUES (:project_id, :collaborator_id, 'pending')";
        $insert_stmt = $db->prepare($insert_query);
        $insert_stmt->bindParam(':project_id', $project_id);
        $insert_stmt->bindParam(':collaborator_id', $collaborator_id);
        
        if ($insert_stmt->execute()) {
            // Create notification for collaborator
            $project_query = "SELECT title FROM projects WHERE id = :project_id";
            $project_stmt = $db->prepare($project_query);
            $project_stmt->bindParam(':project_id', $project_id);
            $project_stmt->execute();
            $project = $project_stmt->fetch();
            
            if ($project) {
                $notification_query = "INSERT INTO notifications (user_id, type, title, message, related_id) 
                                      VALUES (:user_id, 'collaboration_request', 'Collaboration Request', 
                                             :message, :project_id)";
                $notification_stmt = $db->prepare($notification_query);
                $message = "You have been invited to collaborate on '{$project['title']}' by " . $_SESSION['full_name'];
                $notification_stmt->bindParam(':user_id', $collaborator_id);
                $notification_stmt->bindParam(':message', $message);
                $notification_stmt->bindParam(':project_id', $project_id);
                $notification_stmt->execute();
            }
            
            $_SESSION['success_message'] = 'Collaboration request sent successfully!';
        } else {
            $_SESSION['error_message'] = 'Failed to send collaboration request.';
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = 'An error occurred while sending the request.';
    }
    
    redirect('collaborations.php');
}

// Get collaboration data
$pending_requests = [];
$my_requests = [];
$active_collaborations = [];

try {
    // Get pending collaboration requests for user's projects
    $pending_query = "SELECT pc.*, p.title as project_title, u.full_name as requester_name, u.username as requester_username
                      FROM project_collaborators pc 
                      JOIN projects p ON pc.project_id = p.id 
                      JOIN users u ON pc.user_id = u.id 
                      WHERE p.created_by = :user_id AND pc.status = 'pending'
                      ORDER BY pc.requested_at DESC";
    $pending_stmt = $db->prepare($pending_query);
    $pending_stmt->bindParam(':user_id', $user_id);
    $pending_stmt->execute();
    $pending_requests = $pending_stmt->fetchAll();
    
    // Get user's collaboration requests
    $my_requests_query = "SELECT pc.*, p.title as project_title, u.full_name as project_owner
                          FROM project_collaborators pc 
                          JOIN projects p ON pc.project_id = p.id 
                          JOIN users u ON p.created_by = u.id 
                          WHERE pc.user_id = :user_id
                          ORDER BY pc.requested_at DESC";
    $my_requests_stmt = $db->prepare($my_requests_query);
    $my_requests_stmt->bindParam(':user_id', $user_id);
    $my_requests_stmt->execute();
    $my_requests = $my_requests_stmt->fetchAll();
    
    // Get active collaborations
    $active_query = "SELECT pc.*, p.title as project_title, u.full_name as collaborator_name, u.username as collaborator_username
                     FROM project_collaborators pc 
                     JOIN projects p ON pc.project_id = p.id 
                     JOIN users u ON pc.user_id = u.id 
                     WHERE (p.created_by = :user_id OR pc.user_id = :user_id) AND pc.status = 'accepted'
                     ORDER BY pc.responded_at DESC";
    $active_stmt = $db->prepare($active_query);
    $active_stmt->bindParam(':user_id', $user_id);
    $active_stmt->execute();
    $active_collaborations = $active_stmt->fetchAll();
    
} catch (Exception $e) {
    $error_message = 'Error loading collaboration data.';
}

// Get user's projects for collaboration requests
$my_projects = [];
try {
    $projects_query = "SELECT id, title FROM projects WHERE created_by = :user_id AND status = 'active' ORDER BY title";
    $projects_stmt = $db->prepare($projects_query);
    $projects_stmt->bindParam(':user_id', $user_id);
    $projects_stmt->execute();
    $my_projects = $projects_stmt->fetchAll();
} catch (Exception $e) {
    // Handle error silently
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collaborations - <?php echo APP_NAME; ?></title>
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
                        <p>Collaborations</p>
                    </div>
                </div>
                
                <nav class="nav">
                    <ul class="nav-links">
                        <li><a href="../index.php">Home</a></li>
                        <li><a href="../projects/">Projects</a></li>
                        <li><a href="../projects/">Projects</a></li>
                        <li><a href="index.php">Dashboard</a></li>
                        <li><a href="collaborations.php" class="active">Collaborations</a></li>
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
                                <a href="/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
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
                <h1>Collaborations</h1>
                <p>Manage your project collaborations and team members</p>
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
            
            <div class="row">
                <div class="col-8">
                    <!-- Pending Requests -->
                    <?php if (!empty($pending_requests)): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-clock"></i> Pending Requests</h3>
                            <p>Collaboration requests for your projects</p>
                        </div>
                        <div class="card-body">
                            <?php foreach ($pending_requests as $request): ?>
                            <div class="collaboration-item">
                                <div class="collaboration-info">
                                    <h4><?php echo htmlspecialchars($request['project_title']); ?></h4>
                                    <p>
                                        <i class="fas fa-user"></i>
                                        <?php echo htmlspecialchars($request['requester_name']); ?> (@<?php echo htmlspecialchars($request['requester_username']); ?>)
                                    </p>
                                    <p>
                                        <i class="fas fa-calendar"></i>
                                        Requested on <?php echo date('M j, Y g:i A', strtotime($request['requested_at'])); ?>
                                    </p>
                                </div>
                                <div class="collaboration-actions">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="accept_collaboration">
                                        <input type="hidden" name="collaboration_id" value="<?php echo $request['id']; ?>">
                                        <button type="submit" class="btn btn-success btn-sm">
                                            <i class="fas fa-check"></i>
                                            Accept
                                        </button>
                                    </form>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="reject_collaboration">
                                        <input type="hidden" name="collaboration_id" value="<?php echo $request['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="fas fa-times"></i>
                                            Reject
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- My Requests -->
                    <?php if (!empty($my_requests)): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-paper-plane"></i> My Requests</h3>
                            <p>Collaboration requests you've sent</p>
                        </div>
                        <div class="card-body">
                            <?php foreach ($my_requests as $request): ?>
                            <div class="collaboration-item">
                                <div class="collaboration-info">
                                    <h4><?php echo htmlspecialchars($request['project_title']); ?></h4>
                                    <p>
                                        <i class="fas fa-user"></i>
                                        Project by <?php echo htmlspecialchars($request['project_owner']); ?>
                                    </p>
                                    <p>
                                        <i class="fas fa-calendar"></i>
                                        Requested on <?php echo date('M j, Y g:i A', strtotime($request['requested_at'])); ?>
                                    </p>
                                    <span class="status-badge status-<?php echo $request['status']; ?>">
                                        <?php echo ucfirst($request['status']); ?>
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Active Collaborations -->
                    <?php if (!empty($active_collaborations)): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-handshake"></i> Active Collaborations</h3>
                            <p>Projects you're currently collaborating on</p>
                        </div>
                        <div class="card-body">
                            <?php foreach ($active_collaborations as $collaboration): ?>
                            <div class="collaboration-item">
                                <div class="collaboration-info">
                                    <h4>
                                        <a href="../project.php?id=<?php echo $collaboration['project_id']; ?>">
                                            <?php echo htmlspecialchars($collaboration['project_title']); ?>
                                        </a>
                                    </h4>
                                    <p>
                                        <i class="fas fa-user"></i>
                                        <?php echo htmlspecialchars($collaboration['collaborator_name']); ?> (@<?php echo htmlspecialchars($collaboration['collaborator_username']); ?>)
                                    </p>
                                    <p>
                                        <i class="fas fa-calendar"></i>
                                        Started on <?php echo date('M j, Y', strtotime($collaboration['responded_at'])); ?>
                                    </p>
                                </div>
                                <div class="collaboration-actions">
                                    <a href="../project.php?id=<?php echo $collaboration['project_id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-eye"></i>
                                        View Project
                                    </a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Empty State -->
                    <?php if (empty($pending_requests) && empty($my_requests) && empty($active_collaborations)): ?>
                    <div class="card">
                        <div class="card-body">
                            <div class="empty-state">
                                <i class="fas fa-handshake"></i>
                                <h3>No Collaborations Yet</h3>
                                <p>Start collaborating on projects to see them here.</p>
                                <a href="../projects/" class="btn btn-primary">
                                    <i class="fas fa-search"></i>
                                    Explore Projects
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="col-4">
                    <!-- Request Collaboration -->
                    <?php if (!empty($my_projects)): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-user-plus"></i> Request Collaboration</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST" class="collaboration-form">
                                <input type="hidden" name="action" value="request_collaboration">
                                
                                <div class="form-group">
                                    <label for="project_id">Select Project</label>
                                    <select id="project_id" name="project_id" required>
                                        <option value="">Choose a project</option>
                                        <?php foreach ($my_projects as $project): ?>
                                        <option value="<?php echo $project['id']; ?>">
                                            <?php echo htmlspecialchars($project['title']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="collaborator_username">Username</label>
                                    <input type="text" id="collaborator_username" name="collaborator_username" 
                                           placeholder="Enter username" required>
                                    <small class="form-help">Enter the username of the person you want to collaborate with</small>
                                </div>
                                
                                <button type="submit" class="btn btn-primary btn-full">
                                    <i class="fas fa-paper-plane"></i>
                                    Send Request
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Collaboration Tips -->
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-lightbulb"></i> Collaboration Tips</h3>
                        </div>
                        <div class="card-body">
                            <div class="tips">
                                <h4>How to Collaborate</h4>
                                <ul>
                                    <li>Send collaboration requests to other students</li>
                                    <li>Accept or reject incoming requests</li>
                                    <li>Work together on project development</li>
                                    <li>Share knowledge and skills</li>
                                </ul>
                                
                                <h4>Best Practices</h4>
                                <ul>
                                    <li>Communicate clearly about project goals</li>
                                    <li>Define roles and responsibilities</li>
                                    <li>Use version control for code sharing</li>
                                    <li>Document your contributions</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="../assets/js/main.js"></script>
</body>
</html>
