<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}
if (!isset($_POST['password'])) {
    echo json_encode(['success' => false, 'error' => 'No password provided']);
    exit;
}
include_once '../Includes/db.php';
$adminId = $_SESSION['admin_id'];
$password = $_POST['password'];
$stmt = $conn->prepare('SELECT password FROM admin_accounts WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $adminId);
$stmt->execute();
$stmt->bind_result($hashedPassword);
if ($stmt->fetch() && password_verify($password, $hashedPassword)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
$stmt->close();
$conn->close();
?>
