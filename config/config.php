<?php
/**
 * Global configuration and helpers
 * Project Vault - Dr. YC James Yen Government Polytechnic, Kuppam
 */

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Timezone
date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Asia/Kolkata');

// Application constants
define('APP_NAME', getenv('APP_NAME') ?: 'Project Vault');
define('PROJECTS_PER_PAGE', (int)(getenv('PROJECTS_PER_PAGE') ?: 12));
define('PASSWORD_MIN_LENGTH', (int)(getenv('PASSWORD_MIN_LENGTH') ?: 8));

// Base URL utility (best-effort; can be overridden by APP_BASE_URL)
if (!defined('APP_BASE_URL')) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $basePath = rtrim(getenv('APP_BASE_PATH') ?: '', '/');
    $computedBase = $scheme . '://' . $host . $basePath;
    define('APP_BASE_URL', getenv('APP_BASE_URL') ?: $computedBase);
}

// Branches catalogue used across the app and API
$branches = [
    'DCME' => [
        'name' => 'Computer Engineering',
        'types' => [
            'Web Development', 'Mobile Apps', 'Desktop Applications', 'Database Systems', 'Network Projects'
        ]
    ],
    'DEEE' => [
        'name' => 'Electrical & Electronics',
        'types' => [
            'IoT Projects', 'Power Systems', 'Control Systems', 'Renewable Energy', 'Electronics'
        ]
    ],
    'DME' => [
        'name' => 'Mechanical Engineering',
        'types' => [
            'CAD Projects', 'Manufacturing Systems', 'Robotics', 'Machine Design', 'Automation'
        ]
    ],
    'DECE' => [
        'name' => 'Electronics & Communication',
        'types' => [
            'Embedded Systems', 'Communication Systems', 'Signal Processing', 'VLSI', 'Digital Systems'
        ]
    ]
];

/**
 * Sanitize a string input for safe HTML/database usage
 */
function sanitize_input($value) {
    if ($value === null) {
        return '';
    }
    if (is_array($value)) {
        return array_map('sanitize_input', $value);
    }
    $trimmed = trim((string)$value);
    return filter_var($trimmed, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
}

/**
 * Check if the user is logged in
 */
function is_logged_in() {
    return !empty($_SESSION['user_id']);
}

/**
 * Role helpers
 */
function get_user_role() {
    return $_SESSION['user_role'] ?? null;
}

function is_admin() {
    return get_user_role() === 'admin';
}

function is_staff() {
    $role = get_user_role();
    return $role === 'staff' || $role === 'admin';
}

/**
 * Redirect helper and exit
 */
function redirect($path) {
    // If absolute URL, use directly; else, prefix with base URL if not starting with '/'
    if (preg_match('/^https?:\/\//i', $path)) {
        $url = $path;
    } else {
        if (strpos($path, '/') === 0) {
            $url = $path;
        } else {
            $base = rtrim(APP_BASE_URL, '/');
            $url = $base . '/' . ltrim($path, '/');
        }
    }
    header('Location: ' . $url);
    exit;
}

/**
 * Build a full URL for a relative application path
 */
function url($path) {
    if (preg_match('/^https?:\/\//i', $path)) {
        return $path;
    }
    $base = rtrim(APP_BASE_URL, '/');
    if (strpos($path, '/') !== 0) {
        $path = '/' . ltrim($path, '/');
    }
    return $base . $path;
}

/**
 * Determine if a path is a safe internal path (prevents open redirects)
 */
function is_safe_internal_path($path) {
    if ($path === null || $path === '') {
        return false;
    }
    if (preg_match('/^https?:\/\//i', $path)) {
        return false;
    }
    if (strpos($path, '//') === 0) { // protocol-relative URLs
        return false;
    }
    return $path[0] === '/';
}

/**
 * Get the current request path including query string
 */
function current_request_path() {
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    return $uri ?: '/';
}

/**
 * Build login URL with optional redirect_to
 */
function login_url($redirectTo = null) {
    $login = '/auth/login.php';
    if (!empty($redirectTo) && is_safe_internal_path($redirectTo)) {
        return $login . '?redirect_to=' . rawurlencode($redirectTo);
    }
    return $login;
}

/**
 * Require the user to be logged in; otherwise redirect to login with redirect_to
 */
function require_login($redirectTo = null) {
    if (is_logged_in()) {
        return;
    }
    $target = $redirectTo ?: current_request_path();
    if (!is_safe_internal_path($target)) {
        $target = '/dashboard/';
    }
    redirect(login_url($target));
}

/**
 * Require the user to be an admin; redirects to login or dashboard as appropriate
 */
function require_admin() {
    if (!is_logged_in()) {
        require_login();
        return;
    }
    if (!is_admin()) {
        redirect('/dashboard/');
    }
}

?>

