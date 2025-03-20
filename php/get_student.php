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

// Get student ID from request
$studentId = isset($_GET['id']) ? sanitize($_GET['id']) : '';

if (empty($studentId)) {
    echo json_encode(['success' => false, 'message' => 'Student ID is required']);
    exit();
}

try {
    $query = "";
    $params = [];
    $types = "";
    
    // Different queries based on user role
    if ($_SESSION['role'] === 'admin') {
        $query = "SELECT s.*, c.course_name as course
                 FROM students s
                 LEFT JOIN enrollments e ON s.id = e.student_id
                 LEFT JOIN courses c ON e.course_id = c.id
                 WHERE s.id = ? OR s.student_id = ?";
        $params[] = $studentId;
        $params[] = $studentId;
        $types .= "is";
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
        
        $query = "SELECT s.*, c.course_name as course
                 FROM students s
                 JOIN enrollments e ON s.id = e.student_id
                 JOIN courses c ON e.course_id = c.id
                 JOIN course_instructors ci ON c.id = ci.course_id
                 WHERE ci.faculty_id = ? AND (s.id = ? OR s.student_id = ?)";
        $params[] = $facultyId;
        $params[] = $studentId;
        $params[] = $studentId;
        $types .= "iis";
    } else {
        echo json_encode(['success' => false, 'message' => 'Unauthorized role']);
        exit();
    }
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Student not found']);
        exit();
    }
    
    $student = $result->fetch_assoc();
    
    // Get additional student data
    // Get attendance
    $query = "SELECT 
              COUNT(CASE WHEN status = 'present' THEN 1 END) as present,
              COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent,
              COUNT(CASE WHEN status = 'late' THEN 1 END) as late,
              COUNT(CASE WHEN status = 'excused' THEN 1 END) as excused,
              COUNT(*) as total
              FROM attendance
              WHERE student_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $student['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $attendance = $result->fetch_assoc();
    
    // Get grades
    $query = "SELECT g.*, c.course_name, a.title as assignment_title
              FROM grades g
              JOIN courses c ON g.course_id = c.id
              LEFT JOIN assignments a ON g.assignment_id = a.id
              WHERE g.student_id = ?
              ORDER BY g.created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $student['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $grades = [];
    while ($row = $result->fetch_assoc()) {
        $grades[] = $row;
    }
    
    // Get enrollments
    $query = "SELECT e.*, c.course_name, c.course_code
              FROM enrollments e
              JOIN courses c ON e.course_id = c.id
              WHERE e.student_id = ?
              ORDER BY e.enrollment_date DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $student['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $enrollments = [];
    while ($row = $result->fetch_assoc()) {
        $enrollments[] = $row;
    }
    
    // Add additional data to student
    $student['attendance'] = $attendance;
    $student['grades'] = $grades;
    $student['enrollments'] = $enrollments;
    
    echo json_encode(['success' => true, 'student' => $student]);
} catch (Exception $e) {
    error_log("Get student error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to get student: ' . getErrorMessage($e)]);
}
?>

