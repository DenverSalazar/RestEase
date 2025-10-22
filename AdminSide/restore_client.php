<?php
// Minimal, secure restore endpoint used by archive_tab.php
header('Content-Type: application/json; charset=utf-8');

// Allow only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['success' => false, 'message' => 'Method not allowed']);
  exit;
}

// include DB (adjust path if your structure differs)
include_once __DIR__ . '/../Includes/db.php';
if (!isset($conn) || !($conn instanceof mysqli)) {
  // fallback
  $conn = new mysqli('localhost', 'root', '', 'cemeterydb');
}
if ($conn->connect_error) {
  echo json_encode(['success' => false, 'message' => 'Database connection failed']);
  exit;
}

$email = trim($_POST['email'] ?? '');
if ($email === '') {
  echo json_encode(['success' => false, 'message' => 'Missing email']);
  exit;
}

try {
  // Find archive client
  $stmt = $conn->prepare('SELECT id, first_name, last_name, email, contact_no, password FROM archive_clients WHERE email = ? LIMIT 1');
  $stmt->bind_param('s', $email);
  $stmt->execute();
  $res = $stmt->get_result();
  $arch = $res ? $res->fetch_assoc() : null;
  $stmt->close();

  if (!$arch) {
    echo json_encode(['success' => false, 'message' => 'Archived client not found']);
    exit;
  }

  $first = $arch['first_name'] ?? '';
  $last  = $arch['last_name'] ?? '';
  $contact = $arch['contact_no'] ?? null;
  $password = $arch['password'] ?? null;

  // Check if user already exists in users table
  $stmt = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
  $stmt->bind_param('s', $email);
  $stmt->execute();
  $stmt->bind_result($existingUserId);
  $exists = $stmt->fetch();
  $stmt->close();

  if ($exists && $existingUserId) {
    // update existing user (reactivate)
    $stmt = $conn->prepare('UPDATE users SET first_name = ?, last_name = ?, contact_no = ?, password = ?, status = ? WHERE id = ?');
    $status = 'active';
    $stmt->bind_param('sssssi', $first, $last, $contact, $password, $status, $existingUserId);
    $ok = $stmt->execute();
    $stmt->close();
    if (!$ok) throw new Exception('Failed to update existing user');
    $restoredUserId = $existingUserId;
  } else {
    // insert new user
    $stmt = $conn->prepare('INSERT INTO users (first_name, last_name, email, contact_no, password, created_at, status) VALUES (?, ?, ?, ?, ?, NOW(), ?)');
    $status = 'active';
    $stmt->bind_param('ssssss', $first, $last, $email, $contact, $password, $status);
    $ok = $stmt->execute();
    if (!$ok) {
      $err = $stmt->error;
      $stmt->close();
      throw new Exception('Failed to insert user: ' . $err);
    }
    $restoredUserId = $stmt->insert_id;
    $stmt->close();
  }

  // delete from archive_clients
  $stmt = $conn->prepare('DELETE FROM archive_clients WHERE id = ?');
  $stmt->bind_param('i', $arch['id']);
  $ok = $stmt->execute();
  $stmt->close();
  if (!$ok) throw new Exception('Failed to remove archive entry');

  echo json_encode(['success' => true, 'message' => 'Client restored', 'user_id' => $restoredUserId]);
  exit;
} catch (Exception $ex) {
  // return generic message, avoid leaking sensitive details
  error_log('restore_client error: ' . $ex->getMessage());
  echo json_encode(['success' => false, 'message' => 'Server error while restoring']);
  exit;
}
?>
