<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Correct BASE_URL that works regardless of current page depth
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];

// Fix for determining the base path
$script_name = $_SERVER['SCRIPT_NAME'];
$project_root = '/ecommerce-game-store'; // Set this to match your project folder
define('BASE_URL', $protocol . $host . $project_root);

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'simahang90');
define('DB_NAME', 'game_store');

// Global database connection
$GLOBALS['db_connection'] = null;

// Create database connection
function connectDB() {
    // If connection already exists, return it
    if (isset($GLOBALS['db_connection']) && $GLOBALS['db_connection'] instanceof mysqli) {
        return $GLOBALS['db_connection'];
    }

    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        // Store the connection globally
        $GLOBALS['db_connection'] = $conn;
        return $conn;
    } catch (Exception $e) {
        error_log("Database connection error: " . $e->getMessage());
        return false;
    }
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper function to sanitize input
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

// Helper function to redirect
function redirect($path) {
    $path = ltrim($path, '/');
    header("Location: " . BASE_URL . '/' . $path);
    exit();
}

// Flash message helper
function setFlashMessage($type, $message) {
    $_SESSION['flash_messages'][$type] = $message;
}

function getFlashMessage($type) {
    if (isset($_SESSION['flash_messages'][$type])) {
        $message = $_SESSION['flash_messages'][$type];
        unset($_SESSION['flash_messages'][$type]);
        return $message;
    }
    return null;
}

// Helper function to format price in IDR
function formatIDR($price) {
    return 'Rp ' . number_format($price, 0, ',', '.');
}

// Get database connection
function getDbConnection() {
    global $conn;
    
    // Check if connection is already established and valid
    if (!isset($conn) || !$conn || $conn->connect_error) {
        // Create a new connection
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        
        // Set charset
        $conn->set_charset("utf8mb4");
    }
    
    return $conn;
}

// Close database connection if it exists
function closeDbConnection() {
    global $conn;
    
    // Only try to close if connection exists and is still active
    if (isset($conn) && is_object($conn)) {
        try {
            // Skip ping() check which causes the error
            $conn->close();
        } catch (Exception $e) {
            // Silently handle any errors during closing
        }
    }
}

// Register shutdown function to ensure connection is closed
register_shutdown_function('closeDbConnection');

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin() {
    if (!isLoggedIn()) return false;
    
    $conn = getDbConnection();
    $user_id = getCurrentUserId();
    
    $stmt = $conn->prepare("SELECT is_admin FROM users WHERE id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    return $user['is_admin'] ?? false;
}

// Get current user ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Get current username
function getCurrentUsername() {
    return $_SESSION['username'] ?? null;
}

// Require login
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        setFlashMessage('warning', 'Please log in to continue.');
        redirect('login.php');
    }
}

// Logout user
function logout() {
    unset($_SESSION['user_id']);
    unset($_SESSION['username']);
    session_destroy();
    session_start();
    setFlashMessage('success', 'You have been logged out successfully.');
    redirect('/pages/login.php');
}
?>