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
$assignmentId = isset($_GET['id']) ? sanitize($_GET['id']) : '';
$courseId = isset($_GET['courseId']) ? sanitize($_GET['courseId']) : '';

try {
    if (!empty($assignmentId)) {
        // Get specific assignment
        $query = "SELECT a.*, c.course_code, c.course_name, f.name as faculty_name
                 FROM assignments a
                 JOIN courses c ON a.course_id = c.id
                 JOIN faculty f ON a.created_by = f.id
                 WHERE a.id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $assignmentId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Assignment not found']);
            exit();
        }
        
        $assignment = $result->fetch_assoc();
        
        // Get submissions for this assignment
        $query = "SELECT s.id, s.student_id, s.name as student_name, 
                 as.submission_date, as.status, as.marks_obtained, as.feedback
                 FROM assignment_submissions as
                 JOIN students s ON as.student_id = s.id
                 WHERE as.assignment_id = ?
                 ORDER BY as.submission_date DESC";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $assignmentId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $submissions = [];
        while ($row = $result->fetch_assoc()) {
            $submissions[] = $row;
        }
        
        $assignment['submissions'] = $submissions;
        
        echo json_encode(['success' => true, 'assignment' => $assignment]);
    } elseif (!empty($courseId)) {
        // Get assignments for a specific course
        $query = "SELECT a.*, f.name as faculty_name,
                 (SELECT COUNT(*) FROM assignment_submissions WHERE assignment_id = a.id) as submission_count
                 FROM assignments a
                 JOIN faculty f ON a.created_by = f.id
                 WHERE a.course_id = ?
                 ORDER BY a.due_date DESC";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $courseId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $assignments = [];
        while ($row = $result->fetch_assoc()) {
            $assignments[] = $row;
        }
        
        echo json_encode(['success' => true, 'assignments' => $assignments]);
    } else {
        // Get assignments based on role
        if ($_SESSION['role'] === 'admin') {
            $query = "SELECT a.*, c.course_code, c.course_name, f.name as faculty_name,
                     (SELECT COUNT(*) FROM assignment_submissions WHERE assignment_id = a.id) as submission_count
                     FROM assignments a
                     JOIN courses c ON a.course_id = c.id
                     JOIN faculty f ON a.created_by = f.id
                     ORDER BY a.due_date DESC";
            $stmt = $conn->prepare($query);
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
            
            $query = "SELECT a.*, c.course_code, c.course_name,
                     (SELECT COUNT(*) FROM assignment_submissions WHERE assignment_id = a.id) as submission_count
                     FROM assignments a
                     JOIN courses c ON a.course_id = c.id
                     WHERE a.created_by = ?
                     ORDER BY a.due_date DESC";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $facultyId);
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
            
            $query = "SELECT a.*, c.course_code, c.course_name, f.name as faculty_name,
                     as.submission_date, as.status, as.marks_obtained
                     FROM assignments a
                     JOIN courses c ON a.course_id = c.id
                     JOIN faculty f ON a.created_by = f.id
                     JOIN enrollments e ON c.id = e.course_id AND e.student_id = ?
                     LEFT JOIN assignment_submissions as ON a.id = as.assignment_id AND as.student_id = ?
                     ORDER BY a.due_date DESC";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $studentId, $studentId);
        } else {
            echo json_encode(['success' => false, 'message' => 'Unauthorized role']);
            exit();
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $assignments = [];
        while ($row = $result->fetch_assoc()) {
            $assignments[] = $row;
        }
        
        echo json_encode(['success' => true, 'assignments' => $assignments]);
    }
} catch (Exception $e) {
    error_log("Get assignments error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to get assignments: ' . getErrorMessage($e)]);
}
?>

