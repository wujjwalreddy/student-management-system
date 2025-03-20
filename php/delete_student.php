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

// Get student ID from request
$studentId = isset($_GET['id']) ? sanitize($_GET['id']) : '';

if (empty($studentId)) {
    echo json_encode(['success' => false, 'message' => 'Student ID is required']);
    exit();
}

try {
    // Begin transaction
    $conn->begin_transaction();
    
    // Get user ID from student
    $query = "SELECT user_id, name FROM students WHERE id = ? OR student_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $studentId, $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Student not found']);
        exit();
    }
    
    $student = $result->fetch_assoc();
    $userId = $student['user_id'];
    $studentName = $student['name'];
    
    // Delete user (this will cascade delete student due to foreign key constraint)
    $query = "DELETE FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    // Log the activity
    logActivity($_SESSION['user_id'], "Deleted student: $studentName");
    
    echo json_encode(['success' => true, 'message' => 'Student deleted successfully']);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    error_log("Delete student error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to delete student: ' . getErrorMessage($e)]);
}
?>

