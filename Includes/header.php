<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Use __DIR__ so includes work regardless of the current working directory
include_once __DIR__ . '/db.php';

// Provide defaults but allow including page to override by setting $adminName / $adminProfilePic before include
if (!isset($adminName) || !$adminName) $adminName = 'Admin';
if (!isset($adminProfilePic) || !$adminProfilePic) $adminProfilePic = '../assets/Default Image.jpg';

// Compute web base for the project using filesystem paths so URLs don't duplicate segments.
// This finds the project root (one level above Includes) and converts it to a URL path
$projectRootFs = realpath(__DIR__ . '/..') ?: '';
$docRootFs = realpath($_SERVER['DOCUMENT_ROOT']) ?: '';
$appBase = '';
if ($projectRootFs && $docRootFs && strpos($projectRootFs, $docRootFs) === 0) {
    // slice off document root and normalize to forward slashes, ensure leading slash (unless root)
    $appBase = str_replace('\\', '/', substr($projectRootFs, strlen($docRootFs)));
    $appBase = $appBase === '' ? '' : ('/' . ltrim($appBase, '/'));
} else {
    // fallback: no document-root relation detected — use dirname of SCRIPT_NAME's top-level folder
    $parts = explode('/', trim($_SERVER['SCRIPT_NAME'] ?? '', '/'));
    $appBase = isset($parts[0]) && $parts[0] !== '' ? '/' . $parts[0] : '';
}

// Settings URL to use in onclick/navigation (uses project web base)
$settingsUrl = $appBase . '/AdminSide/Settings.php?tab=notification';

// Resolve profile picture to a usable web URL:
// - If $adminProfilePic is an absolute URL or root-relative path, keep it.
// - If it can be resolved to a real filesystem path under document root, convert to web path.
// - Otherwise, prepend $appRoot to the (normalized) relative path.
function resolveProfilePicUrl($path, $appRoot) {
    if (!$path) return $appRoot . '/assets/Default Image.jpg';
    $p = trim($path);
    // If already an absolute url or root-relative path
    if (preg_match('#^(https?:)?//#i', $p) || strpos($p, '/') === 0) {
        return $p;
    }
    // Try to resolve filesystem absolute path
    $docRootReal = realpath($_SERVER['DOCUMENT_ROOT']) ?: '';
    $candidates = [
        $p,
        __DIR__ . '/' . $p,
        __DIR__ . '/../' . $p,
        realpath($p)
    ];
    foreach ($candidates as $cand) {
        if (!$cand) continue;
        $real = realpath($cand) ?: $cand;
        if ($docRootReal && strpos($real, $docRootReal) === 0) {
            $web = str_replace($docRootReal, '', $real);
            $web = str_replace('\\', '/', $web);
            return ($web[0] === '/') ? $web : '/' . $web;
        }
    }
    // Fallback: prepend app root
    $normalized = str_replace('\\', '/', $p);
    return $appRoot . '/' . ltrim($normalized, '/');
}

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

// Normalize profile pic URL for output (pass $appBase)
$adminProfilePic = resolveProfilePicUrl($adminProfilePic, $appBase);
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
      <!-- notification bell placed to the left of the avatar; use generated settings URL -->
      <button class="notif-bell" aria-label="Notifications" title="Notifications"
        onclick="window.location.href='<?php echo htmlspecialchars($settingsUrl, ENT_QUOTES); ?>';"
        style="background:transparent;border:none;padding:0;margin-right:8px;cursor:pointer;color:inherit;position:relative;">
        <i class="fa-solid fa-bell" style="font-size:1.05rem;color:inherit;"></i>
        <!-- numeric unread badge (reduced size, circular, centered) -->
        <span id="notifBellCount"
              style="display:none;position:absolute;top:-8px;right:-8px;background:#e74c3c;color:#fff;font-size:0.6rem;font-weight:700;padding:0;line-height:14px;width:14px;height:14px;border-radius:50%;min-width:14px;text-align:center;box-shadow:0 1px 3px rgba(0,0,0,0.12);display:flex;align-items:center;justify-content:center;z-index:3;">
        </span>
      </button>
      <img src="<?php echo htmlspecialchars($adminProfilePic, ENT_QUOTES); ?>" alt="Profile" class="profile-avatar">
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

<script>
(function(){
  // Compute unread notifications and update bell badge.
  function getUnreadCountFromLocalStorage() {
    // Prefer structured list in localStorage.systemNotifs
    try {
      var raw = localStorage.getItem('systemNotifs');
      if (raw) {
        var arr = JSON.parse(raw);
        if (Array.isArray(arr) && arr.length) {
          var c = 0;
          arr.forEach(function(notif){
            var readKey = 'notif_read_' + (notif.id ?? notif.ID ?? notif._id ?? '');
            if (readKey && localStorage.getItem(readKey) !== '1') c++;
          });
          return c;
        }
      }
    } catch (e) {
      // ignore parse errors
    }
    // Fallback: count unread notif_read_* keys
    var unread = 0;
    for (var i = 0; i < localStorage.length; i++) {
      var key = localStorage.key(i);
      if (key && key.indexOf('notif_read_') === 0) {
        if (localStorage.getItem(key) !== '1') unread++;
      }
    }
    return unread;
  }

  function updateBellCount() {
    var el = document.getElementById('notifBellCount');
    if (!el) return;
    var count = getUnreadCountFromLocalStorage();
    if (count > 0) {
      el.textContent = count > 99 ? '99+' : String(count);
      el.style.display = '';
    } else {
      el.textContent = '';
      el.style.display = 'none';
    }
  }

  // Update on load
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', updateBellCount);
  } else {
    updateBellCount();
  }

  // Update when other tabs change storage
  window.addEventListener('storage', function(e){
    if (!e.key) {
      updateBellCount();
      return;
    }
    if (e.key === 'systemNotifs' || e.key.startsWith('notif_read_')) {
      updateBellCount();
    }
  });

  // Optional: poll occasionally in case notifications updated by JS without storage event
  setInterval(updateBellCount, 5000);
})();
</script>
