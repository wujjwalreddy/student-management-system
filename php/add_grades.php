<?php
require_once 'config.php';

// Check if user is logged in and has faculty role
if (!hasRole('faculty')) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Set header to return JSON
header('Content-Type: application/json');

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get and sanitize input data
$courseId = isset($_POST['courseId']) ? sanitize($_POST['courseId']) : '';
$examType = isset($_POST['examType']) ? sanitize($_POST['examType']) : '';
$assignmentId = isset($_POST['assignmentId']) ? sanitize($_POST['assignmentId']) : null;
$totalMarks = isset($_POST['totalMarks']) ? (float)$_POST['totalMarks'] : 0;
$gradesData = isset($_POST['grades']) ? $_POST['grades'] : [];

// Validate input
if (empty($courseId) || empty($examType) || $totalMarks <= 0 || empty($gradesData)) {
    echo json_encode(['success' => false, 'message' => 'Required fields are missing']);
    exit();
}

try {
    // Begin transaction
    $conn->begin_transaction();
    
    // Get faculty ID
    $facultyId = 0;
    $query = "SELECT id FROM faculty WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $faculty = $result->fetch_assoc();
        $facultyId = $faculty['id'];
    } else {
        echo json_encode(['success' => false, 'message' => 'Faculty not found']);
        exit();
    }
    
    // Check if faculty teaches this course
    $query = "SELECT id FROM course_instructors WHERE course_id = ? AND faculty_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $courseId, $facultyId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'You are not authorized to add grades for this course']);
        exit();
    }
    
    // Delete existing grades for this course, exam type, and assignment (if applicable)
    if ($assignmentId) {
        $query = "DELETE FROM grades WHERE course_id = ? AND exam_type = ? AND assignment_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("isi", $courseId, $examType, $assignmentId);
    } else {
        $query = "DELETE FROM grades WHERE course_id = ? AND exam_type = ? AND assignment_id IS NULL";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("is", $courseId, $examType);
    }
    $stmt->execute();
    
    // Insert new grade records
    if ($assignmentId) {
        $query = "INSERT INTO grades (student_id, course_id, assignment_id, exam_type, marks_obtained, total_marks, grade_letter, remarks, created_by, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    } else {
        $query = "INSERT INTO grades (student_id, course_id, exam_type, marks_obtained, total_marks, grade_letter, remarks, created_by, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    }
    
    foreach ($gradesData as $grade) {
        $studentId = sanitize($grade['studentId']);
        $marksObtained = (float)$grade['marksObtained'];
        $gradeLetter = sanitize($grade['gradeLetter']);
        $remarks = sanitize($grade['remarks']);
        
        // Calculate grade letter if not provided
        if (empty($gradeLetter)) {
            $percentage = ($marksObtained / $totalMarks) * 100;
            if ($percentage >= 90) {
                $gradeLetter = 'A';
            } elseif ($percentage >= 80) {
                $gradeLetter = 'B';
            } elseif ($percentage >= 70) {
                $gradeLetter = 'C';
            } elseif ($percentage >= 60) {
                $gradeLetter = 'D';
            } else {
                $gradeLetter = 'F';
            }
        }
        
        if ($assignmentId) {
            $stmt = $conn->prepare($query);
            $stmt->bind_param("iiisdssi", $studentId, $courseId, $assignmentId, $examType, $marksObtained, $totalMarks, $gradeLetter, $remarks, $facultyId);
        } else {
            $stmt = $conn->prepare($query);
            $stmt->bind_param("isdssi", $studentId, $courseId, $examType, $marksObtained, $totalMarks, $gradeLetter, $remarks, $facultyId);
        }
        $stmt->execute();
    }
    
    // Commit transaction
    $conn->commit();
    
    // Log the activity
    logActivity($_SESSION['user_id'], "Added grades for course ID: $courseId, exam type: $examType");
    
    echo json_encode(['success' => true, 'message' => 'Grades added successfully']);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    error_log("Add grades error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to add grades: ' . getErrorMessage($e)]);
}
?>

