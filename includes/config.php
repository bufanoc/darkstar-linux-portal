<?php
// Configuration file for Terminal Portal

// Database configuration
define('DB_PATH', '/opt/terminal-landing/data/users.db');

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
ini_set('session.cookie_samesite', 'Strict');

// Security settings
define('PASSWORD_COST', 12); // bcrypt cost factor
define('SESSION_TIMEOUT', 3600); // 1 hour

// Site configuration
define('SITE_NAME', 'Terminal Portal');
define('SITE_URL', 'http://localhost'); // Update with your actual URL

// Admin configuration
define('ADMIN_SESSION_KEY', 'admin_logged_in');
define('USER_SESSION_KEY', 'user_logged_in');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CSRF Token generation
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Helper function to verify CSRF token
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Helper function to get CSRF token
function get_csrf_token() {
    return $_SESSION['csrf_token'] ?? '';
}
