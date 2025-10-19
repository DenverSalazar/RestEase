<?php
session_start();
include_once '../Includes/db.php';

$user_id = $_SESSION['user_id'] ?? null;
$notifications = [];
if ($user_id) {
    // Welcome notification (persist to DB if first day)
    $stmt = $conn->prepare("SELECT created_at FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    // ...changed: use get_result() and free it so no commands out-of-sync...
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $created_at = $row['created_at'];
        $account_created = date('Y-m-d', strtotime($created_at));
        $today = date('Y-m-d');
        if ($account_created === $today) {
            // check if a welcome notification already exists for this user
            $msg = 'Welcome to RestEase!';
            $chk = $conn->prepare("SELECT id FROM notifications WHERE user_id = ? AND message = ? LIMIT 1");
            $chk->bind_param("is", $user_id, $msg);
            $chk->execute();
            $chk->store_result();
            if ($chk->num_rows === 0) {
                $ins = $conn->prepare("INSERT INTO notifications (user_id, message, link, is_read, created_at) VALUES (?, ?, '', 0, ?)");
                $ins->bind_param("iss", $user_id, $msg, $created_at);
                $ins->execute();
                $ins->close();
            }
            $chk->close();
        }
    }
    $result->free();
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

    // Assessment & persisted notifications (including welcome)
    $stmt = $conn->prepare("SELECT id, message, link, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        // if this is the welcome message, mark status as 'welcome' so UI uses the same styling
        $status = (trim($row['message']) === 'Welcome to RestEase!') ? 'welcome' : 'assessment';
        $notifications[] = [
            'id' => $row['id'],
            'status' => $status,
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
:root{--accent:#2d72d9;--muted:#888;--card-border:#eef2f5;--card-bg:#f2f4f6;}
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
.notif-dot{width:10px;height:10px;border-radius:50%;background:#b6dca6;display:inline-block;box-shadow:0 1px 2px rgba(0,0,0,0.06);}
.notif-star-left{background:transparent;border:none;padding:0;margin:0 4px 0 0;cursor:pointer;color:#bfc6cc;font-size:1.05rem;transition:color 180ms}
.notif-star-left[aria-pressed="true"]{color:#f0b400}
.notif-icon{width:44px;height:44px;border-radius:8px;display:flex;align-items:center;justify-content:center;background:#fff;border:1px solid #f0f4f7;color:#4b7bec;font-size:16px}
.notif-main{flex:1;min-width:0}
.notif-title{font-weight:700;font-size:1.05rem;margin-bottom:6px;color:#222}
.notif-desc{color:#6f767d;font-size:0.95rem}
.notif-time{color:var(--muted);font-size:0.92rem;white-space:nowrap;margin-left:12px}
.notif-actions{display:flex;align-items:center;gap:8px;margin-left:12px;}
/* right delete button styled like the screenshot */
.notif-delete {
  background:#ff6b6b;
  color:#fff;
  border: none;
  width:40px;
  height:40px;
  border-radius:10px;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  cursor:pointer;
  box-shadow:none;
  transition:background .12s, transform .06s;
}
.notif-delete:hover{background:#ff4b4b;transform:translateY(-1px);}

/* unread card subtle background */
.notif-card-wrapper.unread{background:#f3f5f6}

/* responsive */
@media (max-width:760px){
  .notif-left{min-width:70px}
  .notif-time{display:none}
  .search-input-wrapper{max-width:260px}
}
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
      <div class="controls">
        <button id="notifSelectAllBtn" class="icon-btn" title="Select all"><i class="far fa-square"></i></button>
        <button id="main-delete-btn" class="icon-btn" title="Delete Selected"><i class="fas fa-trash-alt"></i></button>
        <button id="delete-all-btn" class="icon-btn warn" title="Delete All" style="display:none"><i class="fas fa-trash"></i></button>
      </div>
    </div>
  </div>

  <?php if ($user_id && count($notifications) > 0): ?>
    <ul class="notif-list" id="notifications-list">
      <?php foreach ($notifications as $notif): 
        $borderColor = ($notif['status'] === 'accepted') ? '#198754' : (($notif['status'] === 'denied') ? '#DC3545' : (($notif['status'] === 'welcome') ? '#4B7BEC' : '#FFC107'));
        $bgColor = ($notif['status'] === 'accepted') ? '#E9F7EF' : (($notif['status'] === 'denied') ? '#FDEDEC' : (($notif['status'] === 'welcome') ? '#EAF1FF' : '#FFF8E1'));
        $icon = ($notif['status'] === 'accepted') ? 'fa-check-circle' : (($notif['status'] === 'denied') ? 'fa-times-circle' : (($notif['status'] === 'welcome') ? 'fa-smile-beam' : 'fa-file-invoice-dollar'));
        $iconColor = ($notif['status'] === 'accepted') ? '#198754' : (($notif['status'] === 'denied') ? '#DC3545' : (($notif['status'] === 'welcome') ? '#4B7BEC' : '#FFC107'));
      ?>
      <li class="notif-card-wrapper unread" data-id="<?php echo isset($notif['id']) ? htmlspecialchars($notif['id']) : ''; ?>" data-status="<?php echo htmlspecialchars($notif['status']); ?>" data-created_at="<?php echo htmlspecialchars($notif['created_at']); ?>" style="background:<?php echo $bgColor; ?>;border-left:8px solid <?php echo $borderColor; ?>;">
        <input type="checkbox" class="notif-checkbox" />
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
            <a href="notification_details.php?id=<?php echo isset($notif['id']) ? urlencode($notif['id']) : ''; ?>&type=<?php echo urlencode($notif['status']); ?>" title="View Details">
              <i class="fas fa-arrow-right" style="font-size:1.1rem;color:#4B7BEC"></i>
            </a>
          <?php elseif ($notif['status'] === 'assessment'): ?>
            <a href="notification_details.php?type=assessment&created_at=<?php echo urlencode($notif['created_at']); ?>" title="View Assessment Details">
              <i class="fas fa-arrow-right" style="font-size:1.1rem;color:#f39c12"></i>
            </a>
          <?php endif; ?>
          <!-- per-item delete (red pill) -->
          <button class="notif-delete" title="Delete notification"><i class="fas fa-trash"></i></button>
        </div>
      </li>
      <?php endforeach; ?>
    </ul>
  <?php else: ?>
    <div style="text-align:center;padding:64px 8px;color:#888;">
      No notifications available yet.<br>Please contact the administrator or check back later.
    </div>
  <?php endif; ?>
</div>

<footer><?php include '../Includes/footer-client.php'; ?></footer>

<script>
(function(){
  const $ = sel => document.querySelector(sel);
  const $$ = sel => Array.from(document.querySelectorAll(sel));

  const tabs = $$('#notif-tabs .notif-tab');
  const searchInput = $('#notifSearch');
  const selectAllBtn = $('#notifSelectAllBtn');
  const mainDeleteBtn = $('#main-delete-btn');
  const deleteAllBtn = $('#delete-all-btn');

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

  function applyFilter(){
    const active = $('#notif-tabs .notif-tab.active').getAttribute('data-filter');
    const q = (searchInput.value || '').trim().toLowerCase();
    $$('.notif-card-wrapper').forEach(card=>{
      const id = card.getAttribute('data-id') || '';
      // deleted hidden
      if (id && localStorage.getItem('notif_deleted_' + id) === '1'){ card.style.display = 'none'; return; }

      let show = true;
      if (active === 'favorite') {
        show = id && localStorage.getItem('notif_fav_' + id) === '1';
      } else if (active === 'archive') {
        show = id && localStorage.getItem('notif_archived_' + id) === '1';
      } else {
        show = true;
      }

      if (q) {
        const title = (card.querySelector('.notif-title')||{textContent:''}).textContent.toLowerCase();
        const desc = (card.querySelector('.notif-desc')||{textContent:''}).textContent.toLowerCase();
        show = show && (title.includes(q) || desc.includes(q));
      }
      card.style.display = show ? '' : 'none';
    });
    updateCounts();
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

  // deletion mode: show checkboxes and enable delete actions
  let deletionMode = false;
  function setDeletionMode(on){
    deletionMode = on;
    $$('.notif-checkbox').forEach(cb=> cb.style.display = on ? 'inline-block' : 'none');
    deleteAllBtn.style.display = on ? '' : 'none';
    // reset checkboxes off when turning off
    if (!on) $$('.notif-checkbox').forEach(cb=> cb.checked = false);
  }

  mainDeleteBtn.addEventListener('click', function(){
    setDeletionMode(true);
  });

  // select all visible
  selectAllBtn.addEventListener('click', function(){
    const pressed = this.getAttribute('aria-pressed') === 'true';
    const visibleCards = $$('.notif-card-wrapper').filter(c=> c.style.display !== 'none');
    if (!pressed){
      visibleCards.forEach(c=>{
        const cb = c.querySelector('.notif-checkbox');
        if (cb){ cb.style.display = 'inline-block'; cb.checked = true; }
      });
      this.querySelector('i').className = 'far fa-check-square';
      this.setAttribute('aria-pressed','true');
    } else {
      visibleCards.forEach(c=>{
        const cb = c.querySelector('.notif-checkbox');
        if (cb) cb.checked = false;
      });
      this.querySelector('i').className = 'far fa-square';
      this.setAttribute('aria-pressed','false');
    }
  });

  // delete selected (POST) — server endpoint expected to handle action 'delete_selected'
  document.getElementById('main-delete-btn').addEventListener('dblclick', function(){ /* noop to avoid accidental dblclick */ });
  document.getElementById('main-delete-btn').addEventListener('contextmenu', e=>e.preventDefault());

  // Attach single-click delete confirmation when in deletion mode and checkboxes are used
  document.getElementById('main-delete-btn').addEventListener('click', function(){
    const selected = Array.from($$('.notif-checkbox')).filter(cb => cb.checked);
    if (selected.length === 0){
      // If not in deletion mode, open deletion mode instead
      setDeletionMode(true);
      return;
    }
    if (!confirm('Delete selected notifications?')) return;
    const notifications = selected.map(cb => ({
      status: cb.closest('.notif-card-wrapper').getAttribute('data-status') || '',
      id: cb.closest('.notif-card-wrapper').getAttribute('data-id') || '',
      created_at: cb.closest('.notif-card-wrapper').getAttribute('data-created_at') || ''
    }));
    fetch('delete_notification.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ action: 'delete_selected', notifications })
    }).then(r => r.json()).then(data=>{
      if (data && data.success){
        selected.forEach(cb => cb.closest('.notif-card-wrapper').remove());
        setDeletionMode(false);
        updateCounts();
      } else alert('Failed to delete selected notifications.');
    }).catch(()=> alert('Failed to delete selected notifications.'));
  });

  // delete all in archive (POST)
  deleteAllBtn.addEventListener('click', function(){
    if (!confirm('Delete ALL notifications in Archive?')) return;
    fetch('delete_notification.php', {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ action: 'delete_all' })
    }).then(r=>r.json()).then(data=>{
      if (data && data.success){
        // remove archived cards from DOM
        $$('.notif-card-wrapper').forEach(card=>{
          const id = card.getAttribute('data-id');
          if (id && localStorage.getItem('notif_archived_' + id) === '1') {
            try{ localStorage.setItem('notif_deleted_' + id, '1'); localStorage.removeItem('notif_archived_' + id); localStorage.removeItem('notif_fav_' + id); } catch(e){}
            card.remove();
          }
        });
        setDeletionMode(false);
        updateCounts();
        applyFilter();
      } else alert('Failed to delete all notifications.');
    }).catch(()=> alert('Failed to delete all notifications.'));
  });

  // initialize UI
  initStars();
  updateCounts();
  applyFilter();

  // per-card delete button (sends POST 'delete_single' then removes card)
  $$('.notif-delete').forEach(btn=>{
    btn.addEventListener('click', function(e){
      e.stopPropagation();
      const card = this.closest('.notif-card-wrapper');
      const id = card.getAttribute('data-id');
      if (!confirm('Delete this notification?')) return;
      // attempt server call (endpoint expected to accept action 'delete_single')
      fetch('delete_notification.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ action: 'delete_single', id: id })
      }).then(r=>r.json()).then(data=>{
        // we handle success/failure gracefully; remove card on success
        if (data && data.success) {
          try { localStorage.setItem('notif_deleted_' + id, '1'); } catch(e){}
          card.remove();
          updateCounts();
        } else {
          // fallback: remove locally
          try { localStorage.setItem('notif_deleted_' + id, '1'); } catch(e){}
          card.remove();
          updateCounts();
        }
      }).catch(()=>{
        try { localStorage.setItem('notif_deleted_' + id, '1'); } catch(e){}
        card.remove();
        updateCounts();
      });
    });
  });

  // Live storage sync
  window.addEventListener('storage', function(){ updateCounts(); applyFilter(); });

})();
</script>
</body>
</html>

