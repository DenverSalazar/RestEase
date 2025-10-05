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
        #delete-mode-controls {
            min-width: 180px;
        }
        #main-delete-btn, #delete-all-btn, #cancel-delete-btn {
            min-width: 38px;
            min-height: 38px;
            padding: 0 12px;
            font-size: 1rem;
        }
        #main-delete-btn i, #delete-all-btn i, #cancel-delete-btn i {
            font-size: 1.1rem;
        }
        .notification-card-wrapper {
            margin-bottom: 0.5rem;
        }
        .notification-card {
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
        }
    </style>
</head>
<body style="background:#f6f8fa;min-height:100vh;display:flex;flex-direction:column;">
    <div class="container py-4 flex-grow-1">
        <div class="d-flex align-items-center mb-3" style="gap:12px;">
            <!-- Back button leftmost, beside header -->
            <button type="button" onclick="window.history.back();" class="btn btn-link" style="font-size:1.1rem;padding:0;">
                <i class="fas fa-arrow-left"></i> Back
            </button>
            <h2 class="mb-0 text-center" style="flex:1;">All Notifications</h2>
        </div>
        <div class="d-flex justify-content-end align-items-center mb-4" id="delete-mode-controls" style="gap: 0.5rem;">
            <button id="delete-all-btn" class="btn btn-danger btn-sm" style="display:none;">Delete All</button>
            <button id="main-delete-btn" class="btn btn-outline-danger btn-sm ms-2" title="Delete Notifications">
                <i class="fas fa-trash-alt"></i> Delete
            </button>
            <button id="cancel-delete-btn" class="btn btn-secondary btn-sm ms-2" style="display:none;">Cancel</button>
        </div>
        <?php if ($user_id && count($notifications) > 0): ?>
            <div class="row g-4" id="notifications-list">
                <?php foreach ($notifications as $idx => $notif): ?>
                    <div class="col-12 col-sm-6 col-md-4 notification-card-wrapper" style="position:relative;">
                        <div class="card shadow-sm h-100 notification-card" data-idx="<?php echo $idx; ?>" style="transition:box-shadow 0.2s;">
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
                            <!-- Checkbox for deletion mode -->
                            <input type="checkbox" class="form-check-input notif-checkbox" style="position:absolute;top:12px;right:12px;display:none;z-index:2;width:20px;height:20px;" 
                                data-status="<?php echo htmlspecialchars($notif['status']); ?>"
                                data-id="<?php echo isset($notif['id']) ? htmlspecialchars($notif['id']) : ''; ?>"
                                data-created_at="<?php echo isset($notif['created_at']) ? htmlspecialchars($notif['created_at']) : ''; ?>"
                            >
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
    <script>
    // Deletion mode logic
    const mainDeleteBtn = document.getElementById('main-delete-btn');
    const deleteAllBtn = document.getElementById('delete-all-btn');
    const cancelDeleteBtn = document.getElementById('cancel-delete-btn');
    const notifCheckboxes = () => document.querySelectorAll('.notif-checkbox');
    const notificationCards = () => document.querySelectorAll('.notification-card-wrapper');
    let deletionMode = false;

    function setDeletionMode(on) {
        deletionMode = on;
        notifCheckboxes().forEach(cb => cb.style.display = on ? 'block' : 'none');
        notificationCards().forEach(card => {
            card.style.transition = 'box-shadow 0.2s';
            card.style.boxShadow = ''; // Remove red outline
            card.style.opacity = on ? '0.97' : '1';
        });
        deleteAllBtn.style.display = on ? 'inline-block' : 'none';
        cancelDeleteBtn.style.display = on ? 'inline-block' : 'none';
        mainDeleteBtn.classList.toggle('btn-outline-danger', !on);
        mainDeleteBtn.classList.toggle('btn-danger', on);
        mainDeleteBtn.innerHTML = on ? '<i class="fas fa-trash-alt"></i> Delete Selected' : '<i class="fas fa-trash-alt"></i> Delete';
        if (!on) notifCheckboxes().forEach(cb => cb.checked = false);
    }

    mainDeleteBtn.addEventListener('click', function() {
        if (!deletionMode) {
            setDeletionMode(true);
        } else {
            // Delete selected
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
                    selected.forEach(cb => cb.closest('.notification-card-wrapper').remove());
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
</body>
</html>
</html>
</body>
</html>
