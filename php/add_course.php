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
$courseCode = isset($_POST['courseCode']) ? sanitize($_POST['courseCode']) : '';
$courseName = isset($_POST['courseName']) ? sanitize($_POST['courseName']) : '';
$department = isset($_POST['department']) ? sanitize($_POST['department']) : '';
$credits = isset($_POST['credits']) ? (int)$_POST['credits'] : 0;
$instructor = isset($_POST['instructor']) ? sanitize($_POST['instructor']) : '';
$semester = isset($_POST['semester']) ? sanitize($_POST['semester']) : '';
$startDate = isset($_POST['startDate']) ? sanitize($_POST['startDate']) : '';
$endDate = isset($_POST['endDate']) ? sanitize($_POST['endDate']) : '';
$description = isset($_POST['description']) ? sanitize($_POST['description']) : '';

// Validate input
if (empty($courseCode) || empty($courseName) || empty($department) || $credits <= 0 || empty($instructor) || empty($semester)) {
    echo json_encode(['success' => false, 'message' => 'Required fields are missing']);
    exit();
}

try {
    // Begin transaction
    $conn->begin_transaction();
    
    // Check if course code already exists
    $query = "SELECT id FROM courses WHERE course_code = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $courseCode);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Course code already exists']);
        exit();
    }
    
    // Insert course into database
    $query = "INSERT INTO courses (course_code, course_name, department, credits, description, semester, start_date, end_date, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssisiss", $courseCode, $courseName, $department, $credits, $description, $semester, $startDate, $endDate);
    $stmt->execute();
    
    $courseId = $conn->insert_id;
    
    // Assign instructor to course
    $query = "INSERT INTO course_instructors (course_id, faculty_id) VALUES (?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $courseId, $instructor);
    $stmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    // Log the activity
    logActivity($_SESSION['user_id'], "Added new course: $courseName");
    
    echo json_encode([
        'success' => true, 
        'message' => 'Course added successfully',
        'course' => [
            'id' => $courseId,
            'code' => $courseCode,
            'name' => $courseName
        ]
    ]);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    error_log("Add course error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to add course: ' . getErrorMessage($e)]);
}
?>

