<?php
/**
 * User Profile Page
 * Project Vault - Dr. YC James Yen Government Polytechnic, Kuppam
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Require user to be logged in
require_login();

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// Get user information
$user = null;
try {
    $query = "SELECT * FROM users WHERE id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user = $stmt->fetch();
} catch (Exception $e) {
    $error_message = 'Error loading user information.';
}

// Get user's projects
$user_projects = [];
try {
    $query = "SELECT p.*, 
                     (SELECT COUNT(*) FROM project_votes pv WHERE pv.project_id = p.id AND pv.vote_type = 'upvote') as upvote_count,
                     (SELECT COUNT(*) FROM project_votes pv WHERE pv.project_id = p.id AND pv.vote_type = 'downvote') as downvote_count,
                     (SELECT COUNT(*) FROM project_stars ps WHERE ps.project_id = p.id) as star_count,
                     (SELECT COUNT(*) FROM comments c WHERE c.project_id = p.id) as comment_count
              FROM projects p 
              WHERE p.created_by = :user_id AND p.status = 'active'
              ORDER BY p.created_at DESC
              LIMIT 6";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user_projects = $stmt->fetchAll();
} catch (Exception $e) {
    // Handle error silently
}

// Get user's collaborations
$user_collaborations = [];
try {
    $query = "SELECT p.*, 
                     (SELECT COUNT(*) FROM project_votes pv WHERE pv.project_id = p.id AND pv.vote_type = 'upvote') as upvote_count,
                     (SELECT COUNT(*) FROM project_votes pv WHERE pv.project_id = p.id AND pv.vote_type = 'downvote') as downvote_count,
                     (SELECT COUNT(*) FROM project_stars ps WHERE ps.project_id = p.id) as star_count,
                     (SELECT COUNT(*) FROM comments c WHERE c.project_id = p.id) as comment_count
              FROM projects p 
              JOIN project_collaborators pc ON p.id = pc.project_id
              WHERE pc.user_id = :user_id AND pc.status = 'accepted' AND p.status = 'active'
              ORDER BY p.created_at DESC
              LIMIT 6";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user_collaborations = $stmt->fetchAll();
} catch (Exception $e) {
    // Handle error silently
}

// Get user's starred projects
$starred_projects = [];
try {
    $query = "SELECT p.*, 
                     (SELECT COUNT(*) FROM project_votes pv WHERE pv.project_id = p.id AND pv.vote_type = 'upvote') as upvote_count,
                     (SELECT COUNT(*) FROM project_votes pv WHERE pv.project_id = p.id AND pv.vote_type = 'downvote') as downvote_count,
                     (SELECT COUNT(*) FROM project_stars ps WHERE ps.project_id = p.id) as star_count,
                     (SELECT COUNT(*) FROM comments c WHERE c.project_id = p.id) as comment_count
              FROM projects p 
              JOIN project_stars ps ON p.id = ps.project_id
              WHERE ps.user_id = :user_id AND p.status = 'active'
              ORDER BY ps.created_at DESC
              LIMIT 6";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $starred_projects = $stmt->fetchAll();
} catch (Exception $e) {
    // Handle error silently
}

// Get project statistics
$stats = [
    'total_projects' => count($user_projects),
    'total_collaborations' => count($user_collaborations),
    'total_starred' => count($starred_projects),
    'total_upvotes_received' => 0,
    'total_stars_received' => 0
];

foreach ($user_projects as $project) {
    $stats['total_upvotes_received'] += $project['upvote_count'];
    $stats['total_stars_received'] += $project['star_count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="dashboard-body profile-page">
    <!-- Header -->
    <header class="dashboard-header">
        <div class="container">
            <div class="header-content">
                <div class="logo-section">
                    <img src="../assets/images/polytechnic_logo.jpg" alt="College Logo" class="logo">
                    <div class="logo-text">
                        <h1><?php echo APP_NAME; ?></h1>
                        <p>My Profile</p>
                    </div>
                </div>
                
                <nav class="nav">
                    <ul class="nav-links">
                        <li><a href="../index.php">Home</a></li>
                        <li><a href="../projects/">Projects</a></li>
                        <li><a href="index.php">Dashboard</a></li>
                        <li><a href="profile.php" class="active">Profile</a></li>
                    </ul>
                    
                    <div class="user-menu">
                        <div class="user-dropdown">
                            <button class="user-button">
                                <i class="fas fa-user-circle"></i>
                                <?php echo htmlspecialchars($user['full_name']); ?>
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
            <!-- Profile Header -->
            <div class="profile-header">
                <div class="profile-avatar">
                    <i class="fas fa-user-circle"></i>
                </div>
                <div class="profile-info">
                    <h1><?php echo htmlspecialchars($user['full_name']); ?></h1>
                    <p class="profile-username">@<?php echo htmlspecialchars($user['username']); ?></p>
                    <?php if ($user['role'] === 'student' && !empty($user['roll_number'])): ?>
                    <p class="profile-roll">Roll Number: <?php echo htmlspecialchars($user['roll_number']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($user['branch'])): ?>
                    <p class="profile-branch">
                        <i class="fas fa-graduation-cap"></i>
                        <?php echo $user['branch']; ?>
                    </p>
                    <?php endif; ?>
                    <p class="profile-role">
                        <span class="role-badge role-<?php echo $user['role']; ?>">
                            <?php echo ucfirst($user['role']); ?>
                        </span>
                    </p>
                </div>
                <div class="profile-stats">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $stats['total_projects']; ?></div>
                        <div class="stat-label">Projects</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $stats['total_collaborations']; ?></div>
                        <div class="stat-label">Collaborations</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $stats['total_upvotes_received']; ?></div>
                        <div class="stat-label">Upvotes Received</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $stats['total_stars_received']; ?></div>
                        <div class="stat-label">Stars Received</div>
                    </div>
                </div>
            </div>
            
            <!-- Profile Navigation Tabs -->
            <div class="profile-tabs">
                <button class="tab-button active" onclick="showTab('projects')">
                    <i class="fas fa-folder"></i>
                    My Projects (<?php echo count($user_projects); ?>)
                </button>
                <button class="tab-button" onclick="showTab('collaborations')">
                    <i class="fas fa-handshake"></i>
                    Collaborations (<?php echo count($user_collaborations); ?>)
                </button>
                <button class="tab-button" onclick="showTab('starred')">
                    <i class="fas fa-star"></i>
                    Starred Projects (<?php echo count($starred_projects); ?>)
                </button>
            </div>
            
            <!-- My Projects Tab -->
            <div id="projects-tab" class="tab-content active">
                <div class="tab-header">
                    <h2>My Projects</h2>
                    <a href="projects/add.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Add New Project
                    </a>
                </div>
                
                <?php if (!empty($user_projects)): ?>
                <div class="project-grid">
                    <?php foreach ($user_projects as $project): ?>
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
                                    View
                                </a>
                                <a href="projects/edit.php?id=<?php echo $project['id']; ?>" class="btn btn-sm btn-secondary">
                                    Edit
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="text-center">
                    <a href="projects/index.php" class="btn btn-primary">
                        <i class="fas fa-folder"></i>
                        View All My Projects
                    </a>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-folder-open"></i>
                    </div>
                    <h3>No Projects Yet</h3>
                    <p>You haven't created any projects yet. Start by adding your first project!</p>
                    <a href="projects/add.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Create Your First Project
                    </a>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Collaborations Tab -->
            <div id="collaborations-tab" class="tab-content">
                <div class="tab-header">
                    <h2>My Collaborations</h2>
                    <a href="collaborations.php" class="btn btn-primary">
                        <i class="fas fa-handshake"></i>
                        Manage Collaborations
                    </a>
                </div>
                
                <?php if (!empty($user_collaborations)): ?>
                <div class="project-grid">
                    <?php foreach ($user_collaborations as $project): ?>
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
                                    View
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
                    <p>You haven't collaborated on any projects yet.</p>
                    <a href="../projects/" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                        Explore Projects
                    </a>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Starred Projects Tab -->
            <div id="starred-tab" class="tab-content">
                <div class="tab-header">
                    <h2>Starred Projects</h2>
                    <a href="../projects/" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                        Explore More Projects
                    </a>
                </div>
                
                <?php if (!empty($starred_projects)): ?>
                <div class="project-grid">
                    <?php foreach ($starred_projects as $project): ?>
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
                                <span class="tag starred-tag">Starred</span>
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
                                    View
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <h3>No Starred Projects</h3>
                    <p>You haven't starred any projects yet. Start exploring and star projects you find interesting!</p>
                    <a href="../projects/" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                        Explore Projects
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

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
