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

    // Assessment notifications (normalize welcome messages)
    // include the notifications table id so client-side delete can target the notifications row
    $stmt = $conn->prepare("SELECT id AS notif_id, message, link, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $msg = $row['message'] ?? '';
        $link = $row['link'] ?? '';
        $notif_row_id = $row['notif_id'] ?? null; // notifications table id
        // treat notifications containing "welcome" (case-insensitive) as welcome notifications
        $isWelcomeMsg = stripos($msg, 'welcome') !== false;

        // also detect denial messages (admin may insert a notifications row when denying)
        $isDeniedMsg = (stripos($msg, 'denied') !== false) || (stripos($msg, 'deny') !== false);

        // detect expiry/validity notifications
        // Treat several known keywords/phrases as expiry notices.
        // Include admin notify phrase "Expired lease notice for ..." (case-insensitive).
        $trimmedMsg = trim($msg);
        $isExpiryMsg = (stripos($msg, 'validity expiry') !== false)
                    || (stripos($msg, 'expiration notice') !== false)
                    || (stripos($msg, 'validity expiry notice') !== false)
                    || (stripos($msg, 'expired lease notice for') !== false)  // anywhere in message
                    || (stripos($trimmedMsg, 'expired lease notice for') === 0); // starts with phrase

        // if we already added a welcome notification earlier (e.g. from users.created_at),
        // avoid adding a second welcome notification
        if ($isWelcomeMsg) {
            $alreadyHasWelcome = false;
            foreach ($notifications as $n) {
                if (isset($n['status']) && $n['status'] === 'welcome') {
                    $alreadyHasWelcome = true;
                    break;
                }
            }
            if ($alreadyHasWelcome) continue;
        }

        // If the message indicates denial, try to associate to the request id (if present in link)
        if ($isDeniedMsg) {
            $deniedEntry = [
                'status' => 'denied',
                'message' => $msg,
                'link' => $link,
                'created_at' => $row['created_at'],
                // notif_id is the id of the notifications row (used for deletion)
                'notif_id' => $notif_row_id
            ];
            // try to extract request id from link e.g. ...?request_id=123 or ...?id=123 or ...?req=123
            $foundId = null;
            if (!empty($link)) {
                if (preg_match('/request_id=(\d+)/i', $link, $m) || preg_match('/\bid=(\d+)\b/i', $link, $m) || preg_match('/req(?:uest)?_?id=(\d+)/i', $link, $m)) {
                    $foundId = (int)$m[1];
                    $deniedEntry['id'] = $foundId;
                }
            }

            // If we found an id, try to populate type/name by querying denied_request (so the card shows Type & Name)
            if (!empty($foundId)) {
                if ($q = $conn->prepare("SELECT type, first_name, middle_name, last_name FROM denied_request WHERE id = ? AND user_id = ? LIMIT 1")) {
                    $q->bind_param("ii", $foundId, $user_id);
                    $q->execute();
                    $r = $q->get_result();
                    if ($row2 = $r->fetch_assoc()) {
                        $deniedEntry['type'] = $row2['type'];
                        $deniedEntry['name'] = trim(($row2['first_name'] ?? '') . ' ' . ($row2['middle_name'] ?? '') . ' ' . ($row2['last_name'] ?? ''));
                    }
                    $q->close();
                }
            }

            $notifications[] = $deniedEntry;
            continue;
        }

        // If the message is an expiry/validity notice, add as 'expiry' so we render title "Expiration Notice"
        if ($isExpiryMsg) {
            $notifications[] = [
                'status' => 'expiry',
                'message' => $msg,
                'link' => $link,
                'created_at' => $row['created_at'],
                'notif_id' => $notif_row_id
            ];
            continue;
        }

        $notifications[] = [
            'status' => $isWelcomeMsg ? 'welcome' : 'assessment',
            'message' => $msg,
            'link' => $link,
            'created_at' => $row['created_at'],
            'notif_id' => $notif_row_id
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
<link rel="icon" type="image/png" href="../assets/re logo blue.png">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="../css/navbar.css">
<link rel="stylesheet" href="../css/footer.css">
<link rel="stylesheet" href="../css/notif.css">
</head>
<body>
<?php include '../Includes/navbar2.php'; ?>
<div class="main-content">
  <div class="container-main" style="min-height: 400px; padding-bottom: 40px;">

  <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:8px">
    <a href="ClientHome.php" style="color:#506C84;text-decoration:none;font-weight:600"><i class="fas fa-arrow-left"></i> Back</a>
    <div style="font-weight:700;font-size:1.15rem;color:#222; padding-right:45px;">All Notifications</div>
    <div></div>
  </div>

  <div class="header-row">
    <div class="tabs" id="notif-tabs">
      <button class="notif-tab active" data-filter="all" id="tab-all"><span class="tab-badge" id="count-all">0</span>All</button>
      <button class="notif-tab" data-filter="favorite" id="tab-fav"><span class="tab-badge" id="count-fav">0</span>Favorites</button>
      <button class="notif-tab" data-filter="archive" id="tab-arch"><span class="tab-badge" id="count-arch">0</span>Archive</button>
    </div>

    <!-- small-screen top row: dropdown + actions (shown only on small screens) -->
    <div class="mobile-top">
      <select id="notifDropdown" class="notif-dropdown" aria-label="Notifications filter">
        <option value="all">All (0)</option>
        <option value="favorite">Favorites (0)</option>
        <option value="archive">Archive (0)</option>
      </select>
      <div class="small-controls" aria-hidden="false">
        <button id="headerDeleteVisibleBtnSmall" class="icon-btn warn" title="Delete visible" aria-label="Delete visible (mobile)"><i class="fas fa-trash"></i></button>
        <button id="headerMarkReadBtnSmall" class="icon-btn" title="Mark visible as read" aria-label="Mark visible read (mobile)"><i class="fas fa-envelope-open-text"></i></button>
        <button id="headerCalendarBtnSmall" class="icon-btn" title="Select date" aria-label="Calendar (mobile)"><i class="fas fa-calendar-alt"></i></button>
      </div>
    </div>

    <!-- added class for responsive reflow -->
    <div class="header-right" style="display:flex;align-items:center;gap:8px">
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

  <!-- Message for empty notifications, favorites, and archive -->
  <div id="notif-empty-message" class="text-muted" style="display:none;text-align:center;margin:30px 0 10px 0;font-size:1.1rem;">
    There is nothing here yet.<br>
    No notifications, favorites, or archived items.
  </div>

  <!-- Separate empty messages for each tab -->
  <div id="notif-empty-all" class="text-muted notif-empty-msg" style="display:none;text-align:center;margin:30px 0 10px 0;font-size:1.1rem;">
    There are no notifications yet.
  </div>
  <div id="notif-empty-fav" class="text-muted notif-empty-msg" style="display:none;text-align:center;margin:30px 0 10px 0;font-size:1.1rem;">
    You have no favorite notifications.
  </div>
  <div id="notif-empty-arch" class="text-muted notif-empty-msg" style="display:none;text-align:center;margin:30px 0 10px 0;font-size:1.1rem;">
    There are no archived notifications.
  </div>

  <?php if ($user_id && count($notifications) > 0): ?>
    <ul class="notif-list" id="notifications-list">
      <?php foreach ($notifications as $notif):
        // make denied cards visually match others (white background / neutral border)
        $borderColor = ($notif['status'] === 'accepted') ? '#198754'
                     : '#ffffff';

        $bgColor = ($notif['status'] === 'accepted') ? '#E9F7EF'
                 : '#ffffff';
 
        // icon selection: use an inline green-check SVG for accepted notifications,
        // keep Font Awesome icons for other statuses
        if ($notif['status'] === 'accepted') {
            // inline green check circle (white check on green circle)
            $iconHtml = '<svg width="20" height="20" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="10" fill="#198754"></circle><path d="M7 12.5l3 3 7-7" stroke="#ffffff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/></svg>';
            $iconColor = '#198754';
        } else {
            $icon = ($notif['status'] === 'denied') ? 'fa-times-circle'
                  : (($notif['status'] === 'welcome') ? 'fa-smile-beam'
                  : (($notif['status'] === 'expiry') ? 'fa-exclamation-circle' : 'fa-file-invoice-dollar'));
            $iconHtml = '<i class="fas ' . $icon . '"></i>';
            $iconColor = ($notif['status'] === 'denied') ? '#DC2626'
                       : (($notif['status'] === 'welcome') ? '#4B7BEC' : (($notif['status'] === 'expiry') ? '#f59e0b' : '#FFC107'));
        }
      ?>
      <!-- use notif_id (notifications table id) when present so delete targets the notifications row.
           fall back to the request id (for accepted/denied requests that came from request tables) -->
      <li class="notif-card-wrapper unread" data-id="<?php echo htmlspecialchars($notif['notif_id'] ?? ($notif['id'] ?? '')); ?>" data-status="<?php echo htmlspecialchars($notif['status']); ?>" data-created_at="<?php echo htmlspecialchars($notif['created_at']); ?>" style="background:<?php echo $bgColor; ?>;border-left:8px solid <?php echo $borderColor; ?>;">
        <div class="notif-left">
          <span class="notif-dot" title="<?php echo ($notif['status'] === 'accepted'?'Unread':''); ?>"></span>
          <button class="notif-star-left" aria-pressed="false" title="Favorite"><i class="fas fa-star"></i></button>
          <span class="notif-icon" style="color:<?php echo $iconColor; ?>"><?php echo $iconHtml; ?></span>
        </div>

        <div class="notif-main">
          <div class="notif-title">
            <?php
              if ($notif['status'] === 'accepted') echo 'Request Accepted';
              elseif ($notif['status'] === 'denied') echo 'Request Denied';
              elseif ($notif['status'] === 'welcome') echo 'Welcome User';
              elseif ($notif['status'] === 'expiry') echo 'Expiration Notice';
              else echo 'Assessment of Fees';
            ?>
          </div>
          <div class="notif-desc">
            <?php if ($notif['status'] === 'accepted' || $notif['status'] === 'denied'): ?>
              Type: <b><?php echo htmlspecialchars($notif['type'] ?? ''); ?></b> &nbsp;|&nbsp; Name: <b><?php echo htmlspecialchars($notif['name'] ?? ''); ?></b>
            <?php elseif ($notif['status'] === 'welcome'): ?>
              Welcome to RestEase!
            <?php elseif ($notif['status'] === 'assessment' || $notif['status'] === 'expiry'): ?>
              <?php echo htmlspecialchars($notif['message']); ?>
            <?php endif; ?>
          </div>
        </div>

        <div class="notif-time"><?php echo date('M d, Y h:i A', strtotime($notif['created_at'])); ?></div>

        <div class="notif-actions">
          <?php if ($notif['status'] === 'accepted' || $notif['status'] === 'denied'): ?>
            <?php
              // Prefer id-based details link when we have an id from the request row or extracted from notification.link
              if (!empty($notif['id'])) {
                $detailsHref = "notification_details.php?id=" . urlencode($notif['id']) . "&type=" . urlencode($notif['status']);
              } else {
                // fallback to created_at (for denied notifications that came only as a notifications row)
                $detailsHref = "notification_details.php?type=" . urlencode($notif['status']) . "&created_at=" . urlencode($notif['created_at']);
              }
            ?>
            <a href="<?php echo $detailsHref; ?>" title="View Details" style="font-size:0.98rem;color:#4B7BEC;text-decoration:none;font-weight:600;">
              Details
            </a>
          <?php elseif ($notif['status'] === 'assessment' || $notif['status'] === 'expiry'): ?>
            <?php
              // Prefer notif_id when present for a reliable lookup on hosted systems
              if (!empty($notif['notif_id'])) {
                  $detailsHref = "notification_details.php?type=" . urlencode($notif['status']) . "&notif_id=" . urlencode($notif['notif_id']);
              } else {
                  $detailsHref = "notification_details.php?type=" . urlencode($notif['status']) . "&created_at=" . urlencode($notif['created_at']);
              }
            ?>
            <a href="<?php echo $detailsHref; ?>" title="View <?php echo ($notif['status'] === 'expiry' ? 'Expiration' : 'Assessment'); ?> Details" style="font-size:0.98rem;color:#4B7BEC;text-decoration:none;font-weight:600;">
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
    <div class="no-cert-msg text-muted">
      No notifications available yet.<br>
      Please contact the administrator or check back later.
    </div>
  <?php endif; ?>
  </div> <!-- /.container-main -->
</div> <!-- /.main-content -->

<!-- Move the footer below main-content for bottom placement -->
<footer><?php include '../Includes/footer-client.php'; ?></footer>

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

  // dropdown for small screens
  const notifDropdown = $('#notifDropdown');

  // small-screen action buttons (visible next to dropdown)
  const headerDeleteVisibleBtnSmall = $('#headerDeleteVisibleBtnSmall');
  const headerMarkReadBtnSmall = $('#headerMarkReadBtnSmall');
  const headerCalendarBtnSmall = $('#headerCalendarBtnSmall');

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

  function showEmptyMessageIfNeeded() {
    const allCount = parseInt(document.getElementById('count-all').textContent, 10) || 0;
    const favCount = parseInt(document.getElementById('count-fav').textContent, 10) || 0;
    const archCount = parseInt(document.getElementById('count-arch').textContent, 10) || 0;
    const msg = document.getElementById('notif-empty-message');
    if (msg) {
      if (allCount === 0 && favCount === 0 && archCount === 0) {
        msg.style.display = '';
      } else {
        msg.style.display = 'none';
      }
    }
  }

  function showEmptyMessageForTab() {
    // Hide all empty messages first
    $$('.notif-empty-msg').forEach(msg => msg.style.display = 'none');
    // Get active tab
    const tab = (document.querySelector('#notif-tabs .notif-tab.active') || {}).getAttribute('data-filter') || 'all';
    let count = 0;
    if (tab === 'all') count = parseInt(document.getElementById('count-all').textContent, 10) || 0;
    if (tab === 'favorite') count = parseInt(document.getElementById('count-fav').textContent, 10) || 0;
    if (tab === 'archive') count = parseInt(document.getElementById('count-arch').textContent, 10) || 0;
    const msgId = tab === 'all' ? 'notif-empty-all' : (tab === 'favorite' ? 'notif-empty-fav' : 'notif-empty-arch');
    if (count === 0) {
      const msg = document.getElementById(msgId);
      if (msg) msg.style.display = '';
    }
  }

  function updateCounts(){
    const allCards = $$('.notif-card-wrapper');
    // Count only non-archived and non-permanently-deleted for "All"
    const allCount = allCards.reduce((acc, c) => {
      const id = c.getAttribute('data-id');
      const isDeleted = id && localStorage.getItem('notif_deleted_' + id) === '1';
      const isArchived = id && localStorage.getItem('notif_archived_' + id) === '1';
      return acc + ((isDeleted || isArchived) ? 0 : 1);
    }, 0);
    let fav = 0, arch = 0;
    allCards.forEach(c=>{
      const id = c.getAttribute('data-id');
      if (id && localStorage.getItem('notif_fav_' + id) === '1') fav++;
      if (id && localStorage.getItem('notif_archived_' + id) === '1') arch++;
    });
    $('#count-all').textContent = allCount;
    $('#count-fav').textContent = fav;
    $('#count-arch').textContent = arch;
 
    // update dropdown labels (if present)
    if (notifDropdown) {
      const optAll = notifDropdown.querySelector('option[value="all"]');
      const optFav = notifDropdown.querySelector('option[value="favorite"]');
      const optArch = notifDropdown.querySelector('option[value="archive"]');
      if (optAll) optAll.textContent = `All (${allCount})`;
      if (optFav) optFav.textContent = `Favorites (${fav})`;
      if (optArch) optArch.textContent = `Archive (${arch})`;
    }

    showEmptyMessageIfNeeded();
    showEmptyMessageForTab();
  }
 
   // ---- Filtering helpers (source-of-truth for pagination) ----
   let dateFilter = null; // selected date filter (Date or null)
 
   function getActiveTabKey(){
     const activeBtn = $('#notif-tabs .notif-tab.active');
     return activeBtn ? activeBtn.getAttribute('data-filter') : 'all';
   }
 
   function cardMatches(card){
     const id = card.getAttribute('data-id') || '';
     // Permanently deleted items (server-side) should not be shown
     if (id && localStorage.getItem('notif_deleted_' + id) === '1') return false;
 
     // archived items should be hidden from "All" and "Favorites" views; only visible in "archive"
     const isArchived = id && localStorage.getItem('notif_archived_' + id) === '1';
     const tab = getActiveTabKey();
     if (tab === 'favorite' && !(id && localStorage.getItem('notif_fav_' + id) === '1' && !isArchived)) return false;
     if (tab === 'archive' && !isArchived) return false;
     if (tab === 'all' && isArchived) return false;
 
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
     showEmptyMessageForTab();
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
    // sync dropdown when a tab is clicked
    if (notifDropdown) notifDropdown.value = this.getAttribute('data-filter');
    applyFilter();
  }));

  // dropdown change -> sync tabs and apply filter
  if (notifDropdown) {
    notifDropdown.addEventListener('change', function(){
      const val = this.value;
      tabs.forEach(x => x.classList.toggle('active', x.getAttribute('data-filter') === val));
      applyFilter();
    });
  }

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
          if (!confirm('Archive this notification?')) return;
          // Mark archived locally and keep the element in DOM
          try { localStorage.setItem('notif_archived_' + id, '1'); } catch(e){}
          // Also remove any favorite flag
          try { localStorage.removeItem('notif_fav_' + id); } catch(e){}
          // Add visual marker/class so developers can style archived items if desired
          card.classList.add('archived');
          // Ensure counts update
          updateCounts();
          // Switch to Archive tab so user sees archived items immediately
          const archiveTab = document.querySelector('#notif-tabs .notif-tab[data-filter="archive"]');
          if (archiveTab) {
            archiveTab.click();
          } else {
            applyFilter();
          }
        });
      }
    });
  }

  // header bulk actions
  // shared handler for marking visible as read
  function handleMarkRead() {
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
  }
  if (headerMarkReadBtn) headerMarkReadBtn.addEventListener('click', handleMarkRead);
  if (headerMarkReadBtnSmall) headerMarkReadBtnSmall.addEventListener('click', handleMarkRead);
 
   // shared handler for deleting visible
   function handleDeleteVisible() {
     const visible = $$('.notif-card-wrapper').filter(c => c.style.display !== 'none');
     if (visible.length === 0) return;
     if (!confirm('Archive all visible notifications?')) return;
     // Archive visible notifications instead of permanently deleting
     visible.forEach(card => {
       const id = card.getAttribute('data-id');
       try { if (id) localStorage.setItem('notif_archived_' + id, '1'); } catch(e){}
       try { if (id) localStorage.removeItem('notif_fav_' + id); } catch(e){}
       // mark visually; keep DOM node so archive tab can show it
       card.classList.add('archived');
     });
     // Update counts
     updateCounts();
     // Switch to Archive tab to show archived items immediately
     const archiveTab = document.querySelector('#notif-tabs .notif-tab[data-filter="archive"]');
     if (archiveTab) {
       archiveTab.click();
     } else {
       applyFilter();
     }
   }
   if (headerDeleteVisibleBtn) headerDeleteVisibleBtn.addEventListener('click', handleDeleteVisible);
   if (headerDeleteVisibleBtnSmall) headerDeleteVisibleBtnSmall.addEventListener('click', handleDeleteVisible);
 
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
  if (headerCalendarBtn) headerCalendarBtn.addEventListener('click', openCalendarAnchored);
  if (headerCalendarBtnSmall) headerCalendarBtnSmall.addEventListener('click', openCalendarAnchored);

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
  // ensure dropdown reflects the initial active tab
  if (notifDropdown) {
    const activeTab = $('#notif-tabs .notif-tab.active');
    if (activeTab) notifDropdown.value = activeTab.getAttribute('data-filter') || 'all';
  }
  setTimeout(()=>{ applyFilter(); }, 50);

  wirePerCardActions();
  initStars();
})();
</script>
</body>
</html>
