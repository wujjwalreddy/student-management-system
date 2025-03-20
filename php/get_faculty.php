<?php
require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Set header to return JSON
header('Content-Type: application/json');

// Check if specific faculty ID is requested
$facultyId = isset($_GET['id']) ? sanitize($_GET['id']) : '';

try {
    if (!empty($facultyId)) {
        // Get specific faculty
        $query = "SELECT f.*, u.username, u.email as user_email
                 FROM faculty f
                 JOIN users u ON f.user_id = u.id
                 WHERE f.id = ? OR f.faculty_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("is", $facultyId, $facultyId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Faculty not found']);
            exit();
        }
        
        $faculty = $result->fetch_assoc();
        
        // Get courses taught by faculty
        $query = "SELECT c.id, c.course_code, c.course_name, c.department, c.credits, c.semester
                 FROM courses c
                 JOIN course_instructors ci ON c.id = ci.course_id
                 WHERE ci.faculty_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $faculty['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $courses = [];
        while ($row = $result->fetch_assoc()) {
            $courses[] = $row;
        }
        
        $faculty['courses'] = $courses;
        
        echo json_encode(['success' => true, 'faculty' => $faculty]);
    } else {
        // Get all faculty
        $query = "SELECT f.id, f.faculty_id, f.name, f.email, f.department, f.designation, f.status
                 FROM faculty f
                 ORDER BY f.id DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $faculty = [];
        while ($row = $result->fetch_assoc()) {
            $faculty[] = $row;
        }
        
        echo json_encode(['success' => true, 'faculty' => $faculty]);
    }
} catch (Exception $e) {
    error_log("Get faculty error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to get faculty: ' . getErrorMessage($e)]);
}
?>

