<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'unauthenticated']);
    exit;
}

$uid = intval($_SESSION['user_id']);

// set session read timestamp (use server time)
$_SESSION['notifications_read_at'] = date('Y-m-d H:i:s');

// --- NEW: expose the timestamp to client JS & future page loads via a cookie (not HttpOnly) ---
setcookie('notifications_read_at', $_SESSION['notifications_read_at'], 0, '/');
// --- end new ---

// also mark persisted notifications as read in DB (safe no-op if already read)
@include_once __DIR__ . '/../Includes/db.php';
if (isset($conn)) {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
    if ($stmt) {
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $stmt->close();
    }
}

echo json_encode(['success' => true]);
