<?php
header('Content-Type: application/json');
include_once '../Includes/db.php';
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';

if (!$email || !$password) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

$stmt = $conn->prepare("SELECT id, password, status FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows == 1) {
    $stmt->bind_result($user_id, $hashed_password, $user_status);
    $stmt->fetch();
    
    // Check if account is disabled
    if ($user_status === 'disabled') {
        echo json_encode(['success' => false, 'message' => 'Your account has been disabled. Please contact support.']);
    } elseif (password_verify($password, $hashed_password)) {
        echo json_encode(['success' => true, 'user_id' => $user_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Incorrect password.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No account found with that email.']);
}
$stmt->close();
$conn->close();

?>
