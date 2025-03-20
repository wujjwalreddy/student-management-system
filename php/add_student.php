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
$dob = isset($_POST['dob']) ? sanitize($_POST['dob']) : '';
$gender = isset($_POST['gender']) ? sanitize($_POST['gender']) : '';
$address = isset($_POST['address']) ? sanitize($_POST['address']) : '';
$course = isset($_POST['course']) ? sanitize($_POST['course']) : '';
$enrollmentDate = isset($_POST['enrollmentDate']) ? sanitize($_POST['enrollmentDate']) : '';
$status = isset($_POST['status']) ? sanitize($_POST['status']) : '';

// Validate input
if (empty($firstName) || empty($lastName) || empty($email) || empty($course) || empty($enrollmentDate) || empty($status)) {
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
            VALUES (?, ?, ?, ?, 'student', ?, NOW())";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssss", $fullname, $email, $username, $password, $status);
    $stmt->execute();
    
    $userId = $conn->insert_id;
    
    // Generate student ID
    $studentId = 'S' . str_pad($userId, 4, '0', STR_PAD_LEFT);
    
    // Insert student into database
    $query = "INSERT INTO students (user_id, student_id, name, email, phone, dob, gender, address, enrollment_date, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("isssssssss", $userId, $studentId, $fullname, $email, $phone, $dob, $gender, $address, $enrollmentDate, $status);
    $stmt->execute();
    
    // Get course ID
    $query = "SELECT id FROM courses WHERE course_code = ? OR id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $course, $course);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $courseData = $result->fetch_assoc();
        $courseId = $courseData['id'];
        
        // Enroll student in course
        $query = "INSERT INTO enrollments (student_id, course_id, enrollment_date, status) 
                VALUES (?, ?, ?, 'active')";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iis", $userId, $courseId, $enrollmentDate);
        $stmt->execute();
    }
    
    // Commit transaction
    $conn->commit();
    
    // Log the activity
    logActivity($_SESSION['user_id'], "Added new student: $fullname");
    
    echo json_encode([
        'success' => true, 
        'message' => 'Student added successfully',
        'student' => [
            'id' => $studentId,
            'name' => $fullname,
            'email' => $email,
            'username' => $username,
            'password' => $password // Send the plain password in the response so admin can share it
        ]
    ]);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    error_log("Add student error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to add student: ' . getErrorMessage($e)]);
}
?>

