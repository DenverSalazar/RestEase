<?php
// ...expects $conn ready from Settings.php...
?>
<div class="settings-card" id="notificationTab" style="display:none;">
  <div style="font-size: 1.13rem; font-weight: 600; color: #222;">Notification</div>
  <div style="color: #888; font-size: 0.97rem; margin-bottom: 18px;">Notification settings and preferences will be shown here.</div>

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
        </div>
      </div>
      <div style="display:flex;align-items:center;gap:12px;">
        <div style="position:relative;">
          <i class="fas fa-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#999;"></i>
          <input id="notifSearch" type="text" placeholder="Search by name or message" style="padding:10px 14px 10px 36px;border:1px solid #e3e7ed;border-radius:20px;min-width:240px;">
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
    .notif-left { display:flex;align-items:center;gap:8px;min-width:72px; }
    .notif-dot { width:10px;height:10px;border-radius:50%;background:#b6dca6;display:inline-block;box-shadow:0 1px 2px rgba(0,0,0,0.06);cursor:pointer; }
    .notif-dot.read { background:transparent;border:1px solid #e6e9ec; }
    .notif-icon{ width:36px;height:36px;border-radius:6px;background:#f5f7fa;display:inline-flex;align-items:center;justify-content:center;color:#2d72d9;font-weight:700;cursor:pointer; }
    .notif-star-left { background: transparent;border: none;padding: 0;margin: 0;display: inline-flex;align-items: center;justify-content: center;cursor: pointer;color: #bfc6cc;font-size: 1.15rem; }
    .notif-star-left[aria-pressed="true"] { color: #f0b400; }
    .notif-main { flex:1; min-width:0; }
    .notif-title { font-weight:600;color:#222;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:flex;align-items:center;gap:8px; }
    .notif-body { color:#666;font-size:0.95rem;margin-top:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
    .notif-meta { text-align:right;min-width:110px;color:#9aa3ad;font-size:0.9rem; }
    .notif-actions { display:flex;align-items:center;gap:8px; }
    .notif-delete { background:#ff6b6b;border:none;color:#fff;padding:8px;border-radius:8px;cursor:pointer;width:36px;height:36px;display:inline-flex;align-items:center;justify-content:center; }
    .tab-count { display:inline-block;background:#e9eef8;color:#2d72d9;padding:3px 8px;border-radius:999px;font-weight:700;margin-right:8px;font-size:0.95rem; }
    .notif-list-tab { background:transparent;border:none;padding:8px 10px;border-radius:8px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;font-weight:600;color:#444; }
    .notif-list-tab.active { background:#fff;border:1px solid #e3e7ed;box-shadow:0 1px 4px rgba(0,0,0,0.03);color:#2d72d9; }
    .notif-selected { background: linear-gradient(90deg, rgba(45,114,217,0.03), rgba(45,114,217,0.02)); border: 1px solid rgba(45,114,217,0.06); box-shadow: 0 2px 10px rgba(45,114,217,0.03); }
  </style>

  <script>
  (function(){
    // read server-provided arrays (fallback safe empty arrays)
    const systemNotifs = window.systemNotifs || [];
    const newUserNotifs = window.newUserNotifs || [];
    const newRequestNotifs = window.newRequestNotifs || [];

    // unify lists into "all" and keep types
    const all = [];
    systemNotifs.forEach(n => all.push({
      id: 'sys_'+(n.id||Math.random()),
      kind: 'request',
      title: 'New client request',
      name: n.name || '',
      message: 'New client request received from ' + (n.name || ''),
      time: n.created_at || '',
      readKey: 'notif_read_sys_'+(n.id||'')
    }));
    newRequestNotifs.forEach(n => all.push({
      id: 'nreq_'+(n.id||Math.random()),
      kind: 'request',
      title: 'New client request',
      name: n.name || '',
      message: 'New client request received from ' + (n.name || ''),
      time: n.created_at || '',
      readKey: 'notif_read_nreq_'+(n.id||'')
    }));
    newUserNotifs.forEach(u => all.push({
      id: 'usr_'+(u.id||Math.random()),
      kind: 'user',
      title: 'New user registered',
      name: u.name || '',
      message: 'New user registered: ' + (u.name||'') + (u.email ? ' ('+u.email+')' : ''),
      time: u.created_at || '',
      readKey: 'notif_read_usr_'+(u.id||'')
    }));

    // sort by time if present
    all.sort((a,b)=>{
      if (a.time && b.time) return new Date(b.time) - new Date(a.time);
      return 0;
    });

    const PAGE_SIZE = 10;
    let currentPage = 1;
    let showAll = false;
    let currentFilter = 'all';

    function updateCounts() {
      document.getElementById('tabAllCount').textContent = all.length;
      document.getElementById('tabReqCount').textContent = all.filter(i=>i.kind==='request').length;
      document.getElementById('tabUserCount').textContent = all.filter(i=>i.kind==='user').length;
    }

    function renderList(filter, query) {
      const container = document.getElementById('notifListContainer');
      container.innerHTML = '';
      const q = (query||'').toLowerCase();
      let items = all.filter(item=>{
        if (filter === 'requests' && item.kind !== 'request') return false;
        if (filter === 'users' && item.kind !== 'user') return false;
        if (!q) return true;
        return (item.name||'').toLowerCase().includes(q) || (item.message||'').toLowerCase().includes(q);
      });

      const total = items.length;
      const totalPages = Math.max(1, Math.ceil(total / PAGE_SIZE));
      if (currentPage > totalPages) currentPage = 1;
      const itemsToRender = showAll ? items : items.slice((currentPage-1)*PAGE_SIZE, (currentPage-1)*PAGE_SIZE + PAGE_SIZE);

      if (itemsToRender.length === 0) {
        const empty = document.createElement('div');
        empty.style.color = '#888';
        empty.style.textAlign = 'center';
        empty.style.padding = '28px';
        empty.textContent = 'No notifications.';
        container.appendChild(empty);
        appendFooter(container, total, totalPages);
        return;
      }

      itemsToRender.forEach(item=>{
        const isRead = localStorage.getItem(item.readKey) === '1';
        const isFav = localStorage.getItem('notif_fav_' + item.id) === '1';
        const row = document.createElement('div');
        row.className = 'notif-list-item';
        row.innerHTML = `
          <div class="notif-left">
            <span class="notif-dot ${isRead ? 'read' : ''}" title="${isRead ? 'Read' : 'Unread'}"></span>
            <div class="notif-icon" title="Mark as read"><i class="fas fa-envelope"></i></div>
            <button class="notif-star-left" title="Favorite" aria-pressed="${isFav ? 'true' : 'false'}"><i class="fas fa-star"></i></button>
          </div>
          <div class="notif-main">
            <div class="notif-title"><span style="font-weight:${isRead ? '400' : '700'}">${item.title}${item.name ? ' — ' + item.name : ''}</span></div>
            <div class="notif-body">${item.message}</div>
          </div>
          <div class="notif-meta"><div>${item.time ? (new Date(item.time)).toLocaleString() : 'Just Now'}</div></div>
          <div class="notif-actions"><button class="notif-delete" title="Delete"><i class="fas fa-trash"></i></button></div>
        `;

        // star toggle
        const starBtn = row.querySelector('.notif-star-left');
        starBtn.style.color = isFav ? '#f0b400' : '#bfc6cc';
        starBtn.addEventListener('click', function(ev){
          ev.stopPropagation();
          const key = 'notif_fav_' + item.id;
          const currentlyFav = localStorage.getItem(key) === '1';
          if (currentlyFav) { localStorage.removeItem(key); starBtn.style.color = '#bfc6cc'; starBtn.setAttribute('aria-pressed','false'); }
          else { localStorage.setItem(key,'1'); starBtn.style.color = '#f0b400'; starBtn.setAttribute('aria-pressed','true'); }
          updateCounts();
          if (currentFilter === 'favorite') renderList(currentFilter, document.getElementById('notifSearch').value);
        });

        // dot click toggles highlight only
        const dotBtn = row.querySelector('.notif-dot');
        dotBtn.addEventListener('click', function(ev){
          ev.stopPropagation();
          row.classList.toggle('notif-selected');
        });

        // icon click marks as read
        const iconBtn = row.querySelector('.notif-icon');
        iconBtn.addEventListener('click', function(ev){
          ev.stopPropagation();
          localStorage.setItem(item.readKey, '1');
          dotBtn.classList.add('read');
          row.classList.remove('notif-selected');
          const titleSpan = row.querySelector('.notif-title span');
          if (titleSpan) titleSpan.style.fontWeight = '400';
          updateCounts();
        });

        // row click navigates or marks as read
        row.addEventListener('click', function(e){
          if (e.target.closest('.notif-delete') || e.target.closest('.notif-star-left') || e.target.closest('.notif-dot') || e.target.closest('.notif-icon')) return;
          localStorage.setItem(item.readKey, '1');
          const dot = row.querySelector('.notif-dot');
          if (dot) dot.classList.add('read');
          const titleSpan = row.querySelector('.notif-title span');
          if (titleSpan) titleSpan.style.fontWeight = '400';
          if (item.kind === 'request') window.location.href = 'ClientsRequest.php';
        });

        // delete
        row.querySelector('.notif-delete').addEventListener('click', function(ev){
          ev.stopPropagation();
          const idx = all.findIndex(a=>a.id === item.id);
          if (idx > -1) {
            all.splice(idx,1);
            const newTotal = Math.max(0, total - 1);
            const newTotalPages = Math.max(1, Math.ceil(newTotal / PAGE_SIZE));
            if (currentPage > newTotalPages) currentPage = newTotalPages;
            renderList(filter, query);
            updateCounts();
          }
        });

        container.appendChild(row);
      });

      appendFooter(container, total, totalPages);
    }

    // footer
    function appendFooter(container, total, totalPages) {
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

      function createPageBtn(label, disabled, onClick, isActive) {
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
        btn.addEventListener('click', function(ev){ ev.stopPropagation(); if (!disabled) onClick(); });
        return btn;
      }

      const prevBtn = createPageBtn('‹', showAll || currentPage <= 1, function(){ if (currentPage>1){ currentPage--; renderList(currentFilter, document.getElementById('notifSearch').value);} });
      center.appendChild(prevBtn);

      if (!showAll) {
        let maxButtons = 5;
        let start = Math.max(1, currentPage - Math.floor(maxButtons/2));
        let end = Math.min(totalPages, start + maxButtons - 1);
        start = Math.max(1, end - maxButtons + 1);
        for (let p = start; p <= end; p++) {
          center.appendChild(createPageBtn(p, false, (function(page){ return function(){ currentPage = page; renderList(currentFilter, document.getElementById('notifSearch').value); }; })(p), (p === currentPage)));
        }
      } else {
        center.appendChild(createPageBtn('1', true, function(){}, true));
      }

      const nextBtn = createPageBtn('›', showAll || currentPage >= totalPages, function(){ if (currentPage < totalPages) { currentPage++; renderList(currentFilter, document.getElementById('notifSearch').value); } });
      center.appendChild(nextBtn);

      footer.appendChild(center);

      const right = document.createElement('div'); right.style.flex = '0 0 120px';
      footer.appendChild(right);

      container.appendChild(footer);
    }

    // tab behavior
    document.querySelectorAll('.notif-list-tab').forEach(btn=>{
      btn.addEventListener('click', function(){
        document.querySelectorAll('.notif-list-tab').forEach(b=>b.classList.remove('active'));
        this.classList.add('active');
        currentFilter = this.dataset.filter;
        showAll = false;
        currentPage = 1;
        renderList(currentFilter, document.getElementById('notifSearch').value);
      });
    });

    // search
    document.getElementById('notifSearch').addEventListener('input', function(){
      showAll = false;
      currentPage = 1;
      renderList(currentFilter, this.value);
    });

    // init
    updateCounts();
    renderList('all','');
  })();
  </script>
</div>
