<?php
/**
 * User Projects Page
 * Project Vault - Dr. YC James Yen Government Polytechnic, Kuppam
 */

require_once '../config/config.php';
require_once '../config/database.php';

$user_id = $_GET['id'] ?? '';
$username = $_GET['username'] ?? '';

if (empty($user_id) && empty($username)) {
    redirect('index.php');
}

// Get user information
$user = null;
try {
    if (!empty($user_id)) {
        $query = "SELECT * FROM users WHERE id = :user_id AND status = 'active'";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
    } else {
        $query = "SELECT * FROM users WHERE username = :username AND status = 'active'";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $username);
    }
    $stmt->execute();
    $user = $stmt->fetch();
    
    if (!$user) {
        redirect('index.php');
    }
} catch (Exception $e) {
    redirect('index.php');
}

// Get user's projects
$projects = [];
try {
    $query = "SELECT p.*, 
                     (SELECT COUNT(*) FROM project_votes pv WHERE pv.project_id = p.id AND pv.vote_type = 'upvote') as upvote_count,
                     (SELECT COUNT(*) FROM project_votes pv WHERE pv.project_id = p.id AND pv.vote_type = 'downvote') as downvote_count,
                     (SELECT COUNT(*) FROM project_stars ps WHERE ps.project_id = p.id) as star_count,
                     (SELECT COUNT(*) FROM comments c WHERE c.project_id = p.id) as comment_count
              FROM projects p 
              WHERE p.created_by = :user_id AND p.status = 'active'
              ORDER BY p.created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user['id']);
    $stmt->execute();
    $projects = $stmt->fetchAll();
} catch (Exception $e) {
    // Handle error silently
}

// Get user's collaborations
$collaborations = [];
try {
    $query = "SELECT p.*, 
                     (SELECT COUNT(*) FROM project_votes pv WHERE pv.project_id = p.id AND pv.vote_type = 'upvote') as upvote_count,
                     (SELECT COUNT(*) FROM project_votes pv WHERE pv.project_id = p.id AND pv.vote_type = 'downvote') as downvote_count,
                     (SELECT COUNT(*) FROM project_stars ps WHERE ps.project_id = p.id) as star_count,
                     (SELECT COUNT(*) FROM comments c WHERE c.project_id = p.id) as comment_count
              FROM projects p 
              JOIN project_collaborators pc ON p.id = pc.project_id
              WHERE pc.user_id = :user_id AND pc.status = 'accepted' AND p.status = 'active'
              ORDER BY p.created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user['id']);
    $stmt->execute();
    $collaborations = $stmt->fetchAll();
} catch (Exception $e) {
    // Handle error silently
}

// Get project statistics
$stats = [
    'total_projects' => count($projects),
    'total_collaborations' => count($collaborations),
    'total_upvotes' => 0,
    'total_stars' => 0
];

foreach ($projects as $project) {
    $stats['total_upvotes'] += $project['upvote_count'];
    $stats['total_stars'] += $project['star_count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user['full_name']); ?>'s Projects - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo-section">
                    <img src="../assets/images/polytechnic_logo.jpg" alt="College Logo" class="logo">
                    <div class="logo-text">
                        <h1><?php echo APP_NAME; ?></h1>
                        <p>User Projects</p>
                    </div>
                </div>
                
                <nav class="nav">
                    <ul class="nav-links">
                        <li><a href="../index.php">Home</a></li>
                        <li><a href="index.php">Projects</a></li>
                        <?php if (is_logged_in()): ?>
                        <li><a href="../dashboard/">Dashboard</a></li>
                        <?php endif; ?>
                    </ul>
                    
                    <div class="auth-buttons">
                        <?php if (is_logged_in()): ?>
                            <a href="../dashboard/" class="btn btn-primary">
                                <i class="fas fa-tachometer-alt"></i>
                                Dashboard
                            </a>
                            <a href="/auth/logout.php" class="btn btn-secondary">
                                <i class="fas fa-sign-out-alt"></i>
                                Logout
                            </a>
                        <?php else: ?>
                            <a href="/auth/login.php" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt"></i>
                                Login
                            </a>
                            <a href="/auth/register.php" class="btn btn-secondary">
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
    <main class="explore-main">
        <div class="container">
            <!-- User Profile Header -->
            <div class="user-profile-header">
                <div class="user-avatar">
                    <i class="fas fa-user-circle"></i>
                </div>
                <div class="user-info">
                    <h1><?php echo htmlspecialchars($user['full_name']); ?></h1>
                    <p class="user-username">@<?php echo htmlspecialchars($user['username']); ?></p>
                    <?php if ($user['role'] === 'student' && !empty($user['roll_number'])): ?>
                    <p class="user-roll">Roll Number: <?php echo htmlspecialchars($user['roll_number']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($user['branch'])): ?>
                    <p class="user-branch">
                        <i class="fas fa-graduation-cap"></i>
                        <?php echo $user['branch']; ?>
                    </p>
                    <?php endif; ?>
                    <p class="user-role">
                        <span class="role-badge role-<?php echo $user['role']; ?>">
                            <?php echo ucfirst($user['role']); ?>
                        </span>
                    </p>
                </div>
                <div class="user-stats">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $stats['total_projects']; ?></div>
                        <div class="stat-label">Projects</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $stats['total_collaborations']; ?></div>
                        <div class="stat-label">Collaborations</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $stats['total_upvotes']; ?></div>
                        <div class="stat-label">Upvotes</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $stats['total_stars']; ?></div>
                        <div class="stat-label">Stars</div>
                    </div>
                </div>
            </div>
            
            <!-- Navigation Tabs -->
            <div class="user-tabs">
                <button class="tab-button active" onclick="showTab('projects')">
                    <i class="fas fa-folder"></i>
                    My Projects (<?php echo count($projects); ?>)
                </button>
                <button class="tab-button" onclick="showTab('collaborations')">
                    <i class="fas fa-handshake"></i>
                    Collaborations (<?php echo count($collaborations); ?>)
                </button>
            </div>
            
            <!-- Projects Tab -->
            <div id="projects-tab" class="tab-content active">
                <?php if (!empty($projects)): ?>
                <div class="project-grid">
                    <?php foreach ($projects as $project): ?>
                    <div class="project-card">
                        <div class="project-card-header">
                            <h3 class="project-card-title">
                                <a href="../project.php?id=<?php echo $project['id']; ?>">
                                    <?php echo htmlspecialchars($project['title']); ?>
                                </a>
                            </h3>
                            <div class="project-card-meta">
                                <span><i class="fas fa-calendar"></i> <?php echo date('M j, Y', strtotime($project['created_at'])); ?></span>
                                <span><i class="fas fa-graduation-cap"></i> <?php echo $project['branch']; ?></span>
                            </div>
                        </div>
                        
                        <div class="project-card-body">
                            <p class="project-card-description">
                                <?php echo htmlspecialchars(substr($project['short_description'], 0, 150)) . '...'; ?>
                            </p>
                            
                            <div class="project-card-tags">
                                <span class="tag branch-tag"><?php echo $project['branch']; ?></span>
                                <span class="tag type-tag"><?php echo htmlspecialchars($project['project_type']); ?></span>
                            </div>
                        </div>
                        
                        <div class="project-card-footer">
                            <div class="project-stats">
                                <span class="stat upvotes" title="Upvotes">
                                    <i class="fas fa-thumbs-up"></i>
                                    <?php echo $project['upvote_count']; ?>
                                </span>
                                <span class="stat downvotes" title="Downvotes">
                                    <i class="fas fa-thumbs-down"></i>
                                    <?php echo $project['downvote_count']; ?>
                                </span>
                                <span class="stat stars" title="Stars">
                                    <i class="fas fa-star"></i>
                                    <?php echo $project['star_count']; ?>
                                </span>
                                <span class="stat comments" title="Comments">
                                    <i class="fas fa-comments"></i>
                                    <?php echo $project['comment_count']; ?>
                                </span>
                            </div>
                            
                            <div class="project-actions">
                                <a href="../project.php?id=<?php echo $project['id']; ?>" class="btn btn-sm btn-primary">
                                    View Details
                                </a>
                                <?php if (is_logged_in() && $_SESSION['user_id'] == $user['id']): ?>
                                <a href="../dashboard/projects/edit.php?id=<?php echo $project['id']; ?>" class="btn btn-sm btn-secondary">
                                    Edit
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-folder-open"></i>
                    </div>
                    <h3>No Projects Yet</h3>
                    <p><?php echo htmlspecialchars($user['full_name']); ?> hasn't created any projects yet.</p>
                    <?php if (is_logged_in() && $_SESSION['user_id'] == $user['id']): ?>
                    <a href="../dashboard/projects/add.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Create Your First Project
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Collaborations Tab -->
            <div id="collaborations-tab" class="tab-content">
                <?php if (!empty($collaborations)): ?>
                <div class="project-grid">
                    <?php foreach ($collaborations as $project): ?>
                    <div class="project-card">
                        <div class="project-card-header">
                            <h3 class="project-card-title">
                                <a href="../project.php?id=<?php echo $project['id']; ?>">
                                    <?php echo htmlspecialchars($project['title']); ?>
                                </a>
                            </h3>
                            <div class="project-card-meta">
                                <span><i class="fas fa-calendar"></i> <?php echo date('M j, Y', strtotime($project['created_at'])); ?></span>
                                <span><i class="fas fa-graduation-cap"></i> <?php echo $project['branch']; ?></span>
                            </div>
                        </div>
                        
                        <div class="project-card-body">
                            <p class="project-card-description">
                                <?php echo htmlspecialchars(substr($project['short_description'], 0, 150)) . '...'; ?>
                            </p>
                            
                            <div class="project-card-tags">
                                <span class="tag branch-tag"><?php echo $project['branch']; ?></span>
                                <span class="tag type-tag"><?php echo htmlspecialchars($project['project_type']); ?></span>
                                <span class="tag collaboration-tag">Collaboration</span>
                            </div>
                        </div>
                        
                        <div class="project-card-footer">
                            <div class="project-stats">
                                <span class="stat upvotes" title="Upvotes">
                                    <i class="fas fa-thumbs-up"></i>
                                    <?php echo $project['upvote_count']; ?>
                                </span>
                                <span class="stat downvotes" title="Downvotes">
                                    <i class="fas fa-thumbs-down"></i>
                                    <?php echo $project['downvote_count']; ?>
                                </span>
                                <span class="stat stars" title="Stars">
                                    <i class="fas fa-star"></i>
                                    <?php echo $project['star_count']; ?>
                                </span>
                                <span class="stat comments" title="Comments">
                                    <i class="fas fa-comments"></i>
                                    <?php echo $project['comment_count']; ?>
                                </span>
                            </div>
                            
                            <div class="project-actions">
                                <a href="../project.php?id=<?php echo $project['id']; ?>" class="btn btn-sm btn-primary">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <h3>No Collaborations</h3>
                    <p><?php echo htmlspecialchars($user['full_name']); ?> hasn't collaborated on any projects yet.</p>
                </div>
                <?php endif; ?>
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
                        <li><a href="../index.php">Home</a></li>
                        <li><a href="index.php">Projects</a></li>
                        <?php if (is_logged_in()): ?>
                        <li><a href="../dashboard/">Dashboard</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="../assets/js/main.js"></script>
    <script>
        function showTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all tab buttons
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active');
            });
            
            // Show selected tab content
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Add active class to clicked button
            event.target.classList.add('active');
        }
    </script>
</body>
</html>
