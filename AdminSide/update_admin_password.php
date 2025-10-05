<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}
if (!isset($_POST['new_password']) || strlen($_POST['new_password']) < 6) {
    echo json_encode(['success' => false, 'error' => 'Invalid password']);
    exit;
}
include_once '../Includes/db.php';
$adminId = $_SESSION['admin_id'];
$newPassword = $_POST['new_password'];
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

$stmt = $conn->prepare('UPDATE admin_accounts SET password = ? WHERE id = ?');
$stmt->bind_param('si', $hashedPassword, $adminId);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Update failed']);
}
$stmt->close();
$conn->close();
?>