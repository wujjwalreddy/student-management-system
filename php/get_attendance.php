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
$date = isset($_GET['date']) ? sanitize($_GET['date']) : '';
$studentId = isset($_GET['studentId']) ? sanitize($_GET['studentId']) : '';

try {
    $query = "";
    $params = [];
    $types = "";
    
    if (!empty($courseId) && !empty($date)) {
        // Get attendance for a specific course and date
        $query = "SELECT a.*, s.student_id as student_code, s.name as student_name
                 FROM attendance a
                 JOIN students s ON a.student_id = s.id
                 WHERE a.course_id = ? AND a.date = ?
                 ORDER BY s.name";
        $params[] = $courseId;
        $params[] = $date;
        $types .= "is";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $attendance = [];
        while ($row = $result->fetch_assoc()) {
            $attendance[] = $row;
        }
        
        echo json_encode(['success' => true, 'attendance' => $attendance]);
    } elseif (!empty($studentId) && !empty($courseId)) {
        // Get attendance for a specific student in a course
        $query = "SELECT a.*
                 FROM attendance a
                 WHERE a.student_id = ? AND a.course_id = ?
                 ORDER BY a.date DESC";
        $params[] = $studentId;
        $params[] = $courseId;
        $types .= "ii";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $attendance = [];
        while ($row = $result->fetch_assoc()) {
            $attendance[] = $row;
        }
        
        echo json_encode(['success' => true, 'attendance' => $attendance]);
    } elseif (!empty($studentId)) {
        // Get attendance for a specific student in all courses
        $query = "SELECT a.*, c.course_code, c.course_name
                 FROM attendance a
                 JOIN courses c ON a.course_id = c.id
                 WHERE a.student_id = ?
                 ORDER BY a.date DESC";
        $params[] = $studentId;
        $types .= "i";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $attendance = [];
        while ($row = $result->fetch_assoc()) {
            $attendance[] = $row;
        }
        
        echo json_encode(['success' => true, 'attendance' => $attendance]);
    } else {
        // Get attendance summary for all courses
        if ($_SESSION['role'] === 'admin') {
            $query = "SELECT c.id as course_id, c.course_code, c.course_name, a.date,
                     COUNT(CASE WHEN a.status = 'present' THEN 1 END) as present_count,
                     COUNT(CASE WHEN a.status = 'absent' THEN 1 END) as absent_count,
                     COUNT(CASE WHEN a.status = 'late' THEN 1 END) as late_count,
                     COUNT(CASE WHEN a.status = 'excused' THEN 1 END) as excused_count,
                     COUNT(*) as total_count
                     FROM attendance a
                     JOIN courses c ON a.course_id = c.id
                     GROUP BY c.id, a.date
                     ORDER BY a.date DESC";
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
            
            $query = "SELECT c.id as course_id, c.course_code, c.course_name, a.date,
                     COUNT(CASE WHEN a.status = 'present' THEN 1 END) as present_count,
                     COUNT(CASE WHEN a.status = 'absent' THEN 1 END) as absent_count,
                     COUNT(CASE WHEN a.status = 'late' THEN 1 END) as late_count,
                     COUNT(CASE WHEN a.status = 'excused' THEN 1 END) as excused_count,
                     COUNT(*) as total_count
                     FROM attendance a
                     JOIN courses c ON a.course_id = c.id
                     JOIN course_instructors ci ON c.id = ci.course_id
                     WHERE ci.faculty_id = ?
                     GROUP BY c.id, a.date
                     ORDER BY a.date DESC";
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
        
        $attendance = [];
        while ($row = $result->fetch_assoc()) {
            $attendance[] = $row;
        }
        
        echo json_encode(['success' => true, 'attendance' => $attendance]);
    }
} catch (Exception $e) {
    error_log("Get attendance error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to get attendance: ' . getErrorMessage($e)]);
}
?>

