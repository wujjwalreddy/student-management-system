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

// Get course ID from request
$courseId = isset($_GET['id']) ? sanitize($_GET['id']) : '';

if (empty($courseId)) {
    echo json_encode(['success' => false, 'message' => 'Course ID is required']);
    exit();
}

try {
    // Begin transaction
    $conn->begin_transaction();
    
    // Get course details
    $query = "SELECT course_name FROM courses WHERE id = ? OR course_code = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $courseId, $courseId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Course not found']);
        exit();
    }
    
    $course = $result->fetch_assoc();
    $courseName = $course['course_name'];
    
    // Delete course
    $query = "DELETE FROM courses WHERE id = ? OR course_code = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $courseId, $courseId);
    $stmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    // Log the activity
    logActivity($_SESSION['user_id'], "Deleted course: $courseName");
    
    echo json_encode(['success' => true, 'message' => 'Course deleted successfully']);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    error_log("Delete course error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to delete course: ' . getErrorMessage($e)]);
}
?>

