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
        <button id="paymentTabBtn" class="tab active">Payment Details</button>
        <button id="ledgerTabBtn" class="tab">Ledger Information</button>
      </div>
    </div>
    <!-- Payment Details Section (now comes first, visible by default) -->
    <div id="paymentDetailsSection" class="card" style="width: 100%; max-width: 100%; background: #fff; border-radius: 16px; box-shadow: 0 2px 8px rgba(44,62,80,0.08); padding: 32px 32px 32px 32px; box-sizing: border-box;">
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
              <th>Apt No.</th>
              <th>Payee</th>
              <th>Date Paid</th>
              <th>Amount</th>
              <th>Description</th>
              <th>Validity</th>
              <th>OR Number</th>
            </tr>
          </thead>
          <tbody>
            <?php
            // Fetch payment details (where DatePaid is NOT NULL and not empty)
            $paymentResult = $conn->query("SELECT * FROM ledger WHERE DatePaid IS NOT NULL AND DatePaid != '' ORDER BY DatePaid DESC");
            if ($paymentResult && $paymentResult->num_rows > 0) {
              while ($row = $paymentResult->fetch_assoc()) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($row['ApartmentNo']) . '</td>';
                echo '<td>' . htmlspecialchars($row['Payee']) . '</td>';
                echo '<td>' . htmlspecialchars($row['DatePaid']) . '</td>';
                echo '<td>₱' . number_format($row['Amount'], 2) . '</td>';
                echo '<td>' . htmlspecialchars($row['Description']) . '</td>';
                echo '<td>' . htmlspecialchars($row['Validity']) . '</td>';
                echo '<td>' . htmlspecialchars($row['ORNumber']) . '</td>';
                echo '</tr>';
              }
            }
            ?>
          </tbody>
        </table>
      </div>
    </div>
    <!-- Ledger Information Section (now comes second, hidden by default) -->
    <div id="ledgerInfoSection" class="card" style="width: 100%; max-width: 100%; background: #fff; border-radius: 16px; box-shadow: 0 2px 8px rgba(44,62,80,0.08); padding: 32px 32px 32px 32px; box-sizing: border-box; display:none;">
      <div style="font-size:1.25rem;font-weight:600;margin-bottom:24px;letter-spacing:0.5px;">Ledger Information</div>
      <form id="ledgerForm" method="post" action="" enctype="multipart/form-data" autocomplete="off" style="width: 100%;">
        <!-- Section: Basic Information -->
        <div style="font-weight:600;font-size:1.08rem;margin-bottom:8px;">Basic Information</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px 32px;">
          <div>
            <label for="formApartmentNo">Apt No.</label>
            <input type="text" id="formApartmentNo" name="ApartmentNo" required placeholder="<?php echo $apartment ? $apartment : 'e.g. A-101'; ?>" style="width:90%;" value="<?php echo htmlspecialchars($ledgerEntry['ApartmentNo'] ?? $apartment); ?>">
          </div>
          <div>
            <label for="formName">Payee</label>
            <input type="text" id="formName" name="Payee" required placeholder="<?php echo $informant ? $informant : 'Name'; ?>" style="width:90%;" value="<?php echo htmlspecialchars($ledgerEntry['Payee'] ?? $informant); ?>">
          </div>
          <div>
            <label for="formDatePaid">Date Paid</label>
            <input type="date" id="formDatePaid" name="DatePaid" style="width:90%;" value="<?php echo htmlspecialchars($ledgerEntry['DatePaid'] ?? ''); ?>">
          </div>
          <div>
            <label for="formAmount">Amount</label>
            <div style="display:flex;align-items:center;position:relative;">
              <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#888;font-size:1.08rem;">₱</span>
              <input type="text" id="formAmount" name="Amount" required placeholder="0.00" style="width:90%;box-sizing:border-box;padding-left:28px;" value="<?php echo isset($ledgerEntry['Amount']) ? number_format($ledgerEntry['Amount'], 2) : ''; ?>">
            </div>
          </div>
        </div>
        <!-- Section: Details -->
        <div style="font-weight:600;font-size:1.08rem;margin:24px 0 8px 0;">Details</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px 32px;">
          <div>
            <label for="formDescription">Description</label>
            <input
              type="text"
              id="formDescription"
              name="Description"
              list="descOptions"
              required
              placeholder="Description"
              style="width:90%;"
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
          <div>
            <label for="formValidity">Validity</label>
            <input type="date" id="formValidity" name="Validity" style="width:90%;" value="<?php echo htmlspecialchars($ledgerEntry['Validity'] ?? $validity); ?>">
          </div>
          <div>
            <label for="formORNumber">OR Number</label>
            <input type="text" id="formORNumber" name="ORNumber" placeholder="Official Receipt No." style="width:90%;" value="<?php echo htmlspecialchars($ledgerEntry['ORNumber'] ?? $orNumber); ?>" readonly>
          </div>
        </div>
        <div style="margin-top:32px;text-align:right;border-top:1px solid #f0f0f0;padding-top:24px;">
          <button type="submit" class="btn upload" style="width: 140px; padding: 12px 0; font-size:1.08rem;background:#506C84;color:#fff;border-radius:8px;">Submit</button>
        </div>
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($ledgerEntry['id'] ?? ''); ?>">
      </form>
    </div>
    <script>
      // Tab switching logic for two tabs (Payment Details is default)
      const ledgerTabBtn = document.getElementById('ledgerTabBtn');
      const paymentTabBtn = document.getElementById('paymentTabBtn');
      const ledgerInfoSection = document.getElementById('ledgerInfoSection');
      const paymentDetailsSection = document.getElementById('paymentDetailsSection');
      // Set Payment Details as default visible
      paymentTabBtn.classList.add('active');
      ledgerTabBtn.classList.remove('active');
      paymentDetailsSection.style.display = '';
      ledgerInfoSection.style.display = 'none';
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

        $('#paymentDetailsTable').DataTable({
          paging: true,
          searching: true,
          ordering: true,
          info: true
        });
    </script>
</body>
</html>





