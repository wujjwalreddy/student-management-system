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

// Get faculty ID from request
$facultyId = isset($_GET['id']) ? sanitize($_GET['id']) : '';

if (empty($facultyId)) {
    echo json_encode(['success' => false, 'message' => 'Faculty ID is required']);
    exit();
}

try {
    // Begin transaction
    $conn->begin_transaction();
    
    // Get user ID from faculty
    $query = "SELECT user_id, name FROM faculty WHERE id = ? OR faculty_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $facultyId, $facultyId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Faculty not found']);
        exit();
    }
    
    $faculty = $result->fetch_assoc();
    $userId = $faculty['user_id'];
    $facultyName = $faculty['name'];
    
    // Delete user (this will cascade delete faculty due to foreign key constraint)
    $query = "DELETE FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    // Log the activity
    logActivity($_SESSION['user_id'], "Deleted faculty: $facultyName");
    
    echo json_encode(['success' => true, 'message' => 'Faculty deleted successfully']);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    error_log("Delete faculty error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to delete faculty: ' . getErrorMessage($e)]);
}
?>

