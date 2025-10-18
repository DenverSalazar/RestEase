<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include_once '../Includes/db.php';
$user_avatar_html = '';
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $stmt = $conn->prepare('SELECT first_name, last_name, profile_picture FROM users WHERE id = ?');
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $stmt->bind_result($fn, $ln, $pp);
    $stmt->fetch();
    $stmt->close();
    $has_profile_picture = $pp && file_exists('../uploads/' . $pp);
    $initials = strtoupper(substr($fn, 0, 1) . substr($ln, 0, 1));
    if ($has_profile_picture) {
        $user_avatar_html = '<img src="../uploads/' . htmlspecialchars($pp) . '" alt="Avatar" class="navbar-avatar" style="width:36px;height:36px;border-radius:50%;object-fit:cover;border:2px solid #b2c9db;">';
    } else {
        $user_avatar_html = '<div class="navbar-avatar-initials" style="width:36px;height:36px;border-radius:50%;background:#4B7BEC;color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.1rem;font-weight:700;letter-spacing:1px;user-select:none;border:2px solid #b2c9db;">' . $initials . '</div>';
    }
}
$user_id = $_SESSION['user_id'] ?? null;
$latest_notifications = [];
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
            $latest_notifications[] = [
                'status' => 'welcome',
                'type' => '',
                'name' => '',
                'created_at' => $created_at
            ];
        }
    }
    $stmt->close();
    // Accepted requests
    $stmt = $conn->prepare("SELECT 'accepted' AS status, type, first_name, middle_name, last_name, created_at FROM accepted_request WHERE user_id = ? ORDER BY created_at DESC LIMIT 2");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $latest_notifications[] = [
            'status' => 'accepted',
            'type' => $row['type'],
            'name' => trim($row['first_name'].' '.($row['middle_name']??'').' '.$row['last_name']),
            'created_at' => $row['created_at']
        ];
    }
    $stmt->close();
    // Denied requests
    $stmt = $conn->prepare("SELECT 'denied' AS status, type, first_name, middle_name, last_name, created_at FROM denied_request WHERE user_id = ? ORDER BY created_at DESC LIMIT 2");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $latest_notifications[] = [
            'status' => 'denied',
            'type' => $row['type'],
            'name' => trim($row['first_name'].' '.($row['middle_name']??'').' '.$row['last_name']),
            'created_at' => $row['created_at']
        ];
    }
    $stmt->close();
    // Assessment notifications
    $stmt = $conn->prepare("SELECT message, link, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 3");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $latest_notifications[] = [
            'status' => 'assessment',
            'message' => $row['message'],
            'link' => $row['link'],
            'created_at' => $row['created_at']
        ];
    }
    $stmt->close();
    // Sort notifications by date, newest first
    usort($latest_notifications, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    $new_count = count($latest_notifications);
    if (isset($_SESSION['notifications_read']) && $_SESSION['notifications_read']) {
        $new_count = 0;
    }
}
if (isset($_POST['mark_all_read'])) {
    $_SESSION['notifications_read'] = true;
}
?>
<!-- Custom Navbar -->
<style>
/* Mobile notification bell placement and visibility */
.mobile-notification { display:none; align-items:center; gap:6px; color:inherit; text-decoration:none; cursor:pointer; transform: translateX(0); z-index:2100; }
.mobile-notification i { font-size:1.05rem; }
.mobile-notification .nbadge { position:absolute; top:-7px; right:-7px; background:#e74c3c; color:#fff; border-radius:50%; font-size:0.7rem; padding:1px 5px; font-weight:600; min-width:16px; text-align:center; line-height:1; box-shadow:0 1px 4px rgba(0,0,0,0.12); z-index:2200; }

/* Ensure header layout and mobile controls alignment */
.navbar-top { display:flex; align-items:center; justify-content:space-between; gap:8px; position:relative; }
.mobile-controls { display:flex; align-items:center; gap:12px; z-index:2000; }

/* Mobile-only profile link inside the menu */
.mobile-only-profile { display:none; padding:0.6rem 0; color:inherit; text-decoration:none; font-weight:500; }
.mobile-only-profile:hover { color:#4B7BEC; }

/* Mobile-only logout (danger color) */
.mobile-only-logout { display:none; padding:0.6rem 0; color:#e74c3c; text-decoration:none; font-weight:600; }
.mobile-only-logout:hover { color:#c0392b; }

/* On small screens show mobile-only profile & logout */
@media (max-width: 768px) {
    .mobile-notification { display:inline-flex; position:relative; transform: translateX(-6px); }
    .navbar-links .notification-bell-desktop { display:none !important; }
    .mobile-only-profile { display:block; }
    .mobile-only-logout { display:block; }

    /* Hide profile avatar on small devices - avatar not needed in mobile view */
    #profileAvatar { display: none !important; }
}
</style>

<nav class="custom-navbar position-relative">
    <div class="container navbar-top position-relative">
        <a href="#" class="navbar-brand">
            <img src="../assets/RE logo New.png" alt="RestEase Logo" style="height: 32px;">
        </a>

        <!-- Right-side mobile controls: bell immediately left of menu -->
        <div class="mobile-controls" aria-hidden="false">
            <a href="#" id="notificationBellMobile" class="mobile-notification" onclick="toggleNotificationDropdown(event, this)" aria-label="Notifications (mobile)">
                <i class="fas fa-bell"></i>
                <?php if ($new_count > 0): ?>
                    <span class="nbadge"><?php echo $new_count; ?></span>
                <?php endif; ?>
            </a>

            <button class="navbar-toggler" type="button" aria-label="Toggle navigation" onclick="toggleMobileMenu()">
                <i class="fas fa-bars"></i>
            </button>
        </div>

        <div class="navbar-links">
            <button class="navbar-close" type="button" aria-label="Close menu" onclick="toggleMobileMenu()">
                <i class="fas fa-times"></i>
            </button>
            <a href="ClientHome.php">Home</a>
            <a href="./clientabout-us.php">About Us</a>
            <a href="./clientcontact-us.php">Contact Us</a>

            <!-- Mobile-only Profile & Logout links (visible only on small screens) -->
            <?php if (!empty($user_id)): ?>
                <a href="../ClientSide/clientprofile.php" class="mobile-only-profile">Profile</a>
                <a href="../logout.php" class="mobile-only-logout" style="color: red;">Log Out</a>
            <?php endif; ?>

            <!-- Desktop bell (hidden on small screens via CSS class) -->
            <a href="#" id="notificationBell" class="notification-bell-desktop" onclick="toggleNotificationDropdown(event, this)" style="position:relative;display:inline-block;">
                <i class="fas fa-bell"></i>
                <?php if ($new_count > 0): ?>
                    <span style="position:absolute;top:-7px;right:-7px;background:#e74c3c;color:#fff;border-radius:50%;font-size:0.7rem;padding:1px 5px;font-weight:600;min-width:16px;text-align:center;line-height:1;box-shadow:0 1px 4px rgba(0,0,0,0.12);z-index:2;"> <?php echo $new_count; ?> </span>
                <?php endif; ?>
            </a>

            <a href="#" id="profileAvatar" onclick="toggleProfileDropdown(event)"><?php echo $user_avatar_html; ?></a>
            <div class="profile-dropdown" id="profileDropdown" style="display:none;position:absolute;top:44px;right:0;width:180px;background:#fff;border-radius:12px;box-shadow:0 4px 18px rgba(0,0,0,0.13);z-index:1000;overflow:hidden;">
                <div style="padding:0.75rem 1rem;border-bottom:1px solid #e5e9f2;display:flex;align-items:center;gap:0.7rem;cursor:pointer;"
                     onclick="window.location.href='../ClientSide/clientprofile.php'">
                    <i class="fas fa-user" style="color:#4B7BEC;font-size:1.1rem;"></i>
                    <span style="font-size:1rem;font-weight:500;">My Profile</span>
                </div>
                <div style="padding:0.75rem 1rem;display:flex;align-items:center;gap:0.7rem;cursor:pointer;color:#e74c3c;font-weight:500;"
                     onclick="window.location.href='../logout.php'">
                    <i class="fas fa-sign-out-alt" style="color:#e74c3c;font-size:1.1rem;"></i>
                    <span style="font-size:1rem;">Log Out</span>
                </div>
            </div>
            <div class="notification-dropdown" id="notificationDropdown" style="display:none;position:absolute;top:44px;right:0;width:340px;background:#fff;border-radius:14px;box-shadow:0 4px 24px rgba(0,0,0,0.14);z-index:1000;padding:0.5rem 0;overflow:hidden;">
                <div style="padding:0.75rem 1.25rem;border-bottom:1px solid #e5e9f2;font-weight:600;display:flex;justify-content:space-between;align-items:center;background:#f7faff;">
                    <span style="font-size:1.05rem;letter-spacing:0.5px;">Notifications</span>
                </div>
                <div style="max-height:260px;overflow-y:auto;">
                    <?php if ($user_id && count($latest_notifications) > 0): ?>
                        <?php foreach ($latest_notifications as $notif): ?>
                            <div style="padding:0.85rem 1.25rem;border-bottom:1px solid #f2f2f2;display:flex;align-items:flex-start;gap:0.75rem;">
                                <div style="flex-shrink:0;">
                                    <?php if ($notif['status'] === 'accepted'): ?>
                                        <i class="fas fa-check-circle" style="color:#2ecc71;font-size:1.25rem;"></i>
                                    <?php elseif ($notif['status'] === 'denied'): ?>
                                        <i class="fas fa-times-circle" style="color:#e74c3c;font-size:1.25rem;"></i>
                                    <?php elseif ($notif['status'] === 'welcome'): ?>
                                        <i class="fas fa-smile-beam" style="color:#4B7BEC;font-size:1.25rem;"></i>
                                    <?php elseif ($notif['status'] === 'assessment'): ?>
                                        <i class="fas fa-file-invoice-dollar" style="color:#f39c12;font-size:1.25rem;"></i>
                                    <?php endif; ?>
                                </div>
                                <div style="flex:1;min-width:0;">
                                    <span style="font-weight:500;font-size:1rem;">
                                        <?php if ($notif['status'] === 'accepted'): ?>Request Accepted<?php elseif ($notif['status'] === 'denied'): ?>Request Denied<?php elseif ($notif['status'] === 'welcome'): ?>Welcome to RestEase!<?php elseif ($notif['status'] === 'assessment'): ?>Assessment of Fees<?php endif; ?>
                                    </span><br>
                                    <?php if ($notif['status'] === 'accepted' || $notif['status'] === 'denied'): ?>
                                        <span style="font-size:0.97rem;">Type: <b><?php echo htmlspecialchars($notif['type'] ?? ''); ?></b></span><br>
                                        <span style="font-size:0.97rem;">Name: <b><?php echo htmlspecialchars($notif['name'] ?? ''); ?></b></span><br>
                                    <?php elseif ($notif['status'] === 'assessment'): ?>
                                        <span style="font-size:0.97rem;"><?php echo htmlspecialchars($notif['message']); ?></span><br>
                                    <?php endif; ?>
                                    <small style="color:#888;font-size:0.93rem;display:block;margin-top:2px;"><?php echo date('M d, Y h:i A', strtotime($notif['created_at'])); ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="padding:1.25rem;text-align:center;color:#888;font-size:1rem;">No notifications yet.</div>
                    <?php endif; ?>
                </div>
                <div style="text-align:center;padding:0.75rem 1.25rem;background:#f7faff;border-top:1px solid #e5e9f2;">
                    <a href="../ClientSide/view_all_notifications.php" style="color:#4B7BEC;font-weight:500;text-decoration:none;font-size:0.98rem;">View all</a>
                </div>
            </div>
        </div>
    </div>
    <div class="navbar-overlay" onclick="toggleMobileMenu()"></div>
</nav>
<!-- End Custom Navbar -->
<script>
function toggleMobileMenu() {
    var links = document.querySelector('.navbar-links');
    var overlay = document.querySelector('.navbar-overlay');
    links.classList.toggle('show');
    overlay.classList.toggle('show');
}
function toggleNotificationDropdown(e, sourceEl) {
    e.preventDefault();
    e.stopPropagation();
    var dropdown = document.getElementById('notificationDropdown');
    // Prefer the explicitly provided source element, otherwise fallback
    var bell = sourceEl || e.currentTarget || e.target || document.getElementById('notificationBell') || document.getElementById('notificationBellMobile');
    if (!bell) return;
    // Toggle visibility
    if (dropdown.style.display === 'none' || dropdown.style.display === '') {
        dropdown.style.display = 'block';
        // Position dropdown under the invoking bell (calculate relative to its offset parent)
        var rect = bell.getBoundingClientRect();
        // Place dropdown so its right edge aligns with bell's right edge and appears below it
        dropdown.style.top = (bell.offsetTop + bell.offsetHeight + 8) + 'px';
        // If dropdown is in the same container anchored to right, keep right:0 to preserve layout
        dropdown.style.right = '0px';
        setTimeout(function() {
            document.addEventListener('click', closeDropdown);
        }, 0);
    } else {
        dropdown.style.display = 'none';
        document.removeEventListener('click', closeDropdown);
    }
    function closeDropdown(event) {
        if (!dropdown.contains(event.target) && event.target !== bell && !bell.contains(event.target)) {
            dropdown.style.display = 'none';
            document.removeEventListener('click', closeDropdown);
        }
    }
}
function toggleProfileDropdown(e) {
    e.preventDefault();
    e.stopPropagation();
    var dropdown = document.getElementById('profileDropdown');
    var avatar = document.getElementById('profileAvatar');
    if (dropdown.style.display === 'none' || dropdown.style.display === '') {
        dropdown.style.display = 'block';
        var avatarRect = avatar.getBoundingClientRect();
        dropdown.style.top = (avatar.offsetTop + avatar.offsetHeight + 8) + 'px';
        dropdown.style.right = '0px';
        setTimeout(function() {
            document.addEventListener('click', closeProfileDropdown);
        }, 0);
    } else {
        dropdown.style.display = 'none';
        document.removeEventListener('click', closeProfileDropdown);
    }
    function closeProfileDropdown(event) {
        if (!dropdown.contains(event.target) && event.target !== avatar) {
            dropdown.style.display = 'none';
            document.removeEventListener('click', closeProfileDropdown);
        }
    }
}
function updateNotificationBadge() {
    fetch('../ClientSide/get_notification_count.php')
        .then(response => response.json())
        .then(data => {
            // remove existing badges from both desktop and mobile bells
            var desktopBell = document.getElementById('notificationBell');
            var mobileBell = document.getElementById('notificationBellMobile');
            if (desktopBell) {
                var span = desktopBell.querySelector('span');
                if (span) span.remove();
            }
            if (mobileBell) {
                var spanm = mobileBell.querySelector('.nbadge');
                if (spanm) spanm.remove();
            }
            if (data.count > 0) {
                // create badge for desktop
                if (desktopBell) {
                    var span = document.createElement('span');
                    span.textContent = data.count;
                    span.style.position = 'absolute';
                    span.style.top = '-7px';
                    span.style.right = '-7px';
                    span.style.background = '#e74c3c';
                    span.style.color = '#fff';
                    span.style.borderRadius = '50%';
                    span.style.fontSize = '0.7rem';
                    span.style.padding = '1px 5px';
                    span.style.fontWeight = '600';
                    span.style.minWidth = '16px';
                    span.style.textAlign = 'center';
                    span.style.lineHeight = '1';
                    span.style.boxShadow = '0 1px 4px rgba(0,0,0,0.12)';
                    span.style.zIndex = '2';
                    desktopBell.appendChild(span);
                }
                // create badge for mobile
                if (mobileBell) {
                    var spanm = document.createElement('span');
                    spanm.className = 'nbadge';
                    spanm.textContent = data.count;
                    mobileBell.appendChild(spanm);
                }
            }
        });
}

// Real-time notification dropdown update
function updateNotificationDropdown() {
    fetch('../ClientSide/get_latest_notifications.php')
        .then(response => response.json())
        .then(data => {
            var dropdown = document.getElementById('notificationDropdown');
            var container = dropdown.querySelector('div[max-height]');
            if (!container) {
                container = dropdown.querySelector('div[style*="max-height"]');
            }
            if (!container) return;
            container.innerHTML = '';
            if (data && data.length > 0) {
                data.forEach(function(notif) {
                    let icon = '';
                    let color = '';
                    let title = '';
                    if (notif.status === 'accepted') {
                        icon = '<i class="fas fa-check-circle" style="color:#2ecc71;font-size:1.25rem;"></i>';
                        title = 'Request Accepted';
                    } else if (notif.status === 'denied') {
                        icon = '<i class="fas fa-times-circle" style="color:#e74c3c;font-size:1.25rem;"></i>';
                        title = 'Request Denied';
                    } else if (notif.status === 'welcome') {
                        icon = '<i class="fas fa-smile-beam" style="color:#4B7BEC;font-size:1.25rem;"></i>';
                        title = 'Welcome to RestEase!';
                    } else if (notif.status === 'assessment') {
                        icon = '<i class="fas fa-file-invoice-dollar" style="color:#f39c12;font-size:1.25rem;"></i>';
                        title = 'Assessment of Fees';
                    }
                    let html = `<div style="padding:0.85rem 1.25rem;border-bottom:1px solid #f2f2f2;display:flex;align-items:flex-start;gap:0.75rem;">
                        <div style="flex-shrink:0;">${icon}</div>
                        <div style="flex:1;min-width:0;">
                            <span style="font-weight:500;font-size:1rem;">${title}</span><br>`;
                    if (notif.status === 'accepted' || notif.status === 'denied') {
                        html += `<span style="font-size:0.97rem;">Type: <b>${notif.type ?? ''}</b></span><br>
                                 <span style="font-size:0.97rem;">Name: <b>${notif.name ?? ''}</b></span><br>`;
                    } else if (notif.status === 'assessment') {
                        html += `<span style="font-size:0.97rem;">${notif.message}</span><br>`;
                    }
                    html += `<small style="color:#888;font-size:0.93rem;display:block;margin-top:2px;">${notif.created_at}</small>
                        </div>
                    </div>`;
                    container.innerHTML += html;
                });
            } else {
                container.innerHTML = '<div style="padding:1.25rem;text-align:center;color:#888;font-size:1rem;">No notifications yet.</div>';
            }
        });
}

// Poll every 5 seconds for badge and dropdown
setInterval(function() {
    updateNotificationBadge();
    updateNotificationDropdown();
}, 5000);
document.addEventListener('DOMContentLoaded', function() {
    updateNotificationBadge();
    updateNotificationDropdown();
});
</script>
