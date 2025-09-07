<?php
/**
 * Branch Projects Page
 * Project Vault - Dr. YC James Yen Government Polytechnic, Kuppam
 */

require_once '../config/config.php';
require_once '../config/database.php';

$branch_code = $_GET['branch'] ?? '';

if (empty($branch_code) || !isset($branches[$branch_code])) {
    redirect('index.php');
}

$branch_data = $branches[$branch_code];
$project_type = $_GET['type'] ?? '';
$sort = $_GET['sort'] ?? 'newest';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = PROJECTS_PER_PAGE;
$offset = ($page - 1) * $limit;

// Build query
$where_conditions = ["p.status = 'active'", "p.branch = :branch"];
$params = [':branch' => $branch_code];

if (!empty($project_type)) {
    $where_conditions[] = "p.project_type = :project_type";
    $params[':project_type'] = $project_type;
}

$where_clause = implode(' AND ', $where_conditions);

// Sort options
$sort_options = [
    'newest' => 'p.created_at DESC',
    'oldest' => 'p.created_at ASC',
    'popular' => 'p.upvotes DESC, p.stars DESC',
    'title' => 'p.title ASC'
];

$order_by = $sort_options[$sort] ?? $sort_options['newest'];

// Get projects
$projects = [];
$total_projects = 0;

try {
    // Count total projects
    $count_query = "SELECT COUNT(*) as total FROM projects p WHERE {$where_clause}";
    $count_stmt = $db->prepare($count_query);
    foreach ($params as $key => $value) {
        $count_stmt->bindValue($key, $value);
    }
    $count_stmt->execute();
    $total_projects = $count_stmt->fetch()['total'];
    
    // Get projects with pagination
    $query = "SELECT p.*, u.full_name as creator_name, u.branch as creator_branch,
                     (SELECT COUNT(*) FROM project_votes pv WHERE pv.project_id = p.id AND pv.vote_type = 'upvote') as upvote_count,
                     (SELECT COUNT(*) FROM project_votes pv WHERE pv.project_id = p.id AND pv.vote_type = 'downvote') as downvote_count,
                     (SELECT COUNT(*) FROM project_stars ps WHERE ps.project_id = p.id) as star_count,
                     (SELECT COUNT(*) FROM comments c WHERE c.project_id = p.id) as comment_count
              FROM projects p 
              JOIN users u ON p.created_by = u.id 
              WHERE {$where_clause}
              ORDER BY {$order_by}
              LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $projects = $stmt->fetchAll();
} catch (Exception $e) {
    $error_message = 'Error loading projects.';
}

// Calculate pagination
$total_pages = ceil($total_projects / $limit);
$has_prev = $page > 1;
$has_next = $page < $total_pages;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $branch_code; ?> Projects - <?php echo APP_NAME; ?></title>
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
                        <p><?php echo $branch_code; ?> Projects</p>
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
            <!-- Branch Header -->
            <div class="branch-header">
                <div class="branch-icon">
                    <i class="fas fa-<?php echo getBranchIcon($branch_code); ?>"></i>
                </div>
                <div class="branch-info">
                    <h1><?php echo $branch_code; ?></h1>
                    <p><?php echo $branch_data['name']; ?></p>
                </div>
                <div class="branch-stats">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $total_projects; ?></div>
                        <div class="stat-label">Projects</div>
                    </div>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="search-filters">
                <form method="GET" class="search-form">
                    <input type="hidden" name="branch" value="<?php echo $branch_code; ?>">
                    
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="type">Project Type</label>
                            <select id="type" name="type">
                                <option value="">All Types</option>
                                <?php foreach ($branch_data['types'] as $type): ?>
                                <option value="<?php echo $type; ?>" 
                                        <?php echo ($project_type === $type) ? 'selected' : ''; ?>>
                                    <?php echo $type; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="sort">Sort By</label>
                            <select id="sort" name="sort">
                                <option value="newest" <?php echo ($sort === 'newest') ? 'selected' : ''; ?>>Newest First</option>
                                <option value="oldest" <?php echo ($sort === 'oldest') ? 'selected' : ''; ?>>Oldest First</option>
                                <option value="popular" <?php echo ($sort === 'popular') ? 'selected' : ''; ?>>Most Popular</option>
                                <option value="title" <?php echo ($sort === 'title') ? 'selected' : ''; ?>>Title A-Z</option>
                            </select>
                        </div>
                        
                        <div class="filter-actions">
                            <button type="submit" class="btn btn-secondary">
                                <i class="fas fa-filter"></i>
                                Apply Filters
                            </button>
                            <a href="?branch=<?php echo $branch_code; ?>" class="btn btn-outline">
                                <i class="fas fa-times"></i>
                                Clear
                            </a>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Results Header -->
            <div class="results-header">
                <div class="results-info">
                    <h2>Projects</h2>
                    <p>
                        <?php if ($total_projects > 0): ?>
                            Showing <?php echo $offset + 1; ?>-<?php echo min($offset + $limit, $total_projects); ?> 
                            of <?php echo $total_projects; ?> projects in <?php echo $branch_code; ?>
                        <?php else: ?>
                            No projects found in <?php echo $branch_code; ?>
                        <?php endif; ?>
                    </p>
                </div>
                
                <?php if (is_logged_in()): ?>
                <div class="results-actions">
                    <a href="../dashboard/projects/add.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Add Project
                    </a>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Projects Grid -->
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
                            <span><i class="fas fa-user"></i> <a href="user.php?id=<?php echo $project['created_by']; ?>" class="creator-link"><?php echo htmlspecialchars($project['creator_name']); ?></a></span>
                            <span><i class="fas fa-calendar"></i> <?php echo date('M j, Y', strtotime($project['created_at'])); ?></span>
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
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($has_prev): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
                       class="btn btn-secondary">
                        <i class="fas fa-chevron-left"></i>
                        Previous
                    </a>
                <?php endif; ?>
                
                <div class="pagination-info">
                    Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                </div>
                
                <?php if ($has_next): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                       class="btn btn-secondary">
                        Next
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <?php else: ?>
            <!-- Empty State -->
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-folder-open"></i>
                </div>
                <h3>No Projects Found</h3>
                <p>No projects found in <?php echo $branch_code; ?> branch.</p>
                <div class="empty-state-actions">
                    <?php if (is_logged_in()): ?>
                        <a href="../dashboard/projects/add.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                            Add Your Project
                        </a>
                    <?php else: ?>
                        <a href="/auth/register.php" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i>
                            Join to Add Projects
                        </a>
                    <?php endif; ?>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        View All Projects
                    </a>
                </div>
            </div>
            <?php endif; ?>
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
