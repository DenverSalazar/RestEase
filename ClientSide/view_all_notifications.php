<?php
session_start();
include '../Includes/navbar2.php';
include_once '../Includes/db.php';

$user_id = $_SESSION['user_id'] ?? null;
$notifications = [];
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
            $notifications[] = [
                'status' => 'welcome',
                'type' => '',
                'name' => '',
                'created_at' => $created_at
            ];
        }
    }
    $stmt->close();
    // Accepted requests
    $stmt = $conn->prepare("SELECT 'accepted' AS status, id, type, first_name, middle_name, last_name, created_at FROM accepted_request WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $notifications[] = [
            'id' => $row['id'],
            'status' => 'accepted',
            'type' => $row['type'],
            'name' => trim($row['first_name'].' '.($row['middle_name']??'').' '.$row['last_name']),
            'created_at' => $row['created_at']
        ];
    }
    $stmt->close();
    // Denied requests
    $stmt = $conn->prepare("SELECT 'denied' AS status, id, type, first_name, middle_name, last_name, created_at FROM denied_request WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $notifications[] = [
            'id' => $row['id'],
            'status' => 'denied',
            'type' => $row['type'],
            'name' => trim($row['first_name'].' '.($row['middle_name']??'').' '.$row['last_name']),
            'created_at' => $row['created_at']
        ];
    }
    $stmt->close();
    // Assessment notifications
    $stmt = $conn->prepare("SELECT message, link, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $notifications[] = [
            'status' => 'assessment',
            'message' => $row['message'],
            'link' => $row['link'],
            'created_at' => $row['created_at']
        ];
    }
    $stmt->close();
    // Sort notifications by date, newest first
    usort($notifications, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RestEase</title>
    <!-- Add Google Fonts for Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/footer.css">
    <link rel="stylesheet" href="../css/clienthome.css">
    <style>
        .notification-title {
            font-weight: 600;
            font-size: 1.1rem;
        }
        .notification-time {
            color: #888;
            font-size: 0.95rem;
        }
        .notification-status-accepted {
            color: #2ecc71;
        }
        .notification-status-denied {
            color: #e74c3c;
        }
    </style>
</head>
<body style="background:#f6f8fa;min-height:100vh;display:flex;flex-direction:column;">
    <div class="container py-4 flex-grow-1">
        <h2 class="mb-4 text-center">All Notifications</h2>
        <?php if ($user_id && count($notifications) > 0): ?>
            <div class="row g-4">
                <?php foreach ($notifications as $notif): ?>
                    <div class="col-12 col-sm-6 col-md-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-body d-flex flex-row align-items-center justify-content-between" style="min-height:140px;">
                                <div style="flex:1;">
                                    <span class="notification-title d-block mb-2">
                                        <?php if ($notif['status'] === 'accepted'): ?>
                                            <i class="fas fa-check-circle notification-status-accepted"></i> Request Accepted
                                        <?php elseif ($notif['status'] === 'denied'): ?>
                                            <i class="fas fa-times-circle notification-status-denied"></i> Request Denied
                                        <?php elseif ($notif['status'] === 'welcome'): ?>
                                            <i class="fas fa-smile-beam" style="color:#4B7BEC;"></i> Welcome to RestEase!
                                        <?php elseif ($notif['status'] === 'assessment'): ?>
                                            <i class="fas fa-file-invoice-dollar" style="color:#f39c12;"></i> Assessment of Fees
                                        <?php endif; ?>
                                    </span>
                                    <?php if ($notif['status'] === 'accepted' || $notif['status'] === 'denied'): ?>
                                        <span>Type: <b><?php echo htmlspecialchars($notif['type'] ?? ''); ?></b></span><br>
                                        <span>Name: <b><?php echo htmlspecialchars($notif['name'] ?? ''); ?></b></span><br>
                                    <?php elseif ($notif['status'] === 'assessment'): ?>
                                        <span><?php echo htmlspecialchars($notif['message']); ?></span><br>
                                    <?php endif; ?>
                                    <span class="notification-time d-block mt-2"><?php echo date('M d, Y h:i A', strtotime($notif['created_at'])); ?></span>
                                </div>
                                <?php if ($notif['status'] === 'accepted' || $notif['status'] === 'denied'): ?>
                                    <a href="notification_details.php?id=<?php echo isset($notif['id']) ? urlencode($notif['id']) : ''; ?>&type=<?php echo urlencode($notif['status']); ?>" class="btn btn-light border-0 ms-3" style="border-radius:50%;width:38px;height:38px;display:flex;align-items:center;justify-content:center;box-shadow:0 1px 4px rgba(0,0,0,0.08);" title="View Details">
                                        <i class="fas fa-arrow-right" style="font-size:1.25rem;color:#4B7BEC;"></i>
                                    </a>
                                <?php elseif ($notif['status'] === 'assessment'): ?>
                                    <a href="notification_details.php?type=assessment&created_at=<?php echo urlencode($notif['created_at']); ?>" class="btn btn-light border-0 ms-3" style="border-radius:50%;width:38px;height:38px;display:flex;align-items:center;justify-content:center;box-shadow:0 1px 4px rgba(0,0,0,0.08);" title="View Assessment Details">
                                        <i class="fas fa-arrow-right" style="font-size:1.25rem;color:#f39c12;"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="row justify-content-center">
                <div class="col-12 col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-body text-center">
                            <span class="notification-title">No notifications yet.</span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <footer style="margin-top:auto;">
        <?php include '../Includes/footer.php'; ?>
    </footer>
</body>
</html>
