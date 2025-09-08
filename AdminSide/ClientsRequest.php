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
                  $firstName = htmlspecialchars($row['first_name']);
                  $lastName = htmlspecialchars($row['last_name']);
                  $name = $firstName . ' ' . $lastName;
                  $email = htmlspecialchars($row['email']);
                  $requestDate = htmlspecialchars($row['created_at'] ? date('Y-m-d', strtotime($row['created_at'])) : 'N/A');
                  $profilePicture = htmlspecialchars($row['profile_picture']);
                  
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
                  $firstName = htmlspecialchars($row['user_first_name']);
                  $lastName = htmlspecialchars($row['user_last_name']);
                  $name = $firstName . ' ' . $lastName;
                  $email = htmlspecialchars($row['email']);
                  $acceptedDate = htmlspecialchars($row['created_at'] ? date('Y-m-d', strtotime($row['created_at'])) : 'N/A');
                  $profilePicture = htmlspecialchars($row['profile_picture']);
                  
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
      <div style="padding: 32px 0; text-align: center; color: #888;">
        <h2>Assessment of Fees</h2>
        <p>This section is under construction.</p>
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
            <span class="detail-label">Client Name:</span>
            <span class="detail-value" id="popupClientName"></span>
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
          <div class="detail-row">
            <span class="detail-label">Age:</span>
            <span class="detail-value" id="popupAge"></span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Informant Name:</span>
            <span class="detail-value" id="popupInformant"></span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Name of Deceased:</span>
            <span class="detail-value" id="popupDeceased"></span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Attachments:</span>
            <div class="detail-value" id="popupAttachment"></div>
          </div>
        </div>
        <div class="popup-actions">
          <button class="accept-btn" onclick="acceptRequest()">Accept</button>
          <button class="deny-btn" onclick="denyRequest()">Deny</button>
          <button class="go-payment-btn" style="display:none;" onclick="goToPayment()">Go to Payment</button>
        </div>
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
        
        // Trigger animation
        setTimeout(() => {
          modal.classList.add('show');
        }, 10);
        
        window.currentRequestId = requestId;
        window.currentRequestType = type;
        let url = (type === 'accepted') ? 'get_accepted_request_details.php?id=' + requestId : 'get_request_details.php?id=' + requestId;
        fetch(url)
          .then(response => response.json())
          .then(data => {
            if (data && data.success) {
              document.getElementById('popupClientName').textContent = data.name;
              document.getElementById('popupEmail').textContent = data.email;
              document.getElementById('popupType').textContent = data.type;
              document.getElementById('popupAge').textContent = data.age;
              document.getElementById('popupInformant').textContent = data.informant_name;
              document.getElementById('popupDeceased').textContent = data.deceased_name;
              document.getElementById('popupAttachment').innerHTML = data.attachment_html;
              document.getElementById('popupNicheId').textContent = data.niche_id;
              if (data.type === 'Transfer' || data.type === 'Exhumation') {
                document.getElementById('popupNicheIdRow').style.display = '';
              } else {
                document.getElementById('popupNicheIdRow').style.display = 'none';
              }
              // Hide Accept/Deny for accepted
              document.querySelector('.accept-btn').style.display = (type === 'accepted') ? 'none' : '';
              document.querySelector('.deny-btn').style.display = (type === 'accepted') ? 'none' : '';
              if (type === 'accepted') {
                document.querySelector('.go-payment-btn').style.display = '';
                // Store details for payment redirect
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
      
      function acceptRequest() {
        // Get the requestId from the popup (store it globally when opening popup)
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
              location.reload();
            } else {
              alert('Failed to accept request: ' + (data.message || 'Unknown error'));
            }
          });
        }
      }
      
      function denyRequest() {
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
              location.reload();
            } else {
              alert('Failed to deny request: ' + (data.message || 'Unknown error'));
            }
          });
        }
      }
      
      function goToPayment() {
        let apt = window.currentNicheId;
        let informant = window.currentInformant;
        if (!apt) apt = 'Null';
        const params = new URLSearchParams({
          apartment: apt,
          informant: informant || ''
        });
        window.location.href = 'Ledger.php?' + params.toString();
      }
    </script>
  </main>

</body>
</html>





























