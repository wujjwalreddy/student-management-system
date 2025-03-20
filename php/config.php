<?php
// Database configuration
$host = "localhost";
$username = "root";
$password = "";
$database = "student_management_system";

// Create database connection
try {
    $conn = new mysqli($host, $username, $password, $database);

    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Set character set
    $conn->set_charset("utf8");
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}

// Session configuration
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Function to check user role
function hasRole($role) {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

// Function to redirect user
function redirect($url) {
    header("Location: $url");
    exit();
}

// Function to sanitize input data
function sanitize($data) {
    global $conn;
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = sanitize($value);
        }
        return $data;
    }
    return $conn->real_escape_string(trim($data));
}

// Function to generate a random string
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

// Function to log activity
function logActivity($userId, $activity) {
    global $conn;
    $userId = (int)$userId;
    $activity = sanitize($activity);
    $timestamp = date('Y-m-d H:i:s');
    
    try {
        $query = "INSERT INTO activity_logs (user_id, activity, timestamp) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iss", $userId, $activity, $timestamp);
        $stmt->execute();
    } catch (Exception $e) {
        error_log("Activity log error: " . $e->getMessage());
    }
}

// Function to check if a table exists
function tableExists($tableName) {
    global $conn;
    $tableName = sanitize($tableName);
    $result = $conn->query("SHOW TABLES LIKE '$tableName'");
    return $result->num_rows > 0;
}

// Function to get user details
function getUserDetails($userId) {
    global $conn;
    $userId = (int)$userId;
    
    try {
        $query = "SELECT id, username, fullname, email, role, status FROM users WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return null;
        }
        
        return $result->fetch_assoc();
    } catch (Exception $e) {
        error_log("Get user details error: " . $e->getMessage());
        return null;
    }
}

// Function to get error message
function getErrorMessage($e) {
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        return $e->getMessage();
    }
    return "An error occurred. Please try again.";
}

// Set debug mode (change to false in production)
define('DEBUG_MODE', true);
?>

