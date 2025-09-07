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

// Base URL utility (best-effort; can be overridden by APP_BASE_URL)
if (!defined('APP_BASE_URL')) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = rtrim(str_replace('index.php', '', $_SERVER['SCRIPT_NAME'] ?? '/'), '/');
    $computedBase = $scheme . '://' . $host . $scriptDir;
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

?>

