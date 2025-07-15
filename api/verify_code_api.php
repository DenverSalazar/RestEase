<?php
require '../Includes/db.php'; // Adjust path as needed

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$email = $_POST['email'] ?? '';
$code  = $_POST['code'] ?? '';

if (empty($email) || empty($code)) {
    echo json_encode(['success' => false, 'message' => 'Email and verification code are required.']);
    exit;
}

$stmt = $conn->prepare("SELECT reset_code, reset_expires FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if ($user && $user['reset_code'] === $code && strtotime($user['reset_expires']) > time()) {
    echo json_encode(['success' => true, 'message' => 'Code verified.']);
    exit;
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid or expired verification code.']);
    exit;
}
