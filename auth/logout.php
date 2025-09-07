<?php
/**
 * Logout Page
 * Project Vault - Dr. YC James Yen Government Polytechnic, Kuppam
 */

require_once '../config/config.php';

// Destroy session
session_destroy();

// Clear session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Redirect to provided internal target or home
$redirect_to = isset($_GET['redirect_to']) ? $_GET['redirect_to'] : '/';
if (!is_safe_internal_path($redirect_to)) {
    $redirect_to = '/';
}
redirect($redirect_to);
?>
