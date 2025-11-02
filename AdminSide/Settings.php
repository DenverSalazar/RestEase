<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}
include_once '../Includes/db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>RestEase Settings</title>
  <link rel="icon" type="image/png" href="../assets/re logo blue.png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/Settings.css">
  <link rel="stylesheet" href="../css/sidebar.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
  <style>
#archive-clients-table_filter {
  display: none !important;
}
.clients-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0 4px;
  font-size: 0.97rem;
  background: #fff;
  border-radius: 8px;
  overflow: hidden;
}
.clients-table th, .clients-table td {
  padding: 8px 10px;
  border-bottom: 1px solid #e3e7ed;
  text-align: left;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.clients-table th {
  background: #f5f7fa;
  color: #2d3a4a;
  font-weight: 600;
  font-size: 0.85rem;
}
.clients-table tr:last-child td {
  border-bottom: none;
}
.status-badge.status-denied {
  background: #f8d7da;
  color: #c0392b;
  padding: 4px 14px;
  border-radius: 6px;
  font-size: 0.95em;
  font-weight: 600;
  display: inline-block;
}
.view-btn {
  background: #94b2cc;
  color: #fff;
  border: none;
  border-radius: 7px;
  padding: 6px 20px;
  font-size: 1rem;
  font-weight: 400;
  cursor: pointer;
   transition: background 0.2s, box-shadow 0.2s;
  box-shadow: none;
  outline: none;
  letter-spacing: 0.5px;
  display: inline-block;
}
.view-btn:hover {
  background: #7fa0bb;
  color: #fff;
}
.avatar-img {
  width: 38px !important;
  height: 38px !important;
  border-radius: 50% !important;
  font-weight: 600;
  font-size: 1.1em;
  object-fit: cover;
  box-shadow: none;
  padding: 0 !important;
  margin: 0 !important;
  text-align: center;
  line-height: 38px !important;
  display: inline-block !important;
  vertical-align: middle;
}
.avatar-img img {
  width: 38px !important;
  height: 38px !important;
  border-radius: 50% !important;
  object-fit: cover;
  display: block;
}
.avatar-color-1 { background: #6c8ebf !important; }
.avatar-color-2 { background: #e67e22 !important; }
.avatar-color-3 { background: #2ecc71 !important; }
.avatar-color-4 { background: #e74c3c !important; }
.avatar-color-5 { background: #9b59b6 !important; }
.avatar-color-6 { background: #f1c40f !important; }
.avatar-color-7 { background: #34495e !important; }
.avatar-color-8 { background: #16a085 !important; }
.avatar-color-9 { background: #d35400 !important; }
.avatar-color-10 { background: #2980b9 !important; }
  </style>
</head>
<body>
   <!-- Sidebar -->
   <?php include '../Includes/sidebar.php'; ?>

  <!-- Main Content -->
  <main class="main-content">
    <div class="cemetery-masterlist-container" style="margin-left: -50px; margin-top: 0px; padding: 0 32px; font-family: 'Inter', sans-serif;">
      <!-- Header -->
      <header class="header" style="margin-bottom: 0;">
        <h1 style="margin: 0 0 6px 0;">Settings</h1>
      </header>
      <div style="color: #888; font-size: 1rem; margin-bottom: 18px;">
          Manage your account and preferences
        </div>
      <!-- Settings Section -->
      <section class="settings-section" style="margin-top: 0; padding: 0;">
        <div class="settings-tabs">
          <div class="settings-tab active" data-tab="account">Account</div>
          <div class="settings-tab" data-tab="archive">Archive</div>
          <div class="settings-tab" data-tab="notification" id="notificationTabBtn" style="position:relative;">Notification</div>
        </div>

        <?php
        // include tab partials (keeps original behavior/variables intact)
        include './tabs/account_tab.php';
        include './tabs/archive_tab.php';
        include './tabs/notification_tab.php';
        ?>

        <!-- Unsaved changes bar removed (moved to account_tab.php) -->
      </section>
    </div>
  </main>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
  <script>
    // Global unsaved flag (shared with account_tab.php)
    window.unsaved = false;

    // Tab switching logic (uses global window.unsaved, references bar in account_tab.php)
    const tabs = document.querySelectorAll('.settings-tab');
    const tabContents = {
      account: document.getElementById('accountTab'),
      archive: document.getElementById('archiveTab'),
      notification: document.getElementById('notificationTab')
    };
    tabs.forEach(tab => {
      tab.addEventListener('click', function(e) {
        if (!this.classList.contains('active')) {
          if (window.unsaved) {
            document.getElementById('unsavedBar').style.display = 'flex';
            e.preventDefault();
          } else {
            tabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            Object.values(tabContents).forEach(tc => tc.style.display = 'none');
            tabContents[this.dataset.tab].style.display = '';
          }
        }
      });
    });

    // Prevent sidebar navigation if unsaved changes (uses global window.unsaved, references bar in account_tab.php)
    document.querySelectorAll('.sidebar a').forEach(link => {
      link.addEventListener('click', function(e) {
        if (window.unsaved) {
          document.getElementById('unsavedBar').style.display = 'flex';
          e.preventDefault();
        }
      });
    });

    // Archive sub-tab logic, DataTables, Requests popup, Notifications, Activate settings tab from URL
    // ...existing code...

    // ===== ADDED: activate tab from URL query (e.g. ?tab=notification) =====
    (function activateTabFromUrl(){
      try {
        const params = new URLSearchParams(window.location.search);
        const requested = params.get('tab');
        if (requested && tabContents[requested]) {
          // remove active from all tabs and set the requested one
          tabs.forEach(t => t.classList.toggle('active', t.getAttribute('data-tab') === requested));
          // hide all contents and show requested
          Object.values(tabContents).forEach(tc => { if (tc) tc.style.display = 'none'; });
          tabContents[requested].style.display = '';
          // scroll into view lightly (optional)
          tabContents[requested].scrollIntoView({ behavior: 'auto', block: 'start' });
        }
      } catch (e) {
        // silent fallback — keep default tab
        console && console.debug && console.debug('activateTabFromUrl error', e);
      }
    })();
  </script>

  <style>
  /* Keep non-account styles here (used by Archive/Requests/Modals) */
  .modal-overlay {
    position: fixed;
    z-index: 9999;
    left: 0; top: 0; right: 0; bottom: 0;
    background: rgba(44,62,80,0.18);
    display: none;
    align-items: center;
    justify-content: center;
  }
  .modal-overlay[style*="display: flex"] {
    display: flex !important;
  }
  .modal-content {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 8px 32px rgba(60,60,60,0.18), 0 1.5px 6px rgba(0,0,0,0.08);
    padding: 32px 32px 24px 32px;
    min-width: 340px;
    max-width: 95vw;
    text-align: center;
    position: relative;
    margin: auto;
  }
  .modal-header h2 {
    margin: 0;
  }
  .modal-footer {
    margin-top: 10px;
  }
  .modal-delete-btn {
    background: #2ecc71;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 10px 28px;
    font-size: 1.08rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.18s;
  }
  .modal-delete-btn:hover {
    background: #27ae60;
  }
  .modal-cancel-btn {
    background: #f4f6fa;
    color: #444;
    border: none;
    border-radius: 6px;
    padding: 10px 28px;
    font-size: 1.08rem;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.18s;
  }
  .modal-cancel-btn:hover {
    background: #e0e0e0;
  }

  /* Removed: .settings-fields-row, .settings-field-group, .settings-input, .password-eye-icon (moved into account_tab.php) */
  /* Removed: .settings-unsaved-bar (moved into account_tab.php) */
  </style>
</body>
</html>