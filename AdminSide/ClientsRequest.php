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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/Clients.css">
  <link rel="stylesheet" href="../css/sidebar.css">
  <!-- DataTables CSS -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
</head>
<body>
  <!-- Sidebar -->
  <?php include '../Includes/sidebar.php'; ?>

  <!-- Main Content -->
  <main class="main-content">
    <div class="clients-header">
      <h1>Client Requests</h1>
      <p class="subtitle">View all Clients Information.</p>
    </div>
    <div class="clients-tabs-bar">
      <div class="clients-tabs">
        <span class="clients-tab-title active" id="tab-clients-request" onclick="showTab('clients-request')">Clients Request</span>
        <span class="clients-tab-title" id="tab-accepted-request" onclick="showTab('accepted-request')">Accepted Requests</span>
        <span class="clients-tab-title" id="tab-assessment-fees" onclick="showTab('assessment-fees')">Assessment of Fees</span>
      </div>
    </div>
    <div id="clients-request-section">
      <div class="clients-actions">
        <div class="search-container">
          <i class="fas fa-search"></i>
          <input type="text" placeholder="Search Clients" id="clients-search-input">
        </div>
        <div class="actions-right">
          <div class="date-filter-container">
            <input type="date" id="clients-request-date-filter" class="date-input">
            <button type="button" id="clear-clients-request-date-filter" class="clear-date-btn" style="display:none;">
              <i class="fas fa-times"></i>
            </button>
          </div>
        </div>
      </div>
      
      <!-- Show entries dropdown -->
      <div style="margin-bottom: 16px;">
        <div class="dataTables_length">
          <label>Show <select name="clients-request-table_length"><option value="10">10</option><option value="25">25</option><option value="50">50</option><option value="100">100</option></select> entries</label>
        </div>
      </div>
      
      <?php
      include_once '../Includes/db.php';
      // Fetch all client requests with user info including profile_picture
      $sql = "SELECT cr.*, u.first_name, u.last_name, u.email, u.profile_picture FROM client_requests cr JOIN users u ON cr.user_id = u.id ORDER BY cr.created_at DESC";
      $result = $conn->query($sql);
      ?>
      <div class="clients-table-container">
        <table class="clients-table" id="clients-request-table">
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
          <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
              <?php while ($row = $result->fetch_assoc()): ?>
                <?php
                  $firstName = htmlspecialchars($row['first_name'] ?? '');
                  $lastName = htmlspecialchars($row['last_name'] ?? '');
                  $name = $firstName . ' ' . $lastName;
                  $email = htmlspecialchars($row['email'] ?? '');
                  $requestDate = htmlspecialchars($row['created_at'] ? date('Y-m-d', strtotime($row['created_at'])) : 'N/A');
                  $profilePicture = htmlspecialchars($row['profile_picture'] ?? '');
                  
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
                ?>
                <tr data-request-date='<?php echo $requestDate; ?>'>
                  <td>
                    <?php echo $avatarHtml; ?>
                    <span class="client-name" style="vertical-align:middle; margin-left:4px; display:inline-block;"><?php echo $name; ?></span>
                  </td>
                  <td><?php echo $email; ?></td>
                  <td><?php echo htmlspecialchars($row['type']); ?></td>
                  <td><?php echo $requestDate; ?></td>
                  <td><span class="status-badge status-pending">Pending</span></td>
                  <td><button class="view-btn" onclick="openPopup(<?php echo $row['id']; ?>, 'pending')">View</button></td>
                </tr>
              <?php endwhile; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      
      <!-- Move pagination controls to bottom exactly like Clients.php -->
      <div class="dataTables_wrapper">
      </div>
    </div>
    <div id="accepted-request-section" style="display:none;">
      <div class="clients-actions">
        <div class="search-container">
          <i class="fas fa-search"></i>
          <input type="text" placeholder="Search Accepted Clients" id="accepted-search-input">
        </div>
        <div class="actions-right">
          <div class="date-filter-container">
            <input type="date" id="accepted-request-date-filter" class="date-input">
            <button type="button" id="clear-accepted-request-date-filter" class="clear-date-btn" style="display:none;">
              <i class="fas fa-times"></i>
            </button>
          </div>
        </div>
      </div>
      
      <!-- Show entries dropdown for accepted requests -->
      <div style="margin-bottom: 16px;">
        <div class="dataTables_length">
          <label>Show <select name="accepted-table_length"><option value="10">10</option><option value="25">25</option><option value="50">50</option><option value="100">100</option></select> entries</label>
        </div>
      </div>
      
      <?php
      // Fetch all accepted requests with user info including profile_picture
      $sql_accepted = "SELECT ar.*, u.first_name AS user_first_name, u.last_name AS user_last_name, u.email, u.profile_picture FROM accepted_request ar JOIN users u ON ar.user_id = u.id ORDER BY ar.created_at DESC";
      $result_accepted = $conn->query($sql_accepted);
      ?>
      <div class="clients-table-container">
        <table class="clients-table" id="accepted-table">
          <thead>
            <tr>
              <th>Client Name</th>
              <th>Email</th>
              <th>Type</th>
              <th>Accepted Date</th>
              <th>Status</th>
              <th>Details</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($result_accepted && $result_accepted->num_rows > 0): ?>
              <?php while ($row = $result_accepted->fetch_assoc()): ?>
                <?php
                  $firstName = htmlspecialchars($row['user_first_name'] ?? '');
                  $lastName = htmlspecialchars($row['user_last_name'] ?? '');
                  $name = $firstName . ' ' . $lastName;
                  $email = htmlspecialchars($row['email'] ?? '');
                  $acceptedDate = htmlspecialchars($row['created_at'] ? date('Y-m-d', strtotime($row['created_at'])) : 'N/A');
                  $profilePicture = htmlspecialchars($row['profile_picture'] ?? '');
                  
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
                ?>
                <tr data-accepted-date='<?php echo $acceptedDate; ?>'>
                  <td>
                    <?php echo $avatarHtml; ?>
                    <span class="client-name" style="vertical-align:middle; margin-left:4px; display:inline-block;"><?php echo $name; ?></span>
                  </td>
                  <td><?php echo $email; ?></td>
                  <td><?php echo htmlspecialchars($row['type']); ?></td>
                  <td><?php echo $acceptedDate; ?></td>
                  <td><span class="status-badge status-accepted">Accepted</span></td>
                  <td><button class="view-btn" onclick="openPopup(<?php echo $row['id']; ?>, 'accepted')">View</button></td>
                </tr>
              <?php endwhile; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      
      <!-- Move pagination controls to bottom exactly like Clients.php -->
      <div class="dataTables_wrapper">
      </div>
    </div>
    <div id="assessment-fees-section" style="display:none;">
      <div class="assessment-fees-container" style="max-width:600px;margin:0 auto;padding:32px 0;">
        <div style="text-align: center; color: #888;">
          <h2>Assessment of Fees</h2>
          <p>Nothing to Assess, go to Accepted Client Request to get started.</p>
        </div>
      </div>
    </div>
    <!-- Popup Modal -->
    <div id="popupModal" class="popup-modal" style="display:none;">
      <div class="popup-content">
        <div class="popup-header">
          <h3 class="popup-title">Request Details</h3>
          <button class="close-btn" onclick="closePopup()">&times;</button>
        </div>
        <div class="popup-details">
          <div class="detail-row">
            <span class="detail-label">Informant Name:</span>
            <span class="detail-value" id="popupInformant"></span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Email:</span>
            <span class="detail-value" id="popupEmail"></span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Type:</span>
            <span class="detail-value" id="popupType"></span>
          </div>
          <div class="detail-row" id="popupNicheIdRow" style="display:none;">
            <span class="detail-label">Niche ID:</span>
            <span class="detail-value" id="popupNicheId"></span>
          </div>
          <div class="detail-row" id="popupDeceasedRow" style="display:none;">
            <span class="detail-label">Name of Deceased:</span>
            <span class="detail-value" id="popupDeceased"></span>
          </div>
          <!-- Additional fields for full deceased info -->
          <div class="detail-row" id="popupResidencyRow" style="display:none;">
            <span class="detail-label">Residency:</span>
            <span class="detail-value" id="popupResidency"></span>
          </div>
          <div class="detail-row" id="popupDOBRow" style="display:none;">
            <span class="detail-label">Date of Birth:</span>
            <span class="detail-value" id="popupDOB"></span>
          </div>
          <div class="detail-row" id="popupDODRow" style="display:none;">
            <span class="detail-label">Date of Death:</span>
            <span class="detail-value" id="popupDOD"></span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Age:</span>
            <span class="detail-value" id="popupAge"></span>
          </div>
          <div class="detail-row" id="popupCurrentNicheIdRow" style="display:none;">
            <span class="detail-label">Current Niche ID:</span>
            <span class="detail-value" id="popupCurrentNicheId"></span>
          </div>
          <div class="detail-row" id="popupNewNicheIdRow" style="display:none;">
            <span class="detail-label">New Niche Location:</span>
            <span class="detail-value" id="popupNewNicheId"></span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Attachments:</span>
            <div class="detail-value" id="popupAttachment"></div>
          </div>
        </div>
        <div class="popup-actions">
          <button class="accept-btn" onclick="acceptRequest()">Accept</button>
          <button class="deny-btn" onclick="denyRequest()">Deny</button>
          <button class="go-payment-btn" style="display:none;" onclick="goToAssessment()">Assess</button>
        </div>
      </div>
    </div>
    <!-- Confirmation Modal -->
    <div id="actionConfirmModal" style="display:none;align-items:center;justify-content:center;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.35);z-index:9999;">
      <div style="background:#fff;border-radius:16px;box-shadow:0 8px 32px rgba(0,0,0,0.15);padding:32px 32px 28px 32px;max-width:420px;width:100%;text-align:center;position:relative;">
        <div style="display:flex;flex-direction:column;align-items:center;">
          <div id="actionConfirmIconContainer" style="border-radius:50%;width:56px;height:56px;display:flex;align-items:center;justify-content:center;margin-bottom:16px;background:#eafaf1;">
            <span id="actionConfirmIcon" style="font-size:2.1rem;"></span>
          </div>
          <h2 id="actionConfirmTitle" style="font-size:1.35rem;font-weight:700;margin-bottom:12px;color:#222;">Confirm Action</h2>
          <div id="actionConfirmText" style="font-size:1.05rem;color:#444;margin-bottom:24px;line-height:1.5;"></div>
          <div style="display:flex;gap:14px;justify-content:center;">
            <button id="modalActionConfirmBtn" style="background:#27ae60;color:#fff;font-weight:600;padding:10px 32px;border:none;border-radius:8px;font-size:1rem;cursor:pointer;transition:background 0.2s;">Confirm</button>
            <button id="modalActionCancelBtn" style="background:#bdbdbd;color:#fff;font-weight:500;padding:10px 32px;border:none;border-radius:8px;font-size:1rem;cursor:pointer;transition:background 0.2s;">Cancel</button>
          </div>
        </div>
      </div>
    </div>
    <!-- Success Notification -->
    <div id="actionSuccessNotification" style="display:none;position:fixed;top:32px;right:32px;z-index:9999;background:#fff;border-radius:12px;box-shadow:0 4px 16px rgba(0,0,0,0.12);padding:18px 32px;min-width:260px;max-width:350px;align-items:center;">
      <div style="display:flex;align-items:center;gap:12px;">
        <span style="color:#27ae60;font-size:1.5rem;"><i class="fas fa-check-circle"></i></span>
        <span id="actionNotificationText" style="font-size:1.08rem;color:#333;font-weight:500;"></span>
        <button id="closeActionNotificationBtn" style="background:none;border:none;color:#888;font-size:1.2rem;cursor:pointer;margin-left:auto;">&times;</button>
      </div>
    </div>
    <style>
      /* Popup Modal Styles */
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
        max-height: 80vh;
        overflow-y: auto;
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
        max-height: 60vh;
        overflow-y: auto;
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
      .popup-actions {
        display: flex;
        gap: 12px;
        justify-content: flex-end;
        margin-top: 24px;
        padding-top: 16px;
        border-top: 1px solid #e5e7eb;
      }
      .accept-btn, .deny-btn, .go-payment-btn {
        padding: 12px 24px;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 500;
        cursor: pointer;
        border: none;
        transition: background 0.2s;
      }
      .accept-btn {
        background: #27ae60;
        color: #fff;
      }
      .accept-btn:hover {
        background: #219150;
      }
      .deny-btn {
        background: #e4e9ee;
        color: #2d3a4a;
      }
      .deny-btn:hover {
        background: #d3dbe2;
      }
      .go-payment-btn {
        background: #27ae60;
        color: #fff;
        min-width: 120px;
      }
      .go-payment-btn:hover {
        background: #219150;
      }
      .clients-tabs-bar {
        border-bottom: 1px solid #e0e0e0;
        margin-bottom: 8px;
      }
      .clients-tabs {
        display: flex;
        gap: 32px;
        align-items: center;
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
      }
      .clients-tab-title.active {
        border-bottom: 2.5px solid #506C84;
        color: #506C84;
        opacity: 1;
      }
      .status-badge.status-accepted {
        background: #a6f4c5;
        color: #22c55e;
        padding: 4px 12px;
        border-radius: 6px;
        font-weight: 600;
        font-size: 0.98rem;
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

      /* Modal Styles */
      .modal-overlay {
        position: fixed;
        z-index: 10000;
        left: 0; top: 0; width: 100vw; height: 100vh;
        background: rgba(0,0,0,0.7);
        display: flex;
        align-items: center;
        justify-content: center;
      }
      .modal-content {
        background: #fff;
        padding: 24px;
        border-radius: 12px;
        width: 400px;
        max-width: 90vw;
        box-shadow: 0 8px 32px rgba(0,0,0,0.15);
        position: relative;
      }
      .modal-header {
        display: flex;
        align-items: center;
        margin-bottom: 16px;
      }
      .modal-header i {
        font-size: 2rem;
        margin-right: 12px;
      }
      .modal-header h2 {
        font-size: 1.5rem;
        margin: 0;
        color: #333;
      }
      .modal-body {
        margin: 12px 0 16px 0;
        color: #555;
        font-size: 1rem;
      }
      .modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
      }
      .modal-delete-btn, .modal-cancel-btn {
        padding: 10px 20px;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 500;
        cursor: pointer;
        border: none;
        transition: background 0.2s;
      }
      .modal-delete-btn {
        background: #e74c3c;
        color: #fff;
      }
      .modal-delete-btn:hover {
        background: #c0392b;
      }
      .modal-cancel-btn {
        background: #f0f0f0;
        color: #333;
      }
      .modal-cancel-btn:hover {
        background: #e0e0e0;
      }
      /* Success Notification Styles */
      #actionSuccessNotification {
        display: none;
        position: fixed;
        top: 32px;
        right: 32px;
        z-index: 10000;
        background: #2ecc71;
        color: #fff;
        padding: 18px 32px;
        border-radius: 8px;
        box-shadow: 0 4px 16px rgba(46,204,113,0.15);
        font-size: 1.1rem;
        font-weight: 500;
        align-items: center;
        gap: 16px;
        min-width: 220px;
      }
      #actionSuccessNotification span {
        display: flex;
        align-items: center;
      }
      #actionSuccessNotification i {
        margin-right: 8px;
      }
    </style>
    <!-- DataTables JS -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script>
      // Initialize DataTables
      let clientsRequestTable, acceptedTable;
      
      document.addEventListener('DOMContentLoaded', function() {
        // Destroy existing DataTables if they exist
        if ($.fn.DataTable.isDataTable('#clients-request-table')) {
          $('#clients-request-table').DataTable().destroy();
        }
        if ($.fn.DataTable.isDataTable('#accepted-table')) {
          $('#accepted-table').DataTable().destroy();
        }

        // Initialize DataTables for both tables
        try {
          clientsRequestTable = $('#clients-request-table').DataTable({
            "paging": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "dom": 'rtip',
            "pageLength": 10,
            "language": {
              "emptyTable": "No client requests found.",
              "zeroRecords": "No matching records found",
              "info": "Showing _START_ to _END_ of _TOTAL_ entries",
              "infoEmpty": "Showing 0 to 0 of 0 entries",
              "infoFiltered": "(filtered from _MAX_ total entries)"
            },
            "columnDefs": [
              { "orderable": false, "targets": [5] }
            ],
            "drawCallback": function() {
              const tableWrapper = $('#clients-request-table').closest('.clients-table-container');
              const externalWrapper = tableWrapper.next('.dataTables_wrapper');
              
              const info = $('#clients-request-table_info').detach();
              const paginate = $('#clients-request-table_paginate').detach();
              
              externalWrapper.empty().append(info).append(paginate);
            }
          });
        } catch (e) {
          console.error('Error initializing clients request table:', e);
        }

        try {
          acceptedTable = $('#accepted-table').DataTable({
            "paging": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "dom": 'rtip',
            "pageLength": 10,
            "language": {
              "emptyTable": "No accepted requests found.",
              "zeroRecords": "No matching records found",
              "info": "Showing _START_ to _END_ of _TOTAL_ entries",
              "infoEmpty": "Showing 0 to 0 of 0 entries",
              "infoFiltered": "(filtered from _MAX_ total entries)"
            },
            "columnDefs": [
              { "orderable": false, "targets": [5] }
            ],
            "drawCallback": function() {
              const tableWrapper = $('#accepted-table').closest('.clients-table-container');
              const externalWrapper = tableWrapper.next('.dataTables_wrapper');
              
              const info = $('#accepted-table_info').detach();
              const paginate = $('#accepted-table_paginate').detach();
              
              externalWrapper.empty().append(info).append(paginate);
            }
          });
        } catch (e) {
          console.error('Error initializing accepted table:', e);
        }

        // Connect search inputs with error handling
        const clientsSearchInput = document.getElementById('clients-search-input');
        if (clientsSearchInput) {
          clientsSearchInput.addEventListener('keyup', function() {
            try {
              if (clientsRequestTable) {
                clientsRequestTable.search(this.value).draw();
              }
            } catch (e) {
              console.error('Error in clients search:', e);
            }
          });
        }

        const acceptedSearchInput = document.getElementById('accepted-search-input');
        if (acceptedSearchInput) {
          acceptedSearchInput.addEventListener('keyup', function() {
            try {
              if (acceptedTable) {
                acceptedTable.search(this.value).draw();
              }
            } catch (e) {
              console.error('Error in accepted search:', e);
            }
          });
        }

        // Date filters with error handling
        const clientsRequestDateInput = document.getElementById('clients-request-date-filter');
        const clearClientsRequestDateBtn = document.getElementById('clear-clients-request-date-filter');

        if (clientsRequestDateInput && clearClientsRequestDateBtn) {
          clientsRequestDateInput.addEventListener('change', function() {
            const selectedDate = this.value;
            if (selectedDate) {
              clearClientsRequestDateBtn.style.display = 'block';
              try {
                if (clientsRequestTable) {
                  clientsRequestTable.column(3).search(selectedDate, false, false).draw();
                }
              } catch (e) {
                console.error('Error in date filter:', e);
              }
            } else {
              clearClientsRequestDateBtn.style.display = 'none';
              try {
                if (clientsRequestTable) {
                  clientsRequestTable.column(3).search('').draw();
                }
              } catch (e) {
                console.error('Error clearing date filter:', e);
              }
            }
          });

          clearClientsRequestDateBtn.addEventListener('click', function() {
            clientsRequestDateInput.value = '';
            this.style.display = 'none';
            try {
              if (clientsRequestTable) {
                clientsRequestTable.column(3).search('').draw();
              }
            } catch (e) {
              console.error('Error clearing date filter:', e);
            }
          });
        }

        const acceptedRequestDateInput = document.getElementById('accepted-request-date-filter');
        const clearAcceptedRequestDateBtn = document.getElementById('clear-accepted-request-date-filter');

        if (acceptedRequestDateInput && clearAcceptedRequestDateBtn) {
          acceptedRequestDateInput.addEventListener('change', function() {
            const selectedDate = this.value;
            if (selectedDate) {
              clearAcceptedRequestDateBtn.style.display = 'block';
              try {
                if (acceptedTable) {
                  acceptedTable.column(3).search(selectedDate, false, false).draw();
                }
              } catch (e) {
                console.error('Error in accepted date filter:', e);
              }
            } else {
              clearAcceptedRequestDateBtn.style.display = 'none';
              try {
                if (acceptedTable) {
                  acceptedTable.column(3).search('').draw();
                }
              } catch (e) {
                console.error('Error clearing accepted date filter:', e);
              }
            }
          });

          clearAcceptedRequestDateBtn.addEventListener('click', function() {
            acceptedRequestDateInput.value = '';
            this.style.display = 'none';
            try {
              if (acceptedTable) {
                acceptedTable.column(3).search('').draw();
              }
            } catch (e) {
              console.error('Error clearing accepted date filter:', e);
            }
          });
        }

        // Connect entries dropdowns
        const clientsLengthSelect = document.querySelector('select[name="clients-request-table_length"]');
        if (clientsLengthSelect) {
          clientsLengthSelect.addEventListener('change', function() {
            try {
              if (clientsRequestTable) {
                clientsRequestTable.page.len(parseInt(this.value)).draw();
              }
            } catch (e) {
              console.error('Error changing page length:', e);
            }
          });
        }

        const acceptedLengthSelect = document.querySelector('select[name="accepted-table_length"]');
        if (acceptedLengthSelect) {
          acceptedLengthSelect.addEventListener('change', function() {
            try {
              if (acceptedTable) {
                acceptedTable.page.len(parseInt(this.value)).draw();
              }
            } catch (e) {
              console.error('Error changing accepted page length:', e);
            }
          });
        }
      });

      function showTab(tab) {
        document.getElementById('clients-request-section').style.display = (tab === 'clients-request') ? '' : 'none';
        document.getElementById('accepted-request-section').style.display = (tab === 'accepted-request') ? '' : 'none';
        document.getElementById('assessment-fees-section').style.display = (tab === 'assessment-fees') ? '' : 'none';
        document.getElementById('tab-clients-request').classList.toggle('active', tab === 'clients-request');
        document.getElementById('tab-accepted-request').classList.toggle('active', tab === 'accepted-request');
        document.getElementById('tab-assessment-fees').classList.toggle('active', tab === 'assessment-fees');
      }
      
      function openPopup(requestId, type) {
        const modal = document.getElementById('popupModal');
        modal.style.display = 'flex';
        setTimeout(() => { modal.classList.add('show'); }, 10);
        window.currentRequestId = requestId;
        window.currentRequestType = type;
        let url = (type === 'accepted') ? 'get_accepted_request_details.php?id=' + requestId : 'get_request_details.php?id=' + requestId;
        fetch(url)
          .then(response => response.json())
          .then(data => {
            if (data && data.success) {
              document.getElementById('popupEmail').textContent = data.email;
              document.getElementById('popupType').textContent = data.type;
              // Calculate accurate age from dob and dod
              let age = '';
              if (data.dob && data.dod) {
                const dob = new Date(data.dob);
                const dod = new Date(data.dod);
                let years = dod.getFullYear() - dob.getFullYear();
                let m = dod.getMonth() - dob.getMonth();
                if (m < 0 || (m === 0 && dod.getDate() < dob.getDate())) {
                  years--;
                }
                age = years;
              } else {
                age = data.age || '';
              }
              document.getElementById('popupAge').textContent = age;
              document.getElementById('popupInformant').textContent = data.informant_name;
              // Show only the backend formatted deceased name
              document.getElementById('popupDeceased').textContent = (data.deceased_name || '').trim();
              document.getElementById('popupAttachment').innerHTML = data.attachment_html;
              document.getElementById('popupNicheId').textContent = data.niche_id;
              
              // New fields for Relocate
              if (data.type === 'Relocate') {
                document.getElementById('popupCurrentNicheId').textContent = data.current_niche_id || '';
                document.getElementById('popupNewNicheId').textContent = data.new_niche_id || '';
                document.getElementById('popupCurrentNicheIdRow').style.display = '';
                document.getElementById('popupNewNicheIdRow').style.display = '';
              } else {
                document.getElementById('popupCurrentNicheIdRow').style.display = 'none';
                document.getElementById('popupNewNicheIdRow').style.display = 'none';
              }
              
              // Show/hide deceased row
              document.getElementById('popupDeceasedRow').style.display = (data.deceased_name ? '' : 'none');
              // Hide middle name and suffix rows always
              if (document.getElementById('popupMiddleNameRow')) {
                document.getElementById('popupMiddleNameRow').style.display = 'none';
              }
              if (document.getElementById('popupSuffixRow')) {
                document.getElementById('popupSuffixRow').style.display = 'none';
              }
              if (document.getElementById('popupResidency')) {
                document.getElementById('popupResidency').textContent = data.residency || '';
                document.getElementById('popupResidencyRow').style.display = data.residency ? '' : 'none';
              }
              if (document.getElementById('popupDOB')) {
                document.getElementById('popupDOB').textContent = data.dob || '';
                document.getElementById('popupDOBRow').style.display = data.dob ? '' : 'none';
              }
              if (document.getElementById('popupDOD')) {
                document.getElementById('popupDOD').textContent = data.dod || '';
                document.getElementById('popupDODRow').style.display = data.dod ? '' : 'none';
              }
              if (data.type === 'Transfer' || data.type === 'Exhumation') {
                document.getElementById('popupNicheIdRow').style.display = '';
              } else {
                document.getElementById('popupNicheIdRow').style.display = 'none';
              }
              document.querySelector('.accept-btn').style.display = (type === 'accepted') ? 'none' : '';
              document.querySelector('.deny-btn').style.display = (type === 'accepted') ? 'none' : '';
              if (type === 'accepted') {
                document.querySelector('.go-payment-btn').style.display = '';
                window.currentNicheId = data.niche_id;
                window.currentInformant = data.informant_name;
              } else {
                document.querySelector('.go-payment-btn').style.display = 'none';
              }
            }
          });
      }
      
      function closePopup() {
        const modal = document.getElementById('popupModal');
        modal.classList.remove('show');
        
        // Hide modal after animation completes
        setTimeout(() => {
          modal.style.display = 'none';
        }, 300);
      }
      
      // Close popup when clicking outside
      document.getElementById('popupModal').addEventListener('click', function(e) {
        if (e.target === this) {
          closePopup();
        }
      });

      // Close popup with Escape key
      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
          const modal = document.getElementById('popupModal');
          if (modal.style.display === 'flex') {
            closePopup();
          }
        }
      });
      
      let pendingAction = null;
      function showActionConfirmModal(actionType) {
        const modal = document.getElementById('actionConfirmModal');
        const text = document.getElementById('actionConfirmText');
        const iconContainer = document.getElementById('actionConfirmIconContainer');
        const iconSpan = document.getElementById('actionConfirmIcon');
        const title = document.getElementById('actionConfirmTitle');
        const confirmBtn = document.getElementById('modalActionConfirmBtn');
        if (actionType === 'accept') {
          text.innerHTML = 'Are you sure you want to <b>accept</b> this request?<br>This action cannot be undone.';
          title.textContent = 'Confirm Accept';
          iconContainer.style.background = '#eafaf1';
          iconSpan.innerHTML = '<i class="fas fa-check-circle" style="color:#27ae60;"></i>';
          confirmBtn.style.background = '#27ae60';
          confirmBtn.style.color = '#fff';
          pendingAction = 'accept';
        } else if (actionType === 'deny') {
          text.innerHTML = 'Are you sure you want to <b>deny</b> this request?<br>This action cannot be undone.';
          title.textContent = 'Confirm Deny';
          iconContainer.style.background = '#ffeaea';
          iconSpan.innerHTML = '<i class="fas fa-exclamation-triangle" style="color:#e74c3c;"></i>';
          confirmBtn.style.background = '#e74c3c';
          confirmBtn.style.color = '#fff';
          pendingAction = 'deny';
        }
        modal.style.display = 'flex';
      }
      document.addEventListener('DOMContentLoaded', function() {
        document.querySelector('.accept-btn').onclick = function(e) {
          e.preventDefault();
          showActionConfirmModal('accept');
        };
        document.querySelector('.deny-btn').onclick = function(e) {
          e.preventDefault();
          showActionConfirmModal('deny');
        };
        document.getElementById('modalActionCancelBtn').onclick = function() {
          document.getElementById('actionConfirmModal').style.display = 'none';
          pendingAction = null;
        };
        document.getElementById('modalActionConfirmBtn').onclick = function() {
          if (pendingAction === 'accept') {
            performAcceptRequest();
          } else if (pendingAction === 'deny') {
            performDenyRequest();
          }
          document.getElementById('actionConfirmModal').style.display = 'none';
          pendingAction = null;
        };
        document.getElementById('closeActionNotificationBtn').onclick = function() {
          document.getElementById('actionSuccessNotification').style.display = 'none';
        };
      });
      function showActionSuccessNotification(message) {
        const notification = document.getElementById('actionSuccessNotification');
        const notificationText = document.getElementById('actionNotificationText');
        notificationText.textContent = message;
        notification.style.display = 'flex';
        setTimeout(function() { notification.style.display = 'none'; }, 3000);
      }
      function performAcceptRequest() {
        if (window.currentRequestId) {
          fetch('accept_request.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id=' + encodeURIComponent(window.currentRequestId)
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              closePopup();
              showActionSuccessNotification('Request accepted successfully.');
              setTimeout(function() { location.reload(); }, 1200);
            } else {
              alert('Failed to accept request: ' + (data.message || 'Unknown error'));
            }
          });
        }
      }
      function performDenyRequest() {
        if (window.currentRequestId) {
          fetch('deny_request.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id=' + encodeURIComponent(window.currentRequestId)
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              closePopup();
              showActionSuccessNotification('Request denied successfully.');
              setTimeout(function() { location.reload(); }, 1200);
            } else {
              alert('Failed to deny request: ' + (data.message || 'Unknown error'));
            }
          })
          .catch(function(error) {
            alert('Network error: ' + error);
            console.error('Network error:', error);
          });
        } else {
          alert('No request ID found.');
          console.error('No request ID found for denyRequest');
        }
      }
      
      function goToAssessment() {
        // List of barangays in Padre Garcia, Batangas
        const padreGarciaBarangays = [
          'Banaba, Padre Garcia, Batangas',
          'Banaybanay, Padre Garcia, Batangas',
          'Bawi, Padre Garcia, Batangas',
          'Bukal, Padre Garcia, Batangas',
          'Castillo, Padre Garcia, Batangas',
          'Cawongan, Padre Garcia, Batangas',
          'Manggas, Padre Garcia, Batangas',
          'Maugat East, Padre Garcia, Batangas',
          'Maugat West, Padre Garcia, Batangas',
          'Pansol, Padre Garcia, Batangas',
          'Payapa, Padre Garcia, Batangas',
          'Poblacion, Padre Garcia, Batangas',
          'Quilo-quilo North, Padre Garcia, Batangas',
          'Quilo-quilo South, Padre Garcia, Batangas',
          'San Felipe, Padre Garcia, Batangas',
          'San Miguel, Padre Garcia, Batangas',
          'Tamak, Padre Garcia, Batangas',
          'Tangob, Padre Garcia, Batangas'
        ];
        // Fetch the details again to ensure we have the latest data
        let url = (window.currentRequestType === 'accepted') ? 'get_accepted_request_details.php?id=' + window.currentRequestId : 'get_request_details.php?id=' + window.currentRequestId;
        fetch(url)
          .then(response => response.json())
          .then(data => {
            if (data && data.success) {
              let summaryHtml = '';
              let expirationInfo = '';
              let totalFee = 0;
              let renewalFee = 5000;
              let formHtml = '';
              // Relocate logic
              if (data.type === 'Relocate') {
                let openingFee = 1000;
                let remainsCount = parseInt(data.remains_count) || 1;
                let relocationFee = 500 * remainsCount;
                totalFee = openingFee + relocationFee;
                summaryHtml = `
                  <div class=\"detail-row\"><span class=\"detail-label\">Opening Fee:</span><span class=\"detail-value\">₱ 1,000.00</span></div>
                  <div class=\"detail-row\"><span class=\"detail-label\">Relocation Fee:</span><span class=\"detail-value\">₱ 500.00 x ${remainsCount} = ₱ ${(relocationFee).toLocaleString('en-US', {minimumFractionDigits:2})}</span></div>
                  <div class=\"detail-row\"><span class=\"detail-label\">Total Fee:</span><span class=\"detail-value\">₱ ${totalFee.toLocaleString('en-US', {minimumFractionDigits:2})}</span></div>
                `;
              } else {
                // Calculate total fee and expiration date for other types
                let discountNote = '';
                let babyNote = '';
                let isBaby = false;
                let age = parseInt(data.age);
                if (data.type === 'New') {
                  // Check if baby/newborn (2 years old or below)
                  if (!isNaN(age) && age <= 2) {
                    totalFee = 5000;
                    babyNote = ' (Newborn/Baby Rate)';
                    discountNote = '';
                    isBaby = true;
                  } else {
                    let residency = (data.residency || '').trim();
                    let isPadreGarcia = padreGarciaBarangays.some(function(bgy) {
                      return residency.toLowerCase() === bgy.toLowerCase();
                    });
                    if (isPadreGarcia) {
                      totalFee = 10000;
                      discountNote = ' (Graciano discount applied)';
                    } else {
                      totalFee = 15000;
                    }
                  }
                }
                // Calculate expiration date (5 years from date of death)
                let expirationDate = '';
                if (data.dod) {
                  let dod = new Date(data.dod);
                  let exp = new Date(dod);
                  exp.setFullYear(exp.getFullYear() + 5);
                  const months = ['JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC'];
                  let day = String(exp.getDate()).padStart(2, '0');
                  let month = months[exp.getMonth()];
                  let year = exp.getFullYear();
                  expirationDate = `${day}-${month}-${year}`;
                }
                expirationInfo = expirationDate ? `<div class=\"detail-row\"><span class=\"detail-label\">Certificate Expiration:</span><span class=\"detail-value\">${expirationDate}</span></div>` : '';
                let totalFeeInfo = totalFee ? `<div class=\"detail-row\"><span class=\"detail-label\">Total Fee:</span><span class=\"detail-value\">₱ ${totalFee.toLocaleString('en-US', {minimumFractionDigits:2})}${babyNote || discountNote}</span></div>` : '';
                let renewalInfo = `<div class=\"detail-row\"><span class=\"detail-label\">Renewal Fee:</span><span class=\"detail-value\">₱ 5,000.00</span></div>`;
                summaryHtml = `${totalFeeInfo}${renewalInfo}${expirationInfo}`;
              }
              // Build the assessment form HTML (move summary fields to bottom)
              formHtml = `
                <div class=\"assessment-fees-container\" style=\"max-width:600px;margin:0 auto;padding:32px 0;\">
                  <h2>Assessment of Fees</h2>
                  <form id=\"assessmentForm">
                    <div class=\"detail-row\"><span class=\"detail-label\">Informant Name:</span><span class=\"detail-value\">${data.informant_name || ''}</span></div>
                    <div class=\"detail-row\"><span class=\"detail-label\">Email:</span><span class=\"detail-value\">${data.email || ''}</span></div>
                    <div class=\"detail-row\"><span class=\"detail-label\">Type:</span><span class=\"detail-value\">${data.type || ''}</span></div>
                    <div class=\"detail-row\" style=\"display:${data.deceased_name ? '' : 'none'};\"><span class=\"detail-label\">Name of Deceased:</span><span class=\"detail-value\">${data.deceased_name || ''}</span></div>
                    <div class=\"detail-row\" style=\"display:${data.residency ? '' : 'none'};\"><span class=\"detail-label\">Residency:</span><span class=\"detail-value\">${data.residency || ''}</span></div>
                    <div class=\"detail-row\" style=\"display:${data.dob ? '' : 'none'};\"><span class=\"detail-label\">Date of Birth:</span><span class=\"detail-value\">${data.dob || ''}</span></div>
                    <div class=\"detail-row\" style=\"display:${data.dod ? '' : 'none'};\"><span class=\"detail-label\">Date of Death:</span><span class=\"detail-value\">${data.dod || ''}</span></div>
                    <div class=\"detail-row\"><span class=\"detail-label\">Age:</span><span class=\"detail-value\">${data.age || ''}</span></div>
                    <div class=\"detail-row\" style=\"display:${(data.type === 'Transfer' || data.type === 'Exhumation') ? '' : 'none'};\"><span class=\"detail-label\">Niche ID:</span><span class=\"detail-value\">${data.niche_id || ''}</span></div>
                  
                    <hr style=\"margin:24px 0;\">
                    ${summaryHtml}
                    <div style=\"text-align:right;margin-top:24px;\">
                      <button type=\"submit\" class=\"accept-btn\">Submit Assessment</button>
                    </div>
                  </form>
                </div>
              `;
              document.getElementById('assessment-fees-section').innerHTML = formHtml;
              showTab('assessment-fees');
              closePopup();
              // Add form submission handler to send AJAX request
              const assessmentForm = document.getElementById('assessmentForm');
              if (assessmentForm) {
                assessmentForm.onsubmit = function(e) {
                  e.preventDefault();
                  // Send AJAX request to submit_assessment.php
                  fetch('submit_assessment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `request_id=${encodeURIComponent(data.id)}&user_id=${encodeURIComponent(data.user_id)}&total_fee=${encodeURIComponent(totalFee)}`
                  })
                  .then(response => response.json())
                  .then(result => {
                    if (result.success) {
                      showActionSuccessNotification('Assessment submitted and user notified!');
                    } else {
                      alert('Failed to submit assessment: ' + (result.message || 'Unknown error'));
                    }
                  })
                  .catch(error => {
                    alert('Network error: ' + error);
                  });
                };
              }
            }
          });
      }
    </script>
  </main>

</body>
</html>