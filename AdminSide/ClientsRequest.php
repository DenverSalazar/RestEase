<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>RestEase Client Requests</title>
  <link rel="icon" type="image/png" href="../assets/re logo blue.png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/Clients.css">
  <link rel="stylesheet" href="../css/sidebar.css">
  <link rel="stylesheet" href="../css/clientsrequest.css">
  <link rel="stylesheet" href="../css/header.css">
  <!-- DataTables CSS -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
  <style>
    /* Match Records.php table style for done assessment */
    .cemetery-masterlist-table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
      background: #fff;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 1px 4px rgba(0,0,0,0.04);
      margin-bottom: 1rem;
      font-family: 'Poppins', sans-serif;
      font-size: 0.9rem;
    }
    .cemetery-masterlist-table th, .cemetery-masterlist-table td {
      padding: 8px 10px;
      text-align: left;
      font-size: 0.82rem;
      border-bottom: 1px solid #eee;
      background: #fff;
      font-family: 'Poppins', sans-serif;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .cemetery-masterlist-table th {
      background: #f7f8fa;
      font-weight: 500;
      color: #333;
      font-size: 0.77rem;
    }
    .cemetery-masterlist-table tr:last-child td {
      border-bottom: none;
    }
    /* Specific column widths for better date display */
    .cemetery-masterlist-table th:nth-child(1), .cemetery-masterlist-table td:nth-child(1) { width: 10%; }
    .cemetery-masterlist-table th:nth-child(2), .cemetery-masterlist-table td:nth-child(2) { width: 14%; }
    .cemetery-masterlist-table th:nth-child(3), .cemetery-masterlist-table td:nth-child(3) { width: 7%; }
    .cemetery-masterlist-table th:nth-child(4), .cemetery-masterlist-table td:nth-child(4) { width: 14%; }
    .cemetery-masterlist-table th:nth-child(5), .cemetery-masterlist-table td:nth-child(5) { width: 10%; }
    .cemetery-masterlist-table th:nth-child(6), .cemetery-masterlist-table td:nth-child(6) { width: 10%; }
    .cemetery-masterlist-table th:nth-child(7), .cemetery-masterlist-table td:nth-child(7) { width: 10%; }
    .cemetery-masterlist-table th:nth-child(8), .cemetery-masterlist-table td:nth-child(8) { width: 7%; }
    .cemetery-masterlist-table th:nth-child(9), .cemetery-masterlist-table td:nth-child(9) { width: 8%; }
    .cemetery-masterlist-table th:nth-child(10), .cemetery-masterlist-table td:nth-child(10) { width: 10%; }
    .cemetery-masterlist-table th:nth-child(11), .cemetery-masterlist-table td:nth-child(11) { width: 10%; }
    .cemetery-masterlist-table th:nth-child(12), .cemetery-masterlist-table td:nth-child(12) { width: 10%; }
    .cemetery-masterlist-table th:nth-child(13), .cemetery-masterlist-table td:nth-child(13) { width: 12%; }
    @media (max-width: 900px) {
      .cemetery-masterlist-table th, .cemetery-masterlist-table td {
        font-size: 0.78rem;
        padding: 6px 6px;
      }
    }
  </style>
</head>
<body>
  <!-- Sidebar -->
  <?php include '../Includes/sidebar.php'; ?>
   <?php include '../Includes/header.php'; ?>

  <!-- Main Content -->
  <main class="main-content">
    <div class="clients-header">
      <h1>Client Requests</h1>
      <p class="subtitle">View all Clients Requests Information.</p>
    </div>
    <div class="clients-tabs-bar">
      <div class="clients-tabs">
        <span class="clients-tab-title active" id="tab-clients-request" onclick="showTab('clients-request')">Clients Request</span>
        <span class="clients-tab-title" id="tab-accepted-request" onclick="showTab('accepted-request')">Accepted Requests</span>
        <span class="clients-tab-title" id="tab-assessment-fees" onclick="showTab('assessment-fees')">Assessment of Fees</span>
        <span class="clients-tab-title" id="tab-done-assessment" onclick="showTab('done-assessment')">Done Assessment</span>
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
      // Only show accepted requests NOT yet assessed
      $sql_accepted = "SELECT ar.*, u.first_name AS user_first_name, u.last_name AS user_last_name, u.email, u.profile_picture 
        FROM accepted_request ar 
        JOIN users u ON ar.user_id = u.id 
        LEFT JOIN assessment a ON ar.id = a.request_id 
        WHERE a.id IS NULL 
        ORDER BY ar.created_at DESC";
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
    <div id="done-assessment-section" style="display:none;">
      <div class="clients-actions">
        <div class="search-container">
          <i class="fas fa-search"></i>
          <input type="text" placeholder="Search Done Assessments" id="done-assessment-search-input">
        </div>
        <div class="actions-right">
          <div class="date-filter-container">
            <input type="date" id="done-assessment-date-filter" class="date-input">
            <button type="button" id="clear-done-assessment-date-filter" class="clear-date-btn" style="display:none;">
              <i class="fas fa-times"></i>
            </button>
          </div>
        </div>
      </div>
      <div style="margin-bottom: 16px;">
        <div class="dataTables_length">
          <label>Show <select name="done-assessment-table_length"><option value="10">10</option><option value="25">25</option><option value="50">50</option><option value="100">100</option></select> entries</label>
        </div>
      </div>
      <?php
      // Fetch all done assessments
      $sql_assessment = "SELECT * FROM assessment ORDER BY created_at DESC";
      $result_assessment = $conn->query($sql_assessment);
      ?>
      <div class="clients-table-container" style="overflow-x:auto;">
        <table class="cemetery-masterlist-table" id="done-assessment-table">
          <thead>
            <tr>
              <th>Informant Name</th>
              <th>Type</th>
              <th>Deceased Name</th>
              <th>Residency</th>
              <th>Date of Internment</th>
              <th>Niche ID</th>
              <th>Total Fee</th>
              <th>Expiration</th>
              <th>Renewal Fee</th>
              <th>Date Assessed</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($result_assessment && $result_assessment->num_rows > 0): ?>
              <?php while ($row = $result_assessment->fetch_assoc()): ?>
                <tr data-assessed-date='<?php echo htmlspecialchars($row['created_at']); ?>'>
                  <td><b><?php echo htmlspecialchars($row['informant_name']); ?></b></td>
                  <td><?php echo htmlspecialchars($row['type']); ?></td>
                  <td><b><?php echo htmlspecialchars($row['deceased_name']); ?></b></td>
                  <td><?php echo htmlspecialchars($row['residency']); ?></td>
                  <td><?php echo htmlspecialchars($row['internment_date']); ?></td>
                  <td>
                    <?php
                      // For Relocate, show current_niche_id if available, otherwise niche_id (but treat 0 as empty)
                      $displayNiche = '';
                      if ($row['type'] === 'Relocate' && !empty($row['current_niche_id'])) {
                        $displayNiche = $row['current_niche_id'];
                      } else {
                        $n = $row['niche_id'] ?? '';
                        if ($n === '0' || $n === 0) $n = '';
                        $displayNiche = $n;
                      }
                      echo htmlspecialchars($displayNiche);
                    ?>
                  </td>
                  <td>₱ <?php echo number_format($row['total_fee'], 2); ?></td>
                  <td><?php echo htmlspecialchars($row['expiration']); ?></td>
                  <td>₱ <?php echo number_format($row['renewal_fee'], 2); ?></td>
                  <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                </tr>
              <?php endwhile; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      <div class="dataTables_wrapper"></div>
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
          <div class="detail-row" id="popupInternmentDateRow" style="display:none;">
            <span class="detail-label">Date of Internment:</span>
            <span class="detail-value" id="popupInternmentDate"></span>
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
    <!-- Error Notification (same design as success, but red icon/text) -->
    <div id="actionErrorNotification" style="display:none;position:fixed;top:32px;right:32px;z-index:9999;background:#fff;border-radius:12px;box-shadow:0 4px 16px rgba(0,0,0,0.12);padding:18px 32px;min-width:260px;max-width:350px;align-items:center;">
      <div style="display:flex;align-items:center;gap:12px;">
        <span style="color:#e74c3c;font-size:1.5rem;"><i class="fas fa-times-circle"></i></span>
        <span id="actionErrorNotificationText" style="font-size:1.08rem;color:#333;font-weight:500;"></span>
        <button id="closeActionErrorNotificationBtn" style="background:none;border:none;color:#888;font-size:1.2rem;cursor:pointer;margin-left:auto;">&times;</button>
      </div>
    </div>
    
    <!-- DataTables JS -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script>
      // Initialize DataTables
      let clientsRequestTable, acceptedTable, doneAssessmentTable;
      
      document.addEventListener('DOMContentLoaded', function() {
        // Destroy existing DataTables if they exist
        if ($.fn.DataTable.isDataTable('#clients-request-table')) {
          $('#clients-request-table').DataTable().destroy();
        }
        if ($.fn.DataTable.isDataTable('#accepted-table')) {
          $('#accepted-table').DataTable().destroy();
        }
        if ($.fn.DataTable.isDataTable('#done-assessment-table')) {
          $('#done-assessment-table').DataTable().destroy();
        }

        // Initialize DataTables for all tables
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

        try {
          doneAssessmentTable = $('#done-assessment-table').DataTable({
            "paging": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "dom": 'rtip',
            "pageLength": 10,
            "language": {
              "emptyTable": "No assessments found.",
              "zeroRecords": "No matching records found",
              "info": "Showing _START_ to _END_ of _TOTAL_ entries",
              "infoEmpty": "Showing 0 to 0 of 0 entries",
              "infoFiltered": "(filtered from _MAX_ total entries)"
            },
            "columnDefs": [
              { "orderable": false, "targets": [9] } // update to last column index
            ],
            "drawCallback": function() {
              const tableWrapper = $('#done-assessment-table').closest('.clients-table-container');
              const externalWrapper = tableWrapper.next('.dataTables_wrapper');
              
              const info = $('#done-assessment-table_info').detach();
              const paginate = $('#done-assessment-table_paginate').detach();
              
              externalWrapper.empty().append(info).append(paginate);
            }
          });
        } catch (e) {
          console.error('Error initializing done assessment table:', e);
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

        const doneAssessmentSearchInput = document.getElementById('done-assessment-search-input');
        if (doneAssessmentSearchInput) {
          doneAssessmentSearchInput.addEventListener('keyup', function() {
            try {
              if (doneAssessmentTable) {
                doneAssessmentTable.search(this.value).draw();
              }
            } catch (e) {
              console.error('Error in done assessment search:', e);
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

        const doneAssessmentDateInput = document.getElementById('done-assessment-date-filter');
        const clearDoneAssessmentDateBtn = document.getElementById('clear-done-assessment-date-filter');

        if (doneAssessmentDateInput && clearDoneAssessmentDateBtn) {
          doneAssessmentDateInput.addEventListener('change', function() {
            const selectedDate = this.value;
            if (selectedDate) {
              clearDoneAssessmentDateBtn.style.display = 'block';
              try {
                if (doneAssessmentTable) {
                  doneAssessmentTable.column(12).search(selectedDate, false, false).draw();
                }
              } catch (e) {
                console.error('Error in done assessment date filter:', e);
              }
            } else {
              clearDoneAssessmentDateBtn.style.display = 'none';
              try {
                if (doneAssessmentTable) {
                  doneAssessmentTable.column(12).search('').draw();
                }
              } catch (e) {
                console.error('Error clearing done assessment date filter:', e);
              }
            }
          });

          clearDoneAssessmentDateBtn.addEventListener('click', function() {
            doneAssessmentDateInput.value = '';
            this.style.display = 'none';
            try {
              if (doneAssessmentTable) {
                doneAssessmentTable.column(12).search('').draw();
              }
            } catch (e) {
              console.error('Error clearing done assessment date filter:', e);
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

        const doneAssessmentLengthSelect = document.querySelector('select[name="done-assessment-table_length"]');
        if (doneAssessmentLengthSelect) {
          doneAssessmentLengthSelect.addEventListener('change', function() {
            try {
              if (doneAssessmentTable) {
                doneAssessmentTable.page.len(parseInt(this.value)).draw();
              }
            } catch (e) {
              console.error('Error changing done assessment page length:', e);
            }
          });
        }
      });

      function showTab(tab) {
        document.getElementById('clients-request-section').style.display = (tab === 'clients-request') ? '' : 'none';
        document.getElementById('accepted-request-section').style.display = (tab === 'accepted-request') ? '' : 'none';
        document.getElementById('assessment-fees-section').style.display = (tab === 'assessment-fees') ? '' : 'none';
        document.getElementById('done-assessment-section').style.display = (tab === 'done-assessment') ? '' : 'none';
        document.getElementById('tab-clients-request').classList.toggle('active', tab === 'clients-request');
        document.getElementById('tab-accepted-request').classList.toggle('active', tab === 'accepted-request');
        document.getElementById('tab-assessment-fees').classList.toggle('active', tab === 'assessment-fees');
        document.getElementById('tab-done-assessment').classList.toggle('active', tab === 'done-assessment');
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
              if (document.getElementById('popupInternmentDate')) {
                document.getElementById('popupInternmentDate').textContent = data.internment_date || '';
                document.getElementById('popupInternmentDateRow').style.display = data.internment_date ? '' : 'none';
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
        document.getElementById('closeActionErrorNotificationBtn').onclick = function() {
          document.getElementById('actionErrorNotification').style.display = 'none';
        };
      });
      function showActionSuccessNotification(message) {
        const notification = document.getElementById('actionSuccessNotification');
        const notificationText = document.getElementById('actionNotificationText');
        notificationText.textContent = message;
        notification.style.display = 'flex';
        setTimeout(function() { notification.style.display = 'none'; }, 3000);
      }
      function showActionErrorNotification(message) {
        const notification = document.getElementById('actionErrorNotification');
        const notificationText = document.getElementById('actionErrorNotificationText');
        notificationText.textContent = message;
        notification.style.display = 'flex';
        setTimeout(function() { notification.style.display = 'none'; }, 4000);
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

  // Fetch the request details
  const url = (window.currentRequestType === 'accepted') 
    ? 'get_accepted_request_details.php?id=' + window.currentRequestId 
    : 'get_request_details.php?id=' + window.currentRequestId;

  fetch(url)
    .then(response => response.json())
    .then(data => {
      if (!data || !data.success) return;

      let summaryHtml = '';
      let expirationDate = '';
      let totalFee = 0;
      let renewalFee = 5000;
      let openingFee = 0;
      let relocationFee = 0;
      let remainsCount = parseInt(data.remains_count) || 0;

      // Calculate fees and expiration
      if (data.type === 'Relocate') {
        openingFee = 1000;
        remainsCount = parseInt(data.remains_count) || 1;
        relocationFee = 500 * remainsCount;
        totalFee = openingFee + relocationFee;

        // Make opening/relocation/total editable
        summaryHtml = `
          <div class="detail-row"><span class="detail-label">Opening Fee:</span>
            <span class="detail-value view" data-field="opening_fee">₱ ${openingFee.toLocaleString('en-US', {minimumFractionDigits:2})}</span>
            <input type="number" step="0.01" class="detail-value edit" name="opening_fee" value="${openingFee}" style="display:none;width:100%;box-sizing:border-box;" />
          </div>
          <div class="detail-row"><span class="detail-label">Relocation Fee:</span>
            <span class="detail-value view" data-field="relocation_fee">₱ ${relocationFee.toLocaleString('en-US', {minimumFractionDigits:2})} (x ${remainsCount})</span>
            <div style="display:none;" class="edit-block">
              <input type="number" step="1" class="detail-value edit" name="remains_count" value="${remainsCount}" style="display:none;width:100%;box-sizing:border-box;margin-bottom:6px;" />
              <input type="number" step="0.01" class="detail-value edit" name="relocation_fee" value="${relocationFee}" style="display:none;width:100%;box-sizing:border-box;" />
            </div>
          </div>
          <div class="detail-row"><span class="detail-label">Total Fee:</span>
            <span class="detail-value view" data-field="total_fee">₱ ${totalFee.toLocaleString('en-US', {minimumFractionDigits:2})}</span>
            <input type="number" step="0.01" class="detail-value edit" name="total_fee" value="${totalFee}" style="display:none;width:100%;box-sizing:border-box;" />
          </div>
        `;
      } else {
        // New / Transfer logic
        const age = parseInt(data.age);
        let babyNote = '';
        let discountNote = '';

        if (data.type === 'New' || data.type === 'Transfer') {
          if (!isNaN(age) && age <= 2) {
            totalFee = 5000;
            babyNote = ' (Newborn/Baby Rate)';
          } else {
            const residency = (data.residency || '').trim();
            const isPadreGarcia = padreGarciaBarangays.some(bgy => residency.toLowerCase() === bgy.toLowerCase());
            totalFee = isPadreGarcia ? 10000 : 15000;
            discountNote = isPadreGarcia ? ' (Graciano discount applied)' : '';
          }
        }

        if (data.dod) {
          const dod = new Date(data.dod);
          const exp = new Date(dod);
          exp.setFullYear(exp.getFullYear() + 5);
          const months = ['JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC'];
          const day = String(exp.getDate()).padStart(2, '0');
          const month = months[exp.getMonth()];
          const year = exp.getFullYear();
          expirationDate = `${day}-${month}-${year}`;
          // also prepare ISO format for the date input (YYYY-MM-DD)
          var expirationISO = exp.toISOString().slice(0,10);
        } else {
          var expirationISO = '';
        }

        // Make total_fee, renewal_fee and expiration editable (expiration input is a date picker)
        summaryHtml = `
          <div class="detail-row"><span class="detail-label">Total Fee:</span>
            <span class="detail-value view" data-field="total_fee">₱ ${totalFee.toLocaleString('en-US', {minimumFractionDigits:2})}${babyNote || discountNote}</span>
            <input type="number" step="0.01" class="detail-value edit" name="total_fee" value="${totalFee}" style="display:none;width:100%;box-sizing:border-box;" />
          </div>
          <div class="detail-row"><span class="detail-label">Renewal Fee:</span>
            <span class="detail-value view" data-field="renewal_fee">₱ ${renewalFee.toLocaleString('en-US', {minimumFractionDigits:2})}</span>
            <input type="number" step="0.01" class="detail-value edit" name="renewal_fee" value="${renewalFee}" style="display:none;width:100%;box-sizing:border-box;" />
          </div>
          ${expirationDate ? `<div class="detail-row"><span class="detail-label">Certificate Expiration:</span>
            <span class="detail-value view" data-field="expiration">${expirationDate}</span>
            <input type="date" class="detail-value edit" name="expiration" value="${expirationISO}" style="display:none;width:100%;box-sizing:border-box;" />
          </div>` : ''}
        `;
      }

      // Build the form HTML with hidden request_id/user_id and editable pairs for every displayed field
      const formHtml = `
        <div class="assessment-fees-container" style="max-width:700px;margin:0 auto;padding:32px 0;">
          <h2>Assessment of Fees</h2>
          <form id="assessmentForm">
            <input type="hidden" name="request_id" value="${data.id}" />
            <input type="hidden" name="user_id" value="${data.user_id || ''}" />

            <div class="detail-row"><span class="detail-label">Informant Name:</span>
              <span class="detail-value view" data-field="informant_name">${data.informant_name || ''}</span>
              <input type="text" class="detail-value edit" name="informant_name" value="${data.informant_name || ''}" style="display:none;width:100%;box-sizing:border-box;" />
            </div>

            <div class="detail-row"><span class="detail-label">Email:</span>
              <span class="detail-value view" data-field="email">${data.email || ''}</span>
              <input type="email" class="detail-value edit" name="email" value="${data.email || ''}" style="display:none;width:100%;box-sizing:border-box;" />
            </div>

            <div class="detail-row"><span class="detail-label">Type:</span>
              <span class="detail-value view" data-field="type">${data.type || ''}</span>
              <input type="text" class="detail-value edit" name="type" value="${data.type || ''}" style="display:none;width:100%;box-sizing:border-box;" />
            </div>

            <div class="detail-row" style="display:${data.deceased_name ? '' : 'none'};"><span class="detail-label">Name of Deceased:</span>
              <span class="detail-value view" data-field="deceased_name">${data.deceased_name || ''}</span>
              <input type="text" class="detail-value edit" name="deceased_name" value="${data.deceased_name || ''}" style="display:none;width:100%;box-sizing:border-box;" />
            </div>

            <div class="detail-row" style="display:${data.residency ? '' : 'none'};"><span class="detail-label">Residency:</span>
              <span class="detail-value view" data-field="residency">${data.residency || ''}</span>
              <input type="text" class="detail-value edit" name="residency" value="${data.residency || ''}" style="display:none;width:100%;box-sizing:border-box;" />
            </div>

            <div class="detail-row" style="display:${data.dob ? '' : 'none'};"><span class="detail-label">Date of Birth:</span>
              <span class="detail-value view" data-field="dob">${data.dob || ''}</span>
              <input type="date" class="detail-value edit" name="dob" value="${data.dob || ''}" style="display:none;width:100%;box-sizing:border-box;" />
            </div>

            <div class="detail-row" style="display:${data.dod ? '' : 'none'};"><span class="detail-label">Date of Death:</span>
              <span class="detail-value view" data-field="dod">${data.dod || ''}</span>
              <input type="date" class="detail-value edit" name="dod" value="${data.dod || ''}" style="display:none;width:100%;box-sizing:border-box;" />
            </div>

            <div class="detail-row" style="display:${data.internment_date ? '' : 'none'};"><span class="detail-label">Date of Internment:</span>
              <span class="detail-value view" data-field="internment_date">${data.internment_date || ''}</span>
              <input type="date" class="detail-value edit" name="internment_date" value="${data.internment_date || ''}" style="display:none;width:100%;box-sizing:border-box;" />
            </div>

            <div class="detail-row"><span class="detail-label">Age:</span>
              <span class="detail-value view" data-field="age">${data.age || ''}</span>
              <input type="number" class="detail-value edit" name="age" value="${data.age || ''}" style="display:none;width:100%;box-sizing:border-box;" />
            </div>

            <div class="detail-row" style="display:${(data.type === 'Transfer' || data.type === 'Exhumation' || data.type === 'Relocate') ? '' : 'none'};"><span class="detail-label">Niche ID:</span>
              <span class="detail-value view" data-field="niche_id">${data.niche_id || ''}</span>
              <input type="text" class="detail-value edit" name="niche_id" value="${data.niche_id || ''}" style="display:none;width:100%;box-sizing:border-box;" />
            </div>

            <hr style="margin:24px 0;">
            ${summaryHtml}
            <div style="text-align:right;margin-top:24px;display:flex;gap:8px;justify-content:flex-end;align-items:center;">
              <button type="button" id="editAssessmentBtn" class="edit-btn" style="background:#f0f0f0;border:1px solid #ddd;color:#333;padding:8px 14px;border-radius:6px;cursor:pointer;" onclick="toggleEditAssessment(this)">Edit</button>
              <button type="submit" class="accept-btn" style="background:#27ae60;color:#fff;padding:8px 14px;border:none;border-radius:6px;cursor:pointer;">Submit Assessment</button>
            </div>
          </form>
          <div id="assessmentLoadingSpinner" style="display:none;justify-content:center;align-items:center;margin-top:18px;">
            <div style="display:inline-block;width:38px;height:38px;border:4px solid #27ae60;border-top:4px solid #e0e0e0;border-radius:50%;animation:spin 1s linear infinite;"></div>
          </div>
        </div>
      `;

      document.getElementById('assessment-fees-section').innerHTML = formHtml;
      showTab('assessment-fees');
      closePopup();

      // Helper to get the current value of a field (prefers edited input if present)
      function getFieldValueFromForm(form, name, fallback) {
        const el = form.querySelector('[name="'+name+'"]');
        if (el) return el.value;
        return fallback || '';
      }

      // small helper to format YYYY-MM-DD to DD-MMM-YYYY for display
      function formatExpirationDisplay(isoDate) {
        if (!isoDate) return '';
        const d = new Date(isoDate);
        if (isNaN(d.getTime())) return isoDate;
        const months = ['JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC'];
        const day = String(d.getDate()).padStart(2,'0');
        const month = months[d.getMonth()];
        const year = d.getFullYear();
        return `${day}-${month}-${year}`;
      }

      // Toggle Edit / View mode for assessment form (shows inputs / hides spans)
      window.toggleEditAssessment = function(btn) {
        const form = document.getElementById('assessmentForm');
        if (!form) return;
        const isEditing = btn.getAttribute('data-editing') === '1';
        const viewEls = form.querySelectorAll('.detail-value.view');
        const editEls = form.querySelectorAll('.detail-value.edit');

        if (!isEditing) {
          // enter edit mode: show inputs, hide views
          editEls.forEach(e => e.style.display = 'inline-block');
          // also unhide any edit-block container (relocation group) so its inputs are visible
          const editBlocks = form.querySelectorAll('.edit-block');
          editBlocks.forEach(b => {
            b.style.display = 'block';
            const inputs = b.querySelectorAll('.edit');
            inputs.forEach(i => i.style.display = 'inline-block');
          });
          viewEls.forEach(v => v.style.display = 'none');
          btn.textContent = 'Done Editing';
          btn.setAttribute('data-editing','1');
        } else {
          // exit edit mode: copy input values into view spans and hide inputs
          editEls.forEach(e => {
            const name = e.getAttribute('name');
            const view = form.querySelector('.detail-value.view[data-field="'+name+'"]');
            if (view) {
              if (name === 'total_fee' || name === 'renewal_fee' || name === 'opening_fee' || name === 'relocation_fee') {
                const num = parseFloat(e.value) || 0;
                view.textContent = '₱ ' + num.toLocaleString('en-US', {minimumFractionDigits:2});
              } else if (name === 'remains_count') {
                // update related relocation view display if exists
                const relView = form.querySelector('.detail-value.view[data-field="relocation_fee"]');
                if (relView) {
                  const relFeeEl = form.querySelector('.detail-value.edit[name="relocation_fee"]');
                  const relNum = relFeeEl ? (parseFloat(relFeeEl.value) || 0) : 0;
                  relView.textContent = `₱ ${relNum.toLocaleString('en-US', {minimumFractionDigits:2})} (x ${e.value})`;
                }
              } else if (name === 'expiration') {
                // expiration edit is a date input (YYYY-MM-DD) — format nicely for the view
                view.textContent = formatExpirationDisplay(e.value);
              } else {
                view.textContent = e.value;
              }
            }
            e.style.display = 'none';
          });
          // hide any inputs that were inside edit-block and hide the block itself
          const editBlocks2 = form.querySelectorAll('.edit-block');
          editBlocks2.forEach(b => {
            const inputs = b.querySelectorAll('.edit');
            inputs.forEach(i => i.style.display = 'none');
            b.style.display = 'none';
          });
          viewEls.forEach(v => v.style.display = 'inline');
          btn.textContent = 'Edit';
          btn.setAttribute('data-editing','0');
        }
      };

      // Form submission using URLSearchParams (reads current values from inputs if present)
      const assessmentForm = document.getElementById('assessmentForm');
      const loadingSpinner = document.getElementById('assessmentLoadingSpinner');
      assessmentForm.onsubmit = function(e) {
        e.preventDefault();
        if (loadingSpinner) loadingSpinner.style.display = 'flex';

        // read values from form inputs when available, otherwise fallback to original data
        const params = new URLSearchParams({
          request_id: getFieldValueFromForm(assessmentForm, 'request_id', data.id),
          user_id: getFieldValueFromForm(assessmentForm, 'user_id', data.user_id),
          total_fee: getFieldValueFromForm(assessmentForm, 'total_fee', totalFee),
          opening_fee: getFieldValueFromForm(assessmentForm, 'opening_fee', openingFee || 0),
          relocation_fee: getFieldValueFromForm(assessmentForm, 'relocation_fee', relocationFee || 0),
          remains_count: getFieldValueFromForm(assessmentForm, 'remains_count', remainsCount || 0),
          type: getFieldValueFromForm(assessmentForm, 'type', data.type || ''),
          informant_name: getFieldValueFromForm(assessmentForm, 'informant_name', data.informant_name || ''),
          email: getFieldValueFromForm(assessmentForm, 'email', data.email || ''),
          deceased_name: getFieldValueFromForm(assessmentForm, 'deceased_name', data.deceased_name || ''),
          residency: getFieldValueFromForm(assessmentForm, 'residency', data.residency || ''),
          dob: getFieldValueFromForm(assessmentForm, 'dob', data.dob || ''),
          dod: getFieldValueFromForm(assessmentForm, 'dod', data.dod || ''),
          internment_date: getFieldValueFromForm(assessmentForm, 'internment_date', data.internment_date || ''),
          age: getFieldValueFromForm(assessmentForm, 'age', data.age || ''),
          niche_id: getFieldValueFromForm(assessmentForm, 'niche_id', data.niche_id || ''),
          expiration: getFieldValueFromForm(assessmentForm, 'expiration', expirationDate || ''),
          renewal_fee: getFieldValueFromForm(assessmentForm, 'renewal_fee', renewalFee || 0)
        });

        fetch('submit_assessment.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: params.toString()
        })
        .then(response => response.json())
        .then(result => {
          if (loadingSpinner) loadingSpinner.style.display = 'none';
          if (result.success) {
            // Remove from accepted_request table
            fetch('delete_accepted_request.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
              body: 'id=' + encodeURIComponent(data.id)
            }).catch(()=>{ /* non blocking */ });

            showActionSuccessNotification('Assessment submitted and user notified!');
            updateDoneAssessmentTable();
          } else {
            showActionErrorNotification('Failed to submit assessment: ' + (result.message || 'Unknown error'));
          }
        })
        .catch(error => {
          if (loadingSpinner) loadingSpinner.style.display = 'none';
          showActionErrorNotification('Network error: ' + error);
        });
      };
    });
  }

function updateDoneAssessmentTable() {
  fetch('get_done_assessments.php')
    .then(response => response.json())
    .then(data => {
      if (Array.isArray(data)) {
        const tbody = document.querySelector('#done-assessment-table tbody');
        tbody.innerHTML = '';
        data.forEach(row => {
          const tr = document.createElement('tr');
          tr.setAttribute('data-assessed-date', row.created_at);
          // Determine niche cell: for Relocate prefer current_niche_id, otherwise niche_id.
          let niche = '';
          if (row.type === 'Relocate' && row.current_niche_id) {
            niche = row.current_niche_id;
          } else {
            niche = row.niche_id;
          }
          if (niche === 0 || niche === '0' || niche === null) niche = '';
          
          tr.innerHTML = `
            <td><b>${row.informant_name}</b></td>
            <!-- <td>${row.email}</td> -->
            <td>${row.type}</td>
            <td><b>${row.deceased_name}</b></td>
            <td>${row.residency}</td>
            <!-- <td>${row.dob}</td> -->
            <!-- <td>${row.dod}</td> -->
            <td>${row.internment_date || ''}</td>
            <!-- <td>${row.age}</td> -->
            <td>${niche}</td>
            <td>₱ ${parseFloat(row.total_fee).toLocaleString('en-US', {minimumFractionDigits:2})}</td>
            <td>${row.expiration}</td>
            <td>₱ ${parseFloat(row.renewal_fee).toLocaleString('en-US', {minimumFractionDigits:2})}</td>
            <td>${row.created_at}</td>
          `;
          tbody.appendChild(tr);
        });
        // Redraw DataTable
        if (window.doneAssessmentTable) {
          window.doneAssessmentTable.clear().destroy();
          window.doneAssessmentTable = $('#done-assessment-table').DataTable({
            "paging": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "dom": 'rtip',
            "pageLength": 10,
            "language": {
              "emptyTable": "No assessments found.",
              "zeroRecords": "No matching records found",
              "info": "Showing _START_ to _END_ of _TOTAL_ entries",
              "infoEmpty": "Showing 0 to 0 of 0 entries",
              "infoFiltered": "(filtered from _MAX_ total entries)"
            },
            "columnDefs": [
              { "orderable": false, "targets": [9] } // update to last column index
            ],
            "drawCallback": function() {
              const tableWrapper = $('#done-assessment-table').closest('.clients-table-container');
              const externalWrapper = tableWrapper.next('.dataTables_wrapper');
              
              const info = $('#done-assessment-table_info').detach();
              const paginate = $('#done-assessment-table_paginate').detach();
              
              externalWrapper.empty().append(info).append(paginate);
            }
          });
        }
      }
    });
}

// Polling function to fetch and update client requests table
function fetchAndUpdateClientRequestsTable() {
  fetch('get_client_requests.php')
    .then(response => response.json())
    .then(data => {
      if (Array.isArray(data)) {
        const tbody = document.querySelector('#clients-request-table tbody');
        tbody.innerHTML = '';
        data.forEach (row => {
          // Avatar logic (same as PHP)
          let avatarHtml = '';
          if (row.profile_picture && row.profile_picture !== '' && row.profile_picture !== null) {
            avatarHtml = `<img src="../uploads/${row.profile_picture}" alt="Profile" class="avatar-img" style="width:38px;height:38px;border-radius:50%;object-fit:cover;">`;
          } else {
            const initials = ((row.first_name || '').charAt(0) + (row.last_name || '').charAt(0)).toUpperCase();
            const colorIndex = (Math.abs(crc32(row.first_name + row.last_name)) % 10) + 1;
            avatarHtml = `<div class="avatar-img avatar-google avatar-color-${colorIndex}" style="display:inline-flex;">${initials}</div>`;
          }



          const requestDate = row.created_at ? row.created_at.substring(0, 10) : 'N/A';
          tbody.innerHTML += `
            <tr data-request-date="${requestDate}">
              <td>
                ${avatarHtml}
                <span class="client-name" style="vertical-align:middle; margin-left:4px; display:inline-block;">${row.first_name} ${row.last_name}</span>
              </td>
              <td>${row.email}</td>
              <td>${row.type}</td>
              <td>${requestDate}</td>
              <td><span class="status-badge status-pending">Pending</span></td>
              <td><button class="view-btn" onclick="openPopup(${row.id}, 'pending')">View</button></td>
            </tr>
          `;
        });
        // Redraw DataTable
        if (window.clientsRequestTable) {
          window.clientsRequestTable.clear().destroy();
          window.clientsRequestTable = $('#clients-request-table').DataTable({
           
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
        }
      }
    });
}

// Helper for JS crc32
function crc32(str) {
  let crc = 0 ^ (-1);
  for (let i = 0; i < str.length; i++) {
    crc = (crc >>> 8) ^ [0, 1996959894, 3993919788, 2567524794, 124634137, 1886057615, 3915621680, 3929699850, 668119635, 251722036, 2875272554, 3710493301, 4152554867, 1732584193, 2396949568, 3453421203][(crc ^ str.charCodeAt(i)) & 0xFF];
  }
  return (crc ^ (-1)) >>> 0;
}
    </script>
  </main>

</body>
</html>
