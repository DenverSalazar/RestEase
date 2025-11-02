<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}
header('Content-Type: application/json');

// Include DB (adjust path if your includes are elsewhere)
include_once '../Includes/db.php';

// Ensure connection
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection error']);
    exit;
}

// Create table for walk-in clients if it doesn't exist
$createSql = "
CREATE TABLE IF NOT EXISTS `walkin_clients` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(255) DEFAULT NULL,
  `contact_no` VARCHAR(30) DEFAULT NULL,
  `walkin_date` DATE DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
$conn->query($createSql);

// Get and sanitize inputs
$first_name = trim($_POST['first_name'] ?? '');
$last_name  = trim($_POST['last_name'] ?? '');
$email      = trim($_POST['email'] ?? '');
$contact_no = trim($_POST['contact_no'] ?? '');
$walkin_date = trim($_POST['walkin_date'] ?? ''); // expected YYYY-MM-DD or empty

// Basic validation: require first and last name
if ($first_name === '' || $last_name === '') {
    echo json_encode(['success' => false, 'message' => 'First name and last name are required.']);
    exit;
}

// Normalize walkin_date to null if empty or invalid
$walkin_date_db = null;
if ($walkin_date !== '') {
    $d = date_create_from_format('Y-m-d', $walkin_date);
    if ($d !== false) {
        $walkin_date_db = $d->format('Y-m-d');
    } else {
        // try other common formats
        $d2 = strtotime($walkin_date);
        if ($d2 !== false) {
            $walkin_date_db = date('Y-m-d', $d2);
        }
    }
}

// Prepared insert
$stmt = $conn->prepare("INSERT INTO `walkin_clients` (first_name, last_name, email, contact_no, walkin_date) VALUES (?, ?, ?, ?, ?)");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare statement: ' . $conn->error]);
    exit;
}
$stmt->bind_param('sssss', $first_name, $last_name, $email, $contact_no, $walkin_date_db);

$executed = $stmt->execute();
if ($executed) {
    $insertId = $stmt->insert_id;
    echo json_encode(['success' => true, 'id' => $insertId, 'message' => 'Walk-in saved']);
} else {
    echo json_encode(['success' => false, 'message' => 'Insert failed: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
