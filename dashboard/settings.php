<?php
/**
 * User Settings Page
 * Project Vault - Dr. YC James Yen Government Polytechnic, Kuppam
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('auth/login.php');
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// Get user information
try {
    $query = "SELECT * FROM users WHERE id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user = $stmt->fetch();
} catch (Exception $e) {
    $error_message = 'Error loading user information.';
}

$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_profile':
            updateProfile($db, $user_id);
            break;
        case 'change_password':
            changePassword($db, $user_id);
            break;
        case 'delete_account':
            deleteAccount($db, $user_id);
            break;
    }
}

/**
 * Update user profile
 */
function updateProfile($db, $user_id) {
    global $success_message, $error_message;
    
    $full_name = sanitize_input($_POST['full_name']);
    $email = sanitize_input($_POST['email']);
    $roll_number = isset($_POST['roll_number']) ? sanitize_input($_POST['roll_number']) : '';
    $branch = isset($_POST['branch']) ? sanitize_input($_POST['branch']) : '';
    
    if (empty($full_name) || empty($email)) {
        $error_message = 'Full name and email are required.';
        return;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
        return;
    }
    
    try {
        // Check if email is already taken by another user
        $check_query = "SELECT id FROM users WHERE email = :email AND id != :user_id";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':email', $email);
        $check_stmt->bindParam(':user_id', $user_id);
        $check_stmt->execute();
        
        if ($check_stmt->fetch()) {
            $error_message = 'Email address is already taken.';
            return;
        }
        
        // Update user profile
        $query = "UPDATE users SET full_name = :full_name, email = :email, roll_number = :roll_number, branch = :branch WHERE id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':full_name', $full_name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':roll_number', $roll_number);
        $stmt->bindParam(':branch', $branch);
        $stmt->bindParam(':user_id', $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['full_name'] = $full_name;
            $success_message = 'Profile updated successfully!';
        } else {
            $error_message = 'Failed to update profile.';
        }
    } catch (Exception $e) {
        $error_message = 'An error occurred while updating profile.';
    }
}

/**
 * Change password
 */
function changePassword($db, $user_id) {
    global $success_message, $error_message;
    
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = 'All password fields are required.';
        return;
    }
    
    if ($new_password !== $confirm_password) {
        $error_message = 'New passwords do not match.';
        return;
    }
    
    if (strlen($new_password) < PASSWORD_MIN_LENGTH) {
        $error_message = 'New password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long.';
        return;
    }
    
    try {
        // Get current password
        $query = "SELECT password FROM users WHERE id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $user = $stmt->fetch();
        
        if (!password_verify($current_password, $user['password'])) {
            $error_message = 'Current password is incorrect.';
            return;
        }
        
        // Update password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_query = "UPDATE users SET password = :password WHERE id = :user_id";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bindParam(':password', $hashed_password);
        $update_stmt->bindParam(':user_id', $user_id);
        
        if ($update_stmt->execute()) {
            $success_message = 'Password changed successfully!';
        } else {
            $error_message = 'Failed to change password.';
        }
    } catch (Exception $e) {
        $error_message = 'An error occurred while changing password.';
    }
}

/**
 * Delete account
 */
function deleteAccount($db, $user_id) {
    global $success_message, $error_message;
    
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($confirm_password)) {
        $error_message = 'Please enter your password to confirm account deletion.';
        return;
    }
    
    try {
        // Verify password
        $query = "SELECT password FROM users WHERE id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $user = $stmt->fetch();
        
        if (!password_verify($confirm_password, $user['password'])) {
            $error_message = 'Password is incorrect.';
            return;
        }
        
        // Delete user (this will cascade delete all related data)
        $delete_query = "DELETE FROM users WHERE id = :user_id";
        $delete_stmt = $db->prepare($delete_query);
        $delete_stmt->bindParam(':user_id', $user_id);
        
        if ($delete_stmt->execute()) {
            // Destroy session and redirect
            session_destroy();
            redirect('auth/login.php?deleted=1');
        } else {
            $error_message = 'Failed to delete account.';
        }
    } catch (Exception $e) {
        $error_message = 'An error occurred while deleting account.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="dashboard-body">
    <!-- Header -->
    <header class="dashboard-header">
        <div class="container">
            <div class="header-content">
                <div class="logo-section">
                    <img src="../assets/images/polytechnic_logo.jpg" alt="College Logo" class="logo">
                    <div class="logo-text">
                        <h1><?php echo APP_NAME; ?></h1>
                        <p>Settings</p>
                    </div>
                </div>
                
                <nav class="nav">
                    <ul class="nav-links">
                        <li><a href="../index.php">Home</a></li>
                        <li><a href="../projects/">Projects</a></li>
                        <li><a href="index.php">Dashboard</a></li>
                        <li><a href="settings.php" class="active">Settings</a></li>
                    </ul>
                    
                    <div class="user-menu">
                        <div class="user-dropdown">
                            <button class="user-button">
                                <i class="fas fa-user-circle"></i>
                                <?php echo htmlspecialchars($user['full_name']); ?>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="dropdown-menu">
                                <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
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
            <div class="page-header">
                <h1>Account Settings</h1>
                <p>Manage your account information and preferences</p>
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
                </div>
            <?php endif; ?>
            
            <div class="row two-col-fixed">
                <div class="col-8">
                    <!-- Profile Settings -->
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-user"></i> Profile Information</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST" class="settings-form">
                                <input type="hidden" name="action" value="update_profile">
                                
                                <div class="form-group">
                                    <label for="full_name">
                                        <i class="fas fa-id-card"></i>
                                        Full Name *
                                    </label>
                                    <input type="text" id="full_name" name="full_name" required 
                                           value="<?php echo htmlspecialchars($user['full_name']); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="email">
                                        <i class="fas fa-envelope"></i>
                                        Email Address *
                                    </label>
                                    <input type="email" id="email" name="email" required 
                                           value="<?php echo htmlspecialchars($user['email']); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="username">
                                        <i class="fas fa-user"></i>
                                        Username
                                    </label>
                                    <input type="text" id="username" name="username" 
                                           value="<?php echo htmlspecialchars($user['username']); ?>" 
                                           disabled>
                                    <small class="form-help">Username cannot be changed</small>
                                </div>
                                
                                <?php if ($user['role'] === 'student'): ?>
                                <div class="form-group">
                                    <label for="roll_number">
                                        <i class="fas fa-hashtag"></i>
                                        Roll Number
                                    </label>
                                    <input type="text" id="roll_number" name="roll_number" 
                                           value="<?php echo htmlspecialchars($user['roll_number']); ?>" 
                                           maxlength="12">
                                </div>
                                
                                <div class="form-group">
                                    <label for="branch">
                                        <i class="fas fa-graduation-cap"></i>
                                        Branch
                                    </label>
                                    <select id="branch" name="branch">
                                        <option value="">Select Branch</option>
                                        <option value="DCME" <?php echo ($user['branch'] === 'DCME') ? 'selected' : ''; ?>>DCME - Computer Engineering</option>
                                        <option value="DEEE" <?php echo ($user['branch'] === 'DEEE') ? 'selected' : ''; ?>>DEEE - Electrical & Electronics</option>
                                        <option value="DME" <?php echo ($user['branch'] === 'DME') ? 'selected' : ''; ?>>DME - Mechanical Engineering</option>
                                        <option value="DECE" <?php echo ($user['branch'] === 'DECE') ? 'selected' : ''; ?>>DECE - Electronics & Communication</option>
                                    </select>
                                </div>
                                <?php endif; ?>
                                
                                <div class="form-group">
                                    <label>
                                        <i class="fas fa-user-tag"></i>
                                        Role
                                    </label>
                                    <input type="text" value="<?php echo ucfirst($user['role']); ?>" disabled>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i>
                                    Update Profile
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Password Change -->
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-lock"></i> Change Password</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST" class="settings-form">
                                <input type="hidden" name="action" value="change_password">
                                
                                <div class="form-group">
                                    <label for="current_password">
                                        <i class="fas fa-key"></i>
                                        Current Password *
                                    </label>
                                    <input type="password" id="current_password" name="current_password" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="new_password">
                                        <i class="fas fa-lock"></i>
                                        New Password *
                                    </label>
                                    <input type="password" id="new_password" name="new_password" required 
                                           minlength="<?php echo PASSWORD_MIN_LENGTH; ?>">
                                    <small class="form-help">Minimum <?php echo PASSWORD_MIN_LENGTH; ?> characters</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="confirm_password">
                                        <i class="fas fa-lock"></i>
                                        Confirm New Password *
                                    </label>
                                    <input type="password" id="confirm_password" name="confirm_password" required>
                                </div>
                                
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-key"></i>
                                    Change Password
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-4">
                    <!-- Account Information -->
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-info-circle"></i> Account Information</h3>
                        </div>
                        <div class="card-body">
                            <div class="info-list">
                                <div class="info-item">
                                    <span class="info-label">Member Since:</span>
                                    <span class="info-value"><?php echo date('M j, Y', strtotime($user['created_at'])); ?></span>
                                </div>
                                
                                <div class="info-item">
                                    <span class="info-label">Last Updated:</span>
                                    <span class="info-value"><?php echo date('M j, Y g:i A', strtotime($user['updated_at'])); ?></span>
                                </div>
                                
                                <div class="info-item">
                                    <span class="info-label">Account Status:</span>
                                    <span class="info-value status-<?php echo $user['status']; ?>">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </div>
                                
                                <div class="info-item">
                                    <span class="info-label">User Role:</span>
                                    <span class="info-value"><?php echo ucfirst($user['role']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Danger Zone -->
                    <div class="card danger-card">
                        <div class="card-header">
                            <h3><i class="fas fa-exclamation-triangle"></i> Danger Zone</h3>
                        </div>
                        <div class="card-body">
                            <div class="danger-section">
                                <h4>Delete Account</h4>
                                <p>Once you delete your account, there is no going back. This will permanently delete your account and all associated data including projects, comments, and collaborations.</p>
                                
                                <button class="btn btn-danger" onclick="showDeleteModal()">
                                    <i class="fas fa-trash"></i>
                                    Delete Account
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Delete Account Modal -->
    <div class="modal" id="deleteModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-exclamation-triangle"></i> Delete Account</h3>
                <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p><strong>Warning:</strong> This action cannot be undone. This will permanently delete your account and remove all your data from our servers.</p>
                
                <form method="POST" class="delete-form">
                    <input type="hidden" name="action" value="delete_account">
                    
                    <div class="form-group">
                        <label for="confirm_password_modal">
                            <i class="fas fa-key"></i>
                            Enter your password to confirm *
                        </label>
                        <input type="password" id="confirm_password_modal" name="confirm_password" required>
                    </div>
                    
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash"></i>
                            Delete Account
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        function showDeleteModal() {
            document.getElementById('deleteModal').style.display = 'block';
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }
        
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword && newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('deleteModal');
            if (event.target === modal) {
                closeDeleteModal();
            }
        }
    </script>
</body>
</html>
