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

// Handle QR code image upload and saving to qr_codes table
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $update_id = $_POST['id'];
    // Only proceed if a file was uploaded
    if (isset($_FILES['QRCodeImg']) && $_FILES['QRCodeImg']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/';
        $ext = pathinfo($_FILES['QRCodeImg']['name'], PATHINFO_EXTENSION);
        $fileName = time() . '_qr.' . $ext;
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['QRCodeImg']['tmp_name'], $targetPath)) {
            $qrPath = 'uploads/' . $fileName;

            if (!$conn->connect_error) {
                // Use INSERT ... ON DUPLICATE KEY UPDATE to handle both new and existing QR codes
                $stmt = $conn->prepare("
                    INSERT INTO qr_codes (ledger_id, qr_code_path)
                    VALUES (?, ?)
                    ON DUPLICATE KEY UPDATE qr_code_path = VALUES(qr_code_path)
                ");
                $stmt->bind_param("is", $update_id, $qrPath);

                if ($stmt->execute()) {
                    echo '<script>alert("QR Code updated successfully!"); window.location.href="Ledger.php?id=' . $update_id . '";</script>';
                    exit;
                } else {
                    echo '<div style="color:#e74c3c;margin-top:16px;">Error updating QR code: ' . htmlspecialchars($stmt->error) . '</div>';
                }
                $stmt->close();
            }
        } else {
            echo '<div style="color:#e74c3c;margin-top:16px;">Error moving uploaded file.</div>';
        }
    } else {
        // No file uploaded, maybe show a message or just reload
        echo '<script>alert("No image selected for upload."); window.location.href="Ledger.php?id=' . $update_id . '";</script>';
        exit;
    }
    $conn->close();
}

if ($entry_id && !$ledgerEntry) {
    echo "Entry not found.";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>RestEase Ledger</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/Clients.css">
  <link rel="stylesheet" href="../css/sidebar.css">
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
      border-collapse: collapse;
    }
    .ledger-table th, .ledger-table td {
      text-align: left;
      padding: 14px 12px;
      font-size: 1.02rem;
      border-bottom: 1px solid #f0f0f0;
    }
    .ledger-table th {
      font-size: 0.98rem;
      letter-spacing: 0.01em;
      background: #f7f8fa;
      color: #b0b0b0;
      font-weight: 600;
      border-bottom: 1px solid #ececec;
    }
    .ledger-table td {
      vertical-align: middle;
      font-size: 1.02rem;
      color: #222;
      font-weight: 500;
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
  </style>
</head>
<body>
  <!-- Sidebar -->
  <?php include '../Includes/sidebar.php'; ?>

  <!-- Main Content -->
  <main class="main-content">
    <!-- Page Header -->
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
      <div>
        <h1 style="font-size:2rem;font-weight:700;margin-bottom:0;">Ledger</h1>
        <p style="font-size:1.04rem;color:#6b7280;">Fill up the ledger information</p>
      </div>
      <div style="display:flex;align-items:center;gap:24px;">
        <i class="fas fa-bell" style="font-size:1.4rem;color:#6b7280;cursor:pointer;"></i>
        <div style="display:flex;align-items:center;gap:12px;">
          <img src="../assets/sybau.jpg" alt="Admin" style="width:44px;height:44px;border-radius:50%;object-fit:cover;border:2px solid #e0e0e0;">
          <div>
            <div style="font-weight:600;color:#333;">Sybau</div>
            <div style="font-size:0.9rem;color:#6b7280;">Admin</div>
          </div>
        </div>
      </div>
    </div>
    <!-- Tabs -->
    <div style="border-bottom:1px solid #e0e0e0;margin-bottom:24px;">
      <div style="display:flex;gap:32px;align-items:center;">
        <button id="ledgerTabBtn" class="tab active" style="background:none;border:none;font-size:1.08rem;padding:16px 0 12px 0;color:#506C84;font-weight:600;border-bottom:2.5px solid #506C84;cursor:pointer;">Ledger Information</button>
        <button id="paymentTabBtn" class="tab" style="background:none;border:none;font-size:1.08rem;padding:16px 0 12px 0;margin-right:24px;color:#506C84;opacity:0.7;border-bottom:2px solid transparent;cursor:pointer;">Payment Details</button>
      </div>
    </div>
    <!-- Ledger Information Section -->
    <div id="ledgerInfoSection" class="card" style="width: 100%; max-width: 100%; background: #fff; border-radius: 16px; box-shadow: 0 2px 8px rgba(44,62,80,0.08); padding: 32px 32px 32px 32px; box-sizing: border-box;">
      <div style="font-size:1.25rem;font-weight:600;margin-bottom:24px;letter-spacing:0.5px;">Ledger Information</div>
      <form id="ledgerForm" method="post" action="" enctype="multipart/form-data" autocomplete="off" style="width: 100%;">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($ledgerEntry['id'] ?? ''); ?>">
        <div style="display:flex;gap:30px;flex-wrap:wrap;width:100%;align-items:flex-start;">
          <!-- Left Column: Ledger Fields -->
          <div style="flex:1 1 600px;">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px 16px;">
              <div>
                <label for="formApartmentNo" style="font-weight:500;">Apartment No.</label>
                <input type="text" id="formApartmentNo" name="ApartmentNo" required placeholder="e.g. A-101" style="width:100%;box-sizing:border-box;" value="<?php echo htmlspecialchars($ledgerEntry['ApartmentNo'] ?? ''); ?>" readonly>
              </div>
              <div>
                <label for="formName" style="font-weight:500;">Name</label>
                <input type="text" id="formName" name="Payee" required placeholder="Name" style="width:100%;box-sizing:border-box;" value="<?php echo htmlspecialchars($ledgerEntry['Payee'] ?? ''); ?>" readonly>
              </div>
              <div>
                <label for="formAmount" style="font-weight:500;">Amount</label>
                <input type="number" step="0.01" id="formAmount" name="Amount" required placeholder="₱ 0.00" style="width:100%;box-sizing:border-box;" value="<?php echo htmlspecialchars($ledgerEntry['Amount'] ?? ''); ?>" readonly>
              </div>
              <div>
                <label for="formORNumber" style="font-weight:500;">OR Number</label>
                <input type="text" id="formORNumber" name="ORNumber" placeholder="Official Receipt No." style="width:100%;box-sizing:border-box;" value="<?php echo htmlspecialchars($ledgerEntry['ORNumber'] ?? ''); ?>" readonly>
              </div>
              <div>
                <label for="formMCNo" style="font-weight:500;">MC No.</label>
                <input type="text" id="formMCNo" name="MCNo" placeholder="MC Number" style="width:100%;box-sizing:border-box;" value="<?php echo htmlspecialchars($ledgerEntry['MCNo'] ?? ''); ?>" readonly>
              </div>
              <div>
                <label for="formValidity" style="font-weight:500;">Validity</label>
                <input type="date" id="formValidity" name="Validity" style="width:100%;box-sizing:border-box;" value="<?php echo htmlspecialchars($ledgerEntry['Validity'] ?? ''); ?>" readonly>
              </div>
              <div style="grid-column: span 2;">
                <label for="formDescription" style="font-weight:500;">Desc</label>
                <input type="text" id="formDescription" name="Description" placeholder="Description" style="width:100%;box-sizing:border-box;" value="<?php echo htmlspecialchars($ledgerEntry['Description'] ?? ''); ?>" readonly>
              </div>
            </div>
          </div>
          <!-- Right Column: QR Code Upload & Preview -->
          <div style="flex:1 1 320px;min-width:320px;max-width:420px;">
            <div style="background:#e9f0fa;padding:32px 24px 24px 24px;border-radius:12px;display:flex;flex-direction:column;align-items:center;">
              <div style="font-weight:600;font-size:1.08rem;margin-bottom:16px;">Scan QR Code</div>
              <div id="qrPreviewContainer" style="margin-bottom:18px;">
                <img id="qrPreviewImg" src="<?php echo htmlspecialchars(!empty($ledgerEntry['qr_code_path']) ? '../' . $ledgerEntry['qr_code_path'] : '../assets/qr-placeholder.png'); ?>" alt="QR Preview" style="width:220px;height:220px;object-fit:cover;border:1px solid #ececec;border-radius:8px;background:#fff;display:block;">
              </div>
              <input type="file" id="formQRCodeImg" name="QRCodeImg" accept="image/*" style="margin-bottom:18px;">
            </div>
          </div>
        </div>
        <div style="margin-top:32px;text-align:right;border-top:1px solid #f0f0f0;padding-top:24px;">
          <button type="submit" class="btn upload" style="width: 140px; padding: 12px 0; font-size:1.08rem;background:#506C84;color:#fff;border-radius:8px;">Save</button>
        </div>
      </form>
      <script>
        // QR Code image preview
        document.getElementById('formQRCodeImg').addEventListener('change', function(e) {
          const file = e.target.files[0];
          const previewImg = document.getElementById('qrPreviewImg');
          if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(evt) {
              previewImg.src = evt.target.result;
            };
            reader.readAsDataURL(file);
          } else {
            previewImg.src = '<?php echo htmlspecialchars(!empty($ledgerEntry['qr_code_path']) ? '../' . $ledgerEntry['qr_code_path'] : '../assets/qr-placeholder.png'); ?>';
          }
        });
      </script>
    </div>
    <!-- Payment Details Section (hidden by default) -->
    <div id="paymentDetailsSection" class="card" style="width: 100%; max-width: 100%; background: #fff; border-radius: 16px; box-shadow: 0 2px 8px rgba(44,62,80,0.08); padding: 32px 32px 32px 32px; box-sizing: border-box; display:none;">
      <div style="font-size:1.25rem;font-weight:600;margin-bottom:24px;letter-spacing:0.5px;">Payment Details</div>
      <div class="ledger-search-container" style="margin-bottom:18px;">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="Search" />
      </div>
      <div style="overflow-x:auto;">
        <table class="ledger-table" style="min-width:900px;">
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
            <tr><td>0</td><td>1F-0A1</td><td>04-12-25</td><td>Dysania Beans</td><td>₱10,000.00</td><td>New</td><td>35426742</td><td>02-28-30</td></tr>
            <tr><td>1</td><td>1F-0A2</td><td>07-23-24</td><td>Wilson Aminoff</td><td>₱2,000.00</td><td>Renewal</td><td>74382659</td><td>03-09-34</td></tr>
            <tr><td>2</td><td>1F-0A3</td><td>06-04-24</td><td>Brandon Saris</td><td>₱2,000.00</td><td>Renewal</td><td>19284736</td><td>07-21-29</td></tr>
            <tr><td>3</td><td>1F-0A4</td><td>12-11-25</td><td>Zain Philips</td><td>₱2,000.00</td><td>Renewal</td><td>86420359</td><td>12-05-31</td></tr>
            <tr><td>4</td><td>1F-0A5</td><td>08-15-23</td><td>Wilson Lubin</td><td>₱2,000.00</td><td>Renewal</td><td>37491826</td><td>06-30-25</td></tr>
            <tr><td>5</td><td>1F-0A6</td><td>09-13-24</td><td>Wilson Culhane</td><td>₱2,000.00</td><td>Renewal</td><td>62519038</td><td>09-14-38</td></tr>
            <tr><td>6</td><td>1F-0A7</td><td>01-14-22</td><td>Adison Vetrovs</td><td>₱2,000.00</td><td>Renewal</td><td>13849275</td><td>04-18-32</td></tr>
            <tr><td>7</td><td>1F-0A8</td><td>11-25-24</td><td>Jocelyn Mango</td><td>₱2,000.00</td><td>Renewal</td><td>90471628</td><td>11-27-28</td></tr>
            <tr><td>8</td><td>1F-0A9</td><td>05-28-23</td><td>Jocelyn Mango</td><td>₱2,000.00</td><td>Renewal</td><td>28134697</td><td>08-08-36</td></tr>
            <tr><td>9</td><td>1F-0A10</td><td>04-23-19</td><td>Jakob Bator</td><td>₱2,000.00</td><td>Renewal</td><td>71658342</td><td>05-23-33</td></tr>
          </tbody>
        </table>
      </div>
      <div style="display:flex;justify-content:space-between;align-items:center;margin-top:18px;">
        <div style="font-size:0.97rem;color:#888;">Page 1 of 3</div>
        <div style="display:flex;gap:8px;align-items:center;">
          <button style="border:none;background:#f7f8fa;padding:6px 12px;border-radius:6px;cursor:pointer;" disabled><i class="fas fa-chevron-left"></i></button>
          <button style="border:none;background:#f7f8fa;padding:6px 12px;border-radius:6px;cursor:pointer;">1</button>
          <button style="border:none;background:#506C84;color:#fff;padding:6px 12px;border-radius:6px;cursor:pointer;">2</button>
          <button style="border:none;background:#f7f8fa;padding:6px 12px;border-radius:6px;cursor:pointer;">3</button>
          <button style="border:none;background:#f7f8fa;padding:6px 12px;border-radius:6px;cursor:pointer;"><i class="fas fa-chevron-right"></i></button>
        </div>
        <div style="display:flex;gap:8px;">
          <button style="background:#2563eb;color:#fff;border:none;padding:8px 18px;border-radius:7px;font-weight:500;display:flex;align-items:center;gap:8px;cursor:pointer;"><i class="fas fa-print"></i> Print</button>
          <button style="background:#f7f8fa;color:#506C84;border:1px solid #ececec;padding:8px 18px;border-radius:7px;font-weight:500;display:flex;align-items:center;gap:8px;cursor:pointer;"><i class="fas fa-filter"></i> Filter</button>
        </div>
      </div>
    </div>
    <script>
      // Tab switching logic
      const ledgerTabBtn = document.getElementById('ledgerTabBtn');
      const paymentTabBtn = document.getElementById('paymentTabBtn');
      const ledgerInfoSection = document.getElementById('ledgerInfoSection');
      const paymentDetailsSection = document.getElementById('paymentDetailsSection');
      function setActiveTab(tab) {
        if (tab === 'ledger') {
          ledgerTabBtn.classList.add('active');
          paymentTabBtn.classList.remove('active');
          ledgerTabBtn.style.borderBottom = '2.5px solid #506C84';
          paymentTabBtn.style.borderBottom = '2px solid transparent';
          ledgerInfoSection.style.display = '';
          paymentDetailsSection.style.display = 'none';
        } else {
          ledgerTabBtn.classList.remove('active');
          paymentTabBtn.classList.add('active');
          ledgerTabBtn.style.borderBottom = '2px solid transparent';
          paymentTabBtn.style.borderBottom = '2.5px solid #506C84';
          ledgerInfoSection.style.display = 'none';
          paymentDetailsSection.style.display = '';
        }
      }
      ledgerTabBtn.addEventListener('click', function() { setActiveTab('ledger'); });
      paymentTabBtn.addEventListener('click', function() { setActiveTab('payment'); });
    </script>
  </main>
  <style>
    .tab.active { border-bottom:2.5px solid #506C84 !important; color:#506C84 !important; font-weight:600; opacity:1 !important; }
    .tab { opacity:0.7; }
    input[type="text"], input[type="number"], input[type="date"] {
      border:1px solid #d0d7e2; border-radius:7px; padding:8px 12px; font-size:1.04rem; margin-top:4px; margin-bottom:2px; background:#f7fafd; transition:border 0.18s; }
    input[type="text"]:focus, input[type="number"]:focus, input[type="date"]:focus {
      border:1.5px solid #506C84; background:#fff; outline:none; }
    input[readonly] { background-color: #f0f4f8; cursor: not-allowed; }
    label { margin-bottom:2px; display:block; }
    .btn.upload { background:#506C84; color:#fff; border:none; padding:10px 32px; border-radius:8px; font-weight:500; cursor:pointer; transition:background 0.18s; }
    .btn.upload:hover { background:#39546a; }
  </style>
</body>
</html>
