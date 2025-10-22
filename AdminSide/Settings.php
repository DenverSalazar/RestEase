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
// Add init for email change error so account_tab can display messages
$emailChangeError = '';
// Handle profile update and profile picture upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['save_profile']) || isset($_POST['upload_profile_pic']))) {
  $displayName = trim($_POST['displayName'] ?? '');
  $firstName = trim($_POST['firstName'] ?? '');
  $lastName = trim($_POST['lastName'] ?? '');
  $phone = trim($_POST['phone'] ?? '');
  $role = trim($_POST['role'] ?? 'Admin');
  $emailInput = trim($_POST['email'] ?? '');
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

  // Secure email change validation: require current password and validate format
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
        // Check hashed password in admin_accounts
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
    // If no error, update the email in admin_accounts
    if (!$emailChangeError) {
      $stmtEmail = $conn->prepare('UPDATE admin_accounts SET email=? WHERE id=?');
      $stmtEmail->bind_param('si', $emailInput, $adminId);
      $stmtEmail->execute();
      $stmtEmail->close();
      // reflect the change in local variable
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
  // note: email already updated above if changed
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
      // badge removed — no visual indicator needed here
    }
    // badge removed: keep a no-op function so existing calls won't error
    function updateNotifBadge(count) { return; }
    document.addEventListener('DOMContentLoaded', function() {
      renderNotifications();
      renderNewUserNotifications();
      renderNewRequestNotifications();
      // updateNotifBadge intentionally no-op
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
      });
    }
    document.addEventListener('DOMContentLoaded', function() {
      renderNotifications();
      renderNewUserNotifications();
      renderNewRequestNotifications();
      // updateNotifBadge intentionally no-op
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

    // --- Added: activate settings tab from URL on initial load ---
    (function() {
      function activateSettingsTab(tabName) {
        if (!tabName) return;
        // normalize
        tabName = tabName.toString().toLowerCase();
        if (!['account','archive','notification'].includes(tabName)) return;
        const tabEl = document.querySelector('.settings-tab[data-tab="'+tabName+'"]');
        if (!tabEl) return;
        // remove active from all tabs and hide contents
        document.querySelectorAll('.settings-tab').forEach(t => t.classList.remove('active'));
        const tabContentsMap = {
          account: document.getElementById('accountTab'),
          archive: document.getElementById('archiveTab'),
          notification: document.getElementById('notificationTab')
        };
        Object.values(tabContentsMap).forEach(tc => { if (tc) tc.style.display = 'none'; });
        // set selected
        tabEl.classList.add('active');
        if (tabContentsMap[tabName]) tabContentsMap[tabName].style.display = '';
      }

      document.addEventListener('DOMContentLoaded', function() {
        // prefer query param ?tab=..., fallback to hash #notification
        const params = new URLSearchParams(window.location.search);
        let tab = params.get('tab');
        if (!tab && window.location.hash) {
          tab = window.location.hash.replace('#','');
        }
        if (tab) {
          // ensure unsaved guard not shown on initial navigation
          try {
            unsaved = false;
            const unsavedBar = document.getElementById('unsavedBar');
            if (unsavedBar) unsavedBar.style.display = 'none';
          } catch (e) {}
          activateSettingsTab(tab);
        }
      });
    })();
  </script>
  <style>
  /* notif badge removed */
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
</body>
</html>