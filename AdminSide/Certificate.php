<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    // Redirect to login page if not logged in
    header("Location: ../AdminLogin.php"); // Adjust the path if needed
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>RestEase Admin Dashboard</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/Reports.css">
  <link rel="stylesheet" href="../css/sidebar.css">
  <style>
    :root {
      --primary-color: #1a1a1a;
      --sidebar-width: 240px;
      --header-height: 60px;
      --border-radius: 8px;
      --gray-border: #ececec;
      --gray-table: #f7f8fa;
    }
    body {
      font-family: 'Poppins', sans-serif;
      background: #fafbfc;
      margin: 0;
      color: #222;
    }
    .main-content {
      margin-left: 260px;
      padding: 12px 40px 80px 60px;
    }
    .tab {
      background: none;
      border: none;
      font-size: 1.08rem;
      padding: 24px 0 12px 0;
      color: #222;
      font-weight: 600;
      border-bottom: 2px solid transparent;
      cursor: pointer;
      opacity: 0.7;
      transition: border-bottom 0.18s, opacity 0.18s, color 0.18s;
    }
    .tab.active {
      border-bottom: 2.5px solid #506C84;
      color: #506C84;
      opacity: 1;
    }
    .card {
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 2px 8px rgba(44,62,80,0.08);
      padding: 20px 32px 32px 32px;
      box-sizing: border-box;
      margin-bottom: 24px;
    }
    input[type="text"], input[type="number"], input[type="date"] {
      border:1px solid #d0d7e2; border-radius:7px; padding:8px 12px; font-size:1.04rem; margin-top:4px; margin-bottom:2px; background:#f7fafd; transition:border 0.18s;
    }
    input[type="text"]:focus, input[type="number"]:focus, input[type="date"]:focus {
      border:1.5px solid #506C84; background:#fff; outline:none;
    }
    input[readonly] { background-color: #f0f4f8; cursor: not-allowed; }
    label { margin-bottom:2px; display:block; font-weight:500; }
    .btn {
      background:#506C84; color:#fff; border:none; padding:10px 32px; border-radius:8px; font-weight:500; cursor:pointer; transition:background 0.18s;
    }
    .btn:hover { background:#39546a; }
    @media (max-width: 1100px) {
      .main-content {
        min-width: 0;
        max-width: 100vw;
        padding: 24px 8px 0 8px;
      }
      .card {
        padding: 16px 8px 16px 8px;
      }
      .tab {
        font-size: 0.98rem;
        padding: 12px 0 8px 0;
      }
    }
  </style>
</head>

<body>
   <!-- Sidebar -->
   <?php include '../Includes/sidebar.php'; ?>

  <!-- Main Content -->
<main class="main-content">
    <div class="clients-header" style="margin-bottom: 8px;">
      <h1 style="font-size:2rem;font-weight:700;margin-bottom:0;">Certification</h1>
      <p class="subtitle" style="font-size:1.04rem;color:#6b7280;">View and manage certification.</p>
    </div>

    <!-- Tabs Navigation -->
    <div style="border-bottom:1px solid #e0e0e0;margin-bottom:8px;">
      <div style="display:flex;gap:32px;align-items:center;">
        <button id="certTabBtn" class="tab active" onclick="showTab('certTab')">Certification</button>
        <button id="masterlistTabBtn" class="tab" onclick="showTab('masterlistTab')">Certification Masterlist</button>
      </div>
    </div>

    <!-- Tabs Content -->
    <div id="certTab" class="card">
      <h2 style="margin-left:0;margin-bottom:18px;font-size:1.25rem;font-weight:600;">New Certificate</h2>
      <!-- Certificate Template Form -->
      <form method="post" autocomplete="off" style="width:100%;" id="certificateForm">
        <!-- Personal Information Section -->
        <div style="font-weight:600;font-size:1.08rem;margin-bottom:8px;">Personal Information</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px 32px;">
          <div>
            <label>Name:</label>
            <input type="text" name="name" id="nameField" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required style="width:90%;">
            <div id="nameWarning" style="display:none;color:#e74c3c;font-size:0.98rem;margin-top:2px;">Name must not contain numbers or symbols.</div>
          </div>
          <div>
            <label>Barangay:</label>
            <input type="text" name="barangay" value="<?php echo isset($_POST['barangay']) ? htmlspecialchars($_POST['barangay']) : ''; ?>" required style="width:90%;">
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px 32px;margin-top:18px;">
          <div>
            <label>Apartment No.:</label>
            <input type="text" name="apartment" value="<?php echo isset($_POST['apartment']) ? htmlspecialchars($_POST['apartment']) : ''; ?>" required style="width:90%;">
          </div>
          <div>
            <label>Date:</label>
            <input type="date" name="date" value="<?php echo isset($_POST['date']) ? htmlspecialchars($_POST['date']) : date('Y-m-d'); ?>" required style="width:90%;">
          </div>
        </div>
        <hr style="margin:24px 0 18px 0; border:0; border-top:1px solid #ececec;">
        <!-- Certificate Details Section -->
        <div style="font-weight:600;font-size:1.08rem;margin-bottom:8px;">Certificate Details</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px 32px;">
          <div>
            <label>Renewal Date (5 years from Date):</label>
            <?php
              $renewal = '';
              if (isset($_POST['date'])) {
                $renewal = date('Y-m-d', strtotime($_POST['date'].' +5 years'));
              } else {
                $renewal = date('Y-m-d', strtotime('+5 years'));
              }
            ?>
            <input type="date" name="renewal" value="<?php echo $renewal; ?>" readonly style="width:90%;">
          </div>
        </div>
        <div style="margin-top:18px;">
          <label>Certificate Action:</label>
          <div style="display:flex;flex-wrap:wrap;gap:18px;">
            <label style="font-weight:400;"><input type="checkbox" name="actions[]" value="register_death" <?php if(isset($_POST['actions']) && in_array('register_death', $_POST['actions'])) echo 'checked'; ?>> Register death and rent CRYPT for five (5) years</label>
            <label style="font-weight:400;"><input type="checkbox" name="actions[]" value="renewal_crypt" <?php if(isset($_POST['actions']) && in_array('renewal_crypt', $_POST['actions'])) echo 'checked'; ?>> Renewal of CRYPT</label>
            <label style="font-weight:400;"><input type="checkbox" name="actions[]" value="transfer_remains" <?php if(isset($_POST['actions']) && in_array('transfer_remains', $_POST['actions'])) echo 'checked'; ?>> Transfer the remains</label>
            <label style="font-weight:400;"><input type="checkbox" name="actions[]" value="reopen_tomb" <?php if(isset($_POST['actions']) && in_array('reopen_tomb', $_POST['actions'])) echo 'checked'; ?>> Re-open the tomb</label>
            <label style="font-weight:400;"><input type="checkbox" name="actions[]" value="reenter_remains" <?php if(isset($_POST['actions']) && in_array('reenter_remains', $_POST['actions'])) echo 'checked'; ?>> Re-enter the remains</label>
          </div>
        </div>
        <hr style="margin:24px 0 18px 0; border:0; border-top:1px solid #ececec;">
        <!-- Deceased Information Section -->
        <div style="font-weight:600;font-size:1.08rem;margin-bottom:8px;">Deceased Information</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px 32px;">
          <div>
            <label>Deceased Name:</label>
            <input type="text" name="deceased" id="deceasedField" value="<?php echo isset($_POST['deceased']) ? htmlspecialchars($_POST['deceased']) : ''; ?>" style="width:90%;">
            <div id="deceasedWarning" style="display:none;color:#e74c3c;font-size:0.98rem;margin-top:2px;">Deceased Name must not contain numbers or symbols.</div>
          </div>
          <div>
            <label>Date Died:</label>
            <input type="date" name="date_died" value="<?php echo isset($_POST['date_died']) ? htmlspecialchars($_POST['date_died']) : ''; ?>" style="width:90%;">
          </div>
        </div>
        <div style="margin-top:32px;text-align:right;border-top:1px solid #f0f0f0;padding-top:24px;">
          <button type="submit" class="btn" style="width: 140px; padding: 12px 0; font-size:1.08rem;">Preview</button>
        </div>
      </form>
      <!-- Certificate Preview -->
      <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        <hr style="border:0; border-top:3px solid #bbb; margin:32px 0 32px 0;">
        <?php $mc_no = '2024-001'; // Static MC No. ?>
        <div style="display:flex;justify-content:center;">
          <div id="certificatePreview" class="card" style="max-width:850px; width:850px; background:#f9f9f9;">
            <div style="display:flex;align-items:center;justify-content:center;position:relative;margin-bottom:0;">
              <!-- Left Logo -->
              <img src="../css/images/garciaIcon.jpg" alt="Padre Garcia Icon" style="height:80px;width:auto;margin-right:32px;align-self:center;">
              <div style="flex:1;text-align:center;">
                <div style="font-family:'Times New Roman', Times, serif;font-size:1.15rem;line-height:1.3;margin-bottom:2px;">
                  Republic of the Philippines<br>
                  Province of Batangas<br>
                  MUNICIPALITY OF PADRE GARCIA
                </div>
                <div style="display:flex;align-items:center;justify-content:center;">
                  <span style="font-family:'Times New Roman', Times, serif;font-size:2rem;font-weight:900;letter-spacing:1px;margin-bottom:0;white-space:nowrap;">
                    OFFICE OF THE MUNICIPAL MAYOR
                  </span>
                </div>
                <hr style="border:0; border-top:5px solid #222; margin:18px 0 18px 0;">
                <div style="display:flex;align-items:center;justify-content:center;">
                  <span style="font-family:'Times New Roman', Times, serif;font-size:2rem;font-weight:900;letter-spacing:18px;margin-top:0;margin-bottom:0;white-space:nowrap;">
                    CERTIFICATION
                  </span>
                </div>
              </div>
              <!-- Right Logo -->
              <img src="../css/images/Seal_of_Batangas.png" alt="Batangas Seal" style="height:80px;width:auto;margin-left:32px;align-self:center;">
            </div>
            <div style="margin-top:20px;">
              <!-- MC No. on the far right between CERTIFICATION and certificate body -->
              <div style="margin-top:12px;display:flex;justify-content:flex-end;">
                <span style="background:yellow; padding:2px 18px; font-weight:bold; font-size:1.15rem;">
                  MC No. <?php echo $mc_no; ?>
                </span>
              </div>
              <p>This is to certify that <strong><?php echo htmlspecialchars($_POST['name']); ?></strong> of Barangay <strong><?php echo htmlspecialchars($_POST['barangay']); ?></strong></p>
              <ul style="list-style:none; padding-left:0;">
                <?php
                  $actions = [
                    'register_death' => 'register the death of <strong>' . htmlspecialchars($_POST['deceased'] ?? '') . '</strong> and rent CRYPT for five (5) years',
                    'renewal_crypt' => 'renewal of CRYPT',
                    'transfer_remains' => 'transfer the remains of',
                    'reopen_tomb' => 're-open the tomb of',
                    'reenter_remains' => 're-enter the remains of'
                  ];
                  $selected = isset($_POST['actions']) ? $_POST['actions'] : [];
                  foreach ($actions as $key => $desc) {
                    $checked = in_array($key, $selected) ? 'checked' : '';
                    // Add spacing between actions
                    echo '<li style="margin-bottom:20px;"><input type="checkbox" ' . $checked . ' disabled> ' . $desc . '</li>';
                  }
                ?>
              </ul>
              <p>
                Who died last <strong>
                  <?php
                    if (!empty($_POST['date_died'])) {
                      echo strtoupper(date('M-d-Y', strtotime($_POST['date_died'])));
                    }
                  ?>
                </strong> and was buried at the Municipal Cemetery.<br>
                Issued this <strong>
                  <?php
                    if (!empty($_POST['date'])) {
                      echo strtoupper(date('M-d-Y', strtotime($_POST['date'])));
                    }
                  ?>
                </strong> upon the request of Mr./Ms. <strong><?php echo htmlspecialchars($_POST['name']); ?></strong> for whatever purpose it may serve.<br>
                Apartment No. <strong><?php echo htmlspecialchars($_POST['apartment']); ?></strong>
              </p>
              <div style="margin-top:30px;">
                <div style="float:left;">
                  <strong>Recommending Approval:</strong><br>
                  <div style="height:40px;"></div> <!-- Space for signature -->
                  ENGR. KHRISTINE Z. TAPALLA, EnP<br>
                  MPDC/ZA
                </div>
                <!-- Add spacing between signatures -->
                <div style="float:left; width:40px;">&nbsp;</div>
                <div style="float:right;">
                  <strong>Approved by:</strong><br>
                  <div style="height:40px;"></div> <!-- Space for signature -->
                  ATTY. MARK LESTER G. MANALO<br>
                  Municipal Administrator
                </div>
                <div style="clear:both;"></div>
              </div>
              <div style="margin-top:30px;">
                <strong>OR No.:</strong><br>
                <strong>Date Paid:</strong><br>
                <strong>Amount:</strong><br>
                <strong>Renewal:</strong>
                <?php echo strtoupper(date('M-Y', strtotime($renewal))); ?>
              </div>
              <div style="margin-top:30px; text-align:center;">
                <img src="../css/images/CertFooter.png" alt="Certificate Footer" style="max-width:100%;height:auto;">
              </div>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <div id="masterlistTab" class="card" style="display:none;">
      <h2 style="margin-bottom:18px;font-size:1.25rem;font-weight:600;">Certification Masterlist</h2>
      <div style="background:#fff; padding:20px; border-radius:8px; box-shadow:0 2px 8px #eee; max-width:900px;">
        <!-- Masterlist content goes here -->
        <p>Masterlist of all issued certificates will be shown here.</p>
      </div>
    </div>

    <script>
      function showTab(tabId) {
        document.getElementById('certTab').style.display = tabId === 'certTab' ? '' : 'none';
        document.getElementById('masterlistTab').style.display = tabId === 'masterlistTab' ? '' : 'none';
        document.getElementById('certTabBtn').classList.toggle('active', tabId === 'certTab');
        document.getElementById('masterlistTabBtn').classList.toggle('active', tabId === 'masterlistTab');
      }
      // Show Certification tab by default
      showTab('certTab');

      function showWarning(id, show) {
        document.getElementById(id).style.display = show ? 'block' : 'none';
      }
      // Validation for Name and Deceased Name fields
      document.getElementById('certificateForm').addEventListener('submit', function(e) {
        let valid = true;
        const nameField = document.getElementById('nameField');
        const deceasedField = document.getElementById('deceasedField');
        const nameRegex = /^[A-Za-z\s]+$/;

        // Validate Name
        if (!nameRegex.test(nameField.value.trim())) {
          nameField.style.border = '2px solid #e74c3c';
          nameField.style.background = '#fff0f0';
          showWarning('nameWarning', true);
          valid = false;
        } else {
          nameField.style.border = '';
          nameField.style.background = '';
          showWarning('nameWarning', false);
        }

        // Validate Deceased Name
        if (deceasedField.value.trim() !== '' && !nameRegex.test(deceasedField.value.trim())) {
          deceasedField.style.border = '2px solid #e74c3c';
          deceasedField.style.background = '#fff0f0';
          showWarning('deceasedWarning', true);
          valid = false;
        } else {
          deceasedField.style.border = '';
          deceasedField.style.background = '';
          showWarning('deceasedWarning', false);
        }

        if (!valid) {
          e.preventDefault();
        }
      });
    </script>

  </main>

</body>
</html>
</body>
</html>
</body>
</html>
