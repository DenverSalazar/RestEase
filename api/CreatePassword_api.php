<?php
require '../Includes/db.php';  // Adjust path if needed

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$email = $_POST['email'] ?? null;
$password = $_POST['password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

if (!$email) {
    echo json_encode(['success' => false, 'message' => 'Email is required.']);
    exit;
}

$error_messages = [];

// Validation
if (strlen($password) < 8) {
    $error_messages[] = "Password must be at least 8 characters.";
} elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
    $error_messages[] = "Password must contain both letters and numbers.";
}

if ($password !== $confirm) {
    $error_messages[] = "Passwords do not match.";
}

if (!empty($error_messages)) {
    echo json_encode([
        'success' => false,
        'message' => implode(" ", $error_messages)
    ]);
    exit;
}

// Check if user exists
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'User not found.']);
    exit;
}

// Update password
$hashed = password_hash($password, PASSWORD_DEFAULT);
$update = $conn->prepare("UPDATE users SET password = ?, reset_code = NULL, reset_expires = NULL WHERE email = ?");
$update->bind_param("ss", $hashed, $email);

if ($update->execute()) {
    echo json_encode(['success' => true, 'message' => 'Password reset successful.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to reset password.']);
}
$update->close();
