<?php
header('Content-Type: application/json');
include_once '../Includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid request ID.']);
    exit;
}

// Fetch the request
$sql = "SELECT * FROM client_requests WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Request not found.']);
    exit;
}
$row = $result->fetch_assoc();

// Insert into denied_request
$insert_sql = "INSERT INTO denied_request (user_id, type, first_name, last_name, middle_name, age, dob, dod, residency, informant_name, file_upload, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$insert_stmt = $conn->prepare($insert_sql);
$insert_stmt->bind_param(
    'issssissssss',
    $row['user_id'],
    $row['type'],
    $row['first_name'],
    $row['last_name'],
    $row['middle_name'],
    $row['age'],
    $row['dob'],
    $row['dod'],
    $row['residency'],
    $row['informant_name'],
    $row['file_upload'],
    $row['created_at']
);
$success = $insert_stmt->execute();

if (!$success) {
    echo json_encode(['success' => false, 'message' => 'Failed to deny request.']);
    exit;
}

// Delete from client_requests
$delete_sql = "DELETE FROM client_requests WHERE id = ?";
$delete_stmt = $conn->prepare($delete_sql);
$delete_stmt->bind_param('i', $id);
$delete_stmt->execute();

echo json_encode(['success' => true]); 