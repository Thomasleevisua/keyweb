<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // Your MySQL username
define('DB_PASS', '');     // Your MySQL password
define('DB_NAME', 'key_manager');

// Test database connection
try {
    $test_conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($test_conn->connect_error) {
        throw new Exception("Connection failed: " . $test_conn->connect_error);
    }
    $test_conn->close();
} catch (Exception $e) {
    die("Database configuration error: " . $e->getMessage());
}

// Key Configuration
define('VIP_KEY_PREFIX', 'THOMAS_VIP_');    // Prefix for VIP keys
define('FREE_KEY_PREFIX', 'THOMAS_FREE_');   // Prefix for FREE keys
define('ADMIN_KEY_PREFIX', 'ADMIN_');        // Prefix for ADMIN keys
define('KEY_LENGTH', 8);              // Length of random part in keys

// Key Expiry Configuration
define('VIP_KEY_EXPIRY_DAYS', 30);   // Default expiry days for VIP keys
define('FREE_KEY_EXPIRY_DAYS', 1);   // Default expiry days for FREE keys
define('MAX_FREE_KEYS_PER_DAY', 3);  // Maximum FREE keys per IP per day

// Security Settings
define('ENABLE_API_AUTH', true);     // Require authentication for API calls
define('API_TOKEN', 'THOMAS_API_hBEz8ycW46xqHvKTzq4x'); // API authentication token
define('MAX_LOGIN_ATTEMPTS', 3);     // Maximum failed login attempts

// System Settings
define('TIMEZONE', 'Asia/Ho_Chi_Minh');
define('DEBUG_MODE', false);          // Enable/disable debug messages
define('LOG_ERRORS', true);          // Enable error logging
define('ERROR_LOG_FILE', 'errors.log');

// Set timezone
date_default_timezone_set(TIMEZONE);

// Error reporting based on debug mode
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}