<?php
session_start();
include_once '../Includes/db.php';

header('Content-Type: application/json; charset=utf-8');

$user_id = $_SESSION['user_id'] ?? null;
$out = [];

if (!$user_id) {
    echo json_encode([]);
    exit;
}

// helper to safely push item
$push = function($item) use (&$out) {
    $out[] = $item;
};

// Accepted (latest 2)
$stmt = $conn->prepare("SELECT type, first_name, middle_name, last_name, created_at FROM accepted_request WHERE user_id = ? ORDER BY created_at DESC LIMIT 2");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $push([
        'status' => 'accepted',
        'type' => $row['type'],
        'name' => trim($row['first_name'].' '.($row['middle_name']??'').' '.$row['last_name']),
        'created_at' => $row['created_at']
    ]);
}
$result->free();
$stmt->close();

// Denied (latest 2)
$stmt = $conn->prepare("SELECT type, first_name, middle_name, last_name, created_at FROM denied_request WHERE user_id = ? ORDER BY created_at DESC LIMIT 2");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $push([
        'status' => 'denied',
        'type' => $row['type'],
        'name' => trim($row['first_name'].' '.($row['middle_name']??'').' '.$row['last_name']),
        'created_at' => $row['created_at']
    ]);
}
$result->free();
$stmt->close();

// Persisted notifications (latest 3) — map exact welcome message to "welcome"
$stmt = $conn->prepare("SELECT id, message, link, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 3");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $msg = $row['message'] ?? '';
    $trimmed = trim($msg);
    // case-insensitive compare for safety
    if (mb_strtolower($trimmed, 'UTF-8') === mb_strtolower('Welcome to RestEase!', 'UTF-8')) {
        $status = 'welcome';
    } else {
        $status = 'assessment';
    }
    $push([
        'status' => $status,
        'message' => $msg,
        'link' => $row['link'],
        'created_at' => $row['created_at']
    ]);
}
$result->free();
$stmt->close();

// Sort by created_at desc and return
usort($out, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

echo json_encode(array_values($out));
