<?php
session_start();
header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

// Include database connection
include_once '../Includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admin_id = trim($_POST['admin_id'] ?? '');
    $action = trim($_POST['action'] ?? '');

    // Validation
    if (empty($admin_id)) {
        echo json_encode(['status' => 'error', 'message' => 'Admin ID is required']);
        exit;
    }

    if (!in_array($action, ['disable', 'enable'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
        exit;
    }

    // Prevent admin from disabling themselves
    if ($admin_id == $_SESSION['admin_id']) {
        echo json_encode(['status' => 'error', 'message' => 'You cannot disable your own account']);
        exit;
    }

    // Update admin status
    $new_status = ($action === 'disable') ? 'disabled' : 'active';
    
    $stmt = $conn->prepare("UPDATE admin_accounts SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $admin_id);
    
    if ($stmt->execute()) {
        $message = ($action === 'disable') ? 'Admin account disabled successfully' : 'Admin account enabled successfully';
        echo json_encode(['status' => 'success', 'message' => $message]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update admin status']);
    }
    
    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>
