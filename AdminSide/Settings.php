<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>RestEase Admin Dashboard - Settings</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/Settings.css">
  <link rel="stylesheet" href="../css/sidebar.css">
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
              <div class="archive-subtab active" data-archivetab="clients" style="padding-bottom:6px;cursor:pointer;border-bottom:2px solid #2d72d9;font-weight:500;color:#2d72d9;">Archive Clients</div>
              <div class="archive-subtab" data-archivetab="records" style="padding-bottom:6px;cursor:pointer;color:#888;">Archive Records</div>
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
                    <th style="padding:10px 8px;text-align:left;">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <!-- Example rows, replace with PHP loop for real data -->
                  <tr style="background:#fff;">
                    <td style="padding:8px 8px;">
                      <img src="../assets/Default Image.jpg" style="width:32px;height:32px;border-radius:50%;vertical-align:middle;margin-right:8px;">
                      Cooper Herwitz
                    </td>
                    <td style="padding:8px 8px;">Cooper@gmail.com</td>
                    <td style="padding:8px 8px;">0917 234 5678</td>
                    <td style="padding:8px 8px;">
                      <span style="background:#f8d7da;color:#c0392b;padding:4px 14px;border-radius:6px;font-size:0.95em;">Denied</span>
                    </td>
                    <td style="padding:8px 8px;">
                      <button class="restore-btn" title="Restore" style="background:none;border:none;color:#2ecc71;font-size:1.1em;cursor:pointer;margin-right:8px;"><i class="fa fa-undo"></i></button>
                      <button class="delete-btn" title="Delete" style="background:none;border:none;color:#c0392b;font-size:1.1em;cursor:pointer;"><i class="fa fa-trash"></i></button>
                    </td>
                  </tr>
                  <!-- ...repeat for other clients... -->
                  <tr style="background:#fff;">
                    <td style="padding:8px 8px;">
                      <img src="../assets/Default Image.jpg" style="width:32px;height:32px;border-radius:50%;vertical-align:middle;margin-right:8px;">
                      Kadin Rhiel Madsen
                    </td>
                    <td style="padding:8px 8px;">Kadin@gmail.com</td>
                    <td style="padding:8px 8px;">0998 111 2233</td>
                    <td style="padding:8px 8px;">
                      <span style="background:#f8d7da;color:#c0392b;padding:4px 14px;border-radius:6px;font-size:0.95em;">Denied</span>
                    </td>
                    <td style="padding:8px 8px;">
                      <button class="restore-btn" title="Restore" style="background:none;border:none;color:#2ecc71;font-size:1.1em;cursor:pointer;margin-right:8px;"><i class="fa fa-undo"></i></button>
                      <button class="delete-btn" title="Delete" style="background:none;border:none;color:#c0392b;font-size:1.1em;cursor:pointer;"><i class="fa fa-trash"></i></button>
                    </td>
                  </tr>
                  <!-- ...add more rows as needed... -->
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
          <!-- Archive Records Placeholder -->
          <div id="archiveRecordsTab" style="display:none;">
            <div style="color:#888;font-size:1.05em;margin:32px 0;">Archive records will be shown here.</div>
          </div>
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
      records: document.getElementById('archiveRecordsTab')
    };
    archiveTabs.forEach(tab => {
      tab.addEventListener('click', function() {
        if (!this.classList.contains('active')) {
          archiveTabs.forEach(t => t.classList.remove('active'));
          this.classList.add('active');
          Object.values(archiveTabContents).forEach(tc => tc.style.display = 'none');
          archiveTabContents[this.dataset.archivetab].style.display = '';
        }
      });
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
  </script>
</body>
</html>
