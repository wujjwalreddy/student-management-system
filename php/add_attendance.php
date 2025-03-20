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
$date = isset($_POST['date']) ? sanitize($_POST['date']) : '';
$attendanceData = isset($_POST['attendance']) ? $_POST['attendance'] : [];

// Validate input
if (empty($courseId) || empty($date) || empty($attendanceData)) {
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
        echo json_encode(['success' => false, 'message' => 'You are not authorized to mark attendance for this course']);
        exit();
    }
    
    // Delete existing attendance for this course and date
    $query = "DELETE FROM attendance WHERE course_id = ? AND date = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $courseId, $date);
    $stmt->execute();
    
    // Insert new attendance records
    $query = "INSERT INTO attendance (student_id, course_id, date, status, remarks, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($query);
    
    foreach ($attendanceData as $attendance) {
        $studentId = sanitize($attendance['studentId']);
        $status = sanitize($attendance['status']);
        $remarks = sanitize($attendance['remarks']);
        
        $stmt->bind_param("iisss", $studentId, $courseId, $date, $status, $remarks);
        $stmt->execute();
    }
    
    // Commit transaction
    $conn->commit();
    
    // Log the activity
    logActivity($_SESSION['user_id'], "Marked attendance for course ID: $courseId on date: $date");
    
    echo json_encode(['success' => true, 'message' => 'Attendance marked successfully']);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    error_log("Add attendance error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to mark attendance: ' . getErrorMessage($e)]);
}
?>

