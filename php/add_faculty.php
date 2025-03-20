<?php
require_once 'config.php';

// Check if user is logged in and has admin role
if (!hasRole('admin')) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Set header to return JSON
header('Content-Type: application/json');

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get and sanitize input data
$firstName = isset($_POST['firstName']) ? sanitize($_POST['firstName']) : '';
$lastName = isset($_POST['lastName']) ? sanitize($_POST['lastName']) : '';
$email = isset($_POST['email']) ? sanitize($_POST['email']) : '';
$phone = isset($_POST['phone']) ? sanitize($_POST['phone']) : '';
$department = isset($_POST['department']) ? sanitize($_POST['department']) : '';
$designation = isset($_POST['designation']) ? sanitize($_POST['designation']) : '';
$qualification = isset($_POST['qualification']) ? sanitize($_POST['qualification']) : '';
$joiningDate = isset($_POST['joiningDate']) ? sanitize($_POST['joiningDate']) : '';
$address = isset($_POST['address']) ? sanitize($_POST['address']) : '';
$status = isset($_POST['status']) ? sanitize($_POST['status']) : '';

// Validate input
if (empty($firstName) || empty($lastName) || empty($email) || empty($department) || empty($designation) || empty($status)) {
    echo json_encode(['success' => false, 'message' => 'Required fields are missing']);
    exit();
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit();
}

try {
    // Begin transaction
    $conn->begin_transaction();
    
    // Generate a random username and password
    $username = strtolower($firstName . '.' . $lastName . rand(100, 999));
    $password = generateRandomString(8); // Plain text password
    $fullname = $firstName . ' ' . $lastName;
    
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
    
    // Insert user into database with plain password
    $query = "INSERT INTO users (fullname, email, username, password, role, status, created_at) 
            VALUES (?, ?, ?, ?, 'faculty', ?, NOW())";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssss", $fullname, $email, $username, $password, $status);
    $stmt->execute();
    
    $userId = $conn->insert_id;
    
    // Generate faculty ID
    $facultyId = 'F' . str_pad($userId, 4, '0', STR_PAD_LEFT);
    
    // Insert faculty into database
    $query = "INSERT INTO faculty (user_id, faculty_id, name, email, phone, department, designation, qualification, joining_date, address, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("issssssssss", $userId, $facultyId, $fullname, $email, $phone, $department, $designation, $qualification, $joiningDate, $address, $status);
    $stmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    // Log the activity
    logActivity($_SESSION['user_id'], "Added new faculty: $fullname");
    
    echo json_encode([
        'success' => true, 
        'message' => 'Faculty added successfully',
        'faculty' => [
            'id' => $facultyId,
            'name' => $fullname,
            'email' => $email,
            'username' => $username,
            'password' => $password // Send the plain password in the response so admin can share it
        ]
    ]);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    error_log("Add faculty error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to add faculty: ' . getErrorMessage($e)]);
}
?>

