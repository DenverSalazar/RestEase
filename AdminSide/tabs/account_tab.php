<?php
// ...this file expects variables like $adminInfo to be available (provided by Settings.php)...
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
