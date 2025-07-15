<?php
header('Content-Type: application/json');

include_once '../Includes/db.php';
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$first_name = trim($data['first_name'] ?? '');
$last_name = trim($data['last_name'] ?? '');
$email = trim($data['email'] ?? '');
$contact_no = trim($data['contact_no'] ?? '');
$password = $data['password'] ?? '';
$confirm_password = $data['confirm_password'] ?? '';
$terms = isset($data['terms']) ? (bool)$data['terms'] : false;

$register_error = "";

// Basic validation
if (!$first_name || !$last_name || !$email || !$contact_no || !$password || !$confirm_password) {
    $register_error = "All fields are required.";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $register_error = "Invalid email format.";
} elseif (!preg_match('/^[0-9+\-\s]{7,20}$/', $contact_no)) {
    $register_error = "Invalid contact number format.";
} elseif (strlen($password) < 8) {
    $register_error = "Password must be at least 8 characters long.";
} elseif ($password !== $confirm_password) {
    $register_error = "Passwords do not match.";
} elseif (!$terms) {
    $register_error = "You must agree to the Terms & Conditions.";
}

if ($register_error) {
    echo json_encode(['success' => false, 'message' => $register_error]);
    $conn->close();
    exit;
}

// Check if email already exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Email already registered.']);
    $stmt->close();
    $conn->close();
    exit;
}
$stmt->close();

// Insert new user
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, contact_no, password) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssss", $first_name, $last_name, $email, $contact_no, $hashed_password);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Registration successful.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again.']);
}
$stmt->close();
$conn->close();

?>