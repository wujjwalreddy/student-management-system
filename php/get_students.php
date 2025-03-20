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

try {
    $query = "";
    $params = [];
    $types = "";
    
    // Different queries based on user role
    if ($_SESSION['role'] === 'admin') {
        $query = "SELECT s.id, s.student_id, s.name, s.email, s.phone, s.gender, s.enrollment_date, s.status, 
                 c.course_name as course
                 FROM students s
                 LEFT JOIN enrollments e ON s.id = e.student_id
                 LEFT JOIN courses c ON e.course_id = c.id
                 ORDER BY s.id DESC";
    } elseif ($_SESSION['role'] === 'faculty') {
        // Get faculty ID
        $facultyId = 0;
        $subQuery = "SELECT id FROM faculty WHERE user_id = ?";
        $stmt = $conn->prepare($subQuery);
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $faculty = $result->fetch_assoc();
            $facultyId = $faculty['id'];
        }
        
        $query = "SELECT DISTINCT s.id, s.student_id, s.name, s.email, s.phone, s.gender, s.enrollment_date, s.status, 
                 c.course_name as course
                 FROM students s
                 JOIN enrollments e ON s.id = e.student_id
                 JOIN courses c ON e.course_id = c.id
                 JOIN course_instructors ci ON c.id = ci.course_id
                 WHERE ci.faculty_id = ?
                 ORDER BY s.id DESC";
        $params[] = $facultyId;
        $types .= "i";
    } else {
        echo json_encode(['success' => false, 'message' => 'Unauthorized role']);
        exit();
    }
    
    $stmt = $conn->prepare($query);
    
    if (!empty($types) && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    
    echo json_encode(['success' => true, 'students' => $students]);
} catch (Exception $e) {
    error_log("Get students error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to get students: ' . getErrorMessage($e)]);
}
?>

