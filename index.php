
<?php
/**
 * Homepage
 * Project Vault - Dr. YC James Yen Government Polytechnic, Kuppam
 */

require_once 'config/config.php';
require_once 'config/database.php';

// Get recent projects for display
$recent_projects = [];
try {
    $query = "SELECT p.*, u.full_name as creator_name, u.branch as creator_branch 
              FROM projects p 
              JOIN users u ON p.created_by = u.id 
              WHERE p.status = 'active' 
              ORDER BY p.created_at DESC 
              LIMIT 6";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $recent_projects = $stmt->fetchAll();
} catch (Exception $e) {
    // Handle error silently for homepage
}

// Get project statistics
$stats = [
    'total_projects' => 0,
    'total_users' => 0,
    'total_branches' => 4
];

try {
    $query = "SELECT COUNT(*) as count FROM projects WHERE status = 'active'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['total_projects'] = $stmt->fetch()['count'];
    
    $query = "SELECT COUNT(*) as count FROM users WHERE status = 'active'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['total_users'] = $stmt->fetch()['count'];
} catch (Exception $e) {
    // Handle error silently
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Dr. YC James Yen Government Polytechnic, Kuppam</title>
    <link rel="stylesheet" href="assets/css/style.css">
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
                        <p>Dr. YC James Yen Government Polytechnic, Kuppam</p>
                    </div>
                </div>
                
                <nav class="nav">
                    <ul class="nav-links">
                        <li><a href="#home">Home</a></li>
                        <li><a href="#about">About</a></li>
                        <li><a href="projects/">Projects</a></li>
                        <li><a href="#branches">Branches</a></li>
                        <li><a href="#contact">Contact</a></li>
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
                                        <a href="/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                                    </div>
                                </div>
                            </div>
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

    <!-- Hero Section -->
    <section id="home" class="hero">
        <div class="container">
            <div class="hero-content">
                <h1>Welcome to Project Vault</h1>
                <p class="hero-subtitle">A comprehensive platform for students and staff to showcase, collaborate, and explore innovative projects across all engineering branches.</p>
                
                <div class="hero-stats">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $stats['total_projects']; ?></div>
                        <div class="stat-label">Active Projects</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $stats['total_users']; ?></div>
                        <div class="stat-label">Registered Users</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $stats['total_branches']; ?></div>
                        <div class="stat-label">Engineering Branches</div>
                    </div>
                </div>
                
                <div class="hero-actions">
                    <a href="projects/" class="btn btn-primary btn-lg">
                        <i class="fas fa-folder"></i>
                        View All Projects
                    </a>
                    <a href="projects/" class="btn btn-secondary btn-lg">
                        <i class="fas fa-search"></i>
                        Explore Projects
                    </a>
                    <?php if (!is_logged_in()): ?>
                    <a href="/auth/register.php" class="btn btn-outline btn-lg">
                        <i class="fas fa-user-plus"></i>
                        Join Now
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="about">
        <div class="container">
            <div class="section-header">
                <h2>About Project Vault</h2>
                <p>Empowering innovation and collaboration in engineering education</p>
            </div>
            
            <div class="row">
                <div class="col-6">
                    <div class="about-content">
                        <h3>What is Project Vault?</h3>
                        <p>Project Vault is a comprehensive platform designed specifically for Dr. YC James Yen Government Polytechnic, Kuppam. It serves as a central hub where students and staff can showcase their innovative projects, collaborate with peers, and explore the diverse range of engineering solutions developed within our institution.</p>
                        
                        <h3>Key Features</h3>
                        <ul class="feature-list">
                            <li><i class="fas fa-check"></i> Project showcase and portfolio management</li>
                            <li><i class="fas fa-check"></i> Cross-branch collaboration opportunities</li>
                            <li><i class="fas fa-check"></i> Peer review and feedback system</li>
                            <li><i class="fas fa-check"></i> Advanced search and filtering</li>
                            <li><i class="fas fa-check"></i> Real-time notifications and updates</li>
                            <li><i class="fas fa-check"></i> Secure user management system</li>
                        </ul>
                    </div>
                </div>
                
                <div class="col-6">
                    <div class="about-image">
                        <div class="image-placeholder">
                            <i class="fas fa-graduation-cap"></i>
                            <p>Engineering Excellence</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Branches Section -->
    <section id="branches" class="branches">
        <div class="container">
            <div class="section-header">
                <h2>Engineering Branches</h2>
                <p>Explore projects from all our engineering disciplines</p>
            </div>
            
            <div class="branches-grid">
                <?php foreach ($branches as $code => $branch): ?>
                <div class="branch-card">
                    <div class="branch-icon">
                        <i class="fas fa-<?php echo getBranchIcon($code); ?>"></i>
                    </div>
                    <h3><?php echo $code; ?></h3>
                    <p><?php echo $branch['name']; ?></p>
                    <div class="branch-types">
                        <?php foreach (array_slice($branch['types'], 0, 3) as $type): ?>
                        <span class="type-tag"><?php echo $type; ?></span>
                        <?php endforeach; ?>
                        <?php if (count($branch['types']) > 3): ?>
                        <span class="type-tag">+<?php echo count($branch['types']) - 3; ?> more</span>
                        <?php endif; ?>
                    </div>
                    <a href="projects/?branch=<?php echo $code; ?>" class="btn btn-outline">
                        View Projects
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Recent Projects Section -->
    <section id="projects" class="recent-projects">
        <div class="container">
            <div class="section-header">
                <h2>Recent Projects</h2>
                <p>Discover the latest innovations from our students and staff</p>
            </div>
            
            <?php if (!empty($recent_projects)): ?>
            <div class="project-grid">
                <?php foreach ($recent_projects as $project): ?>
                <div class="project-card">
                    <div class="project-card-header">
                        <h3 class="project-card-title"><?php echo htmlspecialchars($project['title']); ?></h3>
                        <div class="project-card-meta">
                            <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($project['creator_name']); ?></span>
                            <span><i class="fas fa-graduation-cap"></i> <?php echo $project['branch']; ?></span>
                        </div>
                    </div>
                    
                    <div class="project-card-body">
                        <p class="project-card-description"><?php echo htmlspecialchars(substr($project['short_description'], 0, 150)) . '...'; ?></p>
                        
                        <div class="project-card-tags">
                            <span class="tag"><?php echo $project['branch']; ?></span>
                            <span class="tag"><?php echo htmlspecialchars($project['project_type']); ?></span>
                        </div>
                    </div>
                    
                    <div class="project-card-footer">
                        <div class="project-stats">
                            <span class="stat">
                                <i class="fas fa-thumbs-up"></i>
                                <?php echo $project['upvotes']; ?>
                            </span>
                            <span class="stat">
                                <i class="fas fa-star"></i>
                                <?php echo $project['stars']; ?>
                            </span>
                            <span class="stat">
                                <i class="fas fa-comments"></i>
                                0
                            </span>
                        </div>
                        <a href="project.php?id=<?php echo $project['id']; ?>" class="btn btn-sm btn-primary">
                            View Details
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="text-center">
                <a href="projects/" class="btn btn-primary">
                    <i class="fas fa-folder"></i>
                    View All Projects
                </a>
            </div>
            <?php else: ?>
            <div class="text-center">
                <div class="empty-state">
                    <i class="fas fa-folder-open"></i>
                    <h3>No Projects Yet</h3>
                    <p>Be the first to showcase your innovative project!</p>
                    <?php if (is_logged_in()): ?>
                    <a href="dashboard/projects/add.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Add Your Project
                    </a>
                    <?php else: ?>
                    <a href="/auth/register.php" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i>
                        Join to Add Projects
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="contact">
        <div class="container">
            <div class="section-header">
                <h2>Get in Touch</h2>
                <p>Have questions or need support? We're here to help!</p>
            </div>
            
            <div class="row">
                <div class="contact_info">
                    <div class="contact-info">
                        <h3>Dr. YC James Yen Government Polytechnic</h3>
                        <p>Kuppam, Andhra Pradesh</p>
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <span>example@gmail.com</span>
                        </div>
                        
                        <div class="contact-item">
                            <i class="fas fa-phone"></i>
                            <span>+91-XXXX-XXXXXX</span>
                        </div>
                        
                        <div class="contact-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>Yanadipalle, Kuppam, Andhra Pradesh, India, 517425</span>
                        </div>
                    </div>
                </div>                        
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3><?php echo APP_NAME; ?></h3>
                    <p>Empowering innovation and collaboration in engineering education at Dr. YC James Yen Government Polytechnic, Kuppam.</p>
                </div>
                
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="#home">Home</a></li>
                        <li><a href="#about">About</a></li>
                        <li><a href="projects/">Projects</a></li>
                        <li><a href="projects/">Projects</a></li>
                        <li><a href="/auth/register.php">Register</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Branches</h4>
                    <ul>
                        <?php foreach ($branches as $code => $branch): ?>
                        <li><a href="projects/?branch=<?php echo $code; ?>"><?php echo $code; ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Contact</h4>
                    <p>Dr. YC James Yen Government Polytechnic</p>
                    <p>Kuppam, Andhra Pradesh</p>
                    <p>Number:+91-xxxxxxxx</p>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html>

<?php
/**
 * Helper function to get branch icon
 */
function getBranchIcon($branch) {
    $icons = [
        'DCME' => 'laptop-code',
        'DEEE' => 'bolt',
        'DME' => 'cogs',
        'DECE' => 'microchip'
    ];
    return $icons[$branch] ?? 'graduation-cap';
}
?>
