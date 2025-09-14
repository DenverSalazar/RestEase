<?php
// submit_assessment.php
header('Content-Type: application/json');
include_once '../Includes/db.php';

// Get POST data
$request_id = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;
$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
$total_fee = isset($_POST['total_fee']) ? floatval($_POST['total_fee']) : 0;

if (!$request_id || !$user_id || !$total_fee) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit;
}

// Save assessment (optional, you can expand this logic)
// Example: Insert into assessments table
// $stmt = $conn->prepare("INSERT INTO assessments (request_id, user_id, total_fee, created_at) VALUES (?, ?, ?, NOW())");
// $stmt->bind_param('iid', $request_id, $user_id, $total_fee);
// $stmt->execute();
// $stmt->close();

// Insert notification for the user
$notif_message = "Your assessment of fees is ready. Total fee: ₱ " . number_format($total_fee, 2);
$notif_link = "clientbilling.php?request_id=$request_id"; // Adjust link as needed

// Make sure you have a notifications table: id, user_id, message, link, is_read, created_at
$stmt = $conn->prepare("INSERT INTO notifications (user_id, message, link, is_read, created_at) VALUES (?, ?, ?, 0, NOW())");
$stmt->bind_param('iss', $user_id, $notif_message, $notif_link);
$success = $stmt->execute();
$stmt->close();

if ($success) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to notify user.']);
}
?>
