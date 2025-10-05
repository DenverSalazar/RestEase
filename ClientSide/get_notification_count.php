<?php
session_start();
include_once '../Includes/db.php';
header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;
$new_count = 0;
if ($user_id) {
    // Welcome notification (first day)
    $stmt = $conn->prepare("SELECT created_at FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($created_at);
    if ($stmt->fetch()) {
        $account_created = date('Y-m-d', strtotime($created_at));
        $today = date('Y-m-d');
        if ($account_created === $today) {
            $new_count++;
        }
    }
    $stmt->close();
    // Accepted requests (last 1 day)
    $stmt = $conn->prepare("SELECT COUNT(*) FROM accepted_request WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($count_acc);
    $stmt->fetch();
    $new_count += $count_acc;
    $stmt->close();
    // Denied requests (last 1 day)
    $stmt = $conn->prepare("SELECT COUNT(*) FROM denied_request WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($count_den);
    $stmt->fetch();
    $new_count += $count_den;
    $stmt->close();
    // Assessments (last 1 day)
    $stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($count_assess);
    $stmt->fetch();
    $new_count += $count_assess;
    $stmt->close();
}
echo json_encode(['count' => $new_count]);
