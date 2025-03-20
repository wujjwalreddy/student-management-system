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

// Check if specific course ID is requested
$courseId = isset($_GET['id']) ? sanitize($_GET['id']) : '';

try {
    if (!empty($courseId)) {
        // Get specific course
        $query = "SELECT c.*, f.name as instructor_name
                 FROM courses c
                 LEFT JOIN course_instructors ci ON c.id = ci.course_id
                 LEFT JOIN faculty f ON ci.faculty_id = f.id
                 WHERE c.id = ? OR c.course_code = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("is", $courseId, $courseId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Course not found']);
            exit();
        }
        
        $course = $result->fetch_assoc();
        
        // Get enrolled students
        $query = "SELECT s.id, s.student_id, s.name, s.email, e.enrollment_date, e.status
                 FROM students s
                 JOIN enrollments e ON s.id = e.student_id
                 WHERE e.course_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $course['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $students = [];
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
        
        $course['students'] = $students;
        
        echo json_encode(['success' => true, 'course' => $course]);
    } else {
        $query = "";
        $params = [];
        $types = "";
        
        // Different queries based on user role
        if ($_SESSION['role'] === 'admin') {
            $query = "SELECT c.id, c.course_code, c.course_name, c.department, c.credits, c.semester, c.status, f.name as instructor_name
                     FROM courses c
                     LEFT JOIN course_instructors ci ON c.id = ci.course_id
                     LEFT JOIN faculty f ON ci.faculty_id = f.id
                     ORDER BY c.id DESC";
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
            
            $query = "SELECT c.id, c.course_code, c.course_name, c.department, c.credits, c.semester, c.status
                     FROM courses c
                     JOIN course_instructors ci ON c.id = ci.course_id
                     WHERE ci.faculty_id = ?
                     ORDER BY c.id DESC";
            $params[] = $facultyId;
            $types .= "i";
        } elseif ($_SESSION['role'] === 'student') {
            // Get student ID
            $studentId = 0;
            $subQuery = "SELECT id FROM students WHERE user_id = ?";
            $stmt = $conn->prepare($subQuery);
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $student = $result->fetch_assoc();
                $studentId = $student['id'];
            }
            
            $query = "SELECT c.id, c.course_code, c.course_name, c.department, c.credits, c.semester, c.status, f.name as instructor_name
                     FROM courses c
                     JOIN enrollments e ON c.id = e.course_id
                     LEFT JOIN course_instructors ci ON c.id = ci.course_id
                     LEFT JOIN faculty f ON ci.faculty_id = f.id
                     WHERE e.student_id = ?
                     ORDER BY c.id DESC";
            $params[] = $studentId;
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
        
        $courses = [];
        while ($row = $result->fetch_assoc()) {
            $courses[] = $row;
        }
        
        echo json_encode(['success' => true, 'courses' => $courses]);
    }
} catch (Exception $e) {
    error_log("Get courses error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to get courses: ' . getErrorMessage($e)]);
}
?>

