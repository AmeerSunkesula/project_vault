<?php
/**
 * Edit Project
 * Project Vault - Dr. YC James Yen Government Polytechnic, Kuppam
 */

require_once '../../config/config.php';
require_once '../../config/database.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('/');
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$project_id = $_GET['id'] ?? '';

if (empty($project_id)) {
    redirect('index.php');
}

// Get project details
$project = null;
try {
    $query = "SELECT * FROM projects WHERE id = :project_id AND created_by = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':project_id', $project_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $project = $stmt->fetch();
    
    if (!$project) {
        redirect('index.php');
    }
} catch (Exception $e) {
    redirect('index.php');
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize_input($_POST['title']);
    $short_description = sanitize_input($_POST['short_description']);
    $long_description = sanitize_input($_POST['long_description']);
    $branch = sanitize_input($_POST['branch']);
    $project_type = sanitize_input($_POST['project_type']);
    $github_link = isset($_POST['github_link']) ? sanitize_input($_POST['github_link']) : '';
    
    // Validation
    if (empty($title) || empty($short_description) || empty($long_description) || empty($branch) || empty($project_type)) {
        $error_message = 'Please fill in all required fields.';
    } elseif (strlen($title) > 200) {
        $error_message = 'Project title must be less than 200 characters.';
    } elseif (strlen($short_description) > 500) {
        $error_message = 'Short description must be less than 500 characters.';
    } elseif (!empty($github_link) && !filter_var($github_link, FILTER_VALIDATE_URL)) {
        $error_message = 'Please enter a valid GitHub URL.';
    } else {
        try {
            // Update project
            $query = "UPDATE projects SET title = :title, short_description = :short_description, 
                      long_description = :long_description, branch = :branch, project_type = :project_type, 
                      github_link = :github_link, updated_at = NOW() 
                      WHERE id = :project_id AND created_by = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':short_description', $short_description);
            $stmt->bindParam(':long_description', $long_description);
            $stmt->bindParam(':branch', $branch);
            $stmt->bindParam(':project_type', $project_type);
            $stmt->bindParam(':github_link', $github_link);
            $stmt->bindParam(':project_id', $project_id);
            $stmt->bindParam(':user_id', $user_id);
            
            if ($stmt->execute()) {
                $success_message = 'Project updated successfully!';
                
                // Redirect to project view after 2 seconds
                header("refresh:2;url=../../project.php?id={$project_id}");
            } else {
                $error_message = 'Failed to update project. Please try again.';
            }
        } catch (Exception $e) {
            $error_message = 'An error occurred. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Project - <?php echo APP_NAME; ?></title>
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
                        <p>Edit Project</p>
                    </div>
                </div>
                
                <nav class="nav">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        Back to My Projects
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="dashboard-main">
        <div class="container">
            <div class="page-header">
                <h1>Edit Project</h1>
                <p>Update your project information</p>
            </div>
            
            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success_message; ?>
                    <p>Redirecting to your project...</p>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-8">
                    <div class="card">
                        <div class="card-header">
                            <h3>Project Details</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST" class="project-form">
                                <div class="form-group">
                                    <label for="title">
                                        <i class="fas fa-heading"></i>
                                        Project Title *
                                    </label>
                                    <input type="text" id="title" name="title" required 
                                           value="<?php echo htmlspecialchars($project['title']); ?>"
                                           maxlength="200" placeholder="Enter a descriptive title for your project">
                                    <small class="form-help">Maximum 200 characters</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="short_description">
                                        <i class="fas fa-align-left"></i>
                                        Short Description *
                                    </label>
                                    <textarea id="short_description" name="short_description" required 
                                              maxlength="500" rows="3" 
                                              placeholder="Brief description of your project (will be shown in project listings)"><?php echo htmlspecialchars($project['short_description']); ?></textarea>
                                    <small class="form-help">Maximum 500 characters</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="long_description">
                                        <i class="fas fa-file-alt"></i>
                                        Detailed Description *
                                    </label>
                                    <textarea id="long_description" name="long_description" required 
                                              rows="8" placeholder="Provide a detailed description of your project, including objectives, methodology, technologies used, and results"><?php echo htmlspecialchars($project['long_description']); ?></textarea>
                                </div>
                                
                                <div class="row">
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label for="branch">
                                                <i class="fas fa-graduation-cap"></i>
                                                Branch *
                                            </label>
                                            <select id="branch" name="branch" required onchange="updateProjectTypes()">
                                                <option value="">Select Branch</option>
                                                <?php foreach ($branches as $code => $branch_data): ?>
                                                <option value="<?php echo $code; ?>" 
                                                        <?php echo ($project['branch'] === $code) ? 'selected' : ''; ?>>
                                                    <?php echo $code; ?> - <?php echo $branch_data['name']; ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label for="project_type">
                                                <i class="fas fa-tags"></i>
                                                Project Type *
                                            </label>
                                            <select id="project_type" name="project_type" required>
                                                <option value="">Select Project Type</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="github_link">
                                        <i class="fab fa-github"></i>
                                        GitHub Repository (Optional)
                                    </label>
                                    <input type="url" id="github_link" name="github_link" 
                                           value="<?php echo htmlspecialchars($project['github_link']); ?>"
                                           placeholder="https://github.com/username/repository">
                                    <small class="form-help">Link to your project's GitHub repository</small>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-save"></i>
                                        Update Project
                                    </button>
                                    <a href="index.php" class="btn btn-secondary btn-lg">
                                        <i class="fas fa-times"></i>
                                        Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-4">
                    <div class="card">
                        <div class="card-header">
                            <h3>Project Information</h3>
                        </div>
                        <div class="card-body">
                            <div class="project-info">
                                <div class="info-item">
                                    <span class="info-label">Created:</span>
                                    <span class="info-value"><?php echo date('M j, Y g:i A', strtotime($project['created_at'])); ?></span>
                                </div>
                                
                                <div class="info-item">
                                    <span class="info-label">Last Updated:</span>
                                    <span class="info-value"><?php echo date('M j, Y g:i A', strtotime($project['updated_at'])); ?></span>
                                </div>
                                
                                <div class="info-item">
                                    <span class="info-label">Status:</span>
                                    <span class="info-value status-<?php echo $project['status']; ?>">
                                        <?php echo ucfirst($project['status']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h3>Project Guidelines</h3>
                        </div>
                        <div class="card-body">
                            <div class="guidelines">
                                <h4><i class="fas fa-lightbulb"></i> Tips for a Great Project</h4>
                                <ul>
                                    <li>Choose a clear, descriptive title</li>
                                    <li>Write a compelling short description</li>
                                    <li>Provide detailed technical information</li>
                                    <li>Include your GitHub repository if available</li>
                                    <li>Select the appropriate branch and type</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="../../assets/js/main.js"></script>
    <script>
        // Branch and project type data
        const branchData = <?php echo json_encode($branches); ?>;
        const currentProjectType = '<?php echo $project['project_type']; ?>';
        
        function updateProjectTypes() {
            const branchSelect = document.getElementById('branch');
            const projectTypeSelect = document.getElementById('project_type');
            const selectedBranch = branchSelect.value;
            
            // Clear existing options
            projectTypeSelect.innerHTML = '<option value="">Select Project Type</option>';
            
            if (selectedBranch && branchData[selectedBranch]) {
                const types = branchData[selectedBranch].types;
                types.forEach(type => {
                    const option = document.createElement('option');
                    option.value = type;
                    option.textContent = type;
                    if (type === currentProjectType) {
                        option.selected = true;
                    }
                    projectTypeSelect.appendChild(option);
                });
            }
        }
        
        // Initialize project types if branch is already selected
        document.addEventListener('DOMContentLoaded', function() {
            updateProjectTypes();
        });
        
        // Character counters
        document.addEventListener('DOMContentLoaded', function() {
            const titleInput = document.getElementById('title');
            const shortDescTextarea = document.getElementById('short_description');
            
            if (titleInput) {
                titleInput.addEventListener('input', function() {
                    const remaining = 200 - this.value.length;
                    updateCharacterCounter(this, remaining);
                });
            }
            
            if (shortDescTextarea) {
                shortDescTextarea.addEventListener('input', function() {
                    const remaining = 500 - this.value.length;
                    updateCharacterCounter(this, remaining);
                });
            }
        });
        
        function updateCharacterCounter(element, remaining) {
            let counter = element.parentNode.querySelector('.char-counter');
            if (!counter) {
                counter = document.createElement('small');
                counter.className = 'char-counter';
                element.parentNode.appendChild(counter);
            }
            
            counter.textContent = `${remaining} characters remaining`;
            counter.style.color = remaining < 50 ? '#dc3545' : '#666';
        }
    </script>
</body>
</html>
