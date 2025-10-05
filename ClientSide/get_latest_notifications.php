<?php
session_start();
include_once '../Includes/db.php';
$user_id = $_SESSION['user_id'] ?? null;
$latest_notifications = [];
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
            $latest_notifications[] = [
                'status' => 'welcome',
                'type' => '',
                'name' => '',
                'created_at' => date('M d, Y h:i A', strtotime($created_at))
            ];
        }
    }
    $stmt->close();
    // Accepted requests (last 1 day)
    $stmt = $conn->prepare("SELECT 'accepted' AS status, type, first_name, middle_name, last_name, created_at FROM accepted_request WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY) ORDER BY created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $latest_notifications[] = [
            'status' => 'accepted',
            'type' => $row['type'],
            'name' => trim($row['first_name'].' '.($row['middle_name']??'').' '.$row['last_name']),
            'created_at' => date('M d, Y h:i A', strtotime($row['created_at']))
        ];
    }
    $stmt->close();
    // Denied requests (last 1 day)
    $stmt = $conn->prepare("SELECT 'denied' AS status, type, first_name, middle_name, last_name, created_at FROM denied_request WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY) ORDER BY created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $latest_notifications[] = [
            'status' => 'denied',
            'type' => $row['type'],
            'name' => trim($row['first_name'].' '.($row['middle_name']??'').' '.$row['last_name']),
            'created_at' => date('M d, Y h:i A', strtotime($row['created_at']))
        ];
    }
    $stmt->close();
    // Assessment notifications (last 1 day)
    $stmt = $conn->prepare("SELECT message, link, created_at FROM notifications WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY) ORDER BY created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $latest_notifications[] = [
            'status' => 'assessment',
            'message' => $row['message'],
            'link' => $row['link'],
            'created_at' => date('M d, Y h:i A', strtotime($row['created_at']))
        ];
    }
    $stmt->close();
    // Sort notifications by date, newest first
    usort($latest_notifications, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
}
header('Content-Type: application/json');
echo json_encode($latest_notifications);
