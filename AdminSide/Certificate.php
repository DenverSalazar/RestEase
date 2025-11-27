<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    // Redirect to login page if not logged in
    header("Location: ../login.php"); // Adjust the path if needed
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
        SELECT id, firstName, middleName, lastName, suffix, residency, nicheID, dateDied, informantName, dateInternment, validity
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
            // Add fallback for validity if not present in deceased table
            if (!isset($r['validity']) || $r['validity'] === null) {
                // Try to get validity from certification table by deceased name or nicheID
                $validity = '';
                $certStmt = $conn->prepare("SELECT Validity FROM certification WHERE (NameOfDeceased = ? OR AptNo = ?) AND Validity IS NOT NULL AND Validity != '' ORDER BY id DESC LIMIT 1");
                if ($certStmt) {
                    $certStmt->bind_param('ss', $nameNorm, $r['nicheID']);
                    $certStmt->execute();
                    $certRes = $certStmt->get_result();
                    if ($certRes && $certRes->num_rows > 0) {
                        $certRow = $certRes->fetch_assoc();
                        $validity = $certRow['Validity'];
                    }
                    $certStmt->close();
                }
                $r['validity'] = $validity;
            }
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
    // --- FIX: Ensure $cert['Amount'] is numeric before formatting, just like in generation ---
    $amount = '';
    if (isset($cert['Amount']) && $cert['Amount'] !== null && $cert['Amount'] !== '') {
        $amountValue = preg_replace('/[^\d.\-]/', '', $cert['Amount']); // Remove any non-numeric except dot and minus
        if (is_numeric($amountValue)) {
            $amount = '₱' . number_format((float)$amountValue, 2);
        } else {
            $amount = htmlspecialchars($cert['Amount']); // fallback: show as is
        }
    }
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
        /* Load Bernard MT font for CERTIFICATION headings */
        @font-face {
          font-family: 'Bernard MT Std Condensed';
          src: url('../assets/fonts/Bernard MT Std Condensed/Bernard MT Std Condensed.otf') format('opentype');
          font-display: swap;
          font-weight: normal;
          font-style: normal;
        }
        /* Load Rockwell Nova Bold for OFFICE OF THE MUNICIPAL MAYOR */
        @font-face {
          font-family: 'Rockwell Nova';
          src: url('../assets/fonts/rockwell-nova/RockwellNova-Bold.ttf') format('truetype');
          font-weight: 700;
          font-style: normal;
          font-display: swap;
        }
        body { font-family: 'Poppins', sans-serif; margin:0; padding:20px; background:#fff; color:#000; }
        #certificatePreview { position:relative; width: 210mm; margin:0 auto; padding:16mm; box-sizing:border-box; background:#fff; }
        /* --- New: Certificate background image style --- */
        #certificatePreview img[alt="Certificate Background"] {
          position:absolute;
          top:50%;
          left:50%;
          width:70%;
          height:auto;
          transform:translate(-50%,-50%);
          z-index:0;
          pointer-events:none;
          opacity:0.22;
        }
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
        <!-- Certificate background image (behind content, covers area, low opacity) -->
        <img src="../assets/certbg.png" alt="Certificate Background"
             style="position:absolute;top:50%;left:50%;width:70%;height:auto;transform:translate(-50%,-50%);z-index:0;pointer-events:none;opacity:0.22;">
        <div style="position:relative;z-index:1;">
        <div class="header">
          <div class="logos">
            <img src="../assets/Logo garcia.png" alt="Padre Garcia Icon">
            <div class="title">
              Republic of the Philippines<br>
              Province of Batangas<br>
              MUNICIPALITY OF PADRE GARCIA<br>
              <strong style="display:block;font-size:20px;margin-top:6px;font-family:'Rockwell Nova', 'Times New Roman', serif;font-weight:700;">OFFICE OF THE MUNICIPAL MAYOR</strong>
              <hr style="border-top:4px solid #222;margin:12px 0;">
              <!-- Use Bernard font for the certificate title -->
              <strong style="font-size:22px;letter-spacing:10px;font-family:'Bernard MT Std Condensed', 'Times New Roman', serif;">CERTIFICATION</strong>
            </div>
            <img src="../assets/Seal_of_Batangas.png" alt="Batangas Seal">
          </div>
        </div>

        <div style="margin-top:12px;display:flex;justify-content:flex-end; padding-right: 40px;">
          <span class="mc-no" style="background:yellow;padding:2px 10px;font-weight:700;display:inline-block;">
            MC No. <?php echo $mc_no ?: '<span style="color:#e74c3c;">No data</span>'; ?>
          </span>
        </div>

        <div class="body">
          <p>This is to certify that <strong><?php echo htmlspecialchars($informantName); ?></strong> of Barangay <strong><?php echo htmlspecialchars($informantAddress); ?></strong></p>
          <ul style="list-style:none;padding-left:0;">
            <?php
              // Build descriptions only including the deceased name when the action is checked
              $nameHtml = htmlspecialchars($nameOfDeceased);
              $actionBases = [
                'DNew'     => ['text' => 'register the death of', 'tail' => ' and rent CRYPT for five (5) years'],
                'DRenew'   => ['text' => 'renewal of CRYPT of', 'tail' => ''],
                'DTransfer'=> ['text' => 'transfer the remains of', 'tail' => ''],
                'DReOpen'  => ['text' => 're-open the tomb of', 'tail' => ''],
                'DReEnter' => ['text' => 're-enter the remains of', 'tail' => ''],
              ];
              foreach ($actionBases as $col => $parts) {
                $isChecked = (!empty($cert[$col]) && $cert[$col] === '✔');
                // If checked include the name; otherwise show a generic phrase without the name
                $namePart = $isChecked ? ' <strong>' . $nameHtml . '</strong>' : '';
                $desc = $parts['text'] . $namePart . $parts['tail'];
                $checkedAttr = $isChecked ? 'checked' : '';
                echo '<li style="margin-bottom:12px;"><input type="checkbox" ' . $checkedAttr . ' disabled> ' . $desc . '</li>';
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
              Department Head
            </div>
          </div>

          <div style="margin-top:30px;">
            <strong>OR No.:</strong> <?php echo $orNo !== '' ? htmlspecialchars($orNo) : '<span style="color:#e74c3c;">No data</span>'; ?><br>
            <strong>Date Paid:</strong> <?php echo $datePaid !== '' ? htmlspecialchars($datePaid) : '<span style="color:#e74c3c;">No data</span>'; ?><br>
            <strong>Amount:</strong> <?php echo $amount !== '' ? $amount : '<span style="color:#e74c3c;">No data</span>'; ?><br>
            <strong>Renewal:</strong>
            <?php
            // Use the value from the validity field for Renewal, just like in generation
            echo $validity ? strtoupper(date('M-Y', strtotime($validity))) : '<span style="color:#e74c3c;">No data</span>';
            ?>
          </div>

          <div class="cert-footer">
            <img src="../assets/certfooter.png" alt="Certificate Footer" style="max-width:100%;height:auto;">
          </div>
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
  <link rel="icon" type="image/png" href="../assets/re logo blue.png">
  <title>RestEase Certification</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/Certificate.css">
  <link rel="stylesheet" href="../css/sidebar.css">
  <link rel="stylesheet" href="../css/header.css">
  <style>

    

  </style>
</head>

<body>
   <!-- Sidebar -->
   <?php include '../Includes/sidebar.php'; ?>
   <?php include '../Includes/header.php'; ?>

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
            <input type="text" name="deceased" id="deceasedField"
              value="<?php echo isset($_POST['deceased']) ? htmlspecialchars($_POST['deceased']) : ''; ?>"
              style="width:90%;" autocomplete="off" list="deceasedNameSuggestions" required>
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
            <input type="date" name="date_died" id="dateDiedField"
              value="<?php echo isset($_POST['date_died']) ? htmlspecialchars($_POST['date_died']) : ''; ?>"
              style="width:90%;" required>
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px 32px;">
          <div>
            <label>Apartment No.:</label>
            <input type="text" name="apartment" id="apartmentField"
              value="<?php echo isset($_POST['apartment']) ? htmlspecialchars($_POST['apartment']) : ''; ?>"
              required style="width:90%;">
          </div>
          <div>
            <label>Barangay:</label>
            <input type="text" name="barangay" id="barangayField"
              value="<?php echo isset($_POST['barangay']) ? htmlspecialchars($_POST['barangay']) : ''; ?>"
              required style="width:90%;">
          </div>
        </div>
        <!-- Personal Information Section -->
        <div style="font-weight:600;font-size:1.08rem;margin-bottom:8px;margin-top:24px;">Personal Information</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px 32px;">
          <div>
            <label>Payee Name:</label>
            <input type="text" name="name" id="nameField"
              value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
              required style="width:90%;">
            <div id="nameWarning" style="display:none;color:#e74c3c;font-size:0.98rem;margin-top:2px;">Name must not contain numbers or symbols.</div>
          </div>
          <div>
            <label>Date Issued:</label>
            <input type="date" name="date"
              value="<?php echo isset($_POST['date']) ? htmlspecialchars($_POST['date']) : date('Y-m-d'); ?>"
              required style="width:90%;">
          </div>
        </div>
        <hr style="margin:24px 0 18px 0; border:0; border-top:1px solid #ececec;">
        <!-- Certificate Details Section -->
        <div style="font-weight:600;font-size:1.08rem;margin-bottom:8px;">Certificate Details</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px 32px;">
          
        <div>
            <label>Validity:</label>
            <input type="date" name="renewal" id="validityField"
                value="<?php echo isset($_POST['renewal']) ? htmlspecialchars($_POST['renewal']) : ''; ?>"
                style="width:90%;" required>
        </div>
          <div>
            <label>Department Head Name:</label>
            <input type="text" name="admin_name" id="adminNameField"
              value="<?php
                echo isset($_POST['admin_name']) && $_POST['admin_name'] !== ''
                  ? htmlspecialchars($_POST['admin_name'])
                  : 'ENGR. KHRISTINE TAPALLA';
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
              <img src="../assets/Logo garcia.png" alt="Padre Garcia Icon"
                   style="flex:0 0 auto; max-width:80px; max-height:80px; width:auto; height:auto; object-fit:contain; margin-right:16px; align-self:center;">
              <div style="flex:1;text-align:center;">
                <div style="font-family:'Times New Roman', Times, serif;font-size:1.15rem;line-height:1.3;margin-bottom:2px;">
                  Republic of the Philippines<br>
                  Province of Batangas<br>
                  MUNICIPALITY OF PADRE GARCIA
                </div>
                <div style="display:flex;align-items:center;justify-content:center;">
                  <span style="font-family:'Rockwell Nova', 'Times New Roman', serif;font-size:1.83rem;font-weight:700;letter-spacing:1px;margin-bottom:0;white-space:nowrap;">
                    OFFICE OF THE MUNICIPAL MAYOR
                  </span>
                </div>
                <hr style="border:0; border-top:5px solid #222; margin:18px 0 18px 0;">
                <div style="display:flex;align-items:center;justify-content:center;">
                  <!-- Use Bernard font for CERTIFICATION in the on-page preview -->
                  <span style="font-family:'Bernard MT Std Condensed', 'Times New Roman', serif;font-size:1.83rem;font-weight:900;letter-spacing:18px;margin-top:0;margin-bottom:0;white-space:nowrap;">
                    CERTIFICATION
                  </span>
                </div>
              </div>
              <!-- Right Logo -->
              <img src="../assets/Seal_of_Batangas.png" alt="Batangas Seal"
                   style="flex:0 0 auto; max-width:80px; max-height:80px; width:auto; height:auto; object-fit:contain; margin-left:16px; align-self:center;">
            </div>
            <div style="margin-top:20px;">
              <!-- MC No. on the far right between CERTIFICATION and certificate body -->
              <div style="margin-top:12px;display:flex;justify-content:flex-end;">
                <span class="mc-no" style="background:yellow;padding:2px 10px;font-weight:700;display:inline-block;">
                  MC No. <?php echo $mc_no !== '' ? htmlspecialchars($mc_no) : '<span style="color:#e74c3c;">No data</span>'; ?>
                </span>
              </div>
              <p>This is to certify that <strong><?php echo htmlspecialchars($_POST['name']); ?></strong> of Barangay <strong><?php echo htmlspecialchars($_POST['barangay']); ?></strong></p>
              <ul style="list-style:none; padding-left:0;">
                <?php
                  $deceasedHtml = htmlspecialchars($_POST['deceased'] ?? '');
                  $previewBases = [
                    'register_death'   => ['text' => 'register the death of', 'tail' => ' and rent CRYPT for five (5) years'],
                    'renewal_crypt'    => ['text' => 'renewal of CRYPT of', 'tail' => ''],
                    'transfer_remains' => ['text' => 'transfer the remains of', 'tail' => ''],
                    'reopen_tomb'      => ['text' => 're-open the tomb of', 'tail' => ''],
                    'reenter_remains'  => ['text' => 're-enter the remains of', 'tail' => ''],
                  ];
                  $selected = isset($_POST['actions']) ? $_POST['actions'] : [];
                  foreach ($previewBases as $key => $parts) {
                    $isChecked = in_array($key, $selected);
                    $namePart = $isChecked && $deceasedHtml !== '' ? ' <strong>' . $deceasedHtml . '</strong>' : '';
                    $desc = $parts['text'] . $namePart . $parts['tail'];
                    $checkedAttr = $isChecked ? 'checked' : '';
                    echo '<li style="margin-bottom:20px;"><input type="checkbox" ' . $checkedAttr . ' disabled> ' . $desc . '</li>';
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
                  <?php echo htmlspecialchars($admin_name); ?><br> <!-- <-- always use submitted admin_name -->
                  Department Head
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
                <?php
                // Use the value from the validity field for Renewal
                $renewal = isset($_POST['renewal']) ? $_POST['renewal'] : '';
                echo $renewal ? strtoupper(date('M-Y', strtotime($renewal))) : '<span style="color:#e74c3c;">No data</span>';
                ?>
              </div>
              <div style="margin-top:30px; text-align:center;">
                <img src="../assets/certfooter.png" alt="Certificate Footer" style="max-width:100%;height:auto;">
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
        <div id="certFilters" style="position:relative;">
          <!-- Replaced multiple inline buttons with a single toggle + dropdown like Payment Details -->
          <button id="certFilterToggle" class="cert-filter-btn active" type="button" aria-expanded="false" data-filter="all" style="display:flex;align-items:center;gap:8px;">
            <span id="certFilterLabel">all</span>
            <i class="fas fa-caret-down" style="font-size:0.95rem;"></i>
          </button>
          <div id="certFilterDropdown" class="cert-filter-dropdown" style="display:none; position:absolute; right:0; top:calc(100% + 8px); background:#fff; border-radius:8px; box-shadow:0 6px 20px rgba(11,117,168,0.08); padding:8px; z-index:1200; min-width:160px;">
            <button class="cert-filter-item cert-filter-btn" data-filter="all" type="button" style="width:100%;text-align:left;padding:8px 10px;border-radius:6px;border:none;background:transparent;">All</button>
            <button class="cert-filter-item cert-filter-btn" data-filter="DNew" type="button" style="width:100%;text-align:left;padding:8px 10px;border-radius:6px;border:none;background:transparent;">New</button>
            <button class="cert-filter-item cert-filter-btn" data-filter="DReEnter" type="button" style="width:100%;text-align:left;padding:8px 10px;border-radius:6px;border:none;background:transparent;">ReEnter</button>
            <button class="cert-filter-item cert-filter-btn" data-filter="DRenew" type="button" style="width:100%;text-align:left;padding:8px 10px;border-radius:6px;border:none;background:transparent;">ReNew</button>
            <button class="cert-filter-item cert-filter-btn" data-filter="DReOpen" type="button" style="width:100%;text-align:left;padding:8px 10px;border-radius:6px;border:none;background:transparent;">ReOpen</button>
            <button class="cert-filter-item cert-filter-btn" data-filter="DTransfer" type="button" style="width:100%;text-align:left;padding:8px 10px;border-radius:6px;border:none;background:transparent;">Transfer</button>
          </div>
        </div>
      </div>
   
      <!-- Custom Search Bar (like clientsrequest.php, magnifying glass inside, no clear button) -->
      <div style="margin-bottom:18px;display:flex;align-items:center;gap:8px;justify-content:space-between;">
        <div style="display:flex;align-items:center;background:#fff;border-radius:10px;border:1.5px solid #d0d7e2;padding:0 16px;height:40px;box-shadow:0 1px 4px rgba(60,72,88,0.03);min-width:320px;max-width:420px;">
          <i class="fas fa-search" style="color:#b0b0b0;margin-right:8px;font-size:1.1rem;"></i>
          <input type="text" id="certCustomSearch" placeholder="Search Certification Masterlist..." style="border:none;background:transparent;outline:none;font-size:1.05rem;width:100%;color:#222;font-weight:400;padding:0;margin:0;">
        </div>

        <!-- Button set placed to the right of the search bar -->
        <div style="display:flex;gap:10px;align-items:center;">
          <!-- <button id="certImportBtn" style="background:#caf0f8;color:#222;border:none;padding:8px 14px;border-radius:8px;font-weight:500;display:flex;align-items:center;gap:8px;cursor:pointer;">
            <i class="fas fa-file-import"></i> Import Data
          </button> -->
          <button id="certExportBtn" style="background:#0077b6;color:#fff;border:none;padding:8px 14px;border-radius:8px;font-weight:500;display:flex;align-items:center;gap:8px;cursor:pointer;">
            <i class="fas fa-file-excel"></i> Export Data
          </button>
          <button id="certDeleteBtn" type="button" style="background:#e74c3c;color:#fff;border:none;padding:8px 14px;border-radius:8px;font-weight:500;display:flex;align-items:center;gap:8px;cursor:pointer;">
            <i class="fas fa-trash"></i> Delete
          </button>
          
        </div>
      </div>
         <!-- Show entries filter -->
      <div style="margin-bottom:12px;">
        <label for="certEntriesLength" style="font-weight:500;font-size:1rem;">
          Show
          <select id="certEntriesLength" style="margin:0 6px;">
            <option value="10">10</option>
            <option value="25">25</option>
            <option value="50">50</option>
            <option value="100">100</option>
            <option value="-1">All</option>
          </select>
          entries
        </label>
      </div>
      <div class="clients-table-container" style="overflow-x:auto;">
        <form id="certDeleteForm" method="post" style="margin:0;">
        <table class="certificate-masterlist-table" id="certificate-masterlist-table" style="min-width:1100px;">
          <thead>
            <tr>
              <th data-col="AptNo">Apt. No</th>
              <th data-col="NameOfDeceased">Name of Deceased</th>
              <th data-col="AddressOfDeceased">Address of Deceased</th>
              <th data-col="InformantName">Informant Name</th>
              <th data-col="InformantAddress">Informant Address</th>
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
              <th style="width:48px;padding-right:8px;display:none;" id="certDeleteTh">
                <input type="checkbox" id="certSelectAllCheckbox" style="display:inline-block;vertical-align:middle;">
              </th>
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
              // Checkbox cell (hidden by default, toggled by JS)
        echo '<td data-col="AptNo">' . htmlspecialchars($row['AptNo']) . '</td>';
        // Name of Deceased (bold)
        echo '<td data-col="NameOfDeceased"><strong>' . htmlspecialchars($row['NameOfDeceased']) . '</strong></td>';
        // Address of Deceased immediately after Name
        echo '<td data-col="AddressOfDeceased">' . htmlspecialchars($row['AddressOfDeceased']) . '</td>';
        // Informant Name (bold) then Informant Address
        echo '<td data-col="InformantName"><strong>' . htmlspecialchars($row['InformantName']) . '</strong></td>';
        echo '<td data-col="InformantAddress">' . htmlspecialchars($row['InformantAddress']) . '</td>';
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
  echo '<td class="cert-delete-cell" style="padding-right:10px;display:none;"><input type="checkbox" class="cert-delete-checkbox" name="delete_ids[]" value="' . htmlspecialchars($row['id']) . '" style="vertical-align:middle;display:none;"></td>';
              echo '</tr>';
            }
            ?>
          </tbody>
        </table>
        </form>
      </div>
      <div class="dataTables_wrapper"></div>
      <!-- Delete Confirmation Modal -->
      <div id="certDeleteModal" class="modal-overlay" style="display:none;position:fixed;z-index:9999;left:0;top:0;right:0;bottom:0;background:rgba(44,62,80,0.18);align-items:center;justify-content:center;">
        <div class="modal-content" style="background:#fff;border-radius:16px;box-shadow:0 8px 32px rgba(60,60,60,0.18),0 1.5px 6px rgba(0,0,0,0.08);padding:32px 32px 24px 32px;min-width:340px;max-width:95vw;text-align:center;position:relative;">
          <div class="modal-header">
            <i class="fas fa-exclamation-triangle" style="color:#e74c3c;font-size:2rem;margin-bottom:8px;"></i>
            <h2 style="color:#e74c3c;margin:0;font-size:1.3rem;">Confirm Delete</h2>
          </div>
          <div class="modal-body" style="margin:18px 0 24px 0;">
            <p id="certDeleteModalText" style="color:#444;font-size:1.07rem;margin:0;">
              Are you sure you want to delete the selected certificate(s)?<br>
              This action cannot be undone.
            </p>
          </div>
          <div class="modal-footer" style="display:flex;justify-content:center;gap:16px;">
            <button id="certModalDeleteBtn" class="modal-delete-btn" style="background:#e74c3c;color:#fff;border:none;padding:12px 24px;border-radius:8px;cursor:pointer;font-weight:500;font-size:1rem;">Delete</button>
            <button id="certModalCancelBtn" class="modal-cancel-btn" style="background:#95a5a6;color:#fff;border:none;padding:12px 24px;border-radius:8px;cursor:pointer;font-weight:500;font-size:1rem;">Cancel</button>
          </div>
        </div>
      </div>
      <!-- Success Notification -->
      <div id="certSuccessNotification" style="display:none;position:fixed;top:32px;right:32px;z-index:10000;background:#2ecc71;color:#fff;padding:18px 32px;border-radius:8px;box-shadow:0 4px 16px rgba(46,204,113,0.15);font-size:1.1rem;font-weight:500;align-items:center;gap:16px;min-width:220px;">
        <span><i class="fas fa-check-circle" style="margin-right:8px;"></i><span id="certNotificationText">Certificate(s) deleted.</span></span>
        <button id="certCloseNotificationBtn" style="background:none;border:none;color:#fff;font-size:1.2em;cursor:pointer;margin-left:12px;">&times;</button>
      </div>
    <script>
    // Certification Masterlist Delete Logic (adapted from Ledger.php)
    (function() {
      const certDeleteBtn = document.getElementById('certDeleteBtn');
      const certTable = document.getElementById('certificate-masterlist-table');
      const certDeleteCheckboxes = () => certTable.querySelectorAll('.cert-delete-checkbox');
      const certSelectAllCheckbox = document.getElementById('certSelectAllCheckbox');
      const certDeleteTh = document.getElementById('certDeleteTh');
      const certDeleteModal = document.getElementById('certDeleteModal');
      const certModalDeleteBtn = document.getElementById('certModalDeleteBtn');
      const certModalCancelBtn = document.getElementById('certModalCancelBtn');
      const certDeleteForm = document.getElementById('certDeleteForm');
      const certSuccessNotification = document.getElementById('certSuccessNotification');
      const certNotificationText = document.getElementById('certNotificationText');
      const certCloseNotificationBtn = document.getElementById('certCloseNotificationBtn');
      let certDeleteMode = false;

      function setCertDeleteMode(on) {
        certDeleteMode = on;
        const cells = certTable.querySelectorAll('.cert-delete-cell');
        if (on) {
          certDeleteCheckboxes().forEach(cb => {
            cb.style.display = 'inline-block';
          });
          cells.forEach(td => td.style.display = '');
          if (certSelectAllCheckbox) certSelectAllCheckbox.style.display = 'inline-block';
          if (certDeleteTh) certDeleteTh.style.display = '';
        } else {
          certDeleteCheckboxes().forEach(cb => {
            cb.checked = false;
            cb.style.display = 'none';
          });
          cells.forEach(td => td.style.display = 'none');
          if (certSelectAllCheckbox) { certSelectAllCheckbox.checked = false; certSelectAllCheckbox.style.display = 'none'; }
          if (certDeleteTh) certDeleteTh.style.display = 'none';
        }
      }
      setCertDeleteMode(false);

      // Select All logic
      function updateSelectionState() {
        const checkboxes = Array.from(certDeleteCheckboxes());
        const checked = checkboxes.filter(cb => cb.checked);
        if (certSelectAllCheckbox) certSelectAllCheckbox.checked = (checkboxes.length > 0 && checked.length === checkboxes.length);
      }
      if (certSelectAllCheckbox) {
        certSelectAllCheckbox.addEventListener('change', function() {
          const checkboxes = Array.from(certDeleteCheckboxes());
          checkboxes.forEach(cb => cb.checked = certSelectAllCheckbox.checked);
          updateSelectionState();
        });
      }
      certTable.addEventListener('change', function(e) {
        if (e.target.classList.contains('cert-delete-checkbox')) {
          updateSelectionState();
        }
      });

      certDeleteBtn.addEventListener('click', function(e) {
        e.preventDefault();
        if (!certDeleteMode) {
          setCertDeleteMode(true);
          certDeleteBtn.style.background = '#c0392b';
        } else {
          const checked = certTable.querySelectorAll('.cert-delete-checkbox:checked');
          if (checked.length === 0) {
            setCertDeleteMode(false);
            certDeleteBtn.style.background = '#e74c3c';
            return;
          }
          // Update modal text
          const modalText = document.getElementById('certDeleteModalText');
          if (modalText) {
            modalText.innerHTML = `Are you sure you want to delete ${checked.length > 1 ? 'these certificates' : 'this certificate'}?<br>This action cannot be undone.`;
          }
          certDeleteModal.style.display = 'flex';
        }
      });

      certModalCancelBtn.addEventListener('click', function() {
        certDeleteModal.style.display = 'none';
      });

      certModalDeleteBtn.addEventListener('click', function() {
        const checked = certTable.querySelectorAll('.cert-delete-checkbox:checked');
        if (checked.length === 0) return;
        certModalDeleteBtn.disabled = true;
        certModalDeleteBtn.textContent = 'Deleting...';
        certModalCancelBtn.disabled = true;
        // Collect IDs
        const deleteIds = Array.from(checked).map(cb => cb.value);
        const formData = new FormData();
        deleteIds.forEach(id => {
          formData.append('delete_ids[]', id);
        });
        fetch('Certificate.php', {
          method: 'POST',
          body: formData
        })
        .then(response => {
          if (!response.ok) throw new Error('Network response was not ok');
          return response.text();
        })
        .then (data => {
          // Remove rows from table
          checked.forEach(cb => {
            const row = cb.closest('tr');
            if (row) row.remove();
          });
          certNotificationText.textContent = `${deleteIds.length} certificate${deleteIds.length > 1 ? 's' : ''} deleted.`;
          certSuccessNotification.style.display = 'flex';
          setTimeout(() => {
            certSuccessNotification.style.display = 'none';
          }, 3000);
          certDeleteModal.style.display = 'none';
          setCertDeleteMode(false);
          certDeleteBtn.style.background = '#e74c3c';
        })
        .catch(error => {
          console.error('Error:', error);
          alert('An error occurred while deleting. Please try again.');
        })
        .finally(() => {
          certModalDeleteBtn.disabled = false;
          certModalDeleteBtn.textContent = 'Delete';
          certModalCancelBtn.disabled = false;
        });
      });

      certCloseNotificationBtn.addEventListener('click', function() {
        certSuccessNotification.style.display = 'none';
      });
      certDeleteModal.addEventListener('click', function(e) {
        if (e.target === this) {
          this.style.display = 'none';
        }
      });
      window.addEventListener('DOMContentLoaded', function() {
        setCertDeleteMode(false);
        certDeleteBtn.style.background = '#e74c3c';

      });
    })();
    </script>
      </div>
    </div>
    <!-- DataTables JS for Certification Masterlist -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <!-- Add SheetJS for client-side Excel export -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
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
        const apartmentField = document.getElementById('apartmentField');
        const barangayField = document.getElementById('barangayField');
        const dateDiedField = document.getElementById('dateDiedField');
        const dateField = document.querySelector('input[name="date"]');
        const adminNameField = document.getElementById('adminNameField');
        const validityField = document.getElementById('validityField');
        const actions = document.querySelectorAll('input[name="actions[]"]:checked');
        const nameRegex = /^[A-Za-z\s]+$/;

        // Validate Name
        if (!nameRegex.test(nameField.value.trim())) {
          // Remove red border/background
          // nameField.style.border = '2px solid #e74c3c';
          // nameField.style.background = '#fff0f0';
          showWarning('nameWarning', true);
          valid = false;
        } else {
          // nameField.style.border = '';
          // nameField.style.background = '';
          showWarning('nameWarning', false);
        }

        // Simple required field validation
        // Remove markInvalid/markValid visual changes
        // function markInvalid(field) {
        //   field.style.border = '2px solid #e74c3c';
        //   field.style.background = '#fff0f0';
        // }
        // function markValid(field) {
        //   field.style.border = '';
        //   field.style.background = '';
        // }

        if (!deceasedField.value.trim()) { valid = false; }
        if (!dateDiedField.value.trim()) { valid = false; }
        if (!apartmentField.value.trim()) { valid = false; }
        if (!barangayField.value.trim()) { valid = false; }
        if (!nameField.value.trim()) { valid = false; }
        if (!dateField.value.trim()) { valid = false; }
        if (!adminNameField.value.trim()) { valid = false; }
        // Validity field: same validation as DateDied
        if (!validityField.value.trim()) { valid = false; }
        if (actions.length === 0) {
          showWarningToast('Please select at least one Certificate Action.');
          valid = false;
        }

        if (!valid) {
          e.preventDefault();
        }
      });

      // --- Toast for warnings ---
      function showWarningToast(msg) {
        let toast = document.getElementById('warningToast');
        if (!toast) {
          toast = document.createElement('div');
          toast.id = 'warningToast';
          toast.style.cssText = 'position:fixed;top:32px;right:32px;z-index:10001;background:#f7b731;color:#222;padding:14px 24px;border-radius:8px;box-shadow:0 4px 16px rgba(247,183,49,0.18);font-size:1.05rem;font-weight:500;display:flex;align-items:center;gap:10px;min-width:220px;';
          toast.innerHTML = '<span style="font-size:1.3rem;"><i class="fas fa-exclamation-triangle"></i></span><span id="warningToastMsg"></span><button id="warningToastClose" style="background:none;border:none;color:#222;font-size:1.2em;cursor:pointer;margin-left:12px;">&times;</button>';
          document.body.appendChild(toast);
          document.getElementById('warningToastClose').onclick = function() { toast.style.display = 'none'; };
        }
        document.getElementById('warningToastMsg').textContent = msg;
        toast.style.display = 'flex';
        setTimeout(() => { toast.style.display = 'none'; }, 3000);
      }

      // Certificate Masterlist Filter Logic
      (function() {
        const filterButtons = document.querySelectorAll('.cert-filter-btn');
        const table = document.getElementById('certificate-masterlist-table');
        const allCols = [
          "AptNo", "NameOfDeceased", "AddressOfDeceased", "InformantName", "InformantAddress", "DateDied", "DateInternment",
          "DNew", "DRenew", "DTransfer", "DReOpen", "DReEnter", "DatePaid", /*"Payee",*/ "Amount", "ORNumber", "Validity", "MCNo"
        ];
         // include new Action column in the column list so custom showColumns can toggle it
         allCols.push("Action");

        let certMasterlistDT = $('#certificate-masterlist-table').DataTable({
          paging: true,
          searching: true,
          ordering: true,
          info: true,
          lengthChange: false, // hide default DataTables length menu
          pageLength: 10,
          // ensure info & paginate are rendered so we can move them to external wrapper
          dom: 'rtip',
          language: {
            search: "",
            searchPlaceholder: "Search..."
          },
          drawCallback: function() {
            const tableWrapper = $('#certificate-masterlist-table').closest('.clients-table-container');
            const externalWrapper = tableWrapper.next('.dataTables_wrapper');
            const info = $('#certificate-masterlist-table_info').detach();
            const paginate = $('#certificate-masterlist-table_paginate').detach();
            externalWrapper.empty().append(info).append(paginate);
          }
        });

        // Show entries filter logic
        document.getElementById('certEntriesLength').addEventListener('change', function() {
          var val = parseInt(this.value, 10);
          certMasterlistDT.page.len(val === -1 ? certMasterlistDT.data().length : val).draw();
        });

        // Custom search bar logic
        $('#certCustomSearch').on('keyup change', function() {
          certMasterlistDT.search(this.value).draw();
        });

        function showColumns(colsToShow) {
          // Show/hide headers
          table.querySelectorAll('th').forEach(th => {
            const col = th.getAttribute('data-col');
            // Always hide Payee column
            if (col === "Payee") {
              th.style.display = 'none';
            } else {
              th.style.display = colsToShow.includes(col) ? '' : 'none';
            }
          });
          // Show/hide cells
          table.querySelectorAll('tbody tr').forEach(tr => {
            tr.querySelectorAll('td').forEach(td => {
              const col = td.getAttribute('data-col');
              // Always hide Payee column
              if (col === "Payee") {
                td.style.display = 'none';
              } else {
                td.style.display = colsToShow.includes(col) ? '' : 'none';
              }
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
              // include AddressOfDeceased next to NameOfDeceased in filtered view
              showColumns(['AptNo', 'NameOfDeceased', 'AddressOfDeceased', filter]);
              filterRowsByAction(filter);
            }
          });
        });

        // Set default to All
        document.querySelector('.cert-filter-btn[data-filter="all"]').click();
        // Replace old inline buttons handling with dropdown-driven filter
        (function() {
          const toggle = document.getElementById('certFilterToggle');
          const label = document.getElementById('certFilterLabel');
          const dropdown = document.getElementById('certFilterDropdown');
          const items = Array.from(document.querySelectorAll('#certFilterDropdown .cert-filter-item'));

          function applyCertFilter(token) {
            // Update active state inside dropdown
            items.forEach(it => it.classList.toggle('active', it.getAttribute('data-filter') === token));
            // Update toggle label
            const matching = items.find(i => i.getAttribute('data-filter') === token);
            label.textContent = matching ? matching.textContent : (token === 'all' ? 'all' : token);
            // Apply the same show/hide column logic used earlier
            if (token === 'all') {
              showColumns(allCols);
              filterRowsByAction(null);
            } else {
              // include AddressOfDeceased next to NameOfDeceased in filtered view
              showColumns(['AptNo', 'NameOfDeceased', 'AddressOfDeceased', token]);
              filterRowsByAction(token);
            }
            // close dropdown
            if (dropdown) { dropdown.style.display = 'none'; toggle.setAttribute('aria-expanded', 'false'); }
          }

          // Toggle dropdown visibility
          if (toggle) {
            toggle.addEventListener('click', function(e) {
              e.stopPropagation();
              const open = dropdown.style.display === 'block';
              dropdown.style.display = open ? 'none' : 'block';
              this.setAttribute('aria-expanded', open ? 'false': 'true');
            });
          }

          // Close on outside click
          document.addEventListener('click', function(e) {
            if (!dropdown.contains(e.target) && e.target !== toggle) {
              dropdown.style.display = 'none';
              if (toggle) toggle.setAttribute('aria-expanded', 'false');
            }
          });

          // Wire dropdown items
          items.forEach(btn => {
            btn.addEventListener('click', function(e) {
              const f = this.getAttribute('data-filter') || 'all';
              applyCertFilter(f);
            });
          });

          // Initialize to 'all'
          applyCertFilter('all');
        })();

        // === Export button handler (uses SheetJS) ===
        // Exports currently visible/filtered columns and rows as .xlsx
        const exportBtn = document.getElementById('certExportBtn');
        if (exportBtn) {
          exportBtn.addEventListener('click', function() {
            try {
              // Build headers from visible <th> elements
              const headerThs = Array.from(table.querySelectorAll('thead th')).filter(th => {
                // include columns that are currently displayed (not hidden by showColumns)
                return th.style.display !== 'none';
              });
              const headers = headerThs.map(th => th.textContent.trim());

              // Get rows from DataTables (only rows that match current search/paging state)
              const rowsNodes = certMasterlistDT.rows({ search: 'applied' }).nodes().toArray();

              const data = [];
              data.push(headers);

              rowsNodes.forEach(tr => {
                // Collect visible td cells in the same order as headers
                const tds = Array.from(tr.querySelectorAll('td')).filter(td => td.style.display !== 'none');
                const row = tds.map(td => {
                  // remove extra whitespace and convert HTML to text
                  let txt = td.innerText || td.textContent || '';
                  return txt.trim();
                });
                data.push(row);
              });

              // Convert AoA to worksheet and create workbook
              const ws = XLSX.utils.aoa_to_sheet(data);
              const wb = XLSX.utils.book_new();
              XLSX.utils.book_append_sheet(wb, ws, 'Certification');

              // Generate filename with date
              const now = new Date();
              const filename = 'certification_masterlist_' + now.toISOString().slice(0,10) + '.xlsx';

              // Trigger download
              XLSX.writeFile(wb, filename);
            } catch (err) {
              console.error('Export error:', err);
              alert('Export failed. Check console for details.');
            }
          });
        }
        // === end export handler ===

      })();
      // Autofill fields when deceased is selected
      document.getElementById('deceasedField').addEventListener('change', function() {
        const name = this.value;
        if (!name) return;
        fetch('?get_deceased_info=' + encodeURIComponent(name))
          .then(res => res.json())
          .then(data => {
            if (!data) return;
            // If multiple, pick first for autofill
            const d = Array.isArray(data) ? data[0] : data;
            if (d.dateDied) document.getElementById('dateDiedField').value = d.dateDied;
            if (d.nicheID) document.getElementById('apartmentField').value = d.nicheID;
            if (d.residency) document.getElementById('barangayField').value = d.residency;
            if (d.informantName) document.getElementById('nameField').value = d.informantName;
            if (d.dateInternment && document.getElementById('dateInternmentField')) document.getElementById('dateInternmentField').value = d.dateInternment;
            if (d.validity && document.getElementById('validityField')) document.getElementById('validityField').value = d.validity;
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
        const validityField = document.getElementById('validityField');

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
                  if (d.dateInternment && document.getElementById('dateInternmentField')) document.getElementById('dateInternmentField').value = d.dateInternment;
                  if (d.validity && validityField) validityField.value = d.validity;
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
                    if (item.dateInternment && document.getElementById('dateInternmentField')) document.getElementById('dateInternmentField').value = item.dateInternment;
                    if (item.validity && validityField) validityField.value = item.validity;
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
    print-color-adjust: exact;
    -webkit-print-color-adjust: exact;
    font-family: "Bernard MT Std Condensed", "Poppins", "Times New Roman", serif;
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

  // New: determine DateInternment
  $dateInternment = null;
  // 1) prefer explicitly submitted date_internment
  if (!empty($_POST['date_internment'])) {
      // basic validation/normalize
      $d = date_create($_POST['date_internment']);
      if ($d !== false) $dateInternment = $d->format('Y-m-d');
  }
  // 2) fallback: try to find from deceased table by AptNo
  if (empty($dateInternment) && !empty($aptNo)) {
      $stmt = $conn->prepare("SELECT dateInternment FROM deceased WHERE nicheID = ? AND dateInternment IS NOT NULL AND dateInternment != '' AND dateInternment != '0000-00-00' LIMIT 1");
      if ($stmt) {
          $stmt->bind_param('s', $aptNo);
          $stmt->execute();
          $r = $stmt->get_result()->fetch_assoc();
          if ($r && !empty($r['dateInternment'])) $dateInternment = $r['dateInternment'];
          $stmt->close();
      }
  }
  // 3) fallback: exact normalized full name match in deceased
  if (empty($dateInternment) && !empty($nameOfDeceased)) {
      $nameNorm = mb_strtolower(trim(preg_replace('/\s+/', ' ', $nameOfDeceased)), 'UTF-8');
      $stmt = $conn->prepare("SELECT dateInternment FROM deceased WHERE LOWER(CONCAT_WS(' ', firstName, COALESCE(middleName,''), lastName, COALESCE(suffix,''))) = ? AND dateInternment IS NOT NULL AND dateInternment != '' AND dateInternment != '0000-00-00' LIMIT 1");
      if ($stmt) {
          $stmt->bind_param('s', $nameNorm);
          $stmt->execute();
          $r = $stmt->get_result()->fetch_assoc();
          if ($r && !empty($r['dateInternment'])) $dateInternment = $r['dateInternment'];
          $stmt->close();
      }
  }
  // 4) fallback: LIKE normalized name
  if (empty($dateInternment) && !empty($nameOfDeceased)) {
      $like = '%' . mb_strtolower(trim(preg_replace('/\s+/', ' ', $nameOfDeceased)), 'UTF-8') . '%';
      $stmt = $conn->prepare("SELECT dateInternment FROM deceased WHERE LOWER(CONCAT_WS(' ', firstName, COALESCE(middleName,''), lastName, COALESCE(suffix,''))) LIKE ? AND dateInternment IS NOT NULL AND dateInternment != '' AND dateInternment != '0000-00-00' LIMIT 1");
      if ($stmt) {
          $stmt->bind_param('s', $like);
          $stmt->execute();
          $r = $stmt->get_result()->fetch_assoc();
          if ($r && !empty($r['dateInternment'])) $dateInternment = $r['dateInternment'];
          $stmt->close();
      }
  }
  // 5) fallback: try first+last parts
  if (empty($dateInternment) && !empty($nameOfDeceased)) {
      $parts = preg_split('/\s+/', trim($nameOfDeceased));
      if (count($parts) >= 2) {
          $first = mb_strtolower($parts[0], 'UTF-8');
          $last = mb_strtolower($parts[count($parts)-1], 'UTF-8');
          $stmt = $conn->prepare("SELECT dateInternment FROM deceased WHERE LOWER(firstName) = ? AND LOWER(lastName) = ? AND dateInternment IS NOT NULL AND dateInternment != '' AND dateInternment != '0000-00-00' LIMIT 1");
          if ($stmt) {
              $stmt->bind_param('ss', $first, $last);
              $stmt->execute();
              $r = $stmt->get_result()->fetch_assoc();
              if ($r && !empty($r['dateInternment'])) $dateInternment = $r['dateInternment'];
              $stmt->close();
          }
      }
  }

  // Ensure $dateInternment is a string acceptable by DB (NULL or 'YYYY-MM-DD')
  if (empty($dateInternment)) $dateInternment = null;

  // Map posted checkbox actions[] to certification columns (store '✔' when checked, else empty)
  $postedActions = isset($_POST['actions']) && is_array($_POST['actions']) ? $_POST['actions'] : [];
  // default empty
  $dNew = $dRenew = $dTransfer = $dReOpen = $dReEnter = '';
  if (in_array('register_death', $postedActions))  $dNew     = '✔';
  if (in_array('renewal_crypt', $postedActions))   $dRenew   = '✔';
  if (in_array('transfer_remains', $postedActions))$dTransfer= '✔';
  if (in_array('reopen_tomb', $postedActions))    $dReOpen  = '✔';
  if (in_array('reenter_remains', $postedActions)) $dReEnter = '✔';

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

  // Insert into certification table (reuse current prepared insert; pass $admin_name from form, not $adminDisplayName)
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
    $admin_name // <-- use submitted admin_name, not $adminDisplayName
  );
  $stmt->execute();
  $stmt->close();

  // After successful insert, show compact top-right success notification (replaces previous centered modal)
  ?>
  <div id="certSuccessPopup" style="display:flex;position:fixed;top:32px;right:32px;z-index:10000;background:#fff;border-radius:12px;box-shadow:0 4px 16px rgba(0,0,0,0.12);padding:14px 18px;min-width:260px;align-items:center;gap:12px;font-family:'Poppins',sans-serif;">
    <span style="color:#27ae60;font-size:1.6rem;line-height:1;"><i class="fas fa-check-circle"></i></span>
    <div style="display:flex;flex-direction:column;flex:1;min-width:0;">
      <div style="font-weight:600;color:#222;font-size:1.02rem;line-height:1;">Success</div>
      <div id="certSuccessPopupMessage" style="color:#555;font-size:0.95rem;line-height:1.2;">Certificate has been submitted successfully.</div>
    </div>
    <button id="certSuccessPopupClose" style="background:none;border:none;color:#888;font-size:1.2rem;cursor:pointer;padding:6px 8px;border-radius:6px;">&times;</button>
  </div>
  <script>
    (function() {
      var popup = document.getElementById('certSuccessPopup');
      var closeBtn = document.getElementById('certSuccessPopupClose');
      var timer = null;
      function hideAndRedirect() {
        if (!popup) return;
        popup.style.display = 'none';
        try { window.location.href = 'Certificate.php'; } catch (e) { window.location.reload(); }
      }
      // Auto-hide after 3s and redirect
      timer = setTimeout(hideAndRedirect, 3000);
      if (closeBtn) {
        closeBtn.addEventListener('click', function(e) {
          e.preventDefault();
          if (timer) clearTimeout(timer);
          hideAndRedirect();
        });
      }
      // Optional: clicking outside will also dismiss & redirect (one-time listener)
      document.addEventListener('click', function(ev) {
        if (!popup) return;
        if (!popup.contains(ev.target)) {
          if (timer) clearTimeout(timer);
          hideAndRedirect();
        }
      }, { once: true });
    })();
  </script>
  <?php
  exit;
}
?>

<!-- Handle certificate deletion via AJAX (server-side) -->
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_ids']) && is_array($_POST['delete_ids'])) {
  include_once '../Includes/db.php';
  if ($conn->connect_error) { http_response_code(500); echo 'DB error'; exit; }
  $deleteIds = array_map('intval', $_POST['delete_ids']);
  if (count($deleteIds) > 0) {
    // build placeholders safely
    $placeholders = implode(',', array_fill(0, count($deleteIds), '?'));
    $types = str_repeat('i', count($deleteIds));
    $stmt = $conn->prepare("DELETE FROM certification WHERE id IN ($placeholders)");
    $stmt->bind_param($types, ...$deleteIds);
    $stmt->execute();
    $stmt->close();
  }
  $conn->close();
  // return simple OK for AJAX
  http_response_code(200);
  echo 'OK';
  exit;
}
?>
<!-- Insert: Certification Import Modal (design copied from Ledger import modal) -->
<!-- place this block somewhere inside the page body (e.g. just before </main>) -->
<div id="certExcelModal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(44,62,80,0.25); z-index:1100; align-items:center; justify-content:center;">
  <div class="import-modal-content">
    <button type="button" id="closeCertModal" class="modal-close-btn">&times;</button>
    <div class="modal-header">
      <i class="fas fa-file-excel" style="color:#27ae60; font-size:2.5rem; margin-bottom:12px;"></i>
      <h3>Import Certification Masterlist</h3>
      <p>Upload CSV/XLS/XLSX to import multiple </p>
      <p>certification records into the masterlist </p>
    </div>
    <form action="ImportCertExcel.php" method="post" enctype="multipart/form-data" class="import-form">
      <div class="file-upload-area">
        <i class="fas fa-cloud-upload-alt"></i>
        <input type="file" name="excel_file" accept=".xls,.xlsx,.csv" required id="certFileInput">
        <label for="certFileInput" class="file-upload-label">
          <span class="upload-text">Choose File</span>
          <span class="file-name">No file selected</span>
        </label>
      </div>
      <div class="file-info">
        <i class="fas fa-info-circle"></i>
        Supported formats: CSV, XLS, XLSX files
      </div>
      <div class="modal-actions">
        <button type="button" id="cancelCertBtn" class="btn-cancel">Cancel</button>
        <button type="submit" class="btn-upload">
          <i class="fas fa-upload"></i>
          Upload File
        </button>
      </div>
    </form>
  </div>
</div>

<script>
  // Certification Import modal logic (mirrors Ledger modal behavior)
  (function(){
    // existing Import button in the Certification masterlist uses id="certImportBtn"
    var importBtn = document.getElementById('certImportBtn');
    var modal = document.getElementById('certExcelModal');
    var closeBtn = document.getElementById('closeCertModal');
    var cancelBtn = document.getElementById('cancelCertBtn');
    var fileInput = document.getElementById('certFileInput');

    if (importBtn) importBtn.addEventListener('click', function() { if (modal) modal.style.display = 'flex'; });
    if (closeBtn) closeBtn.addEventListener('click', function() { if (modal) modal.style.display = 'none'; });
    if (cancelBtn) cancelBtn.addEventListener('click', function() { if (modal) modal.style.display = 'none'; });
    if (modal) modal.addEventListener('click', function(e) { if (e.target === modal) modal.style.display = 'none'; });

    if (fileInput) fileInput.addEventListener('change', function(){
      var fileName = this.files && this.files[0] ? this.files[0].name : 'No file selected';
      var el = document.querySelector('#certExcelModal .file-name');
      if (el) el.textContent = fileName;
    });
  })();
</script>


