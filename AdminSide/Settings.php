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
  $emailInput = trim($_POST['email'] ?? '');
  $profilePicPath = $adminInfo['profile_pic'];
  $emailChangeError = '';

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
  // Secure email change validation
  if ($emailInput && $emailInput !== $adminInfo['email']) {
    // Validate email format
    if (!filter_var($emailInput, FILTER_VALIDATE_EMAIL)) {
      $emailChangeError = "Invalid email format.";
    } else {
      // Require current password for email change
      $emailChangePassword = trim($_POST['emailChangePassword'] ?? '');
      if (!$emailChangePassword) {
        $emailChangeError = "Please enter your current password to change email.";
      } else {
        // Check password
        $stmtPwd = $conn->prepare('SELECT password FROM admin_accounts WHERE id=? LIMIT 1');
        $stmtPwd->bind_param('i', $adminId);
        $stmtPwd->execute();
        $stmtPwd->bind_result($hashedPwd);
        if ($stmtPwd->fetch()) {
          if (!password_verify($emailChangePassword, $hashedPwd)) {
            $emailChangeError = "Incorrect password. Email not changed.";
          }
        } else {
          $emailChangeError = "Account not found.";
        }
        $stmtPwd->close();
      }
    }
    // If no error, update email
    if (!$emailChangeError) {
      $stmtEmail = $conn->prepare('UPDATE admin_accounts SET email=? WHERE id=?');
      $stmtEmail->bind_param('si', $emailInput, $adminId);
      $stmtEmail->execute();
      $stmtEmail->close();
      $adminInfo['email'] = $emailInput;
    }
  }

  // Update adminInfo array with new values
  $adminInfo['display_name'] = $displayName;
  $adminInfo['first_name'] = $firstName;
  $adminInfo['last_name'] = $lastName;
  $adminInfo['phone'] = $phone;
  $adminInfo['role'] = $role;
  $adminInfo['profile_pic'] = $profilePicPath;
  // $adminInfo['email'] already updated above if changed
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
  <style>
#archive-clients-table_filter {
  display: none !important;
}
/* Ensure the tabs are clickable and visible above other elements */
.settings-tabs {
  position: relative;
  z-index: 60; /* bring tabs above overlays/containers */
}
.settings-tab {
  cursor: pointer;
  user-select: none;
  -webkit-user-select: none;
}
.notif-left {
  display:flex;
  align-items:center;
  gap:8px;
  min-width:72px; /* reduced since icon box is removed */
}

/* add mail icon styling */
.notif-icon{
  width:36px;
  height:36px;
  border-radius:6px;
  background:#f5f7fa;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  color:#2d72d9;
  font-weight:700;
  flex: 0 0 36px;
}

/* Add highlight style and make dot/icon clickable */
.notif-dot { cursor: pointer; }
.notif-icon { cursor: pointer; }

/* visual highlight when user "selects" the unread notif by clicking the green dot */
.notif-selected {
  background: linear-gradient(90deg, rgba(45,114,217,0.03), rgba(45,114,217,0.02));
  border: 1px solid rgba(45,114,217,0.06);
  box-shadow: 0 2px 10px rgba(45,114,217,0.03);
}

/* ensure title weight toggles smoothly */
.notif-title span { transition: font-weight 120ms ease; }
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
          <div class="settings-tab" data-tab="notification" id="notificationTabBtn" style="position:relative;">Notification <span id="notifBadge" style="display:none;position:absolute;top:-8px;right:0;background:#e74c3c;color:#fff;font-size:0.85rem;font-weight:600;padding:2px 7px;border-radius:12px;min-width:22px;text-align:center;line-height:1;box-shadow:0 1px 4px rgba(0,0,0,0.08);"></span></div>
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
              <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($adminInfo['email']); ?>">
            </div>
            <div class="settings-field-group">
              <label for="emailChangePassword">Current Password <span style="color:#e74c3c;">*</span></label>
              <input type="password" id="emailChangePassword" name="emailChangePassword" autocomplete="off" placeholder="Required for email change">
              <?php if (!empty($emailChangeError)): ?>
                <div style="color:#e74c3c;font-size:0.97em;margin-top:4px;"><?php echo htmlspecialchars($emailChangeError); ?></div>
              <?php endif; ?>
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
          <div id="changePasswordForm" autocomplete="off">
            <div class="settings-fields-row password-row" style="display:flex;gap:18px;">
              <div class="settings-field-group" style="flex:1;min-width:0;">
                <label for="currentPassword">Current password</label>
                <div style="position: relative;">
                  <input type="password" id="currentPassword" name="currentPassword" class="settings-input" autocomplete="off">
                  <span id="togglePassword" class="password-eye-icon">
                    <i class="fa fa-eye"></i>
                  </span>
                </div>
                <div id="currentPasswordError" style="color:#e74c3c;font-size:0.95em;margin-top:4px;display:none;"></div>
              </div>
              <div class="settings-field-group" style="flex:1;min-width:0;">
                <label for="newPassword">New password</label>
                <div style="position: relative;">
                  <input type="password" id="newPassword" name="newPassword" class="settings-input" disabled autocomplete="off">
                  <span id="toggleNewPassword" class="password-eye-icon">
                    <i class="fa fa-eye"></i>
                  </span>
                </div>
                <div id="newPasswordError" style="color:#e74c3c;font-size:0.95em;margin-top:4px;display:none;"></div>
              </div>
              <div class="settings-field-group" style="flex:1;min-width:0;">
                <label for="confirmPassword">Confirm new password</label>
                <div style="position: relative;">
                  <input type="password" id="confirmPassword" name="confirmPassword" class="settings-input" disabled autocomplete="off">
                  <span id="toggleConfirmPassword" class="password-eye-icon">
                    <i class="fa fa-eye"></i>
                  </span>
                </div>
                <div id="confirmPasswordError" style="color:#e74c3c;font-size:0.95em;margin-top:4px;display:none;"></div>
              </div>
            </div>
            <button id="changePasswordBtn" name="change_password" type="button" style="background:#2d72d9;color:#fff;border:none;border-radius:6px;padding:10px 24px;font-size:1rem;font-weight:600;box-shadow:0 2px 8px rgba(44,130,201,0.10);cursor:pointer;margin-top:10px;display:none;">Change Password</button>
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
                    $conn = new mysqli("localhost", "root", "", "cemeterydb");
                    if ($conn->connect_error) {
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
                    document.getElementById('deniedPopupInternmentDate').textContent = data.dateInternment || '';
                    // Show Niche ID only if type is Transfer
                    if (data.type && data.type.toLowerCase() === 'transfer') {
                      document.getElementById('deniedPopupNicheIdRow').style.display = '';
                    } else {
                      document.getElementById('deniedPopupNicheIdRow').style.display = 'none';
                    }
                    // Show current/new niche for Relocate
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
        </div>
        <div class="settings-card" id="notificationTab" style="display:none;">
          <!-- Replaced notification UI to match provided design -->
          <div style="font-size: 1.13rem; font-weight: 600; color: #222;">Notification</div>
          <div style="color: #888; font-size: 0.97rem; margin-bottom: 18px;">Notification settings and preferences will be shown here.</div>

          <!-- New notification header area (tabs + search) -->
          <div class="notif-list-wrapper" style="background:transparent;">
            <div class="notif-list-header" style="display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:14px;">
              <div style="display:flex;align-items:center;gap:18px;">
                <div class="notif-tabs" style="display:flex;gap:12px;align-items:center;">
                  <button class="notif-list-tab active" data-filter="all">
                    <span class="tab-count" id="tabAllCount">0</span>
                    <span>All</span>
                  </button>
                  <button class="notif-list-tab" data-filter="archive">
                    <span class="tab-count" id="tabArchiveCount">0</span>
                    <span>Archive</span>
                  </button>
                  <button class="notif-list-tab" data-filter="favorite">
                    <span class="tab-count" id="tabFavCount">0</span>
                    <span>Favorite</span>
                  </button>
                </div>
              </div>
              <div style="display:flex;align-items:center;gap:12px;">
                <div style="position:relative;">
                  <i class="fas fa-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#999;"></i>
                  <input id="notifSearch" type="text" placeholder="Search by Name Product" style="padding:10px 14px 10px 36px;border:1px solid #e3e7ed;border-radius:20px;min-width:240px;">
                </div>
              </div>
            </div>

            <!-- Notification list -->
            <div id="notifListContainer" style="display:flex;flex-direction:column;gap:10px;">
              <!-- JS will populate notification items here -->
            </div>
          </div>

          <!-- Small template styles for list (moved inline for single-file change) -->
          <style>
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
            .notif-left {
              display:flex;
              align-items:center;
              gap:8px;
              min-width:72px; /* reduced since icon box is removed */
            }
            .notif-dot {
              width:10px;
              height:10px;
              border-radius:50%;
              background:#b6dca6; /* green unread */
              display:inline-block;
              box-shadow:0 1px 2px rgba(0,0,0,0.06);
            }
            .notif-dot.read {
              background:transparent;
              border:1px solid #e6e9ec;
            }
            .notif-star-left {
              background: transparent;
              border: none;
              padding: 0;
              margin: 0;
              width: auto;
              height: auto;
              display: inline-flex;
              align-items: center;
              justify-content: center;
              cursor: pointer;
              color: #bfc6cc;
              font-size: 1.15rem; /* adjust icon size if needed */
            }
            .notif-star-left[aria-pressed="true"] {
              color: #f0b400;
            }
            .notif-star-left:focus {
              outline: none;
              box-shadow: none;
            }
            .notif-main {
              flex:1; min-width:0;
            }
            .notif-title {
              font-weight:600;
              color:#222;
              white-space:nowrap;
              overflow:hidden;
              text-overflow:ellipsis;
              display:flex;
              align-items:center;
              gap:8px;
            }
            .notif-body {
              color:#666;
              font-size:0.95rem;
              margin-top:4px;
              white-space:nowrap;
              overflow:hidden;
              text-overflow:ellipsis;
            }
            .notif-meta {
              text-align:right;
              min-width:110px;
              color:#9aa3ad;
              font-size:0.9rem;
            }
            .notif-actions {
              display:flex;
              align-items:center;
              gap:8px;
            }
            .notif-delete {
              background:#ff6b6b;border:none;color:#fff;padding:8px;border-radius:8px;cursor:pointer;width:36px;height:36px;display:inline-flex;align-items:center;justify-content:center;
            }
            .tab-count {
              display:inline-block;
              background:#e9eef8;
              color:#2d72d9;
              padding:3px 8px;
              border-radius:999px;
              font-weight:700;
              margin-right:8px;
              font-size:0.95rem;
            }
            .notif-list-tab {
              background:transparent;border:none;padding:8px 10px;border-radius:8px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;font-weight:600;color:#444;
            }
            .notif-list-tab.active { background:#fff;border:1px solid #e3e7ed;box-shadow:0 1px 4px rgba(0,0,0,0.03);color:#2d72d9; }
          </style>

          <!-- Client-side rendering and interactions -->
          <script>
            (function(){
              // Read server-provided arrays (fall back to localStorage) and normalize
              const systemNotifs = window.systemNotifs || JSON.parse(localStorage.getItem('systemNotifs') || '[]');
              const newUserNotifs = window.newUserNotifs || JSON.parse(localStorage.getItem('newUserNotifs') || '[]');
              const newRequestNotifs = window.newRequestNotifs || JSON.parse(localStorage.getItem('newRequestNotifs') || '[]');

              // Build unified list
              const all = [];
              systemNotifs.forEach(n => {
                all.push({
                  id: 'req_'+(n.id||Math.random()),
                  kind: 'request',
                  title: 'New client request',
                  name: n.name || '',
                  message: 'New client request received from ' + (n.name || ''),
                  time: '', // optional
                  readKey: 'notif_read_req_'+(n.id||''),
                });
              });
              newRequestNotifs.forEach(n => {
                all.push({
                  id: 'nreq_'+(n.id||Math.random()),
                  kind: 'request',
                  title: 'New client request',
                  name: n.name || '',
                  message: 'New client request received from ' + (n.name || ''),
                  time: n.created_at || '',
                  readKey: 'notif_read_nreq_'+(n.id||''),
                });
              });
              newUserNotifs.forEach(u => {
                all.push({
                  id: 'usr_'+(u.id||Math.random()),
                  kind: 'user',
                  title: 'New user registered',
                  name: u.name || '',
                  message: 'New user registered: ' + (u.name||'') + (u.email ? ' ('+u.email+')' : ''),
                  time: u.created_at || '',
                  readKey: 'notif_read_usr_'+(u.id||''),
                });
              });

              // Sort by time if available
              all.sort((a,b)=>{
                if (a.time && b.time) return new Date(b.time) - new Date(a.time);
                return 0;
              });

              // Pagination / display state
              const PAGE_SIZE = 10;
              let currentPage = 1;
              let showAll = false;

              function updateCounts() {
                const allCount = all.length;
                const archiveCount = 0;
                const favCount = all.filter(item => localStorage.getItem('notif_fav_' + item.id) === '1').length;
                document.getElementById('tabAllCount').textContent = allCount;
                document.getElementById('tabArchiveCount').textContent = archiveCount;
                document.getElementById('tabFavCount').textContent = favCount;
              }

              function renderList(filter, query) {
                const container = document.getElementById('notifListContainer');
                container.innerHTML = '';
                const q = (query||'').toLowerCase();

                // apply filter + search
                let items = all.filter(item=>{
                  if (filter==='archive') return false;
                  if (filter==='favorite' && localStorage.getItem('notif_fav_' + item.id) !== '1') return false;
                  if (!q) return true;
                  return (item.name||'').toLowerCase().includes(q) || (item.message||'').toLowerCase().includes(q);
                });

                // pagination calculation
                const total = items.length;
                const totalPages = Math.max(1, Math.ceil(total / PAGE_SIZE));
                if (currentPage > totalPages) currentPage = 1; // reset if out of range

                // slice items based on page unless showAll
                const itemsToRender = showAll ? items : items.slice((currentPage-1)*PAGE_SIZE, (currentPage-1)*PAGE_SIZE + PAGE_SIZE);

                if (itemsToRender.length === 0) {
                  const empty = document.createElement('div');
                  empty.style.color = '#888';
                  empty.style.textAlign = 'center';
                  empty.style.padding = '28px';
                  empty.textContent = 'No notifications.';
                  container.appendChild(empty);
                  // still show footer (page info/pagination) if total > 0
                  if (total > 0) appendFooter(container, total, totalPages);
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
                      <button class="notif-star-left" title="Favorite" aria-pressed="${isFav ? 'true' : 'false'}">
                        <i class="fas fa-star"></i>
                      </button>
                    </div>
                    <div class="notif-main">
                      <div class="notif-title"><span style="font-weight:${isRead ? '400' : '700'}">${item.title}${item.name ? ' — ' + item.name : ''}</span></div>
                      <div class="notif-body">${item.message}</div>
                    </div>
                    <div class="notif-meta"><div>${item.time ? (new Date(item.time)).toLocaleString() : 'Just Now'}</div></div>
                    <div class="notif-actions"><button class="notif-delete" title="Delete"><i class="fas fa-trash"></i></button></div>
                  `;

      // star handler (unchanged)
      const starBtn = row.querySelector('.notif-star-left');
      starBtn.style.color = isFav ? '#f0b400' : '#bfc6cc';
      starBtn.addEventListener('click', function(ev){
        ev.stopPropagation();
        const key = 'notif_fav_' + item.id;
        const currentlyFav = localStorage.getItem(key) === '1';
        if (currentlyFav) {
          localStorage.removeItem(key);
          starBtn.style.color = '#bfc6cc';
          starBtn.setAttribute('aria-pressed','false');
        } else {
          localStorage.setItem(key,'1');
          starBtn.style.color = '#f0b400';
          starBtn.setAttribute('aria-pressed','true');
        }
        updateCounts();
        if (currentFilter === 'favorite') renderList(currentFilter, document.getElementById('notifSearch').value);
      });

      // dot click: toggle highlight only (do NOT mark as read)
      const dotBtn = row.querySelector('.notif-dot');
      dotBtn.addEventListener('click', function(ev){
        ev.stopPropagation();
        // toggle visible highlight
        row.classList.toggle('notif-selected');
      });

      // mail icon click: mark as read, remove highlight, update UI
      const iconBtn = row.querySelector('.notif-icon');
      iconBtn.addEventListener('click', function(ev){
        ev.stopPropagation();
        const key = item.readKey;
        localStorage.setItem(key, '1');
        // visual updates
        dotBtn.classList.add('read');
        row.classList.remove('notif-selected');
        // set title weight to normal
        const titleSpan = row.querySelector('.notif-title span');
        if (titleSpan) titleSpan.style.fontWeight = '400';
        // refresh counts and badge
        updateCounts();
        refreshBadge();
      });

      // row click should ignore clicks on dot/icon/star/delete (so dot/icon handlers work)
      row.addEventListener('click', function(e){
        if (e.target.closest('.notif-delete') || e.target.closest('.notif-star-left') || e.target.closest('.notif-dot') || e.target.closest('.notif-icon')) return;
        localStorage.setItem(item.readKey, '1');
        if (item.kind === 'request') {
          window.location.href = 'ClientsRequest.php';
        } else {
          const dot = row.querySelector('.notif-dot');
          if (dot) dot.classList.add('read');
          const titleSpan = row.querySelector('.notif-title span');
          if (titleSpan) titleSpan.style.fontWeight = '400';
        }
        refreshBadge();
      });

      // delete handler (unchanged)
      row.querySelector('.notif-delete').addEventListener('click', function(ev){
        ev.stopPropagation();
        const idx = all.findIndex(a=>a.id === item.id);
        if (idx > -1) {
          all.splice(idx,1);
          // adjust current page if deletion empties current page
          const newTotal = Math.max(0, total - 1);
          const newTotalPages = Math.max(1, Math.ceil(newTotal / PAGE_SIZE));
          if (currentPage > newTotalPages) currentPage = newTotalPages;
          renderList(filter, query);
          updateCounts();
        }
      });

      container.appendChild(row);
    });

    // footer: left: Page X of Y, center: pagination controls
    appendFooter(container, total, totalPages);
  }

  // Tab behavior
  let currentFilter = 'all';
  document.querySelectorAll('.notif-list-tab').forEach(btn=>{
    btn.addEventListener('click', function(){
      document.querySelectorAll('.notif-list-tab').forEach(b=>b.classList.remove('active'));
      this.classList.add('active');
      currentFilter = this.dataset.filter;
      // reset pagination state when switching tabs
      showAll = false;
      currentPage = 1;
      renderList(currentFilter, document.getElementById('notifSearch').value);
    });
  });

  // Search behavior
  const searchEl = document.getElementById('notifSearch');
  searchEl.addEventListener('input', function(){
    // reset pagination state when searching
    showAll = false;
    currentPage = 1;
    renderList(currentFilter, this.value);
  });

  // Initialize
  updateCounts();
  renderList('all','');
  refreshBadge();

  // expose re-render functions
  window.renderNotificationsNewUI = function(){ updateCounts(); renderList(currentFilter, searchEl.value); refreshBadge(); };
})();
          </script>
          <style>
            /* small extra styles to guarantee centered pagination and left page info */
            #notifListFooter { width: 100%; box-sizing: border-box; }
            .notif-pagination button { min-width: 36px; }
          </style>

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
    // Password change logic
    $(function() {
      const currentPasswordInput = $('#currentPassword');
      const newPasswordInput = $('#newPassword');
      const confirmPasswordInput = $('#confirmPassword');
      const changePasswordBtn = $('#changePasswordBtn');
      const currentPasswordError = $('#currentPasswordError');
      const newPasswordError = $('#newPasswordError');
      let currentPasswordValid = false;

      // Validate current password via AJAX
      currentPasswordInput.on('input', function() {
        const val = $(this).val();
        if (val.length === 0) {
          currentPasswordError.hide();
          newPasswordInput.prop('disabled', true);
          confirmPasswordInput.prop('disabled', true);
          changePasswordBtn.hide();
          currentPasswordValid = false;
          return;
        }
        $.post('validate_admin_password.php', { password: val }, function(data) {
          if (data.success) {
            currentPasswordError.hide();
            newPasswordInput.prop('disabled', false);
            confirmPasswordInput.prop('disabled', false);
            currentPasswordValid = true;
          } else {
            currentPasswordError.text('Current password is incorrect.').show();
            newPasswordInput.prop('disabled', true);
            confirmPasswordInput.prop('disabled', true);
            changePasswordBtn.hide();
            currentPasswordValid = false;
          }
        }, 'json');
      });

      // Enable button if new/confirm password match and not empty
      $('#newPassword, #confirmPassword').on('input', function() {
        if (!currentPasswordValid) return;
        const newPass = newPasswordInput.val();
        const confirmPass = confirmPasswordInput.val();
        if (newPass.length < 6) {
          newPasswordError.text('Password must be at least 6 characters.').show();
          $('#confirmPasswordError').hide();
          changePasswordBtn.hide();
        } else {
          newPasswordError.hide();
          // Only show match error if confirmPassword is not empty
          if (confirmPasswordInput.val() && newPass !== confirmPasswordInput.val()) {
            $('#confirmPasswordError').text('Passwords do not match.').show();
            changePasswordBtn.hide();
          } else {
            $('#confirmPasswordError').hide();
            if (confirmPasswordInput.val()) changePasswordBtn.show();
          }
        }
      });
      $('#confirmPassword').on('input', function() {
        if (!currentPasswordValid) return;
        const newPass = newPasswordInput.val();
        const confirmPass = confirmPasswordInput.val();
        if (newPass.length < 6) {
          newPasswordError.text('Password must be at least 6 characters.').show();
          $('#confirmPasswordError').hide();
          changePasswordBtn.hide();
          return;
        }
        if (newPass !== confirmPass) {
          $('#confirmPasswordError').text('Passwords do not match.').show();
          changePasswordBtn.hide();
        } else {
          $('#confirmPasswordError').hide();
          $('#newPasswordError').hide();
          changePasswordBtn.show();
        }
      });

      // Handle password change submit
      $('#changePasswordBtn').on('click', function(e) {
        e.preventDefault();
        if (!currentPasswordValid) return;
        const newPass = newPasswordInput.val();
        const confirmPass = confirmPasswordInput.val();
        if (newPass.length < 6) {
          $('#newPasswordError').text('Password must be at least 6 characters.').show();
          $('#confirmPasswordError').hide();
          return;
        }
        if (newPass !== confirmPass) {
          $('#confirmPasswordError').text('Passwords do not match.').show();
          $('#newPasswordError').hide();
          return;
        }
        $.post('update_admin_password.php', { new_password: newPass }, function(data) {
          if (data.success) {
            newPasswordError.css('color','#27ae60').text('Password changed successfully!').show();
            setTimeout(function(){
              newPasswordError.hide();
              currentPasswordInput.val('');
              newPasswordInput.val('');
              confirmPasswordInput.val('');
              newPasswordInput.prop('disabled', true);
              confirmPasswordInput.prop('disabled', true);
              changePasswordBtn.hide();
            }, 1800);
          } else {
            newPasswordError.text('Failed to change password.').show();
          }
        }, 'json');
      });
    });

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
      'displayName', 'firstName', 'lastName', 'email', 'phone', 'role'
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
    const newPasswordInputEl = document.getElementById('newPassword');
    const toggleNewPassword = document.getElementById('toggleNewPassword');
    const confirmPasswordInputEl = document.getElementById('confirmPassword');
    const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
    togglePassword.onclick = function() {
      const isHidden = currentPasswordInput.type === 'password';
      currentPasswordInput.type = isHidden ? 'text' : 'password';
      this.querySelector('i').className = isHidden ? 'fa fa-eye-slash' : 'fa fa-eye';
    };
    toggleNewPassword.onclick = function() {
      const isHidden = newPasswordInputEl.type === 'password';
      newPasswordInputEl.type = isHidden ? 'text' : 'password';
      this.querySelector('i').className = isHidden ? 'fa fa-eye-slash' : 'fa fa-eye';
    };
    toggleConfirmPassword.onclick = function() {
      const isHidden = confirmPasswordInputEl.type === 'password';
      confirmPasswordInputEl.type = isHidden ? 'text' : 'password';
      this.querySelector('i').className = isHidden ? 'fa fa-eye-slash' : 'fa fa-eye';
    };

    // Archive sub-tab switching
    const archiveTabs = document.querySelectorAll('.archive-subtab');
    const archiveTabContents = {
      clients: document.getElementById('archiveClientsTab'),
      records: document.getElementById('archiveRecordsTab'),
      requests: document.getElementById('archiveRequestsTab')
    };
    let archiveClientsTableInstance = null;
    let archiveRequestsTableInstance = null;
    archiveTabs.forEach(tab => {
      tab.addEventListener('click', function() {
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
        // DataTable logic for Archive Clients
        if (this.dataset.archivetab === 'clients') {
          if ($.fn.DataTable.isDataTable('#archive-clients-table')) {
            $('#archive-clients-table').DataTable().destroy();
          }
          var $table = $('#archive-clients-table');
          var hasDataRows = $table.find('tbody tr').length > 0 && !$table.find('tbody tr td').first().text().includes('No archived clients found');
          if ($table.is(':visible') && $table.find('thead tr th').length > 0 && hasDataRows) {
            archiveClientsTableInstance = $table.DataTable({
              dom: 'lftip', // Hide default search box
              paging: true,
              searching: true,
              ordering: true,
              info: true,
              autoWidth: false,
              language: {
                lengthMenu: 'Show _MENU_ entries',
                zeroRecords: 'No clients found',
                emptyTable: 'No clients available',
                infoEmpty: '',
                info: 'Showing _START_ to _END_ of _TOTAL_',
                infoFiltered: '',
                paginate: {
                  first: 'First',
                  last: 'Last',
                  next: 'Next',
                  previous: 'Previous'
                }
              }
            });
            $('#archiveClientsSearchInput').off('keyup').on('keyup', function() {
              archiveClientsTableInstance.search(this.value).draw();
            });
          }
        } else {
          if ($.fn.DataTable.isDataTable('#archive-clients-table')) {
            $('#archive-clients-table').DataTable().destroy();
          }
        }
        // DataTable logic for Archive Requests
        if (this.dataset.archivetab === 'requests') {
          if ($.fn.DataTable.isDataTable('#archiveRequestsTable')) {
            $('#archiveRequestsTable').DataTable().destroy();
          }
          try {
            var $table = $('#archiveRequestsTable');
            var hasDataRows = $table.find('tbody tr').length > 0 && !$table.find('tbody tr td').first().text().includes('No denied requests found');
            if ($table.is(':visible') && $table.find('thead tr th').length > 0 && hasDataRows) {
              archiveRequestsTableInstance = $table.DataTable({
                dom: 'lrtip',
                paging: true,
                searching: true,
                ordering: true,
                info: true,
                autoWidth: false
              });
              $('#archiveRequestSearchInput').off('keyup').on('keyup', function() {
                archiveRequestsTableInstance.search(this.value).draw();
              });
            }
          } catch (e) {
            console.warn('DataTables init suppressed:', e);
          }
        } else {
          if ($.fn.DataTable.isDataTable('#archiveRequestsTable')) {
            $('#archiveRequestsTable').DataTable().destroy();
          }
        }
      });
    });
    // Always initialize DataTable for Archive Clients on page load
    $(document).ready(function() {
      var $table = $('#archive-clients-table');
      var hasDataRows = $table.find('tbody tr').length > 0 && !$table.find('tbody tr td').first().text().includes('No archived clients found');
      if ($table.find('thead tr th').length > 0 && hasDataRows) {
        if ($.fn.DataTable.isDataTable('#archive-clients-table')) {
          $('#archive-clients-table').DataTable().destroy();
        }
        archiveClientsTableInstance = $table.DataTable({
          dom: 'lftip', // Hide default search box
          paging: true,
          searching: true,
          ordering: true,
          info: true,
          autoWidth: false,
          language: {
            lengthMenu: 'Show _MENU_ entries',
            zeroRecords: 'No clients found',
            emptyTable: 'No clients available',
            infoEmpty: '',
            info: 'Showing _START_ to _END_ of _TOTAL_',
            infoFiltered: '',
            paginate: {
              first: 'First',
              last: 'Last',
              next: 'Next',
              previous: 'Previous'
            }
          }
        });
        $('#archiveClientsSearchInput').off('keyup').on('keyup', function() {
          archiveClientsTableInstance.search(this.value).draw();
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

    // Notification read/unread logic
    function renderNotifications() {
      if (typeof systemNotifs === 'undefined') return;
      var notifList = document.getElementById('notifList');
      if (!notifList) return;
      notifList.innerHTML = '';
      var unreadCount = 0;
      systemNotifs.forEach(function(notif) {
        var readKey = 'notif_read_' + notif.id;
        var isRead = localStorage.getItem(readKey) === '1';
       
        if (!isRead) unreadCount++;
        var notifDiv = document.createElement('div');
        notifDiv.style.display = 'flex';
        notifDiv.style.alignItems = 'center';
        notifDiv.style.justifyContent = 'space-between';
        notifDiv.style.padding = '14px 18px';
        notifDiv.style.background = '#f8f9fa';
        notifDiv.style.borderRadius = '10px';
        notifDiv.style.marginBottom = '10px';
        notifDiv.style.boxShadow = '0 1px 4px rgba(0,0,0,0.04)';
        notifDiv.style.fontSize = '1.05rem';
        notifDiv.style.border = '1px solid #e3e7ed';
        var span = document.createElement('span');
        span.textContent = 'New client request received from ' + notif.name;
        span.style.color = '#222';
        span.style.fontWeight = isRead ? '400' : '700';
        var a = document.createElement('a');
        a.href = 'ClientsRequest.php';
        a.title = 'View Requests';
        a.style.marginLeft = '18px';
        a.style.display = 'inline-flex';
        a.style.alignItems = 'center';
        a.style.justifyContent = 'center';
        a.style.background = '#f1f3f6';
        a.style.borderRadius = '50%';
        a.style.width = '32px';
        a.style.height = '32px';
        a.style.textDecoration = 'none';
        var icon = document.createElement('i');
        icon.className = 'fas fa-arrow-right';
        icon.style.color = '#888';
        icon.style.fontSize = '1.25rem';
        a.appendChild(icon);
        a.onclick = function() {
          localStorage.setItem(readKey, '1');
          span.style.fontWeight = '400';
          updateNotifBadge();
        };
        notifDiv.appendChild(span);
        notifDiv.appendChild(a);
        notifList.appendChild(notifDiv);
      });
      updateNotifBadge(unreadCount);
    }
    function updateNotifBadge(count) {
      var badges = document.querySelectorAll('#notifBadge');
      if (typeof count === 'undefined') {
        // Recalculate if not provided
        if (typeof systemNotifs === 'undefined') return;
        count = 0;
        systemNotifs.forEach(function(notif) {
          var readKey = 'notif_read_' + notif.id;
          if (localStorage.getItem(readKey) !== '1') count++;
        });
      }
      badges.forEach(function(badge) {
        if (count > 0) {
          badge.textContent = count;
          badge.style.display = '';
        } else {
          badge.textContent = '';
          badge.style.display = 'none';
        }
      });
    }
    document.addEventListener('DOMContentLoaded', function() {
      renderNotifications();
      updateNotifBadge();
    });

    // Notification sub-tab switching
    const notifTabs = document.querySelectorAll('.notif-subtab');
    const notifTabContents = {
      all: document.getElementById('notifAllTab'),
      newusers: document.getElementById('notifNewUsersTab'),
      newrequests: document.getElementById('notifNewRequestsTab')
    };
    notifTabs.forEach(tab => {
      tab.addEventListener('click', function() {
        notifTabs.forEach(t => {
          t.classList.remove('active');
          t.style.color = '';
          t.style.borderBottom = '';
        });
        this.classList.add('active');
        this.style.color = '#2d72d9';
        this.style.borderBottom = '2px solid #2d72d9';
        Object.values(notifTabContents).forEach(tc => tc.style.display = 'none');
        notifTabContents[this.dataset.notiftab].style.display = '';
      });
    });
    // Render notifications for new users
    function renderNewUserNotifications() {
      if (typeof newUserNotifs === 'undefined') return;
      var newUserNotifList = document.getElementById('newUserNotifList');
      if (!newUserNotifList) return;
      newUserNotifList.innerHTML = '';
      newUserNotifs.forEach(function(user) {
        var notifDiv = document.createElement('div');
        notifDiv.style.display = 'flex';
        notifDiv.style.alignItems = 'center';
        notifDiv.style.justifyContent = 'space-between';
        notifDiv.style.padding = '14px 18px';
        notifDiv.style.background = '#f8f9fa';
        notifDiv.style.borderRadius = '10px';
        notifDiv.style.marginBottom = '10px';
        notifDiv.style.boxShadow = '0 1px 4px rgba(0,0,0,0.04)';
        notifDiv.style.fontSize = '1.05rem';
        notifDiv.style.border = '1px solid #e3e7ed';
        var span = document.createElement('span');
        span.textContent = 'New user registered: ' + user.name + ' (' + user.email + ')';
        span.style.color = '#222';
        span.style.fontWeight = '700';
        var dateSpan = document.createElement('span');
        dateSpan.textContent = new Date(user.created_at).toLocaleString();
        dateSpan.style.color = '#888';
        dateSpan.style.fontSize = '0.95rem';
        dateSpan.style.marginLeft = '18px';
        notifDiv.appendChild(span);
        notifDiv.appendChild(dateSpan);
        newUserNotifList.appendChild(notifDiv);
      });
    }
    // Render notifications for new requests
    function renderNewRequestNotifications() {
      if (typeof newRequestNotifs === 'undefined') return;
      var newRequestNotifList = document.getElementById('newRequestNotifList');
      if (!newRequestNotifList) return;
      newRequestNotifList.innerHTML = '';
      newRequestNotifs.forEach(function(notif) {
        var notifDiv = document.createElement('div');
        notifDiv.style.display = 'flex';
        notifDiv.style.alignItems = 'center';
        notifDiv.style.justifyContent = 'space-between';
        notifDiv.style.padding = '14px 18px';
        notifDiv.style.background = '#f8f9fa';
        notifDiv.style.borderRadius = '10px';
        notifDiv.style.marginBottom = '10px';
        notifDiv.style.boxShadow = '0 1px 4px rgba(0,0,0,0.04)';
        notifDiv.style.fontSize = '1.05rem';
        notifDiv.style.border = '1px solid #e3e7ed';
        var span = document.createElement('span');
        span.textContent = 'New client request received from ' + notif.name;
        span.style.color = '#222';
        span.style.fontWeight = '700';
        var a = document.createElement('a');
        a.href = 'ClientsRequest.php';
        a.title = 'View Requests';
        a.style.marginLeft = '18px';
        a.style.display = 'inline-flex';
        a.style.alignItems = 'center';
        a.style.justifyContent = 'center';
        a.style.background = '#f1f3f6';
        a.style.borderRadius = '50%';
        a.style.width = '32px';
        a.style.height = '32px';
        a.style.textDecoration = 'none';
        var icon = document.createElement('i');
        icon.className = 'fas fa-arrow-right';
        icon.style.color = '#888';
        icon.style.fontSize = '1.25rem';
        a.appendChild(icon);
        a.onclick = function() {
          span.style.fontWeight = '400';
        };
        notifDiv.appendChild(span);
        notifDiv.appendChild(a);
        newRequestNotifList.appendChild(notifDiv);
           updateNotifBadge();
    });

    // Restore modal logic for Archive Clients
    let restoreTargetRow = null;
    let restoreTargetEmail = null;
    // Attach click event to all restore buttons
    document.querySelectorAll('.restore-btn').forEach(function(btn) {
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        var row = this.closest('tr');
        var email = row.querySelector('td:nth-child(2)').textContent.trim();
        restoreTargetRow = row;
        restoreTargetEmail = email;
        document.getElementById('restoreModal').style.display = 'flex';
      });
    });
    // Cancel button closes modal
    document.getElementById('modalCancelRestoreBtn').addEventListener('click', function() {
      document.getElementById('restoreModal').style.display = 'none';
      restoreTargetRow = null;
      restoreTargetEmail = null;
    });
    // Restore button confirms restore, removes row, and shows notification
    document.getElementById('modalRestoreBtn').addEventListener('click', function() {
      if (!restoreTargetEmail || !restoreTargetRow) return;
      const restoreBtn = this;
      const modal = document.getElementById('restoreModal');
      const cancelBtn = document.getElementById('modalCancelRestoreBtn');
      // Show loading state
      restoreBtn.disabled = true;
      restoreBtn.textContent = 'Restoring...';
      cancelBtn.disabled = true;
                $.post('restore_client.php', { email: restoreTargetEmail }, function(response) {
        if (response.success) {
          if (typeof archiveClientsTableInstance !== 'undefined' && archiveClientsTableInstance) {
            archiveClientsTableInstance.row($(restoreTargetRow)).remove().draw();
          } else {
            restoreTargetRow.parentNode.removeChild(restoreTargetRow);
          }
          showRestoreSuccessNotification('Client successfully restored');
          modal.style.display = 'none';
        } else {
          alert('Failed to restore client.');
        }
      }, 'json').fail(function() {
        alert('An error occurred while restoring. Please try again.');
      }).always(function() {
        restoreBtn.disabled = false;
        restoreBtn.textContent = 'Restore';
        cancelBtn.disabled = false;
        restoreTargetRow = null;
        restoreTargetEmail = null;
      });
    });
    // Show restore success notification
    function showRestoreSuccessNotification(message) {
      const notif = document.getElementById('restoreSuccessNotification');
      notif.querySelector('span').innerHTML = `<i class=\"fas fa-check-circle\" style=\"margin-right:8px;\"></i>${message}`;
      notif.style.display = 'flex';
      notif.style.background = '#2ecc71';
      // Auto-close after 3 seconds
      const timeout = setTimeout(() => {
        notif.style.display = 'none';
      }, 3000);
      document.getElementById('closeRestoreNotificationBtn').onclick = function() {
        notif.style.display = 'none';
        clearTimeout(timeout);
      };
    }
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
  <style>
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
  .settings-fields-row {
    display: flex;
    gap: 18px;
    margin-bottom: 0;
  }
  .settings-field-group {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 4px;
  }
  .settings-field-group label {
    font-size: 1rem;
    font-weight: 500;
    color: #222;
    margin-bottom: 4px;
  }
  .settings-input {
    width: 100%;
    box-sizing: border-box;
    padding: 8px 38px 8px 12px;
    font-size: 1rem;
    border: 1px solid #ccc;
    border-radius: 6px;
    background: #fff;
    font-family: inherit;
    transition: border 0.2s;
    outline: none;
    height: 40px;
    line-height: 1.2;
  }
  .settings-input:disabled {
    background: #f5f5f5;
    color: #aaa;
  }
  .password-eye-icon {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    color: #888;
    z-index: 2;
    font-size: 1.1em;
    padding: 2px 6px;
    background: transparent;
    border-radius: 50%;
    transition: background 0.2s;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .password-eye-icon:hover {
    background: #f1f3f6;
  }
  .settings-field-group .settings-input {
    margin-bottom: 0;
  }
  .settings-field-group .error-message {
    color: #e74c3c;
    font-size: 0.95em;
    margin-top: 4px;
    display: none;
  }
 .settings-fields-row.password-row {
    margin-bottom: 75px;
  }
  </style>
  <?php // ...existing code ... ?>

  <!-- Add delegated tab click handler near end of file, after other scripts that define `unsaved` -->
  <script>
  (function(){
    // Defensive: wait for DOM ready if needed
    function initTabs() {
      var tabsContainer = document.querySelector('.settings-tabs');
      if (!tabsContainer) return;
      var tabContents = {
        account: document.getElementById('accountTab'),
        archive: document.getElementById('archiveTab'),
        notification: document.getElementById('notificationTab')
      };
      // Delegated click handler so inner elements (icons/text) won't break clicks
      tabsContainer.addEventListener('click', function(e){
        var tabEl = e.target.closest('.settings-tab');
        if (!tabEl) return;
        // if already active do nothing
        if (tabEl.classList.contains('active')) return;
        // respect unsaved flag (existing behavior)
        if (window.unsaved) {
          var bar = document.getElementById('unsavedBar');
          if (bar) bar.style.display = 'flex';
          return;
        }
        // switch active tab
        document.querySelectorAll('.settings-tab').forEach(function(t){ t.classList.remove('active'); });
        tabEl.classList.add('active');
        // hide all tab contents and show the one matching data-tab
        Object.keys(tabContents).forEach(function(k){
          if (tabContents[k]) tabContents[k].style.display = 'none';
        });
        var key = tabEl.getAttribute('data-tab');
        if (key && tabContents[key]) tabContents[key].style.display = '';
      }, false);
      // make tabs keyboard focusable for accessibility
      document.querySelectorAll('.settings-tab').forEach(function(tab){
        tab.setAttribute('tabindex','0');
        tab.addEventListener('keydown', function(e){
          if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); tab.click(); }
        });
      });
    }
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', initTabs);
    } else {
      initTabs();
    }
  })();
  </script>

  <!-- ...existing code... -->
