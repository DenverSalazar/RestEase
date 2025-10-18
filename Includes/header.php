<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include_once __DIR__ . '/db.php';

// Provide defaults but allow including page to override by setting $adminName / $adminProfilePic before include
if (!isset($adminName) || !$adminName) $adminName = 'Admin';
if (!isset($adminProfilePic) || !$adminProfilePic) $adminProfilePic = '../assets/Default Image.jpg';

// If an admin is logged in, try to fetch profile info (best-effort)
if (isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']) && isset($conn)) {
    $adminId = intval($_SESSION['admin_id']);
    $stmt = $conn->prepare('SELECT display_name, profile_pic FROM admin_profiles WHERE admin_id = ? LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('i', $adminId);
        $stmt->execute();
        $stmt->bind_result($displayName, $profilePic);
        if ($stmt->fetch()) {
            if (!empty($displayName)) $adminName = $displayName;
            if (!empty($profilePic)) $adminProfilePic = $profilePic;
        }
        $stmt->close();
    }
}
?>
<header class="header">
  <div class="header-left">
      <div class="datetime">
        <span class="date" id="current-date"></span>
        <span class="time" id="current-time"></span>
      </div>
    </div>
  </div>
  <div class="user-profile">
    <div class="profile-info">
      <!-- notification bell placed to the left of the avatar; inline styles keep layout/size unchanged -->
      <button class="notif-bell" aria-label="Notifications" title="Notifications"
        onclick="window.location.href='/RestEase/AdminSide/Notifications.php';"
        style="background:transparent;border:none;padding:0;margin-right:8px;cursor:pointer;color:inherit;">
        <i class="fa-solid fa-bell" style="font-size:1.05rem;color:inherit;"></i>
      </button>
      <img src="<?php echo htmlspecialchars($adminProfilePic); ?>" alt="Profile" class="profile-avatar">
      <div>
        <div class="profile-name"><?php echo htmlspecialchars($adminName); ?></div>
        <div class="profile-role">Admin</div>
      </div>
    </div>
  </div>
</header>

<script>
(function(){
  function tick(){
    const now = new Date();
    const utc = now.getTime() + (now.getTimezoneOffset() * 60000);
    const manila = new Date(utc + (3600000 * 8));
    const days = ["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"];
    const months = ["January","February","March","April","May","June","July","August","September","October","November","December"];
    const day = days[manila.getDay()];
    const month = months[manila.getMonth()];
    const date = manila.getDate();
    const year = manila.getFullYear();
    let hours = manila.getHours();
    let minutes = manila.getMinutes();
    const ampm = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12;
    hours = hours ? hours : 12;
    minutes = minutes < 10 ? '0'+minutes : minutes;
    const elDate = document.getElementById('current-date');
    const elTime = document.getElementById('current-time');
    if (elDate) elDate.textContent = `${day}, ${month} ${date}, ${year}`;
    if (elTime) elTime.textContent = `${hours}:${minutes} ${ampm}`;
  }
  tick();
  setInterval(tick, 1000);
})();
</script>
