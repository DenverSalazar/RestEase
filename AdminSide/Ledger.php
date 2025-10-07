<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../AdminLogin.php");
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
    $amount = str_replace([',', '₱', ' '], '', $_POST['Amount']);
    $orNumber = $_POST['ORNumber'];
    $mcNo = $_POST['MCNo'];
    $validity = $_POST['Validity'];
    $description = $_POST['Description'];
    $datePaid = isset($_POST['DatePaid']) ? $_POST['DatePaid'] : null;
    if ($id) {
        // Update existing
        $stmt = $conn->prepare("UPDATE ledger SET ApartmentNo=?, Payee=?, Amount=?, ORNumber=?, MCNo=?, Validity=?, Description=?, DatePaid=? WHERE id=?");
        $stmt->bind_param('ssdsssssi', $apartmentNo, $payee, $amount, $orNumber, $mcNo, $validity, $description, $datePaid, $id);
        $stmt->execute();
        $stmt->close();
    } else {
        // Insert new
        $stmt = $conn->prepare("INSERT INTO ledger (ApartmentNo, Payee, Amount, ORNumber, MCNo, Validity, Description, DatePaid) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssdsssss', $apartmentNo, $payee, $amount, $orNumber, $mcNo, $validity, $description, $datePaid);
        $stmt->execute();
        $stmt->close();
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
$validity = isset($_GET['validity']) ? htmlspecialchars($_GET['validity']) : '';
if (!$validity) {
  $validity = date('Y-m-d', strtotime('+5 years'));
}
$orNumber = '';
$mcNumber = '';
if (($apartment || $informant) && empty($ledgerEntry['ORNumber'])) {
  $orNumber = generateUniqueORNumber($conn);
}
if (($apartment || $informant) && empty($ledgerEntry['MCNo'])) {
  $mcNumber = generateUniqueMCNumber($conn);
}
// For walk-in clients (no URL parameters), also generate numbers
if (!$apartment && !$informant && !$ledgerEntry) {
  $orNumber = generateUniqueORNumber($conn);
  $mcNumber = generateUniqueMCNumber($conn);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>RestEase Ledger</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/Ledger.css">
  <link rel="stylesheet" href="../css/sidebar.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
  <style>

  </style>
</head>
<body>
  <!-- Sidebar -->
  <?php include '../Includes/sidebar.php'; ?>

  <!-- Main Content -->
  <main class="main-content">
    <!-- Page Header -->
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
      <div>
        <h1 style="font-size:2rem;font-weight:700;margin-bottom:0;">Ledger</h1>
        <p style="font-size:1.04rem;color:#6b7280;">Fill up the ledger information</p>
      </div>
    </div>
    <!-- Search Bar + Buttons Row -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
      <div class="search-container" style="flex:1;max-width:420px;">
        <i class="fas fa-search"></i>
        <input type="text" id="ledger-search-input" placeholder="Search Payment Details" style="font-family:'Poppins',sans-serif;">
      </div>
      <div style="display:flex;gap:10px;align-items:center;margin-left:18px;">
        <button id="importExcelBtn" style="background:#22c55e;color:#fff;border:none;padding:8px 18px;border-radius:7px;font-weight:500;display:flex;align-items:center;gap:8px;cursor:pointer;">
          <i class="fas fa-file-import"></i> Import Data
        </button>
        <button id="exportExcelBtn" style="background:#2563eb;color:#fff;border:none;padding:8px 18px;border-radius:7px;font-weight:500;display:flex;align-items:center;gap:8px;cursor:pointer;">
          <i class="fas fa-file-excel"></i> Export Data
        </button>
        <button id="ledgerDeleteBtn" type="button" style="background:#e74c3c;color:#fff;border:none;padding:8px 18px;border-radius:7px;font-weight:500;display:flex;align-items:center;gap:8px;cursor:pointer;">
          <i class="fas fa-trash"></i> Delete
        </button>
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
      // Import Excel Modal logic for Ledger
      document.getElementById('importExcelBtn').onclick = function() {
        document.getElementById('ledgerExcelModal').style.display = 'flex';
      };
      document.getElementById('closeLedgerModal').onclick = function() {
        document.getElementById('ledgerExcelModal').style.display = 'none';
      };
      document.getElementById('cancelLedgerBtn').onclick = function() {
        document.getElementById('ledgerExcelModal').style.display = 'none';
      };
      document.getElementById('ledgerExcelModal').onclick = function(e) {
        if (e.target === this) this.style.display = 'none';
      };
      document.getElementById('ledgerFileInput').onchange = function() {
        const fileName = this.files[0] ? this.files[0].name : 'No file selected';
        document.querySelector('#ledgerExcelModal .file-name').textContent = fileName;
      };
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
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px 32px;margin-bottom:18px;">
          <div style="display:flex;flex-direction:column;gap:8px;">
            <label for="formName" style="font-weight:500;">Payee</label>
            <input type="text" id="formName" name="Payee" required placeholder="<?php echo $informant ? $informant : 'Name'; ?>" style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid #d1d5db;background:#fff;font-size:1rem;" value="<?php echo htmlspecialchars($ledgerEntry['Payee'] ?? $informant); ?>" autocomplete="off" list="payeeNameList">
            <datalist id="payeeNameList">
              <?php foreach ($payeeSuggestions as $suggestion): ?>
                <option value="<?php echo htmlspecialchars($suggestion); ?>"></option>
              <?php endforeach; ?>
            </datalist>
          </div>
          <div style="display:flex;flex-direction:column;gap:8px;position:relative;">
            <label for="formApartmentNo" style="font-weight:500;">Apt No.</label>
            <input type="text" id="formApartmentNo" name="ApartmentNo" placeholder="<?php echo $apartment ? $apartment : 'e.g. A-101'; ?>" style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid #d1d5db;background:#fff;font-size:1rem;" value="<?php echo htmlspecialchars($ledgerEntry['ApartmentNo'] ?? $apartment); ?>">
            <!-- NicheID dropdown (hidden by default) -->
            <select id="nicheDropdown"></select>
          </div>
          <div style="display:flex;flex-direction:column;gap:8px;">
            <label for="formDatePaid" style="font-weight:500;">Date Paid</label>
            <input type="date" id="formDatePaid" name="DatePaid" style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid #d1d5db;background:#fff;font-size:1rem;" value="<?php echo htmlspecialchars($ledgerEntry['DatePaid'] ?? ''); ?>">
          </div>
          <div style="display:flex;flex-direction:column;gap:8px;">
            <label for="formAmount" style="font-weight:500;">Amount</label>
            <div style="position:relative;">
              <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#888;font-size:1.08rem;">₱</span>
              <input type="text" id="formAmount" name="Amount" required placeholder="0.00" style="width:104.5%;box-sizing:border-box;padding-left:28px;padding-right:12px;padding-top:10px;padding-bottom:10px;border-radius:8px;border:1px solid #d1d5db;background:#fff;font-size:1rem;" value="<?php echo isset($ledgerEntry['Amount']) ? number_format($ledgerEntry['Amount'], 2) : ''; ?>">
            </div>
          </div>
        </div>
        <!-- Section: Details -->
        <div style="font-weight:600;font-size:1.08rem;margin-bottom:18px;margin-top:18px;">Details</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px 32px;">
          <div style="display:flex;flex-direction:column;gap:8px;">
            <label for="formDescription" style="font-weight:500;">Description / Type</label>
            <input
              type="text"
              id="formDescription"
              name="Description"
              list="descOptions"
              required
              placeholder="Description"
              style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid #d1d5db;background:#fff;font-size:1rem;"
              value="<?php echo htmlspecialchars($ledgerEntry['Description'] ?? ''); ?>"
            >
            <datalist id="descOptions">
              <option value="New">
              <option value="Renewal">
              <option value="ReOpen">
              <option value="Transfer">
              <option value="Full Payment">
            </datalist>
          </div>
          <div style="display:flex;flex-direction:column;gap:8px;">
            <label for="formValidity" style="font-weight:500;">Validity</label>
            <input type="date" id="formValidity" name="Validity" style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid #d1d5db;background:#fff;font-size:1rem;" value="<?php echo htmlspecialchars($ledgerEntry['Validity'] ?? $validity); ?>">
          </div>
          <div style="display:flex;flex-direction:column;gap:8px;">
            <label for="formORNumber" style="font-weight:500;">OR Number</label>
            <input type="text" id="formORNumber" name="ORNumber" placeholder="Official Receipt No." style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid #d1d5db;background:#f3f4f6;font-size:1rem;" value="<?php echo isset($ledgerEntry['ORNumber']) ? htmlspecialchars($ledgerEntry['ORNumber']) : htmlspecialchars($orNumber); ?>" readonly>
          </div>
          <div style="display:flex;flex-direction:column;gap:8px;">
            <label for="formMCNo" style="font-weight:500;">MC No.</label>
            <input type="text" id="formMCNo" name="MCNo" placeholder="MC No. (optional)" style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid #d1d5db;background:#fff;font-size:1rem;"
              value="<?php echo isset($ledgerEntry['MCNo']) && $ledgerEntry['MCNo'] !== null ? htmlspecialchars($ledgerEntry['MCNo']) : htmlspecialchars($mcNumber); ?>">
          </div>
        </div>
        <div style="margin-top:32px;text-align:right;border-top:1px solid #f0f0f0;padding-top:24px;">
          <button type="submit" class="btn upload" style="width: 140px; padding: 12px 0; font-size:1.08rem;background:#506C84;color:#fff;border-radius:8px;">Submit</button>
        </div>
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($ledgerEntry['id'] ?? ''); ?>">
      </form>
    </div>
    <!-- Payment Details Section (now comes second, hidden by default) -->
    <div id="paymentDetailsSection" class="card ledger-table-container" style="max-width: 100%; background: #fff; border-radius: 16px; box-shadow: 0 2px 8px rgba(44,62,80,0.08); padding: 0 0 32px 0; box-sizing: border-box; display:none; margin-top: 18px;">
      <div style="display: flex; justify-content: space-between; align-items: center; padding: 32px 32px 0 32px; margin-bottom:24px;">
        <span style="font-size:1.25rem;font-weight:600;letter-spacing:0.5px;">Payment Details</span>
        <!-- Removed buttons from here -->
      </div>
      <form id="ledgerDeleteForm" method="post" style="margin:0;">
        <div style="overflow-x:auto; padding: 0 32px;">
          <table class="ledger-table" id="paymentDetailsTable" style="min-width:1100px;">
            <thead>
              <tr>
                <th>Apt No.</th>
                <th>Payee</th>
                <th>Date Paid</th>
                <th>Amount</th>
                <th>Description</th>
                <th>Validity</th>
                <th>OR Number</th>
                <th>MC No.</th>
                <th>Action</th>
                <th id="ledgerDeleteTh" style="display:none;">
                  <input type="checkbox" id="ledgerSelectAllCheckbox" style="display:none;">
                </th>
              </tr>
            </thead>
            <tbody>
              <?php
              // Fetch payment details (where DatePaid is NOT NULL and not empty)
              $paymentResult = $conn->query("SELECT * FROM ledger WHERE DatePaid IS NOT NULL AND DatePaid != '' ORDER BY DatePaid DESC");
              if ($paymentResult && $paymentResult->num_rows > 0) {
                while ($row = $paymentResult->fetch_assoc()) {
                  echo '<tr>';
                  echo '<td>' . htmlspecialchars($row['ApartmentNo'] ?? '') . '</td>';
                  echo '<td>' . htmlspecialchars($row['Payee']) . '</td>';
                  echo '<td>' . htmlspecialchars($row['DatePaid']) . '</td>';
                  echo '<td>₱' . number_format($row['Amount'], 2) . '</td>';
                  echo '<td>' . htmlspecialchars($row['Description']) . '</td>';
                  echo '<td>' . htmlspecialchars($row['Validity']) . '</td>';
                  echo '<td>' . htmlspecialchars($row['ORNumber']) . '</td>';
                  // MCNo can be null, show empty string if so
                  echo '<td>' . (isset($row['MCNo']) && $row['MCNo'] !== null ? htmlspecialchars($row['MCNo']) : '') . '</td>';
                  // Action button
                  echo '<td><a href="insert.php?id=' . $row['id'] . '" class="action-btn" style="background:#2563eb;color:#fff;padding:4px 12px;border-radius:6px;text-decoration:none;font-weight:400;font-size:0.92rem;display:inline-block;">Insert</a></td>';
                  echo '<td><input type="checkbox" class="ledger-delete-checkbox" name="delete_ids[]" value="' . $row['id'] . '"></td>';
                  echo '</tr>';
                }
              }
              ?>
            </tbody>
          </table>
        </div>
      </form>
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
    <style>
      @media (max-width: 900px) {
        #ledgerInfoSection {
          max-width: 100vw !important;
          padding: 24px 8px 24px 8px !important;
        }
        #ledgerInfoSection form > div[style*="grid-template-columns"] {
          grid-template-columns:1fr !important;
          gap:18px 0 !important;
        }
      }
    </style>
    <!-- Success Popup Modal for Ledger Information submission -->
    <?php if ($showLedgerSuccessModal): ?>
    <div id="ledgerSuccessModal" class="modal-overlay" style="position:fixed;z-index:9999;left:0;top:0;right:0;bottom:0;background:rgba(44,62,80,0.18);display:flex;align-items:center;justify-content:center;">
      <div class="modal-content" style="background:#fff;border-radius:16px;box-shadow:0 8px 32px rgba(60,60,60,0.18),0 1.5px 6px rgba(0,0,0,0.08);padding:32px 32px 24px 32px;min-width:340px;max-width:95vw;text-align:center;position:relative;">
        <div class="modal-header">
          <i class="fas fa-check-circle" style="color:#2ecc71;font-size:2.5rem;margin-bottom:8px;"></i>
          <h2 style="color:#2ecc71;margin:0;font-size:1.3rem;">Success!</h2>
        </div>
        <div class="modal-body" style="margin:18px 0 24px 0;">
          <p style="color:#444;font-size:1.07rem;margin:0;">
            Ledger Information has been submitted successfully.
          </p>
        </div>
        <div class="modal-footer" style="display:flex;justify-content:center;gap:16px;">
          <button id="ledgerSuccessModalCloseBtn" style="background:#506C84;color:#fff;border:none;padding:12px 32px;border-radius:8px;cursor:pointer;font-weight:500;font-size:1rem;">OK</button>
        </div>
      </div>
    </div>
    <script>
      document.getElementById('ledgerSuccessModalCloseBtn').onclick = function() {
        window.location.href = "Ledger.php";
      };
      document.getElementById('ledgerSuccessModal').onclick = function(e) {
        if (e.target === this) window.location.href = "Ledger.php";
      };
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
        // Remove checkboxes column (now index 8)
        Array.from(clone.querySelectorAll('tr')).forEach(function(row) {
          if (row.cells.length > 8) row.deleteCell(8);
        });
        // Remove time from Date Paid and Validity columns
        Array.from(clone.querySelectorAll('tbody tr')).forEach(function(row) {
          // Date Paid is column 2, Validity is column 5 (0-based)
          var datePaidCell = row.cells[2];
          var validityCell = row.cells[5];
          if (datePaidCell) {
            datePaidCell.textContent = datePaidCell.textContent.split(' ')[0];
          }
          if (validityCell) {
            validityCell.textContent = validityCell.textContent.split(' ')[0];
          }
        });
        var wb = XLSX.utils.table_to_book(clone, {sheet:"Payment Details"});
        XLSX.writeFile(wb, 'PaymentDetails.xlsx');
      });

      // Ledger delete logic (copied/adapted from Records.php)
      const ledgerDeleteBtn = document.getElementById('ledgerDeleteBtn');
      const ledgerTable = document.getElementById('paymentDetailsTable');
      const ledgerDeleteCheckboxes = ledgerTable.querySelectorAll('.ledger-delete-checkbox');
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
          ledgerTable.querySelectorAll('.ledger-delete-checkbox').forEach(cb => cb.style.display = '');
          if (ledgerSelectAllCheckbox) ledgerSelectAllCheckbox.style.display = '';
          if (ledgerDeleteTh) ledgerDeleteTh.style.display = '';
        } else {
          ledgerTable.querySelectorAll('.ledger-delete-checkbox').forEach(cb => { cb.checked = false; cb.style.display = 'none'; });
          if (ledgerSelectAllCheckbox) { ledgerSelectAllCheckbox.checked = false; ledgerSelectAllCheckbox.style.display = 'none'; }
          if (ledgerDeleteTh) ledgerDeleteTh.style.display = 'none';
        }
      }
      setLedgerDeleteMode(false);

      // Select All logic
      if (ledgerSelectAllCheckbox) {
        ledgerSelectAllCheckbox.addEventListener('change', function() {
          const checkboxes = ledgerTable.querySelectorAll('.ledger-delete-checkbox');
          checkboxes.forEach(cb => cb.checked = ledgerSelectAllCheckbox.checked);
        });
        ledgerTable.addEventListener('change', function(e) {
          if (e.target.classList.contains('ledger-delete-checkbox')) {
            const checkboxes = ledgerTable.querySelectorAll('.ledger-delete-checkbox');
            const checked = ledgerTable.querySelectorAll('.ledger-delete-checkbox:checked');
            ledgerSelectAllCheckbox.checked = (checkboxes.length > 0 && checked.length === checkboxes.length);
          }
        });
      }

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
        .then(data => {
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
      const payeeInput = document.getElementById('formName');
      const aptInput = document.getElementById('formApartmentNo');
      const nicheDropdown = document.getElementById('nicheDropdown');

      payeeInput.addEventListener('change', function() {
        const name = this.value.trim();
        const nicheIDs = informantNicheMap[name] || [];
        if (nicheIDs.length === 1) {
          aptInput.value = nicheIDs[0];
          nicheDropdown.style.display = 'none';
        } else if (nicheIDs.length > 1) {
          // Populate dropdown
          nicheDropdown.innerHTML = '';
          nicheIDs.forEach(function(nicheID) {
            const option = document.createElement('option');
            option.value = nicheID;
            option.textContent = nicheID;
            nicheDropdown.appendChild(option);
          });
          // Position dropdown below Apt No. input
          nicheDropdown.style.display = 'block';
          nicheDropdown.style.position = 'absolute';
          nicheDropdown.style.left = aptInput.offsetLeft + 'px';
          nicheDropdown.style.top = (aptInput.offsetTop + aptInput.offsetHeight + 2) + 'px';
          nicheDropdown.size = Math.min(nicheIDs.length, 6);
        } else {
          nicheDropdown.style.display = 'none';
        }
      });

      nicheDropdown.addEventListener('change', function() {
        aptInput.value = this.value;
        nicheDropdown.style.display = 'none';
      });

      // Hide dropdown if clicking elsewhere
      document.addEventListener('mousedown', function(e) {
        if (!nicheDropdown.contains(e.target) && e.target !== aptInput && e.target !== payeeInput) {
          nicheDropdown.style.display = 'none';
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
?>





