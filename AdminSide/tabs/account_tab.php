<?php
// Moved-in: Account tab PHP (admin info + POST handlers)
if (!isset($adminInfo)) {
  include_once '../Includes/db.php';
  $adminId = $_SESSION['admin_id'] ?? null;
  $adminInfo = [
    'display_name' => '',
    'first_name'   => '',
    'last_name'    => '',
    'email'        => '',
    'phone'        => '',
    'role'         => 'Admin',
    'profile_pic'  => '../assets/Default Image.jpg'
  ];
  $emailChangeError = '';
  if ($adminId && $conn && !$conn->connect_error) {
    // Get email from admin_accounts
    if ($stmt = $conn->prepare('SELECT email FROM admin_accounts WHERE id = ? LIMIT 1')) {
      $stmt->bind_param('i', $adminId);
      $stmt->execute();
      $stmt->bind_result($email);
      if ($stmt->fetch()) $adminInfo['email'] = $email;
      $stmt->close();
    }
    // Get profile info from admin_profiles
    if ($stmt2 = $conn->prepare('SELECT display_name, first_name, last_name, phone, role, profile_pic FROM admin_profiles WHERE admin_id = ? LIMIT 1')) {
      $stmt2->bind_param('i', $adminId);
      $stmt2->execute();
      $stmt2->bind_result($displayName, $firstName, $lastName, $phone, $role, $profilePic);
      if ($stmt2->fetch()) {
        $adminInfo['display_name'] = $displayName;
        $adminInfo['first_name']  = $firstName;
        $adminInfo['last_name']   = $lastName;
        $adminInfo['phone']       = $phone;
        $adminInfo['role']        = $role ?: 'Admin';
        $adminInfo['profile_pic'] = $profilePic ?: '../assets/Default Image.jpg';
      }
      $stmt2->close();
    }
  }
}

// Handle profile update and profile picture upload (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['save_profile']) || isset($_POST['upload_profile_pic']) || isset($_POST['save_password']))) {
  $displayName = trim($_POST['displayName'] ?? '');
  $firstName   = trim($_POST['firstName'] ?? '');
  $lastName    = trim($_POST['lastName'] ?? '');
  $phone       = trim($_POST['phone'] ?? '');
  $role        = trim($_POST['role'] ?? 'Admin');
  $emailInput  = trim($_POST['email'] ?? '');
  $profilePicPath = $adminInfo['profile_pic'];

  // Password change server-side handling (if requested)
  $passwordChangeError = '';
  $passwordChangeSuccess = '';
  if (isset($_POST['save_password'])) {
    $currentPassword = trim($_POST['currentPassword'] ?? '');
    $newPassword     = trim($_POST['newPassword'] ?? '');
    $confirmPassword = trim($_POST['confirmPassword'] ?? '');

    if (!$currentPassword) {
      $passwordChangeError = 'Please enter your current password.';
    } elseif (strlen($newPassword) < 6) {
      $passwordChangeError = 'New password must be at least 6 characters.';
    } elseif ($newPassword !== $confirmPassword) {
      $passwordChangeError = 'New passwords do not match.';
    } elseif ($conn && !$conn->connect_error && $adminId) {
      if ($stmtPwd = $conn->prepare('SELECT password FROM admin_accounts WHERE id=? LIMIT 1')) {
        $stmtPwd->bind_param('i', $adminId);
        $stmtPwd->execute();
        $stmtPwd->bind_result($hashedPwd);
        if ($stmtPwd->fetch()) {
          if (password_verify($currentPassword, $hashedPwd)) {
            $stmtPwd->close();
            // update password
            if ($stmtUpd = $conn->prepare('UPDATE admin_accounts SET password=? WHERE id=?')) {
              $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
              $stmtUpd->bind_param('si', $newHash, $adminId);
              if ($stmtUpd->execute()) {
                $passwordChangeSuccess = 'Password changed successfully.';
              } else {
                $passwordChangeError = 'Failed to update password.';
              }
              $stmtUpd->close();
            } else {
              $passwordChangeError = 'Failed to prepare update.';
            }
          } else {
            $passwordChangeError = 'Incorrect current password.';
            $stmtPwd->close();
          }
        } else {
          $passwordChangeError = 'Account not found.';
          $stmtPwd->close();
        }
      } else {
        $passwordChangeError = 'Failed to verify password.';
      }
    }
    // After handling password change we continue so page re-renders with messages.
  }

  // Upload new profile picture
  if (isset($_FILES['profilePicInput']) && $_FILES['profilePicInput']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '../uploads/';
    if (!is_dir($uploadDir)) { @mkdir($uploadDir, 0777, true); }
    $fileName = 'admin_' . ($adminId ?? '0') . '_' . time() . '_' . basename($_FILES['profilePicInput']['name']);
    $targetFile = $uploadDir . $fileName;
    if (move_uploaded_file($_FILES['profilePicInput']['tmp_name'], $targetFile)) {
      $profilePicPath = $targetFile;
    }
  }

  // Upsert profile
  if ($conn && !$conn->connect_error && $adminId) {
    if ($stmt = $conn->prepare('SELECT id FROM admin_profiles WHERE admin_id = ? LIMIT 1')) {
      $stmt->bind_param('i', $adminId);
      $stmt->execute();
      $stmt->store_result();
      $exists = $stmt->num_rows > 0;
      $stmt->close();
      if ($exists) {
        if ($stmt2 = $conn->prepare('UPDATE admin_profiles SET display_name=?, first_name=?, last_name=?, phone=?, role=?, profile_pic=? WHERE admin_id=?')) {
          $stmt2->bind_param('ssssssi', $displayName, $firstName, $lastName, $phone, $role, $profilePicPath, $adminId);
          $stmt2->execute();
          $stmt2->close();
        }
      } else {
        if ($stmt2 = $conn->prepare('INSERT INTO admin_profiles (admin_id, display_name, first_name, last_name, phone, role, profile_pic) VALUES (?, ?, ?, ?, ?, ?, ?)')) {
          $stmt2->bind_param('issssss', $adminId, $displayName, $firstName, $lastName, $phone, $role, $profilePicPath);
          $stmt2->execute();
          $stmt2->close();
        }
      }
    }
  }

  // Email change with password verification
  $emailChangeError = '';
  if ($emailInput && $emailInput !== ($adminInfo['email'] ?? '')) {
    if (!filter_var($emailInput, FILTER_VALIDATE_EMAIL)) {
      $emailChangeError = "Invalid email format.";
    } else {
      $emailChangePassword = trim($_POST['emailChangePassword'] ?? '');
      if (!$emailChangePassword) {
        $emailChangeError = "Please enter your current password to change email.";
      } else if ($conn && !$conn->connect_error && $adminId) {
        if ($stmtPwd = $conn->prepare('SELECT password FROM admin_accounts WHERE id=? LIMIT 1')) {
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
    }
    if (!$emailChangeError && $conn && !$conn->connect_error && $adminId) {
      if ($stmtEmail = $conn->prepare('UPDATE admin_accounts SET email=? WHERE id=?')) {
        $stmtEmail->bind_param('si', $emailInput, $adminId);
        $stmtEmail->execute();
        $stmtEmail->close();
        $adminInfo['email'] = $emailInput; // reflect change
      }
    }
  }

  // Update $adminInfo for page render
  $adminInfo['display_name'] = $displayName;
  $adminInfo['first_name']   = $firstName;
  $adminInfo['last_name']    = $lastName;
  $adminInfo['phone']        = $phone;
  $adminInfo['role']         = $role;
  $adminInfo['profile_pic']  = $profilePicPath;
}
?>

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
        <input type="password" id="emailChangePassword" name="emailChangePassword" autocomplete="off" placeholder="Required to change email">
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
            <span id="togglePassword" class="password-eye-icon"><i class="fa fa-eye"></i></span>
          </div>
          <div id="currentPasswordError" style="color:#e74c3c;font-size:0.95em;margin-top:4px;display:none;"></div>
        </div>
        <div class="settings-field-group" style="flex:1;min-width:0;">
          <label for="newPassword">New password</label>
          <div style="position: relative;">
            <input type="password" id="newPassword" name="newPassword" class="settings-input" disabled autocomplete="off">
            <span id="toggleNewPassword" class="password-eye-icon"><i class="fa fa-eye"></i></span>
          </div>
          <div id="newPasswordError" style="color:#e74c3c;font-size:0.95em;margin-top:4px;display:none;">
            <?php
              // show server-side password change feedback (if any)
              if (!empty($passwordChangeError)) {
                echo htmlspecialchars($passwordChangeError);
                echo '<script>document.addEventListener("DOMContentLoaded",function(){var el=document.getElementById("newPasswordError"); if(el){el.style.display="block";}})</script>';
              } elseif (!empty($passwordChangeSuccess)) {
                // success: show green
                echo '<span style="color:#27ae60;">' . htmlspecialchars($passwordChangeSuccess) . '</span>';
                echo '<script>document.addEventListener("DOMContentLoaded",function(){var el=document.getElementById("newPasswordError"); if(el){el.style.display="block";}})</script>';
              }
            ?>
          </div>
        </div>
        <div class="settings-field-group" style="flex:1;min-width:0;">
          <label for="confirmPassword">Confirm new password</label>
          <div style="position: relative;">
            <input type="password" id="confirmPassword" name="confirmPassword" class="settings-input" disabled autocomplete="off">
            <span id="toggleConfirmPassword" class="password-eye-icon"><i class="fa fa-eye"></i></span>
          </div>
          <div id="confirmPasswordError" style="color:#e74c3c;font-size:0.95em;margin-top:4px;display:none;"></div>
        </div>
      </div>
    </div>
    <button id="changePasswordBtn" name="change_password" type="button" style="position:absolute;right:32px;bottom:32px;z-index:10;background:#2d72d9;color:#fff;border:none;border-radius:6px;padding:12px 28px;font-size:1.1rem;font-weight:600;box-shadow:0 4px 16px rgba(44,130,201,0.15);cursor:pointer;display:none;">
      Save Password
    </button>
   </form>

  <!-- Unsaved changes bar (moved here) -->
  <div class="settings-unsaved-bar hidden" id="unsavedBar" role="status" aria-live="polite" aria-atomic="true" style="display: none;">
    <div class="unsaved-left">
      <span class="unsaved-icon" aria-hidden="true">⚠️</span>
      <div class="unsaved-text">
        <span class="unsaved-title">Careful — you have unsaved changes!</span>
        <span class="unsaved-sub">Remember to save to keep your changes.</span>
      </div>
    </div>
    <div class="settings-unsaved-actions">
      <button class="reset-link" id="resetLink" type="button">Reset</button>
      <button class="save-btn" id="saveBtn" type="button">Save Changes</button>
    </div>
  </div>
</div>

<!-- Account-specific CSS moved here -->
<style>
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
  .password-eye-icon:hover { background: #f1f3f6; }
  .settings-field-group .settings-input { margin-bottom: 0; }
  .settings-field-group .error-message {
    color: #e74c3c;
    font-size: 0.95em;
    margin-top: 4px;
    display: none;
  }
  .settings-fields-row.password-row { margin-bottom: 75px; }

  /* Updated unsaved-bar styles: high-visibility, card-like, animated */
  .settings-unsaved-bar {
    position: fixed;
    left: 50%;
    transform: translateX(-50%) translateY(12px) scale(.99);
    bottom: 24px;
    z-index: 1400;
    width: min(920px, calc(100% - 40px));
    background: linear-gradient(180deg,#fff7ed 0%, #fff 100%);
    border-radius: 12px;
    padding: 14px 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    box-shadow: 0 18px 40px rgba(19,48,85,0.16);
    border: 1px solid rgba(34,60,80,0.08);
    transition: transform .18s cubic-bezier(.2,.9,.2,1), opacity .18s ease;
    font-family: inherit;
    pointer-events: auto;
    opacity: 0;
  }
  .settings-unsaved-bar .unsaved-left {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
  }
  .unsaved-icon {
    font-size: 1.35rem;
    line-height: 1;
    margin-right: 6px;
  }
  .unsaved-text .unsaved-title {
    display: block;
    font-weight: 700;
    color: #1f2937;
  }
  .unsaved-text .unsaved-sub {
    display: block;
    color: #475569;
    font-size: 0.95rem;
  }

  /* visible/hidden states */
  .settings-unsaved-bar.hidden { opacity: 0; transform: translateX(-50%) translateY(12px) scale(.98); pointer-events: none; }
  .settings-unsaved-bar.visible { opacity: 1; transform: translateX(-50%) translateY(0) scale(1); pointer-events: auto; }

  /* Actions group on the right */
  .settings-unsaved-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-shrink: 0;
  }

  /* Reset = subtle outlined button */
  .reset-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    border: 1px solid rgba(34,51,85,0.08);
    color: #0b66ff;
    padding: 8px 14px;
    border-radius: 10px;
    cursor: pointer;
    font-weight: 700;
    transition: background .12s ease, transform .08s ease;
    font-size: 0.95rem;
    background-clip: padding-box;
  }
  .reset-link:hover { background: rgba(11,102,255,0.06); transform: translateY(-2px); }

  /* Prominent save button */
  .save-btn {
    background: linear-gradient(180deg,#ff7a18 0%, #ff3d00 100%);
    color: #fff;
    border: none;
    border-radius: 10px;
    padding: 10px 18px;
    font-size: 1rem;
    font-weight: 800;
    cursor: pointer;
    box-shadow: 0 14px 34px rgba(255,93,41,0.18);
    transition: transform .08s ease, box-shadow .12s ease;
  }
  .save-btn:hover { transform: translateY(-3px); box-shadow: 0 18px 40px rgba(255,93,41,0.22); }

  /* Responsive: stack on smaller screens */
  @media (max-width: 520px) {
    .settings-unsaved-bar {
      left: 50%;
      transform: translateX(-50%);
      bottom: 16px;
      width: calc(100% - 24px);
      padding: 12px;
      flex-direction: column;
      align-items: stretch;
      gap: 10px;
    }
    .settings-unsaved-bar .unsaved-left { align-items: flex-start; }
    .settings-unsaved-actions { justify-content: space-between; width: 100%; }
    .reset-link { flex: 1; justify-content: center; }
    .save-btn { flex: 1; }
  }
</style>

<!-- Account-specific JS moved here -->
<script>
  // Ensure global unsaved exists
  if (typeof window.unsaved === 'undefined') window.unsaved = false;

  // Password change logic (AJAX validation/update)
  if (typeof $ !== 'undefined') {
    // keep original jQuery logic when jQuery is available
    $(function() {
      const currentPasswordInput = $('#currentPassword');
      const newPasswordInput = $('#newPassword');
      const confirmPasswordInput = $('#confirmPassword');
      const changePasswordBtn = $('#changePasswordBtn');
      const cardSaveBtnJq = $('#cardSaveBtn');
      const currentPasswordError = $('#currentPasswordError');
      const newPasswordError = $('#newPasswordError');
      let currentPasswordValid = false;

      currentPasswordInput.on('input', function() {
        const val = $(this).val();
        if (val.length === 0) {
          currentPasswordError.hide();
          newPasswordInput.prop('disabled', true);
          confirmPasswordInput.prop('disabled', true);
          changePasswordBtn.hide();
          cardSaveBtnJq.hide();
          currentPasswordValid = false;
          return;
        }
        // validate current password via server endpoint
        $.post('validate_admin_password.php', { password: val }, function(data) {
          if (data && data.success) {
            currentPasswordError.hide();
            newPasswordInput.prop('disabled', false);
            confirmPasswordInput.prop('disabled', false);
            currentPasswordValid = true;
          } else {
            currentPasswordError.text('Current password is incorrect.').show();
            newPasswordInput.prop('disabled', true);
            confirmPasswordInput.prop('disabled', true);
            changePasswordBtn.hide();
            cardSaveBtnJq.hide();
            currentPasswordValid = false;
          }
        }, 'json').fail(function(){ /* ignore */ });
      });

      $('#newPassword, #confirmPassword').on('input', function() {
        if (!currentPasswordValid) return;
        const newPass = newPasswordInput.val();
        const confirmPass = confirmPasswordInput.val();
        if (newPass.length < 6) {
          newPasswordError.text('Password must be at least 6 characters.').css('color','#e74c3c').show();
          $('#confirmPasswordError').hide();
          changePasswordBtn.hide();
          cardSaveBtnJq.hide();
        } else {
          newPasswordError.hide();
          if (confirmPass && newPass !== confirmPass) {
            $('#confirmPasswordError').text('Passwords do not match.').css('color','#e74c3c').show();
            changePasswordBtn.hide();
            cardSaveBtnJq.hide();
          } else {
            $('#confirmPasswordError').hide();
            if (confirmPass) {
              changePasswordBtn.show();
              cardSaveBtnJq.show();
            }
          }
        }
      });

      $('#changePasswordBtn').on('click', function(e) {
        e.preventDefault();
        if (!currentPasswordValid) return;
        const newPass = newPasswordInput.val();
        const confirmPass = confirmPasswordInput.val();
        if (newPass.length < 6) {
          newPasswordError.text('Password must be at least 6 characters.').css('color','#e74c3c').show();
          $('#confirmPasswordError').hide();
          return;
        }
        if (newPass !== confirmPass) {
          $('#confirmPasswordError').text('Passwords do not match.').css('color','#e74c3c').show();
          $('#newPasswordError').hide();
          return;
        }
        // set save_password flag and submit the form to let PHP update DB
        let saveFlag = $('#profileForm').find('input[name="save_password"]');
        if (!saveFlag.length) {
          $('<input>').attr({ type: 'hidden', name: 'save_password', value: '1' }).appendTo('#profileForm');
        } else {
          saveFlag.val('1');
        }
        $('#profileForm')[0].submit();
      });

      // Delegate floating button to trigger the same action
      cardSaveBtnJq.on('click', function(e){
        e.preventDefault();
        changePasswordBtn.trigger('click');
      });
    });
  } else {
    // Vanilla JS fallback: show validation errors under fields and call same endpoints
    document.addEventListener('DOMContentLoaded', function() {
      const currentPasswordInput = document.getElementById('currentPassword');
      const newPasswordInput = document.getElementById('newPassword');
      const confirmPasswordInput = document.getElementById('confirmPassword');
      const changePasswordBtn = document.getElementById('changePasswordBtn');
      const cardSaveBtn = document.getElementById('cardSaveBtn');
      const currentPasswordError = document.getElementById('currentPasswordError');
      const newPasswordError = document.getElementById('newPasswordError');
      const confirmPasswordError = document.getElementById('confirmPasswordError');
      let currentPasswordValid = false;

      function show(el){ if(!el) return; el.style.display='block'; }
      function hide(el){ if(!el) return; el.style.display='none'; }
      function postForm(url, dataObj){ // returns Promise of parsed JSON
        const body = Object.keys(dataObj).map(k => encodeURIComponent(k)+'='+encodeURIComponent(dataObj[k])).join('&');
        return fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body }).then(r=>r.json());
      }

      if (currentPasswordInput) {
        currentPasswordInput.addEventListener('input', function() {
          const val = this.value || '';
          if (!val) {
            hide(currentPasswordError);
            if (newPasswordInput) newPasswordInput.disabled = true;
            if (confirmPasswordInput) confirmPasswordInput.disabled = true;
            if (changePasswordBtn) changePasswordBtn.style.display = 'none';
            if (cardSaveBtn) cardSaveBtn.style.display = 'none';
            currentPasswordValid = false;
            return;
          }
          // call server endpoint to validate current password
          postForm('validate_admin_password.php', { password: val }).then(data=>{
            if (data && data.success) {
              hide(currentPasswordError);
              if (newPasswordInput) newPasswordInput.disabled = false;
              if (confirmPasswordInput) confirmPasswordInput.disabled = false;
              currentPasswordValid = true;
            } else {
              if (currentPasswordError) { currentPasswordError.textContent = 'Current password is incorrect.'; currentPasswordError.style.color = '#e74c3c'; show(currentPasswordError); }
              if (newPasswordInput) newPasswordInput.disabled = true;
              if (confirmPasswordInput) confirmPasswordInput.disabled = true;
              if (changePasswordBtn) changePasswordBtn.style.display = 'none';
              if (cardSaveBtn) cardSaveBtn.style.display = 'none';
              currentPasswordValid = false;
            }
          }).catch(()=>{ /* ignore network errors silently */ });
        });
      }

      function validateNewConfirm(){
        if (!currentPasswordValid) return;
        const newPass = newPasswordInput ? newPasswordInput.value : '';
        const confirmPass = confirmPasswordInput ? confirmPasswordInput.value : '';
        if (!newPasswordInput) return;
        if (newPass.length < 6) {
          newPasswordError.textContent = 'Password must be at least 6 characters.'; newPasswordError.style.color='#e74c3c'; show(newPasswordError);
          hide(confirmPasswordError);
          if (changePasswordBtn) changePasswordBtn.style.display = 'none';
          if (cardSaveBtn) cardSaveBtn.style.display = 'none';
        } else {
          hide(newPasswordError);
          if (confirmPass && newPass !== confirmPass) {
            confirmPasswordError.textContent = 'Passwords do not match.'; confirmPasswordError.style.color='#e74c3c'; show(confirmPasswordError);
            if (changePasswordBtn) changePasswordBtn.style.display = 'none';
            if (cardSaveBtn) cardSaveBtn.style.display = 'none';
          } else {
            hide(confirmPasswordError);
            if (confirmPass && changePasswordBtn) changePasswordBtn.style.display = 'inline-block';
            if (confirmPass && cardSaveBtn) cardSaveBtn.style.display = 'inline-block';
          }
        }
      }
      if (newPasswordInput) newPasswordInput.addEventListener('input', validateNewConfirm);
      if (confirmPasswordInput) confirmPasswordInput.addEventListener('input', validateNewConfirm);

      if (changePasswordBtn) {
        changePasswordBtn.addEventListener('click', function(e){
          e.preventDefault();
          if (!currentPasswordValid) return;
          const newPass = newPasswordInput ? newPasswordInput.value : '';
          const confirmPass = confirmPasswordInput ? confirmPasswordInput.value : '';
          if (newPass.length < 6) {
            newPasswordError.textContent = 'Password must be at least 6 characters.'; newPasswordError.style.color='#e74c3c'; show(newPasswordError);
            hide(confirmPasswordError);
            return;
          }
          if (newPass !== confirmPass) {
            confirmPasswordError.textContent = 'Passwords do not match.'; confirmPasswordError.style.color='#e74c3c'; show(confirmPasswordError);
            hide(newPasswordError);
            return;
          }
          // set save_password flag and submit the form
          let saveFlag = document.querySelector('#profileForm input[name="save_password"]');
          if (!saveFlag) {
            saveFlag = document.createElement('input');
            saveFlag.type = 'hidden';
            saveFlag.name = 'save_password';
            saveFlag.value = '1';
            document.getElementById('profileForm').appendChild(saveFlag);
          } else {
            saveFlag.value = '1';
          }
          // submit the form (full page reload so PHP updates DB)
          document.getElementById('profileForm').submit();
        });
        // floating button delegates to same handler
        if (cardSaveBtn) {
          cardSaveBtn.addEventListener('click', function(e){ e.preventDefault(); changePasswordBtn.click(); });
        }
      }

      // Eye toggle handlers for password visibility (current/new/confirm)
      const toggleCurrent = document.getElementById('togglePassword');
      const toggleNew = document.getElementById('toggleNewPassword');
      const toggleConfirm = document.getElementById('toggleConfirmPassword');
      function attachToggle(toggleEl, inputEl) {
        if (!toggleEl || !inputEl) return;
        toggleEl.style.userSelect = 'none';
        toggleEl.addEventListener('click', function() {
          if (inputEl.type === 'password') {
            inputEl.type = 'text';
            toggleEl.innerHTML = '<i class="fa fa-eye-slash"></i>';
          } else {
            inputEl.type = 'password';
            toggleEl.innerHTML = '<i class="fa fa-eye"></i>';
          }
        });
      }
      attachToggle(toggleCurrent, currentPasswordInput);
      attachToggle(toggleNew, newPasswordInput);
      attachToggle(toggleConfirm, confirmPasswordInput);
    });
  }

  // --- New: DOM-ready wiring for unsaved/save/upload behavior ---
  document.addEventListener('DOMContentLoaded', function() {
    const profileForm = document.getElementById('profileForm');
    const saveBarBtn = document.getElementById('saveBtn');
    const resetLink = document.getElementById('resetLink');
    const profilePicInput = document.getElementById('profilePicInput');
    const uploadPicBtn = document.getElementById('uploadPicBtn');
    const unsavedBar = document.getElementById('unsavedBar');

    // Fields to watch for changes
    const profileInputIds = ['displayName','firstName','lastName','email','phone','role'];

    // Store original values for reset
    const originalValues = {};
    profileInputIds.forEach(id => {
      const el = document.getElementById(id);
      if (el) originalValues[id] = el.value;
    });

    // Show unsaved bar with animation
    function showUnsavedBar() {
      if (!unsavedBar) return;
      unsavedBar.style.display = 'flex';
      requestAnimationFrame(() => {
        unsavedBar.classList.remove('hidden');
        unsavedBar.classList.add('visible');
      });
    }
    // Hide unsaved bar with animation
    function hideUnsavedBar() {
      if (!unsavedBar) return;
      unsavedBar.classList.remove('visible');
      unsavedBar.classList.add('hidden');
      setTimeout(() => { if (unsavedBar) unsavedBar.style.display = 'none'; }, 220);
    }

    // Attach input listeners to mark unsaved
    profileInputIds.forEach(id => {
      const el = document.getElementById(id);
      if (!el) return;
      el.addEventListener('input', function() {
        window.unsaved = true;
        // auto-show the unsaved bar so changes are visible
        showUnsavedBar();
      });
    });

    // Unsaved-bar Save button: submit profile form (add save_profile flag)
    if (saveBarBtn && profileForm) {
      saveBarBtn.addEventListener('click', function(e){
        e.preventDefault();
        let saveFlag = profileForm.querySelector('input[name="save_profile"]');
        if (!saveFlag) {
          saveFlag = document.createElement('input');
          saveFlag.type = 'hidden';
          saveFlag.name = 'save_profile';
          saveFlag.value = '1';
          profileForm.appendChild(saveFlag);
        } else {
          saveFlag.value = '1';
        }
        window.unsaved = false;
        hideUnsavedBar();
        profileForm.submit();
      });
    }

    // Reset link: restore original values and hide save UI
    if (resetLink) {
      resetLink.addEventListener('click', function(e){
        e.preventDefault();
        profileInputIds.forEach(id => {
          const el = document.getElementById(id);
          if (el && Object.prototype.hasOwnProperty.call(originalValues, id)) {
            el.value = originalValues[id];
            // trigger input event so UI updates consistently
            el.dispatchEvent(new Event('input', { bubbles: true }));
          }
        });
        window.unsaved = false;
        hideUnsavedBar();
      });
    }

    // Profile picture upload: ensure form is submitted (so PHP gets $_FILES)
    if (uploadPicBtn && profilePicInput && profileForm) {
      uploadPicBtn.addEventListener('click', function(e){
        e.preventDefault();
        profilePicInput.click();
      });
      profilePicInput.addEventListener('change', function(e){
        const file = e.target.files && e.target.files[0];
        if (!file) return;
        // create/ensure upload flag so server branch runs
        let flag = profileForm.querySelector('input[name="upload_profile_pic"]');
        if (!flag) {
          flag = document.createElement('input');
          flag.type = 'hidden';
          flag.name = 'upload_profile_pic';
          flag.value = '1';
          profileForm.appendChild(flag);
        } else {
          flag.value = '1';
        }
        // submit form normally (multipart/form-data) so PHP receives $_FILES reliably
        profileForm.submit();
        // preview quickly while page reloads (non-blocking)
        try {
          const profileImg = document.querySelector('.settings-profile-img');
          const reader = new FileReader();
          reader.onload = function(ev) { if (profileImg) profileImg.src = ev.target.result; };
          reader.readAsDataURL(file);
        } catch (err) { /* ignore preview errors */ }
      });
    }

    // ensure unsaved bar starts hidden
    if (unsavedBar) { unsavedBar.classList.add('hidden'); unsavedBar.style.display = 'none'; }
  });
</script>
