<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}
?>
<?php
include_once '../Includes/db.php';
$ledgerEntry = null;
$entry_id = null;

// --- Suggestion Data for Payee Name ---
$payeeSuggestions = [];
$userResult = $conn->query("SELECT first_name, last_name FROM users");
if ($userResult && $userResult->num_rows > 0) {
    while ($row = $userResult->fetch_assoc()) {
        $fullName = trim($row['first_name'] . ' ' . $row['last_name']);
        if ($fullName !== '') $payeeSuggestions[$fullName] = true;
    }
}
$informantResult = $conn->query("SELECT DISTINCT informantName FROM deceased WHERE informantName IS NOT NULL AND informantName != ''");
if ($informantResult && $informantResult->num_rows > 0) {
    while ($row = $informantResult->fetch_assoc()) {
        $name = trim($row['informantName']);
        if ($name !== '') $payeeSuggestions[$name] = true;
    }
}
$payeeSuggestions = array_keys($payeeSuggestions);

// --- Mapping informant names to array of nicheIDs for autofill ---
$informantNicheMap = [];
$nicheResult = $conn->query("SELECT informantName, nicheID FROM deceased WHERE informantName IS NOT NULL AND informantName != '' AND nicheID IS NOT NULL AND nicheID != ''");
if ($nicheResult && $nicheResult->num_rows > 0) {
    while ($row = $nicheResult->fetch_assoc()) {
        $name = trim($row['informantName']);
        $nicheID = trim($row['nicheID']);
        if ($name !== '' && $nicheID !== '') {
            if (!isset($informantNicheMap[$name])) $informantNicheMap[$name] = [];
            if (!in_array($nicheID, $informantNicheMap[$name])) $informantNicheMap[$name][] = $nicheID;
        }
    }
}

// --- NEW: Mapping informant/payee names to array of deceased names (from assessments/done-assessment) ---
$informantDeceasedMap = [];
$deceasedResult = $conn->query("SELECT informant_name, deceased_name FROM assessment WHERE informant_name IS NOT NULL AND informant_name != '' AND deceased_name IS NOT NULL AND deceased_name != ''");
if ($deceasedResult && $deceasedResult->num_rows > 0) {
    while ($row = $deceasedResult->fetch_assoc()) {
        $name = trim($row['informant_name']);
        $deceasedName = trim($row['deceased_name']);
        if ($name !== '' && $deceasedName !== '') {
            if (!isset($informantDeceasedMap[$name])) $informantDeceasedMap[$name] = [];
            if (!in_array($deceasedName, $informantDeceasedMap[$name])) $informantDeceasedMap[$name][] = $deceasedName;
        }
    }
}

// --- NEW: Mapping nicheID -> deceased names for exact apartment lookup ---
$nicheDeceasedMap = [];
$nicheDeceasedResult = $conn->query("
  SELECT nicheID, firstName, middleName, lastName, suffix
  FROM deceased
  WHERE nicheID IS NOT NULL AND nicheID != ''
    AND (firstName IS NOT NULL OR lastName IS NOT NULL)
 ");
if ($nicheDeceasedResult && $nicheDeceasedResult->num_rows > 0) {
    while ($row = $nicheDeceasedResult->fetch_assoc()) {
        $nid = trim($row['nicheID']);
        $parts = [];
        if (!empty($row['firstName'])) $parts[] = trim($row['firstName']);
        if (!empty($row['middleName'])) $parts[] = trim($row['middleName']);
        if (!empty($row['lastName'])) $parts[] = trim($row['lastName']);
        if (!empty($row['suffix'])) $parts[] = trim($row['suffix']);
        $dname = trim(implode(' ', $parts));
        if ($nid !== '' && $dname !== '') {
            if (!isset($nicheDeceasedMap[$nid])) $nicheDeceasedMap[$nid] = [];
            if (!in_array($dname, $nicheDeceasedMap[$nid])) $nicheDeceasedMap[$nid][] = $dname;
        }
    }
}

// --- Mapping informant/payee names to validity (from deceased table) ---
$informantValidityMap = [];
$validityResult = $conn->query("SELECT informantName, validity FROM deceased WHERE informantName IS NOT NULL AND informantName != '' AND validity IS NOT NULL AND validity != ''");
if ($validityResult && $validityResult->num_rows > 0) {
    while ($row = $validityResult->fetch_assoc()) {
        $name = trim($row['informantName']);
        $validity = trim($row['validity']);
        if ($name !== '' && $validity !== '') {
            // If multiple, keep the latest validity (by string compare, assuming date format)
            if (!isset($informantValidityMap[$name]) || $validity > $informantValidityMap[$name]) {
                $informantValidityMap[$name] = $validity;
            }
        }
    }
}

// Handle Ledger Form Submission (Insert or Update)
$showLedgerSuccessModal = false;
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['ApartmentNo']) && isset($_POST['Payee']) && isset($_POST['Amount']) &&
    trim($_POST['Payee']) !== '' &&
    trim(str_replace([',', '₱', ' '], '', $_POST['Amount'])) !== ''
) {
    $id = isset($_POST['id']) && $_POST['id'] !== '' ? intval($_POST['id']) : null;
    $apartmentNo = $_POST['ApartmentNo'];
    $payee = $_POST['Payee'];
    $deceasedName = isset($_POST['DeceasedName']) ? trim($_POST['DeceasedName']) : null;
    $amount = str_replace([',', '₱', ' '], '', $_POST['Amount']);
    $orNumber = $_POST['ORNumber'];
    $mcNo = $_POST['MCNo'];
    $validity = $_POST['Validity'];
    $description = $_POST['Description'];
    $datePaid = isset($_POST['DatePaid']) ? $_POST['DatePaid'] : null;

    if ($id) {
        // Update existing (include DeceasedName)
        $stmt = $conn->prepare("UPDATE ledger SET ApartmentNo=?, Payee=?, DeceasedName=?, Amount=?, ORNumber=?, MCNo=?, Validity=?, Description=?, DatePaid=? WHERE id=?");
        $stmt->bind_param('sssdsssssi', $apartmentNo, $payee, $deceasedName, $amount, $orNumber, $mcNo, $validity, $description, $datePaid, $id);
        $stmt->execute();
        $stmt->close();
        // --- Also update validity in deceased table if ApartmentNo matches nicheID and it's a Renewal ---
        if ($description === 'Renewal') {
            $stmt2 = $conn->prepare("UPDATE deceased SET validity = ? WHERE nicheID = ?");
            $stmt2->bind_param('ss', $validity, $apartmentNo);
            $stmt2->execute();
            $stmt2->close();
        }
    } else {
        // Insert new (include DeceasedName)
        $stmt = $conn->prepare("INSERT INTO ledger (ApartmentNo, Payee, DeceasedName, Amount, ORNumber, MCNo, Validity, Description, DatePaid) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('sssdsssss', $apartmentNo, $payee, $deceasedName, $amount, $orNumber, $mcNo, $validity, $description, $datePaid);
        $stmt->execute();
        $stmt->close();
        // --- Also update validity in deceased table if ApartmentNo matches nicheID and it's a Renewal ---
        if ($description === 'Renewal') {
            $stmt2 = $conn->prepare("UPDATE deceased SET validity = ? WHERE nicheID = ?");
            $stmt2->bind_param('ss', $validity, $apartmentNo);
            $stmt2->execute();
            $stmt2->close();
        }
    }
    $showLedgerSuccessModal = true;
    // Do NOT redirect or echo JS alert here
    // exit; <-- REMOVE THIS LINE
} 
function generateUniqueORNumber($conn) {
  $count = 0;
  do {
    $orNumber = str_pad(mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT);
    $stmt = $conn->prepare("SELECT COUNT(*) FROM ledger WHERE ORNumber = ?");
    $stmt->bind_param('s', $orNumber);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
  } while ($count > 0);
  return $orNumber;
}

function generateUniqueMCNumber($conn) {
  $year = date('Y');
  // Find the highest MCNo for the current year
  $result = $conn->query("SELECT MCNo FROM ledger WHERE MCNo LIKE '{$year}-%' ORDER BY MCNo DESC LIMIT 1");
  $nextNum = 1;
  if ($result && $row = $result->fetch_assoc()) {
    // Extract the numeric part and increment
    $parts = explode('-', $row['MCNo']);
    if (count($parts) == 2 && is_numeric($parts[1])) {
      $nextNum = intval($parts[1]) + 1;
    }
  }
  return sprintf('%s-%03d', $year, $nextNum);
}

if ($entry_id && !$ledgerEntry) {
    echo "Entry not found.";
    exit;
}
?>
<?php
$apartment = isset($_GET['apartment']) ? htmlspecialchars($_GET['apartment']) : '';
$informant = isset($_GET['informant']) ? htmlspecialchars($_GET['informant']) : '';
$validity = ''; // Always empty at first
$orNumber = '';
$mcNumber = '';
// Do NOT auto-generate ORNumber anymore so it can be entered manually.
// Keep MCNo generation where relevant.
if (($apartment || $informant) && empty($ledgerEntry['MCNo'])) {
  $mcNumber = generateUniqueMCNumber($conn);
}
// For walk-in clients (no URL parameters), generate MCNo only; leave ORNumber empty for manual entry.
if (!$apartment && !$informant && !$ledgerEntry) {
  $mcNumber = generateUniqueMCNumber($conn);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>RestEase Ledger</title>
  <link rel="icon" type="image/png" href="../assets/re logo blue.png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/Ledger.css">
  <link rel="stylesheet" href="../css/sidebar.css">
  <link rel="stylesheet" href="../css/header.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
  <style>
	/* Ledger filter button boxed style + active underline (matches Certification Masterlist) */
	#ledgerFilters { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
	.ledger-filter-btn {
	  display:inline-block;
	  border:1px solid #e6e9ec;
	  background:#fff;
	  color:#222;
	  padding:6px 10px;
	  border-radius:8px;
	  font-weight:400; /* changed to normal */
	  cursor:pointer;
	  transition: color 0.12s ease, border-color 0.12s ease, background 0.12s ease, box-shadow 0.12s;
	  box-sizing: border-box;
	}
	.ledger-filter-btn:hover {
	  color:#0b75a8;
	  box-shadow: 0 1px 4px rgba(11,117,168,0.06);
	}
	.ledger-filter-btn.active {
	  color:#0077b6;
	  border-color:#0077b6 !important;
	  font-weight:400; /* ensure not bold when active */
	  box-shadow: 0 4px 12px rgba(0,119,182,0.06);
	  background: #fff;
	}
	/* Dropdown styles for filter */
	.ledger-filter-dropdown {
	  display:none;
	  position:absolute;
	  right:0;
	  top:calc(100% + 8px);
	  background:#fff;
	  border-radius:8px;
	  box-shadow:0 6px 20px rgba(11,117,168,0.08);
	  padding:8px;
	  z-index:1200;
	  min-width:160px;
	}
	.ledger-filter-item {
	  width:100%;
	  text-align:left;
	  padding:8px 10px;
	  border-radius:6px;
	  border:none;
	  background:transparent;
	  cursor:pointer;
	  transition: background 0.12s ease;
	  font-weight:400; /* ensure dropdown items not bold */
	}
	.ledger-filter-item:hover {
	  background:#f1f9ff;
	}
</style>
</head>
<body>
  <!-- Sidebar -->
  <?php include '../Includes/sidebar.php'; ?>
   <?php include '../Includes/header.php'; ?>

  <!-- Main Content -->
  <main class="main-content">
    <!-- Page Header -->
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
      <div>
        <h1 style="font-size:2rem;font-weight:700;margin-bottom:0;">Ledger</h1>
        <p style="font-size:1.04rem;color:#6b7280;">Fill up the ledger information</p>
      </div>
    </div>
    <!-- Import Excel Modal for Ledger -->
    <div id="ledgerExcelModal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(44,62,80,0.25); z-index:1000; align-items:center; justify-content:center;">
      <div class="import-modal-content">
        <button type="button" id="closeLedgerModal" class="modal-close-btn">&times;</button>
        <div class="modal-header">
          <i class="fas fa-file-excel" style="color:#27ae60; font-size:2.5rem; margin-bottom:12px;"></i>
          <h3>Import Excel File</h3>
          <p>Upload your Excel file to import multiple ledger records at once</p>
        </div>
        <form action="ImportLedgerExcel.php" method="post" enctype="multipart/form-data" class="import-form">
          <div class="file-upload-area">
            <i class="fas fa-cloud-upload-alt"></i>
            <input type="file" name="excel_file" accept=".xls,.xlsx,.csv" required id="ledgerFileInput">
            <label for="ledgerFileInput" class="file-upload-label">
              <span class="upload-text">Choose File</span>
              <span class="file-name">No file selected</span>
            </label>
          </div>
          <div class="file-info">
            <i class="fas fa-info-circle"></i>
            Supported formats: CSV, XLS, XLSX files
          </div>
          <div class="modal-actions">
            <button type="button" id="cancelLedgerBtn" class="btn-cancel">Cancel</button>
            <button type="submit" class="btn-upload">
              <i class="fas fa-upload"></i>
              Upload File
            </button>
          </div>
        </form>
      </div>
    </div>
    <script>
      // Import Excel Modal logic for Ledger - run after DOMContentLoaded so moved buttons are available
      document.addEventListener('DOMContentLoaded', function() {
        var importBtn = document.getElementById('importExcelBtn');
        var closeBtn = document.getElementById('closeLedgerModal');
        var cancelBtn = document.getElementById('cancelLedgerBtn');
        var modal = document.getElementById('ledgerExcelModal');
        var fileInput = document.getElementById('ledgerFileInput');

        if (importBtn) importBtn.onclick = function() { if (modal) modal.style.display = 'flex'; };
        if (closeBtn) closeBtn.onclick = function() { if (modal) modal.style.display = 'none'; };
        if (cancelBtn) cancelBtn.onclick = function() { if (modal) modal.style.display = 'none'; };
        if (modal) modal.onclick = function(e) { if (e.target === this) this.style.display = 'none'; };
        if (fileInput) fileInput.onchange = function() {
          var fileName = this.files[0] ? this.files[0].name : 'No file selected';
          var el = document.querySelector('#ledgerExcelModal .file-name');
          if (el) el.textContent = fileName;
        };
      });
    </script>
    <!-- Tabs -->
    <div style="border-bottom:1px solid #e0e0e0;margin-bottom:8px;">
      <div style="display:flex;gap:32px;align-items:center;">
        <button id="ledgerTabBtn" class="tab active">Ledger Information</button>
        <button id="paymentTabBtn" class="tab">Payment Details</button>
      </div>
    </div>
    <!-- Ledger Information Section (now comes first, visible by default) -->
    <div id="ledgerInfoSection" class="card" style="width: 100%; max-width: 100%; background: #fff; border-radius: 16px; box-shadow: 0 2px 8px rgba(44,62,80,0.08); padding: 32px 32px 32px 32px; box-sizing: border-box;">
      <div style="font-size:1.25rem;font-weight:600;margin-bottom:24px;letter-spacing:0.5px;">Ledger Information</div>
      <form id="ledgerForm" method="post" action="" enctype="multipart/form-data" autocomplete="off" style="width:100%;">
        <!-- Section: Basic Information -->
        <div style="font-weight:600;font-size:1.08rem;margin-bottom:18px;">Basic Information</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px 32px;margin-bottom:18px;">
          <!-- Payee Name (required) -->
          <div style="display:flex;flex-direction:column;gap:8px;">
            <label for="formName" style="font-weight:500;">Payee Name</label>
            <input type="text" id="formName" name="Payee" required placeholder="<?php echo $informant ? $informant : 'Name'; ?>" style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid #d1d5db;background:#fff;font-size:1rem;" value="<?php echo htmlspecialchars($ledgerEntry['Payee'] ?? $informant); ?>" autocomplete="off" list="payeeNameList">
            <datalist id="payeeNameList">
              <?php foreach ($payeeSuggestions as $suggestion): ?>
                <option value="<?php echo htmlspecialchars($suggestion); ?>"></option>
              <?php endforeach; ?>
            </datalist>
          </div>
          <!-- Apt No. (NOT required) -->
          <div style="display:flex;flex-direction:column;gap:8px;position:relative;">
            <label for="formApartmentNo" style="font-weight:500;">Apt No.</label>
            <input type="text" id="formApartmentNo" name="ApartmentNo" placeholder="<?php echo $apartment ? $apartment : 'e.g. A-101'; ?>" style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid #d1d5db;background:#fff;font-size:1rem;" value="<?php echo htmlspecialchars($ledgerEntry['ApartmentNo'] ?? $apartment); ?>">
            <!-- NicheID matches container (hidden by default) - renders items like deceasedMatches -->
            <div id="nicheMatches" style="display:none; position:absolute; left:0; top:calc(100% + 6px); z-index:1200; min-width:220px; padding:6px; border:1px solid #d1d5db; border-radius:8px; background:#fff; font-size:1rem; box-shadow:0 6px 18px rgba(0,0,0,0.06); max-height:260px; overflow:auto;"></div>
          </div>
          <!-- Deceased Name (required) -->
          <div style="display:flex;flex-direction:column;gap:6px;position:relative;">
            <label for="formDeceased" style="font-weight:500;">Deceased Name</label>
            <input type="text" id="formDeceased" name="DeceasedName" required placeholder="Deceased Name" style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid #d1d5db;background:#fff;font-size:1rem;" value="<?php echo htmlspecialchars($ledgerEntry['DeceasedName'] ?? ''); ?>" autocomplete="off">
            <!-- New: container for multiple-match suggestions (hidden until needed) -->
            <div id="deceasedMatches" style="display:none; position:absolute; left:0; top:calc(100% + 6px); z-index:1200; min-width:220px; padding:6px; border:1px solid #d1d5db; border-radius:8px; background:#fff; font-size:1rem; box-shadow:0 6px 18px rgba(0,0,0,0.06); max-height:260px; overflow:auto;"></div>
          </div>
          <!-- Amount (required) -->
          <div style="display:flex;flex-direction:column;gap:8px;">
            <label for="formAmount" style="font-weight:500;">Amount</label>
            <div style="position:relative;">
              <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#888;font-size:1.08rem;">₱</span>
              <input type="text" id="formAmount" name="Amount" required placeholder="0.00" style="width:104%;box-sizing:border-box;padding-left:28px;padding-right:12px;padding-top:10px;padding-bottom:10px;border-radius:8px;border:1px solid #d1d5db;background:#fff;font-size:1rem;" value="<?php echo isset($ledgerEntry['Amount']) ? number_format($ledgerEntry['Amount'], 2) : ''; ?>">
            </div>
          </div>
          <!-- Date Paid (required) -->
          <div style="display:flex;flex-direction:column;gap:8px;">
            <label for="formDatePaid" style="font-weight:500;">Date Paid</label>
            <input type="date" id="formDatePaid" name="DatePaid" required style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid #d1d5db;background:#fff;font-size:1rem;" value="<?php echo htmlspecialchars($ledgerEntry['DatePaid'] ?? ''); ?>">
          </div>
          <div></div>
        </div>
        <!-- Section: Details -->
        <div style="font-weight:600;font-size:1.08rem;margin-bottom:18px;margin-top:18px;">Details</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px 32px;">
          <!-- Description / Type (required) -->
          <div style="display:flex;flex-direction:column;gap:8px;">
            <label for="formDescription" style="font-weight:500;">Description / Type</label>
            <select
              id="formDescription"
              name="Description"
              required
              style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid #d1d5db;background:#fff;font-size:1rem;"
            >
              <option value="" disabled selected>Select Type</option>
              <option value="New" <?php if (($ledgerEntry['Description'] ?? '') === 'New') echo 'selected'; ?>>New</option>
              <option value="Renewal" <?php if (($ledgerEntry['Description'] ?? '') === 'Renewal') echo 'selected'; ?>>Renewal</option>
              <option value="ReOpen" <?php if (($ledgerEntry['Description'] ?? '') === 'ReOpen') echo 'selected'; ?>>ReOpen</option>
              <option value="Transfer" <?php if (($ledgerEntry['Description'] ?? '') === 'Transfer') echo 'selected'; ?>>Transfer</option>
              <option value="Full Payment" <?php if (($ledgerEntry['Description'] ?? '') === 'Full Payment') echo 'selected'; ?>>Full Payment</option>
            </select>
          </div>
          <!-- Validity (required) -->
          <div style="display:flex;flex-direction:column;gap:8px;">
            <label for="formValidity" style="font-weight:500;">Validity</label>
            <input type="date" id="formValidity" name="Validity" required style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid #d1d5db;background:#fff;font-size:1rem;" value="">
          </div>
          <!-- OR Number (required) -->
          <div style="display:flex;flex-direction:column;gap:8px;">
            <label for="formORNumber" style="font-weight:500;">OR Number</label>
            <input type="text" id="formORNumber" name="ORNumber" required placeholder="Official Receipt No." style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid #d1d5db;font-size:1rem;" value="<?php echo isset($ledgerEntry['ORNumber']) ? htmlspecialchars($ledgerEntry['ORNumber']) : htmlspecialchars($orNumber); ?>" maxlength="8" pattern="\d{8}" title="OR Number must be 8 digits">
          </div>
          <!-- MC No. (required) -->
          <div style="display:flex;flex-direction:column;gap:8px;">
            <label for="formMCNo" style="font-weight:500;">MC No.</label>
            <input type="text" id="formMCNo" name="MCNo" required placeholder="MC No. (optional)" style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid #d1d5db;font-size:1rem;"
              value="<?php echo isset($ledgerEntry['MCNo']) && $ledgerEntry['MCNo'] !== null ? htmlspecialchars($ledgerEntry['MCNo']) : htmlspecialchars($mcNumber); ?>">
          </div>
        </div>
        <div style="margin-top:32px;text-align:right;border-top:1px solid #f0f0f0;padding-top:24px;">
          <button type="submit" class="btn upload" style="width: 140px; padding: 12px 0; font-size:1.08rem;background:#0077b6;color:#fff;border-radius:8px;">Submit</button>
        </div>
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($ledgerEntry['id'] ?? ''); ?>">
      </form>
    </div>
    <!-- Payment Details Section (now comes second, hidden by default) -->
    <div id="paymentDetailsSection" class="card ledger-table-container" style="max-width: 100%; background: #fff; border-radius: 16px; box-shadow: 0 2px 8px rgba(44,62,80,0.08); padding: 0 0 32px 0; box-sizing: border-box; display:none; margin-top: 18px;">
      <div style="display: flex; justify-content: space-between; align-items: center; padding: 32px 32px 0 32px; margin-bottom:24px;">
        <span style="font-size:1.25rem;font-weight:600;letter-spacing:0.5px;">Payment Details</span>
        <!-- Filter buttons: All / New / renewal / reopen / transfer / full payment -->
        <div id="ledgerFilters" style="display:flex;gap:8px;align-items:center; position:relative;">
            <!-- Single toggle button that shows a dropdown for filter choices -->
            <button id="ledgerFilterToggle" class="ledger-filter-btn active" type="button" aria-expanded="false" aria-haspopup="true" data-filter="all" style="display:flex;align-items:center;gap:8px;">
              <span id="ledgerFilterLabel">All</span>
              <i class="fas fa-caret-down" style="font-size:0.95rem;"></i>
            </button>
            <div id="ledgerFilterDropdown" class="ledger-filter-dropdown" style="display:none; position:absolute; right:0; top:calc(100% + 8px); background:#fff; border-radius:8px; box-shadow:0 6px 20px rgba(11,117,168,0.08); padding:8px; z-index:1200; min-width:160px;">
              <button class="ledger-filter-item ledger-filter-btn" data-filter="all" type="button" style="width:100%;text-align:left;padding:8px 10px;border-radius:6px;border:none;background:transparent;">All</button>
              <button class="ledger-filter-item ledger-filter-btn" data-filter="new" type="button" style="width:100%;text-align:left;padding:8px 10px;border-radius:6px;border:none;background:transparent;">New</button>
              <button class="ledger-filter-item ledger-filter-btn" data-filter="renewal" type="button" style="width:100%;text-align:left;padding:8px 10px;border-radius:6px;border:none;background:transparent;">Renewal</button>
              <button class="ledger-filter-item ledger-filter-btn" data-filter="reopen" type="button" style="width:100%;text-align:left;padding:8px 10px;border-radius:6px;border:none;background:transparent;">Reopen</button>
              <button class="ledger-filter-item ledger-filter-btn" data-filter="transfer" type="button" style="width:100%;text-align:left;padding:8px 10px;border-radius:6px;border:none;background:transparent;">Transfer</button>
              <button class="ledger-filter-item ledger-filter-btn" data-filter="fullpayment" type="button" style="width:100%;text-align:left;padding:8px 10px;border-radius:6px;border:none;background:transparent;">Full Payment</button>
            </div>
          </div>
      </div>
      <!-- Search Bar + Buttons Row (moved here from top) -->
      <div style="display:flex;align-items:center;justify-content:space-between;padding: 0 32px 18px 32px;">
        <div class="search-container" style="flex:1;max-width:420px;">
          <i class="fas fa-search"></i>
          <input type="text" id="ledger-search-input" placeholder="Search Payment Details" style="font-family:'Poppins',sans-serif;">
        </div>
        <div style="display:flex;gap:10px;align-items:center;margin-left:18px;">
          <!-- Add Generate Report button (green) -->
          <button id="generateReportBtn" style="background:#27ae60;color:#fff;border:none;padding:8px 18px;border-radius:7px;font-weight:500;display:flex;align-items:center;gap:8px;cursor:pointer;">
            <i class="fas fa-file-alt"></i> Generate Report
          </button>
          <button id="exportExcelBtn" style="background:#0077b6;color:#fff;border:none;padding:8px 18px;border-radius:7px;font-weight:500;display:flex;align-items:center;gap:8px;cursor:pointer;">
            <i class="fas fa-file-excel"></i> Export Data
          </button>
          <button id="ledgerDeleteBtn" type="button" style="background:#e74c3c;color:#fff;border:none;padding:8px 18px;border-radius:7px;font-weight:500;display:flex;align-items:center;gap:8px;cursor:pointer;">
            <i class="fas fa-trash"></i> Delete
          </button>
        </div>
      </div>
      <form id="ledgerDeleteForm" method="post" style="margin:0;">
        <div style="overflow-x:auto; padding: 0 32px; position:relative;">
          <table class="ledger-table" id="paymentDetailsTable" style="min-width:1100px;border-collapse:collapse;">
            <thead>
              <tr>
                <th style="min-width:100px;">Apt No.</th>
                <th style="min-width:100px;">Payee Name</th>
                <th style="min-width:140px;">Deceased Name</th>
                <th style="min-width:100px;">Date Paid</th>
                <th>Amount</th>
                <th style="min-width:100px;">Description</th>
                <th style="min-width:100px;">OR Number</th>
                <th style="min-width:100px;">Validity</th>
                <th style="min-width:100px;">MC No.</th>
                <th>Action</th>
                <!-- checkbox column for selection on the right -->
                <th id="ledgerDeleteTh" style="width:48px;padding-right:8px;display:none;">
                  <input type="checkbox" id="ledgerSelectAllCheckbox" style="display:inline-block;vertical-align:middle;">
                </th>
                <style>
                  /* Match filter buttons to status badge colors (neutralized: transparent bg, black text) */
                  .ledger-filter-btn.badge-green,
                  .ledger-filter-btn.badge-blue,
                  .ledger-filter-btn.badge-yellow,
                  .ledger-filter-btn.badge-purple,
                  .ledger-filter-btn.badge-default {
                    color: #000; /* black text */
                    border-color: #e6e9ec;
                    background: transparent;
                    font-weight:400; /* normal */
                  }
                  /* Active state: keep emphasis with normal weight, no filled color or heavy shadow */
                  .ledger-filter-btn.active.badge-green,
                  .ledger-filter-btn.active.badge-blue,
                  .ledger-filter-btn.active.badge-yellow,
                  .ledger-filter-btn.active.badge-purple,
                  .ledger-filter-btn.active.badge-default {
                    background: transparent;
                    color: #000;
                    border-color: #bfc8d1;
                    font-weight:400; /* normal */
                    box-shadow: none;
                  }
                </style>
              </tr>
            </thead>
            <tbody>
              <?php
              // Fetch payment details (where DatePaid is NOT NULL and not empty)
              $paymentResult = $conn->query("SELECT * FROM ledger WHERE DatePaid IS NOT NULL AND DatePaid != '' ORDER BY DatePaid DESC");
              if ($paymentResult && $paymentResult->num_rows > 0) {
                while ($row = $paymentResult->fetch_assoc()) {
                  // normalize description into one of: new, renewal, reopen, transfer, fullpayment (server-side token)
                  $descRaw = isset($row['Description']) ? trim($row['Description']) : '';
                  $descNorm = strtolower(preg_replace('/[\s\-\_]+/', '', $descRaw));
                  // Map common variants to tokens (defensive)
                  if (in_array($descNorm, ['new'])) $token = 'new';
                  else if (strpos($descNorm, 'renew') === 0 || $descNorm === 'renewal') $token = 'renewal';
                  else if (strpos($descNorm, 'reopen') === 0) $token = 'reopen';
                  else if (strpos($descNorm, 'transfer') === 0) $token = 'transfer';
                  else if (strpos($descNorm, 'fullpayment') === 0 || strpos($descNorm, 'fullpay') === 0) $token = 'fullpayment';
                  else $token = $descNorm; // fallback (won't match filters)
                  // Build small avatar (first letter) and status badge per description token
                  $aptNo = isset($row['ApartmentNo']) && trim($row['ApartmentNo']) !== '' ? htmlspecialchars($row['ApartmentNo']) : 'No Data';
                  $payee = isset($row['Payee']) && trim($row['Payee']) !== '' ? htmlspecialchars($row['Payee']) : 'No Data';
                  $deceased = isset($row['DeceasedName']) && trim($row['DeceasedName']) !== '' ? htmlspecialchars($row['DeceasedName']) : 'No Data';
                  $datePaid = isset($row['DatePaid']) && trim($row['DatePaid']) !== '' ? htmlspecialchars($row['DatePaid']) : 'No Data';
                  $amountFmt = isset($row['Amount']) && $row['Amount'] !== '' ? '₱' . number_format($row['Amount'], 2) : 'No Data';
                  $badgeText = isset($row['Description']) && trim($row['Description']) !== '' ? htmlspecialchars($row['Description']) : 'No Data';
                  $orderNo = isset($row['ORNumber']) && trim($row['ORNumber']) !== '' ? '#' . htmlspecialchars($row['ORNumber']) : 'No Data';
                  $validity = isset($row['Validity']) && trim($row['Validity']) !== '' ? htmlspecialchars($row['Validity']) : 'No Data';
                  $mcNo = isset($row['MCNo']) && trim($row['MCNo']) !== '' ? htmlspecialchars($row['MCNo']) : 'No Data';
                  // Status badge color mapping
                  $badgeClass = 'badge-default';
                  if ($token === 'new' || $token === 'fullpayment') $badgeClass = 'badge-green';
                  else if ($token === 'renewal') $badgeClass = 'badge-blue';
                  else if ($token === 'reopen') $badgeClass = 'badge-yellow';
                  else if ($token === 'transfer') $badgeClass = 'badge-purple';

    echo '<tr data-type="' . htmlspecialchars($token) . '">';
    // Apartment No. (moved to first column)
    echo '<td style="color:#000000;">' . $aptNo . '</td>';
    // Customer (name)
    echo '<td style="display:flex;flex-direction:column;">';
    echo '<span style="font-weight:600;">' . $payee . '</span>';
    echo '</td>';
    // Deceased (from ledger.DeceasedName)
    echo '<td style="color:#000000;">' . $deceased . '</td>';
    // Date Paid
    echo '<td>' . $datePaid . '</td>';
    // Amount
    echo '<td style="font-weight:600;">' . $amountFmt . '</td>';
    // Description (status badge)
    echo '<td><span class="status-badge ' . $badgeClass . '">' . $badgeText . '</span></td>';
    // OR Number (moved between Description and Validity)
    echo '<td style="color:#000000;">' . $orderNo . '</td>';
    // Validity
    echo '<td>' . $validity . '</td>';
    // MC No.
    echo '<td>' . $mcNo . '</td>';
          // Action column (edit / insert / more)
          $apt = trim($row['ApartmentNo'] ?? '');
          if ($apt !== '') {
            $params = http_build_query([
              'nicheID'    => $row['ApartmentNo'],
              'ledger_id'  => $row['id'],
              'payee'      => $row['Payee'],
              'amount'     => number_format($row['Amount'], 2, '.', ''),
              'DatePaid'   => $row['DatePaid'],
              'ORNumber'   => $row['ORNumber'],
              'MCNo'       => $row['MCNo'],
              'Description'=> $row['Description'],
              'Validity'   => $row['Validity']
            ]);
            echo '<td><a href="EditNiches.php?' . $params . '" class="action-btn" style="background:none;border:none;color:#0b75a8;text-decoration:none;font-weight:600;">Edit</a> &nbsp; <a href="#" class="more-btn" data-id="' . $row['id'] . '" style="color:#9ca3af;"></a></td>';
          } else {
            echo '<td><a href="Insert.php?id=' . intval($row['id']) . '" class="action-btn" style="background:none;border:none;color:#0b75a8;text-decoration:none;font-weight:600;">Insert</a> &nbsp; <a href="#" class="more-btn" data-id="' . $row['id'] . '" style="color:#9ca3af;"></a></td>';
          }
          // Checkbox cell on the right
          echo '<td style="padding-right:10px;"><input type="checkbox" class="ledger-delete-checkbox" name="delete_ids[]" value="' . $row['id'] . '" style="vertical-align:middle;"></td>';
          echo '</tr>';
                }
              }
              ?>
            </tbody>
          </table>
        </div>
      </form>
      <style>
        /* Avatar and badge styles for Payment Details */
        .avatar { font-family: 'Poppins',sans-serif; }
        .status-badge { display:inline-block;padding:6px 10px;border-radius:999px;font-weight:400;font-size:0.87rem;background:transparent;color:#000;border:1px solid transparent; }
        .badge-green { background: transparent; color: #000; }
        .badge-blue { background: transparent; color: #000; }
        .badge-yellow { background: transparent; color: #000; }
        .badge-purple { background: transparent; color: #000; }
        .badge-default { background: transparent; color: #000; }
        /* Table row hover */
        #paymentDetailsTable tbody tr:hover { background:#fbfdff; }
        /* Selection checkbox larger and visible */
        .ledger-delete-checkbox { width:18px; height:18px; }
      </style>
      <!-- Delete Confirmation Modal (proper popup modal, overlays the page) -->
      <div id="ledgerDeleteModal" class="modal-overlay" style="display:none;position:fixed;z-index:9999;left:0;top:0;right:0;bottom:0;background:rgba(44,62,80,0.18);align-items:center;justify-content:center;">
        <div class="modal-content" style="background:#fff;border-radius:16px;box-shadow:0 8px 32px rgba(60,60,60,0.18),0 1.5px 6px rgba(0,0,0,0.08);padding:32px 32px 24px 32px;min-width:340px;max-width:95vw;text-align:center;position:relative;">
          <div class="modal-header">
            <i class="fas fa-exclamation-triangle" style="color:#e74c3c;font-size:2rem;margin-bottom:8px;"></i>
            <h2 style="color:#e74c3c;margin:0;font-size:1.3rem;">Confirm Archive</h2>
          </div>
          <div class="modal-body" style="margin:18px 0 24px 0;">
            <p id="ledgerDeleteModalText" style="color:#444;font-size:1.07rem;margin:0;">
              Are you sure you want to delete this ledger entry?<br>
              This action will move the record to the archive section.
            </p>
          </div>
          <div class="modal-footer" style="display:flex;justify-content:center;gap:16px;">
            <button id="ledgerModalDeleteBtn" class="modal-delete-btn" style="background:#e74c3c;color:#fff;border:none;padding:12px 24px;border-radius:8px;cursor:pointer;font-weight:500;font-size:1rem;">Delete</button>
            <button id="ledgerModalCancelBtn" class="modal-cancel-btn" style="background:#95a5a6;color:#fff;border:none;padding:12px 24px;border-radius:8px;cursor:pointer;font-weight:500;font-size:1rem;">Cancel</button>
          </div>
        </div>
      </div>
      <!-- Success Notification -->
      <div id="ledgerSuccessNotification" style="display:none;position:fixed;top:32px;right:32px;z-index:10000;background:#2ecc71;color:#fff;padding:18px 32px;border-radius:8px;box-shadow:0 4px 16px rgba(46,204,113,0.15);font-size:1.1rem;font-weight:500;align-items:center;gap:16px;min-width:220px;">
        <span><i class="fas fa-check-circle" style="margin-right:8px;"></i><span id="ledgerNotificationText">Ledger entry deleted.</span></span>
        <button id="ledgerCloseNotificationBtn" style="background:none;border:none;color:#fff;font-size:1.2em;cursor:pointer;margin-left:12px;">&times;</button>
      </div>
    </div>
    <!-- Generate Report Modal -->
    <div id="reportModal" style="display:none;position:fixed;z-index:9999;left:0;top:0;width:100vw;height:100vh;background:rgba(44,62,80,0.18);align-items:center;justify-content:center;">
      <div style="background:#fff;border-radius:16px;box-shadow:0 8px 32px rgba(60,60,60,0.18),0 1.5px 6px rgba(0,0,0,0.08);padding:32px 32px 24px 32px;min-width:340px;max-width:95vw;text-align:center;position:relative;">
        <div style="font-size:1.3rem;font-weight:600;margin-bottom:18px;">Generate Income Report</div>
        <div style="margin-bottom:18px;">
          <label for="reportDateFilter" style="font-weight:500;margin-bottom:8px;display:block;">Select Date Range</label>
          <select id="reportDateFilter" style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid #d1d5db;font-size:1rem;">
            <option value="week">Week</option>
            <option value="month">Month</option>
            <option value="year">Year</option>
          </select>
        </div>
        <div id="reportDateInputs" style="margin-bottom:18px;">
          <!-- Dynamic date inputs will be rendered here -->
        </div>
        <!-- Description Type Checkbox Filter -->
        <div style="margin-bottom:18px;">
          <label style="font-weight:500;margin-bottom:8px;display:block;">Filter by Description Type</label>
          <div id="reportDescTypes" style="display:flex;flex-wrap:wrap;gap:12px;">
            <label><input type="checkbox" value="All" id="desc-type-all"> All</label>
            <label><input type="checkbox" value="New" class="desc-type-checkbox"> New</label>
            <label><input type="checkbox" value="Renewal" class="desc-type-checkbox"> Renewal</label>
            <label><input type="checkbox" value="ReOpen" class="desc-type-checkbox"> ReOpen</label>
            <label><input type="checkbox" value="Transfer" class="desc-type-checkbox"> Transfer</label>
            <label><input type="checkbox" value="Full Payment" class="desc-type-checkbox"> Full Payment</label>
          </div>
        </div>
        <button id="reportGenerateBtn" style="background:#27ae60;color:#fff;border:none;padding:12px 24px;border-radius:8px;cursor:pointer;font-weight:500;font-size:1rem;">Generate</button>
        <button id="reportCloseBtn" style="background:#95a5a6;color:#fff;border:none;padding:12px 24px;border-radius:8px;cursor:pointer;font-weight:500;font-size:1rem;margin-left:12px;">Close</button>
        <div id="reportResult" style="margin-top:24px;font-size:1.08rem;color:#222;font-weight:500;"></div>
      </div>
    </div>

    <script>
      // --- Generate Report Modal Logic ---
      const reportModal = document.getElementById('reportModal');
      const generateReportBtn = document.getElementById('generateReportBtn');
      const reportCloseBtn = document.getElementById('reportCloseBtn');
      const reportDateFilter = document.getElementById('reportDateFilter');
      const reportDateInputs = document.getElementById('reportDateInputs');
      const reportGenerateBtn = document.getElementById('reportGenerateBtn');
      const reportResult = document.getElementById('reportResult');

      // Show modal
      generateReportBtn.addEventListener('click', function() {
        reportModal.style.display = 'flex';
        renderDateInputs();
        reportResult.textContent = '';
      });

      // Close modal
      reportCloseBtn.addEventListener('click', function() {
        reportModal.style.display = 'none';
      });
      reportModal.addEventListener('click', function(e) {
        if (e.target === this) this.style.display = 'none';
      });

      // Render date inputs based on filter
      function renderDateInputs() {
        const filter = reportDateFilter.value;
        let html = '';
        if (filter === 'week') {
          html = `
            <label style="font-weight:500;">Select Week:</label>
            <input type="week" id="reportWeekInput" style="width:95%;padding:10px 12px;border-radius:8px;border:1px solid #d1d5db;font-size:1rem;">
          `;
        } else if (filter === 'month') {
          html = `
            <label style="font-weight:500;">Select Month:</label>
            <input type="month" id="reportMonthInput" style="width:95%;padding:10px 12px;border-radius:8px;border:1px solid #d1d5db;font-size:1rem;">
          `;
        } else if (filter === 'year') {
          html = `
            <label style="font-weight:500;">Select Year:</label>
            <input type="number" id="reportYearInput" min="2000" max="2100" value="${new Date().getFullYear()}" style="width:95%;padding:10px 12px;border-radius:8px;border:1px solid #d1d5db;font-size:1rem;">
          `;
        }
        reportDateInputs.innerHTML = html;
      }
      reportDateFilter.addEventListener('change', renderDateInputs);

      // Description Type "All" checkbox logic
      const descTypeAll = document.getElementById('desc-type-all');
      const descTypeCheckboxes = document.querySelectorAll('.desc-type-checkbox');
      descTypeAll.addEventListener('change', function() {
        if (this.checked) {
          descTypeCheckboxes.forEach(cb => {
            cb.checked = true;
            cb.disabled = true;
          });
        } else {
          descTypeCheckboxes.forEach(cb => {
            cb.checked = false;
            cb.disabled = false;
          });
        }
      });
      descTypeCheckboxes.forEach(cb => {
        cb.addEventListener('change', function() {
          // If any unchecked, uncheck "All"
          if (!this.checked) descTypeAll.checked = false;
          // If all checked, check "All"
          if ([...descTypeCheckboxes].every(c => c.checked)) {
            descTypeAll.checked = true;
            descTypeCheckboxes.forEach(c => c.disabled = true);
          }
        });
      });

      // Generate report logic
      reportGenerateBtn.addEventListener('click', function() {
        const filter = reportDateFilter.value;
        let param = '', value = '';
        if (filter === 'week') {
          const weekVal = document.getElementById('reportWeekInput').value;
          if (!weekVal) { reportResult.textContent = 'Please select a week.'; return; }
          param = 'week';
          value = weekVal;
        } else if (filter === 'month') {
          const monthVal = document.getElementById('reportMonthInput').value;
          if (!monthVal) { reportResult.textContent = 'Please select a month.'; return; }
          param = 'month';
          value = monthVal;
        } else if (filter === 'year') {
          const yearVal = document.getElementById('reportYearInput').value;
          if (!yearVal) { reportResult.textContent = 'Please enter a year.'; return; }
          param = 'year';
          value = yearVal;
        }
        if (!param || !value) { reportResult.textContent = 'Invalid date range.'; return; }
        // Get selected description types from checkboxes
        let selectedTypes = [];
        if (descTypeAll.checked) {
          selectedTypes = ['All'];
        } else {
          selectedTypes = Array.from(document.querySelectorAll('.desc-type-checkbox:checked')).map(cb => cb.value);
        }
        window.location.href = `generateReport.php?filter=${encodeURIComponent(param)}&value=${encodeURIComponent(value)}&types=${encodeURIComponent(selectedTypes.join(','))}`;
      });
    </script>
    <!-- Success Popup Modal for Ledger Information submission -->
    <?php if ($showLedgerSuccessModal): ?>
<!-- Compact ClientsRequest-style success notification (top-right) -->
<div id="ledgerSuccessPopup" style="display:flex;position:fixed;top:32px;right:32px;z-index:10000;background:#fff;border-radius:12px;box-shadow:0 4px 16px rgba(0,0,0,0.12);padding:14px 18px;min-width:260px;align-items:center;gap:12px;font-family:'Poppins',sans-serif;">
  <span style="color:#27ae60;font-size:1.6rem;line-height:1;"><i class="fas fa-check-circle"></i></span>
  <div style="display:flex;flex-direction:column;flex:1;min-width:0;">
    <div style="font-weight:600;color:#222;font-size:1.02rem;line-height:1;">Success</div>
    <div id="ledgerSuccessPopupMessage" style="color:#555;font-size:0.95rem;line-height:1.2;">Ledger Information has been submitted successfully.</div>
  </div>
  <button id="ledgerSuccessPopupClose" style="background:none;border:none;color:#888;font-size:1.2rem;cursor:pointer;padding:6px 8px;border-radius:6px;">&times;</button>
</div>
<script>
  (function() {
    var popup = document.getElementById('ledgerSuccessPopup');
    var closeBtn = document.getElementById('ledgerSuccessPopupClose');
    var timer = null;
    function hideAndRedirect() {
      if (!popup) return;
      popup.style.display = 'none';
      // Redirect to the ledger page (refresh) to reflect new data
      try { window.location.href = 'Ledger.php'; } catch(e) { /* fallback */ window.location.reload(); }
    }
    // Auto-hide after 3000ms
    timer = setTimeout(hideAndRedirect, 3000);
    // Close button behavior: cancel timer and redirect immediately
    if (closeBtn) {
      closeBtn.addEventListener('click', function(e) {
        e.preventDefault();
        if (timer) clearTimeout(timer);
        hideAndRedirect();
      });
    }
    // Also hide + redirect if user clicks outside popup (optional)
    // Not strictly needed, but keeps UX consistent if user clicks around
    document.addEventListener('click', function(ev) {
      if (!popup) return;
      if (!popup.contains(ev.target)) {
        if (timer) clearTimeout(timer);
        hideAndRedirect();
      }
    }, { once: true });
  })();
</script>
<?php endif; ?>
    <script>
      // Tab switching logic for two tabs (Ledger Information is default)
      const ledgerTabBtn = document.getElementById('ledgerTabBtn');
      const paymentTabBtn = document.getElementById('paymentTabBtn');
      const ledgerInfoSection = document.getElementById('ledgerInfoSection');
      const paymentDetailsSection = document.getElementById('paymentDetailsSection');
      // Set Ledger Information as default visible
      ledgerTabBtn.classList.add('active');
      paymentTabBtn.classList.remove('active');
      ledgerInfoSection.style.display = '';
      paymentDetailsSection.style.display = 'none';

      // DataTables lazy initialization for Payment Details
      let paymentDetailsDataTable = null;
      let paymentTabInitialized = false;

      ledgerTabBtn.addEventListener('click', function() {
        ledgerTabBtn.classList.add('active');
        paymentTabBtn.classList.remove('active');
        ledgerInfoSection.style.display = '';
        paymentDetailsSection.style.display = 'none';
      });
      paymentTabBtn.addEventListener('click', function() {
        ledgerTabBtn.classList.remove('active');
        paymentTabBtn.classList.add('active');
        ledgerInfoSection.style.display = 'none';
        paymentDetailsSection.style.display = '';
        // Initialize DataTables only once, after table is visible
        if (!paymentTabInitialized) {
              // Apply initial badge classes to dropdown items and the toggle to match status colors
              (function() {
                const mapping = {
                  'all': 'badge-default',
                  'new': 'badge-green',
                  'renewal': 'badge-blue',
                  'reopen': 'badge-yellow',
                  'transfer': 'badge-purple',
                  'fullpayment': 'badge-green'
                };
                const items = document.querySelectorAll('.ledger-filter-item');
                items.forEach(it => {
                  const t = (it.getAttribute('data-filter') || 'all').toLowerCase();
                  const cls = mapping[t] || 'badge-default';
                  it.classList.add(cls);
                });
                // Also set the toggle button class to the currently selected token (default: all)
                const toggle = document.getElementById('ledgerFilterToggle');
                if (toggle) toggle.classList.add(mapping['all']);
              })();
          paymentDetailsDataTable = $('#paymentDetailsTable').DataTable({
            paging: true,
            searching: true,
            ordering: true,
            info: true,
            dom: 'lrtip' // Remove default search box
          });
          // Connect top search bar to DataTables search
          document.getElementById('ledger-search-input').addEventListener('keyup', function() {
            paymentDetailsDataTable.search(this.value).draw();
          });
          paymentTabInitialized = true;
          // --- Ledger filter integration using DataTables custom search ---
          (function() {
            let currentLedgerFilter = 'all';
                // Register DataTables custom filter
                 $.fn.dataTable.ext.search.push(function(settings, data, dataIndex, rowData, counter) {
                   if (settings.nTable.id !== 'paymentDetailsTable') return true;
                   if (currentLedgerFilter === 'all') return true;
                   // Get tr node for this row and read data-type attribute
                   const rowNode = paymentDetailsDataTable.row(dataIndex).node();
                   if (!rowNode) return true;
                   const type = (rowNode.getAttribute('data-type') || '').toLowerCase();
                   return type === currentLedgerFilter;
                 });
 
                // Wire up dropdown filter items
                const filterItems = Array.from(document.querySelectorAll('.ledger-filter-item'));
                const filterToggle = document.getElementById('ledgerFilterToggle');
                const filterLabel = document.getElementById('ledgerFilterLabel');
                const filterDropdown = document.getElementById('ledgerFilterDropdown');
 
                // Toggle dropdown visibility
                if (filterToggle) {
                  filterToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const open = filterDropdown.style.display === 'block';
                    filterDropdown.style.display = open ? 'none' : 'block';
                    this.setAttribute('aria-expanded', open ? 'false' : 'true');
                  });
                }
 
                // Close dropdown on outside click
                document.addEventListener('click', function(e) {
                  if (!filterDropdown) return;
                  if (!filterDropdown.contains(e.target) && e.target !== filterToggle) {
                    filterDropdown.style.display = 'none';
                    if (filterToggle) filterToggle.setAttribute('aria-expanded', 'false');
                  }
                });
 
                 // Helper to apply a filter token (updates active class, current filter, redraws)
                 function applyLedgerFilter(token, pushHash = false) {
                   currentLedgerFilter = token || 'all';
                  // update active state on dropdown items
                  filterItems.forEach(b => b.classList.toggle('active', b.getAttribute('data-filter') === currentLedgerFilter));
                  // update toggle label and badge class
                  if (filterLabel) filterLabel.textContent = currentLedgerFilter === 'all' ? 'All' : filterItems.find(i => i.getAttribute('data-filter') === currentLedgerFilter).textContent;
                  // remove existing badge- classes from toggle then add the new one
                  if (filterToggle) {
                    filterToggle.classList.remove('badge-default','badge-green','badge-blue','badge-yellow','badge-purple');
                    const map = { 'all':'badge-default','new':'badge-green','renewal':'badge-blue','reopen':'badge-yellow','transfer':'badge-purple','fullpayment':'badge-green' };
                    filterToggle.classList.add(map[currentLedgerFilter] || 'badge-default');
                  }
                   paymentDetailsDataTable.draw();
                   if (pushHash) {
                     try {
                       // Update hash without adding history entry
                       history.replaceState(null, '', '#filter=' + encodeURIComponent(currentLedgerFilter));
                     } catch (e) {
                       location.hash = 'filter=' + encodeURIComponent(currentLedgerFilter);
                     }
                   }
                 }
 
                 // Click handler sets filter and updates hash
                filterItems.forEach(btn => {
                  btn.addEventListener('click', function(e) {
                    const f = this.getAttribute('data-filter') || 'all';
                    // close dropdown and apply
                    if (filterDropdown) filterDropdown.style.display = 'none';
                    if (filterToggle) filterToggle.setAttribute('aria-expanded', 'false');
                    applyLedgerFilter(f, true);
                  });
                });
 
                 // Initialize from URL hash if present (format: #filter=token)
                 (function initFromHash() {
                   const h = (location.hash || '').replace(/^#/, '');
                   const m = h.match(/(?:^|&)filter=([^&]+)/) || h.match(/^filter=([^&]+)/);
                   if (m && m[1]) {
                     const token = decodeURIComponent(m[1].toLowerCase());
                     // Only apply if one of the known tokens (defensive)
                     const known = ['all','new','renewal','reopen','transfer','fullpayment'];
                    applyLedgerFilter(known.includes(token) ? token : 'all', false);
                   } else {
                     // ensure UI matches default 'all'
                     applyLedgerFilter('all', false);
                   }
                 })();
 
                 // Listen for hashchange so external navigation updates filter
                 window.addEventListener('hashchange', function() {
                   const h = (location.hash || '').replace(/^#/, '');
                   const m = h.match(/(?:^|&)filter=([^&]+)/) || h.match(/^filter=([^&]+)/);
                   if (m && m[1]) {
                     const token = decodeURIComponent(m[1].toLowerCase());
                     applyLedgerFilter(token, false);
                   }
                 });
               })();
             }
           });
    </script>
    <!-- Add SheetJS for Excel export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
      // Export table to Excel functionality
      document.getElementById('exportExcelBtn').addEventListener('click', function() {
        var table = document.getElementById('paymentDetailsTable');
        // Clone table to avoid DataTables hidden columns
        var clone = table.cloneNode(true);
        // Remove any column that contains a checkbox input (checkbox cells)
        Array.from(clone.querySelectorAll('tr')).forEach(function(row) {
          for (var i = row.cells.length - 1; i >= 0; i--) {
            if (row.cells[i].querySelector && row.cells[i].querySelector('input[type="checkbox"]')) {
              row.deleteCell(i);
            }
          }
        });
        // Remove time from Date Paid and Validity columns by determining their column indexes
        var headerCells = Array.from(clone.querySelectorAll('thead th'));
        var dateIdx = -1, validIdx = -1;
        headerCells.forEach(function(h, idx) {
          var txt = (h.textContent || '').trim().toLowerCase();
          if (txt === 'date paid') dateIdx = idx;
          if (txt === 'validity') validIdx = idx;
        });
        Array.from(clone.querySelectorAll('tbody tr')).forEach(function(row) {
          if (dateIdx >= 0 && row.cells[dateIdx]) row.cells[dateIdx].textContent = (row.cells[dateIdx].textContent || '').split(' ')[0];
          if (validIdx >= 0 && row.cells[validIdx]) row.cells[validIdx].textContent = (row.cells[validIdx].textContent || '').split(' ')[0];
        });
        var wb = XLSX.utils.table_to_book(clone, {sheet:"Payment Details"});
        XLSX.writeFile(wb, 'PaymentDetails.xlsx');
      });

      // Ledger delete logic (copied/adapted from Records.php)
      const ledgerDeleteBtn = document.getElementById('ledgerDeleteBtn');
      const ledgerTable = document.getElementById('paymentDetailsTable');
  // Query selectors will be live/updated as rows change
  const ledgerDeleteCheckboxes = () => ledgerTable.querySelectorAll('.ledger-delete-checkbox');
  const ledgerSelectAllCheckbox = document.getElementById('ledgerSelectAllCheckbox');
      const ledgerDeleteModal = document.getElementById('ledgerDeleteModal');
      const ledgerModalDeleteBtn = document.getElementById('ledgerModalDeleteBtn');
      const ledgerModalCancelBtn = document.getElementById('ledgerModalCancelBtn');
      const ledgerDeleteForm = document.getElementById('ledgerDeleteForm');
      let ledgerDeleteMode = false;

      const ledgerDeleteTh = document.getElementById('ledgerDeleteTh');
      function setLedgerDeleteMode(on) {
        ledgerDeleteMode = on;
        if (on) {
          ledgerDeleteCheckboxes().forEach(cb => cb.style.display = '');
          if (ledgerSelectAllCheckbox) ledgerSelectAllCheckbox.style.display = '';
          if (ledgerDeleteTh) ledgerDeleteTh.style.display = '';
        } else {
          ledgerDeleteCheckboxes().forEach(cb => { cb.checked = false; cb.style.display = 'none'; });
          if (ledgerSelectAllCheckbox) { ledgerSelectAllCheckbox.checked = false; ledgerSelectAllCheckbox.style.display = 'none'; }
          if (ledgerDeleteTh) ledgerDeleteTh.style.display = 'none';
        }
      }
      setLedgerDeleteMode(false);

      // Select All logic
      function updateSelectionState() {
        const checkboxes = Array.from(ledgerDeleteCheckboxes());
        const checked = checkboxes.filter(cb => cb.checked);
        // update select-all (no toolbar UI)
        if (ledgerSelectAllCheckbox) ledgerSelectAllCheckbox.checked = (checkboxes.length > 0 && checked.length === checkboxes.length);
      }

      if (ledgerSelectAllCheckbox) {
        ledgerSelectAllCheckbox.addEventListener('change', function() {
          const checkboxes = Array.from(ledgerDeleteCheckboxes());
          checkboxes.forEach(cb => cb.checked = ledgerSelectAllCheckbox.checked);
          updateSelectionState();
        });
      }

      // Delegate change events to table for dynamic rows
      ledgerTable.addEventListener('change', function(e) {
        if (e.target.classList.contains('ledger-delete-checkbox')) {
          updateSelectionState();
        }
      });

      // Delete button click handler
      ledgerDeleteBtn.addEventListener('click', function(e) {
        e.preventDefault();
        if (!ledgerDeleteMode) {
          setLedgerDeleteMode(true);
          ledgerDeleteBtn.style.background = '#c0392b';
        } else {
          const checked = ledgerTable.querySelectorAll('.ledger-delete-checkbox:checked');
          if (checked.length === 0) {
            setLedgerDeleteMode(false);
            ledgerDeleteBtn.style.background = '#e74c3c';
            return;
          }
          // Update modal text
          const modalText = document.getElementById('ledgerDeleteModalText');
          if (modalText) {
            modalText.innerHTML = `Are you sure you want to delete ${checked.length > 1 ? 'these ledger entries' : 'this ledger entry'}?<br>This action will move the record to the archive section.`;
          }
          // Show modal
          ledgerDeleteModal.style.display = 'flex';
        }
      });

      // Modal handlers
      ledgerModalCancelBtn.addEventListener('click', function() {
        ledgerDeleteModal.style.display = 'none';
      });

      ledgerModalDeleteBtn.addEventListener('click', function() {
        const checked = ledgerTable.querySelectorAll('.ledger-delete-checkbox:checked');
        if (checked.length === 0) return;
        ledgerModalDeleteBtn.disabled = true;
        ledgerModalDeleteBtn.textContent = 'Deleting...';
        ledgerModalCancelBtn.disabled = true;
        // Collect IDs
        const deleteIds = Array.from(checked).map(cb => cb.value);
        // Create form data
        const formData = new FormData();
        deleteIds.forEach(id => {
          formData.append('delete_ids[]', id);
        });
        // Send request
        fetch('Ledger.php', {
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
          // Show success notification
          const notification = document.getElementById('ledgerSuccessNotification');
          const notificationText = document.getElementById('ledgerNotificationText');
          notificationText.textContent = `${deleteIds.length} ledger entr${deleteIds.length > 1 ? 'ies' : 'y'} deleted.`;
          notification.style.display = 'flex';
          setTimeout(() => {
            notification.style.display = 'none';
          }, 3000);
          // Hide modal and reset
          ledgerDeleteModal.style.display = 'none';
          setLedgerDeleteMode(false);
          ledgerDeleteBtn.style.background = '#e74c3c';
        })
        .catch(error => {
          console.error('Error:', error);
          alert('An error occurred while deleting. Please try again.');
        })
        .finally(() => {
          ledgerModalDeleteBtn.disabled = false;
          ledgerModalDeleteBtn.textContent = 'Delete';
          ledgerModalCancelBtn.disabled = false;
        });
      });

      // Close notification
      document.getElementById('ledgerCloseNotificationBtn').addEventListener('click', function() {
        document.getElementById('ledgerSuccessNotification').style.display = 'none';
      });

      // Close modal on overlay click
      ledgerDeleteModal.addEventListener('click', function(e) {
        if (e.target === this) {
          this.style.display = 'none';
        }
      });

      // Reset delete button and mode after page reload
      window.addEventListener('DOMContentLoaded', function() {
        setLedgerDeleteMode(false);
        ledgerDeleteBtn.style.background = '#e74c3c';
      });
    </script>
    <!-- DataTables JS -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script>
      // --- Autofill Apt No. with dropdown if multiple nicheIDs for Payee ---
      const informantNicheMap = <?php echo json_encode($informantNicheMap); ?>;
      const informantDeceasedMap = <?php echo json_encode($informantDeceasedMap); ?>;
      const nicheDeceasedMap = <?php echo json_encode($nicheDeceasedMap); ?>;
      const informantValidityMap = <?php echo json_encode($informantValidityMap); ?>;
      const payeeInput = document.getElementById('formName');
      const aptInput = document.getElementById('formApartmentNo');
      const deceasedInput = document.getElementById('formDeceased');
      const deceasedMatches = document.getElementById('deceasedMatches');
      const nicheMatches = document.getElementById('nicheMatches');
      const validityInput = document.getElementById('formValidity');

      // Helper: case-insensitive safe lookup for maps (returns array or value)
      function getArrayForInformant(map, name) {
        if (!name || !map) return [];
        if (map[name]) return map[name];
        const lower = name.toLowerCase();
        for (const k in map) {
          if (Object.prototype.hasOwnProperty.call(map, k) && String(k).toLowerCase() === lower) {
            return map[k];
          }
        }
        return [];
      }
      function getValueForInformant(map, name) {
        if (!name || !map) return '';
        if (map[name]) return map[name];
        const lower = name.toLowerCase();
        for (const k in map) {
          if (Object.prototype.hasOwnProperty.call(map, k) && String(k).toLowerCase() === lower) {
            return map[k];
          }
        }
        return '';
      }

      // Helper: render deceased matches (single -> fill input, multiple -> show list)
      function showDeceasedNames(names) {
        if (!deceasedInput || !deceasedMatches) return;
        // nothing found -> clear
        if (!names || names.length === 0) {
          deceasedMatches.innerHTML = '';

          deceasedMatches.style.display = 'none';
          return;
        }
        // single -> fill and hide list
        if (names.length === 1) {
          deceasedInput.value = names[0];
          deceasedMatches.innerHTML = '';
          deceasedMatches.style.display = 'none';
          return;
        }
        // multiple -> build selectable list, but expand duplicate names into per-niche rows

        deceasedMatches.innerHTML = '';
        const entries = [];
        names.forEach(function(dname) {
          // try to find which niche(s) this deceased appears in
          let aps = [];
          try {
            aps = Object.keys(nicheDeceasedMap).filter(function(k) {
              return Array.isArray(nicheDeceasedMap[k]) && nicheDeceasedMap[k].indexOf(dname) !== -1;
            });
          } catch (e) { aps = []; }
          // if no niche info, keep a single general entry
          if (!aps || aps.length === 0) {
            entries.push({ name: dname, niche: '' });
          } else {
            // create one entry per niche so same-name/different-niche shows separately
            aps.forEach(function(ap) {
              entries.push({ name: dname, niche: ap });
            });
          }
        });
        // Render entries
        entries.forEach(function(entry) {
          const item = document.createElement('div');
          item.className = 'deceased-match-item';
          item.style.cssText = 'padding:8px 10px;cursor:pointer;border-radius:6px;margin-bottom:6px;display:flex;justify-content:space-between;align-items:center;';
          const left = document.createElement('div');
          // show name and small niche meta if available
          left.innerHTML = '<strong style="display:block;">' + escapeHtml(entry.name) + '</strong>';
          if (entry.niche) {
            left.innerHTML += '<div style="font-size:0.85rem;color:#6b7280;margin-top:4px;">' + escapeHtml(entry.niche) + '</div>';
          }
          const right = document.createElement('div');
          right.style.cssText = 'font-size:0.85rem;color:#506C84;';
          right.textContent = 'Select';
          item.appendChild(left);
          item.appendChild(right);
          item.dataset.name = entry.name;
          item.dataset.niche = entry.niche || '';
          // click: choose this deceased; if niche present, also set apt input for clarity
          item.addEventListener('click', function() {
            deceasedInput.value = this.dataset.name || '';
            // populate Apt No if niche known
            try {
              if (this.dataset.niche && aptInput) aptInput.value = this.dataset.niche;
            } catch (e) { /* ignore */ }
            deceasedMatches.style.display = 'none';
            deceasedMatches.innerHTML = '';
          });
          // hover style
          item.addEventListener('mouseenter', function(){ this.style.background = '#f6fbff'; });
          item.addEventListener('mouseleave', function(){ this.style.background = 'transparent'; });
          deceasedMatches.appendChild(item);
        });
        // position and show
        deceasedMatches.style.display = 'block';
        const rect = deceasedInput.getBoundingClientRect();
        deceasedMatches.style.left = (deceasedInput.offsetLeft) + 'px';
        deceasedMatches.style.top = (deceasedInput.offsetTop + deceasedInput.offsetHeight + 6) + 'px';
        deceasedMatches.style.minWidth = Math.max(220, deceasedInput.offsetWidth) + 'px';
      }

      // small helper to escape inserted text (innerHTML usage above)
      function escapeHtml(str) {
        return String(str).replace(/[&<>"']/g, function(m){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]; });
      }
 
     // Populate deceased by niche first (exact apt match). If none, fallback to informant mapping using payee name.
     // New behavior: only display deceased that are related to the given payee/informant.
     function populateDeceasedForAptOrPayee(nicheID, payeeName) {
        const n = (nicheID || '').toString().trim();
        const pn = (payeeName || '').toString().trim();

        // Step A: collect deceased names known for the payee (case-insensitive lookup)
        const payeeDeceased = getArrayForInformant(informantDeceasedMap, pn); // [] if none

        // If we have a niche specific lookup
        if (n) {
          const nicheNames = (nicheDeceasedMap[n] && Array.isArray(nicheDeceasedMap[n])) ? nicheDeceasedMap[n] : [];

          // If payee has known deceased names, only show intersection (names in this niche that belong to payee)
          if (payeeDeceased && payeeDeceased.length > 0) {
            const intersection = nicheNames.filter(name => payeeDeceased.indexOf(name) !== -1);
            if (intersection.length > 0) {
              showDeceasedNames(intersection);
              return;
            }
            // No intersection: the user explicitly asked that we show only deceased related to that payee.
            // So show payeeDeceased (even if they aren't listed under this niche), rather than showing unrelated niche occupants.
            showDeceasedNames(payeeDeceased);
            return;
          }

          // If no payee mapping available, fall back to showing niche occupants (old behavior)
          showDeceasedNames(nicheNames);
          return;
        }

        // No niche provided: show only deceased associated with the payee (if any)
        if (payeeDeceased && payeeDeceased.length > 0) {
          showDeceasedNames(payeeDeceased);
          return;
        }

        // Nothing found
        showDeceasedNames([]);
      }

      // Adjust payee input handler to use case-insensitive lookups and the updated populate function
      payeeInput.addEventListener('change', function() {
        const name = this.value.trim();
        // use case-insensitive lookup for nicheIDs too
        let nicheIDs = [];
        if (name) {
          if (informantNicheMap[name]) nicheIDs = informantNicheMap[name];
          else {
            const lower = name.toLowerCase();
            for (const k in informantNicheMap) {
              if (Object.prototype.hasOwnProperty.call(informantNicheMap, k) && String(k).toLowerCase() === lower) {
                nicheIDs = informantNicheMap[k];
                break;
              }
            }
          }
        }

        if (nicheIDs.length === 1) {
          aptInput.value = nicheIDs[0];
          if (nicheMatches) { nicheMatches.style.display = 'none'; nicheMatches.innerHTML = ''; }
        } else if (nicheIDs.length > 1) {
          // Populate nicheMatches with clickable rows showing "NicheID + DeceasedName"
          if (!nicheMatches) return;
          nicheMatches.innerHTML = '';
          const listWrap = document.createElement('div');
          listWrap.style.cssText = 'background:#fff;border-radius:8px;padding:6px;max-height:240px;overflow:auto;';
          nicheIDs.forEach(function(nicheID) {
            const names = (nicheDeceasedMap[nicheID] && Array.isArray(nicheDeceasedMap[nicheID])) ? nicheDeceasedMap[nicheID] : [];
            let label = nicheID;
            if (names.length > 0) {
              label += ' ' + names[0];
              if (names.length > 1) label += ' +' + (names.length - 1);
            }
            const row = document.createElement('div');
            row.style.cssText = 'padding:8px 10px;cursor:pointer;border-radius:6px;margin-bottom:6px;display:flex;justify-content:space-between;align-items:center;';
            row.innerHTML = '<div style="font-size:0.95rem;color:#111;">' + escapeHtml(label) + '</div><div style="font-size:0.85rem;color:#506C84;">Select</div>';
            row.dataset.value = nicheID;
            if (names.length) row.dataset.deceased = names.join('|');
            row.addEventListener('click', function() {
              aptInput.value = this.dataset.value || '';
              nicheMatches.style.display = 'none';
              nicheMatches.innerHTML = '';
              // When user selects a niche, populate deceased suggestions for that apt — filtered by current payee
              populateDeceasedForAptOrPayee(aptInput.value, payeeInput.value);
            });
            row.addEventListener('mouseenter', function(){ this.style.background = '#f6fbff'; });
            row.addEventListener('mouseleave', function(){ this.style.background = 'transparent'; });
            listWrap.appendChild(row);
          });
          nicheMatches.appendChild(listWrap);
          // Position and show
          nicheMatches.style.display = 'block';
          nicheMatches.style.position = 'absolute';
          nicheMatches.style.left = aptInput.offsetLeft + 'px';
          nicheMatches.style.top = (aptInput.offsetTop + aptInput.offsetHeight + 2) + 'px';
          nicheMatches.style.minWidth = Math.max(220, aptInput.offsetWidth) + 'px';
        } else {
          if (nicheMatches) { nicheMatches.style.display = 'none'; nicheMatches.innerHTML = ''; }
        }

        // Deceased names: use case-insensitive lookup for informant -> deceased
        const deceasedNames = getArrayForInformant(informantDeceasedMap, name);
        showDeceasedNames(deceasedNames);

        // Prefer niche-based lookup (if apt is already filled), otherwise fallback to informant mapping
        const currentApt = (aptInput.value || '').toString().trim();
        populateDeceasedForAptOrPayee(currentApt, name);

        // Validity: apply if present (case-insensitive lookup)
        const validity = getValueForInformant(informantValidityMap, name);
        validityInput.value = validity || '';
      });

      // When Apt No is manually changed, update deceased accordingly
     aptInput.addEventListener('change', function() {
             const val = (this.value || '').toString().trim();
       populateDeceasedForAptOrPayee(val, payeeInput.value);
     });
     aptInput.addEventListener('blur', function() {
       // also handle blur to catch typed values
       const val = (this.value || '').toString().trim();
       populateDeceasedForAptOrPayee(val, payeeInput.value);
     });
 
      // Hide dropdowns if clicking elsewhere
      document.addEventListener('mousedown', function(e) {
        if (nicheMatches && !nicheMatches.contains(e.target) && e.target !== aptInput && e.target !== payeeInput) {
          nicheMatches.style.display = 'none';
        }
        // deceased matches container
        if (deceasedMatches && !deceasedMatches.contains(e.target) && e.target !== deceasedInput && e.target !== payeeInput) {
          deceasedMatches.style.display = 'none';
        }
      });
 
       // --- Amount field auto-format with commas ---
      const amountInput = document.getElementById('formAmount');
      function formatPesoAmount(value) {
        // Remove non-numeric except dot
        value = value.replace(/[^\d.]/g, '');
        // Split integer and decimal
        let parts = value.split('.');
        let intPart = parts[0];
        let decPart = parts[1] || '';
        // Format integer part with commas
        intPart = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        // Limit to 2 decimal places
        if (decPart.length > 2) decPart = decPart.slice(0,2);
        return decPart ? intPart + '.' + decPart : intPart;
      }
      amountInput.addEventListener('input', function(e) {
        let cursorPos = this.selectionStart;
        let raw = this.value.replace(/[^\d.]/g, '');
        let formatted = formatPesoAmount(raw);
        this.value = formatted;
        // Try to restore cursor position (best effort)
        this.setSelectionRange(this.value.length, this.value.length);
      });
      amountInput.addEventListener('blur', function() {
        this.value = formatPesoAmount(this.value);
      });

      // --- Add 5 years to Validity if Renewal is chosen ---
      const descriptionInput = document.getElementById('formDescription');
      descriptionInput.addEventListener('change', function() {
        // If Renewal is chosen and validity exists, add 5 years and autofill
        if (this.value === 'Renewal' && validityInput.value) {
          const oldDate = validityInput.value;
          // Only add if valid date
          if (/^\d{4}-\d{2}-\d{2}$/.test(oldDate)) {
            const d = new Date(oldDate);
            d.setFullYear(d.getFullYear() + 5);
            // Format as yyyy-mm-dd
            const yyyy = d.getFullYear();
            const mm = String(d.getMonth() + 1).padStart(2, '0');
            const dd = String(d.getDate()).padStart(2, '0');
            validityInput.value = `${yyyy}-${mm}-${dd}`;
          }
        }
      });
    </script>
    <!-- ...existing code... -->
</body>
</html>
<?php
// Handle deletion POST for ledger
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_ids']) && is_array($_POST['delete_ids'])) {
  include_once '../Includes/db.php';
  if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }
  $deleteIds = array_map('intval', $_POST['delete_ids']);
  $placeholders = str_repeat('?,', count($deleteIds) - 1) . '?';
  $stmt = $conn->prepare("DELETE FROM ledger WHERE id IN ($placeholders)");
  $stmt->bind_param(str_repeat('i', count($deleteIds)), ...$deleteIds);
  $stmt->execute();
  $conn->close();
  exit; // For AJAX, no redirect
}

// --- Handle AJAX report request ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
  $input = json_decode(file_get_contents('php://input'), true);
  if (isset($input['report']) && !empty($input['start']) && !empty($input['end'])) {
    include_once '../Includes/db.php';
    $start = $conn->real_escape_string($input['start']);
    $end = $conn->real_escape_string($input['end']);
    $sql = "SELECT SUM(Amount) as total FROM ledger WHERE DatePaid >= '$start' AND DatePaid <= '$end'";
    $result = $conn->query($sql);
    $total = 0;
    if ($result && $row = $result->fetch_assoc()) {
      $total = floatval($row['total']);
    }
    header('Content-Type: application/json');
    echo json_encode(['total' => $total]);
    exit;
  }
}
?>
<!-- last -->




