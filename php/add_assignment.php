<?php
require_once 'config.php';

// Check if user is logged in and has faculty role
if (!hasRole('faculty')) {
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
$courseId = isset($_POST['courseId']) ? sanitize($_POST['courseId']) : '';
$title = isset($_POST['title']) ? sanitize($_POST['title']) : '';
$description = isset($_POST['description']) ? sanitize($_POST['description']) : '';
$dueDate = isset($_POST['dueDate']) ? sanitize($_POST['dueDate']) : '';
$totalMarks = isset($_POST['totalMarks']) ? (int)$_POST['totalMarks'] : 0;

// Validate input
if (empty($courseId) || empty($title) || empty($dueDate) || $totalMarks <= 0) {
    echo json_encode(['success' => false, 'message' => 'Required fields are missing']);
    exit();
}

try {
    // Begin transaction
    $conn->begin_transaction();
    
    // Get faculty ID
    $facultyId = 0;
    $query = "SELECT id FROM faculty WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $faculty = $result->fetch_assoc();
        $facultyId = $faculty['id'];
    } else {
        echo json_encode(['success' => false, 'message' => 'Faculty not found']);
        exit();
    }
    
    // Check if faculty teaches this course
    $query = "SELECT id FROM course_instructors WHERE course_id = ? AND faculty_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $courseId, $facultyId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'You are not authorized to add assignments for this course']);
        exit();
    }
    
    // Insert assignment into database
    $query = "INSERT INTO assignments (course_id, title, description, due_date, total_marks, created_by, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("issiii", $courseId, $title, $description, $dueDate, $totalMarks, $facultyId);
    $stmt->execute();
    
    $assignmentId = $conn->insert_id;
    
    // Commit transaction
    $conn->commit();
    
    // Log the activity
    logActivity($_SESSION['user_id'], "Added new assignment: $title for course ID: $courseId");
    
    echo json_encode([
        'success' => true, 
        'message' => 'Assignment added successfully',
        'assignment' => [
            'id' => $assignmentId,
            'title' => $title,
            'dueDate' => $dueDate
        ]
    ]);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    error_log("Add assignment error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to add assignment: ' . getErrorMessage($e)]);
}
?>

