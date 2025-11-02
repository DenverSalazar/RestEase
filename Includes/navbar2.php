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
    // changed: use get_result() and free it before preparing another statement
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $created_at = $row['created_at'];
        $account_created = date('Y-m-d', strtotime($created_at));
        $today = date('Y-m-d');
        if ($account_created === $today) {
            // check if a persisted welcome notification already exists for this user
            $msg = 'Welcome to RestEase!';
            $chk = $conn->prepare("SELECT id FROM notifications WHERE user_id = ? AND TRIM(message) = ? LIMIT 1");
            $chk->bind_param("is", $user_id, $msg);
            $chk->execute();
            $chk->store_result();
            if ($chk->num_rows === 0) {
                // no persisted welcome found — add a transient welcome so the user sees it immediately
                $latest_notifications[] = [
                    'status' => 'welcome',
                    'type' => '',
                    'name' => '',
                    'created_at' => $created_at
                ];
            }
            $chk->close();
        }
    }
    $result->free();
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
    // Assessment notifications (map persisted welcome -> 'welcome')
    $stmt = $conn->prepare("SELECT message, link, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 3");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $trimmed = trim($row['message'] ?? '');
        $lc = strtolower($trimmed);
        // map message to a status for the UI
        if ($trimmed === 'Welcome to RestEase!') {
            $status = 'welcome';
        } elseif (stripos($lc, 'expiry') !== false || stripos($lc, 'expire') !== false || stripos($lc, 'validity') !== false) {
            // treat any admin-sent expiry/validity notice as "expiry"
            $status = 'expiry';
        } elseif (strpos($lc, 'renew') !== false) {
            $status = 'renewal';
        } else {
            $status = 'assessment';
        }
        $latest_notifications[] = [
            'status'      => $status,
            'message'     => $row['message'],
            'link'        => $row['link'],
            'created_at'  => $row['created_at']
        ];
    }
    $stmt->close();
    // Sort notifications by date, newest first
    usort($latest_notifications, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });

    // Get unread notifications count from DB (notifications.is_read = 0)
    $new_count = 0;
    $cntStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM notifications WHERE user_id = ? AND is_read = 0");
    $cntStmt->bind_param("i", $user_id);
    $cntStmt->execute();
    $cntRes = $cntStmt->get_result();
    if ($cntRow = $cntRes->fetch_assoc()) {
        $new_count = intval($cntRow['cnt']);
    }
    $cntRes->free();
    $cntStmt->close();

    // allow session override (existing behavior)
    if (isset($_SESSION['notifications_read']) && $_SESSION['notifications_read']) {
        $new_count = 0;
    }
}
if (isset($_POST['mark_all_read'])) {
    $_SESSION['notifications_read'] = true;
}

// Add: compact relative time helper to mimic "2HR", "3DY" style (guarded to avoid redeclare errors)
if (!function_exists('re_short_relative_time')) {
    function re_short_relative_time($datetime) {
        $ts = strtotime($datetime);
        if (!$ts) return '';
        $diff = time() - $ts;
        if ($diff < 3600) { // minutes
            $m = max(1, round($diff / 60));
            return $m . 'MIN';
        } elseif ($diff < 86400) { // hours
            return round($diff / 3600) . 'HR';
        }
        return round($diff / 86400) . 'DY';
    }
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

    /* ===== Responsive notification dropdown: keep centered with equal side margins ===== */
    /* Use a responsive width (leave ~17px margin on each side), cap at 360px */
    .notification-dropdown {
        left: 50% !important;
        right: auto !important;
        transform: translateX(-50%) !important;
        width: calc(100% - 34px) !important; /* ~17px margin both sides */
        max-width: 360px !important;
        border-radius: 12px;
    }
    /* Slightly reduce list height on small screens to avoid overflow beyond viewport */
    .notification-dropdown .nd-list { max-height: 60vh; }
}

/* === Redesigned notification dropdown (card-like, similar to the image) === */
.notification-dropdown { width: 360px !important; border-radius: 14px; overflow: hidden; }
.nd-header { padding: 0.75rem 1.25rem 0.4rem; background: #fff; border-bottom: 1px solid #e9eef6; }
.nd-title { font-weight: 700; letter-spacing: .2px; font-size: 1.05rem; margin-bottom: .35rem; }
.nd-tabs { display: flex; gap: 18px; }
.nd-tab { position: relative; padding: .35rem 0; color: #7b8794; font-weight: 600; font-size: .92rem; cursor: default; user-select: none; }
.nd-tab.active { color: #2f3a4a; }
.nd-tab.active::after { content: ''; position: absolute; left: 0; right: 0; bottom: -10px; height: 2px; background: #4B7BEC; border-radius: 2px; }

/* List */
.nd-list { max-height: 260px; overflow-y: auto; background: #fff; }
.nd-item { display: flex; gap: 12px; padding: 12px 18px; border-bottom: 1px solid #f2f4f8; }
.nd-avatar { flex: 0 0 36px; height: 36px; width: 36px; border-radius: 50%; display: grid; place-items: center; font-weight: 700; color: #fff; }
.nd-avatar--accepted { background: #2ecc71; }
.nd-avatar--denied { background: #e74c3c; }
.nd-avatar--welcome { background: #4B7BEC; }
.nd-avatar--assessment { background: #f39c12; }
/* New: renewal reminder avatar color */
.nd-avatar--renewal { background: #8e44ad; }
/* New: explicit expiry avatar color (amber) */
.nd-avatar--expiry { background: #f59e0b; }
.nd-body { flex: 1; min-width: 0; }
.nd-top { display: flex; align-items: baseline; justify-content: space-between; gap: 8px; }
.nd-name { font-weight: 700; color: #2f3a4a; font-size: .98rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.nd-time { color: #98a2b3; font-weight: 700; font-size: .75rem; letter-spacing: .6px; }
.nd-text { color: #394150; font-size: .95rem; line-height: 1.25rem; margin-top: 2px; }
.nd-sub { color: #6b7785; font-size: .9rem; }

/* Footer */
.nd-footer { text-align: center; padding: .75rem 1.25rem; background: #f7faff; border-top: 1px solid #e5e9f2; }
.nd-footer a { color: #4B7BEC; font-weight: 600; text-decoration: none; font-size: .98rem; }
</style>

<nav class="custom-navbar position-relative">
    <div class="container navbar-top position-relative">
        <a href="ClientHome.php" class="navbar-brand">
            <img src="../assets/RE Logo New.png" alt="RestEase Logo" style="height: 32px;">
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
        </div>
 <div class="notification-dropdown" id="notificationDropdown" style="display:none;position:absolute;top:44px;right:0;width:340px;background:#fff;border-radius:14px;box-shadow:0 4px 24px rgba(0,0,0,0.14);z-index:1000;padding:0.5rem 0;overflow:hidden;">
                <div style="padding:0.75rem 1.25rem;border-bottom:1px solid #e5e9f2;font-weight:600;display:flex;justify-content:space-between;align-items:center;background:#f7faff;">
                    <span style="font-size:1.05rem;letter-spacing:0.5px;">Notifications</span>
                </div>

                <div id="notifList" class="nd-list">
                    <?php if ($user_id && count($latest_notifications) > 0): ?>
                        <?php foreach ($latest_notifications as $notif): ?>
                            <?php
                                $status = $notif['status'];
                                // Avatar style + title/name + main text
                                $avatarClass = 'nd-avatar--welcome';
                                $avatarIcon  = '<i class="fas fa-smile-beam"></i>';
                                $titleName   = 'RestEase';
                                $text        = 'Welcome to RestEase!';
                                if ($status === 'accepted') {
                                    $avatarClass = 'nd-avatar--accepted';
                                    $avatarIcon  = '<i class="fas fa-check"></i>';
                                    $titleName   = htmlspecialchars($notif['name'] ?? 'Request');
                                    $text        = 'accepted your request.';
                                } elseif ($status === 'denied') {
                                    $avatarClass = 'nd-avatar--denied';
                                    $avatarIcon  = '<i class="fas fa-times"></i>';
                                    $titleName   = htmlspecialchars($notif['name'] ?? 'Request');
                                    $text        = 'denied your request.';
                                } elseif ($status === 'assessment') {
                                    $avatarClass = 'nd-avatar--assessment';
                                    $avatarIcon  = '<i class="fas fa-file-invoice-dollar"></i>';
                                    $titleName   = 'Assessment of Fees';
                                    $text        = htmlspecialchars($notif['message'] ?? '');
                                } elseif ($status === 'expiry') {
                                    // Expiration Notice: exclamation icon + title change
                                    $avatarClass = 'nd-avatar--expiry';
                                    $avatarIcon  = '<i class="fas fa-exclamation-circle"></i>';
                                    $titleName   = 'Expiration Notice';
                                    $text        = htmlspecialchars($notif['message'] ?? '');
                                } elseif ($status === 'renewal') {
                                    // New: renewal reminder
                                    $avatarClass = 'nd-avatar--renewal';
                                    $avatarIcon  = '<i class="fas fa-calendar-alt"></i>';
                                    $titleName   = 'Renewal Reminder';
                                    $text        = htmlspecialchars($notif['message'] ?: 'Your renewal is near.');
                                }
                                $when   = re_short_relative_time($notif['created_at']);
                                $href   = isset($notif['link']) ? trim($notif['link']) : '';
                                $onclick= $href !== '' ? ' onclick="window.location.href=\''. htmlspecialchars($href) .'\';" style="cursor:pointer;"' : '';
                            ?>
                            <div class="nd-item"<?php echo $onclick; ?>>
                                <div class="nd-avatar <?php echo $avatarClass; ?>"><?php echo $avatarIcon; ?></div>
                                <div class="nd-body">
                                    <div class="nd-top">
                                        <span class="nd-name"><?php echo $titleName; ?></span>
                                        <span class="nd-time"><?php echo $when; ?></span>
                                    </div>
                                    <div class="nd-text"><?php echo $text; ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="nd-item" style="justify-content:center;color:#888;">No notifications yet.</div>
                    <?php endif; ?>
                </div>
                <div style="text-align:center;padding:0.75rem 1.25rem;background:#f7faff;border-top:1px solid #e5e9f2;">
                    <a href="../ClientSide/view_all_notifications.php" style="color:#4B7BEC;font-weight:500;text-decoration:none;font-size:0.98rem;">View all</a>
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
    var bell = sourceEl || e.currentTarget || e.target || document.getElementById('notificationBell') || document.getElementById('notificationBellMobile');
    if (!bell) return;

    if (dropdown.style.display === 'none' || dropdown.style.display === '') {
        dropdown.style.display = 'block';

        // Adjust dropdown position for mobile vs desktop
        if (window.innerWidth <= 768) {
            // Use CSS centering (left:50% + translateX) so both sides have same margin
            dropdown.style.left = '50%';
            dropdown.style.right = 'auto';
            dropdown.style.transform = 'translateX(-50%)';
            // responsive width is handled by CSS @media rule; ensure top is placed beneath the bell
            dropdown.style.top = (bell.getBoundingClientRect().bottom + window.scrollY + 8) + 'px';
            // clear desktop-specific inline right if previously set
            dropdown.style.removeProperty('right');
        } else {
            // Desktop: keep original right-aligned behavior and clear transform
            dropdown.style.transform = '';
            dropdown.style.left = '';
            dropdown.style.top = (bell.offsetTop + bell.offsetHeight + 8) + 'px';
            dropdown.style.right = '0px';
            dropdown.style.width = ''; // return to default width
        }

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
        dropdown.style.top = (avatar.offsetTop + avatar.offsetHeight + 8 + 'px');
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
            var desktopBell = document.getElementById('notificationBell');
            var mobileBell = document.getElementById('notificationBellMobile');
            if (!desktopBell && !mobileBell) return; // guard

            if (desktopBell) {
                var span = desktopBell.querySelector('span');
                if (span) span.remove();
            }
            if (mobileBell) {
                var spanm = mobileBell.querySelector('.nbadge');
                if (spanm) spanm.remove();
            }
            if (data && Number(data.count) > 0) {
                if (desktopBell) {
                    var span = document.createElement('span');
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
                    span.textContent = data.count;
                    desktopBell.appendChild(span);
                }
                if (mobileBell) {
                    var spanm = document.createElement('span');
                    spanm.className = 'nbadge';
                    spanm.textContent = data.count;
                    mobileBell.appendChild(spanm);
                }
            }
        })
        .catch(() => {
            // swallow network/parse errors to prevent breaking UI
        });
}

// Real-time notification dropdown update
function updateNotificationDropdown() {
    fetch('../ClientSide/get_latest_notifications.php')
        .then(response => response.json())
        .then(data => {
            var list = document.getElementById('notifList');
            if (!list) return;
            list.innerHTML = '';
            if (Array.isArray(data) && data.length > 0) {
                data.forEach(function(notif) {
                    const rawStatus = (notif.status || '').toLowerCase();
                    // accept both "renewal" and "reminder" from adminside
                    // prefer explicit expiry detection, but also detect expiry by message content
                    let status = rawStatus;
                    if (!status || status === 'assessment') {
                        const msg = (notif.message || '').toLowerCase();
                        if (msg.indexOf('expiry') !== -1 || msg.indexOf('expire') !== -1 || msg.indexOf('validity') !== -1) {
                            status = 'expiry';
                        }
                    }
                    if (status === 'reminder') status = 'renewal';

                    let avatarClass = 'nd-avatar--welcome';
                    let icon  = '<i class="fas fa-smile-beam"></i>';
                    let title = 'RestEase';
                    let text  = 'Welcome to RestEase!';
                    if (status === 'accepted') {
                        avatarClass = 'nd-avatar--accepted';
                        icon  = '<i class="fas fa-check"></i>';
                        title = notif.name || 'Request';
                        text  = 'accepted your request.';
                    } else if (status === 'denied') {
                        avatarClass = 'nd-avatar--denied';
                        icon  = '<i class="fas fa-times"></i>';
                        title = notif.name || 'Request';
                        text  = 'denied your request.';
                    } else if (status === 'assessment') {
                        avatarClass = 'nd-avatar--assessment';
                        icon  = '<i class="fas fa-file-invoice-dollar"></i>';
                        title = 'Assessment of Fees';
                        text  = escapeHtml(notif.message || '');
                    } else if (status === 'expiry') {
                        avatarClass = 'nd-avatar--expiry';
                        icon  = '<i class="fas fa-exclamation-circle"></i>';
                        title = 'Expiration Notice';
                        text  = escapeHtml(notif.message || '');
                    } else if (status === 'renewal') {
                        avatarClass = 'nd-avatar--renewal';
                        icon  = '<i class="fas fa-calendar-alt"></i>';
                        title = 'Renewal Reminder';
                        text  = escapeHtml(notif.message || 'Your renewal is near.');
                    }

                    const when = reShortRelativeTime(notif.created_at);
                    const item = document.createElement('div');
                    item.className = 'nd-item';
                    if (notif.link) {
                        item.style.cursor = 'pointer';
                        item.addEventListener('click', () => { window.location.href = notif.link; });
                    }
                    item.innerHTML = `
                        <div class="nd-avatar ${avatarClass}">${icon}</div>
                        <div class="nd-body">
                            <div class="nd-top">
                                <span class="nd-name">${escapeHtml(title)}</span>
                                <span class="nd-time">${when}</span>
                            </div>
                            <div class="nd-text">${text}</div>
                        </div>`;
                    list.appendChild(item);
                });
            } else {
                list.innerHTML = '<div class="nd-item" style="justify-content:center;color:#888;">No notifications yet.</div>';
            }
        })
        .catch(() => {
            var list = document.getElementById('notifList');
            if (list) list.innerHTML = '<div class="nd-item" style="justify-content:center;color:#888;">Unable to load notifications.</div>';
        });
}

// Small helpers for client-side formatting/escaping
function reShortRelativeTime(iso) {
    // tolerate "YYYY-mm-dd HH:ii:ss" by replacing space
    const d = new Date(iso && iso.replace(' ', 'T'));
    if (isNaN(d)) return '';
    const diff = Math.max(1, Math.round((Date.now() - d.getTime()) / 1000));
    if (diff < 3600) return Math.max(1, Math.round(diff / 60)) + 'MIN';
    if (diff < 86400) return Math.round(diff / 3600) + 'HR';
    return Math.round(diff / 86400) + 'DY';
}
function escapeHtml(s) {
    if (s == null) return '';
    return String(s)
        .replace(/&/g,'&amp;')
        .replace(/</g,'&lt;')
        .replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;')
        .replace(/'/g,'&#39;');
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
