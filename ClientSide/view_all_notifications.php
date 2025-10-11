<?php
session_start();
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
    <link rel="stylesheet" href="../css/view_all_notif.css">
</head>
<body>
    <?php include '../Includes/navbar2.php'; ?>
    <div class="main-content">
        <div class="cert-list-container">
            <div style="height:32px;"></div>
            <a href="ClientHome.php" class="cert-list-back" style="display:inline-block;color:#506C84;font-size:1.08rem;font-weight:500;margin-bottom:0px;text-decoration:none;cursor:pointer;transition:color 0.18s;">
                <i class="fas fa-arrow-left"></i> Back
            </a>
            <div class="cert-list-title text-muted" style="margin-bottom:18px;">All Notifications</div>
            <div class="notif-controls" style="margin-bottom:18px;display:flex;gap:10px;">
                <button id="delete-all-btn" class="notif-delete-all-btn" style="display:none;" title="Delete All"><i class="fas fa-trash"></i></button>
                <button id="main-delete-btn" class="notif-delete-btn" title="Delete Selected"><i class="fas fa-trash-alt"></i></button>
                <button id="cancel-delete-btn" class="notif-cancel-btn" style="display:none;" title="Cancel"><i class="fas fa-times"></i></button>
            </div>
            <?php if ($user_id && count($notifications) > 0): ?>
                <ul class="notif-list">
                    <div id="notifications-list">
                    <?php foreach ($notifications as $idx => $notif): 
                        // Color and icon logic
                        $borderColor = $notif['status'] === 'accepted' ? '#198754' : ($notif['status'] === 'denied' ? '#DC3545' : ($notif['status'] === 'welcome' ? '#4B7BEC' : '#FFC107'));
                        $bgColor = $notif['status'] === 'accepted' ? '#E9F7EF' : ($notif['status'] === 'denied' ? '#FDEDEC' : ($notif['status'] === 'welcome' ? '#EAF1FF' : '#FFF8E1'));
                        $icon = $notif['status'] === 'accepted' ? 'fa-check-circle' : ($notif['status'] === 'denied' ? 'fa-times-circle' : ($notif['status'] === 'welcome' ? 'fa-smile-beam' : 'fa-file-invoice-dollar'));
                        $iconColor = $notif['status'] === 'accepted' ? '#198754' : ($notif['status'] === 'denied' ? '#DC3545' : ($notif['status'] === 'welcome' ? '#4B7BEC' : '#FFC107'));
                    ?>
                        <li class="notif-card-wrapper" style="background:<?php echo $bgColor; ?>;border-left:8px solid <?php echo $borderColor; ?>;border-radius:12px;box-shadow:0 2px 8px rgba(44,62,80,0.08);margin-bottom:18px;padding:18px 24px;display:flex;align-items:center;justify-content:space-between;transition:box-shadow 0.18s;border:1px solid #e0e7ef;">
                            <input type="checkbox" class="notif-checkbox" style="margin-right:18px;display:none;"
                                data-status="<?php echo htmlspecialchars($notif['status']); ?>"
                                data-id="<?php echo isset($notif['id']) ? htmlspecialchars($notif['id']) : ''; ?>"
                                data-created_at="<?php echo isset($notif['created_at']) ? htmlspecialchars($notif['created_at']) : ''; ?>"
                            >
                            <span class="notif-icon" style="color:<?php echo $iconColor; ?>;margin-right:18px;font-size:1.5rem;">
                                <i class="fas <?php echo $icon; ?>"></i>
                            </span>
                            <div style="flex:1;display:flex;flex-direction:column;gap:2px;">
                                <div class="notif-main" style="font-weight:600;font-size:1.08rem;color:#222;">
                                    <?php if ($notif['status'] === 'accepted'): ?>
                                        Request Accepted
                                    <?php elseif ($notif['status'] === 'denied'): ?>
                                        Request Denied
                                    <?php elseif ($notif['status'] === 'welcome'): ?>
                                        Welcome to RestEase!
                                    <?php elseif ($notif['status'] === 'assessment'): ?>
                                        Assessment of Fees
                                    <?php endif; ?>
                                </div>
                                <div class="notif-desc" style="color:#666;font-size:0.97rem;">
                                    <?php if ($notif['status'] === 'accepted' || $notif['status'] === 'denied'): ?>
                                        Type: <b><?php echo htmlspecialchars($notif['type'] ?? ''); ?></b>
                                        &nbsp;|&nbsp; Name: <b><?php echo htmlspecialchars($notif['name'] ?? ''); ?></b>
                                    <?php elseif ($notif['status'] === 'assessment'): ?>
                                        <?php echo htmlspecialchars($notif['message']); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="notif-time" style="color:#888;font-size:0.95rem;white-space:nowrap;margin-left:18px;">
                                <?php echo date('M d, Y h:i A', strtotime($notif['created_at'])); ?>
                            </div>
                            <div class="notif-actions" style="text-align:right;margin-left:18px;">
                                <?php if ($notif['status'] === 'accepted' || $notif['status'] === 'denied'): ?>
                                    <a href="notification_details.php?id=<?php echo isset($notif['id']) ? urlencode($notif['id']) : ''; ?>&type=<?php echo urlencode($notif['status']); ?>" title="View Details">
                                        <i class="fas fa-arrow-right" style="font-size:1.25rem;color:#4B7BEC;"></i>
                                    </a>
                                <?php elseif ($notif['status'] === 'assessment'): ?>
                                    <a href="notification_details.php?type=assessment&created_at=<?php echo urlencode($notif['created_at']); ?>" title="View Assessment Details">
                                        <i class="fas fa-arrow-right" style="font-size:1.25rem;color:#f39c12;"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                    </div>
                </ul>
            <?php else: ?>
                <div class="main-content" style="display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:60vh;">
                    <div class="cert-list-container" style="max-width:500px;width:100%;margin-top:48px;">
                        <div class="no-records-msg text-muted" style="color:#888;font-size:1.15rem;text-align:center;margin:48px 0 24px 0;font-weight:500;">
                            No notifications available yet.<br>
                            Please contact the administrator or check back later.
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <footer style="margin-top:auto;">
        <?php include '../Includes/footer-client.php'; ?>
    </footer>
    <script>
    // Deletion mode logic
    const mainDeleteBtn = document.getElementById('main-delete-btn');
    const deleteAllBtn = document.getElementById('delete-all-btn');
    const cancelDeleteBtn = document.getElementById('cancel-delete-btn');
    const notifCheckboxes = () => document.querySelectorAll('.notif-checkbox');
    let deletionMode = false;

    function setDeletionMode(on) {
        deletionMode = on;
        notifCheckboxes().forEach(cb => cb.style.display = on ? 'inline-block' : 'none');
        deleteAllBtn.style.display = on ? 'inline-block' : 'none';
        cancelDeleteBtn.style.display = on ? 'inline-block' : 'none';
        mainDeleteBtn.innerHTML = on ? '<i class="fas fa-trash-alt"></i>' : '<i class="fas fa-trash-alt"></i>';
        if (!on) notifCheckboxes().forEach(cb => cb.checked = false);
    }

    mainDeleteBtn.addEventListener('click', function() {
        if (!deletionMode) {
            setDeletionMode(true);
        } else {
            const selected = Array.from(notifCheckboxes()).filter(cb => cb.checked);
            if (selected.length === 0) {
                alert('Select at least one notification to delete.');
                return;
            }
            if (!confirm('Delete selected notifications?')) return;
            const notifications = selected.map(cb => ({
                status: cb.getAttribute('data-status'),
                id: cb.getAttribute('data-id'),
                created_at: cb.getAttribute('data-created_at')
            }));
            fetch('delete_notification.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'delete_selected', notifications })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    selected.forEach(cb => cb.closest('.notif-card-wrapper').remove());
                    setDeletionMode(false);
                } else {
                    alert('Failed to delete selected notifications.');
                }
            })
            .catch(() => alert('Failed to delete selected notifications.'));
        }
    });

    deleteAllBtn.addEventListener('click', function() {
        if (!confirm('Delete ALL notifications?')) return;
        fetch('delete_notification.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete_all' })
        })
        .then(res => res.json())
        .then(data => { // <-- fixed here
            if (data.success) {
                document.getElementById('notifications-list').innerHTML = '';
                setDeletionMode(false);
            } else {
                alert('Failed to delete all notifications.');
            }
        })
        .catch(() => alert('Failed to delete all notifications.'));
    });

    cancelDeleteBtn.addEventListener('click', function() {
        setDeletionMode(false);
    });

    // Optional: visually highlight cards on checkbox hover
    document.addEventListener('mouseover', function(e) {
        if (e.target.classList.contains('notif-checkbox')) {
            e.target.closest('.notification-card-wrapper').style.boxShadow = ''; // Remove highlight
        }
    });
    document.addEventListener('mouseout', function(e) {
        if (e.target.classList.contains('notif-checkbox')) {
            e.target.closest('.notification-card-wrapper').style.boxShadow = '';
        }
    });
    </script>
</body>
</html>

