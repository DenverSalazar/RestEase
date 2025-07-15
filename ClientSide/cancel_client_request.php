<?php
include_once '../Includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = $_POST['request_id'] ?? null;
    if (!$request_id) {
        echo json_encode(['success' => false, 'message' => 'No request_id provided']);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM client_requests WHERE id = ?");
    $stmt->bind_param("i", $request_id);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to cancel request or request not found']);
    }
    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>