<?php
/**
 * Add New Project
 * Project Vault - Dr. YC James Yen Government Polytechnic, Kuppam
 */

require_once '../../config/config.php';
require_once '../../config/database.php';

// Require user to be logged in
require_login();

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize_input($_POST['title']);
    $short_description = sanitize_input($_POST['short_description']);
    $long_description = sanitize_input($_POST['long_description']);
    $branch = sanitize_input($_POST['branch']);
    $project_type = sanitize_input($_POST['project_type']);
    $github_link = isset($_POST['github_link']) ? sanitize_input($_POST['github_link']) : '';
    $collaborators = isset($_POST['collaborators']) ? array_filter(array_map('sanitize_input', $_POST['collaborators'])) : [];
    
    // Validation
    if (empty($title) || empty($short_description) || empty($long_description) || empty($branch) || empty($project_type)) {
        $error_message = 'Please fill in all required fields.';
    } elseif (strlen($title) > 200) {
        $error_message = 'Project title must be less than 200 characters.';
    } elseif (strlen($short_description) > 500) {
        $error_message = 'Short description must be less than 500 characters.';
    } elseif (!empty($github_link) && !filter_var($github_link, FILTER_VALIDATE_URL)) {
        $error_message = 'Please enter a valid GitHub URL.';
    } elseif (count($collaborators) > 5) {
        $error_message = 'You can add a maximum of 5 collaborators.';
    } else {
        try {
            // Start transaction
            $db->beginTransaction();
            
            // Insert project
            $query = "INSERT INTO projects (title, short_description, long_description, branch, project_type, github_link, created_by) 
                      VALUES (:title, :short_description, :long_description, :branch, :project_type, :github_link, :created_by)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':short_description', $short_description);
            $stmt->bindParam(':long_description', $long_description);
            $stmt->bindParam(':branch', $branch);
            $stmt->bindParam(':project_type', $project_type);
            $stmt->bindParam(':github_link', $github_link);
            $stmt->bindParam(':created_by', $user_id);
            
            if ($stmt->execute()) {
                $project_id = $db->lastInsertId();
                
                // Create notification for staff about new project
                $notification_query = "INSERT INTO notifications (user_id, type, title, message, related_id) 
                                      SELECT id, 'project_approval', 'New Project Added', 
                                             :message, :project_id 
                                      FROM users WHERE role = 'staff' OR role = 'admin'";
                $notification_stmt = $db->prepare($notification_query);
                $message = "A new project '{$title}' has been added by " . $_SESSION['full_name'];
                $notification_stmt->bindParam(':message', $message);
                $notification_stmt->bindParam(':project_id', $project_id);
                $notification_stmt->execute();
                
                // Handle collaborators
                if (!empty($collaborators)) {
                    foreach ($collaborators as $collaborator_username) {
                        if (!empty($collaborator_username)) {
                            // Get collaborator user ID
                            $user_query = "SELECT id FROM users WHERE username = :username AND status = 'active'";
                            $user_stmt = $db->prepare($user_query);
                            $user_stmt->bindParam(':username', $collaborator_username);
                            $user_stmt->execute();
                            $collaborator = $user_stmt->fetch();
                            
                            if ($collaborator) {
                                $collaborator_id = $collaborator['id'];
                                
                                // Check if collaboration already exists
                                $check_query = "SELECT id FROM project_collaborators 
                                                WHERE project_id = :project_id AND user_id = :collaborator_id";
                                $check_stmt = $db->prepare($check_query);
                                $check_stmt->bindParam(':project_id', $project_id);
                                $check_stmt->bindParam(':collaborator_id', $collaborator_id);
                                $check_stmt->execute();
                                
                                if (!$check_stmt->fetch()) {
                                    // Create collaboration request
                                    $collab_query = "INSERT INTO project_collaborators (project_id, user_id, status) 
                                                     VALUES (:project_id, :collaborator_id, 'pending')";
                                    $collab_stmt = $db->prepare($collab_query);
                                    $collab_stmt->bindParam(':project_id', $project_id);
                                    $collab_stmt->bindParam(':collaborator_id', $collaborator_id);
                                    $collab_stmt->execute();
                                    
                                    // Create notification for collaborator
                                    $notification_query = "INSERT INTO notifications (user_id, type, title, message, related_id) 
                                                          VALUES (:user_id, 'collaboration_request', 'Collaboration Request', 
                                                                 :message, :project_id)";
                                    $notification_stmt = $db->prepare($notification_query);
                                    $message = "You have been invited to collaborate on '{$title}' by " . $_SESSION['full_name'];
                                    $notification_stmt->bindParam(':user_id', $collaborator_id);
                                    $notification_stmt->bindParam(':message', $message);
                                    $notification_stmt->bindParam(':project_id', $project_id);
                                    $notification_stmt->execute();
                                }
                            }
                        }
                    }
                }
                
                // Commit transaction
                $db->commit();
                
                $success_message = 'Project added successfully!';
                if (!empty($collaborators)) {
                    $success_message .= ' Collaboration requests have been sent.';
                }
                
                // Redirect to project view after 2 seconds
                header("refresh:2;url=../../project.php?id={$project_id}");
            } else {
                $db->rollback();
                $error_message = 'Failed to add project. Please try again.';
            }
        } catch (Exception $e) {
            $db->rollback();
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
    <title>Add New Project - <?php echo APP_NAME; ?></title>
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
                        <p>Add New Project</p>
                    </div>
                </div>
                
                <nav class="nav">
                    <a href="../" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        Back to Dashboard
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="dashboard-main add-project-page">
        <div class="container">
            <div class="page-header">
                <h1>Add New Project</h1>
                <p>Share your innovative project with the community</p>
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
                <!-- Left: Project Form -->
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
                                           value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>"
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
                                              placeholder="Brief description of your project (will be shown in project listings)"><?php echo isset($_POST['short_description']) ? htmlspecialchars($_POST['short_description']) : ''; ?></textarea>
                                    <small class="form-help">Maximum 500 characters</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="long_description">
                                        <i class="fas fa-file-alt"></i>
                                        Detailed Description *
                                    </label>
                                    <textarea id="long_description" name="long_description" required 
                                              rows="8" placeholder="Provide a detailed description of your project, including objectives, methodology, technologies used, and results"><?php echo isset($_POST['long_description']) ? htmlspecialchars($_POST['long_description']) : ''; ?></textarea>
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
                                                <?php foreach ($branches as $code => $branch): ?>
                                                <option value="<?php echo $code; ?>" 
                                                        <?php echo (isset($_POST['branch']) && $_POST['branch'] === $code) ? 'selected' : ''; ?>>
                                                    <?php echo $code; ?> - <?php echo $branch['name']; ?>
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
                                                <option value="">Select Branch First</option>
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
                                           value="<?php echo isset($_POST['github_link']) ? htmlspecialchars($_POST['github_link']) : ''; ?>"
                                           placeholder="https://github.com/username/repository">
                                    <small class="form-help">Link to your project's GitHub repository</small>
                                </div>
                                
                                <div class="form-group">
                                    <label>
                                        <i class="fas fa-users"></i>
                                        Initial Collaborators (Optional)
                                    </label>
                                    <div class="collaborator-inputs">
                                        <div class="collaborator-input-group">
                                            <input type="text" name="collaborators[]" placeholder="Enter username" class="collaborator-input">
                                            <button type="button" class="btn btn-outline btn-sm remove-collaborator" style="display: none;">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-outline btn-sm add-collaborator">
                                        <i class="fas fa-plus"></i>
                                        Add Collaborator
                                    </button>
                                    <small class="form-help">You can add up to 5 collaborators. They will receive collaboration requests after the project is created.</small>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-plus"></i>
                                        Add Project
                                    </button>
                                    <a href="../" class="btn btn-secondary btn-lg">
                                        <i class="fas fa-times"></i>
                                        Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Right Sidebar: Guidelines only -->
                <div class="col-4">
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
                                <h4><i class="fas fa-shield-alt"></i> Project Standards</h4>
                                <ul>
                                    <li>Projects must be original work</li>
                                    <li>Include proper documentation</li>
                                    <li>Follow ethical guidelines</li>
                                    <li>Respect intellectual property</li>
                                </ul>
                                <h4><i class="fas fa-users"></i> Collaboration</h4>
                                <p>After creating your project, you can invite other students to collaborate. Look for the collaboration option in your project dashboard.</p>
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
        
        // Collaborator management
        document.addEventListener('DOMContentLoaded', function() {
            const addCollaboratorBtn = document.querySelector('.add-collaborator');
            const collaboratorInputs = document.querySelector('.collaborator-inputs');
            const maxCollaborators = 5;
            
            if (addCollaboratorBtn) {
                addCollaboratorBtn.addEventListener('click', function() {
                    const currentInputs = collaboratorInputs.querySelectorAll('.collaborator-input-group');
                    
                    if (currentInputs.length < maxCollaborators) {
                        const newInputGroup = document.createElement('div');
                        newInputGroup.className = 'collaborator-input-group';
                        newInputGroup.innerHTML = `
                            <input type="text" name="collaborators[]" placeholder="Enter username" class="collaborator-input">
                            <button type="button" class="btn btn-outline btn-sm remove-collaborator">
                                <i class="fas fa-times"></i>
                            </button>
                        `;
                        
                        collaboratorInputs.appendChild(newInputGroup);
                        
                        // Show remove buttons if more than one input
                        if (currentInputs.length >= 1) {
                            document.querySelectorAll('.remove-collaborator').forEach(btn => {
                                btn.style.display = 'inline-block';
                            });
                        }
                        
                        // Hide add button if max reached
                        if (currentInputs.length >= maxCollaborators - 1) {
                            addCollaboratorBtn.style.display = 'none';
                        }
                        
                        // Focus on new input
                        newInputGroup.querySelector('.collaborator-input').focus();
                    }
                });
                
                // Handle remove collaborator
                collaboratorInputs.addEventListener('click', function(e) {
                    if (e.target.closest('.remove-collaborator')) {
                        const inputGroup = e.target.closest('.collaborator-input-group');
                        inputGroup.remove();
                        
                        const remainingInputs = collaboratorInputs.querySelectorAll('.collaborator-input-group');
                        
                        // Show/hide remove buttons
                        if (remainingInputs.length <= 1) {
                            document.querySelectorAll('.remove-collaborator').forEach(btn => {
                                btn.style.display = 'none';
                            });
                        }
                        
                        // Show add button if under max
                        if (remainingInputs.length < maxCollaborators) {
                            addCollaboratorBtn.style.display = 'inline-block';
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>
