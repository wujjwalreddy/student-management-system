<?php
require_once 'config.php';

// Check if user is logged in and has student role
if (!hasRole('student')) {
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
$assignmentId = isset($_POST['assignmentId']) ? sanitize($_POST['assignmentId']) : '';
$submissionText = isset($_POST['submissionText']) ? sanitize($_POST['submissionText']) : '';

// Validate input
if (empty($assignmentId)) {
    echo json_encode(['success' => false, 'message' => 'Assignment ID is required']);
    exit();
}

try {
    // Begin transaction
    $conn->begin_transaction();
    
    // Get student ID
    $studentId = 0;
    $query = "SELECT id FROM students WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc();
        $studentId = $student['id'];
    } else {
        echo json_encode(['success' => false, 'message' => 'Student not found']);
        exit();
    }
    
    // Check if assignment exists and is not past due date
    $query = "SELECT a.*, c.id as course_id FROM assignments a JOIN courses c ON a.course_id = c.id WHERE a.id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $assignmentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Assignment not found']);
        exit();
    }
    
    $assignment = $result->fetch_assoc();
    $courseId = $assignment['course_id'];
    
    // Check if student is enrolled in the course
    $query = "SELECT id FROM enrollments WHERE student_id = ? AND course_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $studentId, $courseId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'You are not enrolled in this course']);
        exit();
    }
    
    // Check if assignment is past due date
    $dueDate = new DateTime($assignment['due_date']);
    $now = new DateTime();
    $status = 'submitted';
    
    if ($now > $dueDate) {
        $status = 'late';
    }
    
    // Check if student has already submitted this assignment
    $query = "SELECT id FROM assignment_submissions WHERE assignment_id = ? AND student_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $assignmentId, $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing submission
        $submission = $result->fetch_assoc();
        $submissionId = $submission['id'];
        
        $query = "UPDATE assignment_submissions SET submission_text = ?, submission_date = NOW(), status = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssi", $submissionText, $status, $submissionId);
        $stmt->execute();
    } else {
        // Insert new submission
        $query = "INSERT INTO assignment_submissions (assignment_id, student_id, submission_text, submission_date, status) 
                VALUES (?, ?, ?, NOW(), ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iiss", $assignmentId, $studentId, $submissionText, $status);
        $stmt->execute();
        
        $submissionId = $conn->insert_id;
    }
    
    // Handle file upload if present
    $filePath = null;
    
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/assignments/';
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Generate unique filename
        $fileName = $assignmentId . '_' . $studentId . '_' . time() . '_' . basename($_FILES['file']['name']);
        $filePath = $uploadDir . $fileName;
        
        // Move uploaded file
        if (move_uploaded_file($_FILES['file']['tmp_name'], $filePath)) {
            // Update submission with file path
            $query = "UPDATE assignment_submissions SET file_path = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("si", $filePath, $submissionId);
            $stmt->execute();
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
            exit();
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    // Log the activity
    logActivity($_SESSION['user_id'], "Submitted assignment ID: $assignmentId");
    
    echo json_encode([
        'success' => true, 
        'message' => 'Assignment submitted successfully',
        'submission' => [
            'id' => $submissionId,
            'status' => $status,
            'submissionDate' => date('Y-m-d H:i:s')
        ]
    ]);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    error_log("Submit assignment error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to submit assignment: ' . getErrorMessage($e)]);
}
?>

