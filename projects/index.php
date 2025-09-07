<?php
/**
 * Projects Index Page
 * Project Vault - Dr. YC James Yen Government Polytechnic, Kuppam
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Get search and filter parameters
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$branch = isset($_GET['branch']) ? sanitize_input($_GET['branch']) : '';
$project_type = isset($_GET['project_type']) ? sanitize_input($_GET['project_type']) : '';
$sort = isset($_GET['sort']) ? sanitize_input($_GET['sort']) : 'newest';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = PROJECTS_PER_PAGE;
$offset = ($page - 1) * $limit;

// Build query
$where_conditions = ["p.status = 'active'"];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(p.title LIKE :search OR p.short_description LIKE :search OR p.long_description LIKE :search)";
    $params[':search'] = "%{$search}%";
}

if (!empty($branch)) {
    $where_conditions[] = "p.branch = :branch";
    $params[':branch'] = $branch;
}

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
    <title>Projects - <?php echo APP_NAME; ?></title>
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
                        <p>Projects</p>
                    </div>
                </div>
                
                <nav class="nav">
                    <ul class="nav-links">
                        <li><a href="../index.php">Home</a></li>
                        <li><a href="index.php" class="active">Projects</a></li>
                        <?php if (is_logged_in()): ?>
                        <li><a href="../dashboard/">Dashboard</a></li>
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
                                        <a href="../dashboard/"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                                        <a href="../dashboard/profile.php"><i class="fas fa-user"></i> My Profile</a>
                                        <a href="../dashboard/settings.php"><i class="fas fa-cog"></i> Settings</a>
                                        <a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
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

    <!-- Main Content -->
    <main class="explore-main">
        <div class="container">
            <!-- Page Header -->
            <div class="page-header">
                <h1>All Projects</h1>
                <p>Discover innovative projects from students and staff across all engineering branches</p>
            </div>
            
            <!-- Search and Filters -->
            <div class="search-filters">
                <form method="GET" class="search-form">
                    <div class="search-bar">
                        <div class="search-input-group">
                            <i class="fas fa-search"></i>
                            <input type="text" name="search" placeholder="Search projects..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                            Search
                        </button>
                    </div>
                    
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="branch">Branch</label>
                            <select id="branch" name="branch">
                                <option value="">All Branches</option>
                                <?php foreach ($branches as $code => $branch_data): ?>
                                <option value="<?php echo $code; ?>" 
                                        <?php echo ($branch === $code) ? 'selected' : ''; ?>>
                                    <?php echo $code; ?> - <?php echo $branch_data['name']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="project_type">Project Type</label>
                            <select id="project_type" name="project_type">
                                <option value="">All Types</option>
                                <?php if (!empty($branch) && isset($branches[$branch])): ?>
                                    <?php foreach ($branches[$branch]['types'] as $type): ?>
                                    <option value="<?php echo $type; ?>" 
                                            <?php echo ($project_type === $type) ? 'selected' : ''; ?>>
                                        <?php echo $type; ?>
                                    </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
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
                            <a href="index.php" class="btn btn-outline">
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
                            of <?php echo $total_projects; ?> projects
                        <?php else: ?>
                            No projects found
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
                            <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($project['creator_name']); ?></span>
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
                    <i class="fas fa-search"></i>
                </div>
                <h3>No Projects Found</h3>
                <p>
                    <?php if (!empty($search) || !empty($branch) || !empty($project_type)): ?>
                        Try adjusting your search criteria or filters.
                    <?php else: ?>
                        Be the first to add a project to the vault!
                    <?php endif; ?>
                </p>
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
                        <i class="fas fa-refresh"></i>
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
                
                <div class="footer-section">
                    <h4>Branches</h4>
                    <ul>
                        <?php foreach ($branches as $code => $branch_data): ?>
                        <li><a href="?branch=<?php echo $code; ?>"><?php echo $code; ?></a></li>
                        <?php endforeach; ?>
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
        // Dynamically populate project types when a branch is selected
        document.addEventListener('DOMContentLoaded', function() {
            const branchesData = <?php echo json_encode($branches); ?>;
            const branchSelect = document.getElementById('branch');
            const typeSelect = document.getElementById('project_type');
            const initialSelectedType = <?php echo json_encode($project_type ?? ''); ?>;

            function populateProjectTypes(branchCode, preselectValue) {
                if (!typeSelect) return;

                // Reset options
                typeSelect.innerHTML = '';
                const defaultOption = document.createElement('option');
                defaultOption.value = '';
                defaultOption.textContent = 'All Types';
                typeSelect.appendChild(defaultOption);

                const branchInfo = branchesData && branchCode ? branchesData[branchCode] : null;
                const types = branchInfo && Array.isArray(branchInfo.types) ? branchInfo.types : [];

                if (types.length > 0) {
                    types.forEach(function(type) {
                        const opt = document.createElement('option');
                        opt.value = type;
                        opt.textContent = type;
                        if (preselectValue && preselectValue === type) {
                            opt.selected = true;
                        }
                        typeSelect.appendChild(opt);
                    });
                    typeSelect.disabled = false;
                } else {
                    // No branch selected → disable types
                    typeSelect.disabled = true;
                }
            }

            if (branchSelect && typeSelect) {
                // Initialize on load
                populateProjectTypes(branchSelect.value, initialSelectedType);

                // Update on change
                branchSelect.addEventListener('change', function() {
                    populateProjectTypes(this.value, '');
                });
            }
        });
    </script>
</body>
</html>
