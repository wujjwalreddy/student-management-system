<?php
require_once 'config.php';

// Set header to return JSON
header('Content-Type: application/json');

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get and sanitize input data
$username = isset($_POST['username']) ? sanitize($_POST['username']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$role = isset($_POST['role']) ? sanitize($_POST['role']) : '';

// Validate input
if (empty($username) || empty($password) || empty($role)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit();
}

// Check if role is valid
$validRoles = ['admin', 'faculty', 'student'];
if (!in_array($role, $validRoles)) {
    echo json_encode(['success' => false, 'message' => 'Invalid role']);
    exit();
}

try {
    // Query to check user credentials with direct password comparison
    $query = "SELECT id, username, password, fullname, email, role, status FROM users WHERE username = ? AND password = ? AND role = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sss", $username, $password, $role);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid username, password, or role']);
        exit();
    }

    $user = $result->fetch_assoc();

    // Check if user is active
    if ($user['status'] !== 'active') {
        echo json_encode(['success' => false, 'message' => 'Your account is not active. Please contact administrator.']);
        exit();
    }

    // Set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['fullname'] = $user['fullname'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];

    // Log the login activity
    logActivity($user['id'], 'User logged in');

    // Return success response
    echo json_encode([
        'success' => true, 
        'message' => 'Login successful', 
        'role' => $user['role'],
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'fullname' => $user['fullname'],
            'email' => $user['email']
        ]
    ]);
} catch (Exception $e) {
    // Log the error
    error_log("Login error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred during login. Please try again.']);
}
?>

