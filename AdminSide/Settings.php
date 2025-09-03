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
  <title>RestEase Admin Dashboard - Settings</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/Settings.css">
  <link rel="stylesheet" href="../css/sidebar.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
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
          <div class="settings-tab" data-tab="notification">Notification</div>
        </div>
        <div class="settings-card" id="accountTab">
          <div style="font-size: 1.13rem; font-weight: 600; color: #222;">Account</div>
          <div style="color: #888; font-size: 0.97rem; margin-bottom: 18px;">
            Real-time information and activities of your property.
          </div>
          <div class="settings-account-header">
            <img src="../assets/Default Image.jpg" alt="Profile" class="settings-profile-img">
            <div class="settings-profile-info">
              <div class="settings-profile-name">Sybau</div>
              <div class="settings-profile-email">sybau@gmail.com</div>
            </div>
            <div class="settings-profile-actions" style="flex-direction: row; gap: 8px; margin-left: auto;">
              <button id="uploadPicBtn" style="border: 1px solid #ccc; box-shadow: 0 2px 6px rgba(0,0,0,0.10);">Upload new picture</button>
              <input type="file" id="profilePicInput" accept="image/*" style="display:none;">
              <button class="delete-btn" style="border: 1px solid #ccc; box-shadow: 0 2px 6px rgba(0,0,0,0.10);">Delete</button>
            </div>
          </div>
          <div class="settings-section-title">Personal Information</div>
          <div class="settings-fields-row">
            <div class="settings-field-group">
              <label for="displayName">Display Name</label>
              <input type="text" id="displayName" value="Sybau">
            </div>
            <div class="settings-field-group">
              <label for="firstName">First Name</label>
              <input type="text" id="firstName" value="Kierra">
            </div>
            <div class="settings-field-group">
              <label for="lastName">Last Name</label>
              <input type="text" id="lastName" value="Vaccaro">
            </div>
          </div>
          <hr style="margin: 5px 0;">
          <div class="settings-section-title">Contact Email</div>
          <div style="color: #888; font-size: 0.97rem; margin-bottom: 10px;">
            Manage your contact email address here
          </div>
          <div class="settings-fields-row">
            <div class="settings-field-group">
              <label for="email">Email Address</label>
              <input type="email" id="email" value="sybau@gmail.com" readonly>
            </div>
            <div class="settings-field-group">
              <label for="phone">Phone Number</label>
              <input type="text" id="phone" value="+935 734 6817">
            </div>
            <div class="settings-field-group">
              <label for="role">Role</label>
              <input type="text" id="role" value="Admin" readonly>
            </div>
          </div>
          <hr style="margin: 5px 0;">
          <div class="settings-section-title">Password</div>
          <div style="color: #888; font-size: 0.97rem; margin-bottom: 10px;">
            Modify your password
          </div>
          <div class="settings-fields-row" style="max-width: 350px;">
            <div class="settings-field-group" style="width: 100%;">
              <label for="currentPassword">Current password</label>
              <div style="position: relative;">
                <input type="password" id="currentPassword" value="passwordpassword" style="width: 100%; padding-right: 38px;">
                <span id="togglePassword" style="position: absolute; right: -40px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #888;">
                  <i class="fa fa-eye"></i>
                </span>
              </div>
            </div>
          </div>
          <!-- Fixed Save Button inside card -->
          <button id="cardSaveBtn" style="position:absolute;right:32px;bottom:32px;z-index:10;background:#2ecc71;color:#fff;border:none;border-radius:6px;padding:12px 28px;font-size:1.1rem;font-weight:600;box-shadow:0 4px 16px rgba(46,204,113,0.15);cursor:pointer;display:none;">
            Save Changes
          </button>
        </div>
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
            <div style="margin-bottom:12px;">
              <span class="archive-search-bar">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search Clients">
              </span>
            </div>
            <div style="overflow-x:auto;">
              <table style="width:100%;border-collapse:separate;border-spacing:0 4px;font-size:0.97rem;">
                <thead>
                  <tr style="background:#fafbfc;">
                    <th style="padding:10px 8px;text-align:left;">Client Name</th>
                    <th style="padding:10px 8px;text-align:left;">Email</th>
                    <th style="padding:10px 8px;text-align:left;">Contact</th>
                    <th style="padding:10px 8px;text-align:left;">Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  // Database connection (adjust credentials as needed)
                  $conn = new mysqli("localhost", "root", "", "cemeterydb");
                  if ($conn->connect_error) {
                    echo '<tr><td colspan="4">Database connection failed.</td></tr>';
                  } else {
                    $result = $conn->query("SELECT * FROM archive_clients ORDER BY archived_at DESC");
                    if ($result && $result->num_rows > 0) {
                      while ($row = $result->fetch_assoc()) {
                        $firstName = htmlspecialchars($row['first_name']);
                        $lastName = htmlspecialchars($row['last_name']);
                        $name = $firstName . ' ' . $lastName;
                        $email = htmlspecialchars($row['email']);
                        $contact = htmlspecialchars($row['contact_no']);
                        $initials = strtoupper(mb_substr($firstName, 0, 1, 'UTF-8') . mb_substr($lastName, 0, 1, 'UTF-8'));
                        $colorIndex = (abs(crc32($firstName . $lastName)) % 10) + 1;
                        $colorClass = "avatar-color-$colorIndex";
                        echo '<tr style="background:#fff;">';
                        echo '<td style="padding:8px 8px; display:flex; align-items:center;">';
                        echo '<div class="avatar-img avatar-google ' . $colorClass . '">' . $initials . '</div>';
                        echo '<span class="client-name" style="margin-left:4px; display:inline-block;">' . $name . '</span>';
                        echo '</td>';
                        echo '<td style="padding:8px 8px;">' . $email . '</td>';
                        echo '<td style="padding:8px 8px;">' . $contact . '</td>';
                        echo '<td style="padding:8px 8px;"><span style="background:#f8d7da;color:#c0392b;padding:4px 14px;border-radius:6px;font-size:0.95em;">Archived</span></td>';
                        echo '</tr>';
                      }
                    } else {
                      echo '<tr><td colspan="4">No archived clients found.</td></tr>';
                    }
                    $conn->close();
                  }
                  ?>
                </tbody>
              </table>
            </div>
            <!-- Pagination (static example) -->
             
              <div style="margin-top:18px;display:flex;align-items:center;gap:8px;font-size:0.97em;color:#888;justify-content:center;position:relative;min-height:36px;">
                <span style="position:absolute;left:0;top:50%;transform:translateY(-50%);">Page 1 of 3</span>
                <div>
                  <button style="border:none;background:#f4f4f4;padding:4px 10px;border-radius:4px;cursor:pointer;color:#888;" disabled>&lt;</button>
                  <button style="border:none;background:#f4f4f4;padding:4px 10px;border-radius:4px;cursor:pointer;color:#888;">1</button>
                  <button style="border:none;background:#6c8ebf;color:#fff;padding:4px 10px;border-radius:4px;cursor:pointer;">2</button>
                  <button style="border:none;background:#f4f4f4;padding:4px 10px;border-radius:4px;cursor:pointer;color:#888;">3</button>
                  <button style="border:none;background:#f4f4f4;padding:4px 10px;border-radius:4px;cursor:pointer;color:#888;">&gt;</button>
                </div>
              </div>
          </div>
          <!-- Archive Records Section -->
          <div id="archiveRecordsTab" style="display:none;">
            <div class="settings-section">
              <h2>Archive Records</h2>
              <div class="archive-table-container">
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
                    include_once '../Includes/db.php';
                    if ($conn->connect_error) {
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
                      $conn->close();
                    }
                    ?>
                  </tbody>
                </table>
              </div>
            </div>

            <style>
            .archive-table-container {
              margin: 24px 0;
              overflow-x: auto;
            }
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
            <div style="margin-bottom:12px;">
              <span class="archive-search-bar">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search Requests" id="archiveRequestSearchInput">
              </span>
            </div>
            <div style="overflow-x:auto;">
              <table style="width:100%;border-collapse:separate;border-spacing:0 4px;font-size:0.97rem;">
                <thead>
                  <tr style="background:#fafbfc;">
                    <th style="padding:10px 8px;text-align:left;">Client Name</th>
                    <th style="padding:10px 8px;text-align:left;">Email</th>
                    <th style="padding:10px 8px;text-align:left;">Type</th>
                    <th style="padding:10px 8px;text-align:left;">Status</th>
                    <th style="padding:10px 8px;text-align:left;">Details</th>
                  </tr>
                </thead>
                <tbody id="archiveRequestTableBody">
                  <?php
                  $conn = new mysqli("localhost", "root", "", "cemeterydb");
                  if ($conn->connect_error) {
                    echo '<tr><td colspan="5">Database connection failed.</td></tr>';
                  } else {
                    $sql = "SELECT dr.*, u.email, u.first_name AS user_first_name, u.last_name AS user_last_name FROM denied_request dr JOIN users u ON dr.user_id = u.id ORDER BY dr.created_at DESC";
                    $result = $conn->query($sql);
                    if ($result && $result->num_rows > 0) {
                      while ($row = $result->fetch_assoc()) {
                        $firstName = htmlspecialchars($row['user_first_name']);
                        $lastName = htmlspecialchars($row['user_last_name']);
                        $name = $firstName . ' ' . $lastName;
                        $email = htmlspecialchars($row['email']);
                        $type = htmlspecialchars($row['type']);
                        $status = '<span style="background:#f8d7da;color:#c0392b;padding:4px 14px;border-radius:6px;font-size:0.95em;">Denied</span>';
                        echo '<tr style="background:#fff;">';
                        echo '<td style="padding:8px 8px;">' . $name . '</td>';
                        echo '<td style="padding:8px 8px;">' . $email . '</td>';
                        echo '<td style="padding:8px 8px;">' . $type . '</td>';
                        echo '<td style="padding:8px 8px;">' . $status . '</td>';
                        echo '<td style="padding:8px 8px;"><button class="view-btn" onclick="openDeniedPopup(' . $row['id'] . ')">View</button></td>';
                        echo '</tr>';
                      }
                    } else {
                      echo '<tr><td colspan="5">No denied requests found.</td></tr>';
                    }
                    $conn->close();
                  }
                  ?>
                </tbody>
              </table>
            </div>
          </div>
          <!-- Denied Request Popup Modal -->
          <div id="deniedPopupModal" class="popup-modal" style="display:none;">
            <div class="popup-content">
              <span class="close-btn" onclick="closeDeniedPopup()">&times;</span>
              <div class="popup-details" style="display: flex; flex-direction: column; gap: 5px;">
                <p><b>Client Name:</b> <span id="deniedPopupClientName" style="color:#888;"></span></p>
                <p><b>Email:</b> <span id="deniedPopupEmail" style="color:#888;"></span></p>
                <p><b>Type:</b> <span id="deniedPopupType" style="color:#888;"></span></p>
                <p id="deniedPopupNicheIdRow" style="display:none;"><b>Niche ID:</b> <span id="deniedPopupNicheId" style="color:#888;"></span></p>
                <p><b>Age:</b> <span id="deniedPopupAge" style="color:#888;"></span></p>
                <p><b>Informant Name:</b> <span id="deniedPopupInformant" style="color:#888;"></span></p>
                <p><b>Name of Deceased:</b> <span id="deniedPopupDeceased" style="color:#888;"></span></p>
                <p><b>Attachments:</b></p>
                <div id="deniedPopupAttachment"></div>
              </div>
            </div>
          </div>
          <style>
            .popup-modal { position: fixed; z-index: 9999; left: 0; top: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.18); display: flex; align-items: center; justify-content: center; }
            .popup-content { background: #fff; border-radius: 18px; padding: 48px 56px 32px 56px; min-width: 420px; max-width: 95vw; min-height: 340px; box-shadow: 0 8px 32px rgba(60,60,60,0.18), 0 1.5px 6px rgba(0,0,0,0.08); position: relative; font-family: 'Inter', sans-serif; border-top: 6px solid #506C84; transition: box-shadow 0.2s; }
            .close-btn { position: absolute; top: 18px; right: 22px; font-size: 2.1rem; color: #e74c3c; cursor: pointer; font-weight: 400; transition: color 0.18s; }
            .close-btn:hover { color: #b91c1c; }
            .popup-details p { margin: 0 0 18px 0; font-size: 1.13rem; font-weight: 500; display: flex; align-items: center; gap: 8px; }
            .popup-details b { font-weight: 600; color: #222; min-width: 170px; display: inline-block; opacity: 0.7; }
            .popup-details span { color: #666; font-weight: 500; font-size: 1.13rem; }
            .attachment-box { border: 1px solid #e5e7eb; border-radius: 7px; padding: 10px 18px; background: #fafbfc; display: flex; align-items: center; width: fit-content; margin-bottom: 18px; margin-top: 4px; font-size: 1.08rem; }
            /* View button style copied from Clients.css for consistency */
            .btn-view, .view-btn, button.view-btn, button.btn-view {
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
            .btn-view:hover, .view-btn:hover, button.view-btn:hover, button.btn-view:hover {
              background: #7fa0bb;
              color: #fff;
            }
          </style>
          <script>
            function openDeniedPopup(requestId) {
              fetch('get_denied_request_details.php?id=' + requestId)
                .then(response => response.json())
                .then(data => {
                  if (data && data.success) {
                    document.getElementById('deniedPopupClientName').textContent = data.name;
                    document.getElementById('deniedPopupEmail').textContent = data.email;
                    document.getElementById('deniedPopupType').textContent = data.type;
                    document.getElementById('deniedPopupAge').textContent = data.age;
                    document.getElementById('deniedPopupInformant').textContent = data.informant_name;
                    document.getElementById('deniedPopupDeceased').textContent = data.deceased_name;
                    document.getElementById('deniedPopupAttachment').innerHTML = data.attachment_html;
                    document.getElementById('deniedPopupModal').style.display = 'flex';
                    document.getElementById('deniedPopupNicheId').textContent = data.niche_id;
                    if (data.type === 'Transfer' || data.type === 'Exhumation') {
                      document.getElementById('deniedPopupNicheIdRow').style.display = '';
                    } else {
                      document.getElementById('deniedPopupNicheIdRow').style.display = 'none';
                    }
                  }
                });
            }
            function closeDeniedPopup() {
              document.getElementById('deniedPopupModal').style.display = 'none';
            }
            window.onclick = function(event) {
              var modal = document.getElementById('deniedPopupModal');
              if (event.target === modal) {
                closeDeniedPopup();
              }
            }
          </script>
        </div>
        <div class="settings-card" id="notificationTab" style="display:none;">
          <div style="font-size: 1.13rem; font-weight: 600; color: #222;">Notification</div>
          <div style="color: #888; font-size: 0.97rem; margin-bottom: 18px;">
            Notification settings and preferences will be shown here.
          </div>
        </div>
        <!-- Unsaved changes bar -->
        <div class="settings-unsaved-bar" id="unsavedBar" style="display: none;">
          <span>Careful — you have unsaved changes!</span>
          <span class="reset-link" id="resetLink">Reset</span>
          <button class="save-btn" id="saveBtn">Save Changes</button>
        </div>
      </section>
    </div>
  </main>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
  <script>
    // Track if there are unsaved changes
    let unsaved = false;
    // Store original values for reset
    const originalValues = {};
    document.querySelectorAll('.settings-card input').forEach(input => {
      originalValues[input.id] = input.value;
    });

    // Mark as unsaved on input change
    document.querySelectorAll('.settings-card input').forEach(input => {
      input.addEventListener('input', () => { unsaved = true; });
    });

    // Tab switching logic
    const tabs = document.querySelectorAll('.settings-tab');
    const tabContents = {
      account: document.getElementById('accountTab'),
      archive: document.getElementById('archiveTab'),
      notification: document.getElementById('notificationTab')
    };
    tabs.forEach(tab => {
      tab.addEventListener('click', function(e) {
        if (!this.classList.contains('active')) {
          if (unsaved) {
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

    // Prevent sidebar navigation if unsaved changes
    document.querySelectorAll('.sidebar a').forEach(link => {
      link.addEventListener('click', function(e) {
        if (unsaved) {
          document.getElementById('unsavedBar').style.display = 'flex';
          e.preventDefault();
        }
      });
    });

    // Save and reset handlers
    document.getElementById('saveBtn').onclick = function() {
      unsaved = false;
      document.getElementById('unsavedBar').style.display = 'none';
      // ...add save logic here...
    };
    document.getElementById('resetLink').onclick = function() {
      // Restore original values
      document.querySelectorAll('.settings-card input').forEach(input => {
        if (originalValues.hasOwnProperty(input.id)) {
          input.value = originalValues[input.id];
        }
      });
      unsaved = false;
      document.getElementById('unsavedBar').style.display = 'none';
      updateCardSaveBtn && updateCardSaveBtn();
      // ...add reset logic here...
    };

    // Card Save Button logic
    const cardSaveBtn = document.getElementById('cardSaveBtn');
    function updateCardSaveBtn() {
      cardSaveBtn.style.display = unsaved ? 'block' : 'none';
    }
    document.querySelectorAll('.settings-card input').forEach(input => {
      input.addEventListener('input', () => {
        unsaved = true;
        updateCardSaveBtn();
      });
    });
    document.getElementById('saveBtn').onclick = function() {
      unsaved = false;
      document.getElementById('unsavedBar').style.display = 'none';
      updateCardSaveBtn();
      // ...add save logic here...
    };
    cardSaveBtn.onclick = function() {
      unsaved = false;
      updateCardSaveBtn();
      // ...add save logic here...
    };
    document.getElementById('resetLink').onclick = function() {
      // Restore original values
      document.querySelectorAll('.settings-card input').forEach(input => {
        if (originalValues.hasOwnProperty(input.id)) {
          input.value = originalValues[input.id];
        }
      });
      unsaved = false;
      document.getElementById('unsavedBar').style.display = 'none';
      updateCardSaveBtn && updateCardSaveBtn();
      // ...add reset logic here...
    };

    // Profile picture upload logic
    const uploadPicBtn = document.getElementById('uploadPicBtn');
    const profilePicInput = document.getElementById('profilePicInput');
    const profileImg = document.querySelector('.settings-profile-img');
    uploadPicBtn.onclick = function(e) {
      e.preventDefault();
      profilePicInput.click();
    };
    profilePicInput.onchange = function(e) {
      const file = e.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function(ev) {
          profileImg.src = ev.target.result;
          unsaved = true;
          updateCardSaveBtn && updateCardSaveBtn();
        };
        reader.readAsDataURL(file);
      }
    };

    // Password show/hide logic
    const currentPasswordInput = document.getElementById('currentPassword');
    const togglePassword = document.getElementById('togglePassword');
    togglePassword.onclick = function() {
      const isHidden = currentPasswordInput.type === 'password';
      currentPasswordInput.type = isHidden ? 'text' : 'password';
      this.querySelector('i').className = isHidden ? 'fa fa-eye-slash' : 'fa fa-eye';
    };

    // Archive sub-tab switching
    const archiveTabs = document.querySelectorAll('.archive-subtab');
    const archiveTabContents = {
      clients: document.getElementById('archiveClientsTab'),
      records: document.getElementById('archiveRecordsTab'),
      requests: document.getElementById('archiveRequestsTab')
    };
    archiveTabs.forEach(tab => {
      tab.addEventListener('click', function() {
        if (!this.classList.contains('active')) {
          archiveTabs.forEach(t => {
            t.classList.remove('active');
            t.style.color = '#888';
            t.style.borderBottom = 'none';
          });
          this.classList.add('active');
          this.style.color = '#2d72d9';
          this.style.borderBottom = '2px solid #2d72d9';
          Object.values(archiveTabContents).forEach(tc => tc.style.display = 'none');
          archiveTabContents[this.dataset.archivetab].style.display = '';
        }
      });
    });
    // Archive Request search filter
    document.addEventListener('DOMContentLoaded', function() {
      var searchInput = document.getElementById('archiveRequestSearchInput');
      if (searchInput) {
        searchInput.addEventListener('keyup', function() {
          var filter = searchInput.value.toLowerCase();
          var table = document.getElementById('archiveRequestTableBody');
          var trs = table.getElementsByTagName('tr');
          for (var i = 0; i < trs.length; i++) {
            var tds = trs[i].getElementsByTagName('td');
            var found = false;
            for (var j = 0; j < tds.length; j++) {
              if (tds[j].textContent.toLowerCase().indexOf(filter) > -1) {
                found = true;
                break;
              }
            }
            trs[i].style.display = found ? '' : 'none';
          }
        });
      }
    });

    // Action buttons in archive clients table (restore and delete)
    document.querySelectorAll('.restore-btn').forEach(btn => {
      btn.onclick = function() {
        const row = this.closest('tr');
        row.parentNode.removeChild(row);
        // ...add restore logic here...
      };
    });
    document.querySelectorAll('.delete-btn').forEach(btn => {
      btn.onclick = function() {
        const row = this.closest('tr');
        row.parentNode.removeChild(row);
        // ...add delete logic here...
      };
    });
    $(document).ready(function() {
      $('#archiveRecordsTable').DataTable({
        paging: true,
        searching: true,
        ordering: true,
        info: true
      });
    });
  </script>
</body>
</html>
