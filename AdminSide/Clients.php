<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../AdminLogin.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>RestEase Clients</title>
  <link rel="icon" type="image/png" href="../assets/re logo blue.png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/Clients.css">
  <link rel="stylesheet" href="../css/sidebar.css">
  <link rel="stylesheet" href="../css/header.css">
  <!-- DataTables CSS -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
</head>
<body>
  <!-- Sidebar -->
  <?php include '../Includes/sidebar.php'; ?>
   <?php include '../Includes/header.php'; ?>

  <!-- Main Content -->
  <main class="main-content">
    <div class="clients-header">
      <h1>Clients</h1>
      <p class="subtitle">View all Clients Information.</p>
    </div>
    <div class="clients-tabs-bar">
      <div class="clients-tabs">
        <!-- Manage Clients on the left, Walk-in tab placed to the right -->
        <span id="tab-manage-clients" class="clients-tab-title active" onclick="showClientsTab('manage')">Manage Clients Account</span>
        <span id="tab-walkin" class="clients-tab-title" onclick="showClientsTab('walkin')">Manage Walk-in Clients</span>
      </div>
    </div>

    <!-- New: Manage Walk-in Clients Section (initially hidden) -->
    <div id="manage-walkins-section" style="display:none;">
      <div class="clients-actions">
        <div class="search-container">
          <i class="fas fa-search"></i>
          <input type="text" placeholder="Search Walk-in Clients" id="walkin-search-input">
        </div>
        <div class="actions-right">
          <div class="date-filter-container">
            <!-- Insert button to the left of the calendar (walk-in date) -->
            <button type="button" id="walkin-insert-btn" class="insert-btn"><i class="fas fa-plus"></i> Insert</button>
            <input type="date" id="walkin-date-filter" class="date-input">
            <button type="button" id="clear-walkin-date-filter" class="clear-date-btn" style="display:none;">
              <i class="fas fa-times"></i>
            </button>
          </div>
        </div>
      </div>

      <div style="margin-bottom: 16px;">
        <div class="dataTables_length">
          <label>Show <select name="walkin-table_length"><option value="10">10</option><option value="25">25</option><option value="50">50</option><option value="100">100</option></select> entries</label>
        </div>
      </div>

      <div class="clients-table-container">
        <table class="clients-table" id="walkin-table">
          <thead>
            <tr>
              <th>Client Name</th>
              <th>Email</th>
              <th>Contact</th>
              <th>Walk-in Date</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
          <?php
          // Replace placeholder with actual walk-in clients retrieval and rendering
          include_once '../Includes/db.php';
          $sql_walkin = "SELECT id, first_name, last_name, email, contact_no, walkin_date FROM walkin_clients ORDER BY walkin_date DESC";
          $result_walkin = $conn->query($sql_walkin);
          if ($result_walkin && $result_walkin->num_rows > 0) {
              while ($row = $result_walkin->fetch_assoc()) {
                  $firstName = htmlspecialchars($row['first_name'] ?? '');
                  $lastName = htmlspecialchars($row['last_name'] ?? '');
                  $name = trim($firstName . ' ' . $lastName);
                  $email = htmlspecialchars($row['email'] ?? '');
                  $contact = htmlspecialchars($row['contact_no'] ?? '');
                  $walkinDate = htmlspecialchars($row['walkin_date'] ? date('Y-m-d', strtotime($row['walkin_date'])) : 'N/A');

                  // No profile pictures stored in walkin_clients by design — render initials avatar
                  $initials = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));
                  $colorIndex = (abs(crc32($firstName . $lastName)) % 10) + 1;
                  $colorClass = "avatar-color-$colorIndex";
                  $avatarHtml = '<div class="avatar-img avatar-google ' . $colorClass . '" style="display:inline-flex;width:38px;height:38px;border-radius:50%;align-items:center;justify-content:center;font-weight:600;">' . $initials . '</div>';

                  // Simple status for walk-in records
                  $statusHtml = '<span style="background:#f0f4f8;color:#223;background-clip:padding-box;padding:4px 12px;border-radius:6px;font-size:0.95em;color:#123456;">Walk-in</span>';

                  echo "<tr data-registration-date='$walkinDate'>
                    <td style='white-space: nowrap;'>
                        $avatarHtml<span class=\"client-name\" style=\"vertical-align:middle; margin-left:8px; display:inline-block;\">$name</span>
                    </td>
                    <td>$email</td>
                    <td>$contact</td>
                    <td>$walkinDate</td>
                    <td>$statusHtml</td>
                </tr>";
              }
          }
          ?>
          </tbody>
        </table>
      </div>
      <div class="dataTables_wrapper"></div>

      <!-- Walk-in Insert Modal moved into the Walk-in section so it appears with this tab only -->
      <div id="walkinInsertModal" class="modal-overlay" style="display:none;">
        <div class="modal-card">
          <button type="button" class="modal-close close-modal" aria-label="Close">&times;</button>
          <header class="modal-card-header">
            <h3 class="modal-title">Insert Walk-in Clients</h3>
            <p class="modal-sub">Add a new walk-in client record</p>
          </header>
          <div class="modal-card-body">
            <form id="walkinInsertForm" class="walkin-form">
              <div class="form-row">
                <input type="text" name="first_name" placeholder="First name" required class="form-input">
              </div>
              <div class="form-row">
                <input type="text" name="last_name" placeholder="Last name" required class="form-input">
              </div>
              <div class="form-row">
                <input type="email" name="email" placeholder="Email (optional)" class="form-input">
              </div>
              <div class="form-row">
                <input type="text" name="contact_no" placeholder="Contact (optional)" class="form-input">
              </div>
            </form>
          </div>
          <footer class="modal-card-footer">
            <button id="walkinInsertSubmit" class="btn btn-primary">Insert</button>
            <button id="walkinInsertCancel" class="btn btn-secondary">Cancel</button>
          </footer>
        </div>
      </div>
    </div>

    <!-- Wrap existing Manage Clients Account section so it can be toggled by tabs -->
    <div id="manage-clients-section">
    <div class="clients-actions">
      <div class="search-container">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="Search Clients" id="search-input">
      </div>
      <div class="actions-right">
        <div class="date-filter-container">
          <input type="date" id="registration-date-filter" class="date-input">
          <button type="button" id="clear-date-filter" class="clear-date-btn" style="display:none;">
            <i class="fas fa-times"></i>
          </button>
        </div>
      </div>
    </div>
    
    <!-- Show entries dropdown positioned exactly like Records.php -->
    <div style="margin-bottom: 16px;">
      <div class="dataTables_length">
        <label>Show <select name="clients-table_length"><option value="10">10</option><option value="25">25</option><option value="50">50</option><option value="100">100</option></select> entries</label>
      </div>
    </div>
    
    <div class="clients-table-container">
      <table class="clients-table" id="clients-table">
        <thead>
          <tr>
            <th>Client Name</th>
            <th>Email</th>
            <th>Contact</th>
            <th>Registration Date</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php
        // Connect to the database
        include_once '../Includes/db.php';
        if ($conn->connect_error) {
            echo "<tr><td colspan='6'>Database connection failed.</td></tr>";
        } else {
            $sql = "SELECT first_name, last_name, email, contact_no, created_at, profile_picture, status FROM users ORDER BY created_at DESC";
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $firstName = htmlspecialchars($row['first_name'] ?? '');
                    $lastName = htmlspecialchars($row['last_name'] ?? '');
                    $name = $firstName . ' ' . $lastName;
                    $email = htmlspecialchars($row['email'] ?? '');
                    $contact = htmlspecialchars($row['contact_no'] ?? '');
                    $registrationDate = htmlspecialchars($row['created_at'] ? date('Y-m-d', strtotime($row['created_at'])) : 'N/A');
                    $profilePicture = htmlspecialchars($row['profile_picture'] ?? '');
                    $status = htmlspecialchars($row['status'] ?? '');
                    
                    // Check if user has profile picture
                    $hasProfilePicture = $profilePicture && file_exists('../uploads/' . $profilePicture);
                    
                    if ($hasProfilePicture) {
                        $avatarHtml = '<img src="../uploads/' . $profilePicture . '" alt="Profile" class="avatar-img" style="width:38px;height:38px;border-radius:50%;object-fit:cover;">';
                    } else {
                        $initials = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));
                        $colorIndex = (abs(crc32($firstName . $lastName)) % 10) + 1;
                        $colorClass = "avatar-color-$colorIndex";
                        $avatarHtml = '<div class="avatar-img avatar-google ' . $colorClass . '" style="display:inline-flex;">' . $initials . '</div>';
                    }
                    
                    // Status display based on actual database value
                    if ($status === 'disabled') {
                        $statusHtml = '<span style="background:#f8d7da;color:#721c24;padding:4px 14px;border-radius:6px;font-size:0.95em;">Disabled</span>';
                        $disableButtonText = '<i class="fas fa-user-check"></i> Enable';
                        $disableButtonClass = 'enable';
                    } else {
                        $statusHtml = '<span style="background:#19d64c;color:#fff;padding:4px 14px;border-radius:6px;font-size:0.95em;">Active</span>';
                        $disableButtonText = '<i class="fas fa-user-slash"></i> Disable';
                        $disableButtonClass = 'disable';
                    }
                    
                    echo "<tr data-registration-date='$registrationDate'>
                    <td style='white-space: nowrap;'>
                        $avatarHtml<span class=\"client-name\" style=\"vertical-align:middle; margin-left:4px; display:inline-block;\">$name</span>
                    </td>
                    <td>$email</td>
                    <td>$contact</td>
                    <td>$registrationDate</td>
                    <td>$statusHtml</td>
                    <td>
                        <div class=\"actions-dropdown\">
                            <!-- removed inline onclick to rely on delegated JS handler -->
                            <button class=\"actions-btn\" type=\"button\">
                                <i class=\"fas fa-ellipsis-v\"></i>
                            </button>
                            <div class=\"actions-menu\">
                                <button class=\"dropdown-item $disableButtonClass\">$disableButtonText</button>
                                <button class=\"dropdown-item delete\"><i class=\"fas fa-archive\"></i> Archive</button>
                            </div>
                        </div>
                    </td>
                </tr>";
                }
            }
        }
        $conn->close();
        ?>
        </tbody>
      </table>
    </div>
    
    <!-- Move pagination controls to bottom exactly like Records.php -->
    <div class="dataTables_wrapper">
    </div>
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal-overlay" style="display:none;">
      <div class="modal-card modal-large" role="dialog" aria-modal="true" aria-labelledby="deleteModalTitle">
        <div class="modal-card-header" style="padding-top:22px;">
          <div style="display:flex;align-items:center;justify-content:center;">
            <div style="width:64px;height:64px;border-radius:50%;background:#fdecec;display:flex;align-items:center;justify-content:center;">  
            <i class="fas fa-exclamation-triangle" style="color:#e74c3c;font-size:2rem;margin-bottom:8px;"></i> </div>
          </div>
          <h2 id="deleteModalTitle" style="color:#e04a5f;margin:12px 0 6px;font-size:1.25rem;text-align:center;">Confirm Archive</h2>
        </div>
        <div class="modal-card-body" style="text-align:center;padding-bottom:18px;">
          <p id="deleteModalText" style="color:#444;font-size:1.03rem;margin:0 0 6px;">
            Are you sure you want to archive this Client?<br>This action will move the Archvive Client to the archive section.
          </p>
        </div>
        <div class="modal-card-footer" style="display:flex;justify-content:center;gap:14px;padding-top:6px;">
          <button id="modalDeleteBtn" class="modal-delete-btn" style="background:#e74c3c;color:#fff;border:none;padding:10px 22px;border-radius:8px;font-weight:700;cursor:pointer;">Archive</button>
          <button id="modalCancelBtn" class="modal-cancel-btn" style="background:#9aa3ad;color:#fff;border:none;padding:10px 22px;border-radius:8px;cursor:pointer;font-weight:700;">Cancel</button>
        </div>
      </div>
    </div>
    <!-- Success Notification -->
    <div id="successNotification" style="display:none;position:fixed;top:32px;right:32px;z-index:10000;background:#2ecc71;color:#fff;padding:18px 32px;border-radius:8px;box-shadow:0 4px 16px rgba(46,204,113,0.15);font-size:1.1rem;font-weight:500;align-items:center;gap:16px;min-width:220px;">
      <span><i class="fas fa-check-circle" style="margin-right:8px;"></i>Client successfully archived.</span>
      <button id="closeNotificationBtn" style="background:none;border:none;color:#fff;font-size:1.2em;cursor:pointer;margin-left:12px;">&times;</button>
    </div>
    <!-- DataTables JS -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
  </main>

</body>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Ensure both modals (archive confirmation and walk-in insert) are attached to document.body
    // and have overlay click-to-close handlers. Moving them avoids visual clipping when toggling tabs.
    (function normalizeModals() {
        try {
            const mods = [
                { id: 'deleteModal', z: '12020' },
                { id: 'walkinInsertModal', z: '12010' } // walkin below delete by default
            ];
            mods.forEach(m => {
                const modal = document.getElementById(m.id);
                if (!modal) return;
                if (modal.parentElement !== document.body) document.body.appendChild(modal);
                modal.style.zIndex = modal.style.zIndex || m.z;
                if (!modal.style.display || modal.style.display === '') modal.style.display = 'none';
                // clicking the overlay (outside inner content) closes the modal
                modal.addEventListener('click', function (ev) {
                    if (ev.target === modal) modal.style.display = 'none';
                });
            });
        } catch (err) {
            console.error('normalizeModals error', err);
        }
    })();
    // Initialize DataTables for Manage Clients (existing)
    const dataTable = $('#clients-table').DataTable({
        "paging": true,
        "searching": true,
        "ordering": true,
        "info": true,
        "dom": 'rtip',
        "columnDefs": [
            { "orderable": false, "targets": [5] }
        ],
        "drawCallback": function() {
            const tableWrapper = $('#clients-table').closest('.clients-table-container');
            const externalWrapper = tableWrapper.next('.dataTables_wrapper');
            const info = $('#clients-table_info').detach();
            const paginate = $('#clients-table_paginate').detach();
            externalWrapper.empty().append(info).append(paginate);
        }
    });

    // New: Initialize DataTable for Walk-in Clients
    let walkinTable = null;
    try {
      walkinTable = $('#walkin-table').DataTable({
          "paging": true,
          "searching": true,
          "ordering": true,
          "info": true,
          "dom": 'rtip',
          "columnDefs": [
              // Actions column removed: last column index is now 4 (0..4), make status column orderable and only prevent ordering on last column if needed
              { "orderable": false, "targets": [4] }
          ],
          "drawCallback": function() {
              const tableWrapper = $('#walkin-table').closest('.clients-table-container');
              const externalWrapper = tableWrapper.next('.dataTables_wrapper');
              const info = $('#walkin-table_info').detach();
              const paginate = $('#walkin-table_paginate').detach();
              externalWrapper.empty().append(info).append(paginate);
          }
      });
    } catch (e) {
      // If table is not present or initialization fails, log and continue.
      console.error('Walk-in table init error:', e);
    }

    // Connect existing search bar to DataTables
    document.getElementById('search-input').addEventListener('keyup', function() {
        dataTable.search(this.value).draw();
    });

    // Connect walk-in search bar to walkin DataTable
    const walkinSearch = document.getElementById('walkin-search-input');
    if (walkinSearch) {
      walkinSearch.addEventListener('keyup', function() {
        try {
          if (walkinTable) walkinTable.search(this.value).draw();
        } catch (e) {
          console.error('Walkin search error:', e);
        }
      });
    }

    // New date filter functionality for clients (existing)
    const dateInput = document.getElementById('registration-date-filter');
    const clearDateBtn = document.getElementById('clear-date-filter');

    dateInput.addEventListener('change', function() {
        const selectedDate = this.value;
        if (selectedDate) {
            clearDateBtn.style.display = 'block';
            dataTable.column(3).search(selectedDate, false, false).draw();
        } else {
            clearDateBtn.style.display = 'none';
            dataTable.column(3).search('').draw();
        }
    });

    clearDateBtn.addEventListener('click', function() {
        dateInput.value = '';
        this.style.display = 'none';
        dataTable.column(3).search('').draw();
    });

    // Date filter for walk-ins
    const walkinDate = document.getElementById('walkin-date-filter');
    const clearWalkinDateBtn = document.getElementById('clear-walkin-date-filter');
    if (walkinDate && clearWalkinDateBtn) {
      walkinDate.addEventListener('change', function() {
        const selectedDate = this.value;
        if (selectedDate) {
          clearWalkinDateBtn.style.display = 'block';
          try { if (walkinTable) walkinTable.column(3).search(selectedDate, false, false).draw(); } catch(e){console.error(e);}
        } else {
          clearWalkinDateBtn.style.display = 'none';
          try { if (walkinTable) walkinTable.column(3).search('').draw(); } catch(e){console.error(e);}
        }
      });
      clearWalkinDateBtn.addEventListener('click', function() {
        walkinDate.value = '';
        this.style.display = 'none';
        try { if (walkinTable) walkinTable.column(3).search('').draw(); } catch(e){console.error(e);}
      });
    }

    // Connect entries dropdowns
    document.querySelector('select[name="clients-table_length"]').addEventListener('change', function() {
        dataTable.page.len(parseInt(this.value)).draw();
    });
    const walkinLen = document.querySelector('select[name="walkin-table_length"]');
    if (walkinLen) {
      walkinLen.addEventListener('change', function() {
        try { if (walkinTable) walkinTable.page.len(parseInt(this.value)).draw(); } catch(e){console.error(e);}
      });
    }

    // Close all open menus if clicking outside
    document.addEventListener('click', function(e) {
        document.querySelectorAll('.actions-menu').forEach(function(menu) {
            menu.style.display = 'none';
        });
    });

    // Stop propagation for clicks inside actions-dropdown so the document click won't close it
    $(document).on('click', '.actions-dropdown', function(e) {
        e.stopPropagation();
    });

    // Delegated toggle for actions button (works across pagination/search/redraw)
    $(document).on('click', '.actions-btn', function(e) {
        e.stopPropagation();
        // hide other menus first
        $('.actions-menu').hide();
        const $menu = $(this).siblings('.actions-menu');
        // toggle display as flex to match original intent
        if ($menu.css('display') === 'flex') {
            $menu.hide();
        } else {
            $menu.css('display','flex');
        }
    });

    // Delegated handler for Archive (delete) button in Actions menu
    let deleteTargetRow = null;
    let deleteTargetEmail = null;

    $(document).on('click', '.dropdown-item.delete', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const $btn = $(this);
        const $row = $btn.closest('tr');
        deleteTargetRow = $row.get(0);
        deleteTargetEmail = $row.find('td:nth-child(2)').text().trim();
        document.getElementById('deleteModal').style.display = 'flex';
    });

    // Cancel button closes modal
    document.getElementById('modalCancelBtn').addEventListener('click', function() {
        document.getElementById('deleteModal').style.display = 'none';
        deleteTargetRow = null;
        deleteTargetEmail = null;
    });

    // Delete (Archive) confirmation — ensure modal is hidden promptly and then remove row via DataTables
    document.getElementById('modalDeleteBtn').addEventListener('click', function() {
        if (!deleteTargetEmail || !deleteTargetRow) return;

        const deleteBtn = this;
        const modal = document.getElementById('deleteModal');
        const cancelBtn = document.getElementById('modalCancelBtn');

        // Show loading state
        deleteBtn.disabled = true;
        deleteBtn.textContent = 'Archiving...';
        cancelBtn.disabled = true;

        fetch('archive_client.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'archive_client_email=' + encodeURIComponent(deleteTargetEmail)
        })
        .then(res => {
            if (!res.ok) throw new Error('Network response was not ok');
            return res.json();
        })
        .then(data => {
            if (data.status === 'success') {
                try {
                    // hide modal first so user sees the transition
                    modal.style.display = 'none';
                    // remove the row using DataTables where applicable
                    const $tr = $(deleteTargetRow);
                    if (walkinTable && $tr.closest('table').is('#walkin-table')) {
                        walkinTable.row($tr).remove().draw(false);
                    } else if (dataTable && $tr.closest('table').is('#clients-table')) {
                        dataTable.row($tr).remove().draw(false);
                    } else {
                        $tr.remove();
                    }
                    showSuccessNotification(data.message || 'Client successfully archived');
                } catch (err) {
                    console.error('Error removing row from DataTable:', err);
                    showErrorNotification('Archive succeeded but failed to update table view. Refresh page.');
                }
            } else {
                showErrorNotification(data.message || 'Failed to archive client');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorNotification('An error occurred while archiving. Please try again.');
        })
        .finally(() => {
            // Reset button states
            deleteBtn.disabled = false;
            deleteBtn.textContent = 'Archive';
            cancelBtn.disabled = false;

            // Clear references
            deleteTargetRow = null;
            deleteTargetEmail = null;
        });
    });

    // Delegated handler for Disable / Enable buttons so it works on searched/paginated rows
    $(document).on('click', '.dropdown-item.disable, .dropdown-item.enable', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const $btn = $(this);
        const $row = $btn.closest('tr');
        const email = $row.find('td:nth-child(2)').text().trim();
        const isDisable = $btn.hasClass('disable');
        const action = isDisable ? 'disable' : 'enable';

        // Show loading state
        $btn.prop('disabled', true);
        const originalHtml = $btn.html();
        $btn.html(isDisable ? 'Disabling...' : 'Enabling...');

        fetch('disable_client.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'disable_client_email=' + encodeURIComponent(email) + '&action=' + action
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                // Update status display
                const $statusCell = $row.find('td:nth-child(5)');
                if (isDisable) {
                    $statusCell.html('<span style="background:#f8d7da;color:#721c24;padding:4px 14px;border-radius:6px;font-size:0.95em;">Disabled</span>');
                    $btn.html('<i class="fas fa-user-check"></i> Enable');
                    $btn.removeClass('disable').addClass('enable');
                } else {
                    $statusCell.html('<span style="background:#19d64c;color:#fff;padding:4px 14px;border-radius:6px;font-size:0.95em;">Active</span>');
                    $btn.html('<i class="fas fa-user-slash"></i> Disable');
                    $btn.removeClass('enable').addClass('disable');
                }
                showSuccessNotification(data.message || 'Client status updated');
                // If DataTables needs re-draw for consistent rendering, trigger minimal draw (no paging reset)
                try {
                    if ($row.closest('table').is('#clients-table')) dataTable.row($row).invalidate().draw(false);
                    else if (walkinTable && $row.closest('table').is('#walkin-table')) walkinTable.row($row).invalidate().draw(false);
                } catch (err) {
                    // harmless if row not part of DataTable or draw fails
                }
            } else {
                showErrorNotification(data.message || 'Failed to update client status');
                $btn.html(originalHtml);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorNotification('An error occurred. Please try again.');
            $btn.html(originalHtml);
        })
        .finally(() => {
            $btn.prop('disabled', false);
        });
    });

    // Show notification logic (added)
    function showSuccessNotification(message) {
        try {
            const notif = document.getElementById('successNotification');
            if (!notif) return;
            const span = notif.querySelector('span');
            if (span) span.innerHTML = `<i class="fas fa-check-circle" style="margin-right:8px;"></i>${message}`;
            notif.style.display = 'flex';
            notif.style.background = '#2ecc71';
            // clear any previous timeout
            if (notif._timeout) {
                clearTimeout(notif._timeout);
                notif._timeout = null;
            }
            // auto-close after 3s
            notif._timeout = setTimeout(() => {
                notif.style.display = 'none';
                notif._timeout = null;
            }, 3000);

            const closeBtn = document.getElementById('closeNotificationBtn');
            if (closeBtn) {
                closeBtn.onclick = function() {
                    notif.style.display = 'none';
                    if (notif._timeout) {
                        clearTimeout(notif._timeout);
                        notif._timeout = null;
                    }
                };
            }
        } catch (err) {
            console.error('showSuccessNotification error', err);
        }
    }

    function showErrorNotification(message) {
        try {
            const notif = document.getElementById('successNotification');
            if (!notif) return;
            const span = notif.querySelector('span');
            if (span) span.innerHTML = `<i class="fas fa-exclamation-circle" style="margin-right:8px;"></i>${message}`;
            notif.style.display = 'flex';
            notif.style.background = '#e74c3c';
            if (notif._timeout) {
                clearTimeout(notif._timeout);
                notif._timeout = null;
            }
            notif._timeout = setTimeout(() => {
                notif.style.display = 'none';
                notif._timeout = null;
            }, 3000);

            const closeBtn = document.getElementById('closeNotificationBtn');
            if (closeBtn) {
                closeBtn.onclick = function() {
                    notif.style.display = 'none';
                    if (notif._timeout) {
                        clearTimeout(notif._timeout);
                        notif._timeout = null;
                    }
                };
            }
        } catch (err) {
            console.error('showErrorNotification error', err);
        }
    }

    // Tab switching helper
    window.showClientsTab = function(tab) {
      const walkinSection = document.getElementById('manage-walkins-section');
      const manageSection = document.getElementById('manage-clients-section');
      const tabWalkin = document.getElementById('tab-walkin');
      const tabManage = document.getElementById('tab-manage-clients');

      if (tab === 'walkin') {
        if (walkinSection) walkinSection.style.display = '';
        if (manageSection) manageSection.style.display = 'none';
        tabWalkin.classList.add('active');
        tabManage.classList.remove('active');
        // redraw walkin table if needed
        try { if (walkinTable) walkinTable.columns.adjust().draw(); } catch (e) {}
      } else {
        if (walkinSection) walkinSection.style.display = 'none';
        if (manageSection) manageSection.style.display = '';
        tabManage.classList.add('active');
        tabWalkin.classList.remove('active');
        try { if (dataTable) dataTable.columns.adjust().draw(); } catch (e) {}
      }
    };

    // ensure the default visible tab remains the existing Manage Clients Account
    // (no call needed as the HTML default has manage-clients active)
    
    // Insert button modal handlers (walk-in tab only)
    const walkinInsertBtn = document.getElementById('walkin-insert-btn');
    const walkinInsertModal = document.getElementById('walkinInsertModal');
    const walkinInsertCancel = document.getElementById('walkinInsertCancel');
    const walkinInsertSubmit = document.getElementById('walkinInsertSubmit');
    const walkinInsertForm = document.getElementById('walkinInsertForm');
    const walkinModalClose = walkinInsertModal ? walkinInsertModal.querySelector('.close-modal') : null;

    function openWalkinModal() {
      if (!walkinInsertModal) return;
      walkinInsertModal.style.display = 'flex';
      // small focus for accessibility
      const first = walkinInsertForm.querySelector('input[name="first_name"]');
      if (first) try { first.focus(); } catch(e){ }
    }
    function closeWalkinModal() {
      if (!walkinInsertModal) return;
      walkinInsertModal.style.display = 'none';
      walkinInsertForm.reset();
    }

    if (walkinInsertBtn) {
      walkinInsertBtn.addEventListener('click', function(e) {
        e.preventDefault();
        openWalkinModal();
      });
    }
    if (walkinInsertCancel) {
      walkinInsertCancel.addEventListener('click', function(e) {
        e.preventDefault();
        closeWalkinModal();
      });
    }
    if (walkinModalClose) {
      walkinModalClose.addEventListener('click', function(e) {
        e.preventDefault();
        closeWalkinModal();
      });
    }

    // Submit handler - minimal placeholder, posts to insert_walkin.php
    if (walkinInsertSubmit) {
      walkinInsertSubmit.addEventListener('click', function(e) {
        e.preventDefault();
        const formData = new FormData(walkinInsertForm);

        // Append selected walk-in date (from the calendar left of insert button)
        const walkinDateInput = document.getElementById('walkin-date-filter');
        if (walkinDateInput && walkinDateInput.value) {
          formData.append('walkin_date', walkinDateInput.value);
        } else {
          // optional: append empty to make sure server sees the field
          formData.append('walkin_date', '');
        }

        walkinInsertSubmit.disabled = true;
        walkinInsertSubmit.textContent = 'Inserting...';
        fetch('insert_walkin.php', {
          method: 'POST',
          body: formData
        })
        .then(res => res.json())
        .then(data => {
          walkinInsertSubmit.disabled = false;
          walkinInsertSubmit.textContent = 'Insert';
          if (data && data.success) {
            closeWalkinModal();
            // refresh the table or page to reflect new walk-in
            if (walkinTable) {
              location.reload();
            }
          } else {
            alert(data.message || 'Failed to insert walk-in');
          }
        })
        .catch(err => {
          walkinInsertSubmit.disabled = false;
          walkinInsertSubmit.textContent = 'Insert';
          console.error('Insert walkin error', err);
          alert('Network error while inserting walk-in');
        });
      });
    }
});
</script>
<style>
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(22, 28, 33, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1200;
}
.modal-card {
  background: #ffffff;
  border-radius: 12px;
  width: 420px;
  max-width: calc(100% - 40px);
  box-shadow: 0 12px 40px rgba(16,185,129,0.12);
  overflow: hidden;
  position: relative;
  transform: translateY(0);
  transition: transform .18s ease;
}
.modal-close {
  position: absolute;
  right: 12px;
  top: 10px;
  border: none;
  background: transparent;
  font-size: 22px;
  color: #6b7280;
  cursor: pointer;
  padding: 6px;
  line-height: 1;
}
.modal-card-header {
  padding: 20px 24px 8px 24px;
  border-bottom: 1px solid #f3f4f6;
  text-align: center;
}.modal-title {
  margin: 0;
  font-size: 18px;
  color: #111827;
  font-weight: 700;
}
.modal-sub {
  margin: 6px 0 0 0;
  color: #6b7280;
  font-size: 13px;
}
.modal-card-body {
  padding: 18px 24px 12px 24px;
}
.walkin-form .form-row { margin-bottom: 10px; }
.form-input {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: #fff;
  font-size: 14px;
  color: #111827;
  box-sizing: border-box;
}
.form-input:focus {
  outline: none;
  border-color: #60a5fa;
  box-shadow: 0 0 0 4px rgba(96,165,250,0.08);
}
.modal-card-footer {
  padding: 12px 24px 18px 24px;
  display: flex;
  justify-content: center;
  gap: 12px;
  border-top: 1px solid #f3f4f6;
}
.btn {
  border: none;
  padding: 10px 20px;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  font-size: 14px;
}
.btn-primary {
  background: #10b981;
  color: #fff;
  box-shadow: 0 6px 18px rgba(16,185,129,0.12);
}
.btn-primary:active { transform: translateY(1px); }
.btn-secondary {
  background: #f3f4f6;
  color: #111827;
}
.clients-tab-title {
    background: none;
    border: none;
    font-size: 1.08rem;
    padding: 16px 0 12px 0;
    color: #222;
    font-weight: 600;
    border-bottom: 2px solid transparent;
    cursor: pointer;
    opacity: 0.7;
    transition: border-bottom 0.18s, opacity 0.18s, color 0.18s;
    border-radius: 0;
    box-shadow: none;
    margin-bottom: 0;
    margin-top: 0;
    display: inline-block;
}
.clients-tab-title.active {
    border-bottom: 2.5px solid #506C84;
    color: #506C84;
    opacity: 1;
}
.date-filter-container {
    position: relative;
    display: flex;
    align-items: center;
}

.date-input {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
    background: #fff;
    cursor: pointer;
}

/* Insert button style (walk-in tab) */
.insert-btn {
  background: #506C84;
  color: #fff;
  border: none;
  padding: 8px 12px;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
  margin-right: 8px;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}
.insert-btn i { font-size: 0.95rem; }

/* Reuse modal styles already defined, but ensure walkin modal overlay appears above page */
#walkinInsertModal .modal-card {
  min-width: 320px;
}

/* make delete modal larger and style action buttons to match screenshot */
.modal-card.modal-large { width: 520px; max-width: calc(100% - 40px); box-shadow: 0 20px 60px rgba(0,0,0,0.18); border-radius:12px; }
.modal-delete-btn { background:#e04a5f; color:#fff; border:none; padding:10px 22px; border-radius:8px; font-weight:700; cursor:pointer; }
.modal-cancel-btn { background:#9aa3ad; color:#fff; border:none; padding:10px 22px; border-radius:8px; font-weight:700; cursor:pointer; }
.modal-card-header h2 { margin:0; }
.modal-card .modal-card-body p { margin:0; }
</style>
</html>
