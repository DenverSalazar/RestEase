<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    // Redirect to login page if not logged in
    header("Location: ../AdminLogin.php"); // Adjust the path if needed
    exit;
}

// --- Suggestion Data for Deceased Name field ---
$deceasedNameSuggestions = [];
include_once '../Includes/db.php';
$deceasedResult = $conn->query("SELECT DISTINCT firstName, middleName, lastName, suffix FROM deceased WHERE firstName IS NOT NULL AND lastName IS NOT NULL AND firstName != '' AND lastName != ''");
if ($deceasedResult && $deceasedResult->num_rows > 0) {
    while ($row = $deceasedResult->fetch_assoc()) {
        $fullName = trim($row['firstName'] . ' ' . $row['middleName'] . ' ' . $row['lastName']);
        if (!empty($row['suffix'])) {
            $fullName .= ' ' . trim($row['suffix']);
        }
        $fullName = preg_replace('/\s+/', ' ', $fullName);
        if ($fullName !== '') $deceasedNameSuggestions[$fullName] = true;
    }
}
$deceasedNameSuggestions = array_keys($deceasedNameSuggestions);

// --- AJAX endpoint for deceased info autofill ---
if (isset($_GET['get_deceased_info']) && strlen($_GET['get_deceased_info']) > 0) {
    include_once '../Includes/db.php';
    $name = $_GET['get_deceased_info'];
    $stmt = $conn->prepare("SELECT firstName, middleName, lastName, suffix, residency, nicheID, dateDied, informantName FROM deceased WHERE CONCAT_WS(' ', firstName, middleName, lastName, suffix) = ? LIMIT 1");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $res = $stmt->get_result();
    $info = $res->fetch_assoc();
    $stmt->close();
    header('Content-Type: application/json');
    echo json_encode($info ?: []);
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
    /* --- Begin Certificate Masterlist Table Design (renamed from cemetery-masterlist-table) --- */
    .certificate-masterlist-table {
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
    .certificate-masterlist-table th, .certificate-masterlist-table td {
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
    .certificate-masterlist-table th {
      background: #f7f8fa;
      font-weight: 500;
      color: #333;
      font-size: 0.77rem;
    }
    .certificate-masterlist-table tr:last-child td {
      border-bottom: none;
    }
    /* Responsive adjustments */
    @media (max-width: 900px) {
      .certificate-masterlist-table th, .certificate-masterlist-table td {
        font-size: 0.75rem;
        padding: 6px 6px;
      }
    }
    /* --- End Certificate Masterlist Table Design --- */
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
        <!-- Deceased Information Section (moved to top) -->
        <div style="font-weight:600;font-size:1.08rem;margin-bottom:8px;">Deceased Information</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px 32px;">
          <div>
            <label>Deceased Name:</label>
            <input type="text" name="deceased" id="deceasedField" value="<?php echo isset($_POST['deceased']) ? htmlspecialchars($_POST['deceased']) : ''; ?>" style="width:90%;" autocomplete="off" list="deceasedNameSuggestions">
            <datalist id="deceasedNameSuggestions">
              <?php foreach ($deceasedNameSuggestions as $suggestion): ?>
                <option value="<?php echo htmlspecialchars($suggestion); ?>"></option>
              <?php endforeach; ?>
            </datalist>
          </div>
          <div>
            <label>Date Died:</label>
            <input type="date" name="date_died" id="dateDiedField" value="<?php echo isset($_POST['date_died']) ? htmlspecialchars($_POST['date_died']) : ''; ?>" style="width:90%;">
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px 32px;">
          <div>
            <label>Apartment No.:</label>
            <input type="text" name="apartment" id="apartmentField" value="<?php echo isset($_POST['apartment']) ? htmlspecialchars($_POST['apartment']) : ''; ?>" required style="width:90%;">
          </div>
          <div>
            <label>Barangay:</label>
            <input type="text" name="barangay" id="barangayField" value="<?php echo isset($_POST['barangay']) ? htmlspecialchars($_POST['barangay']) : ''; ?>" required style="width:90%;">
          </div>
        </div>
        <!-- Personal Information Section -->
        <div style="font-weight:600;font-size:1.08rem;margin-bottom:8px;margin-top:24px;">Personal Information</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px 32px;">
          <div>
            <label>Name:</label>
            <input type="text" name="name" id="nameField" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required style="width:90%;">
            <div id="nameWarning" style="display:none;color:#e74c3c;font-size:0.98rem;margin-top:2px;">Name must not contain numbers or symbols.</div>
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
          <div>
            <label>Municipal Administrator Name:</label>
            <input type="text" name="admin_name" id="adminNameField"
              value="<?php
                echo isset($_POST['admin_name']) && $_POST['admin_name'] !== ''
                  ? htmlspecialchars($_POST['admin_name'])
                  : 'ATTY. MARK LESTER G. MANALO';
              ?>"
              required style="width:90%;">
          </div>
        </div>
        <script>
          // Save Municipal Administrator Name to localStorage on change for real-time persistence
          const adminNameField = document.getElementById('adminNameField');
          // Load from localStorage if available
          if (localStorage.getItem('municipal_admin_name')) {
            adminNameField.value = localStorage.getItem('municipal_admin_name');
          }
          adminNameField.addEventListener('input', function() {
            localStorage.setItem('municipal_admin_name', this.value);
          });
        </script>
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
             <!-- Certificate Preview -->
      <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        <hr style="border:0; border-top:3px solid #bbb; margin:32px 0 32px 0;">
        <?php
          // Fetch payment details from ledger table for the current certificate
          // Match by ApartmentNo and Payee (informant name)
          $orNo = '';
          $datePaid = '';
          $amount = '';
          $mc_no = '';
          if (!empty($_POST['apartment']) && !empty($_POST['name'])) {
            $aptNo = $conn->real_escape_string($_POST['apartment']);
            $payee = $conn->real_escape_string($_POST['name']);
            // Try exact match first
            $ledgerRes = $conn->query("SELECT ORNumber, DatePaid, Amount, MCNo FROM ledger WHERE ApartmentNo='$aptNo' AND Payee='$payee' AND DatePaid IS NOT NULL AND DatePaid != '' ORDER BY DatePaid DESC LIMIT 1");
            if ($ledgerRes && $ledgerRes->num_rows > 0) {
              $ledgerRow = $ledgerRes->fetch_assoc();
              $orNo = $ledgerRow['ORNumber'];
              $datePaid = $ledgerRow['DatePaid'];
              $amount = $ledgerRow['Amount'];
              $mc_no = $ledgerRow['MCNo'];
            } else {
              // Fallback: match only by Payee if no ApartmentNo match
              $ledgerRes = $conn->query("SELECT ORNumber, DatePaid, Amount, MCNo FROM ledger WHERE Payee='$payee' AND DatePaid IS NOT NULL AND DatePaid != '' ORDER BY DatePaid DESC LIMIT 1");
              if ($ledgerRes && $ledgerRes->num_rows > 0) {
                $ledgerRow = $ledgerRes->fetch_assoc();
                $orNo = $ledgerRow['ORNumber'];
                $datePaid = $ledgerRow['DatePaid'];
                $amount = $ledgerRow['Amount'];
                $mc_no = $ledgerRow['MCNo'];
              }
            }
          }
          // MPDC/ZA name is the logged-in admin name
          $mpdc_name = isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : '';
          // Municipal Administrator name from the field
          $admin_name = isset($_POST['admin_name']) ? $_POST['admin_name'] : '';
        ?>
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
                  MC No. <?php echo $mc_no !== '' ? htmlspecialchars($mc_no) : '<span style="color:#e74c3c;">No data</span>'; ?>
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
                  <?php
                  // Use admin_profiles display_name if available, fallback to first_name + last_name, else empty, always uppercase
                  $adminDisplayName = '';
                  if (isset($_SESSION['admin_id'])) {
                      $adminId = $_SESSION['admin_id'];
                      $profileRes = $conn->query("SELECT display_name, first_name, last_name FROM admin_profiles WHERE admin_id = $adminId LIMIT 1");
                      if ($profileRes && $profileRes->num_rows > 0) {
                          $profile = $profileRes->fetch_assoc();
                          if (!empty($profile['display_name'])) {
                              $adminDisplayName = $profile['display_name'];
                          } else {
                              $adminDisplayName = trim($profile['first_name'] . ' ' . $profile['last_name']);
                          }
                      }
                  }
                  echo strtoupper(htmlspecialchars($adminDisplayName));
                  ?><br>
                  MPDC/ZA
                </div>
                <!-- Add spacing between signatures -->
                <div style="float:left; width:40px;">&nbsp;</div>
                <div style="float:right;">
                  <strong>Approved by:</strong><br>
                  <div style="height:40px;"></div> <!-- Space for signature -->
                  <?php echo htmlspecialchars($admin_name); ?><br>
                  Municipal Administrator
                </div>
                <div style="clear:both;"></div>
              </div>
              <div style="margin-top:30px;">
                <?php
                // Fetch payment details from ledger table for the current certificate
                // Match by ApartmentNo and Payee (informant name)
                $orNo = '';
                $datePaid = '';
                $amount = '';
                if (!empty($_POST['apartment']) && !empty($_POST['name'])) {
                  $aptNo = $conn->real_escape_string($_POST['apartment']);
                  $payee = $conn->real_escape_string($_POST['name']);
                  // Try exact match first
                  $ledgerRes = $conn->query("SELECT ORNumber, DatePaid, Amount FROM ledger WHERE ApartmentNo='$aptNo' AND Payee='$payee' AND DatePaid IS NOT NULL AND DatePaid != '' ORDER BY DatePaid DESC LIMIT 1");
                  if ($ledgerRes && $ledgerRes->num_rows > 0) {
                    $ledgerRow = $ledgerRes->fetch_assoc();
                    $orNo = $ledgerRow['ORNumber'];
                    $datePaid = $ledgerRow['DatePaid'];
                    $amount = $ledgerRow['Amount'];
                  } else {
                    // Fallback: match only by Payee if no ApartmentNo match
                    $ledgerRes = $conn->query("SELECT ORNumber, DatePaid, Amount FROM ledger WHERE Payee='$payee' AND DatePaid IS NOT NULL AND DatePaid != '' ORDER BY DatePaid DESC LIMIT 1");
                    if ($ledgerRes && $ledgerRes->num_rows > 0) {
                      $ledgerRow = $ledgerRes->fetch_assoc();
                      $orNo = $ledgerRow['ORNumber'];
                      $datePaid = $ledgerRow['DatePaid'];
                      $amount = $ledgerRow['Amount'];
                    }
                  }
                }
                ?>
                <strong>OR No.:</strong> <?php echo $orNo !== '' ? htmlspecialchars($orNo) : '<span style="color:#e74c3c;">No data</span>'; ?><br>
                <strong>Date Paid:</strong> <?php echo $datePaid !== '' ? htmlspecialchars($datePaid) : '<span style="color:#e74c3c;">No data</span>'; ?><br>
                <strong>Amount:</strong> <?php echo $amount !== '' ? '₱' . number_format($amount, 2) : '<span style="color:#e74c3c;">No data</span>'; ?><br>
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
        <!-- Preview Button at the bottom -->
        <div style="margin-top:32px;text-align:right;border-top:1px solid #f0f0f0;padding-top:24px;">
          <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['preview'])): ?>
            <button type="submit" name="submit_cert" class="btn" style="width: 140px; padding: 12px 0; font-size:1.08rem;">Submit</button>
          <?php else: ?>
            <button type="submit" name="preview" class="btn" style="width: 140px; padding: 12px 0; font-size:1.08rem;">Generate</button>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <div id="masterlistTab" class="card" style="display:none;">
      <h2 style="margin-bottom:18px;font-size:1.25rem;font-weight:600;">Certification Masterlist</h2>
      <div style="margin-bottom:18px;">
        <button class="cert-filter-btn" data-filter="all" style="margin-right:8px;">All</button>
        <button class="cert-filter-btn" data-filter="DNew" style="margin-right:8px;">New</button>
        <button class="cert-filter-btn" data-filter="DReEnter" style="margin-right:8px;">ReEnter</button>
        <button class="cert-filter-btn" data-filter="DRenew" style="margin-right:8px;">ReNew</button>
        <button class="cert-filter-btn" data-filter="DReOpen" style="margin-right:8px;">ReOpen</button>
        <button class="cert-filter-btn" data-filter="DTransfer">Transfer</button>
      </div>
      <!-- Custom Search Bar (like clientsrequest.php, magnifying glass inside, no clear button) -->
      <div style="margin-bottom:18px;display:flex;align-items:center;gap:8px;">
        <div style="display:flex;align-items:center;background:#fff;border-radius:10px;border:1.5px solid #d0d7e2;padding:0 16px;height:40px;box-shadow:0 1px 4px rgba(60,72,88,0.03);min-width:320px;max-width:420px;">
          <i class="fas fa-search" style="color:#b0b0b0;margin-right:8px;font-size:1.1rem;"></i>
          <input type="text" id="certCustomSearch" placeholder="Search Certification Masterlist..." style="border:none;background:transparent;outline:none;font-size:1.05rem;width:100%;color:#222;font-weight:400;padding:0;margin:0;">
        </div>
      </div>
      <div style="overflow-x:auto;">
        <table class="certificate-masterlist-table" id="certificate-masterlist-table" style="min-width:1100px;">
          <thead>
            <tr>
              <th data-col="AptNo">Apt. No</th>
              <th data-col="NameOfDeceased">Name of Deceased</th>
              <th data-col="InformantName">Informant Name</th>
              <th data-col="InformantAddress">Informant Address</th>
              <th data-col="AddressOfDeceased">Address of Deceased</th>
              <th data-col="DateDied">Date Died</th>
              <th data-col="DateInternment">Date Internment</th>
              <th data-col="DNew">DNew</th>
              <th data-col="DRenew">DRenew</th>
              <th data-col="DTransfer">DTransfer</th>
              <th data-col="DReOpen">DReOpen</th>
              <th data-col="DReEnter">DReEnter</th>
              <th data-col="DatePaid">Date Paid</th>
              <th data-col="Payee">Payee</th>
              <th data-col="Amount">Amount</th>
              <th data-col="ORNumber">ORNumber</th>
              <th data-col="Validity">Validity</th>
              <th data-col="MCNo">MCNo.</th>
            </tr>
          </thead>
          <tbody>
            <?php
            // Fetch certification masterlist data from DB
            $certRes = $conn->query("SELECT * FROM certification ORDER BY id DESC");
            $rows = [];
            if ($certRes && $certRes->num_rows > 0) {
              while ($row = $certRes->fetch_assoc()) {
                // Get accurate DateInternment from deceased table by matching NameOfDeceased and AptNo
                $dateInternment = $row['DateInternment'];
                if ($dateInternment == '0000-00-00' || empty($dateInternment)) {
                  $deceasedQuery = $conn->prepare("SELECT dateInternment FROM deceased WHERE nicheID = ? AND CONCAT_WS(' ', firstName, middleName, lastName, suffix) = ? LIMIT 1");
                  $deceasedQuery->bind_param('ss', $row['AptNo'], $row['NameOfDeceased']);
                  $deceasedQuery->execute();
                  $deceasedRes = $deceasedQuery->get_result();
                  if ($deceasedRes && $deceasedRes->num_rows > 0) {
                    $deceasedRow = $deceasedRes->fetch_assoc();
                    $dateInternment = $deceasedRow['dateInternment'];
                  }
                  $deceasedQuery->close();
                }
                // Store all rows for JS filtering
                $rows[] = [
                  'AptNo' => $row['AptNo'],
                  'NameOfDeceased' => $row['NameOfDeceased'],
                  'InformantName' => $row['InformantName'],
                  'InformantAddress' => $row['InformantAddress'],
                  'AddressOfDeceased' => $row['AddressOfDeceased'],
                  'DateDied' => $row['DateDied'],
                  'DateInternment' => $dateInternment,
                  'DNew' => $row['DNew'],
                  'DRenew' => $row['DRenew'],
                  'DTransfer' => $row['DTransfer'],
                  'DReOpen' => $row['DReOpen'],
                  'DReEnter' => $row['DReEnter'],
                  'DatePaid' => $row['DatePaid'],
                  'Payee' => $row['Payee'],
                  'Amount' => $row['Amount'],
                  'ORNumber' => $row['ORNumber'],
                  'Validity' => $row['Validity'],
                  'MCNo' => $row['MCNo'],
                ];
              }
            }
            // Output all rows, add data-action attributes for accurate JS filtering
            foreach ($rows as $row) {
              // Determine which actions are checked
              $actions = [];
              foreach (['DNew','DRenew','DTransfer','DReOpen','DReEnter'] as $action) {
                if ($row[$action] === '✔') $actions[] = $action;
              }
              $actionAttr = implode(' ', array_map(function($a){return "data-action-$a='1'";}, $actions));
              echo "<tr $actionAttr>";
              echo '<td data-col="AptNo">' . htmlspecialchars($row['AptNo']) . '</td>';
              echo '<td data-col="NameOfDeceased">' . htmlspecialchars($row['NameOfDeceased']) . '</td>';
              echo '<td data-col="InformantName">' . htmlspecialchars($row['InformantName']) . '</td>';
              echo '<td data-col="InformantAddress">' . htmlspecialchars($row['InformantAddress']) . '</td>';
              echo '<td data-col="AddressOfDeceased">' . htmlspecialchars($row['AddressOfDeceased']) . '</td>';
              echo '<td data-col="DateDied">' . htmlspecialchars($row['DateDied']) . '</td>';
              echo '<td data-col="DateInternment">' . ($row['DateInternment'] && $row['DateInternment'] != '0000-00-00' ? htmlspecialchars($row['DateInternment']) : '<span style="color:#e74c3c;">No data</span>') . '</td>';
              echo '<td data-col="DNew">' . htmlspecialchars($row['DNew']) . '</td>';
              echo '<td data-col="DRenew">' . htmlspecialchars($row['DRenew']) . '</td>';
              echo '<td data-col="DTransfer">' . htmlspecialchars($row['DTransfer']) . '</td>';
              echo '<td data-col="DReOpen">' . htmlspecialchars($row['DReOpen']) . '</td>';
              echo '<td data-col="DReEnter">' . htmlspecialchars($row['DReEnter']) . '</td>';
              echo '<td data-col="DatePaid">' . htmlspecialchars($row['DatePaid']) . '</td>';
              echo '<td data-col="Payee">' . htmlspecialchars($row['Payee']) . '</td>';
              echo '<td data-col="Amount">' . ($row['Amount'] !== null ? '₱' . number_format($row['Amount'], 2) : '') . '</td>';
              echo '<td data-col="ORNumber">' . htmlspecialchars($row['ORNumber']) . '</td>';
              echo '<td data-col="Validity">' . htmlspecialchars($row['Validity']) . '</td>';
              echo '<td data-col="MCNo">' . htmlspecialchars($row['MCNo']) . '</td>';
              echo '</tr>';
            }
            ?>
          </tbody>
        </table>
      </div>
    </div>
    <!-- DataTables JS for Certification Masterlist -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script>
      function showTab(tabId) {
        document.getElementById('certTab').style.display = tabId === 'certTab' ? '' : 'none';
        document.getElementById('masterlistTab').style.display = tabId === 'masterlistTab' ? '' : 'none';
        document.getElementById('certTabBtn').classList.toggle('active', tabId === 'certTab');
        document.getElementById('masterlistTabBtn').classList.toggle('active', tabId === 'masterlistTab');
      }
      // Show correct tab on page load
      <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        showTab('certTab');
      <?php else: ?>
        showTab('certTab');
      <?php endif; ?>

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

        if (!valid) {
          e.preventDefault();
        }
      });

      // Certificate Masterlist Filter Logic
      (function() {
        const filterButtons = document.querySelectorAll('.cert-filter-btn');
        const table = document.getElementById('certificate-masterlist-table');
        const allCols = [
          "AptNo", "NameOfDeceased", "InformantName", "InformantAddress", "AddressOfDeceased", "DateDied", "DateInternment",
          "DNew", "DRenew", "DTransfer", "DReOpen", "DReEnter", "DatePaid", "Payee", "Amount", "ORNumber", "Validity", "MCNo"
        ];

        let certMasterlistDT = $('#certificate-masterlist-table').DataTable({
          paging: true,
          searching: true,
          ordering: true,
          info: true,
          lengthChange: true,
          pageLength: 10,
          dom: 'lrtip',
          language: {
            search: "",
            searchPlaceholder: "Search..."
          }
        });

        // Custom search bar logic
        $('#certCustomSearch').on('keyup change', function() {
          certMasterlistDT.search(this.value).draw();
        });

        function showColumns(colsToShow) {
          // Show/hide headers
          table.querySelectorAll('th').forEach(th => {
            const col = th.getAttribute('data-col');
            th.style.display = colsToShow.includes(col) ? '' : 'none';
          });
          // Show/hide cells
          table.querySelectorAll('tbody tr').forEach(tr => {
            tr.querySelectorAll('td').forEach(td => {
              const col = td.getAttribute('data-col');
              td.style.display = colsToShow.includes(col) ? '' : 'none';
            });
          });
        }

        // Accurate filter logic based on checked columns
        function filterRowsByAction(actionCol) {
          certMasterlistDT.rows().every(function() {
            const $row = $(this.node());
            if (!actionCol || actionCol === 'all') {
              $row.show();
            } else {
              // Only show rows that have the correct action checked
              $row.toggle($row.attr('data-action-' + actionCol) === '1');
            }
          });
        }

        filterButtons.forEach(btn => {
          btn.addEventListener('click', function() {
            filterButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            const filter = btn.getAttribute('data-filter');
            if (filter === 'all') {
              showColumns(allCols);
              filterRowsByAction(null);
            } else {
              showColumns(['AptNo', 'NameOfDeceased', filter]);
              filterRowsByAction(filter);
            }
          });
        });

        // Set default to All
        document.querySelector('.cert-filter-btn[data-filter="all"]').click();
      })();
      // Autofill fields when deceased is selected
      document.getElementById('deceasedField').addEventListener('change', function() {
        const name = this.value;
        if (!name) return;
        fetch('?get_deceased_info=' + encodeURIComponent(name))
          .then(res => res.json())
          .then(data => {
            if (!data) return;
            if (data.dateDied) document.getElementById('dateDiedField').value = data.dateDied;
            if (data.nicheID) document.getElementById('apartmentField').value = data.nicheID;
            if (data.residency) document.getElementById('barangayField').value = data.residency;
            if (data.informantName) document.getElementById('nameField').value = data.informantName;
          });
      });
    </script>
  </main>
</body>
</html>

<?php
// Handle certificate submission to database
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_cert'])) {
  // Prepare values from POST and previously fetched ledger data
  $aptNo = $_POST['apartment'] ?? '';
  $nameOfDeceased = $_POST['deceased'] ?? '';
  $informantName = $_POST['name'] ?? '';
  $informantAddress = $_POST['barangay'] ?? '';
  $addressOfDeceased = $_POST['barangay'] ?? '';
  $dateDied = $_POST['date_died'] ?? '';
  $dateInternment = ''; // You may need to add this field to the form if needed
  $dNew = in_array('register_death', $_POST['actions'] ?? []) ? '✔' : '';
  $dRenew = in_array('renewal_crypt', $_POST['actions'] ?? []) ? '✔' : '';
  $dTransfer = in_array('transfer_remains', $_POST['actions'] ?? []) ? '✔' : '';
  $dReOpen = in_array('reopen_tomb', $_POST['actions'] ?? []) ? '✔' : '';
  $dReEnter = in_array('reenter_remains', $_POST['actions'] ?? []) ? '✔' : '';
  // Get payment details from ledger (same logic as preview)
  $orNo = '';
  $datePaid = '';
  $amount = '';
  $mc_no = '';
  if (!empty($aptNo) && !empty($informantName)) {
    $aptNoEsc = $conn->real_escape_string($aptNo);
    $payeeEsc = $conn->real_escape_string($informantName);
    $ledgerRes = $conn->query("SELECT ORNumber, DatePaid, Amount, MCNo FROM ledger WHERE ApartmentNo='$aptNoEsc' AND Payee='$payeeEsc' AND DatePaid IS NOT NULL AND DatePaid != '' ORDER BY DatePaid DESC LIMIT 1");
    if ($ledgerRes && $ledgerRes->num_rows > 0) {
      $ledgerRow = $ledgerRes->fetch_assoc();
      $orNo = $ledgerRow['ORNumber'];
      $datePaid = $ledgerRow['DatePaid'];
      $amount = $ledgerRow['Amount'];
      $mc_no = $ledgerRow['MCNo'];
    } else {
      $ledgerRes = $conn->query("SELECT ORNumber, DatePaid, Amount, MCNo FROM ledger WHERE Payee='$payeeEsc' AND DatePaid IS NOT NULL AND DatePaid != '' ORDER BY DatePaid DESC LIMIT 1");
      if ($ledgerRes && $ledgerRes->num_rows > 0) {
        $ledgerRow = $ledgerRes->fetch_assoc();
        $orNo = $ledgerRow['ORNumber'];
        $datePaid = $ledgerRow['DatePaid'];
        $amount = $ledgerRow['Amount'];
        $mc_no = $ledgerRow['MCNo'];
      }
    }
  }
  $validity = $_POST['renewal'] ?? '';
  $payee = $informantName;
  $masterlistFields = [
    'AptNo', 'NameOfDeceased', 'InformantName', 'InformantAddress', 'AddressOfDeceased',
    'DateDied', 'DateInternment', 'DNew', 'DRenew', 'DTransfer', 'DReOpen', 'DReEnter',
    'DatePaid', 'Payee', 'Amount', 'ORNumber', 'Validity', 'MCNo'
  ];
  // Insert into certification table
  $stmt = $conn->prepare("INSERT INTO certification (AptNo, NameOfDeceased, InformantName, InformantAddress, AddressOfDeceased, DateDied, DateInternment, DNew, DRenew, DTransfer, DReOpen, DReEnter, DatePaid, Payee, Amount, ORNumber, Validity, MCNo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
  $stmt->bind_param(
    'ssssssssssssssssss',
    $aptNo,
    $nameOfDeceased,
    $informantName,
    $informantAddress,
    $addressOfDeceased,
    $dateDied,
    $dateInternment,
    $dNew,
    $dRenew,
    $dTransfer,
    $dReOpen,
    $dReEnter,
    $datePaid,
    $payee,
    $amount,
    $orNo,
    $validity,
    $mc_no
  );
  $stmt->execute();
  $stmt->close();
  // Output HTML before closing PHP to ensure modal is rendered
  ?>
  <div id="certSuccessModal" style="position:fixed;z-index:9999;left:0;top:0;right:0;bottom:0;background:rgba(44,62,80,0.18);display:flex;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:16px;box-shadow:0 8px 32px rgba(60,60,60,0.18),0 1.5px 6px rgba(0,0,0,0.08);padding:32px 32px 24px 32px;min-width:340px;max-width:95vw;text-align:center;position:relative;">
      <div>
        <i class='fas fa-check-circle' style='color:#2ecc71;font-size:2.5rem;margin-bottom:8px;'></i>
        <h2 style="color:#2ecc71;margin:0;font-size:1.3rem;">Success!</h2>
      </div>
      <div style="margin:18px 0 24px 0;">
        <p style="color:#444;font-size:1.07rem;margin:0;">
          Certificate has been submitted successfully.
        </p>
      </div>
      <div style="display:flex;justify-content:center;gap:16px;">
        <button id="certSuccessModalCloseBtn" style="background:#506C84;color:#fff;border:none;padding:12px 32px;border-radius:8px;cursor:pointer;font-weight:500;font-size:1rem;">OK</button>
      </div>
    </div>
  </div>
  <script>
    document.getElementById("certSuccessModalCloseBtn").onclick = function() {
      window.location.href = "Certificate.php";
    };
    document.getElementById("certSuccessModal").onclick = function(e) {
      if (e.target === this) window.location.href = "Certificate.php";
    };
  </script>
  <?php
  exit;
}
  exit;

