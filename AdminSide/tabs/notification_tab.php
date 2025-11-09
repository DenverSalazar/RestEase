<?php
// ...expects $conn ready from Settings.php...
?>
<div class="settings-card" id="notificationTab" style="display:none;">
  <div class="notif-page-title" style="font-size: 1.13rem; font-weight: 600; color: #222;">Notification</div>
  <div class="notif-page-subtitle" style="color: #888; font-size: 0.97rem; margin-bottom: 18px;">Notification settings and preferences will be shown here.</div>

  <?php
  // reuse DB queries to produce JS arrays used by the new UI
  include_once '../Includes/db.php';
  // system notifications (client requests)
  $result = $conn->query("SELECT cr.id, u.first_name, u.last_name, cr.created_at FROM client_requests cr JOIN users u ON cr.user_id = u.id ORDER BY cr.created_at DESC LIMIT 20");
  $notifArr = [];
  if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
      $clientName = htmlspecialchars(trim($row['first_name'] . ' ' . $row['last_name']));
      $notifArr[] = [
        'id' => $row['id'],
        'name' => $clientName,
        'created_at' => $row['created_at'] ?? ''
      ];
    }
  }
  echo '<script>var systemNotifs = ' . json_encode($notifArr) . ';</script>';

  // new users
  $result_users = $conn->query("SELECT id, first_name, last_name, email, created_at FROM users ORDER BY created_at DESC LIMIT 20");
  $userArr = [];
  if ($result_users && $result_users->num_rows > 0) {
    while ($row = $result_users->fetch_assoc()) {
      $userArr[] = [
        'id' => $row['id'],
        'name' => htmlspecialchars(trim($row['first_name'] . ' ' . $row['last_name'])),
        'email' => htmlspecialchars($row['email']),
        'created_at' => $row['created_at'] ?? ''
      ];
    }
  }
  echo '<script>var newUserNotifs = ' . json_encode($userArr) . ';</script>';

  // new requests (duplicate of systemNotifs but keep for backward compatibility)
  $result_req = $conn->query("SELECT cr.id, u.first_name, u.last_name, cr.created_at FROM client_requests cr JOIN users u ON cr.user_id = u.id ORDER BY cr.created_at DESC LIMIT 20");
  $reqArr = [];
  if ($result_req && $result_req->num_rows > 0) {
    while ($row = $result_req->fetch_assoc()) {
      $reqArr[] = [
        'id' => $row['id'],
        'name' => htmlspecialchars(trim($row['first_name'] . ' ' . $row['last_name'])),
        'created_at' => $row['created_at'] ?? ''
      ];
    }
  }
  echo '<script>var newRequestNotifs = ' . json_encode($reqArr) . ';</script>';
  ?>
  <!-- moved search: placed directly below the description -->
  <div style="margin:8px 0 14px;">
    <div style="position:relative;max-width:520px;">
      <i class="fas fa-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#999;"></i>
      <input id="notifSearch" type="text" placeholder="Search by name or message" style="width:100%;padding:10px 14px 10px 36px;border:1px solid #e3e7ed;border-radius:10px;">
    </div>
  </div>

  <!-- New notification header area (tabs + search) -->
  <div class="notif-list-wrapper" style="background:transparent;">
    <div class="notif-list-header" style="display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:14px;">
      <div style="display:flex;align-items:center;gap:18px;">
        <div class="notif-tabs" style="display:flex;gap:12px;align-items:center;">
          <button class="notif-list-tab active" data-filter="all">
            <span class="tab-count" id="tabAllCount">0</span>
            <span>All</span>
          </button>
          <button class="notif-list-tab" data-filter="requests">
            <span class="tab-count" id="tabReqCount">0</span>
            <span>Requests</span>
          </button>
          <button class="notif-list-tab" data-filter="users">
            <span class="tab-count" id="tabUserCount">0</span>
            <span>Users</span>
          </button>

          <!-- Favorite tab -->
          <button class="notif-list-tab" data-filter="favorite">
            <span class="tab-count" id="tabFavCount">0</span>
            <span>Favorites</span>
          </button>

          <!-- Archive tab added -->
          <button class="notif-list-tab" data-filter="archive">
            <span class="tab-count" id="tabArchiveCount">0</span>
            <span>Archive</span>
          </button>
        </div>
      </div>
      <div style="display:flex;align-items:center;gap:12px;">
        <!-- select-all / action icons -->
        <div style="position:relative;display:flex;align-items:center;gap:8px;">
          <!-- removed header select-all button; keep Delete selected and Mark selected as read buttons (they operate on .notif-selected rows) -->
          <button type="button" id="notifDeleteSelectedBtn" title="Delete selected" style="display:none;border:none;border-radius:0;background:transparent;padding:6px;cursor:pointer;color:#666;display:inline-flex;align-items:center;justify-content:center;">
            <i class="fas fa-trash" style="color:inherit;"></i>
          </button>
          <!-- mark selected as read (open mail) -->
          <button type="button" id="notifMarkReadSelectedBtn" title="Mark selected read" style="display:none;border:none;border-radius:0;background:transparent;padding:6px;cursor:pointer;color:#666;display:inline-flex;align-items:center;justify-content:center;margin-left:6px;">
            <i class="fas fa-envelope-open-text" style="color:inherit;"></i>
          </button>
          <div style="position:relative;">
            <button type="button" id="notifCalendarBtn" title="Select date" style="border:none;border-radius:0;background:transparent;padding:6px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;">
              <i class="fas fa-calendar-alt" style="color:#666;"></i>
            </button>
            <div id="notifCalendarPanel" style="display:none; position:absolute; left:0; top:44px; background:#fff; border:1px solid #e3e7ed; padding:12px; border-radius:0; box-shadow:0 8px 24px rgba(0,0,0,0.12); z-index:200; width:320px;">
              <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;gap:8px;">
                <div style="display:flex;gap:8px;align-items:center;">
                  <select id="calendarMonth" style="padding:6px;border:1px solid #e3e7ed;border-radius:0;background:#fff;"></select>
                  <select id="calendarYear" style="padding:6px;border:1px solid #e3e7ed;border-radius:0;background:#fff;"></select>
                </div>
                <div style="display:flex;gap:6px;">
                  <button id="calendarClear" title="Clear" style="padding:6px 10px;border:1px solid #e3e7ed;background:transparent;border-radius:0;cursor:pointer;">Clear</button>
                </div>
              </div>
              <div style="display:flex;justify-content:space-between;font-weight:700;margin-bottom:6px;color:#666;font-size:12px;">
                <div style="width:14.28%;text-align:center;">Sun</div><div style="width:14.28%;text-align:center;">Mon</div><div style="width:14.28%;text-align:center;">Tue</div><div style="width:14.28%;text-align:center;">Wed</div><div style="width:14.28%;text-align:center;">Thu</div><div style="width:14.28%;text-align:center;">Fri</div><div style="width:14.28%;text-align:center;">Sat</div>
              </div>
              <div id="calendarGrid" style="display:grid;grid-template-columns:repeat(7,1fr);gap:6px;"></div>

              <!-- moved Confirm down here so it's below the calendar grid -->
              <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:10px;">
                <button id="calendarConfirm" title="Confirm" style="padding:8px 14px;border-radius:0;background:#0077B6;border:none;color:#fff;cursor:pointer;">Confirm</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Notification list -->
    <div id="notifListContainer" style="display:flex;flex-direction:column;gap:10px;">
      <!-- JS will populate notification items here -->
    </div>
  </div>

  <style>
    /* compact inline styles for notification list */
    .notif-list-item {
      display:flex;
      align-items:center;
      background:#fff;
      padding:12px 16px;
      border-radius:10px;
      box-shadow:0 1px 4px rgba(0,0,0,0.04);
      border:1px solid #eef2f5;
      gap:12px;
    }
    /* highlight for new / unread notifications */
    .notif-new {
      background: #EFEFEF;
      border: 1px solid #E8E8E8;
      box-shadow: 0 2px 6px rgba(0,0,0,0.04);
    }
    .notif-left { display:flex;align-items:center;gap:8px;min-width:72px; }
    .notif-dot { width:10px;height:10px;border-radius:50%;background:#b6dca6;display:inline-block;box-shadow:0 1px 2px rgba(0,0,0,0.06);cursor:pointer; }
    .notif-dot.read { background:transparent;border:1px solid #e6e9ec; }
    .notif-icon{ width:36px;height:36px;border-radius:6px;background:#f5f7fa;display:inline-flex;align-items:center;justify-content:center;color:#0077B6;font-weight:700;cursor:pointer; }
    /* star button: transition and pressed color (yellow) */
    .notif-star-left {
      background: transparent;
      border: none;
      padding: 0;
      margin: 0;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      color: #bfc6cc;
      font-size: 1.15rem;
      transition: color 200ms ease;
    }
    .notif-star-left[aria-pressed="true"] { color: #f0b400; }
    .notif-main { flex:1; min-width:0; }
    .notif-title { font-weight:600;color:#222;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:flex;align-items:center;gap:8px; }
    .notif-body { color:#666;font-size:0.95rem;margin-top:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
    .notif-meta { text-align:right;min-width:110px;color:#9aa3ad;font-size:0.9rem; }
    .notif-actions { display:flex;align-items:center;gap:8px; }
    .notif-delete { background:#ff6b6b;border:none;color:#fff;padding:8px;border-radius:8px;cursor:pointer;width:36px;height:36px;display:inline-flex;align-items:center;justify-content:center; }
    /* .notif-archive { background:#6c757d;border:none;color:#fff;padding:8px;border-radius:8px;cursor:pointer;width:36px;height:36px;display:inline-flex;align-items:center;justify-content:center; } */
    .tab-count { display:inline-block;background:#e9eef8;color:#2d72d9;padding:3px 8px;border-radius:999px;font-weight:700;margin-right:8px;font-size:0.95rem; }
    /* animated tabs: color and underline slide */
    .notif-list-tab {
      background: transparent;
      border: none;
      padding: 8px 10px;
      border-radius: 0;
      padding-bottom: 12px; /* room for underline */
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-weight: 600;
      color: #444;
      position: relative; /* for underline */
      transition: color 260ms ease, transform 140ms ease;
      -webkit-tap-highlight-color: transparent;
    }
    .notif-list-tab:active { transform: translateY(1px); }

    /* underline pseudo-element (hidden by default) */
    .notif-list-tab::after {
      content: "";
      position: absolute;
      left: 50%;
      right: 50%;
      bottom: 6px;
      height: 3px;
      background: #2d72d9;
      border-radius: 3px;
      opacity: 0;
      transition: left 320ms cubic-bezier(.2,.8,.2,1), right 320ms cubic-bezier(.2,.8,.2,1), opacity 220ms ease;
    }
    /* active tab: slide underline to full width */
    .notif-list-tab.active {
      color: #2d72d9;
    }
    .notif-list-tab.active::after {
      left: 8px;
      right: 8px;
      opacity: 1;
    }

    /* subtle transition for the count bubble */
    .tab-count { transition: background 220ms ease, transform 180ms ease; }
    .notif-list-tab.active .tab-count { transform: translateY(-1px); background: rgba(45,114,217,0.08); }

    /* icon-only header controls: no border/background, single icon color */
    #notifSelectAllBtn, #notifDeleteSelectedBtn, #notifMarkReadSelectedBtn, #notifCalendarBtn {
      height:36px;
      min-width:36px;
      border: none;
      background: transparent;
      border-radius: 0;
      padding: 6px;
      color: #666; /* match calendar icon color */
    }
    /* ensure the icon inherits the color */
    #notifSelectAllBtn i, #notifDeleteSelectedBtn i, #notifMarkReadSelectedBtn i, #notifCalendarBtn i { color: inherit; }

    /* override: make the top "Delete selected" icon red to match the UI */
    #notifDeleteSelectedBtn { color: #ff6b6b !important; }
    #notifDeleteSelectedBtn i { color: inherit !important; }

    /* remove any corner/rounding on calendar panel and its descendants */
    #notifCalendarPanel,
    #notifCalendarPanel * {
      border-radius: 0 !important;
      overflow: visible;
    }

    /* calendar popup styles (kept, day cells remain square) */
    #notifCalendarPanel { font-family:inherit; }
    #notifCalendarPanel .day-cell { height:36px; border-radius:0; border:1px solid transparent; background:#fff; cursor:pointer; display:flex;align-items:center;justify-content:center; }
    #notifCalendarPanel .day-cell.range { background:#ffe6c9; }
    #notifCalendarPanel .day-cell.selected { background:#0077B6; color:#fff; border-color:#006099; }
    #notifCalendarPanel .day-cell:hover { box-shadow:0 1px 4px rgba(0,0,0,0.06); }
    #notifCalendarPanel .day-cell.today { box-shadow:inset 0 0 0 1px rgba(0,0,0,0.04); }

    /* confirm button color changed and preserved sizing */
    #calendarConfirm { background:#0077B6; color:#fff; border:none; padding:8px 14px; border-radius:0; cursor:pointer; }

    #calendarClear { background:transparent; border:1px solid #e3e7ed; padding:6px 10px; border-radius:0; cursor:pointer; }

    /* append these simple corner rules to the existing <style> block */
    /* rounded corners for main boxes */
    .settings-card {
      border-radius: 10px;
      overflow: visible; /* allow popups (calendar/modal) to escape container */
    }

    .notif-list-wrapper {
      border-radius: 10px;
      overflow: visible; /* list items keep their own radius */
    }

    /* ensure each notification box keeps a 10px radius (already present but reinforce) */
    .notif-list-item {
      border-radius: 10px; /* ensure exact 10px everywhere */
    }

    /* calendar panel and controls */
    #notifCalendarPanel {
      border-radius: 10px;
      overflow: hidden;
    }
    #notifCalendarPanel .day-cell {
      border-radius: 10px; /* round each day cell */
    }

    /* footer/pagination and action buttons */
    #notifListFooter, .notif-actions, .notif-actions button, #calendarConfirm, #calendarClear {
      border-radius: 10px;
    }

    /* small touch: rounded star and icon buttons */
    .notif-star-left, .notif-delete, #notifDeleteSelectedBtn, #notifMarkReadSelectedBtn, #notifCalendarBtn {
      border-radius: 10px;
    }

    /* UI overrides to match the provided screenshot (spacing, sizes, badges, pill items) */
    /* page header */
    #notificationTab .notif-page-title { font-size: 1.45rem; font-weight: 700; color: #222; margin-bottom: 6px; }
    #notificationTab .notif-page-subtitle { color: #9aa3ad; font-size: 0.98rem; margin-bottom: 14px; }

    /* search */
    #notifSearch { width:100%; height:22px; padding:6px 12px 6px 44px; border-radius:12px; border:1px solid #e9eef5; background:#fff; box-shadow:none; font-size:0.98rem; }
    #notificationTab i.fas.fa-search { left:14px; top:50%; color:#9aa3ad; }

    /* tabs */
    .notif-list-tab { padding:10px 12px; gap:10px; color:#444; font-weight:700; }
    .tab-count { min-width:28px; height:28px; line-height:28px; padding:0 8px; border-radius:999px; font-weight:800; font-size:0.95rem; background:#eaf1ff; color:#2d72d9; display:inline-flex; align-items:center; justify-content:center; }
    .notif-list-tab.active { color: #2d72d9; }
    .notif-list-tab.active::after { left:8px; right:8px; bottom:6px; height:3px; background:#2d72d9; opacity:1; border-radius:3px; }

    /* header action icons (right) */
    .notif-list-header > div:last-child { display:flex; align-items:center; gap:12px; }
    #notifDeleteSelectedBtn, #notifMarkReadSelectedBtn, #notifCalendarBtn { height:40px; width:40px; border-radius:10px; display:inline-flex; align-items:center; justify-content:center; background:transparent; color:#6d7780; }

    /* list items — pill style */
    .notif-list-item { padding:14px 18px; border-radius:12px; background:#f6f7f8; border:1px solid #eef2f5; gap:14px; align-items:center; }
    .notif-left { min-width:92px; gap:12px; display:flex; align-items:center; }
    .notif-icon { width:44px; height:44px; border-radius:8px; background:#fff; display:inline-flex; align-items:center; justify-content:center; border:1px solid #eef6ff; color:#0077B6; }
    .notif-title { font-weight:700; font-size:1.02rem; color:#222; }
    .notif-body { font-size:0.95rem; color:#6f767d; margin-top:4px; }
    .notif-meta { min-width:140px; text-align:right; color:#9aa3ad; font-size:0.92rem; }
    .notif-delete { width:44px; height:36px; border-radius:10px; background:#ff6b6b; display:inline-flex; align-items:center; justify-content:center; color:#fff; }

    /* unread marker */
    .notif-dot { width:11px; height:11px; border-radius:50%; background:#8fd08a; display:inline-block; margin-right:6px; }

    /* make read rows white immediately (override other .notif-list-item backgrounds) */
    .notif-list-item.notif-read {
      background: #fff !important;
      border-color: #eef2f5 !important;
      box-shadow: 0 1px 4px rgba(0,0,0,0.02) !important;
    }

    /* footer pagination centered */
    #notifListFooter { margin-top:12px; padding-top:8px; border-top:0; display:flex; justify-content:center; align-items:center; gap:12px; }

    /* responsive tweak */
    @media (max-width:760px){
      .notif-meta { display:none; }
      .notif-left { min-width:72px; }
    }

    /* hide per-item checkboxes (if present) and any generic checkboxes inside the list */
    #notifListContainer input[type="checkbox"],
    #notifListContainer .notif-checkbox {
      display: none !important;
      width: 0 !important;
      height: 0 !important;
      margin: 0 !important;
      padding: 0 !important;
      border: none !important;
      appearance: none !important;
    }

    /* hide header/select-all checkbox icon/button to remove checkbox beside delete-all */
    #notifSelectAllBtn,
    #notifSelectAllBtn * {
      display: none !important;
    }

    /* also hide any square/check icons used for selection in the header */
    .fa-square, .fa-check-square, .fa-check {
      display: none !important;
    }

    /* ensure layout doesn't leave awkward gaps where checkbox was */
    .notif-left { gap: 8px; align-items:center; }
    .notif-list-header > div:last-child { gap: 8px; }
  </style>

  <!-- replace the whole script block with the cleaned script below -->
  <script>
(function(){
  const $ = id => document.getElementById(id);

  // server-provided arrays
  const systemNotifs = window.systemNotifs || [];
  const newUserNotifs = window.newUserNotifs || [];
  const newRequestNotifs = window.newRequestNotifs || [];

  // unify lists
  const all = [];
  systemNotifs.forEach(n => all.push({
    id: 'sys_' + (n.id || Math.random()),
    kind: 'request',
    title: 'New client request',
    message: 'New client request received from ' + (n.name || ''),
    time: n.created_at || '',
    readKey: 'notif_read_sys_' + (n.id || ''),
    srcType: 'request',
    srcId: String(n.id || '')
  }));
  newRequestNotifs.forEach(n => all.push({
    id: 'nreq_' + (n.id || Math.random()),
    kind: 'request',
    title: 'New client request',
    name: n.name || '',
    message: 'New client request received from ' + (n.name || ''),
    time: n.created_at || '',
    readKey: 'notif_read_nreq_' + (n.id || ''),
    srcType: 'request',
    srcId: String(n.id || '')
  }));
  newUserNotifs.forEach(u => all.push({
    id: 'usr_' + (u.id || Math.random()),
    kind: 'user',
    title: 'New user registered',
    message: 'New user registered: ' + (u.name || '') + (u.email ? ' ('+u.email+')' : ''),
    time: u.created_at || '',
    readKey: 'notif_read_usr_' + (u.id || ''),
    srcType: 'user',
    srcId: String(u.id || '')
  }));

  // robust date parser (handles ISO and common "YYYY-MM-DD HH:MM:SS")
  function parseDate(s){
    if (!s) return null;
    if (s instanceof Date) return s;
    if (typeof s !== 'string') return null;
    const t = s.trim().replace(' ', 'T');
    const d = new Date(t);
    if (!isNaN(d.getTime())) return d;
    const p = Date.parse(s);
    return isNaN(p) ? null : new Date(p);
  }

  // helpers to read calendar-created inputs (they may be created on Confirm)
  function getDateInputs(){
    return { from: document.getElementById('notifDateFrom'), to: document.getElementById('notifDateTo') };
  }
  function attachDateInputs(){
    const d = getDateInputs();
    if (d.from) d.from.addEventListener('change', window.onDateChange);
    if (d.to) d.to.addEventListener('change', window.onDateChange);
  }

  // sort by parsed time (newest first)
  all.sort((a,b) => {
    const da = parseDate(a.time), db = parseDate(b.time);
    if (da && db) return db - da;
    return 0;
  });

  // Deduplicate real items that share the same source (srcType:srcId).
  // Keep the first occurrence (list is newest-first so the first is the newest).
  (function dedupeBySource(){
    const seen = new Map();
    const kept = [];
    for (let i = 0; i < all.length; i++){
      const it = all[i];
      const key = (it.srcType ? it.srcType : '') + ':' + (it.srcId ? it.srcId : '');
      if (!key || key === ':') {
        // fallback - keep items without a source key
        kept.push(it);
        continue;
      }
      if (!seen.has(key)) {
        seen.set(key, true);
        kept.push(it);
      }
      // else duplicate — skip
    }
    // replace contents of all with deduped list (preserve reference)
    all.length = 0;
    kept.forEach(x => all.push(x));
  })();

  const PAGE_SIZE = 10;
  let currentPage = 1;
  let showAll = false;
  let currentFilter = 'all';
  let selectAllGlobal = false;

  // date inputs are read dynamically via getDateInputs() — attachDateInputs() will wire them when created

  // safe localStorage getter to avoid uncaught exceptions (used throughout)
  const safeGet = key => { try { return localStorage.getItem(key); } catch(e) { return null; } };

  // Shared: update Delete / Mark buttons based on any selected rows
  function updateDeleteBtnState(){
    const container = document.getElementById('notifListContainer');
    const deleteSelBtn = document.getElementById('notifDeleteSelectedBtn');
    const markReadBtn = document.getElementById('notifMarkReadSelectedBtn');
    if (!container || !deleteSelBtn) return;
    const any = container.querySelectorAll('.notif-list-item.notif-selected').length > 0;
    deleteSelBtn.style.display = any ? 'inline-flex' : 'none';
    if (markReadBtn) markReadBtn.style.display = any ? 'inline-flex' : 'none';
  }

  // --- Delete-all confirmation modal (created once) ---
  (function initDeleteConfirmModal(){
    if (document.getElementById('deleteAllConfirmModal')) return;
    const tpl = document.createElement('div');
    tpl.id = 'deleteAllConfirmModal';
    tpl.style.display = 'none';

    // Use the exact Records.php modal markup/styles so size matches (width:520px, padding, radii, etc.)
    tpl.innerHTML = `
      <div id="deleteModal" class="modal-overlay" style="display:none; position:fixed; inset:0; align-items:center; justify-content:center; background:rgba(0,0,0,0.35); z-index:10000;">
        <div class="modal-content" style="background:#fff; border-radius:10px; width:520px; max-width:94%; padding:22px 26px; box-shadow:0 18px 48px rgba(0,0,0,0.16);">
          <div class="modal-card-header" style="padding-top:2px;padding-bottom:8px;">
             <div style="display:flex;align-items:center;justify-content:center; padding-bottom:10px;">
            <div style="width:64px;height:64px;border-radius:50%;background:#fdecec;display:flex;align-items:center;justify-content:center;">  
            <i class="fas fa-exclamation-triangle" style="color:#e74c3c;font-size:2rem;margin-bottom:8px;"></i> </div>
          </div>
            <h2 style="color:#e74c3c;margin:0;font-size:1.3rem;">Confirm Archive</h2>
          </div>
          <div class="modal-body" style="margin:18px 0 2px 0; text-align:center;">
            <p id="deleteModalText" style="color:#444;font-size:1.07rem;margin:0;">
              Are you sure you want to archive this record?<br>
              This action will move the record to the archive section.
            </p>
          </div>
          <div class="modal-footer" style="display:flex;justify-content:center;gap:16px;">
            <button id="modalDeleteBtn" class="modal-delete-btn" style="background:#ff6b6b;color:#fff;border:none;padding:10px 20px;border-radius:8px;cursor:pointer;font-weight:700;">Archive</button>
            <button id="modalCancelBtn" class="modal-cancel-btn" style="background:#fff;color:#6d7780;border:1px solid #e7e7e7;padding:10px 20px;border-radius:8px;cursor:pointer;font-weight:700;">Cancel</button>
          </div>
        </div>
      </div>
    `;
    document.body.appendChild(tpl);

    // simple show helper
    window.showDeleteConfirm = function(message, sub, onConfirm){
      const wrapper = document.getElementById('deleteAllConfirmModal');
      const modal = document.getElementById('deleteModal');
      if (!wrapper || !modal) return;
      wrapper.style.display = 'block';
      modal.style.display = 'flex';
      const msgEl = modal.querySelector('#deleteModalText');
      if (msgEl) msgEl.innerHTML = message ? (message + (sub ? '<br><small style="color:#666;">' + sub + '</small>' : '')) : (sub || 'This action cannot be undone.');
      const confirmBtn = modal.querySelector('#modalDeleteBtn');
      const cancelBtn = modal.querySelector('#modalCancelBtn');

      // cleanup helpers
      function cleanupHandlers() {
        if (confirmBtn) confirmBtn.removeEventListener('click', onConfirmHandler);
        if (cancelBtn) cancelBtn.removeEventListener('click', onCancelHandler);
        modal.removeEventListener('click', onOverlayClick);
        wrapper.style.display = 'none';
        modal.style.display = 'none';
      }
      function onConfirmHandler(e){
        e.preventDefault();
        try { if (typeof onConfirm === 'function') onConfirm(); } finally { cleanupHandlers(); }
      }
      function onCancelHandler(e){
        e.preventDefault();
        cleanupHandlers();
      }
      function onOverlayClick(e){
        if (e.target === modal) { cleanupHandlers(); }
      }

      if (confirmBtn) confirmBtn.addEventListener('click', onConfirmHandler);
      if (cancelBtn) cancelBtn.addEventListener('click', onCancelHandler);
      // allow clicking outside to close
      modal.addEventListener('click', onOverlayClick);
    };
  })();

  function updateCounts(){
    const counts = { all:0, req:0, user:0, fav:0, arch:0 };
    for (let i=0;i<all.length;i++){
      const it = all[i];
      if (safeGet('notif_deleted_' + it.id) === '1') continue;
      const archived = safeGet('notif_archived_' + it.id) === '1';
      if (archived) { counts.arch++; continue; }
      counts.all++;
      if (it.kind === 'request') counts.req++;
      if (it.kind === 'user') counts.user++;
      if (safeGet('notif_fav_' + it.id) === '1') counts.fav++;
    }
    const setIf = (id,val)=>{ const el = $(id); if (el) el.textContent = String(val); };
    setIf('tabAllCount', counts.all);
    setIf('tabReqCount', counts.req);
    setIf('tabUserCount', counts.user);
    setIf('tabFavCount', counts.fav);
    setIf('tabArchiveCount', counts.arch);
  }

  function appendFooter(container, total, totalPages){
    const existing = container.querySelector('#notifListFooter');
    if (existing) existing.remove();
    const footer = document.createElement('div');
    footer.id = 'notifListFooter';
    footer.style.display = 'flex';
    footer.style.alignItems = 'center';
    footer.style.justifyContent = 'space-between';
    footer.style.width = '100%';
    footer.style.boxSizing = 'border-box';
    footer.style.marginTop = '8px';
    footer.style.gap = '12px';
    footer.style.color = '#666';
    footer.style.fontSize = '0.95rem';

    const left = document.createElement('div');
    left.textContent = `Page ${showAll ? 1 : currentPage} of ${totalPages}`;
    footer.appendChild(left);

    const center = document.createElement('div');
    center.style.display = 'flex';
    center.style.justifyContent = 'center';
    center.style.alignItems = 'center';
    center.style.gap = '6px';

    function createPageBtn(label, disabled, onClick, isActive){
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.textContent = label;
      btn.style.margin = '0 4px';
      btn.style.padding = '6px 10px';
      btn.style.border = '1px solid #e3e7ed';
      btn.style.borderRadius = '6px';
      btn.style.background = isActive ? '#2d72d9' : 'transparent';
      btn.style.color = isActive ? '#fff' : '#444';
      btn.style.cursor = disabled ? 'not-allowed' : 'pointer';
      btn.disabled = !!disabled;
      btn.addEventListener('click', ev => { ev.stopPropagation(); if (!disabled) onClick(); });
      return btn;
    }

    const prevBtn = createPageBtn('‹', showAll || currentPage <= 1, function(){ if (currentPage>1){ currentPage--; renderList(currentFilter, $('notifSearch') ? $('notifSearch').value : ''); } });
    center.appendChild(prevBtn);

    if (!showAll){
      let maxButtons = 5;
      let start = Math.max(1, currentPage - Math.floor(maxButtons/2));
      let end = Math.min(totalPages, start + maxButtons - 1);
      start = Math.max(1, end - maxButtons + 1);
      for (let p = start; p <= end; p++){
        center.appendChild(createPageBtn(p, false, (function(page){ return function(){ currentPage = page; renderList(currentFilter, $('notifSearch') ? $('notifSearch').value : ''); }; })(p), (p === currentPage)));
      }
    } else {
      center.appendChild(createPageBtn('1', true, function(){}, true));
    }

    const nextBtn = createPageBtn('›', showAll || currentPage >= totalPages, function(){ if (currentPage < totalPages) { currentPage++; renderList(currentFilter, $('notifSearch') ? $('notifSearch').value : ''); } });
    center.appendChild(nextBtn);

    footer.appendChild(center);

    const right = document.createElement('div'); right.style.flex = '0 0 120px';
    footer.appendChild(right);

    container.appendChild(footer);
  }

  function renderList(filter, query){
    const container = $('notifListContainer');
    if (!container) return;
    container.innerHTML = '';

    const deleteSelBtn = $('notifDeleteSelectedBtn');
    const markReadSelBtn = $('notifMarkReadSelectedBtn');

    const q = (query||'').toLowerCase();

    // read date inputs live (they may be created by the calendar Confirm)
    let fromDate = null, toDate = null;
    try {
      const d = getDateInputs();
      if (d.from && d.from.value) fromDate = parseDate(d.from.value + 'T00:00:00');
      if (d.to && d.to.value) toDate = parseDate(d.to.value + 'T23:59:59');
    } catch(e){ fromDate = null; toDate = null; }

    let items = all.filter(item=>{
      try { if (localStorage.getItem('notif_deleted_' + item.id) === '1') return false; } catch(e){}
      if (fromDate || toDate) {
        if (!item.time) return false;
        const it = parseDate(item.time);
        if (!it || isNaN(it.getTime())) return false;
        if (fromDate && it < fromDate) return false;
        if (toDate && it > toDate) return false;
      }

      let isArchived = false;
      try { isArchived = localStorage.getItem('notif_archived_' + item.id) === '1'; } catch(e){ isArchived = false; }

      if (filter === 'archive') {
        if (!isArchived) return false;
      } else {
        if (isArchived) return false;
      }

      if (filter === 'requests' && item.kind !== 'request') return false;
      if (filter === 'users' && item.kind !== 'user') return false;
      if (filter === 'favorite') {
        try { if (localStorage.getItem('notif_fav_' + item.id) !== '1') return false; } catch(e){ return false; }
      }
      if (!q) return true;
      return (item.name||'').toLowerCase().includes(q) || (item.message||'').toLowerCase().includes(q);
    });

    const total = items.length;
    const totalPages = Math.max(1, Math.ceil(total / PAGE_SIZE));
    if (currentPage > totalPages) currentPage = 1;
    const itemsToRender = showAll ? items : items.slice((currentPage-1)*PAGE_SIZE, (currentPage-1)*PAGE_SIZE + PAGE_SIZE);

    if (itemsToRender.length === 0){
      const empty = document.createElement('div');
      empty.style.color = '#888';
      empty.style.textAlign = 'center';
      empty.style.padding = '28px';
      empty.textContent = 'No notifications.';
      container.appendChild(empty);
      appendFooter(container, total, totalPages);
      updateCounts();
      return;
    }

    itemsToRender.forEach(item=>{
      const isRead = safeGet(item.readKey) === '1';
      const isFav = safeGet('notif_fav_' + item.id) === '1';
      const row = document.createElement('div');
      row.className = 'notif-list-item';
      row.dataset.id = item.id || '';

      row.innerHTML = `
        <div class="notif-left">
          <span class="notif-dot ${isRead ? 'read' : ''}" title="${isRead ? 'Read' : 'Unread'}"></span>
          <button class="notif-star-left" title="Favorite" aria-pressed="${isFav ? 'true' : 'false'}"><i class="fas fa-star"></i></button>
          <div class="notif-icon" title="${item.kind === 'request' ? 'Client' : 'Message'}"><i class="fas ${item.kind === 'request' ? 'fa-user' : 'fa-envelope'}"></i></div>
        </div>
        <div class="notif-main">
          <div class="notif-title"><span class="notif-title-text"></span></div>
          <div class="notif-body"><span class="notif-body-text"></span></div>
        </div>
        <div class="notif-meta"><div class="notif-meta-text"></div></div>
        <div class="notif-actions">
          <button class="notif-delete" title="Archive"><i class="fas fa-trash"></i></button>
        </div>
      `;

      const titleEl = row.querySelector('.notif-title-text');
      if (titleEl) titleEl.textContent = item.title + (item.name ? ' — ' + item.name : '');
      const bodyEl = row.querySelector('.notif-body-text');
      if (bodyEl) bodyEl.textContent = item.message || '';
      const metaEl = row.querySelector('.notif-meta-text');
      if (metaEl) {
        const dt = parseDate(item.time);
        metaEl.textContent = dt ? dt.toLocaleString() : 'Just Now';
      }

      // apply explicit read or unread class so we can force the visual immediately
      if (isRead) {
        row.classList.add('notif-read');
      } else {
        row.classList.add('notif-new');
      }

      const starBtn = row.querySelector('.notif-star-left');
      starBtn.setAttribute('aria-pressed', isFav ? 'true' : 'false');
      starBtn.addEventListener('click', function(ev){
        ev.stopPropagation();
        const key = 'notif_fav_' + item.id;
        const currentlyFav = safeGet(key) === '1';
        try {
          if (currentlyFav) localStorage.removeItem(key);
          else localStorage.setItem(key, '1');
        } catch(e){}
        const newFav = !currentlyFav;
        starBtn.setAttribute('aria-pressed', newFav ? 'true' : 'false');
        updateCounts();
        if (currentFilter === 'favorite') renderList(currentFilter, $('notifSearch') ? $('notifSearch').value : '');
      });

      const dotBtn = row.querySelector('.notif-dot');
      dotBtn.addEventListener('click', function(ev){
        ev.stopPropagation();
        selectAllGlobal = false;
        row.classList.toggle('notif-selected');
        updateDeleteBtnState();
      });

      // Updated icon click handler: ensure visual read state is applied immediately
      const iconBtn = row.querySelector('.notif-icon');
      iconBtn.addEventListener('click', function(ev){
        ev.stopPropagation();
        try {
          if (item.readKey) localStorage.setItem(item.readKey, '1');
          else localStorage.setItem('notif_read_' + item.id, '1');
        } catch(e){}
        const dot = row.querySelector('.notif-dot'); if (dot) dot.classList.add('read');
        // remove unread highlight and show explicit read state
        row.classList.remove('notif-new');
        row.classList.add('notif-read');
        row.style.background = ''; // clear any inline background
        updateCounts();
      });

      // Updated row click handler: mark as read immediately and update UI/counts
      row.addEventListener('click', function(e){
        if (e.target.closest('.notif-delete') || e.target.closest('.notif-star-left') || e.target.closest('.notif-dot') || e.target.closest('.notif-icon')) return;
        try {
          if (item.readKey) localStorage.setItem(item.readKey, '1');
          else localStorage.setItem('notif_read_' + item.id, '1');
        } catch(e){}
        const dot = row.querySelector('.notif-dot');
        if (dot) dot.classList.add('read');
        row.classList.remove('notif-new');
        row.classList.add('notif-read');
        row.style.background = ''; // ensure the grey highlight is cleared immediately
        updateCounts();
        if (item.kind === 'request') window.location.href = 'ClientsRequest.php';
      });

      const delBtn = row.querySelector('.notif-delete');
      delBtn.addEventListener('click', function(ev){
        ev.stopPropagation();
        const id = item.id;
        if (!id) return;
        if (currentFilter === 'archive') {
          try {
            localStorage.setItem('notif_deleted_' + id, '1');
            localStorage.removeItem('notif_archived_' + id);
            localStorage.removeItem('notif_fav_' + id);
            if (item.readKey) localStorage.removeItem(item.readKey);
          } catch(e){}
        } else {
          try { localStorage.setItem('notif_archived_' + id, '1'); } catch(e){}
        }
        updateCounts();
        renderList(currentFilter, $('notifSearch') ? $('notifSearch').value : '');
      });

      container.appendChild(row);
    });

    appendFooter(container, total, totalPages);
    updateCounts();
  }

  // tabs
  document.querySelectorAll('.notif-list-tab').forEach(btn=>{
    btn.addEventListener('click', function(){
      document.querySelectorAll('.notif-list-tab').forEach(b=>b.classList.remove('active'));
      this.classList.add('active');
      currentFilter = this.dataset.filter;
      showAll = false;
      currentPage = 1;
      renderList(currentFilter, $('notifSearch') ? $('notifSearch').value : '');
    });
  });

  // search
  const searchEl = $('notifSearch');
  if (searchEl) searchEl.addEventListener('input', function(){ showAll = false; currentPage = 1; renderList(currentFilter, this.value); });

  window.onDateChange = function(){ showAll = false; currentPage = 1; renderList(currentFilter, $('notifSearch') ? $('notifSearch').value : ''); };
  // attach if calendar inputs already exist (and Calendar Confirm will call attachDateInputs)
  attachDateInputs();

  // calendar (kept as before)
  (function initCalendar(){
    const calendarBtn = $('notifCalendarBtn');
    const calendarPanel = $('notifCalendarPanel');
    const monthSelect = $('calendarMonth');
    const yearSelect = $('calendarYear');
    const grid = $('calendarGrid');
    const confirmBtn = $('calendarConfirm');
    const clearBtn = $('calendarClear');
    if (!calendarBtn || !calendarPanel || !monthSelect || !yearSelect || !grid) return;

    // Ensure the calendar popup is attached to the document body so it's not clipped
    // by parent containers (settings-card had overflow previously). This preserves
    // the existing element and keeps all IDs the same for the JS listeners.
    if (calendarPanel.parentElement !== document.body) document.body.appendChild(calendarPanel);

    const monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    const today = new Date();
    let viewYear = today.getFullYear();
    let viewMonth = today.getMonth();
    let pickFrom = null, pickTo = null;

    function populateMonthYear(){
      monthSelect.innerHTML = '';
      monthNames.forEach((m,i)=> monthSelect.appendChild(new Option(m,i)));
      yearSelect.innerHTML = '';
      const start = today.getFullYear() - 5;
      const end = today.getFullYear() + 2;
      for (let y = start; y <= end; y++) yearSelect.appendChild(new Option(y,y));
      monthSelect.value = viewMonth;
      yearSelect.value = viewYear;
    }

    function startOfDay(d){ return new Date(d.getFullYear(), d.getMonth(), d.getDate()); }
    function sameDay(a,b){ return a && b && a.getFullYear()===b.getFullYear() && a.getMonth()===b.getMonth() && a.getDate()===b.getDate(); }
    function inRange(d,a,b){ if (!a || !b) return false; return startOfDay(d) >= startOfDay(a) && startOfDay(d) <= startOfDay(b); }

    function renderCalendar(){
      grid.innerHTML = '';
      const firstDay = new Date(viewYear, viewMonth, 1);
      const startOffset = firstDay.getDay();
      const daysInMonth = new Date(viewYear, viewMonth+1, 0).getDate();
      for (let i=0;i<startOffset;i++) grid.appendChild(document.createElement('div'));
      for (let day=1; day<=daysInMonth; day++){
        const cellDate = new Date(viewYear, viewMonth, day);
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'day-cell';
        btn.textContent = String(day);
        if (sameDay(cellDate, today)) btn.classList.add('today');
        if (sameDay(cellDate, pickFrom) || sameDay(cellDate, pickTo)) btn.classList.add('selected');
        else if (inRange(cellDate, pickFrom, pickTo)) btn.classList.add('range');
        btn.addEventListener('click', function(e){
          e.stopPropagation();
          if (!pickFrom || (pickFrom && pickTo)) { pickFrom = startOfDay(cellDate); pickTo = null; }
          else {
            const clicked = startOfDay(cellDate);
            if (+clicked < +startOfDay(pickFrom)) { pickTo = pickFrom; pickFrom = clicked; } else { pickTo = clicked; }
          }
          renderCalendar();
        });
        grid.appendChild(btn);
      }
    }

    monthSelect.addEventListener('change', function(){ viewMonth = parseInt(this.value,10); renderCalendar(); });
    yearSelect.addEventListener('change', function(){ viewYear = parseInt(this.value,10); renderCalendar(); });

    function positionPanel(){
      const panel = calendarPanel;
      const btnRect = calendarBtn.getBoundingClientRect();
      const panelRect = panel.getBoundingClientRect();
      const viewportW = Math.max(document.documentElement.clientWidth, window.innerWidth || 0);
      const viewportH = Math.max(document.documentElement.clientHeight, window.innerHeight || 0);
      const gap = 8;

      let placedLeft = btnRect.right - panelRect.width;
      if (placedLeft < gap) placedLeft = gap;
      if (placedLeft + panelRect.width > viewportW - gap) placedLeft = Math.max(gap, viewportW - gap - panelRect.width);

      let placedTop = btnRect.bottom + gap;
      if (placedTop + panelRect.height > viewportH - gap) {
        placedTop = btnRect.top - panelRect.height - gap;
        if (placedTop < gap) placedTop = gap;
      }

      const parentRect = panel.offsetParent ? panel.offsetParent.getBoundingClientRect() : { left: 0, top: 0 };
      panel.style.left = Math.round(placedLeft - parentRect.left) + 'px';
      panel.style.top = Math.round(placedTop - parentRect.top) + 'px';
    }

    confirmBtn.addEventListener('click', function(){
      let from = $('notifDateFrom'), to = $('notifDateTo');
      if (!from) { from = document.createElement('input'); from.type='hidden'; from.id='notifDateFrom'; document.body.appendChild(from); }
      if (!to) { to = document.createElement('input'); to.type='hidden'; to.id='notifDateTo'; document.body.appendChild(to); }
      if (!pickFrom) { from.value = ''; to.value = ''; }
      else if (pickFrom && !pickTo) {
        const y = pickFrom.getFullYear(), m = String(pickFrom.getMonth()+1).padStart(2,'0'), d = String(pickFrom.getDate()).padStart(2,'0');
        from.value = `${y}-${m}-${d}`; to.value = `${y}-${m}-${d}`;
      } else {
        const a = pickFrom, b = pickTo;
        const ay = a.getFullYear(), am = String(a.getMonth()+1).padStart(2,'0'), ad = String(a.getDate()).padStart(2,'0');
        const by = b.getFullYear(), bm = String(b.getMonth()+1).padStart(2,'0'), bd = String(b.getDate()).padStart(2,'0');
        from.value = `${ay}-${am}-${ad}`; to.value = `${by}-${bm}-${bd}`;
      }
      // ensure change listeners on the created inputs so filtering reacts immediately
      attachDateInputs();
      if (typeof window.onDateChange === 'function') window.onDateChange();
      calendarPanel.style.display = 'none';
    });

    clearBtn.addEventListener('click', function(e){
      e.stopPropagation();
      pickFrom = null; pickTo = null;
      const f = $('notifDateFrom'), t = $('notifDateTo'); if (f) f.value=''; if (t) t.value='';
      renderCalendar();
      if (typeof window.onDateChange === 'function') window.onDateChange();
    });

    calendarBtn.addEventListener('click', function(e){
      e.stopPropagation();
      if (calendarPanel.style.display === 'block') { calendarPanel.style.display = 'none'; return; }
      calendarPanel.style.display = 'block';
      requestAnimationFrame(positionPanel);
    });

    calendarPanel.addEventListener('click', e => e.stopPropagation());
    document.addEventListener('click', () => { if (calendarPanel.style.display === 'block') calendarPanel.style.display = 'none'; });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') calendarPanel.style.display = 'none'; });
    window.addEventListener('resize', function(){ if (calendarPanel.style.display === 'block') positionPanel(); });

    populateMonthYear();
    renderCalendar();
  })();

  // batch select (delete + mark-read)
  (function batchSelect(){
     const deleteSelBtn = $('notifDeleteSelectedBtn');
     const markReadBtn = $('notifMarkReadSelectedBtn');
     const container = $('notifListContainer');
     if (!deleteSelBtn || !container) return;

     (function bindIconClicks(){
       const bind = (btn) => {
         if (!btn) return;
         const icon = btn.querySelector('i');
         if (!icon) return;
         icon.addEventListener('click', (ev) => { ev.stopPropagation(); btn.click(); });
       };
      bind(deleteSelBtn); bind(markReadBtn); bind($('notifCalendarBtn'));
     })();

     if (markReadBtn) {
       markReadBtn.addEventListener('click', function(e){
         e.stopPropagation();
         const selected = container.querySelectorAll('.notif-list-item.notif-selected');
         if (selected.length > 0) {
           // existing behavior: mark only explicitly selected rows
           selected.forEach(r => {
             const id = r.dataset.id;
             if (!id) return;
             const it = all.find(x => x.id === id);
             const readKey = it ? it.readKey : ('notif_read_' + id);
             try { localStorage.setItem(readKey, '1'); } catch(err){}
            const dot = r.querySelector('.notif-dot'); if (dot) dot.classList.add('read');
            r.classList.remove('notif-new');
            r.classList.add('notif-read');
           });
         } else {
           // new: no explicit selection -> mark all notifications matching current filter & date range
           let fromDate = null, toDate = null;
           try {
             const d = getDateInputs();
             if (d.from && d.from.value) fromDate = parseDate(d.from.value + 'T00:00:00');
             if (d.to && d.to.value) toDate = parseDate(d.to.value + 'T23:59:59');
           } catch(e){ fromDate = null; toDate = null; }

           all.forEach(it => {
             try {
               // skip deleted
               if (safeGet('notif_deleted_' + it.id) === '1') return;
               // date filter
               if (fromDate || toDate) {
                 if (!it.time) return;
                 const dt = parseDate(it.time);
                 if (!dt) return;
                 if (fromDate && dt < fromDate) return;
                 if (toDate && dt > toDate) return;
               }
               // archived handling
               const isArchived = safeGet('notif_archived_' + it.id) === '1';
               if (currentFilter === 'archive') {
                 if (!isArchived) return;
               } else {
                 if (isArchived) return;
               }
               // kind/favorite filters
               if (currentFilter === 'requests' && it.kind !== 'request') return;
               if (currentFilter === 'users' && it.kind !== 'user') return;
               if (currentFilter === 'favorite' && safeGet('notif_fav_' + it.id) !== '1') return;

               // mark as read
               if (it.readKey) {
                 try { localStorage.setItem(it.readKey, '1'); } catch(err){}
               } else {
                 try { localStorage.setItem('notif_read_' + it.id, '1'); } catch(err){}
               }
             } catch(err){}
           });
         }

        updateCounts();
        renderList(currentFilter, $('notifSearch') ? $('notifSearch').value : '');
      });
     }

     container.addEventListener('click', function(){
       updateDeleteBtnState();
     });

     deleteSelBtn.addEventListener('click', function(e){
       e.stopPropagation();
       // 1) archive-delete all archived items when in archive + selectAllGlobal
       if (currentFilter === 'archive' && selectAllGlobal) {
         // ask confirmation before deleting everything in archive
         showDeleteConfirm('Are you sure you want to delete all archived notifications?', 'This will permanently remove all archived notifications.', function(){
           all.forEach(it => {
             try {
               if (safeGet('notif_archived_' + it.id) === '1' && safeGet('notif_deleted_' + it.id) !== '1') {
                 localStorage.setItem('notif_deleted_' + it.id, '1');
                 localStorage.removeItem('notif_archived_' + it.id);
                 localStorage.removeItem('notif_fav_' + it.id);
                 if (it.readKey) localStorage.removeItem(it.readKey);
               }
             } catch(err){}
           });
           selectAllGlobal = false;
           updateCounts();
           renderList(currentFilter, $('notifSearch') ? $('notifSearch').value : '');
         });
       } else {
         // 2) prefer explicit selection
         const selected = container.querySelectorAll('.notif-list-item.notif-selected');
         if (selected.length > 0) {
           selected.forEach(r => {
             const id = r.dataset.id;
             if (!id) return;
             if (currentFilter === 'archive') {
               try {
                 localStorage.setItem('notif_deleted_' + id, '1');
                 localStorage.removeItem('notif_archived_' + id);
                 localStorage.removeItem('notif_fav_' + id);
                 const it = all.find(x => x.id === id);
                 if (it && it.readKey) localStorage.removeItem(it.readKey);
               } catch(err){}
             } else {
               try { localStorage.setItem('notif_archived_' + id, '1'); } catch(err){}
             }
           });
           updateCounts();
           renderList(currentFilter, $('notifSearch') ? $('notifSearch').value : '');
         } else {
           // 3) no explicit selection -> ask confirmation then act on all matching notifications
           showDeleteConfirm('Are you sure you want to delete all notifications?', '', function(){
             let fromDate = null, toDate = null;
             try {
               const d = getDateInputs();
               if (d.from && d.from.value) fromDate = parseDate(d.from.value + 'T00:00:00');
               if (d.to && d.to.value) toDate = parseDate(d.to.value + 'T23:59:59');
             } catch(e){ fromDate = null; toDate = null; }
 
            all.forEach(it => {
              try {
                // skip already deleted
                if (safeGet('notif_deleted_' + it.id) === '1') return;
                // date filter
                if (fromDate || toDate) {
                  if (!it.time) return;
                  const dt = parseDate(it.time);
                  if (!dt) return;
                  if (fromDate && dt < fromDate) return;
                  if (toDate && dt > toDate) return;
                }
                // archive filter handling
                const isArchived = safeGet('notif_archived_' + it.id) === '1';
                if (currentFilter === 'archive') {
                  if (!isArchived) return;
                  // when in archive and delete-all, permanently delete
                  if (safeGet('notif_deleted_' + it.id) !== '1') {
                    localStorage.setItem('notif_deleted_' + it.id, '1');
                    localStorage.removeItem('notif_archived_' + it.id);
                    localStorage.removeItem('notif_fav_' + it.id);
                    if (it.readKey) localStorage.removeItem(it.readKey);
                  }
                } else {
                  // for other filters, ensure item matches filter kind / favorite
                  if (currentFilter === 'requests' && it.kind !== 'request') return;
                  if (currentFilter === 'users' && it.kind !== 'user') return;
                  if (currentFilter === 'favorite' && safeGet('notif_fav_' + it.id) !== '1') return;
                  // archive (soft-delete) the item
                  localStorage.setItem('notif_archived_' + it.id, '1');
                }
              } catch(err){}
            });
            updateCounts();
            renderList(currentFilter, $('notifSearch') ? $('notifSearch').value : '');
           });
         }
       }
     });
   })();

  // init
  updateCounts();
  renderList('all','');

  // storage sync
  window.addEventListener('storage', function(e){
    try {
      updateCounts();
      renderList(currentFilter, $('notifSearch') ? $('notifSearch').value : '');
    } catch(err){}
  });
})();
  </script>
<!-- inject canonical localStorage.systemNotifs so header can compute unread reliably -->
<script>
(function(){
  try {
    var combined = [];

    // server arrays (these are the same vars used by the tab's JS)
    (window.systemNotifs || []).forEach(function(n){
      combined.push({ id: 'sys_' + (n.id || ''), name: n.name || '', created_at: n.created_at || '' });
    });
    (window.newRequestNotifs || []).forEach(function(n){
      combined.push({ id: 'nreq_' + (n.id || ''), name: n.name || '', created_at: n.created_at || '' });
    });
    (window.newUserNotifs || []).forEach(function(n){
      combined.push({ id: 'usr_' + (n.id || ''), name: n.name || '', created_at: n.created_at || '' });
    });

    // store for header script to read; header expects localStorage.systemNotifs to be a JSON array
    try { localStorage.setItem('systemNotifs', JSON.stringify(combined)); } catch(e){ /* ignore quota/disabled storage */ }

    // immediately ask header to update the bell on this same page (updateNotifBellCount was exposed by header.php)
    if (window.updateNotifBellCount && typeof window.updateNotifBellCount === 'function') {
      try { window.updateNotifBellCount(); } catch(e) {}
    }
  } catch(e){}
})();
</script>
</div></div>
