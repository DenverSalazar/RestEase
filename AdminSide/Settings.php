<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../AdminLogin.php");
    exit;
}
// Fetch admin info from new admin_profiles table
include_once '../Includes/db.php';
$adminId = $_SESSION['admin_id'];
$adminInfo = [
  'display_name' => '',
  'first_name' => '',
  'last_name' => '',
  'email' => '',
  'phone' => '',
  'role' => 'Admin',
  'profile_pic' => '../assets/Default Image.jpg'
];
// Get email from admin_accounts
$email = '';
if ($conn && !$conn->connect_error) {
  $stmt = $conn->prepare('SELECT email FROM admin_accounts WHERE id = ? LIMIT 1');
  $stmt->bind_param('i', $adminId);
  $stmt->execute();
  $stmt->bind_result($email);
  if ($stmt->fetch()) {
    $adminInfo['email'] = $email;
  }
  $stmt->close();
  // Get profile info from admin_profiles
  $stmt2 = $conn->prepare('SELECT display_name, first_name, last_name, phone, role, profile_pic FROM admin_profiles WHERE admin_id = ? LIMIT 1');
  $stmt2->bind_param('i', $adminId);
  $stmt2->execute();
  $stmt2->bind_result($displayName, $firstName, $lastName, $phone, $role, $profilePic);
  if ($stmt2->fetch()) {
    $adminInfo['display_name'] = $displayName;
    $adminInfo['first_name'] = $firstName;
    $adminInfo['last_name'] = $lastName; // Fixed variable name
    $adminInfo['phone'] = $phone;
    $adminInfo['role'] = $role;
    $adminInfo['profile_pic'] = $profilePic ? $profilePic : '../assets/Default Image.jpg';
  }
  $stmt2->close();
}
// Handle profile update and profile picture upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['save_profile']) || isset($_POST['upload_profile_pic']))) {
  $displayName = trim($_POST['displayName'] ?? '');
  $firstName = trim($_POST['firstName'] ?? '');
  $lastName = trim($_POST['lastName'] ?? '');
  $phone = trim($_POST['phone'] ?? '');
  $role = trim($_POST['role'] ?? 'Admin');
  $profilePicPath = $adminInfo['profile_pic'];
  // Handle profile picture upload
  if (isset($_FILES['profilePicInput']) && $_FILES['profilePicInput']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '../uploads/';
    if (!is_dir($uploadDir)) {
      mkdir($uploadDir, 0777, true);
    }
    $fileName = 'admin_' . $adminId . '_' . time() . '_' . basename($_FILES['profilePicInput']['name']);
    $targetFile = $uploadDir . $fileName;
    if (move_uploaded_file($_FILES['profilePicInput']['tmp_name'], $targetFile)) {
      $profilePicPath = $targetFile;
    }
  }
  // Update or insert profile
  $stmt = $conn->prepare('SELECT id FROM admin_profiles WHERE admin_id = ? LIMIT 1');
  $stmt->bind_param('i', $adminId);
  $stmt->execute();
  $stmt->store_result();
  if ($stmt->num_rows > 0) {
    $stmt->close();
    $stmt2 = $conn->prepare('UPDATE admin_profiles SET display_name=?, first_name=?, last_name=?, phone=?, role=?, profile_pic=? WHERE admin_id=?');
    $stmt2->bind_param('ssssssi', $displayName, $firstName, $lastName, $phone, $role, $profilePicPath, $adminId);
    $stmt2->execute();
    $stmt2->close();
  } else {
    $stmt->close();
    $stmt2 = $conn->prepare('INSERT INTO admin_profiles (admin_id, display_name, first_name, last_name, phone, role, profile_pic) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt2->bind_param('issssss', $adminId, $displayName, $firstName, $lastName, $phone, $role, $profilePicPath);
    $stmt2->execute();
    $stmt2->close();
  }
  // Update adminInfo array with new values
  $adminInfo['display_name'] = $displayName;
  $adminInfo['first_name'] = $firstName;
  $adminInfo['last_name'] = $lastName;
  $adminInfo['phone'] = $phone;
  $adminInfo['role'] = $role;
  $adminInfo['profile_pic'] = $profilePicPath;
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
          <form method="POST" id="profileForm" enctype="multipart/form-data">
          <div class="settings-account-header">
            <img src="<?php echo htmlspecialchars($adminInfo['profile_pic']); ?>" alt="Profile" class="settings-profile-img">
            <div class="settings-profile-info">
              <div class="settings-profile-name"><?php echo htmlspecialchars($adminInfo['display_name']); ?></div>
              <div class="settings-profile-email"><?php echo htmlspecialchars($adminInfo['email']); ?></div>
            </div>
            <div class="settings-profile-actions" style="flex-direction: row; gap: 8px; margin-left: auto;">
              <button id="uploadPicBtn" style="border: 1px solid #ccc; box-shadow: 0 2px 6px rgba(0,0,0,0.10);" type="button">Upload new picture</button>
              <input type="file" id="profilePicInput" name="profilePicInput" accept="image/*" style="display:none;">
            </div>
          </div>
          <div class="settings-section-title">Personal Information</div>
          <div class="settings-fields-row">
            <div class="settings-field-group">
              <label for="displayName">Display Name</label>
              <input type="text" id="displayName" name="displayName" value="<?php echo htmlspecialchars($adminInfo['display_name']); ?>">
            </div>
            <div class="settings-field-group">
              <label for="firstName">First Name</label>
              <input type="text" id="firstName" name="firstName" value="<?php echo htmlspecialchars($adminInfo['first_name']); ?>">
            </div>
            <div class="settings-field-group">
              <label for="lastName">Last Name</label>
              <input type="text" id="lastName" name="lastName" value="<?php echo htmlspecialchars($adminInfo['last_name']); ?>">
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
              <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($adminInfo['email']); ?>" readonly>
            </div>
            <div class="settings-field-group">
              <label for="phone">Phone Number</label>
              <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($adminInfo['phone']); ?>">
            </div>
            <div class="settings-field-group">
              <label for="role">Role</label>
              <input type="text" id="role" name="role" value="<?php echo htmlspecialchars($adminInfo['role']); ?>" readonly>
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
          <button id="cardSaveBtn" name="save_profile" type="submit" style="position:absolute;right:32px;bottom:32px;z-index:10;background:#2ecc71;color:#fff;border:none;border-radius:6px;padding:12px 28px;font-size:1.1rem;font-weight:600;box-shadow:0 4px 16px rgba(46,204,113,0.15);cursor:pointer;display:none;">
            Save Changes
          </button>
          </form>
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
            <div class="settings-section">
              <h2 style="margin-bottom:12px;">Archive Clients</h2>
              <div style="margin-bottom:12px;">
                <span class="archive-search-bar">
                  <i class="fas fa-search"></i>
                  <input type="text" placeholder="Search Clients" id="archiveClientsSearchInput">
                </span>
              </div>
              <div style="overflow-x:auto;">
                <table id="archiveClientsTable" class="archive-table" style="width:100%;border-collapse:separate;border-spacing:0 4px;font-size:0.97rem;">
                  <thead>
                    <tr style="background:#fafbfc;">
                      <th style="padding:10px 8px;text-align:left;">Avatar</th>
                      <th style="padding:10px 8px;text-align:left;">Client Name</th>
                      <th style="padding:10px 8px;text-align:left;">Email</th>
                      <th style="padding:10px 8px;text-align:left;">Contact</th>
                      <th style="padding:10px 8px;text-align:left;">Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    $conn = new mysqli("localhost", "root", "", "cemeterydb");
                    if ($conn->connect_error) {
                      echo '<tr><td colspan="5">Database connection failed.</td></tr>';
                    } else {
                      $result = $conn->query("SELECT * FROM archive_clients ORDER BY archived_at DESC");
                      if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                          $firstName = htmlspecialchars($row['first_name']);
                          $lastName = htmlspecialchars($row['last_name']);
                          $name = $firstName . ' ' . $lastName;
                          $email = htmlspecialchars($row['email']);
                          $contact = htmlspecialchars($row['contact_no']);
                          $profilePic = isset($row['profile_pic']) && $row['profile_pic'] ? $row['profile_pic'] : '';
                          $initials = strtoupper(mb_substr($firstName, 0, 1, 'UTF-8') . mb_substr($lastName, 0, 1, 'UTF-8'));
                          $colorIndex = (abs(crc32($firstName . $lastName)) % 10) + 1;
                          $colorClass = "avatar-color-$colorIndex";
                          echo '<tr style="background:#fff;">';
                          echo '<td style="padding:8px 8px;">';
                          if ($profilePic) {
                            echo '<img src="' . htmlspecialchars($profilePic) . '" alt="Avatar" class="avatar-img" style="width:36px;height:36px;border-radius:50%;object-fit:cover;display:block;">';
                          } else {
                            echo '<div class="avatar-img ' . $colorClass . '" style="width:36px;height:36px;border-radius:50%;font-weight:600;font-size:1.1em;color:#fff;line-height:36px;text-align:center;">' . $initials . '</div>';
                          }
                          echo '</td>';
                          echo '<td style="padding:8px 8px;">' . $name . '</td>';
                          echo '<td style="padding:8px 8px;">' . $email . '</td>';
                          echo '<td style="padding:8px 8px;">' . $contact . '</td>';
                          echo '<td style="padding:8px 8px;"><span style="background:#f8d7da;color:#c0392b;padding:4px 14px;border-radius:6px;font-size:0.95em;">Archived</span></td>';
                          echo '</tr>';
                        }
                      } else {
                        echo '<tr><td colspan="5">No archived clients found.</td></tr>';
                      }
                      // $conn->close();
                    }
                    ?>
                  </tbody>
                </table>
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
                      // $conn->close();
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
                    $conn = new mysqli("localhost", "root", "", "cemeterydb");
                    if ($conn->connect_error) {
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
                <div class="detail-row">
                  <span class="detail-label">Age:</span>
                  <span class="detail-value" id="deniedPopupAge"></span>
                </div>
                <div class="detail-row">
                  <span class="detail-label">Attachments:</span>
                  <div class="detail-value" id="deniedPopupAttachment"></div>
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
            function openDeniedPopup(requestId) {
              const modal = document.getElementById('deniedPopupModal');
              modal.style.display = 'flex';
              setTimeout(() => { modal.classList.add('show'); }, 10);
              fetch('get_denied_request_details.php?id=' + requestId)
                .then(response => response.json())
                .then(data => {
                  if (data && data.success) {
                    // Build full deceased name
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
                    // Show Niche ID only if type is Transfer
                    if (data.type && data.type.toLowerCase() === 'transfer') {
                      document.getElementById('deniedPopupNicheIdRow').style.display = '';
                    } else {
                      document.getElementById('deniedPopupNicheIdRow').style.display = 'none';
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
    // Only mark unsaved for profile form fields, not search boxes
    const profileInputIds = [
      'displayName', 'firstName', 'lastName', 'email', 'phone', 'role', 'currentPassword'
    ];
    document.querySelectorAll('.settings-card input').forEach(input => {
      if (profileInputIds.includes(input.id)) {
        input.addEventListener('input', () => { unsaved = true; });
      }
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
      if (profileInputIds.includes(input.id)) {
        input.addEventListener('input', () => {
          unsaved = true;
          updateCardSaveBtn();
        });
      }
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
        const formData = new FormData(document.getElementById('profileForm'));
        formData.append('upload_profile_pic', '1');
        fetch('', {
          method: 'POST',
          body: formData
        })
        .then(response => response.text())
        .then(() => {
          const reader = new FileReader();
          reader.onload = function(ev) {
            profileImg.src = ev.target.result;
          };
          reader.readAsDataURL(file);
          location.reload(); // Reload to get updated image from server
        });
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
    let archiveRequestsTableInstance = null;
    archiveTabs.forEach(tab => {
      tab.addEventListener('click', function() {
        // Restore original tab active logic
        archiveTabs.forEach(t => {
          t.classList.remove('active');
          t.style.color = '';
          t.style.borderBottom = '';
        });
        this.classList.add('active');
        this.style.color = '#2d72d9';
        this.style.borderBottom = '2px solid #2d72d9';
        Object.values(archiveTabContents).forEach(tc => tc.style.display = 'none');
        archiveTabContents[this.dataset.archivetab].style.display = '';
        // DataTable logic for Archive Requests
        if (this.dataset.archivetab === 'requests') {
          // Destroy previous instance if exists
          if ($.fn.DataTable.isDataTable('#archiveRequestsTable')) {
            $('#archiveRequestsTable').DataTable().destroy();
          }
          // Only initialize if table is visible, has thead, and has more than just the 'No denied requests found.' row
          try {
            var $table = $('#archiveRequestsTable');
            var hasDataRows = $table.find('tbody tr').length > 0 && !$table.find('tbody tr td').first().text().includes('No denied requests found');
            if ($table.is(':visible') && $table.find('thead tr th').length > 0 && hasDataRows) {
              archiveRequestsTableInstance = $table.DataTable({
                dom: 'lrtip', // Hide default DataTables search bar
                paging: true,
                searching: true,
                ordering: true,
                info: true,
                autoWidth: false
              });
              // Custom search
              $('#archiveRequestSearchInput').off('keyup').on('keyup', function() {
                archiveRequestsTableInstance.search(this.value).draw();
              });
            }
          } catch (e) {
            // Suppress DataTables warning
            console.warn('DataTables init suppressed:', e);
          }
        } else {
          // Destroy DataTable if leaving requests tab
          if ($.fn.DataTable.isDataTable('#archiveRequestsTable')) {
            $('#archiveRequestsTable').DataTable().destroy();
          }
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
      // Archive Records Table DataTables initialization with strict checks
      var $archiveRecordsTable = $('#archiveRecordsTable');
      var $theadRecords = $archiveRecordsTable.find('thead');
      var $tbodyRecords = $archiveRecordsTable.find('tbody');
      var hasTheadRecords = $theadRecords.length > 0 && $theadRecords.find('tr th').length > 0;
      var hasTbodyRecords = $tbodyRecords.length > 0;
      var colCountRecords = hasTheadRecords ? $theadRecords.find('tr th').length : 0;
      var validRowsRecords = false;
      var archiveRecordsTableInstance = null;
      if (hasTbodyRecords) {
        $tbodyRecords.find('tr').each(function() {
          if ($(this).find('td').length === colCountRecords) {
            validRowsRecords = true;
            return false; // break loop
          }
        });
      }
      if (hasTheadRecords && hasTbodyRecords && validRowsRecords) {
        try {
          archiveRecordsTableInstance = $archiveRecordsTable.DataTable({
            paging: true,
            searching: true,
            ordering: true,
            info: true,
            autoWidth: false,
            dom: 'lrtip', // Use default DataTables layout
            language: {
              lengthMenu: 'Show _MENU_ entries',
              zeroRecords: 'No records found',
              emptyTable: 'No records available',
              infoEmpty: '',
              info: 'Showing _START_ to _END_ of _TOTAL_',
              infoFiltered: '',
              infoPostFix: '',
              thousands: ',',
              loadingRecords: 'Loading...',
              processing: 'Processing...',
              search: '',
              paginate: {
                first: 'First',
                last: 'Last',
                next: 'Next',
                previous: 'Previous'
              },
              aria: {
                sortAscending: ': activate to sort column ascending',
                sortDescending: ': activate to sort column descending'
              }
            }
          });
          // Connect custom search bar to DataTables search
          $('#archiveRecordsSearchInput').on('keyup', function() {
            archiveRecordsTableInstance.search(this.value).draw();
          });
        } catch (e) {
          // Suppress DataTables warning
          console.warn('DataTables init suppressed:', e);
        }
      } else {
        // If DataTable is not initialized, still allow custom search to filter rows manually
        $('#archiveRecordsSearchInput').on('keyup', function() {
          var filter = this.value.toLowerCase();
          $tbodyRecords.find('tr').each(function() {
            var found = false;
            $(this).find('td').each(function() {
              if ($(this).text().toLowerCase().indexOf(filter) > -1) {
                found = true;
                return false;
              }
            });
            $(this).css('display', found ? '' : 'none');
          });
        });
      }
    });
  </script>
  <style>
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
</body>
</html>
