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

// Get parameters
$courseId = isset($_GET['courseId']) ? sanitize($_GET['courseId']) : '';
$examType = isset($_GET['examType']) ? sanitize($_GET['examType']) : '';
$studentId = isset($_GET['studentId']) ? sanitize($_GET['studentId']) : '';
$assignmentId = isset($_GET['assignmentId']) ? sanitize($_GET['assignmentId']) : '';

try {
    $query = "";
    $params = [];
    $types = "";
    
    if (!empty($courseId) && !empty($examType) && !empty($assignmentId)) {
        // Get grades for a specific course, exam type, and assignment
        $query = "SELECT g.*, s.student_id as student_code, s.name as student_name
                 FROM grades g
                 JOIN students s ON g.student_id = s.id
                 WHERE g.course_id = ? AND g.exam_type = ? AND g.assignment_id = ?
                 ORDER BY s.name";
        $params[] = $courseId;
        $params[] = $examType;
        $params[] = $assignmentId;
        $types .= "isi";
    } elseif (!empty($courseId) && !empty($examType)) {
        // Get grades for a specific course and exam type
        $query = "SELECT g.*, s.student_id as student_code, s.name as student_name
                 FROM grades g
                 JOIN students s ON g.student_id = s.id
                 WHERE g.course_id = ? AND g.exam_type = ? AND g.assignment_id IS NULL
                 ORDER BY s.name";
        $params[] = $courseId;
        $params[] = $examType;
        $types .= "is";
    } elseif (!empty($studentId) && !empty($courseId)) {
        // Get grades for a specific student in a course
        $query = "SELECT g.*, c.course_name, a.title as assignment_title
                 FROM grades g
                 JOIN courses c ON g.course_id = c.id
                 LEFT JOIN assignments a ON g.assignment_id = a.id
                 WHERE g.student_id = ? AND g.course_id = ?
                 ORDER BY g.created_at DESC";
        $params[] = $studentId;
        $params[] = $courseId;
        $types .= "ii";
    } elseif (!empty($studentId)) {
        // Get grades for a specific student in all courses
        $query = "SELECT g.*, c.course_code, c.course_name, a.title as assignment_title
                 FROM grades g
                 JOIN courses c ON g.course_id = c.id
                 LEFT JOIN assignments a ON g.assignment_id = a.id
                 WHERE g.student_id = ?
                 ORDER BY g.created_at DESC";
        $params[] = $studentId;
        $types .= "i";
    } else {
        // Get grades summary for all courses
        if ($_SESSION['role'] === 'admin') {
            $query = "SELECT c.id as course_id, c.course_code, c.course_name, g.exam_type,
                     AVG(g.marks_obtained / g.total_marks * 100) as average_percentage,
                     MAX(g.marks_obtained) as highest_marks,
                     MIN(g.marks_obtained) as lowest_marks,
                     COUNT(*) as total_students,
                     g.created_at
                     FROM grades g
                     JOIN courses c ON g.course_id = c.id
                     GROUP BY c.id, g.exam_type
                     ORDER BY g.created_at DESC";
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
            
            $query = "SELECT c.id as course_id, c.course_code, c.course_name, g.exam_type,
                     AVG(g.marks_obtained / g.total_marks * 100) as average_percentage,
                     MAX(g.marks_obtained) as highest_marks,
                     MIN(g.marks_obtained) as lowest_marks,
                     COUNT(*) as total_students,
                     g.created_at
                     FROM grades g
                     JOIN courses c ON g.course_id = c.id
                     JOIN course_instructors ci ON c.id = ci.course_id
                     WHERE ci.faculty_id = ? AND g.created_by = ?
                     GROUP BY c.id, g.exam_type
                     ORDER BY g.created_at DESC";
            $params[] = $facultyId;
            $params[] = $facultyId;
            $types .= "ii";
        } else {
            echo json_encode(['success' => false, 'message' => 'Unauthorized role']);
            exit();
        }
    }
    
    $stmt = $conn->prepare($query);
    
    if (!empty($types) && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $grades = [];
    while ($row = $result->fetch_assoc()) {
        $grades[] = $row;
    }
    
    echo json_encode(['success' => true, 'grades' => $grades]);
} catch (Exception $e) {
    error_log("Get grades error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to get grades: ' . getErrorMessage($e)]);
}
?>

