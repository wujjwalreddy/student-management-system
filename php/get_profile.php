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
    $userId = $_SESSION['user_id'];
    $role = $_SESSION['role'];
    
    if ($role === 'admin') {
        // Get admin profile
        $query = "SELECT id, username, fullname, email, role, status, created_at FROM users WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            exit();
        }
        
        $user = $result->fetch_assoc();
        
        echo json_encode(['success' => true, 'profile' => $user]);
    } elseif ($role === 'faculty') {
        // Get faculty profile
        $query = "SELECT f.*, u.username, u.email as user_email, u.status
                 FROM faculty f
                 JOIN users u ON f.user_id = u.id
                 WHERE f.user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $userId);
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
        
        echo json_encode(['success' => true, 'profile' => $faculty]);
    } elseif ($role === 'student') {
        // Get student profile
        $query = "SELECT s.*, u.username, u.email as user_email, u.status
                 FROM students s
                 JOIN users u ON s.user_id = u.id
                 WHERE s.user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Student not found']);
            exit();
        }
        
        $student = $result->fetch_assoc();
        
        // Get enrolled courses
        $query = "SELECT c.id, c.course_code, c.course_name, c.department, c.credits, c.semester, e.enrollment_date, e.status
                 FROM courses c
                 JOIN enrollments e ON c.id = e.course_id
                 WHERE e.student_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $student['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $courses = [];
        while ($row = $result->fetch_assoc()) {
            $courses[] = $row;
        }
        
        $student['courses'] = $courses;
        
        // Get attendance summary
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
        
        $student['attendance'] = $attendance;
        
        // Get GPA
        $query = "SELECT AVG(marks_obtained / total_marks * 100) as average_percentage
                 FROM grades
                 WHERE student_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $student['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $grades = $result->fetch_assoc();
        
        // Calculate GPA (4.0 scale)
        $avgPercentage = $grades['average_percentage'] ?? 0;
        $gpa = 0;
        
        if ($avgPercentage >= 90) {
            $gpa = 4.0;
        } elseif ($avgPercentage >= 80) {
            $gpa = 3.0 + ($avgPercentage - 80) / 10;
        } elseif ($avgPercentage >= 70) {
            $gpa = 2.0 + ($avgPercentage - 70) / 10;
        } elseif ($avgPercentage >= 60) {
            $gpa = 1.0 + ($avgPercentage - 60) / 10;
        } else {
            $gpa = $avgPercentage / 60;
        }
        
        $student['gpa'] = round($gpa, 2);
        
        echo json_encode(['success' => true, 'profile' => $student]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid role']);
    }
} catch (Exception $e) {
    error_log("Get profile error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to get profile: ' . getErrorMessage($e)]);
}
?>

