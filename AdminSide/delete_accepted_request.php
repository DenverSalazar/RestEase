<?php
include_once '../Includes/db.php';
header('Content-Type: application/json');

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
if ($id > 0) {
    $stmt = $conn->prepare("DELETE FROM accepted_request WHERE id = ?");
    $stmt->bind_param('i', $id);
    $success = $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => $success]);
    exit;
}
echo json_encode(['success' => false]);
exit;
