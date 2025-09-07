<?php
/**
 * My Projects Dashboard
 * Project Vault - Dr. YC James Yen Government Polytechnic, Kuppam
 */

require_once '../../config/config.php';
require_once '../../config/database.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('auth/login.php');
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// Get search and filter parameters
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$branch = isset($_GET['branch']) ? sanitize_input($_GET['branch']) : '';
$project_type = isset($_GET['project_type']) ? sanitize_input($_GET['project_type']) : '';
$status = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';
$sort = isset($_GET['sort']) ? sanitize_input($_GET['sort']) : 'newest';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = PROJECTS_PER_PAGE;
$offset = ($page - 1) * $limit;

// Build query for user's projects
$where_conditions = ["p.created_by = :user_id"];
$params = [':user_id' => $user_id];

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

// Get user's projects
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
    $query = "SELECT p.*, 
                     (SELECT COUNT(*) FROM project_votes pv WHERE pv.project_id = p.id AND pv.vote_type = 'upvote') as upvote_count,
                     (SELECT COUNT(*) FROM project_votes pv WHERE pv.project_id = p.id AND pv.vote_type = 'downvote') as downvote_count,
                     (SELECT COUNT(*) FROM project_stars ps WHERE ps.project_id = p.id) as star_count,
                     (SELECT COUNT(*) FROM comments c WHERE c.project_id = p.id) as comment_count
              FROM projects p 
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
    <title>My Projects - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
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
                        <p>My Projects</p>
                    </div>
                </div>
                
                <nav class="nav">
                    <ul class="nav-links">
                        <li><a href="../../index.php">Home</a></li>
                        <li><a href="../../projects/">Projects</a></li>
                        <li><a href="../index.php">Dashboard</a></li>
                    </ul>
                    
                    <div class="user-menu">
                        <div class="user-dropdown">
                            <button class="user-button">
                                <i class="fas fa-user-circle"></i>
                                <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="dropdown-menu">
                                <a href="../settings.php"><i class="fas fa-cog"></i> Settings</a>
                                <a href="../../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
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
                <h1>My Projects</h1>
                <p>Manage and view all your created projects</p>
            </div>
            
            <!-- Search and Filters -->
            <div class="search-filters">
                <form method="GET" class="search-form">
                    <div class="search-bar">
                        <div class="search-input-group">
                            <i class="fas fa-search"></i>
                            <input type="text" name="search" placeholder="Search your projects..." 
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
                    <h2>My Projects</h2>
                    <p>
                        <?php if ($total_projects > 0): ?>
                            Showing <?php echo $offset + 1; ?>-<?php echo min($offset + $limit, $total_projects); ?> 
                            of <?php echo $total_projects; ?> projects
                        <?php else: ?>
                            No projects found
                        <?php endif; ?>
                    </p>
                </div>
                
                <div class="results-actions">
                    <a href="add.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Add New Project
                    </a>
                </div>
            </div>
            
            <!-- Projects Grid -->
            <?php if (!empty($projects)): ?>
            <div class="project-grid">
                <?php foreach ($projects as $project): ?>
                <div class="project-card">
                    <div class="project-card-header">
                        <h3 class="project-card-title">
                            <a href="../../project.php?id=<?php echo $project['id']; ?>">
                                <?php echo htmlspecialchars($project['title']); ?>
                            </a>
                        </h3>
                        <div class="project-card-meta">
                            <span><i class="fas fa-graduation-cap"></i> <?php echo $project['branch']; ?></span>
                            <span><i class="fas fa-calendar"></i> <?php echo date('M j, Y', strtotime($project['created_at'])); ?></span>
                            <span class="status-badge status-<?php echo $project['status']; ?>">
                                <?php echo ucfirst($project['status']); ?>
                            </span>
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
                            <a href="../../project.php?id=<?php echo $project['id']; ?>" class="btn btn-sm btn-primary">
                                View
                            </a>
                            <a href="edit.php?id=<?php echo $project['id']; ?>" class="btn btn-sm btn-secondary">
                                Edit
                            </a>
                            <button class="btn btn-sm btn-danger" onclick="deleteProject(<?php echo $project['id']; ?>)">
                                Delete
                            </button>
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
                <h3>No Projects Yet</h3>
                <p>You haven't created any projects yet. Start by adding your first project!</p>
                <div class="empty-state-actions">
                    <a href="add.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Create Your First Project
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Delete Project Modal -->
    <div class="modal" id="deleteModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-exclamation-triangle"></i> Delete Project</h3>
                <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p><strong>Warning:</strong> This action cannot be undone. This will permanently delete your project and remove all associated data including comments, votes, and collaborations.</p>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">
                        Cancel
                    </button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                        <i class="fas fa-trash"></i>
                        Delete Project
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="../../assets/js/main.js"></script>
    <script>
        let projectToDelete = null;
        
        function deleteProject(projectId) {
            projectToDelete = projectId;
            document.getElementById('deleteModal').style.display = 'block';
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
            projectToDelete = null;
        }
        
        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            if (projectToDelete) {
                // Send delete request
                fetch('../../api/projects.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'delete',
                        project_id: projectToDelete
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('Project deleted successfully!', 'success');
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                    } else {
                        showAlert(data.message || 'Failed to delete project', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('Network error. Please try again.', 'error');
                })
                .finally(() => {
                    closeDeleteModal();
                });
            }
        });
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('deleteModal');
            if (event.target === modal) {
                closeDeleteModal();
            }
        }
        
        function showAlert(message, type) {
            // Create alert element
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                ${message}
            `;
            
            // Insert at top of main content
            const main = document.querySelector('.dashboard-main .container');
            main.insertBefore(alert, main.firstChild);
            
            // Remove after 5 seconds
            setTimeout(() => {
                alert.remove();
            }, 5000);
        }
    </script>
</body>
</html>
