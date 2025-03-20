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
$fullname = isset($_POST['fullname']) ? sanitize($_POST['fullname']) : '';
$email = isset($_POST['email']) ? sanitize($_POST['email']) : '';
$username = isset($_POST['username']) ? sanitize($_POST['username']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$role = isset($_POST['role']) ? sanitize($_POST['role']) : '';

// Validate input
if (empty($fullname) || empty($email) || empty($username) || empty $password) || empty($role)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit();
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit();
}

// Check if role is valid
$validRoles = ['student', 'faculty'];
if (!in_array($role, $validRoles)) {
    echo json_encode(['success' => false, 'message' => 'Invalid role']);
    exit();
}

try {
    // Check if username already exists
    $query = "SELECT id FROM users WHERE username = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Username already exists']);
        exit();
    }

    // Check if email already exists
    $query = "SELECT id FROM users WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already exists']);
        exit();
    }

    // Begin transaction
    $conn->begin_transaction();

    // Insert user into database with plain password
    $query = "INSERT INTO users (fullname, email, username, password, role, status, created_at) 
            VALUES (?, ?, ?, ?, ?, 'active', NOW())";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssss", $fullname, $email, $username, $password, $role);
    $stmt->execute();
    
    $userId = $conn->insert_id;
    
    // Create profile based on role
    if ($role === 'student') {
        $studentId = 'S' . str_pad($userId, 4, '0', STR_PAD_LEFT);
        $query = "INSERT INTO students (user_id, student_id, name, email) 
                VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("isss", $userId, $studentId, $fullname, $email);
        $stmt->execute();
    } else if ($role === 'faculty') {
        $facultyId = 'F' . str_pad($userId, 4, '0', STR_PAD_LEFT);
        $query = "INSERT INTO faculty (user_id, faculty_id, name, email) 
                VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("isss", $userId, $facultyId, $fullname, $email);
        $stmt->execute();
    }
    
    // Commit transaction
    $conn->commit();
    
    // Log the registration activity
    logActivity($userId, 'User registered');
    
    echo json_encode(['success' => true, 'message' => 'Registration successful! Please login.']);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    error_log("Registration error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Registration failed: ' . getErrorMessage($e)]);
}
?>

