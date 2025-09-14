<?php
session_start();
include_once '../Includes/db.php';

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

if ($action === 'delete_one') {
    $status = $data['status'] ?? '';
    if ($status === 'assessment') {
        // Delete from notifications table by user_id and created_at
        $created_at = $data['created_at'] ?? '';
        if ($created_at) {
            $stmt = $conn->prepare("DELETE FROM notifications WHERE user_id = ? AND created_at = ?");
            $stmt->bind_param('is', $user_id, $created_at);
            $stmt->execute();
            $stmt->close();
            echo json_encode(['success' => true]);
            exit;
        }
    } elseif ($status === 'accepted') {
        $id = intval($data['id'] ?? 0);
        if ($id) {
            $stmt = $conn->prepare("DELETE FROM accepted_request WHERE id = ? AND user_id = ?");
            $stmt->bind_param('ii', $id, $user_id);
            $stmt->execute();
            $stmt->close();
            echo json_encode(['success' => true]);
            exit;
        }
    } elseif ($status === 'denied') {
        $id = intval($data['id'] ?? 0);
        if ($id) {
            $stmt = $conn->prepare("DELETE FROM denied_request WHERE id = ? AND user_id = ?");
            $stmt->bind_param('ii', $id, $user_id);
            $stmt->execute();
            $stmt->close();
            echo json_encode(['success' => true]);
            exit;
        }
    }
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
} elseif ($action === 'delete_all') {
    // Delete all notifications for this user
    $conn->query("DELETE FROM notifications WHERE user_id = $user_id");
    $conn->query("DELETE FROM accepted_request WHERE user_id = $user_id");
    $conn->query("DELETE FROM denied_request WHERE user_id = $user_id");
    echo json_encode(['success' => true]);
    exit;
} elseif ($action === 'delete_selected') {
    $notifications = $data['notifications'] ?? [];
    $allOk = true;
    foreach ($notifications as $notif) {
        $status = $notif['status'] ?? '';
        if ($status === 'assessment') {
            $created_at = $notif['created_at'] ?? '';
            if ($created_at) {
                $stmt = $conn->prepare("DELETE FROM notifications WHERE user_id = ? AND created_at = ?");
                $stmt->bind_param('is', $user_id, $created_at);
                $stmt->execute();
                $stmt->close();
            } else {
                $allOk = false;
            }
        } elseif ($status === 'accepted') {
            $id = intval($notif['id'] ?? 0);
            if ($id) {
                $stmt = $conn->prepare("DELETE FROM accepted_request WHERE id = ? AND user_id = ?");
                $stmt->bind_param('ii', $id, $user_id);
                $stmt->execute();
                $stmt->close();
            } else {
                $allOk = false;
            }
        } elseif ($status === 'denied') {
            $id = intval($notif['id'] ?? 0);
            if ($id) {
                $stmt = $conn->prepare("DELETE FROM denied_request WHERE id = ? AND user_id = ?");
                $stmt->bind_param('ii', $id, $user_id);
                $stmt->execute();
                $stmt->close();
            } else {
                $allOk = false;
            }
        }
    }
    echo json_encode(['success' => $allOk]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
exit;
