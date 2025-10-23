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
    $stmt = $conn->prepare("SELECT id, type, first_name, middle_name, last_name, created_at FROM accepted_request WHERE user_id = ?");
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
    $stmt = $conn->prepare("SELECT id, type, first_name, middle_name, last_name, created_at FROM denied_request WHERE user_id = ?");
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
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Notifications — RestEase</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="../css/navbar.css">
<link rel="stylesheet" href="../css/footer.css">
<style>
:root{--accent:#0077B6;--muted:#888;--card-border:#eef2f5;--card-bg:#f2f4f6;}
body{font-family:'Poppins',system-ui,Arial;color:#222;background:#fff;}
.container-main{max-width:980px;margin:28px auto;padding:18px;}
.header-row{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px;}
.search-input-wrapper{position:relative;max-width:640px;width:100%;}
.search-input{width:100%;padding:14px 48px 14px 44px;border:1px solid #e6ecf3;border-radius:14px;background:#fff;font-size:1rem;box-shadow:none;}
.search-icon{position:absolute;left:16px;top:50%;transform:translateY(-50%);color:#9aa3ad;font-size:1.15rem;}
.tabs{display:flex;gap:18px;align-items:center;}
.notif-tab{background:transparent;border:none;padding:8px 10px;cursor:pointer;font-weight:600;color:#444;position:relative;border-radius:6px;display:inline-flex;align-items:center;gap:8px;}
.notif-tab.active{color:var(--accent);}
.notif-tab.active::after{content:"";position:absolute;left:12px;right:12px;bottom:-8px;height:3px;background:var(--accent);border-radius:3px;opacity:1;}
.notif-tab .tab-badge{display:inline-block;background:#eaf1ff;color:var(--accent);padding:6px 10px;border-radius:999px;font-weight:700;font-size:0.92rem;min-width:28px;text-align:center;}

.controls{display:flex;gap:8px;align-items:center;}
.icon-btn{border:none;background:transparent;cursor:pointer;padding:6px;font-size:18px;color:#666;}
.icon-btn.warn{color:#dc3545;}

.notif-list{list-style:none;padding:0;margin:22px 0;}
/* card */
.notif-card-wrapper{display:flex;align-items:center;gap:12px;padding:14px 18px;border-radius:12px;background:#fbfbfb;border:1px solid #eef2f5;box-shadow:none;transition:box-shadow .12s, transform .06s;}
.notif-card-wrapper:hover{box-shadow:0 6px 18px rgba(39,54,66,0.06);}
.notif-left{display:flex;align-items:center;gap:12px;min-width:120px}
.notif-dot{width:10px;height:10px;border-radius:50%;background:#ffffff !important;box-shadow:none !important;border:1px solid #e6e9ec !important;}
.notif-star-left{background:transparent;border:none;padding:0;margin:0 4px 0 0;cursor:pointer;color:#bfc6cc;font-size:1.05rem;transition:color 180ms}
.notif-star-left[aria-pressed="true"] {
  color: #f0b400 !important;
}
.notif-star-left:hover {
  color: #f0b400;
}
.notif-icon{width:44px;height:44px;border-radius:8px;display:flex;align-items:center;justify-content:center;background:#fff;border:1px solid rgba(7,119,182,0.06);color:var(--accent);font-size:16px}
.notif-main{flex:1;min-width:0}
.notif-title{font-weight:700;font-size:1.05rem;margin-bottom:6px;color:#222}
.notif-desc{color:#6f767d;font-size:0.95rem}
.notif-time{color:var(--muted);font-size:0.92rem;white-space:nowrap;margin-left:12px}
.notif-actions{display:flex;align-items:center;gap:8px;margin-left:12px;}
.action-btn { background:transparent;border:none;padding:6px;border-radius:8px;cursor:pointer;font-size:1rem;display:inline-flex;align-items:center;justify-content:center; }
.action-btn.read { color:var(--accent); }
.action-btn.archive { color:#6d7780; }
.action-btn.delete { background:#ff6b6b;color:#fff;border:none; }

/* accepted items override */
.notif-card-wrapper[data-status="accepted"] {
  background: #ffffff !important;
  border-left-color: #ffffff !important;
}
.notif-card-wrapper[data-status="accepted"] .notif-icon {
  color: #ffffff !important;
  background: #ffffff !important;
  border-color: transparent !important;
}
.notif-card-wrapper[data-status="accepted"] .notif-title,
.notif-card-wrapper[data-status="accepted"] .notif-desc {
  color: #222 !important;
}

/* notification status colors */
.notif-card-wrapper[data-status="accepted"] .notif-title {
  color: #198754 !important;
}
.notif-card-wrapper[data-status="denied"] .notif-title {
  color: #DC3545 !important;
}

/* focus style */
.search-input:focus {
  outline: none;
  border-color: var(--accent);
  box-shadow: 0 6px 20px rgba(0,119,182,0.06);
}

/* pagination styles */
#notifPagination {
  display:flex;
  align-items:center;
  justify-content:space-between;
  margin-top:12px;
  gap:12px;
  padding-top:6px;
  position: relative; /* added: allow absolute centering of middle group */
}
#notifPagination .pg-info { color:#666; font-size:0.95rem; }
#notifPagination .pg-center {
  display:flex; gap:8px; align-items:center; justify-content:center;
  position: absolute; left: 50%; transform: translateX(-50%); /* centered */
}
#notifPagination .pg-btn {
  border:1px solid #e3e7ed;
  background:transparent;
  color:#444;
  padding:6px 10px;
  border-radius:8px;
  cursor:pointer;
  min-width:36px;
  display:inline-flex;
  align-items:center;
  justify-content:center;
}
#notifPagination .pg-btn[disabled] { opacity:0.45; cursor:not-allowed; }
#notifPagination .pg-btn.current {
  background:var(--accent);
  color:#fff;
  border-color:var(--accent);
}

/* Calendar Popup Styles */
.calendar-popup {
  display: none;
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  background: white;
  padding: 20px;
  border-radius: 12px;
  box-shadow: 0 5px 20px rgba(0,0,0,0.15);
  z-index: 1000;
  min-width: 300px;
}

.calendar-popup.active {
  display: block;
}

.calendar-popup .header {
  display: flex;
  justify-content: flex-end; /* move content to the right */
  align-items: center;
  margin-bottom: 15px;
}

.calendar-popup .header h3 {
  display: none; /* hide Select Date */
  margin: 0;
  font-size: 1.1rem;
}

.calendar-popup .close-btn {
  display: none; /* hide X button */
  background: none;
  border: none;
  font-size: 1.2rem;
  cursor: pointer;
  color: #666;
}

.calendar-popup select {
  padding: 5px;
  border: 1px solid #ddd;
  border-radius: 4px;
  margin: 0 5px;
}

.calendar-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 10px;
}

.calendar-table th {
  padding: 8px;
  text-align: center;
  color: #666;
  font-weight: 600;
}

.calendar-table td {
  padding: 8px;
  text-align: center;
  cursor: pointer;
  border-radius: 4px;
}

.calendar-table td:hover {
  background: #f0f0f0;
}

.calendar-table td.selected {
  background: var(--accent);
  color: white;
}

.calendar-table td.today {
  border: 1px solid var(--accent);
}

.calendar-controls {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 15px;
}

.calendar-controls button {
  padding: 6px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  background: white;
  cursor: pointer;
}

.calendar-controls button.confirm {
  background: var(--accent);
  color: white;
  border: none;
}

.overlay {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: transparent; /* was rgba(0,0,0,0.5); remove black bg */
  z-index: 999;
}

.overlay.active {
  display: block;
}

/* responsive */
@media (max-width:760px){
  .notif-left{min-width:70px}
  .notif-time{display:none}
  .search-input-wrapper{max-width:260px}
}

/* Anchored calendar (opens near the icon). Keeps existing .calendar-popup styles for fallback modal use */
.calendar-popup.anchored {
  position: fixed;            /* anchor to viewport for reliable math */
  top: 0; left: 0;            /* will be set via JS */
  transform: none;            /* override center transform */
  min-width: 320px;
  z-index: 5000;              /* above any headers */
}
.calendar-popup.anchored::after {
  content: "";
  position: absolute;
  width: 10px; height: 10px;
  background: white;
  transform: rotate(45deg);
  top: calc(100% - 5px);      /* small arrow when popup is above button (fallback below adjusts in JS) */
  right: 18px;
  border-left: 1px solid rgba(0,0,0,0.1);
  border-bottom: 1px solid rgba(0,0,0,0.1);
  display: none;              /* toggled by JS depending on placement */
}
.calendar-popup.anchored.show-arrow::after { display: block; }
</style>
</head>
<body>
<?php include '../Includes/navbar2.php'; ?>
<div class="container-main">
  <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:8px">
    <a href="ClientHome.php" style="color:#506C84;text-decoration:none;font-weight:600"><i class="fas fa-arrow-left"></i> Back</a>
    <div style="font-weight:700;font-size:1.15rem;color:#222">All Notifications</div>
    <div></div>
  </div>

  <div class="header-row">
    <div class="tabs" id="notif-tabs">
      <button class="notif-tab active" data-filter="all" id="tab-all"><span class="tab-badge" id="count-all">0</span>All</button>
      <button class="notif-tab" data-filter="favorite" id="tab-fav"><span class="tab-badge" id="count-fav">0</span>Favorites</button>
      <button class="notif-tab" data-filter="archive" id="tab-arch"><span class="tab-badge" id="count-arch">0</span>Archive</button>
    </div>

    <div style="display:flex;align-items:center;gap:8px">
      <div class="search-input-wrapper">
        <input id="notifSearch" class="search-input" placeholder="Search notifications..." />
        <i class="fas fa-search search-icon"></i>
      </div>
      <div class="controls" style="position:relative;">
        <button id="headerDeleteVisibleBtn" class="icon-btn warn" title="Delete visible" aria-label="Delete visible" style="margin-right:6px;">
          <i class="fas fa-trash"></i>
        </button>
        <button id="headerMarkReadBtn" class="icon-btn" title="Mark visible as read" aria-label="Mark visible read" style="margin-right:6px;">
          <i class="fas fa-envelope-open-text"></i>
        </button>
        <button id="delete-all-btn" class="icon-btn warn" title="Delete All" style="display:none;margin-right:6px;"><i class="fas fa-trash"></i></button>
        <button id="headerCalendarBtn" class="icon-btn" title="Select date" aria-label="Calendar" style="margin-left:8px;">
          <i class="fas fa-calendar-alt"></i>
        </button>
      </div>
    </div>
  </div>

  <?php if ($user_id && count($notifications) > 0): ?>
    <ul class="notif-list" id="notifications-list">
      <?php foreach ($notifications as $notif):
        $borderColor = ($notif['status'] === 'accepted') ? '#198754' : (($notif['status'] === 'denied') ? '#ffffff' : (($notif['status'] === 'welcome') ? '#4B7BEC' : '#FFC107'));
        $bgColor = ($notif['status'] === 'accepted') ? '#E9F7EF' : (($notif['status'] === 'denied') ? '#ffffff' : (($notif['status'] === 'welcome') ? '#EAF1FF' : '#FFF8E1'));
        $icon = ($notif['status'] === 'accepted') ? 'fa-check-circle' : (($notif['status'] === 'denied') ? 'fa-times-circle' : (($notif['status'] === 'welcome') ? 'fa-smile-beam' : 'fa-file-invoice-dollar'));
        $iconColor = ($notif['status'] === 'accepted') ? '#198754' : (($notif['status'] === 'denied') ? '#ffffff' : (($notif['status'] === 'welcome') ? '#4B7BEC' : '#FFC107'));
      ?>
      <li class="notif-card-wrapper unread" data-id="<?php echo isset($notif['id']) ? htmlspecialchars($notif['id']) : ''; ?>" data-status="<?php echo htmlspecialchars($notif['status']); ?>" data-created_at="<?php echo htmlspecialchars($notif['created_at']); ?>" style="background:<?php echo $bgColor; ?>;border-left:8px solid <?php echo $borderColor; ?>;">
        <div class="notif-left">
          <span class="notif-dot" title="<?php echo ($notif['status'] === 'accepted'?'Unread':''); ?>"></span>
          <button class="notif-star-left" aria-pressed="false" title="Favorite"><i class="fas fa-star"></i></button>
          <span class="notif-icon" style="color:<?php echo $iconColor; ?>"><i class="fas <?php echo $icon; ?>"></i></span>
        </div>

        <div class="notif-main">
          <div class="notif-title">
            <?php
              if ($notif['status'] === 'accepted') echo 'Request Accepted';
              elseif ($notif['status'] === 'denied') echo 'Request Denied';
              elseif ($notif['status'] === 'welcome') echo 'Welcome to RestEase!';
              else echo 'Assessment of Fees';
            ?>
          </div>
          <div class="notif-desc">
            <?php if ($notif['status'] === 'accepted' || $notif['status'] === 'denied'): ?>
              Type: <b><?php echo htmlspecialchars($notif['type'] ?? ''); ?></b> &nbsp;|&nbsp; Name: <b><?php echo htmlspecialchars($notif['name'] ?? ''); ?></b>
            <?php elseif ($notif['status'] === 'assessment'): ?>
              <?php echo htmlspecialchars($notif['message']); ?>
            <?php endif; ?>
          </div>
        </div>

        <div class="notif-time"><?php echo date('M d, Y h:i A', strtotime($notif['created_at'])); ?></div>

        <div class="notif-actions">
          <?php if ($notif['status'] === 'accepted' || $notif['status'] === 'denied'): ?>
            <a href="notification_details.php?id=<?php echo isset($notif['id']) ? urlencode($notif['id']) : ''; ?>&type=<?php echo urlencode($notif['status']); ?>" title="View Details" style="font-size:0.98rem;color:#4B7BEC;text-decoration:none;font-weight:600;">
              Details
            </a>
          <?php elseif ($notif['status'] === 'assessment'): ?>
            <a href="notification_details.php?type=assessment&created_at=<?php echo urlencode($notif['created_at']); ?>" title="View Assessment Details" style="font-size:0.98rem;color:#f39c12;text-decoration:none;font-weight:600;">
              Details
            </a>
          <?php endif; ?>
          <button class="action-btn delete notif-delete" title="Delete" aria-label="Delete"><i class="fas fa-trash"></i></button>
        </div>
      </li>
      <?php endforeach; ?>
    </ul>

    <!-- pagination UI -->
    <div id="notifPagination" style="display:none;">
      <div class="pg-info" id="pagination-info">Page 1 of 1</div>
      <div class="pg-center" id="pagination-controls"></div>
      <div style="width:120px;"></div>
    </div>

  <?php else: ?>
    <div style="text-align:center;padding:64px 8px;color:#888;">
      No notifications available yet.<br>Please contact the administrator or check back later.
    </div>
  <?php endif; ?>
</div>

<div class="overlay" id="calendarOverlay"></div>
<div class="calendar-popup" id="calendarPopup">
  <div class="header">
    <h3>Select Date</h3>
    <button class="close-btn">&times;</button>
  </div>
  <div>
    <select id="monthSelect">
      <option value="0">January</option>
      <option value="1">February</option>
      <option value="2">March</option>
      <option value="3">April</option>
      <option value="4">May</option>
      <option value="5">June</option>
      <option value="6">July</option>
      <option value="7">August</option>
      <option value="8">September</option>
      <option value="9">October</option>
      <option value="10">November</option>
      <option value="11">December</option>
    </select>
    <select id="yearSelect"></select>
  </div>
  <table class="calendar-table">
    <thead>
      <tr>
        <th>Sun</th>
        <th>Mon</th>
        <th>Tue</th>
        <th>Wed</th>
        <th>Thu</th>
        <th>Fri</th>
        <th>Sat</th>
      </tr>
    </thead>
    <tbody id="calendarBody"></tbody>
  </table>
  <div class="calendar-controls">
    <button class="clear">Clear</button>
    <button class="confirm">Confirm</button>
  </div>
</div>

<footer><?php include '../Includes/footer-client.php'; ?></footer>

<script>
(function(){
  const $ = sel => document.querySelector(sel);
  const $$ = sel => Array.from(document.querySelectorAll(sel));

  const tabs = $$('#notif-tabs .notif-tab');
  const searchInput = $('#notifSearch');
  const deleteAllBtn = $('#delete-all-btn');
  const headerMarkReadBtn = $('#headerMarkReadBtn');
  const headerDeleteVisibleBtn = $('#headerDeleteVisibleBtn');
  const headerCalendarBtn = $('#headerCalendarBtn');

  // pagination
  const PAGE_SIZE_CLIENT = 10; // was 5; show 10 notifications per page
  let currentPageClient = 1;

  function createPageButton(label, disabled, onClick, isCurrent) {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'pg-btn' + (isCurrent ? ' current' : '');
    btn.textContent = label;
    btn.disabled = !!disabled;
    if (!disabled) btn.addEventListener('click', onClick);
    return btn;
  }

  function updateCounts(){
    const cards = $$('.notif-card-wrapper').filter(c => !c.hasAttribute('data-deleted'));
    const allCount = cards.length;
    let fav = 0, arch = 0;
    cards.forEach(c=>{
      const id = c.getAttribute('data-id');
      if (id && localStorage.getItem('notif_fav_' + id) === '1') fav++;
      if (id && localStorage.getItem('notif_archived_' + id) === '1') arch++;
    });
    $('#count-all').textContent = allCount;
    $('#count-fav').textContent = fav;
    $('#count-arch').textContent = arch;
  }

  // ---- Filtering helpers (source-of-truth for pagination) ----
  let dateFilter = null; // selected date filter (Date or null)

  function getActiveTabKey(){
    const activeBtn = $('#notif-tabs .notif-tab.active');
    return activeBtn ? activeBtn.getAttribute('data-filter') : 'all';
  }

  function cardMatches(card){
    // ignore already deleted via localStorage
    const id = card.getAttribute('data-id') || '';
    if (id && localStorage.getItem('notif_deleted_' + id) === '1') return false;

    // tab filter
    const tab = getActiveTabKey();
    if (tab === 'favorite' && !(id && localStorage.getItem('notif_fav_' + id) === '1')) return false;
    if (tab === 'archive' && !(id && localStorage.getItem('notif_archived_' + id) === '1')) return false;

    // search filter
    const q = (searchInput.value || '').trim().toLowerCase();
    if (q) {
      const title = (card.querySelector('.notif-title')||{textContent:''}).textContent.toLowerCase();
      const desc  = (card.querySelector('.notif-desc') ||{textContent:''}).textContent.toLowerCase();
      if (!title.includes(q) && !desc.includes(q)) return false;
    }

    // date filter (compare date-only)
    if (dateFilter instanceof Date) {
      const cardDate = new Date(card.getAttribute('data-created_at'));
      if (cardDate.toDateString() !== dateFilter.toDateString()) return false;
    }
    return true;
  }

  function getFilteredCards(){
    return $$('.notif-card-wrapper').filter(cardMatches);
  }

  // ---- Pagination based on filtered cards ----
  function updatePagination() {
    const pagRoot = document.getElementById('notifPagination');
    if (!pagRoot) return;

    const allFiltered = getFilteredCards();
    const total = allFiltered.length;
    const totalPages = Math.max(1, Math.ceil(total / PAGE_SIZE_CLIENT));
    if (currentPageClient > totalPages) currentPageClient = 1;

    const center = pagRoot.querySelector('.pg-center');
    const info   = pagRoot.querySelector('.pg-info');
    info.textContent = `Page ${total ? currentPageClient : 1} of ${totalPages}`;
    center.innerHTML = '';

    center.appendChild(createPageButton('‹', currentPageClient <= 1, () => {
      currentPageClient = Math.max(1, currentPageClient - 1);
      paginateDisplay();
    }, false));

    const maxButtons = 5;
    let start = Math.max(1, currentPageClient - Math.floor(maxButtons/2));
    let end   = Math.min(totalPages, start + maxButtons - 1);
    start     = Math.max(1, end - maxButtons + 1);

    for (let p = start; p <= end; p++) {
      center.appendChild(createPageButton(String(p), false, (() => {
        const page = p;
        return () => { currentPageClient = page; paginateDisplay(); };
      })(), p === currentPageClient));
    }

    center.appendChild(createPageButton('›', currentPageClient >= totalPages, () => {
      currentPageClient = Math.min(totalPages, currentPageClient + 1);
      paginateDisplay();
    }, false));

    pagRoot.style.display = total > 0 ? 'flex' : 'none';
  }

  function paginateDisplay() {
    // hide all first
    $$('.notif-card-wrapper').forEach(el => el.style.display = 'none');

    const cards = getFilteredCards();
    const totalPages = Math.max(1, Math.ceil(cards.length / PAGE_SIZE_CLIENT));
    if (currentPageClient > totalPages) currentPageClient = 1;

    const start = (currentPageClient - 1) * PAGE_SIZE_CLIENT;
    const end   = start + PAGE_SIZE_CLIENT;
    cards.slice(start, end).forEach(el => el.style.display = '');

    updatePagination();
  }

  function applyFilter(){
    currentPageClient = 1;
    updateCounts();
    paginateDisplay();
  }

  function initStars(){
    $$('.notif-card-wrapper').forEach(card=>{
      const id = card.getAttribute('data-id');
      const star = card.querySelector('.notif-star-left');
      if (!star) return;
      if (id && localStorage.getItem('notif_fav_' + id) === '1') star.setAttribute('aria-pressed','true');
      else star.setAttribute('aria-pressed','false');
      star.addEventListener('click', function(e){
        e.stopPropagation();
        if (!id) return;
        const key = 'notif_fav_' + id;
        const is = localStorage.getItem(key) === '1';
        if (is) localStorage.removeItem(key); else localStorage.setItem(key,'1');
        star.setAttribute('aria-pressed', is ? 'false' : 'true');
        updateCounts();
        if ($('#notif-tabs .notif-tab.active').getAttribute('data-filter') === 'favorite') applyFilter();
      });
    });
  }

  // tabs
  tabs.forEach(t => t.addEventListener('click', function(){
    tabs.forEach(x=>x.classList.remove('active'));
    this.classList.add('active');
    applyFilter();
  }));

  searchInput && searchInput.addEventListener('input', applyFilter);

  // delete all in archive (POST)
  if (deleteAllBtn) {
    deleteAllBtn.addEventListener('click', function(){
      if (!confirm('Delete ALL notifications in Archive?')) return;
      fetch('delete_notification.php', {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ action: 'delete_all' })
      }).then(r=>r.json()).then(data=>{
        if (data && data.success){
          $$('.notif-card-wrapper').forEach(card=>{
            const id = card.getAttribute('data-id');
            if (id && localStorage.getItem('notif_archived_' + id) === '1') {
              try{ localStorage.setItem('notif_deleted_' + id, '1'); localStorage.removeItem('notif_archived_' + id); localStorage.removeItem('notif_fav_' + id); } catch(e){}
              card.remove();
            }
          });
          updateCounts();
          applyFilter();
        } else alert('Failed to delete all notifications.');
      }).catch(()=> alert('Failed to delete all notifications.'));
    });
  }

  // per-card delete wiring
  function wirePerCardActions() {
    $$('.notif-card-wrapper').forEach(card => {
      const id = card.getAttribute('data-id');
      const delBtn = card.querySelector('.notif-delete');
      if (delBtn) {
        delBtn.addEventListener('click', function(e){
          e.stopPropagation();
          if (!id) return;
          if (!confirm('Delete this notification?')) return;
          fetch('delete_notification.php', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ action: 'delete_single', id: id })
          }).then(r=>r.json()).then(data=>{
            try { localStorage.setItem('notif_deleted_' + id, '1'); } catch(e){}
            card.remove();
            updateCounts();
            applyFilter();
          }).catch(()=>{
            try { localStorage.setItem('notif_deleted_' + id, '1'); } catch(e){}
            card.remove();
            updateCounts();
            applyFilter();
          });
        });
      }
    });
  }

  // header bulk actions
  if (headerMarkReadBtn) {
    headerMarkReadBtn.addEventListener('click', function(){
      const visible = $$('.notif-card-wrapper').filter(c => c.style.display !== 'none');
      if (visible.length === 0) return;
      if (!confirm('Mark all visible notifications as read?')) return;
      visible.forEach(card => {
        const id = card.getAttribute('data-id');
        try { if (id) localStorage.setItem('notif_read_' + id, '1'); } catch(e){}
        const dot = card.querySelector('.notif-dot'); if (dot) dot.classList.add('read');
        card.classList.remove('unread');
      });
      updateCounts();
      applyFilter();
    });
  }

  if (headerDeleteVisibleBtn) {
    headerDeleteVisibleBtn.addEventListener('click', function(){
      const visible = $$('.notif-card-wrapper').filter(c => c.style.display !== 'none');
      if (visible.length === 0) return;
      if (!confirm('Delete all visible notifications?')) return;
      visible.forEach(card => {
        const id = card.getAttribute('data-id');
        try { if (id) localStorage.setItem('notif_deleted_' + id, '1'); } catch(e){}
        card.remove();
      });
      updateCounts();
      applyFilter();
    });
  }

  // calendar
  const calendarPopup = $('#calendarPopup');
  const overlay = $('#calendarOverlay');
  const monthSelect = $('#monthSelect');
  const yearSelect = $('#yearSelect');
  const calendarBody = $('#calendarBody');
  let selectedDate = null;

  // Move the Clear button into the header so it replaces the X (keeps existing click handler)
  (function moveClearIntoHeader(){
    const headerEl = calendarPopup ? calendarPopup.querySelector('.header') : null;
    const clearBtn = calendarPopup ? calendarPopup.querySelector('.calendar-controls .clear') : null;
    if (headerEl && clearBtn && clearBtn.parentElement !== headerEl) {
      headerEl.appendChild(clearBtn);
    }
  })();

  // Initialize year select
  const currentYear = new Date().getFullYear();
  for (let year = currentYear - 5; year <= currentYear + 5; year++) {
    const option = document.createElement('option');
    option.value = year;
    option.textContent = year;
    yearSelect.appendChild(option);
  }
  yearSelect.value = currentYear;

  function generateCalendar(month, year) {
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const today = new Date();
    
    calendarBody.innerHTML = '';
    let date = 1;
    for (let i = 0; i < 6; i++) {
      const row = document.createElement('tr');
      for (let j = 0; j < 7; j++) {
        const cell = document.createElement('td');
        if (i === 0 && j < firstDay.getDay()) {
          cell.textContent = '';
        } else if (date > lastDay.getDate()) {
          cell.textContent = '';
        } else {
          cell.textContent = date;
          const currentDate = new Date(year, month, date);
          
          if (currentDate.toDateString() === today.toDateString()) {
            cell.classList.add('today');
          }
          
          if (selectedDate && currentDate.toDateString() === selectedDate.toDateString()) {
            cell.classList.add('selected');
          }
          
          cell.addEventListener('click', () => {
            $$('.calendar-table td').forEach(td => td.classList.remove('selected'));
            cell.classList.add('selected');
            selectedDate = currentDate;
          });
          
          date++;
        }
        row.appendChild(cell);
      }
      calendarBody.appendChild(row);
      if (date > lastDay.getDate()) break;
    }
  }

  monthSelect.addEventListener('change', () => {
    generateCalendar(parseInt(monthSelect.value), parseInt(yearSelect.value));
  });

  yearSelect.addEventListener('change', () => {
    generateCalendar(parseInt(monthSelect.value), parseInt(yearSelect.value));
  });

  function positionCalendarAnchored() {
    if (!calendarPopup || !headerCalendarBtn) return;

    // Ensure we can measure size
    calendarPopup.style.visibility = 'hidden';
    calendarPopup.classList.add('active', 'anchored');
    const popupW = calendarPopup.offsetWidth || 340;
    const popupH = calendarPopup.offsetHeight || 280;

    const rect = headerCalendarBtn.getBoundingClientRect();
    const scrollX = window.scrollX || document.documentElement.scrollLeft;
    const scrollY = window.scrollY || document.documentElement.scrollTop;
    const margin = 10;

    // Preferred: above the button, right-aligned to the button
    let left = rect.right + scrollX - popupW;
    // Keep within viewport
    left = Math.max(8, Math.min(left, window.innerWidth - popupW - 8));

    let top = rect.top + scrollY - popupH - margin;  // above
    let placedAbove = true;

    // If not enough space above, place below the button
    if (top < scrollY + 8) {
      top = rect.bottom + scrollY + margin;          // below
      placedAbove = false;
    }

    calendarPopup.style.left = left + 'px';
    calendarPopup.style.top = top + 'px';
    // Toggle small arrow depending on placement (arrow points to button)
    calendarPopup.classList.toggle('show-arrow', placedAbove);

    calendarPopup.style.visibility = 'visible';
  }

  function openCalendarAnchored() {
    // Build current month/year calendar
    const now = new Date();
    monthSelect.value = now.getMonth();
    yearSelect.value = now.getFullYear();
    generateCalendar(now.getMonth(), now.getFullYear());

    // Show popup and overlay, then position
    overlay.classList.add('active');
    positionCalendarAnchored();

    // Reposition on resize/scroll while open
    const reposer = () => { if (calendarPopup.classList.contains('active')) positionCalendarAnchored(); };
    window.addEventListener('resize', reposer, { passive: true });
    window.addEventListener('scroll', reposer, { passive: true });

    // Store to remove later
    calendarPopup._reposer = reposer;
  }

  function closeCalendar() {
    calendarPopup.classList.remove('active', 'anchored', 'show-arrow');
    overlay.classList.remove('active');
    calendarPopup.style.left = '';
    calendarPopup.style.top = '';
    if (calendarPopup._reposer) {
      window.removeEventListener('resize', calendarPopup._reposer);
      window.removeEventListener('scroll', calendarPopup._reposer);
      calendarPopup._reposer = null;
    }
  }

  // Replace previous click handler to use anchored positioning
  headerCalendarBtn.addEventListener('click', openCalendarAnchored);

  $('.calendar-popup .close-btn').addEventListener('click', closeCalendar);

  // Clear now also resets date filter and reapplies pagination
  $('.calendar-popup .clear').addEventListener('click', () => {
    selectedDate = null;
    dateFilter = null;
    $$('.calendar-table td').forEach(td => td.classList.remove('selected'));
    applyFilter();
  });

  // Confirm applies date filter and paginates
  $('.calendar-popup .confirm').addEventListener('click', () => {
    dateFilter = selectedDate ? new Date(selectedDate) : null;
    applyFilter();
    closeCalendar();
  });

  overlay.addEventListener('click', closeCalendar);

  // initial render
  updateCounts();
  setTimeout(()=>{ applyFilter(); }, 50);

  wirePerCardActions();
  initStars();
})();
</script>
</body>
</html>
