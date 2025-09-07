<?php
/**
 * Admin Projects Management
 * Project Vault - Dr. YC James Yen Government Polytechnic, Kuppam
 */

require_once '../../config/config.php';
require_once '../../config/database.php';

// Check if user is logged in and is admin
if (!is_logged_in() || !is_admin()) {
    redirect('/');
}

$message = '';
$error_message = '';

// Handle project actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $project_id = $_POST['project_id'] ?? '';
    
    try {
        switch ($action) {
            case 'archive_project':
                $query = "UPDATE projects SET status = 'archived' WHERE id = :project_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':project_id', $project_id);
                $stmt->execute();
                $message = 'Project archived successfully.';
                break;
                
            case 'activate_project':
                $query = "UPDATE projects SET status = 'active' WHERE id = :project_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':project_id', $project_id);
                $stmt->execute();
                $message = 'Project activated successfully.';
                break;
                
            case 'delete_project':
                $db->beginTransaction();
                
                // Delete related data first
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
                $delete_project = "DELETE FROM projects WHERE id = :project_id";
                $stmt = $db->prepare($delete_project);
                $stmt->bindParam(':project_id', $project_id);
                $stmt->execute();
                
                $db->commit();
                $message = 'Project deleted successfully.';
                break;
        }
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollback();
        }
        $error_message = 'An error occurred: ' . $e->getMessage();
    }
}

// Get search and filter parameters
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$branch = isset($_GET['branch']) ? sanitize_input($_GET['branch']) : '';
$status = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';
$sort = isset($_GET['sort']) ? sanitize_input($_GET['sort']) : 'newest';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Build query
$where_conditions = ["1=1"];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(p.title LIKE :search OR p.short_description LIKE :search OR u.full_name LIKE :search)";
    $params[':search'] = "%{$search}%";
}

if (!empty($branch)) {
    $where_conditions[] = "p.branch = :branch";
    $params[':branch'] = $branch;
}

if (!empty($status)) {
    $where_conditions[] = "p.status = :status";
    $params[':status'] = $status;
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
    $count_query = "SELECT COUNT(*) as total FROM projects p 
                    JOIN users u ON p.created_by = u.id 
                    WHERE {$where_clause}";
    $count_stmt = $db->prepare($count_query);
    foreach ($params as $key => $value) {
        $count_stmt->bindValue($key, $value);
    }
    $count_stmt->execute();
    $total_projects = $count_stmt->fetch()['total'];
    
    // Get projects with pagination
    $query = "SELECT p.*, u.full_name as creator_name, u.username as creator_username,
                     (SELECT COUNT(*) FROM project_votes pv WHERE pv.project_id = p.id AND pv.vote_type = 'upvote') as upvote_count,
                     (SELECT COUNT(*) FROM project_votes pv WHERE pv.project_id = p.id AND pv.vote_type = 'downvote') as downvote_count,
                     (SELECT COUNT(*) FROM project_stars ps WHERE ps.project_id = p.id) as star_count,
                     (SELECT COUNT(*) FROM comments c WHERE c.project_id = p.id) as comment_count,
                     (SELECT COUNT(*) FROM project_collaborators pc WHERE pc.project_id = p.id AND pc.status = 'accepted') as collaborator_count
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

// Get statistics
$stats = [
    'total_projects' => $total_projects,
    'active_projects' => 0,
    'archived_projects' => 0,
    'total_upvotes' => 0,
    'total_stars' => 0
];

foreach ($projects as $project) {
    if ($project['status'] === 'active') {
        $stats['active_projects']++;
    } else {
        $stats['archived_projects']++;
    }
    $stats['total_upvotes'] += $project['upvote_count'];
    $stats['total_stars'] += $project['star_count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Projects - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="dashboard-body">
    <!-- Header -->
    <header class="dashboard-header">
        <div class="container">
            <div class="header-content">
                <div class="logo-section">
                    <img src="../../assets/images/polytechnic_logo.jpg" alt="College Logo" class="logo">
                    <div class="logo-text">
                        <h1><?php echo APP_NAME; ?></h1>
                        <p>Admin - Manage Projects</p>
                    </div>
                </div>
                
                <nav class="nav">
                    <ul class="nav-links">
                        <li><a href="../../index.php">Home</a></li>
                        <li><a href="../../projects/">Projects</a></li>
                        <li><a href="../../projects/">Projects</a></li>
                        <li><a href="../index.php">Dashboard</a></li>
                        <li><a href="index.php">Admin Panel</a></li>
                    </ul>
                    
                    <div class="user-menu">
                        <div class="user-dropdown">
                            <button class="user-button">
                                <i class="fas fa-user-circle"></i>
                                <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="dropdown-menu">
                                <a href="../profile.php"><i class="fas fa-user"></i> My Profile</a>
                                <a href="../settings.php"><i class="fas fa-cog"></i> Settings</a>
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
            <!-- Page Header -->
            <div class="page-header">
                <h1>Manage Projects</h1>
                <p>Monitor, manage, and moderate all projects in the system</p>
            </div>
            
            <!-- Messages -->
            <?php if ($message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <!-- Statistics -->
            <div class="dashboard-stats">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-folder"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $stats['total_projects']; ?></div>
                        <div class="stat-label">Total Projects</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-folder-open"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $stats['active_projects']; ?></div>
                        <div class="stat-label">Active Projects</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-archive"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $stats['archived_projects']; ?></div>
                        <div class="stat-label">Archived Projects</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-thumbs-up"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $stats['total_upvotes']; ?></div>
                        <div class="stat-label">Total Upvotes</div>
                    </div>
                </div>
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
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="">All Status</option>
                                <option value="active" <?php echo ($status === 'active') ? 'selected' : ''; ?>>Active</option>
                                <option value="archived" <?php echo ($status === 'archived') ? 'selected' : ''; ?>>Archived</option>
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
                            <a href="projects.php" class="btn btn-outline">
                                <i class="fas fa-times"></i>
                                Clear
                            </a>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Projects Table -->
            <div class="admin-section">
                <div class="section-header">
                    <h2>All Projects</h2>
                    <div class="section-actions">
                        <button class="btn btn-primary" onclick="refreshProjects()">
                            <i class="fas fa-sync-alt"></i>
                            Refresh
                        </button>
                    </div>
                </div>
                
                <div class="table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Project</th>
                                <th>Creator</th>
                                <th>Branch</th>
                                <th>Status</th>
                                <th>Stats</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($projects as $project): ?>
                            <tr>
                                <td>
                                    <div class="project-info">
                                        <div class="project-title">
                                            <a href="../../project.php?id=<?php echo $project['id']; ?>" target="_blank">
                                                <?php echo htmlspecialchars($project['title']); ?>
                                            </a>
                                        </div>
                                        <div class="project-description">
                                            <?php echo htmlspecialchars(substr($project['short_description'], 0, 100)) . '...'; ?>
                                        </div>
                                        <div class="project-type">
                                            <span class="tag type-tag"><?php echo htmlspecialchars($project['project_type']); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="creator-info">
                                        <div class="creator-name"><?php echo htmlspecialchars($project['creator_name']); ?></div>
                                        <div class="creator-username">@<?php echo htmlspecialchars($project['creator_username']); ?></div>
                                    </div>
                                </td>
                                <td>
                                    <span class="tag branch-tag"><?php echo $project['branch']; ?></span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $project['status']; ?>">
                                        <?php echo ucfirst($project['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="project-stats">
                                        <div class="stat-item">
                                            <i class="fas fa-thumbs-up"></i>
                                            <span><?php echo $project['upvote_count']; ?></span>
                                        </div>
                                        <div class="stat-item">
                                            <i class="fas fa-star"></i>
                                            <span><?php echo $project['star_count']; ?></span>
                                        </div>
                                        <div class="stat-item">
                                            <i class="fas fa-comments"></i>
                                            <span><?php echo $project['comment_count']; ?></span>
                                        </div>
                                        <div class="stat-item">
                                            <i class="fas fa-users"></i>
                                            <span><?php echo $project['collaborator_count']; ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="date"><?php echo date('M j, Y', strtotime($project['created_at'])); ?></span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="../../project.php?id=<?php echo $project['id']; ?>" 
                                           class="btn btn-sm btn-primary" target="_blank">
                                            <i class="fas fa-eye"></i>
                                            View
                                        </a>
                                        
                                        <?php if ($project['status'] === 'active'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="archive_project">
                                                <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-warning" 
                                                        onclick="return confirm('Archive this project?')">
                                                    <i class="fas fa-archive"></i>
                                                    Archive
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="activate_project">
                                                <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-success" 
                                                        onclick="return confirm('Activate this project?')">
                                                    <i class="fas fa-folder-open"></i>
                                                    Activate
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="delete_project">
                                            <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" 
                                                    onclick="return confirm('Delete this project permanently? This will remove all votes, stars, comments, and collaborations. This cannot be undone!')">
                                                <i class="fas fa-trash"></i>
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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
            </div>
        </div>
    </main>

    <script src="../../assets/js/main.js"></script>
    <script>
        function refreshProjects() {
            location.reload();
        }
    </script>
</body>
</html>
