<?php
/**
 * Admin Collaborations Management
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

// Handle collaboration actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $collaboration_id = $_POST['collaboration_id'] ?? '';
    
    try {
        switch ($action) {
            case 'approve_collaboration':
                $query = "UPDATE project_collaborators SET status = 'accepted', responded_at = CURRENT_TIMESTAMP WHERE id = :collaboration_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':collaboration_id', $collaboration_id);
                $stmt->execute();
                $message = 'Collaboration approved successfully.';
                break;
                
            case 'reject_collaboration':
                $query = "UPDATE project_collaborators SET status = 'rejected', responded_at = CURRENT_TIMESTAMP WHERE id = :collaboration_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':collaboration_id', $collaboration_id);
                $stmt->execute();
                $message = 'Collaboration rejected successfully.';
                break;
                
            case 'remove_collaboration':
                $query = "DELETE FROM project_collaborators WHERE id = :collaboration_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':collaboration_id', $collaboration_id);
                $stmt->execute();
                $message = 'Collaboration removed successfully.';
                break;
        }
    } catch (Exception $e) {
        $error_message = 'An error occurred: ' . $e->getMessage();
    }
}

// Get search and filter parameters
$status = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';
$sort = isset($_GET['sort']) ? sanitize_input($_GET['sort']) : 'newest';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Build query
$where_conditions = ["1=1"];
$params = [];

if (!empty($status)) {
    $where_conditions[] = "pc.status = :status";
    $params[':status'] = $status;
}

$where_clause = implode(' AND ', $where_conditions);

// Sort options
$sort_options = [
    'newest' => 'pc.requested_at DESC',
    'oldest' => 'pc.requested_at ASC',
    'project' => 'p.title ASC',
    'user' => 'u.full_name ASC'
];

$order_by = $sort_options[$sort] ?? $sort_options['newest'];

// Get collaborations
$collaborations = [];
$total_collaborations = 0;

try {
    // Count total collaborations
    $count_query = "SELECT COUNT(*) as total FROM project_collaborators pc 
                    JOIN projects p ON pc.project_id = p.id 
                    JOIN users u ON pc.user_id = u.id 
                    WHERE {$where_clause}";
    $count_stmt = $db->prepare($count_query);
    foreach ($params as $key => $value) {
        $count_stmt->bindValue($key, $value);
    }
    $count_stmt->execute();
    $total_collaborations = $count_stmt->fetch()['total'];
    
    // Get collaborations with pagination
    $query = "SELECT pc.*, 
                     p.title as project_title, p.branch as project_branch, p.project_type,
                     u.full_name as user_name, u.username as user_username, u.email as user_email,
                     creator.full_name as creator_name, creator.username as creator_username
              FROM project_collaborators pc 
              JOIN projects p ON pc.project_id = p.id 
              JOIN users u ON pc.user_id = u.id 
              JOIN users creator ON p.created_by = creator.id
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
    $collaborations = $stmt->fetchAll();
} catch (Exception $e) {
    $error_message = 'Error loading collaborations.';
}

// Calculate pagination
$total_pages = ceil($total_collaborations / $limit);
$has_prev = $page > 1;
$has_next = $page < $total_pages;

// Get statistics
$stats = [
    'total_collaborations' => $total_collaborations,
    'pending_collaborations' => 0,
    'accepted_collaborations' => 0,
    'rejected_collaborations' => 0
];

foreach ($collaborations as $collaboration) {
    switch ($collaboration['status']) {
        case 'pending':
            $stats['pending_collaborations']++;
            break;
        case 'accepted':
            $stats['accepted_collaborations']++;
            break;
        case 'rejected':
            $stats['rejected_collaborations']++;
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Collaborations - <?php echo APP_NAME; ?></title>
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
                        <p>Admin - Manage Collaborations</p>
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
            <!-- Page Header -->
            <div class="page-header">
                <h1>Manage Collaborations</h1>
                <p>Monitor and manage collaboration requests between users and projects</p>
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
                        <i class="fas fa-handshake"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $stats['total_collaborations']; ?></div>
                        <div class="stat-label">Total Collaborations</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $stats['pending_collaborations']; ?></div>
                        <div class="stat-label">Pending Requests</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $stats['accepted_collaborations']; ?></div>
                        <div class="stat-label">Accepted</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $stats['rejected_collaborations']; ?></div>
                        <div class="stat-label">Rejected</div>
                    </div>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="search-filters">
                <form method="GET" class="search-form">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="">All Status</option>
                                <option value="pending" <?php echo ($status === 'pending') ? 'selected' : ''; ?>>Pending</option>
                                <option value="accepted" <?php echo ($status === 'accepted') ? 'selected' : ''; ?>>Accepted</option>
                                <option value="rejected" <?php echo ($status === 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="sort">Sort By</label>
                            <select id="sort" name="sort">
                                <option value="newest" <?php echo ($sort === 'newest') ? 'selected' : ''; ?>>Newest First</option>
                                <option value="oldest" <?php echo ($sort === 'oldest') ? 'selected' : ''; ?>>Oldest First</option>
                                <option value="project" <?php echo ($sort === 'project') ? 'selected' : ''; ?>>Project Name</option>
                                <option value="user" <?php echo ($sort === 'user') ? 'selected' : ''; ?>>User Name</option>
                            </select>
                        </div>
                        
                        <div class="filter-actions">
                            <button type="submit" class="btn btn-secondary">
                                <i class="fas fa-filter"></i>
                                Apply Filters
                            </button>
                            <a href="collaborations.php" class="btn btn-outline">
                                <i class="fas fa-times"></i>
                                Clear
                            </a>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Collaborations Table -->
            <div class="admin-section">
                <div class="section-header">
                    <h2>All Collaborations</h2>
                    <div class="section-actions">
                        <button class="btn btn-primary" onclick="refreshCollaborations()">
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
                                <th>Requested By</th>
                                <th>Project Creator</th>
                                <th>Status</th>
                                <th>Requested</th>
                                <th>Responded</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($collaborations as $collaboration): ?>
                            <tr>
                                <td>
                                    <div class="project-info">
                                        <div class="project-title">
                                            <a href="../../project.php?id=<?php echo $collaboration['project_id']; ?>" target="_blank">
                                                <?php echo htmlspecialchars($collaboration['project_title']); ?>
                                            </a>
                                        </div>
                                        <div class="project-meta">
                                            <span class="tag branch-tag"><?php echo $collaboration['project_branch']; ?></span>
                                            <span class="tag type-tag"><?php echo htmlspecialchars($collaboration['project_type']); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="user-info">
                                        <div class="user-name"><?php echo htmlspecialchars($collaboration['user_name']); ?></div>
                                        <div class="user-username">@<?php echo htmlspecialchars($collaboration['user_username']); ?></div>
                                        <div class="user-email"><?php echo htmlspecialchars($collaboration['user_email']); ?></div>
                                    </div>
                                </td>
                                <td>
                                    <div class="creator-info">
                                        <div class="creator-name"><?php echo htmlspecialchars($collaboration['creator_name']); ?></div>
                                        <div class="creator-username">@<?php echo htmlspecialchars($collaboration['creator_username']); ?></div>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $collaboration['status']; ?>">
                                        <?php echo ucfirst($collaboration['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="date"><?php echo date('M j, Y H:i', strtotime($collaboration['requested_at'])); ?></span>
                                </td>
                                <td>
                                    <?php if ($collaboration['responded_at']): ?>
                                        <span class="date"><?php echo date('M j, Y H:i', strtotime($collaboration['responded_at'])); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">Not responded</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="../../project.php?id=<?php echo $collaboration['project_id']; ?>" 
                                           class="btn btn-sm btn-primary" target="_blank">
                                            <i class="fas fa-eye"></i>
                                            View Project
                                        </a>
                                        
                                        <?php if ($collaboration['status'] === 'pending'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="approve_collaboration">
                                                <input type="hidden" name="collaboration_id" value="<?php echo $collaboration['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-success" 
                                                        onclick="return confirm('Approve this collaboration request?')">
                                                    <i class="fas fa-check"></i>
                                                    Approve
                                                </button>
                                            </form>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="reject_collaboration">
                                                <input type="hidden" name="collaboration_id" value="<?php echo $collaboration['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" 
                                                        onclick="return confirm('Reject this collaboration request?')">
                                                    <i class="fas fa-times"></i>
                                                    Reject
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="remove_collaboration">
                                                <input type="hidden" name="collaboration_id" value="<?php echo $collaboration['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-warning" 
                                                        onclick="return confirm('Remove this collaboration?')">
                                                    <i class="fas fa-trash"></i>
                                                    Remove
                                                </button>
                                            </form>
                                        <?php endif; ?>
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
        function refreshCollaborations() {
            location.reload();
        }
    </script>
</body>
</html>
