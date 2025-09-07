<?php
/**
 * Project Detail Page
 * Project Vault - Dr. YC James Yen Government Polytechnic, Kuppam
 */

require_once 'config/config.php';
require_once 'config/database.php';

$project_id = $_GET['id'] ?? '';

if (empty($project_id)) {
    redirect('index.php');
}

// Get project details
$project = null;
$user_interactions = [
    'upvoted' => false,
    'downvoted' => false,
    'starred' => false
];

try {
    $query = "SELECT p.*, u.full_name as creator_name, u.branch as creator_branch, u.username as creator_username
              FROM projects p 
              JOIN users u ON p.created_by = u.id 
              WHERE p.id = :project_id AND p.status = 'active'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':project_id', $project_id);
    $stmt->execute();
    $project = $stmt->fetch();
    
    if (!$project) {
        redirect('index.php');
    }
    
    // Get user interactions if logged in
    if (is_logged_in()) {
        $user_id = $_SESSION['user_id'];
        
        // Check votes
        $vote_query = "SELECT vote_type FROM project_votes WHERE project_id = :project_id AND user_id = :user_id";
        $vote_stmt = $db->prepare($vote_query);
        $vote_stmt->bindParam(':project_id', $project_id);
        $vote_stmt->bindParam(':user_id', $user_id);
        $vote_stmt->execute();
        $vote = $vote_stmt->fetch();
        
        if ($vote) {
            $user_interactions[$vote['vote_type'] === 'upvote' ? 'upvoted' : 'downvoted'] = true;
        }
        
        // Check star
        $star_query = "SELECT id FROM project_stars WHERE project_id = :project_id AND user_id = :user_id";
        $star_stmt = $db->prepare($star_query);
        $star_stmt->bindParam(':project_id', $project_id);
        $star_stmt->bindParam(':user_id', $user_id);
        $star_stmt->execute();
        $user_interactions['starred'] = $star_stmt->fetch() ? true : false;
    }
    
    // Get project statistics
    $stats_query = "SELECT 
                        (SELECT COUNT(*) FROM project_votes WHERE project_id = :project_id AND vote_type = 'upvote') as upvotes,
                        (SELECT COUNT(*) FROM project_votes WHERE project_id = :project_id AND vote_type = 'downvote') as downvotes,
                        (SELECT COUNT(*) FROM project_stars WHERE project_id = :project_id) as stars,
                        (SELECT COUNT(*) FROM comments WHERE project_id = :project_id) as comments";
    $stats_stmt = $db->prepare($stats_query);
    $stats_stmt->bindParam(':project_id', $project_id);
    $stats_stmt->execute();
    $stats = $stats_stmt->fetch();
    
    // Get collaborators
    $collaborators_query = "SELECT u.full_name, u.username, pc.status 
                           FROM project_collaborators pc 
                           JOIN users u ON pc.user_id = u.id 
                           WHERE pc.project_id = :project_id AND pc.status = 'accepted'";
    $collaborators_stmt = $db->prepare($collaborators_query);
    $collaborators_stmt->bindParam(':project_id', $project_id);
    $collaborators_stmt->execute();
    $collaborators = $collaborators_stmt->fetchAll();
    
} catch (Exception $e) {
    redirect('index.php');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($project['title']); ?> - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/project.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo-section">
                    <img src="assets/images/polytechnic_logo.jpg" alt="College Logo" class="logo">
                    <div class="logo-text">
                        <h1><?php echo APP_NAME; ?></h1>
                        <p>Project Details</p>
                    </div>
                </div>
                
                <nav class="nav">
                    <ul class="nav-links">
                        <li><a href="index.php">Home</a></li>
                        <li><a href="projects/">Projects</a></li>
                        <?php if (is_logged_in()): ?>
                        <li><a href="dashboard/">Dashboard</a></li>
                        <?php endif; ?>
                    </ul>
                    
                    <div class="auth-buttons">
                        <?php if (is_logged_in()): ?>
                            <div class="user-menu">
                                <div class="user-dropdown">
                                    <button class="user-button">
                                        <i class="fas fa-user-circle"></i>
                                        <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                                        <i class="fas fa-chevron-down"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a href="dashboard/"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                                        <a href="dashboard/profile.php"><i class="fas fa-user"></i> My Profile</a>
                                        <a href="dashboard/settings.php"><i class="fas fa-cog"></i> Settings</a>
                                        <a href="auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <a href="/auth/login.php?redirect_to=<?php echo rawurlencode($_SERVER['REQUEST_URI'] ?? '/'); ?>" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt"></i>
                                Login
                            </a>
                            <a href="/auth/register.php?redirect_to=<?php echo rawurlencode($_SERVER['REQUEST_URI'] ?? '/'); ?>" class="btn btn-secondary">
                                <i class="fas fa-user-plus"></i>
                                Register
                            </a>
                        <?php endif; ?>
                    </div>
                </nav>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="project-main">
        <div class="container">
            <!-- Project Header -->
            <div class="project-header">
                <div class="project-title-section">
                    <h1><?php echo htmlspecialchars($project['title']); ?></h1>
                    <div class="project-meta">
                        <span class="creator">
                            <i class="fas fa-user"></i>
                            By <a href="projects/user.php?id=<?php echo $project['created_by']; ?>" class="creator-link"><?php echo htmlspecialchars($project['creator_name']); ?></a>
                        </span>
                        <span class="branch">
                            <i class="fas fa-graduation-cap"></i>
                            <?php echo $project['branch']; ?>
                        </span>
                        <span class="type">
                            <i class="fas fa-tag"></i>
                            <?php echo htmlspecialchars($project['project_type']); ?>
                        </span>
                        <span class="date">
                            <i class="fas fa-calendar"></i>
                            <?php echo date('M j, Y', strtotime($project['created_at'])); ?>
                        </span>
                    </div>
                </div>
                
                <div class="project-actions">
                    <?php if (is_logged_in()): ?>
                        <button class="action-btn upvote-btn <?php echo $user_interactions['upvoted'] ? 'active' : ''; ?>" 
                                data-project-id="<?php echo $project['id']; ?>" 
                                data-action="upvote">
                            <i class="fas fa-thumbs-up"></i>
                            <span class="count"><?php echo $stats['upvotes']; ?></span>
                        </button>
                        
                        <button class="action-btn downvote-btn <?php echo $user_interactions['downvoted'] ? 'active' : ''; ?>" 
                                data-project-id="<?php echo $project['id']; ?>" 
                                data-action="downvote">
                            <i class="fas fa-thumbs-down"></i>
                            <span class="count"><?php echo $stats['downvotes']; ?></span>
                        </button>
                        
                        <button class="action-btn star-btn <?php echo $user_interactions['starred'] ? 'active' : ''; ?>" 
                                data-project-id="<?php echo $project['id']; ?>" 
                                data-action="star">
                            <i class="fas fa-star"></i>
                            <span class="count"><?php echo $stats['stars']; ?></span>
                        </button>
                        
                        <?php if ($project['created_by'] != $_SESSION['user_id']): ?>
                        <button class="action-btn collaborate-btn" 
                                data-project-id="<?php echo $project['id']; ?>" 
                                data-action="collaborate">
                            <i class="fas fa-handshake"></i>
                            <span>Collaborate</span>
                        </button>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="action-btn disabled">
                            <i class="fas fa-thumbs-up"></i>
                            <span class="count"><?php echo $stats['upvotes']; ?></span>
                        </div>
                        <div class="action-btn disabled">
                            <i class="fas fa-thumbs-down"></i>
                            <span class="count"><?php echo $stats['downvotes']; ?></span>
                        </div>
                        <div class="action-btn disabled">
                            <i class="fas fa-star"></i>
                            <span class="count"><?php echo $stats['stars']; ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Project Content -->
            <div class="row">
                <div class="col-8">
                    <div class="project-content">
                        <!-- Short Description -->
                        <div class="project-section">
                            <h2>Overview</h2>
                            <p class="project-short-desc"><?php echo nl2br(htmlspecialchars($project['short_description'])); ?></p>
                        </div>
                        
                        <!-- Detailed Description -->
                        <div class="project-section">
                            <h2>Detailed Description</h2>
                            <div class="project-long-desc">
                                <?php echo nl2br(htmlspecialchars($project['long_description'])); ?>
                            </div>
                        </div>
                        
                        <!-- GitHub Link -->
                        <?php if (!empty($project['github_link'])): ?>
                        <div class="project-section">
                            <h2>GitHub Repository</h2>
                            <a href="<?php echo htmlspecialchars($project['github_link']); ?>" 
                               target="_blank" rel="noopener noreferrer" 
                               class="github-link">
                                <i class="fab fa-github"></i>
                                View on GitHub
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Comments Section -->
                        <div class="project-section">
                            <h2>Comments (<?php echo $stats['comments']; ?>)</h2>
                            
                            <?php if (is_logged_in()): ?>
                            <div class="comment-form">
                                <form id="commentForm">
                                    <div class="form-group">
                                        <textarea name="content" placeholder="Add a comment..." required></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-comment"></i>
                                        Post Comment
                                    </button>
                                </form>
                            </div>
                            <?php else: ?>
                            <div class="login-prompt">
                                <p>Please <a href="/auth/login.php?redirect_to=<?php echo rawurlencode($_SERVER['REQUEST_URI'] ?? '/'); ?>">login</a> to post comments.</p>
                            </div>
                            <?php endif; ?>
                            
                            <div class="comments-list" id="commentsList">
                                <!-- Comments will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-4">
                    <div class="project-sidebar">
                        <!-- Project Stats -->
                        <div class="sidebar-card">
                            <h3>Project Statistics</h3>
                            <div class="stats-grid">
                                <div class="stat-item">
                                    <i class="fas fa-thumbs-up"></i>
                                    <span><?php echo $stats['upvotes']; ?> Upvotes</span>
                                </div>
                                <div class="stat-item">
                                    <i class="fas fa-thumbs-down"></i>
                                    <span><?php echo $stats['downvotes']; ?> Downvotes</span>
                                </div>
                                <div class="stat-item">
                                    <i class="fas fa-star"></i>
                                    <span><?php echo $stats['stars']; ?> Stars</span>
                                </div>
                                <div class="stat-item">
                                    <i class="fas fa-comments"></i>
                                    <span><?php echo $stats['comments']; ?> Comments</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Collaborators -->
                        <?php if (!empty($collaborators)): ?>
                        <div class="sidebar-card">
                            <h3>Collaborators</h3>
                            <div class="collaborators-list">
                                <?php foreach ($collaborators as $collaborator): ?>
                                <div class="collaborator-item">
                                    <i class="fas fa-user"></i>
                                    <span><?php echo htmlspecialchars($collaborator['full_name']); ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Project Tags -->
                        <div class="sidebar-card">
                            <h3>Project Tags</h3>
                            <div class="project-tags">
                                <span class="tag branch-tag"><?php echo $project['branch']; ?></span>
                                <span class="tag type-tag"><?php echo htmlspecialchars($project['project_type']); ?></span>
                            </div>
                        </div>
                        
                        <!-- Actions -->
                        <?php if (is_logged_in()): ?>
                        <div class="sidebar-card">
                            <h3>Actions</h3>
                            <div class="action-buttons">
                                <?php if ($_SESSION['user_id'] == $project['created_by']): ?>
                                <a href="dashboard/projects/edit.php?id=<?php echo $project['id']; ?>" 
                                   class="btn btn-secondary btn-full">
                                    <i class="fas fa-edit"></i>
                                    Edit Project
                                </a>
                                <?php endif; ?>
                                
                                <button class="btn btn-primary btn-full" onclick="requestCollaboration(<?php echo $project['id']; ?>)">
                                    <i class="fas fa-handshake"></i>
                                    Request Collaboration
                                </button>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3><?php echo APP_NAME; ?></h3>
                    <p>Explore innovative projects from Dr. YC James Yen Government Polytechnic, Kuppam.</p>
                </div>
                
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="projects/">Projects</a></li>
                        <?php if (is_logged_in()): ?>
                        <li><a href="dashboard/">Dashboard</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
    <script src="assets/js/project.js"></script>
    <script>
        // Pass project data to JavaScript
        window.projectData = {
            id: <?php echo $project['id']; ?>,
            userInteractions: <?php echo json_encode($user_interactions); ?>,
            stats: <?php echo json_encode($stats); ?>
        };
    </script>
</body>
</html>
