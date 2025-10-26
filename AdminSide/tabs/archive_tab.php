<?php
// Ensure this partial is self-contained: include DB and expose $conn similar to account_tab.php
if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) {
	// Use a relative path to Includes/db.php; adjust if your project layout differs
	include_once __DIR__ . '/../Includes/db.php';
	// If Includes/db.php does not set $conn (defensive), create one
	if (!isset($conn) || !($conn instanceof mysqli)) {
		$conn = new mysqli('localhost', 'root', '', 'cemeterydb');
	}
}
?>
<div class="settings-card" id="archiveTab" style="display:none;">
  <!-- Archive Sub-tabs -->
  <div style="border-bottom:1px solid #e0e0e0; margin-bottom: 10px; margin-top: 18px;">
    <div id="archiveSubTabs" style="display:flex;gap:32px;">
      <div class="archive-subtab active" data-archivetab="clients" id="archiveClientsTabBtn" style="padding-bottom:6px;cursor:pointer;border-bottom:2px solid #2d72d9;font-weight:500;color:#2d72d9;">Archive Clients</div>
      <div class="archive-subtab" data-archivetab="records" id="archiveRecordsTabBtn" style="padding-bottom:6px;cursor:pointer;color:#888;">Archive Records</div>
      <div class="archive-subtab" data-archivetab="requests" id="archiveRequestsTabBtn" style="padding-bottom:6px;cursor:pointer;color:#888;">Archive Request</div>
    </div>
  </div>
  <!-- Archive Clients Table -->
  <div id="archiveClientsTab">
    <div class="settings-section">
      <h2 style="margin-bottom:12px;">Archive Clients</h2>
      <div style="margin-bottom:12px;">
        <span class="archive-search-bar">
          <i class="fas fa-search"></i>
          <input type="text" placeholder="Search Clients" id="archiveClientsSearchInput">
        </span>
      </div>
      <div class="clients-table-container">
        <table class="clients-table" id="archive-clients-table">
          <thead>
            <tr>
              <th>Client Name</th>
              <th>Email</th>
              <th>Contact</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php
            if (!isset($conn) || $conn->connect_error) {
              echo "<tr><td colspan='5'>Database connection failed.</td></tr>";
            } else {
              $result = $conn->query("SELECT * FROM archive_clients ORDER BY archived_at DESC");
               if ($result && $result->num_rows > 0) {
                 while ($row = $result->fetch_assoc()) {
                   $firstName = htmlspecialchars($row['first_name'] ?? '');
                   $lastName = htmlspecialchars($row['last_name'] ?? '');
                   $name = $firstName . ' ' . $lastName;
                   $email = htmlspecialchars($row['email'] ?? '');
                   $contact = htmlspecialchars($row['contact_no'] ?? '');
                   $profilePicture = htmlspecialchars($row['profile_pic'] ?? '');
                   $hasProfilePicture = $profilePicture && file_exists('../uploads/' . $profilePicture);
                   if ($hasProfilePicture) {
                     $avatarHtml = '<img src="../uploads/' . $profilePicture . '" alt="Profile" class="avatar-img" style="width:38px;height:38px;border-radius:50%;object-fit:cover;">';
                   } else {
                     $initials = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));
                     $colorIndex = (abs(crc32($firstName . $lastName)) % 10) + 1;
                     $colorClass = "avatar-color-$colorIndex";
                     $avatarHtml = '<div class="avatar-img avatar-google ' . $colorClass . '" style="display:inline-flex;">' . $initials . '</div>';
                   }
                   $statusHtml = '<span style="background:#f8d7da;color:#721c24;padding:4px 14px;border-radius:6px;font-size:0.95em;">Archived</span>';
                   echo "<tr>
                     <td style='white-space: nowrap;'>
                       $avatarHtml<span class=\"client-name\" style=\"vertical-align:middle; margin-left:4px; display:inline-block;\">$name</span>
                     </td>
                     <td>$email</td>
                     <td>$contact</td>
                     <td>$statusHtml</td>
                     <td>
                       <button class=\"restore-btn\" style=\"background:#2d72d9;color:#fff;border:none;border-radius:6px;padding:7px 18px;font-size:1rem;font-weight:500;cursor:pointer;\"><i class=\"fas fa-undo\"></i> Restore</button>
                     </td>
                   </tr>";
                 }
               } else {
                 echo "<tr><td colspan='5'>No archived clients found.</td></tr>";
               }
            }
            ?>
          </tbody>
        </table>
      </div>
      <div class="dataTables_wrapper"></div>
      <!-- Restore Confirmation Modal (styled like archive modal) -->
      <div id="restoreModal" class="modal-overlay" style="display:none;">
        <div class="modal-content" style="margin:auto;">
          <div class="modal-header">
            <i class="fas fa-exclamation-triangle" style="color:#2ecc71;font-size:2rem;margin-bottom:8px;"></i>
            <h2 style="color:#2ecc71;margin:0;font-size:1.3rem;">Confirm Restore</h2>
          </div>
          <div class="modal-body" style="margin:18px 0 24px 0;">
            <p style="color:#444;font-size:1.07rem;margin:0;">
              Are you sure you want to restore this client?<br>
              This action will move the client back to the active clients list.
            </p>
          </div>
          <div class="modal-footer" style="display:flex;justify-content:center;gap:16px;">
            <button id="modalRestoreBtn" class="modal-delete-btn" style="background:#2ecc71;">Restore</button>
            <button id="modalCancelRestoreBtn" class="modal-cancel-btn">Cancel</button>
          </div>
        </div>
      </div>
      <!-- Success Notification for Restore -->
      <div id="restoreSuccessNotification" style="display:none;position:fixed;top:32px;right:32px;z-index:10000;background:#2ecc71;color:#fff;padding:18px 32px;border-radius:8px;box-shadow:0 4px 16px rgba(46,204,113,0.15);font-size:1.1rem;font-weight:500;align-items:center;gap:16px;min-width:220px;">
        <span><i class="fas fa-check-circle" style="margin-right:8px;"></i>Client successfully restored.</span>
        <button id="closeRestoreNotificationBtn" style="background:none;border:none;color:#fff;font-size:1.2em;cursor:pointer;margin-left:12px;">&times;</button>
      </div>
    </div>
  </div>

  <!-- Archive Records Section -->
  <div id="archiveRecordsTab" style="display:none;">
    <div class="settings-section">
      <h2 style="margin-bottom:12px;">Archive Records</h2>
      <div style="margin-bottom:12px;">
        <span class="archive-search-bar">
          <i class="fas fa-search"></i>
          <input type="text" placeholder="Search Records" id="archiveRecordsSearchInput">
        </span>
      </div>
      <div class="archive-table-container" style="overflow-x:auto;">
        <table class="archive-table" id="archiveRecordsTable">
          <thead>
            <tr>
              <th>First Name</th>
              <th>Last Name</th>
              <th>Age</th>
              <th>Born</th>
              <th>Residency</th>
              <th>Date Died</th>
              <th>Date Internment</th>
              <th>Niche ID</th>
              <th>Informant Name</th>
            </tr>
          </thead>
          <tbody>
            <?php
            if (!isset($conn) || $conn->connect_error) {
              echo '<tr><td colspan="9">Database connection failed.</td></tr>';
            } else {
              $result = $conn->query("SELECT * FROM archive_deceased ORDER BY id DESC");
              if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                  echo '<tr>';
                  echo '<td>' . htmlspecialchars($row['firstName'] ?? '') . '</td>';
                  echo '<td>' . htmlspecialchars($row['lastName'] ?? '') . '</td>';
                  echo '<td>' . htmlspecialchars($row['age'] ?? '') . '</td>';
                  echo '<td>' . htmlspecialchars($row['born'] ?? '') . '</td>';
                  echo '<td>' . htmlspecialchars($row['residency'] ?? '') . '</td>';
                  echo '<td>' . htmlspecialchars($row['dateDied'] ?? '') . '</td>';
                  echo '<td>' . htmlspecialchars($row['dateInternment'] ?? '') . '</td>';
                  echo '<td>' . htmlspecialchars($row['nicheID'] ?? '') . '</td>';
                  echo '<td>' . htmlspecialchars($row['informantName'] ?? '') . '</td>';
                  echo '</tr>';
                }
              } else {
                echo '<tr><td colspan="9">No archived records found.</td></tr>';
              }
              // $conn->close();
            }
            ?>
          </tbody>
        </table>
      </div>
      <!-- External wrapper for DataTables info + pagination (ONLY for Archive Records) -->
      <div class="archive-records-dt-wrapper dataTables_wrapper"></div>
    </div>

    <style>
    .archive-table-container {
      margin: 24px 0;
      overflow-x: auto;
    }
    /* Sticky external pagination specific to Archive Records table only */
    .archive-records-dt-wrapper {
      position: sticky;
      bottom: 0;
      background: #fff;
      padding: 10px 12px;
      border-top: 1px solid #eee;
      display: flex;
      align-items: center;
      gap: 12px;
      box-sizing: border-box;
      z-index: 5;
      width: 100%;
    }
    .archive-records-dt-wrapper .dataTables_info { flex: 0 1 auto; }
    .archive-records-dt-wrapper .dataTables_paginate { margin-left: auto; }
    .archive-table {
      width: 100%;
      border-collapse: collapse;
      background: #fff;
      border-radius: 8px;
      overflow: hidden;
      font-size: 0.9rem;
    }
    .archive-table th, .archive-table td {
      padding: 8px 10px;
      border-bottom: 1px solid #e3e7ed;
      text-align: left;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .archive-table th {
      background: #f5f7fa;
      color: #2d3a4a;
      font-weight: 600;
      font-size: 0.85rem;
    }
    .archive-table tr:last-child td {
      border-bottom: none;
    }
    /* Specific column widths for better date display */
    .archive-table th:nth-child(1), 
    .archive-table td:nth-child(1) { width: 12%; }
    .archive-table th:nth-child(2), 
    .archive-table td:nth-child(2) { width: 12%; }
    .archive-table th:nth-child(3), 
    .archive-table td:nth-child(3) { width: 8%; }
    .archive-table th:nth-child(4), 
    .archive-table td:nth-child(4) { width: 12%; }
    .archive-table th:nth-child(5), 
    .archive-table td:nth-child(5) { width: 20%; }
    .archive-table th:nth-child(6), 
    .archive-table td:nth-child(6) { width: 12%; }
    .archive-table th:nth-child(7), 
    .archive-table td:nth-child(7) { width: 12%; }
    .archive-table th:nth-child(8), 
    .archive-table td:nth-child(8) { width: 8%; }
    .archive-table th:nth-child(9), 
    .archive-table td:nth-child(9) { width: 14%; }
    </style>
  </div>

  <!-- Archive Requests Section -->
  <div id="archiveRequestsTab" style="display:none;">
    <div class="settings-section">
      <h2 style="margin-bottom:12px;">Archive Requests</h2>
      <div style="margin-bottom:12px;">
        <span class="archive-search-bar">
          <i class="fas fa-search"></i>
          <input type="text" placeholder="Search Requests" id="archiveRequestSearchInput">
        </span>
      </div>
      <div style="overflow-x:auto;">
        <table class="clients-table" id="archiveRequestsTable">
          <thead>
            <tr>
              <th>Client Name</th>
              <th>Email</th>
              <th>Type</th>
              <th>Request Date</th>
              <th>Status</th>
              <th>Details</th>
            </tr>
          </thead>
          <tbody id="archiveRequestTableBody">
            <?php
            if (!isset($conn) || $conn->connect_error) {
              echo '<tr><td colspan="6">Database connection failed.</td></tr>';
            } else {
              $sql = "SELECT dr.*, u.email, u.first_name AS user_first_name, u.last_name AS user_last_name, u.profile_picture FROM denied_request dr JOIN users u ON dr.user_id = u.id ORDER BY dr.created_at DESC";
              $result = $conn->query($sql);
               if ($result && $result->num_rows > 0) {
                 while ($row = $result->fetch_assoc()) {
                   $firstName = htmlspecialchars($row['user_first_name']);
                   $lastName = htmlspecialchars($row['user_last_name']);
                   $name = $firstName . ' ' . $lastName;
                   $email = htmlspecialchars($row['email']);
                   $type = htmlspecialchars($row['type']);
                   $requestDate = htmlspecialchars($row['created_at'] ? date('Y-m-d', strtotime($row['created_at'])) : 'N/A');
                   $status = '<span class="status-badge status-denied">Denied</span>';
                   $profilePicture = htmlspecialchars($row['profile_picture']);
                   $hasProfilePicture = $profilePicture && file_exists('../uploads/' . $profilePicture);
                   if ($hasProfilePicture) {
                     $avatarHtml = '<img src="../uploads/' . $profilePicture . '" alt="Profile" class="avatar-img" style="width:38px;height:38px;border-radius:50%;object-fit:cover;">';
                   } else {
                     $initials = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));
                     $colorIndex = (abs(crc32($firstName . $lastName)) % 10) + 1;
                     $colorClass = "avatar-color-$colorIndex";
                     $avatarHtml = '<div class="avatar-img avatar-google ' . $colorClass . '" style="display:inline-flex;">' . $initials . '</div>';
                   }
                   echo '<tr style="background:#fff;">';
                   echo '<td style="padding:8px 8px;display:flex;align-items:center;gap:10px;">' . $avatarHtml . '<span class="client-name" style="vertical-align:middle; margin-left:4px; display:inline-block;font-weight:500;">' . $name . '</span></td>';
                   echo '<td>' . $email . '</td>';
                   echo '<td>' . $type . '</td>';
                   echo '<td>' . $requestDate . '</td>';
                   echo '<td>' . $status . '</td>';
                   echo '<td><button class="view-btn" onclick="openDeniedPopup(' . $row['id'] . ')">View</button></td>';
                   echo '</tr>';
                 }
               } else {
                 echo '<tr><td colspan="6">No denied requests found.</td></tr>';
               }
              // $conn->close();
            }
            ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Denied Request Popup Modal -->
  <div id="deniedPopupModal" class="popup-modal" style="display:none;">
    <div class="popup-content">
      <div class="popup-header">
        <h3 class="popup-title">Request Details</h3>
        <button class="close-btn" onclick="closeDeniedPopup()">&times;</button>
      </div>
      <div class="popup-details">
        <div class="detail-row">
          <span class="detail-label">Informant Name:</span>
          <span class="detail-value" id="deniedPopupInformant"></span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Email:</span>
          <span class="detail-value" id="deniedPopupEmail"></span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Type:</span>
          <span class="detail-value" id="deniedPopupType"></span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Name of Deceased:</span>
          <span class="detail-value" id="deniedPopupDeceased"></span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Residency:</span>
          <span class="detail-value" id="deniedPopupResidency"></span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Date of Birth:</span>
          <span class="detail-value" id="deniedPopupDOB"></span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Date of Death:</span>
          <span class="detail-value" id="deniedPopupDOD"></span>
        </div>
        <div class="detail-row" id="deniedPopupNicheIdRow" style="display:none;">
          <span class="detail-label">Niche ID:</span>
          <span class="detail-value" id="deniedPopupNicheId"></span>
        </div>
        <div class="detail-row" id="deniedPopupCurrentNicheIdRow" style="display:none;">
          <span class="detail-label">Current Niche ID:</span>
          <span class="detail-value" id="deniedPopupCurrentNicheId"></span>
        </div>
        <div class="detail-row" id="deniedPopupNewNicheIdRow" style="display:none;">
          <span class="detail-label">New Niche Location:</span>
          <span class="detail-value" id="deniedPopupNewNicheId"></span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Age:</span>
          <span class="detail-value" id="deniedPopupAge"></span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Attachments:</span>
          <div class="detail-value" id="deniedPopupAttachment"></div>
        </div>
        <div class="detail-row">
          <span class="detail-label">Date of Internment:</span>
          <span class="detail-value" id="deniedPopupInternmentDate"></span>
        </div>
      </div>
    </div>
  </div>

  <style>
    /* Popup Modal Styles (copied from ClientsRequest.php) */
    .popup-modal {
      position: fixed;
      z-index: 9999;
      left: 0; top: 0; width: 100vw; height: 100vh;
      background: rgba(44,62,80,0.25);
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .popup-content {
      background: #fff;
      padding: 32px;
      border-radius: 16px;
      width: 500px;
      max-width: 90vw;
      position: relative;
      box-shadow: 0 12px 48px rgba(44,62,80,0.15);
      animation: modalSlideIn 0.3s ease-out;
    }
    @keyframes modalSlideIn {
      0% { transform: scale(0.9); opacity: 0; }
      100% { transform: scale(1); opacity: 1; }
    }
    .popup-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      padding-bottom: 12px;
      border-bottom: 1px solid #e5e7eb;
    }
    .popup-title {
      font-size: 1.25rem;
      font-weight: 600;
      color: #374151;
      margin: 0;
    }
    .close-btn {
      background: none;
      border: none;
      font-size: 1.5rem;
      color: #9ca3af;
      cursor: pointer;
      padding: 4px 8px;
      line-height: 1;
      border-radius: 50%;
      transition: all 0.2s ease;
      width: 32px;
      height: 32px;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .close-btn:hover {
      color: #6b7280;
      background: #f3f4f6;
    }
    .popup-details {
      display: flex;
      flex-direction: column;
      gap: 16px;
      margin-bottom: 24px;
    }
    .detail-row {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      padding: 8px 12px;
      transition: background 0.2s ease;
      border-radius: 6px;
    }
    .detail-row:hover {
      background: #f9fafb;
    }
    .detail-label {
      font-weight: 600;
      color: #374151;
      min-width: 120px;
      font-size: 0.95rem;
    }
    .detail-value {
      color: #6b7280;
      font-size: 0.95rem;
      text-align: right;
      flex: 1;
      margin-left: 16px;
    }
    .attachment-link {
      color: #3b82f6;
      text-decoration: none;
      font-size: 0.9rem;
      transition: color 0.2s ease;
    }
    .attachment-link:hover {
      color: #2563eb;
      text-decoration: underline;
    }
  </style>

  <script>
    // openDeniedPopup / closeDeniedPopup functions remain exactly as before
    function openDeniedPopup(requestId) {
      const modal = document.getElementById('deniedPopupModal');
      modal.style.display = 'flex';
      setTimeout(() => { modal.classList.add('show'); }, 10);
      fetch('get_denied_request_details.php?id=' + requestId)
        .then(response => response.json())
        .then(data => {
          if (data && data.success) {
            const deceasedName = [data.first_name, data.middle_name, data.last_name, data.suffix]
              .filter(Boolean)
              .join(' ').replace(/ +/g, ' ').trim();
            document.getElementById('deniedPopupDeceased').textContent = deceasedName;
            document.getElementById('deniedPopupEmail').textContent = data.email || '';
            document.getElementById('deniedPopupType').textContent = data.type || '';
            document.getElementById('deniedPopupAge').textContent = data.age || '';
            document.getElementById('deniedPopupInformant').textContent = data.informant_name || '';
            document.getElementById('deniedPopupResidency').textContent = data.residency || '';
            document.getElementById('deniedPopupDOB').textContent = data.dob || '';
            document.getElementById('deniedPopupDOD').textContent = data.dod || '';
            document.getElementById('deniedPopupNicheId').textContent = data.niche_id || '';
            document.getElementById('deniedPopupAttachment').innerHTML = data.attachment_html || '';
            document.getElementById('deniedPopupInternmentDate').textContent = data.dateInternment || '';
            if (data.type && data.type.toLowerCase() === 'transfer') {
              document.getElementById('deniedPopupNicheIdRow').style.display = '';
            } else {
              document.getElementById('deniedPopupNicheIdRow').style.display = 'none';
            }
            if (data.type && data.type.toLowerCase() === 'relocate') {
              document.getElementById('deniedPopupCurrentNicheId').textContent = data.current_niche_id || '';
              document.getElementById('deniedPopupNewNicheId').textContent = data.new_niche_id || '';
              document.getElementById('deniedPopupCurrentNicheIdRow').style.display = '';
              document.getElementById('deniedPopupNewNicheIdRow').style.display = '';
            } else {
              document.getElementById('deniedPopupCurrentNicheIdRow').style.display = 'none';
              document.getElementById('deniedPopupNewNicheIdRow').style.display = 'none';
            }
          }
        });
    }
    function closeDeniedPopup() {
      const modal = document.getElementById('deniedPopupModal');
      modal.classList.remove('show');
      setTimeout(() => {
        modal.style.display = 'none';
      }, 300);
    }
    document.getElementById('deniedPopupModal').addEventListener('click', function(e) {
      if (e.target === this) {
        closeDeniedPopup();
      }
    });
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        const modal = document.getElementById('deniedPopupModal');
        if (modal.style.display === 'flex') {
          closeDeniedPopup();
        }
      }
    });
  </script>

  <script>
(function(){
  // Archive sub-tab switching (local to this partial)
  const subTabs = document.querySelectorAll('.archive-subtab');
  const contents = {
    clients: document.getElementById('archiveClientsTab'),
    records: document.getElementById('archiveRecordsTab'),
    requests: document.getElementById('archiveRequestsTab')
  };
  function activateArchiveTab(name) {
    subTabs.forEach(t => {
      if (t.dataset.archivetab === name) {
        t.classList.add('active');
        t.style.color = '#2d72d9';
        t.style.borderBottom = '2px solid #2d72d9';
      } else {
        t.classList.remove('active');
        t.style.color = '';
        t.style.borderBottom = '';
      }
    });
    Object.keys(contents).forEach(k => {
      if (!contents[k]) return;
      contents[k].style.display = (k === name) ? '' : 'none';
    });
    // trigger DataTable init for visible tab
    initArchiveDataTables(name);
  }

  subTabs.forEach(t => {
    t.addEventListener('click', function(){
      activateArchiveTab(this.dataset.archivetab);
    });
  });

  // DataTables init helpers (defensive)
  let clientsTableInstance = null;
  let recordsTableInstance = null;
  let requestsTableInstance = null;

  function safeInitDataTable(selector, opts) {
    try {
      if (!$.fn.DataTable) return null;
      const $table = $(selector);
      if ($table.length === 0) return null;
      // ensure table has thead and at least one data row (not the "no rows" placeholder)
      const hasThead = $table.find('thead tr th').length > 0;
      const hasRows = $table.find('tbody tr').length > 0 && !$table.find('tbody tr td').first().text().toLowerCase().includes('no');
      if (!hasThead || !hasRows) return null;
      if ($.fn.DataTable.isDataTable(selector)) {
        $table.DataTable().destroy();
      }
      return $table.DataTable($.extend({
        paging: true,
        searching: true,
        ordering: true,
        info: true,
        autoWidth: false,
        dom: 'lrtip'
      }, opts || {}));
    } catch (e) {
      console.warn('DataTables init suppressed:', e);
      return null;
    }
  }

  function initArchiveDataTables(activeTab) {
    // clients
    if (activeTab === 'clients') {
      if (clientsTableInstance) { try { clientsTableInstance.destroy(); } catch(e){} clientsTableInstance = null; }
      clientsTableInstance = safeInitDataTable('#archive-clients-table', {
        dom: 'lftip',
        language: {
          lengthMenu: 'Show _MENU_ entries',
          zeroRecords: 'No clients found',
          emptyTable: 'No clients available',
          info: 'Showing _START_ to _END_ of _TOTAL_'
        }
      });
      // wire search input to table search
      if (clientsTableInstance) {
        $('#archiveClientsSearchInput').off('keyup').on('keyup', function(){ clientsTableInstance.search(this.value).draw(); });
      }
    } else {
      if (clientsTableInstance && $.fn.DataTable.isDataTable('#archive-clients-table')) { try { $('#archive-clients-table').DataTable().destroy(); } catch(e){} clientsTableInstance = null; }
    }

    // records
    if (activeTab === 'records') {
      if (recordsTableInstance) { try { recordsTableInstance.destroy(); } catch(e){} recordsTableInstance = null; }
      // Initialize with drawCallback that moves info & paginate into the dedicated external wrapper for records
      recordsTableInstance = safeInitDataTable('#archiveRecordsTable', {
        // include 'l' so the length ("Show N entries") control is rendered
        dom: 'lrtip',
        drawCallback: function() {
          try {
            const tableWrapper = $('#archiveRecordsTable').closest('.archive-table-container');
            const externalWrapper = $('.archive-records-dt-wrapper');
            const info = $('#archiveRecordsTable_info').detach();
            const paginate = $('#archiveRecordsTable_paginate').detach();
            externalWrapper.empty().append(info).append(paginate);
          } catch (e) { /* fail silently */ }
        }
      });
      if (recordsTableInstance) {
        $('#archiveRecordsSearchInput').off('keyup').on('keyup', function(){ recordsTableInstance.search(this.value).draw(); });
      } else {
        // fallback: simple filter when DataTables not initialized
        $('#archiveRecordsSearchInput').off('keyup').on('keyup', function(){
          const filter = this.value.toLowerCase();
          $('#archiveRecordsTable tbody tr').each(function(){
            let found = false;
            $(this).find('td').each(function(){ if ($(this).text().toLowerCase().indexOf(filter) > -1) { found = true; return false; } });
            $(this).toggle(found);
          });
        });
      }
    } else {
      if (recordsTableInstance && $.fn.DataTable.isDataTable('#archiveRecordsTable')) { try { $('#archiveRecordsTable').DataTable().destroy(); } catch(e){} recordsTableInstance = null; }
    }

    // requests
    if (activeTab === 'requests') {
      if (requestsTableInstance) { try { requestsTableInstance.destroy(); } catch(e){} requestsTableInstance = null; }
      requestsTableInstance = safeInitDataTable('#archiveRequestsTable', {});
      if (requestsTableInstance) {
        $('#archiveRequestSearchInput').off('keyup').on('keyup', function(){ requestsTableInstance.search(this.value).draw(); });
      }
    } else {
      if (requestsTableInstance && $.fn.DataTable.isDataTable('#archiveRequestsTable')) { try { $('#archiveRequestsTable').DataTable().destroy(); } catch(e){} requestsTableInstance = null; }
    }
  }

  // Activate default tab on load (clients)
  activateArchiveTab('clients');

  // Restore modal logic for Archive Clients (hook restore buttons delegated)
  let restoreTargetRow = null;
  let restoreTargetEmail = null;

  // Delegate restore button clicks for dynamic table rows
  document.addEventListener('click', function(e){
    const btn = e.target.closest('.restore-btn');
    if (!btn) return;
    e.preventDefault();
    const row = btn.closest('tr');
    if (!row) return;
    const emailCell = row.querySelector('td:nth-child(2)');
    const email = emailCell ? emailCell.textContent.trim() : '';
    restoreTargetRow = row;
    restoreTargetEmail = email;
    const modal = document.getElementById('restoreModal');
    if (modal) modal.style.display = 'flex';
  });

  const modalCancel = document.getElementById('modalCancelRestoreBtn');
  if (modalCancel) modalCancel.addEventListener('click', function(){
    const modal = document.getElementById('restoreModal');
    if (modal) modal.style.display = 'none';
    restoreTargetRow = null; restoreTargetEmail = null;
  });

  const modalRestoreBtn = document.getElementById('modalRestoreBtn');
  if (modalRestoreBtn) {
    modalRestoreBtn.addEventListener('click', function(){
      if (!restoreTargetEmail || !restoreTargetRow) return;
      const btn = this;
      btn.disabled = true;
      btn.textContent = 'Restoring...';
      modalCancel.disabled = true;
      // post to server to restore
      $.post('restore_client.php', { email: restoreTargetEmail }, function(response){
        try {
          if (response && response.success) {
            // remove row from table or DataTable
            try {
              if ($.fn.DataTable.isDataTable('#archive-clients-table')) {
                const tbl = $('#archive-clients-table').DataTable();
                tbl.row($(restoreTargetRow)).remove().draw();
              } else {
                restoreTargetRow.parentNode.removeChild(restoreTargetRow);
              }
            } catch(e){}
            // show success notification
            const notif = document.getElementById('restoreSuccessNotification');
            if (notif) {
              notif.querySelector('span').innerHTML = '<i class="fas fa-check-circle" style="margin-right:8px;"></i>Client successfully restored';
              notif.style.display = 'flex';
              setTimeout(()=>{ notif.style.display='none'; }, 3000);
            }
            const modal = document.getElementById('restoreModal');
            if (modal) modal.style.display = 'none';
          } else {
            alert('Failed to restore client.');
          }
        } finally {
          btn.disabled = false;
          btn.textContent = 'Restore';
          if (modalCancel) modalCancel.disabled = false;
          restoreTargetRow = null; restoreTargetEmail = null;
        }
      }, 'json').fail(function(){ alert('An error occurred. Please try again.'); btn.disabled=false; btn.textContent='Restore'; if (modalCancel) modalCancel.disabled=false; restoreTargetRow=null; restoreTargetEmail=null; });
    });
  }

  // Close notification UI when clicking outside restore modal
  document.addEventListener('click', function(e){
    const modal = document.getElementById('restoreModal');
    if (!modal) return;
    if (modal.style.display === 'flex' && e.target === modal) {
      modal.style.display = 'none';
      restoreTargetRow = null; restoreTargetEmail = null;
    }
  });
})();
  </script>
</div>
