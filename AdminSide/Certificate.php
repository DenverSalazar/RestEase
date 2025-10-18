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
    $name = trim($_GET['get_deceased_info']);
    $nameNorm = preg_replace('/\s+/', ' ', $name);
    $parts = explode(' ', $nameNorm);
    $firstCandidate = $parts[0] ?? $nameNorm;
    $lastCandidate = $parts[count($parts)-1] ?? $nameNorm;

    // Prepare parameters (lowercased for case-insensitive comparisons)
    $likeParam = '%' . strtolower($nameNorm) . '%';
    $firstLower = strtolower($firstCandidate);
    $lastLower = strtolower($lastCandidate);
    $nicheLower = strtolower($nameNorm);

    // Search by: normalized full name LIKE, exact first+last (case-insensitive), or nicheID exact
    $stmt = $conn->prepare("
        SELECT id, firstName, middleName, lastName, suffix, residency, nicheID, dateDied, informantName
        FROM deceased
        WHERE LOWER(CONCAT_WS(' ', firstName, middleName, lastName, suffix)) LIKE ?
           OR (LOWER(firstName) = ? AND LOWER(lastName) = ?)
           OR LOWER(nicheID) = ?
        LIMIT 10
    ");
    $results = [];
    if ($stmt) {
        $stmt->bind_param('ssss', $likeParam, $firstLower, $lastLower, $nicheLower);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) {
            $results[] = $r;
        }
        $stmt->close();
    }

    header('Content-Type: application/json');
    echo json_encode($results);
    exit;
}

// --- New: View generated certificate by id ---
if (isset($_GET['view_cert']) && is_numeric($_GET['view_cert'])) {
    include_once '../Includes/db.php';
    $certId = intval($_GET['view_cert']);
    $stmt = $conn->prepare("SELECT * FROM certification WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $certId);
        $stmt->execute();
        $res = $stmt->get_result();
        $cert = $res->fetch_assoc();
        $stmt->close();
    } else {
        $cert = null;
    }

    if (!$cert) {
        echo "Certificate not found.";
        exit;
    }

    // Prepare values (fallbacks)
    $aptNo = htmlspecialchars($cert['AptNo']);
    $nameOfDeceased = htmlspecialchars($cert['NameOfDeceased']);
    $informantName = htmlspecialchars($cert['InformantName']);
    $informantAddress = htmlspecialchars($cert['InformantAddress']);
    $addressOfDeceased = htmlspecialchars($cert['AddressOfDeceased']);
    $dateDied = $cert['DateDied'];
    $dateInternment = $cert['DateInternment'];
    $orNo = htmlspecialchars($cert['ORNumber']);
    $datePaid = htmlspecialchars($cert['DatePaid']);
    $amount = $cert['Amount'] !== null ? '₱' . number_format($cert['Amount'], 2) : '';
    $mc_no = htmlspecialchars($cert['MCNo']);
    $validity = htmlspecialchars($cert['Validity']);
    $adminNameSaved = htmlspecialchars($cert['AdminName'] ?? '');

    // For MPDC/ZA (recommending), attempt to reuse logged-in admin profile when available
    $mpdcName = '';
    if (isset($_SESSION['admin_id'])) {
        $adminId = $_SESSION['admin_id'];
        $pRes = $conn->query("SELECT display_name, first_name, last_name FROM admin_profiles WHERE admin_id = $adminId LIMIT 1");
        if ($pRes && $pRes->num_rows > 0) {
            $p = $pRes->fetch_assoc();
            $mpdcName = !empty($p['display_name']) ? $p['display_name'] : trim($p['first_name'] . ' ' . $p['last_name']);
        }
    }
    $mpdcName = strtoupper(htmlspecialchars($mpdcName));

    // Render certificate page (simple printable page)
    ?>
    <!doctype html>
    <html>
    <head>
      <meta charset="utf-8">
      <title>Certificate #<?php echo $certId; ?></title>
      <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
      <style>
        body { font-family: 'Poppins', sans-serif; margin:0; padding:20px; background:#fff; color:#000; }
        #certificatePreview { width: 210mm; margin:0 auto; padding:16mm; box-sizing:border-box; background:#fff; }
        .header { text-align:center; }
        .logos { display:flex; align-items:center; justify-content:center; gap:28px; }
        .logos img { max-height:80px; width:auto; }
        .title { font-family: "Times New Roman", serif; font-weight:700; margin-top:6px; }
        .mc-no { text-align:right; margin-top:8px; font-weight:700; background:yellow; display:inline-block; padding:4px 10px; }
        .body { margin-top:18px; font-size:14px; line-height:1.35; }
        .signatures { margin-top:36px; display:flex; justify-content:space-between; }
        .signature-block { width:45%; text-align:left; }
        .centered { text-align:center; }
        .cert-footer { margin-top:36px; text-align:center; }
        .btn { display:inline-block; background:#506C84;color:#fff;padding:6px 10px;border-radius:6px;text-decoration:none;font-weight:600; }
        @media print { body { padding:0 } #printBtn { display:none } }
      </style>
    </head>
    <body>
      <div id="certificatePreview">
        <div class="header">
          <div class="logos">
            <img src="../assets/Logo garcia.png" alt="Padre Garcia Icon">
            <div class="title">
              Republic of the Philippines<br>
              Province of Batangas<br>
              MUNICIPALITY OF PADRE GARCIA<br>
              <strong style="display:block;font-size:20px;margin-top:6px;">OFFICE OF THE MUNICIPAL MAYOR</strong>
              <hr style="border-top:4px solid #222;margin:12px 0;">
              <strong style="font-size:22px;letter-spacing:10px;">CERTIFICATION</strong>
            </div>
            <img src="../assets/Seal_of_Batangas.png" alt="Batangas Seal">
          </div>
        </div>

        <div class="mc-no">MC No. <?php echo $mc_no ?: '<span style="color:#e74c3c;">No data</span>'; ?></div>

        <div class="body">
          <p>This is to certify that <strong><?php echo htmlspecialchars($informantName); ?></strong> of Barangay <strong><?php echo htmlspecialchars($informantAddress); ?></strong></p>
          <ul style="list-style:none;padding-left:0;">
            <?php
              // Always display all possible actions. Mark checked ones based on saved certification record.
              $actionsList = [
                'DNew'     => 'register the death of <strong>' . $nameOfDeceased . '</strong> and rent CRYPT for five (5) years',
                'DRenew'   => 'renewal of CRYPT of <strong>' . $nameOfDeceased . '</strong>',
                'DTransfer'=> 'transfer the remains of <strong>' . $nameOfDeceased . '</strong>',
                'DReOpen'  => 're-open the tomb of <strong>' . $nameOfDeceased . '</strong>',
                'DReEnter' => 're-enter the remains of <strong>' . $nameOfDeceased . '</strong>',
              ];
              foreach ($actionsList as $col => $desc) {
                $checked = (!empty($cert[$col]) && $cert[$col] === '✔') ? 'checked' : '';
                echo '<li style="margin-bottom:12px;"><input type="checkbox" ' . $checked . ' disabled> ' . $desc . '</li>';
              }
            ?>
          </ul>

          <p>
            Who died last <strong><?php if (!empty($dateDied)) echo strtoupper(date('M-d-Y', strtotime($dateDied))); ?></strong> and was buried at the Municipal Cemetery.<br>
            Issued upon request of Mr./Ms. <strong><?php echo htmlspecialchars($informantName); ?></strong>.<br>
            Apartment No. <strong><?php echo $aptNo; ?></strong>
          </p>

          <div class="signatures">
            <div class="signature-block">
              <strong>Recommending Approval:</strong><br><br>
              <div style="height:48px;"></div>
              <strong><?php echo $mpdcName; ?></strong><br>
              MPDC/ZA
            </div>
            <div class="signature-block" style="text-align:right;">
              <strong>Approved by:</strong><br><br>
              <div style="height:48px;"></div>
              <strong><?php echo $adminNameSaved; ?></strong><br>
              Municipal Administrator
            </div>
          </div>

          <div style="margin-top:28px;">
            <strong>OR No.:</strong> <?php echo $orNo ?: '<span style="color:#e74c3c;">No data</span>'; ?><br>
            <strong>Date Paid:</strong> <?php echo $datePaid ?: '<span style="color:#e74c3c;">No data</span>'; ?><br>
            <strong>Amount:</strong> <?php echo $amount ?: '<span style="color:#e74c3c;">No data</span>'; ?><br>
            <strong>Renewal:</strong> <?php echo strtoupper(date('M-Y', strtotime($validity))); ?>
          </div>

          <div class="cert-footer">
            <img src="../css/images/CertFooter.png" alt="Certificate Footer" style="max-width:100%;height:auto;">
          </div>
        </div>
      </div>

      <div style="text-align:center;margin-top:12px;">
        <button id="printBtn" onclick="window.print()" class="btn">Print / Download</button>
      </div>
    </body>
    </html>
    <?php
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
    /* Certification masterlist filter buttons - underline/active style */
    .cert-filter-btn {
      border-bottom: 3px solid transparent; /* reserve space so switching doesn't shift layout */
      padding-bottom: 6px;
      transition: color 0.12s ease, border-color 0.12s ease, background 0.12s ease;
      background: transparent;
      color: #222;
      cursor: pointer;
      border-radius: 8px;
      padding: 6px 10px;
      border: 1px solid #e6e9ec;
      font-weight: 500;
    }
    .cert-filter-btn:hover { color: #0b75a8; }
    .cert-filter-btn.active { border-color: #0077b6; color: #0077b6; font-weight: 600; }
    /* allow the filter group to wrap on small screens */
    #certFilters { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
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
    @media print {
      /* Try to remove browser header/footer and fit content on one page */
      @page { size: auto; margin: 0; }
      html, body { height: 100%; margin: 0; padding: 0; }
      body * { visibility: hidden !important; }
      /* Only show certificate preview when printing */
      #certificatePreview, #certificatePreview * { visibility: visible !important; }
      /* Layout the certificate to fill the printable area with safe padding */
      #certificatePreview {
        position: absolute;
        left: 0;
        top: 0;
        width: 210mm; /* A4 width; browsers will scale if using Letter */
        height: 297mm; /* A4 height */
        box-sizing: border-box;
        margin: 0 !important;
        padding: 16mm; /* printable margin */
        background: #fff !important;
        box-shadow: none !important;
        -webkit-print-color-adjust: exact;
        page-break-inside: avoid;
        overflow: hidden;
      }
      /* Reduce image size for print */
      #certificatePreview img { max-height: 88px !important; width: auto !important; }
      /* Slightly reduce font scaling in print to help fit one page (adjust if needed) */
      #certificatePreview { font-size: 12px; }
      /* Hide the on-page print button */
      .print-btn { display: none !important; }
      /* If content still slightly overflows, apply a small scale */
      #certificatePreview[ data-scale="1" ] { transform-origin: top left; transform: scale(0.96); }
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

            <!-- container for multiple-match suggestions (hidden until needed) -->
            <div id="deceasedMatches" style="display:none;position:relative;margin-top:6px;"></div>

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
            <label>Payee Name:</label>
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
              <img src="../assets/Logo garcia.png" alt="Padre Garcia Icon" style="height:80px;width:auto;margin-right:32px;align-self:center;">
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
              <img src="../assets/Seal_of_Batangas.png" alt="Batangas Seal" style="height:80px;width:auto;margin-left:32px;align-self:center;">
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
                    'renewal_crypt' => 'renewal of CRYPT of <strong>' . htmlspecialchars($_POST['deceased'] ?? '') . '</strong>',
                    'transfer_remains' => 'transfer the remains of <strong>' . htmlspecialchars($_POST['deceased'] ?? '') . '</strong>',
                    'reopen_tomb' => 're-open the tomb of <strong>' . htmlspecialchars($_POST['deceased'] ?? '') . '</strong>',
                    'reenter_remains' => 're-enter the remains of <strong>' . htmlspecialchars($_POST['deceased'] ?? '') . '</strong>'
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
            <div style="display:flex;gap:12px;justify-content:flex-end;align-items:center;">
              <!-- Print button: uses existing printCertificate() JS and is hidden in print via .print-btn -->
              <!-- <button type="button" class="btn print-btn" onclick="printCertificate()" style="width: 160px; padding: 12px 0; font-size:1.02rem; background:#fff;color:#506C84;border:1px solid #506C84;">
                Print / Download
              </button> -->
              <button type="submit" name="submit_cert" class="btn" style="width: 140px; padding: 12px 0; font-size:1.08rem;">Submit</button>
            </div>
          <?php else: ?>
            <button type="submit" name="preview" class="btn" style="width: 140px; padding: 12px 0; font-size:1.08rem;">Generate</button>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <div id="masterlistTab" class="card" style="display:none;">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <h2 style="margin:0;font-size:1.25rem;font-weight:600;">Certification Masterlist</h2>
        <div id="certFilters">
          <button class="cert-filter-btn active" data-filter="all">all</button>
          <button class="cert-filter-btn" data-filter="DNew">New</button>
          <button class="cert-filter-btn" data-filter="DReEnter">ReEnter</button>
          <button class="cert-filter-btn" data-filter="DRenew">ReNew</button>
          <button class="cert-filter-btn" data-filter="DReOpen">ReOpen</button>
          <button class="cert-filter-btn" data-filter="DTransfer">Transfer</button>
        </div>
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
             <th data-col="Action">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php
            // Fetch certification masterlist data from DB
            $certRes = $conn->query("SELECT * FROM certification ORDER BY id DESC");
            $rows = [];
            if ($certRes && $certRes->num_rows > 0) {
              // Helper: try several ways to find a matching dateInternment in deceased table
              function findDateInternment($conn, $aptNo, $fullName) {
                $date = '';
                // 1) By nicheID (most reliable)
                if (!empty($aptNo)) {
                  $stmt = $conn->prepare("SELECT dateInternment FROM deceased WHERE nicheID = ? AND dateInternment IS NOT NULL AND dateInternment != '' AND dateInternment != '0000-00-00' LIMIT 1");
                  if ($stmt) {
                    $stmt->bind_param('s', $aptNo);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    if ($res && $res->num_rows > 0) {
                      $r = $res->fetch_assoc();
                      $stmt->close();
                      return $r['dateInternment'];
                    }
                    $stmt->close();
                  }
                }
                // Normalize name for comparisons
                $nameNorm = trim(preg_replace('/\s+/', ' ', (string)$fullName));
                if ($nameNorm === '') return $date;
                $nameLower = mb_strtolower($nameNorm, 'UTF-8');
                // 2) Exact normalized full name (case-insensitive)
                $stmt = $conn->prepare("SELECT dateInternment FROM deceased WHERE LOWER(CONCAT_WS(' ', firstName, COALESCE(middleName,''), lastName, COALESCE(suffix,''))) = ? AND dateInternment IS NOT NULL AND dateInternment != '' AND dateInternment != '0000-00-00' LIMIT 1");
                if ($stmt) {
                  $stmt->bind_param('s', $nameLower);
                  $stmt->execute();
                  $res = $stmt->get_result();
                  if ($res && $res->num_rows > 0) {
                    $r = $res->fetch_assoc();
                    $stmt->close();
                    return $r['dateInternment'];
                  }
                  $stmt->close();
                }
                // 3) LIKE search on normalized full name (handles variations)
                $likeParam = '%' . $nameLower . '%';
                $stmt = $conn->prepare("SELECT dateInternment FROM deceased WHERE LOWER(CONCAT_WS(' ', firstName, COALESCE(middleName,''), lastName, COALESCE(suffix,''))) LIKE ? AND dateInternment IS NOT NULL AND dateInternment != '' AND dateInternment != '0000-00-00' LIMIT 1");
                if ($stmt) {
                  $stmt->bind_param('s', $likeParam);
                  $stmt->execute();
                  $res = $stmt->get_result();
                  if ($res && $res->num_rows > 0) {
                    $r = $res->fetch_assoc();
                    $stmt->close();
                    return $r['dateInternment'];
                  }
                  $stmt->close();
                }
                // 4) Try matching first and last name parts (defensive)
                $parts = preg_split('/\s+/', $nameNorm);
                if (count($parts) >= 2) {
                  $first = mb_strtolower($parts[0], 'UTF-8');
                  $last = mb_strtolower($parts[count($parts)-1], 'UTF-8');
                  $stmt = $conn->prepare("SELECT dateInternment FROM deceased WHERE LOWER(firstName) = ? AND LOWER(lastName) = ? AND dateInternment IS NOT NULL AND dateInternment != '' AND dateInternment != '0000-00-00' LIMIT 1");
                  if ($stmt) {
                    $stmt->bind_param('ss', $first, $last);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    if ($res && $res->num_rows > 0) {
                      $r = $res->fetch_assoc();
                      $stmt->close();
                      return $r['dateInternment'];
                    }
                    $stmt->close();
                  }
                }
                return $date;
              }

              while ($row = $certRes->fetch_assoc()) {
                // Get accurate DateInternment from deceased table by matching AptNo OR NameOfDeceased if needed
                $dateInternment = $row['DateInternment'];
                if (empty($dateInternment) || $dateInternment === '0000-00-00') {
                  $found = findDateInternment($conn, $row['AptNo'] ?? '', $row['NameOfDeceased'] ?? '');
                  if (!empty($found)) $dateInternment = $found;
                }
                // Store all rows for JS filtering
                $rows[] = [
                 'id' => $row['id'],
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
         echo '<td data-col="Action"><a href="Certificate.php?view_cert=' . urlencode($row['id']) . '" target="_blank" class="btn" style="padding:6px 10px;font-size:0.82rem;text-decoration:none;">View Cert</a></td>';
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
        // include new Action column in the column list so custom showColumns can toggle it
        allCols.push("Action");

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

      // Replace previous single-result autofill with multi-match-aware logic
      (function() {
        const deceasedField = document.getElementById('deceasedField');
        const matchesEl = document.getElementById('deceasedMatches');
        const dateDiedField = document.getElementById('dateDiedField');
        const apartmentField = document.getElementById('apartmentField');
        const barangayField = document.getElementById('barangayField');
        const nameField = document.getElementById('nameField');

        function clearMatches() {
          matchesEl.innerHTML = '';
          matchesEl.style.display = 'none';
        }

        function buildFullName(item) {
          return [item.firstName, item.middleName, item.lastName, item.suffix].filter(Boolean).join(' ').replace(/\s+/g,' ');
        }

        // Fetch as user types (debounce lightly)
        let debounceTimer = null;
        deceasedField.addEventListener('input', function() {
          clearTimeout(debounceTimer);
          const q = this.value.trim();
          if (q.length < 2) { clearMatches(); return; }
          debounceTimer = setTimeout(() => {
            fetch('?get_deceased_info=' + encodeURIComponent(q))
              .then(res => res.json())
              .then(data => {
                if (!data || data.length === 0) { clearMatches(); return; }
                // Single match => autofill directly
                if (data.length === 1) {
                  const d = data[0];
                  if (d.dateDied) dateDiedField.value = d.dateDied;
                  if (d.nicheID) apartmentField.value = d.nicheID;
                  if (d.residency) barangayField.value = d.residency;
                  if (d.informantName) nameField.value = d.informantName;
                  clearMatches();
                  return;
                }
                // Multiple matches => show selectable list
                matchesEl.innerHTML = '';
                matchesEl.style.display = 'block';
                const listWrap = document.createElement('div');
                listWrap.style.cssText = 'background:#fff;border:1px solid #d0d7e2;border-radius:8px;padding:6px;max-height:220px;overflow:auto;box-shadow:0 6px 18px rgba(0,0,0,0.06);';
                data.forEach(item => {
                  const full = buildFullName(item);
                  const row = document.createElement('div');
                  row.style.cssText = 'padding:8px 10px;cursor:pointer;border-radius:6px;margin-bottom:6px;';
                  row.innerHTML = '<div style="display:flex;justify-content:space-between;align-items:center;"><div><strong>' + full + '</strong><div style="font-size:0.85rem;color:#6b7280;">' + (item.nicheID || '') + (item.dateDied ? ' · ' + item.dateDied : '') + (item.residency ? ' · ' + item.residency : '') + '</div></div><div style="font-size:0.85rem;color:#506C84;">Select</div></div>';
                  row.addEventListener('click', function() {
                    deceasedField.value = full;
                    if (item.dateDied) dateDiedField.value = item.dateDied;
                    if (item.nicheID) apartmentField.value = item.nicheID;
                    if (item.residency) barangayField.value = item.residency;
                    if (item.informantName) nameField.value = item.informantName;
                    clearMatches();
                  });
                  listWrap.appendChild(row);
                });
                matchesEl.appendChild(listWrap);
              })
              .catch(err => {
                console.error('Deceased fetch error', err);
                clearMatches();
              });
          }, 250);
        });

        // Hide matches when clicking outside
        document.addEventListener('click', function(e) {
          if (!matchesEl.contains(e.target) && e.target !== deceasedField) {
            clearMatches();
          }
        });
      })();

      // Print only the certificate preview
      function printCertificate() {
        const el = document.getElementById('certificatePreview');
        if (!el) { window.print(); return; }

        // Open print window
        const printWin = window.open('', '_blank', 'width=900,height=1100');
        const baseHref = location.origin + location.pathname.substring(0, location.pathname.lastIndexOf('/') + 1);

      const printStyles = `
  @page {
    size: A4;
    margin: 0;
  }

  html, body {
    margin: 0;
    padding: 0;
    height: 100%;
    background: #fff;
  }

  body {
    -webkit-print-color-adjust: exact;
    font-family: "Poppins", "Times New Roman", serif;
    display: flex;
    align-items: flex-start;
    justify-content: center;
  }

  #certificatePreview {
    position: relative;
    width: 210mm;
    min-height: 297mm;
    box-sizing: border-box;
    padding: 16mm 20mm 16mm 20mm; /* LEFT/RIGHT increased to move logos inside */
    background: #fff;
    color: #000;
    overflow: visible;
    font-size: 12px;
    line-height: 1.15;
  }

  #certificatePreview .print-inner {
    display: block;
    overflow: visible;
    box-sizing: border-box;
  }

  /* Logos slightly smaller and centered inside page */
  #certificatePreview img[alt="Padre Garcia Icon"],
  #certificatePreview img[alt="Batangas Seal"] {
    max-height: 120px;
    width: auto;
    margin-left: 4mm;   /* push logos slightly inward */
    margin-right: 4mm;
    display: inline-block;
  }

  /* General image scaling */
  #certificatePreview img {
    max-width: 100%;
    height: auto;
    display: block;
  }

  /* Footer image full-bleed */
  #certificatePreview .cert-footer-print {
    position: absolute;
    left: 0;
    bottom: 0;
    width: 210mm;
    height: auto;
    display: block;
    margin: 0;
    padding: 0;
  }

  button, .print-btn {
    display: none !important;
  }
`;


        // Build print document head
        printWin.document.write(`<!doctype html><html><head><base href="${baseHref}"><meta charset="utf-8"><title>Certificate</title>`);
        printWin.document.write('<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">');
        printWin.document.write('<style>' + printStyles + '</style>');
        printWin.document.write('</head><body>');

        // Clone original certificate and convert image src to absolute URLs
        const clone = el.cloneNode(true);

        // Convert relative img src to absolute and mark footer image
        const imgs = clone.querySelectorAll('img');
        imgs.forEach(img => {
          try {
            const srcAttr = img.getAttribute('src') || '';
            // Resolve absolute URL relative to current page
            const abs = new URL(srcAttr, window.location.href).href;
            img.setAttribute('src', abs);
          } catch (e) {
            // leave as-is if resolution fails
          }
        });

        // Prepare print-inner and detach footer image
        const inner = printWin.document.createElement('div');
        inner.className = 'print-inner';
        // copy innerHTML of clone (we will remove footer image from it)
        inner.innerHTML = clone.innerHTML;

        // Remove footer img from inner and capture it
        let footerNodeHtml = '';
        const parserTemp = document.createElement('div');
        parserTemp.innerHTML = inner.innerHTML;
        const footerImg = parserTemp.querySelector('img[alt="Certificate Footer"], img[src*="CertFooter" i]');
        if (footerImg) {
          footerNodeHtml = footerImg.outerHTML;
          // remove footer from inner html
          footerImg.remove();
        }
        inner.innerHTML = parserTemp.innerHTML;

        // Build container in print window and append inner + footer
        const container = printWin.document.createElement('div');
        container.id = 'certificatePreview';
        container.appendChild(inner);
        if (footerNodeHtml) {
          // ensure footer has class for styling
          const footerWrapper = printWin.document.createElement('div');
          footerWrapper.innerHTML = footerNodeHtml;
          const footerImgEl = footerWrapper.querySelector('img');
          if (footerImgEl) footerImgEl.classList.add('cert-footer-print');
          container.appendChild(footerWrapper.firstChild);
        }

        printWin.document.body.appendChild(container);
        printWin.document.close();
        printWin.focus();

        // Wait for images to load (with timeout) before printing
        const waitForImages = () => {
          const imgsInPrint = Array.from(printWin.document.images || []);
          if (imgsInPrint.length === 0) return Promise.resolve();
          const loaders = imgsInPrint.map(imgEl => new Promise(resolve => {
            if (imgEl.complete && imgEl.naturalHeight !== 0) return resolve();
            const t = setTimeout(() => resolve(), 2000); // safety timeout
            imgEl.addEventListener('load', () => { clearTimeout(t); resolve(); });
            imgEl.addEventListener('error', () => { clearTimeout(t); resolve(); });
          }));
          return Promise.all(loaders);
        };

        waitForImages().then(() => {
          try {
            // final scale check: if content overflows, apply small scale down
            const previewInWin = printWin.document.getElementById('certificatePreview');
            if (previewInWin) {
              // make sure overflow is visible so logos are not clipped
              previewInWin.style.overflow = 'visible';
              previewInWin.querySelectorAll && previewInWin.querySelectorAll('.print-inner').forEach(function(pi){ pi.style.overflow = 'visible'; });
              // scale only if content definitely overflows; use gentle scaling to preserve logo size
              const clientH = previewInWin.clientHeight;
              const scrollH = previewInWin.scrollHeight;
              if (scrollH > clientH + 6) { // add small tolerance
                const scale = Math.max(0.75, clientH / scrollH);
                previewInWin.style.transformOrigin = 'top left';
                previewInWin.style.transform = 'scale(' + scale + ')';
              }
            }
            printWin.print();
          } catch (err) {
            console.error('Print error:', err);
            printWin.print();
          }
        });
      }
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
  // Get accurate admin display name (same logic as preview)
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
  $adminDisplayName = strtoupper($adminDisplayName);
  $masterlistFields = [
    'AptNo', 'NameOfDeceased', 'InformantName', 'InformantAddress', 'AddressOfDeceased',
    'DateDied', 'DateInternment', 'DNew', 'DRenew', 'DTransfer', 'DReOpen', 'DReEnter',
    'DatePaid', 'Payee', 'Amount', 'ORNumber', 'Validity', 'MCNo'
  ];
  // Insert into certification table
  $stmt = $conn->prepare("INSERT INTO certification (AptNo, NameOfDeceased, InformantName, InformantAddress, AddressOfDeceased, DateDied, DateInternment, DNew, DRenew, DTransfer, DReOpen, DReEnter, DatePaid, Payee, Amount, ORNumber, Validity, MCNo, AdminName) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
  $stmt->bind_param(
    'sssssssssssssssssss',
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
    $mc_no,
    $adminDisplayName // <-- Save admin name
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

