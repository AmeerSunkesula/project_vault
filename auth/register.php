<?php
/**
 * Registration Page
 * Project Vault - Dr. YC James Yen Government Polytechnic, Kuppam
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Redirect if already logged in
if (is_logged_in()) {
    redirect('/');
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username']);
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = sanitize_input($_POST['full_name']);
    $role = sanitize_input($_POST['role']);
    $roll_number = isset($_POST['roll_number']) ? sanitize_input($_POST['roll_number']) : '';
    $branch = isset($_POST['branch']) ? sanitize_input($_POST['branch']) : '';
    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($full_name) || empty($role)) {
        $error_message = 'Please fill in all required fields.';
    } elseif ($password !== $confirm_password) {
        $error_message = 'Passwords do not match.';
    } elseif (strlen($password) < PASSWORD_MIN_LENGTH) {
        $error_message = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long.';
    } elseif ($role === 'student' && (empty($roll_number) || empty($branch))) {
        $error_message = 'Roll number and branch are required for students.';
    } elseif ($role === 'student' && strlen($roll_number) !== 12) {
        $error_message = 'Roll number must be exactly 12 characters.';
    } else {
        try {
            // Check if username or email already exists
            $check_query = "SELECT id FROM users WHERE username = :username OR email = :email";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->bindParam(':username', $username);
            $check_stmt->bindParam(':email', $email);
            $check_stmt->execute();
            
            if ($check_stmt->rowCount() > 0) {
                $error_message = 'Username or email already exists.';
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Determine status based on role
                $status = ($role === 'staff') ? 'pending' : 'active';
                
                // Insert user
                $insert_query = "INSERT INTO users (username, email, password, full_name, roll_number, branch, role, status) 
                                VALUES (:username, :email, :password, :full_name, :roll_number, :branch, :role, :status)";
                $insert_stmt = $db->prepare($insert_query);
                $insert_stmt->bindParam(':username', $username);
                $insert_stmt->bindParam(':email', $email);
                $insert_stmt->bindParam(':password', $hashed_password);
                $insert_stmt->bindParam(':full_name', $full_name);
                $insert_stmt->bindParam(':roll_number', $roll_number);
                $insert_stmt->bindParam(':branch', $branch);
                $insert_stmt->bindParam(':role', $role);
                $insert_stmt->bindParam(':status', $status);
                
                if ($insert_stmt->execute()) {
                    // Redirect to home page after successful registration
                    redirect('/');
                } else {
                    $error_message = 'Registration failed. Please try again.';
                }
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
    <title>Register - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="auth-body">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <img src="../assets/images/polytechnic_logo.jpg" alt="College Logo" class="logo">
                <h1><?php echo APP_NAME; ?></h1>
                <p>Dr. YC James Yen Government Polytechnic, Kuppam</p>
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
            
            <form method="POST" class="auth-form" id="registerForm">
                <div class="form-group">
                    <label for="role">
                        <i class="fas fa-user-tag"></i>
                        Role *
                    </label>
                    <select id="role" name="role" required onchange="toggleStudentFields()">
                        <option value="">Select Role</option>
                        <option value="student" <?php echo (isset($_POST['role']) && $_POST['role'] === 'student') ? 'selected' : ''; ?>>Student</option>
                        <option value="staff" <?php echo (isset($_POST['role']) && $_POST['role'] === 'staff') ? 'selected' : ''; ?>>Staff</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user"></i>
                        Username *
                    </label>
                    <input type="text" id="username" name="username" required 
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i>
                        Email *
                    </label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="full_name">
                        <i class="fas fa-id-card"></i>
                        Full Name *
                    </label>
                    <input type="text" id="full_name" name="full_name" required 
                           value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                </div>
                
                <div class="form-group" id="rollNumberGroup" style="display: none;">
                    <label for="roll_number">
                        <i class="fas fa-hashtag"></i>
                        Roll Number (12 characters) *
                    </label>
                    <input type="text" id="roll_number" name="roll_number" maxlength="12" 
                           value="<?php echo isset($_POST['roll_number']) ? htmlspecialchars($_POST['roll_number']) : ''; ?>">
                </div>
                
                <div class="form-group" id="branchGroup" style="display: none;">
                    <label for="branch">
                        <i class="fas fa-graduation-cap"></i>
                        Branch *
                    </label>
                    <select id="branch" name="branch">
                        <option value="">Select Branch</option>
                        <option value="DCME" <?php echo (isset($_POST['branch']) && $_POST['branch'] === 'DCME') ? 'selected' : ''; ?>>DCME - Computer Engineering</option>
                        <option value="DEEE" <?php echo (isset($_POST['branch']) && $_POST['branch'] === 'DEEE') ? 'selected' : ''; ?>>DEEE - Electrical & Electronics</option>
                        <option value="DME" <?php echo (isset($_POST['branch']) && $_POST['branch'] === 'DME') ? 'selected' : ''; ?>>DME - Mechanical Engineering</option>
                        <option value="DECE" <?php echo (isset($_POST['branch']) && $_POST['branch'] === 'DECE') ? 'selected' : ''; ?>>DECE - Electronics & Communication</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i>
                        Password *
                    </label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">
                        <i class="fas fa-lock"></i>
                        Confirm Password *
                    </label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-full">
                    <i class="fas fa-user-plus"></i>
                    Register
                </button>
            </form>
            
            <div class="auth-footer">
                <p>Already have an account? <a href="login.php">Login here</a></p>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/main.js"></script>
    <script>
        function toggleStudentFields() {
            const role = document.getElementById('role').value;
            const rollNumberGroup = document.getElementById('rollNumberGroup');
            const branchGroup = document.getElementById('branchGroup');
            const rollNumber = document.getElementById('roll_number');
            const branch = document.getElementById('branch');
            
            if (role === 'student') {
                rollNumberGroup.style.display = 'block';
                branchGroup.style.display = 'block';
                rollNumber.required = true;
                branch.required = true;
            } else {
                rollNumberGroup.style.display = 'none';
                branchGroup.style.display = 'none';
                rollNumber.required = false;
                branch.required = false;
                rollNumber.value = '';
                branch.value = '';
            }
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            toggleStudentFields();
        });
    </script>
</body>
</html>
