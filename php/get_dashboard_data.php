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
    $role = $_SESSION['role'];
    $dashboardData = [];
    
    if ($role === 'admin') {
        // Get total students count
        $query = "SELECT COUNT(*) as total FROM students";
        $result = $conn->query($query);
        $dashboardData['totalStudents'] = $result->fetch_assoc()['total'];
        
        // Get total faculty count
        $query = "SELECT COUNT(*) as total FROM faculty";
        $result = $conn->query($query);
        $dashboardData['totalFaculty'] = $result->fetch_assoc()['total'];
        
        // Get total courses count
        $query = "SELECT COUNT(*) as total FROM courses";
        $result = $conn->query($query);
        $dashboardData['totalCourses'] = $result->fetch_assoc()['total'];
        
        // Get total assignments count
        $query = "SELECT COUNT(*) as total FROM assignments";
        $result = $conn->query($query);
        $dashboardData['totalAssignments'] = $result->fetch_assoc()['total'];
        
        // Get recent enrollments
        $query = "SELECT e.id, s.student_id, s.name as student_name, c.course_name, e.enrollment_date, e.status
                 FROM enrollments e
                 JOIN students s ON e.student_id = s.id
                 JOIN courses c ON e.course_id = c.id
                 ORDER BY e.enrollment_date DESC
                 LIMIT 5";
        $result = $conn->query($query);
        $recentEnrollments = [];
        while ($row = $result->fetch_assoc()) {
            $recentEnrollments[] = $row;
        }
        $dashboardData['recentEnrollments'] = $recentEnrollments;
        
        // Get recent activities
        $query = "SELECT a.activity, a.timestamp, u.fullname as user, u.role
                 FROM activity_logs a
                 JOIN users u ON a.user_id = u.id
                 ORDER BY a.timestamp DESC
                 LIMIT 5";
        $result = $conn->query($query);
        $recentActivities = [];
        while ($row = $result->fetch_assoc()) {
            $recentActivities[] = $row;
        }
        $dashboardData['recentActivities'] = $recentActivities;
    } elseif ($role === 'faculty') {
        // Get faculty ID
        $query = "SELECT id FROM faculty WHERE user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Faculty not found']);
            exit();
        }
        
        $faculty = $result->fetch_assoc();
        $facultyId = $faculty['id'];
        
        // Get courses count
        $query = "SELECT COUNT(*) as total FROM course_instructors WHERE faculty_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $facultyId);
        $stmt->execute();
        $result = $stmt->get_result();
        $dashboardData['totalCourses'] = $result->fetch_assoc()['total'];
        
        // Get students count
        $query = "SELECT COUNT(DISTINCT e.student_id) as total
                 FROM enrollments e
                 JOIN course_instructors ci ON e.course_id = ci.course_id
                 WHERE ci.faculty_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $facultyId);
        $stmt->execute();
        $result = $stmt->get_result();
        $dashboardData['totalStudents'] = $result->fetch_assoc()['total'];
        
        // Get assignments count
        $query = "SELECT COUNT(*) as total FROM assignments WHERE created_by = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $facultyId);
        $stmt->execute();
        $result = $stmt->get_result();
        $dashboardData['totalAssignments'] = $result->fetch_assoc()['total'];
        
        // Get average attendance
        $query = "SELECT 
                 COUNT(CASE WHEN a.status = 'present' THEN 1 END) as present,
                 COUNT(*) as total
                 FROM attendance a
                 JOIN course_instructors ci ON a.course_id = ci.course_id
                 WHERE ci.faculty_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $facultyId);
        $stmt->execute();
        $result = $stmt->get_result();
        $attendance = $result->fetch_assoc();
        $dashboardData['averageAttendance'] = ($attendance['total'] > 0) ? round(($attendance['present'] / $attendance['total']) * 100) : 0;
        
        // Get courses
        $query = "SELECT c.id, c.course_code, c.course_name, 
                 (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id) as student_count
                 FROM courses c
                 JOIN course_instructors ci ON c.id = ci.course_id
                 WHERE ci.faculty_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $facultyId);
        $stmt->execute();
        $result = $stmt->get_result();
        $courses = [];
        while ($row = $result->fetch_assoc()) {
            $courses[] = $row;
        }
        $dashboardData['courses'] = $courses;
        
        // Get recent assignments
        $query = "SELECT a.id, a.title, c.course_name, a.due_date,
                 (SELECT COUNT(*) FROM assignment_submissions WHERE assignment_id = a.id) as submission_count
                 FROM assignments a
                 JOIN courses c ON a.course_id = c.id
                 WHERE a.created_by = ?
                 ORDER BY a.due_date DESC
                 LIMIT 3";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $facultyId);
        $stmt->execute();
        $result = $stmt->get_result();
        $recentAssignments = [];
        while ($row = $result->fetch_assoc()) {
            $recentAssignments[] = $row;
        }
        $dashboardData['recentAssignments'] = $recentAssignments;
    } elseif ($role === 'student') {
        // Get student ID
        $query = "SELECT id FROM students WHERE user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Student not found']);
            exit();
        }
        
        $student = $result->fetch_assoc();
        $studentId = $student['id'];
        
        // Get courses count
        $query = "SELECT COUNT(*) as total FROM enrollments WHERE student_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $dashboardData['totalCourses'] = $result->fetch_assoc()['total'];
        
        // Get attendance percentage
        $query = "SELECT 
                 COUNT(CASE WHEN status = 'present' THEN 1 END) as present,
                 COUNT(*) as total
                 FROM attendance
                 WHERE student_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $attendance = $result->fetch_assoc();
        $dashboardData['attendancePercentage'] = ($attendance['total'] > 0) ? round(($attendance['present'] / $attendance['total']) * 100) : 0;
        
        // Get pending assignments count
        $query = "SELECT COUNT(*) as total
                 FROM assignments a
                 JOIN enrollments e ON a.course_id = e.course_id
                 LEFT JOIN assignment_submissions s ON a.id = s.assignment_id AND s.student_id = ?
                 WHERE e.student_id = ? AND s.id IS NULL AND a.due_date > NOW()";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $studentId, $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $dashboardData['pendingAssignments'] = $result->fetch_assoc()['total'];
        
        // Get GPA
        $query = "SELECT AVG(marks_obtained / total_marks * 100) as average_percentage
                 FROM grades
                 WHERE student_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $grades = $result->fetch_assoc();
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
        
        $dashboardData['gpa'] = round($gpa, 1);
        
        // Get courses
        $query = "SELECT c.id, c.course_code, c.course_name, f.name as instructor_name
                 FROM courses c
                 JOIN enrollments e ON c.id = e.course_id
                 JOIN course_instructors ci ON c.id = ci.course_id
                 JOIN faculty f ON ci.faculty_id = f.id
                 WHERE e.student_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $courses = [];
        while ($row = $result->fetch_assoc()) {
            $courses[] = $row;
        }
        $dashboardData['courses'] = $courses;
        
        // Get upcoming assignments
        $query = "SELECT a.id, a.title, c.course_name, a.due_date,
                 s.status, s.marks_obtained
                 FROM assignments a
                 JOIN courses c ON a.course_id = c.id
                 JOIN enrollments e ON c.id = e.course_id
                 LEFT JOIN assignment_submissions s ON a.id = s.assignment_id AND s.student_id = ?
                 WHERE e.student_id = ? AND a.due_date > NOW()
                 ORDER BY a.due_date ASC
                 LIMIT 3";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $studentId, $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $upcomingAssignments = [];
        while ($row = $result->fetch_assoc()) {
            $upcomingAssignments[] = $row;
        }
        $dashboardData['upcomingAssignments'] = $upcomingAssignments;
    }
    
    echo json_encode(['success' => true, 'data' => $dashboardData]);
} catch (Exception $e) {
    error_log("Get dashboard data error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to get dashboard data: ' . getErrorMessage($e)]);
}
?>

