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

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get and sanitize input data
$phone = isset($_POST['phone']) ? sanitize($_POST['phone']) : '';
$address = isset($_POST['address']) ? sanitize($_POST['address']) : '';
$currentPassword = isset($_POST['currentPassword']) ? $_POST['currentPassword'] : '';
$newPassword = isset($_POST['newPassword']) ? $_POST['newPassword'] : '';

try {
    // Begin transaction
    $conn->begin_transaction();
    
    $userId = $_SESSION['user_id'];
    $role = $_SESSION['role'];
    
    // Check if password change is requested
    if (!empty($currentPassword) && !empty($newPassword)) {
        // Verify current password directly
        $query = "SELECT password FROM users WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            exit();
        }
        
        $user = $result->fetch_assoc();
        
        if ($currentPassword !== $user['password']) {
            echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
            exit();
        }
        
        // Update password with plain text
        $query = "UPDATE users SET password = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $newPassword, $userId);
        $stmt->execute();
    }
    
    // Update profile based on role
    if ($role === 'faculty') {
        $qualification = isset($_POST['qualification']) ? sanitize($_POST['qualification']) : '';
        
        $query = "UPDATE faculty SET phone = ?, address = ?, qualification = ? WHERE user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssi", $phone, $address, $qualification, $userId);
        $stmt->execute();
    } elseif ($role === 'student') {
        $query = "UPDATE students SET phone = ?, address = ? WHERE user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssi", $phone, $address, $userId);
        $stmt->execute();
    }
    
    // Commit transaction
    $conn->commit();
    
    // Log the activity
    logActivity($userId, "Updated profile");
    
    echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    error_log("Update profile error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to update profile: ' . getErrorMessage($e)]);
}
?>

