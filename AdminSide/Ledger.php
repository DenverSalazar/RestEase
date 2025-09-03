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

if (isset($_GET['id'])) {
    $entry_id = $_GET['id'];
    // Join ledger with qr_codes to get the path
    $stmt = $conn->prepare("SELECT l.*, qc.qr_code_path FROM ledger l LEFT JOIN qr_codes qc ON l.id = qc.ledger_id WHERE l.id = ?");
    $stmt->bind_param("i", $entry_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $ledgerEntry = $result->fetch_assoc();
    }
    $stmt->close();
}

// Handle QR code image upload and saving to admin_qr_code table
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['QRCodeImg']) && $_FILES['QRCodeImg']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/';
        $ext = pathinfo($_FILES['QRCodeImg']['name'], PATHINFO_EXTENSION);
        $fileName = time() . '_qr.' . $ext;
        $targetPath = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['QRCodeImg']['tmp_name'], $targetPath)) {
        $qrPathDb = 'uploads/' . $fileName;
        // Remove old QR code row and file
        $result = $conn->query("SELECT qr_code_path FROM admin_qr_code ORDER BY id DESC LIMIT 1");
        if ($result && $row = $result->fetch_assoc()) {
            $oldPath = '../' . $row['qr_code_path'];
            if (file_exists($oldPath)) { unlink($oldPath); }
        }
        $conn->query("DELETE FROM admin_qr_code");
        $stmt = $conn->prepare("INSERT INTO admin_qr_code (qr_code_path) VALUES (?)");
        $stmt->bind_param("s", $qrPathDb);
        $stmt->execute();
        echo '<script>alert("QR Code updated successfully!"); window.location.href="Ledger.php";</script>';
        exit;
    } else {
        echo '<div style="color:#e74c3c;margin-top:16px;">Error moving uploaded file.</div>';
    }
}

// Handle Ledger Form Submission (Insert or Update)
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['ApartmentNo']) && isset($_POST['Payee']) && isset($_POST['Amount']) &&
    trim($_POST['ApartmentNo']) !== '' &&
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
    echo '<script>alert("Ledger information saved successfully!"); window.location.href="Ledger.php";</script>';
    exit;
} else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ApartmentNo']) && isset($_POST['Payee'])) {
    echo '<script>alert("Please select an accepted request first using Go to Payment."); window.location.href="Ledger.php";</script>';
    exit;
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
  // Get the highest MC No. and increment by 1, starting from 0
  $result = $conn->query("SELECT MAX(CAST(MCNo AS UNSIGNED)) as max_mc FROM ledger WHERE MCNo REGEXP '^[0-9]+$'");
  $maxMC = -1; // Start from -1 so first number will be 0
  if ($result && $row = $result->fetch_assoc()) {
    $maxMC = intval($row['max_mc']);
  }
  return strval($maxMC + 1);
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
  <link rel="stylesheet" href="../css/Clients.css">
  <link rel="stylesheet" href="../css/sidebar.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
  <style>
    /* ...existing code... */
    .ledger-header h1 {
      font-size: 2rem;
      font-weight: 700;
      margin-bottom: 0px;
    }
    .ledger-header .subtitle {
      font-size: 1.04rem;
      color: #6b7280;
      margin-bottom: 18px;
    }
    .ledger-table-container {
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.04);
      margin-top: 18px;
      overflow: visible;
      border: 1px solid #ececec;
    }
    .ledger-table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
      background: #fff;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 1px 4px rgba(0,0,0,0.04);
      margin-bottom: 1rem;
      font-family: 'Poppins', sans-serif;
    }
    .ledger-table th, .ledger-table td {
      padding: 10px 12px;
      text-align: left;
      font-size: 0.98rem;
      border-bottom: 1px solid #eee;
      background: #fff;
      font-family: 'Poppins', sans-serif;
    }
    .ledger-table th {
      background: #f7f8fa;
      font-weight: 500;
      color: #333;
    }
    .ledger-table tr:last-child td {
      border-bottom: none;
    }
    .ledger-table tr:hover {
      background: #f5f7fa;
    }
    .ledger-search-container {
      flex: 1 1 320px;
      max-width: 420px;
      display: flex;
      align-items: center;
      background: #fff;
      border-radius: 10px;
      border: 1px solid #ececec;
      padding: 0 16px;
      height: 40px;
      min-width: 320px;
      box-shadow: 0 1px 4px rgba(60,72,88,0.03);
      margin-bottom: 18px;
    }
    .ledger-search-container i {
      color: #b0b0b0;
      margin-right: 8px;
      font-size: 1.1rem;
    }
    .ledger-search-container input {
      border: none;
      background: transparent;
      outline: none;
      font-size: 1.04rem;
      width: 100%;
      color: #222;
      font-weight: 400;
      padding: 0;
      margin: 0;
    }
    .ledger-search-container input::placeholder {
      color: #b0b0b0;
      font-weight: 400;
      opacity: 1;
    }
    @media (max-width: 1100px) {
      .main-content {
        min-width: 0;
        max-width: 100vw;
        padding: 24px 8px 0 8px;
      }
      .ledger-header h1 {
        font-size: 1.3rem;
      }
      .ledger-table th, .ledger-table td {
        padding: 10px 6px;
        font-size: 0.95rem;
      }
      .ledger-search-container {
        min-width: 0;
        padding: 0 8px;
        height: 34px;
      }
    }
    .tab {
      background: none;
      border: none;
      font-size: 1.08rem;
      padding: 16px 0 12px 0;
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
    <!-- Tabs -->
    <div style="border-bottom:1px solid #e0e0e0;margin-bottom:8px;">
      <div style="display:flex;gap:32px;align-items:center;">
        <button id="ledgerTabBtn" class="tab active">Ledger Information</button>
        <button id="pendingPaymentTabBtn" class="tab">Pending Payment</button>
        <button id="paymentTabBtn" class="tab">Payment Details</button>
      </div>
    </div>
    <!-- Ledger Information Section -->
    <div id="ledgerInfoSection" class="card" style="width: 100%; max-width: 100%; background: #fff; border-radius: 16px; box-shadow: 0 2px 8px rgba(44,62,80,0.08); padding: 32px 32px 32px 32px; box-sizing: border-box;">
      <div style="font-size:1.25rem;font-weight:600;margin-bottom:24px;letter-spacing:0.5px;">Ledger Information</div>
        <div style="display:flex;gap:30px;flex-wrap:wrap;width:100%;align-items:flex-start;">
          <!-- Left Column: Ledger Fields -->
          <div style="flex:1 1 600px;">
          <form id="ledgerForm" method="post" action="" enctype="multipart/form-data" autocomplete="off" style="width: 100%;">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($ledgerEntry['id'] ?? ''); ?>">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px 16px;">
              <div>
                <label for="formApartmentNo" style="font-weight:500;">Apartment No.</label>
                <input type="text" id="formApartmentNo" name="ApartmentNo" required placeholder="<?php echo $apartment ? $apartment : 'e.g. A-101'; ?>" style="width:100%;box-sizing:border-box;" value="<?php echo htmlspecialchars($ledgerEntry['ApartmentNo'] ?? $apartment); ?>">
              </div>
              <div>
                <label for="formName" style="font-weight:500;">Name</label>
                <input type="text" id="formName" name="Payee" required placeholder="<?php echo $informant ? $informant : 'Name'; ?>" style="width:100%;box-sizing:border-box;" value="<?php echo htmlspecialchars($ledgerEntry['Payee'] ?? $informant); ?>">
              </div>
              <div>
                <label for="formAmount" style="font-weight:500;">Amount</label>
                <div style="display:flex;align-items:center;position:relative;">
                  <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#888;font-size:1.08rem;">₱</span>
                  <input type="text" id="formAmount" name="Amount" required placeholder="0.00" style="width:100%;box-sizing:border-box;padding-left:28px;" value="<?php echo isset($ledgerEntry['Amount']) ? number_format($ledgerEntry['Amount'], 2) : ''; ?>">
                </div>
              </div>
              <div>
                <label for="formORNumber" style="font-weight:500;">OR Number</label>
                <input type="text" id="formORNumber" name="ORNumber" placeholder="Official Receipt No." style="width:100%;box-sizing:border-box;" value="<?php echo htmlspecialchars($ledgerEntry['ORNumber'] ?? $orNumber); ?>" readonly>
              </div>
              <div>
                <label for="formMCNo" style="font-weight:500;">MC No.</label>
                <input type="text" id="formMCNo" name="MCNo" placeholder="MC Number" style="width:100%;box-sizing:border-box;" value="<?php echo htmlspecialchars($ledgerEntry['MCNo'] ?? $mcNumber); ?>" readonly>
              </div>
              <div>
                <label for="formValidity" style="font-weight:500;">Validity</label>
                <input type="date" id="formValidity" name="Validity" style="width:100%;box-sizing:border-box;" value="<?php echo htmlspecialchars($ledgerEntry['Validity'] ?? $validity); ?>">
              </div>
              <div style="grid-column: span 2;">
                <label for="formDescription" style="font-weight:500;">Desc</label>
                <input type="text" id="formDescription" name="Description" placeholder="Description" style="width:100%;box-sizing:border-box;" value="<?php echo htmlspecialchars($ledgerEntry['Description'] ?? ''); ?>">
              </div>
            </div>
            <div style="margin-top:32px;text-align:right;border-top:1px solid #f0f0f0;padding-top:24px;">
              <button type="submit" class="btn upload" style="width: 140px; padding: 12px 0; font-size:1.08rem;background:#506C84;color:#fff;border-radius:8px;">Submit</button>
            </div>
          </form>
          </div>
        <!-- Right Column: QR Code Upload & Preview (outside the main form, but visually in the same place) -->
          <div style="flex:1 1 320px;min-width:320px;max-width:420px;">
          <div style="background:#e9f0fa;padding:32px 24px 24px 24px;border-radius:12px;display:flex;flex-direction:column;align-items:center;min-width:260px;">
            <div style="font-weight:600;font-size:1.08rem;margin-bottom:16px;">Admin QR Code</div>
              <div id="qrPreviewContainer" style="margin-bottom:18px;">
              <?php
              // Fetch the global admin QR code
              $qrPath = '../assets/qr-placeholder.png'; // default
              $result = $conn->query("SELECT qr_code_path FROM admin_qr_code ORDER BY id DESC LIMIT 1");
              if ($result && $row = $result->fetch_assoc()) {
                  $qrPath = '../' . $row['qr_code_path'];
              }
              ?>
              <img id="qrPreviewImg" src="<?php echo htmlspecialchars($qrPath); ?>" alt="QR Preview" style="width:220px;height:220px;object-fit:cover;border:1.5px solid #b6c6d6;border-radius:10px;background:#fff;display:block;box-shadow:0 2px 8px rgba(44,62,80,0.08);">
            </div>
            <form id="qrUploadForm" method="post" action="" enctype="multipart/form-data" style="display:flex;flex-direction:column;align-items:center;width:100%;gap:10px;" novalidate>
              <input type="file" id="formQRCodeImg" name="QRCodeImg" accept="image/*" style="display:none;">
              <label for="formQRCodeImg" style="background:#506C84;color:#fff;padding:10px 0;border-radius:7px;font-size:1.08rem;font-weight:500;cursor:pointer;transition:background 0.18s;box-shadow:0 1.5px 6px rgba(80,108,132,0.08);margin-bottom:6px;width:160px;text-align:center;">Choose QR Image</label>
              <button type="submit" class="btn upload" style="width:160px; padding:12px 0; font-size:1.08rem;background:#22c55e;color:#fff;border-radius:8px;font-weight:600;box-shadow:0 1.5px 6px rgba(34,197,94,0.08);border:none;transition:background 0.18s;">Save QR Code</button>
            </form>
            <style>
              #qrUploadForm label[for='formQRCodeImg']:hover { background: #29405a; }
              #qrUploadForm .btn.upload:hover { background: #15803d; }
            </style>
          </div>
        </div>
        </div>
      <script>
        // QR Code image preview for the separate QR upload form
        const qrInput = document.getElementById('formQRCodeImg');
        if (qrInput) {
          qrInput.addEventListener('change', function(e) {
          const file = e.target.files[0];
          const previewImg = document.getElementById('qrPreviewImg');
          if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(evt) {
              previewImg.src = evt.target.result;
            };
            reader.readAsDataURL(file);
          } else {
              previewImg.src = '<?php echo htmlspecialchars($qrPath); ?>';
          }
        });
        }
      </script>
    </div>
    <!-- Pending Payment Section (hidden by default) -->
    <div id="pendingPaymentSection" class="card" style="width: 100%; max-width: 100%; background: #fff; border-radius: 16px; box-shadow: 0 2px 8px rgba(44,62,80,0.08); padding: 32px 32px 32px 32px; box-sizing: border-box; display:none;">
      <div style="font-size:1.25rem;font-weight:600;margin-bottom:24px;letter-spacing:0.5px;">Pending Payment</div>
      <div class="ledger-search-container" style="margin-bottom:18px;">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="Search" />
      </div>
      <div style="overflow-x:auto;">
        <table class="ledger-table" id="pendingPaymentTable" style="min-width:900px;">
          <thead>
            <tr>
              <th>MC No.</th>
              <th>Apt No.</th>
              <th>Payee</th>
              <th>Amount</th>
              <th>Des</th>
              <th>OR Number</th>
              <th>Validity</th>
            </tr>
          </thead>
          <tbody>
            <?php
            // Example: Fetch pending payments (where DatePaid is NULL or empty)
            $pendingResult = $conn->query("SELECT * FROM ledger WHERE DatePaid IS NULL OR DatePaid = ''");
            if ($pendingResult && $pendingResult->num_rows > 0) {
              while ($row = $pendingResult->fetch_assoc()) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($row['MCNo']) . '</td>';
                echo '<td>' . htmlspecialchars($row['ApartmentNo']) . '</td>';
                echo '<td>' . htmlspecialchars($row['Payee']) . '</td>';
                echo '<td>₱' . number_format($row['Amount'], 2) . '</td>';
                echo '<td>' . htmlspecialchars($row['Description']) . '</td>';
                echo '<td>' . htmlspecialchars($row['ORNumber']) . '</td>';
                echo '<td>' . htmlspecialchars($row['Validity']) . '</td>';
                echo '</tr>';
              }
            }
            ?>
          </tbody>
        </table>
      </div>
    </div>
    <!-- Payment Details Section (hidden by default) -->
    <div id="paymentDetailsSection" class="card" style="width: 100%; max-width: 100%; background: #fff; border-radius: 16px; box-shadow: 0 2px 8px rgba(44,62,80,0.08); padding: 32px 32px 32px 32px; box-sizing: border-box; display:none;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom:24px;">
        <span style="font-size:1.25rem;font-weight:600;letter-spacing:0.5px;">Payment Details</span>
        <button style="background:#2563eb;color:#fff;border:none;padding:8px 18px;border-radius:7px;font-weight:500;display:flex;align-items:center;gap:8px;cursor:pointer;">
          <i class="fas fa-print"></i> Print
        </button>
      </div>
      <div class="ledger-search-container" style="margin-bottom:18px;">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="Search" />
      </div>
      <div style="overflow-x:auto;">
        <table class="ledger-table" id="paymentDetailsTable" style="min-width:900px;">
          <thead>
            <tr>
              <th>MC No.</th>
              <th>Apt No.</th>
              <th>Date Paid</th>
              <th>Payee</th>
              <th>Amount</th>
              <th>Des</th>
              <th>OR Number</th>
              <th>Validity</th>
            </tr>
          </thead>
          <tbody>
            <?php
            // Fetch payment details (where DatePaid is NOT NULL and not empty)
            $paymentResult = $conn->query("SELECT * FROM ledger WHERE DatePaid IS NOT NULL AND DatePaid != '' ORDER BY DatePaid DESC");
            if ($paymentResult && $paymentResult->num_rows > 0) {
              while ($row = $paymentResult->fetch_assoc()) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($row['MCNo']) . '</td>';
                echo '<td>' . htmlspecialchars($row['ApartmentNo']) . '</td>';
                echo '<td>' . htmlspecialchars($row['DatePaid']) . '</td>';
                echo '<td>' . htmlspecialchars($row['Payee']) . '</td>';
                echo '<td>₱' . number_format($row['Amount'], 2) . '</td>';
                echo '<td>' . htmlspecialchars($row['Description']) . '</td>';
                echo '<td>' . htmlspecialchars($row['ORNumber']) . '</td>';
                echo '<td>' . htmlspecialchars($row['Validity']) . '</td>';
                echo '</tr>';
              }
            }
            ?>
          </tbody>
        </table>
      </div>
    </div>
    <script>
      // Tab switching logic for three tabs
      const ledgerTabBtn = document.getElementById('ledgerTabBtn');
      const pendingPaymentTabBtn = document.getElementById('pendingPaymentTabBtn');
      const paymentTabBtn = document.getElementById('paymentTabBtn');
      const ledgerInfoSection = document.getElementById('ledgerInfoSection');
      const pendingPaymentSection = document.getElementById('pendingPaymentSection');
      const paymentDetailsSection = document.getElementById('paymentDetailsSection');
      ledgerTabBtn.addEventListener('click', function() {
          ledgerTabBtn.classList.add('active');
        pendingPaymentTabBtn.classList.remove('active');
          paymentTabBtn.classList.remove('active');
          ledgerInfoSection.style.display = '';
        pendingPaymentSection.style.display = 'none';
        paymentDetailsSection.style.display = 'none';
      });
      pendingPaymentTabBtn.addEventListener('click', function() {
        ledgerTabBtn.classList.remove('active');
        pendingPaymentTabBtn.classList.add('active');
        paymentTabBtn.classList.remove('active');
        ledgerInfoSection.style.display = 'none';
        pendingPaymentSection.style.display = '';
          paymentDetailsSection.style.display = 'none';
      });
      paymentTabBtn.addEventListener('click', function() {
          ledgerTabBtn.classList.remove('active');
        pendingPaymentTabBtn.classList.remove('active');
          paymentTabBtn.classList.add('active');
          ledgerInfoSection.style.display = 'none';
        pendingPaymentSection.style.display = 'none';
          paymentDetailsSection.style.display = '';
      });
    </script>
  </main>
  <style>
    input[type="text"], input[type="number"], input[type="date"] {
      border:1px solid #d0d7e2; border-radius:7px; padding:8px 12px; font-size:1.04rem; margin-top:4px; margin-bottom:2px; background:#f7fafd; transition:border 0.18s; }
    input[type="text"]:focus, input[type="number"]:focus, input[type="date"]:focus {
      border:1.5px solid #506C84; background:#fff; outline:none; }
    input[readonly] { background-color: #f0f4f8; cursor: not-allowed; }
    label { margin-bottom:2px; display:block; }
    .btn.upload { background:#506C84; color:#fff; border:none; padding:10px 32px; border-radius:8px; font-weight:500; cursor:pointer; transition:background 0.18s; }
    .btn.upload:hover { background:#39546a; }
  </style>
  <script>
    // Format Amount input with commas as thousands separators
    const amountInput = document.getElementById('formAmount');
    if (amountInput) {
      amountInput.addEventListener('input', function(e) {
        let value = this.value.replace(/,/g, '').replace(/[^\d.]/g, '');
        if (value) {
          const parts = value.split('.');
          parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");
          this.value = parts.join('.');
        }
      });
      // On form submit, remove commas so the value is numeric
      const ledgerForm = document.getElementById('ledgerForm');
      if (ledgerForm) {
        ledgerForm.addEventListener('submit', function() {
          amountInput.value = amountInput.value.replace(/,/g, '');
        });
      }
    }
  </script>
  <!-- DataTables JS -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script>
      // DataTables initialization for both tables
      $(document).ready(function() {
        $('#pendingPaymentTable').DataTable({
          paging: true,
          searching: true,
          ordering: true,
          info: true
        });
        $('#paymentDetailsTable').DataTable({
          paging: true,
          searching: true,
          ordering: true,
          info: true
        });
      });
    </script>
</body>
</html>
</body>
</html>




