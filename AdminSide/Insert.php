<?php
// Database connection (adjust credentials as needed)
$conn = new mysqli("localhost", "root", "", "cemeterydb");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $firstName = trim($_POST['firstName'] ?? '');
  $lastName = trim($_POST['lastName'] ?? '');
  $age = trim($_POST['age'] ?? '');
  $born = trim($_POST['born'] ?? '');
  $residency = trim($_POST['residency'] ?? '');
  $dateDied = trim($_POST['dateDied'] ?? '');
  $dateInternment = trim($_POST['dateInternment'] ?? '');
  $apartmentNo = trim($_POST['apartmentNo'] ?? '');
  $informantName = trim($_POST['informantName'] ?? '');

  // Simple required validation
  if ($firstName === '') $errors[] = "First Name is required.";
  if ($lastName === '') $errors[] = "Last Name is required.";
  if ($age === '' || !is_numeric($age)) $errors[] = "Valid Age is required.";
  if ($born === '') $errors[] = "Born date is required.";
  if ($residency === '') $errors[] = "Residency is required.";
  if ($dateDied === '') $errors[] = "Date Died is required.";
  if ($dateInternment === '') $errors[] = "Date of Internment is required.";
  if ($apartmentNo === '') $errors[] = "Apartment No. is required.";
  if ($informantName === '') $errors[] = "Informant Name is required.";

  if (empty($errors)) {
    $stmt = $conn->prepare("INSERT INTO deceased (firstName, lastName, age, born, residency, dateDied, dateInternment, nicheID, informantName) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssissssss", $firstName, $lastName, $age, $born, $residency, $dateDied, $dateInternment, $apartmentNo, $informantName);
    $stmt->execute();
    $stmt->close();

    header("Location: Mapping.php");
    exit();
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>RestEase Admin Dashboard</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/sidebar.css">
  <link rel="stylesheet" href="../css/Insert.css">
  <style>
    /* Add this style block inside <head> or in your Insert.css */
    .niche-picker-group {
      display: flex;
      align-items: center;
      gap: 8px;
      width: 100%;
    }
    .niche-picker-group input[readonly] {
      flex: 1 1 0;
      min-width: 0;
      background: #f8fafc;
      border: 1.5px solid #e3e7ed;
      color: #2d3a4a;
      font-weight: 500;
      letter-spacing: 0.5px;
      /* Remove fixed width if any */
    }
    .pick-niche-btn {
      background: #f5f7fa;
      color: #2d3a4a;
      border: 1.5px solid #d3dbe2;
      border-radius: 7px;
      padding: 8px 14px;
      min-width: 44px;
      height: 42px;
      font-size: 1.1rem;
      transition: background 0.18s, color 0.18s, border 0.18s;
      box-shadow: none;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .pick-niche-btn:hover, .pick-niche-btn:focus {
      background: #e4e9ee;
      color: #1976d2;
      border-color: #bfc9d1;
    }
    .error-alert {
      background: #fff0f0;
      color: #c0392b;
      border: 1.5px solid #f5c6cb;
      border-radius: 8px;
      padding: 18px 22px 14px 22px;
      margin-bottom: 18px;
      box-shadow: 0 2px 8px rgba(220,53,69,0.07);
      font-family: 'Inter', sans-serif;
      max-width: 520px;
    }
    .error-title {
      font-weight: 600;
      font-size: 1.08em;
      margin-bottom: 6px;
      color: #b52a2a;
      display: flex;
      align-items: center;
      gap: 7px;
    }
    .error-list {
      margin: 0 0 0 18px;
      padding: 0;
      font-size: 0.98em;
      line-height: 1.7;
    }
    .error-list li {
      margin-bottom: 2px;
      list-style: disc;
    }
    .popup-error-overlay {
      position: fixed;
      top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(44, 62, 80, 0.18);
      z-index: 2000;
    }
    .popup-error-modal {
      position: fixed;
      top: 50%; left: 50%;
      transform: translate(-50%, -50%);
      background: #fff0f0;
      color: #c0392b;
      border: 1.5px solid #f5c6cb;
      border-radius: 12px;
      padding: 28px 32px 18px 32px;
      box-shadow: 0 8px 32px rgba(220,53,69,0.13);
      font-family: 'Inter', sans-serif;
      min-width: 320px;
      max-width: 90vw;
      z-index: 2100;
      text-align: left;
      animation: popupErrorPop .18s cubic-bezier(.4,1.4,.6,1.0);
    }
    @keyframes popupErrorPop {
      0% { transform: translate(-50%, -60%) scale(0.92); opacity: 0; }
      100% { transform: translate(-50%, -50%) scale(1); opacity: 1; }
    }
    .popup-error-header {
      font-weight: 600;
      font-size: 1.12em;
      margin-bottom: 10px;
      color: #b52a2a;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .popup-error-list {
      margin: 0 0 0 18px;
      padding: 0;
      font-size: 1em;
      line-height: 1.7;
    }
    .popup-error-list li {
      margin-bottom: 2px;
      list-style: disc;
    }
    .popup-error-close {
      margin-top: 18px;
      background: #d9534f;
      color: #fff;
      border: none;
      border-radius: 7px;
      padding: 8px 28px;
      font-size: 1em;
      font-weight: 500;
      cursor: pointer;
      transition: background 0.18s;
      float: right;
    }
    .popup-error-close:hover, .popup-error-close:focus {
      background: #b52a2a;
    }
  </style>
  <script>
    // Add this script before </body>
    document.getElementById('pickNicheBtn').onclick = function() {
      window.open('Mapping.php?pickNiche=1', 'PickNiche', 'width=900,height=700');
    };

    // Listen for message from Mapping.php
    window.addEventListener('message', function(event) {
      if (event.data && event.data.nicheID) {
        document.getElementById('apartmentNo').value = event.data.nicheID;
      }
    });
  </script>
</head>
<body>
  <!-- Sidebar -->
  <?php include '../Includes/sidebar.php'; ?>

  <!-- Error Popup Notification -->
  <?php if (!empty($errors)): ?>
    <div class="popup-error-overlay" id="popupErrorOverlay"></div>
    <div class="popup-error-modal" id="popupErrorModal">
      <div class="popup-error-header">
        <i class="fas fa-exclamation-circle"></i> Please fix the following:
      </div>
      <ul class="popup-error-list">
        <?php foreach ($errors as $error): ?>
          <li><?php echo htmlspecialchars($error); ?></li>
        <?php endforeach; ?>
      </ul>
      <button class="popup-error-close" id="popupErrorCloseBtn">Close</button>
    </div>
    <style>
      .popup-error-overlay {
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(44, 62, 80, 0.18);
        z-index: 2000;
      }
      .popup-error-modal {
        position: fixed;
        top: 50%; left: 50%;
        transform: translate(-50%, -50%);
        background: #fff0f0;
        color: #c0392b;
        border: 1.5px solid #f5c6cb;
        border-radius: 12px;
        padding: 28px 32px 18px 32px;
        box-shadow: 0 8px 32px rgba(220,53,69,0.13);
        font-family: 'Inter', sans-serif;
        min-width: 320px;
        max-width: 90vw;
        z-index: 2100;
        text-align: left;
        animation: popupErrorPop .18s cubic-bezier(.4,1.4,.6,1.0);
      }
      @keyframes popupErrorPop {
        0% { transform: translate(-50%, -60%) scale(0.92); opacity: 0; }
        100% { transform: translate(-50%, -50%) scale(1); opacity: 1; }
      }
      .popup-error-header {
        font-weight: 600;
        font-size: 1.12em;
        margin-bottom: 10px;
        color: #b52a2a;
        display: flex;
        align-items: center;
        gap: 8px;
      }
      .popup-error-list {
        margin: 0 0 0 18px;
        padding: 0;
        font-size: 1em;
        line-height: 1.7;
      }
      .popup-error-list li {
        margin-bottom: 2px;
        list-style: disc;
      }
      .popup-error-close {
        margin-top: 18px;
        background: #d9534f;
        color: #fff;
        border: none;
        border-radius: 7px;
        padding: 8px 28px;
        font-size: 1em;
        font-weight: 500;
        cursor: pointer;
        transition: background 0.18s;
        float: right;
      }
      .popup-error-close:hover, .popup-error-close:focus {
        background: #b52a2a;
      }
    </style>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        var overlay = document.getElementById('popupErrorOverlay');
        var modal = document.getElementById('popupErrorModal');
        var closeBtn = document.getElementById('popupErrorCloseBtn');
        function closePopup() {
          if (overlay) overlay.style.display = 'none';
          if (modal) modal.style.display = 'none';
        }
        if (closeBtn) closeBtn.onclick = closePopup;
        if (overlay) overlay.onclick = closePopup;
        document.addEventListener('keydown', function(e) {
          if (e.key === "Escape") closePopup();
        });
      });
    </script>
  <?php endif; ?>

  <div class="main-content">
    <div class="top-bar">
      <span class="page-title">Insert Data</span>
      <div class="user-profile">
        <div class="notification-icon">
          <i class="fas fa-bell"></i>
          <span class="notification-badge">1</span>
        </div>
        <div class="profile-info">
          <img src="../assets/Default Image.jpg" alt="Profile" class="profile-avatar">
          <div>
            <div class="profile-name">Sybau</div>
            <div class="profile-role">Admin</div>
          </div>
        </div>
      </div>
    </div>
    <div class="page-subtitle">Fill up the masterlist data</div>
    <div class="top-actions">
      <button type="button" class="btn upload" id="importDataBtn">Import Data</button>
      <a href="Records.php"><button type="button" class="btn secondary">Back</button></a>
    </div>
    <!-- Excel Import Modal -->
    <div id="excelModal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.25); z-index:1000; align-items:center; justify-content:center;">
      <div style="background:#fff; padding:32px 24px; border-radius:10px; min-width:320px; max-width:90vw; position:relative;">
        <button type="button" id="closeModal" style="position:absolute; top:10px; right:10px; background:none; border:none; font-size:1.2rem; cursor:pointer;">&times;</button>
        <h3 style="margin-top:0;">Import Excel File</h3>
        <form action="ImportExcel.php" method="post" enctype="multipart/form-data">
          <input type="file" name="excel_file" accept=".xls,.xlsx" required style="margin-bottom:16px;">
          <br>
          <button type="submit" style="background:#506C84; color:#fff; border:none; border-radius:6px; padding:8px 20px; font-size:1rem; cursor:pointer;">Upload</button>
        </form>
        <div style="font-size:0.95rem; color:#555; margin-top:10px;">Only .xls or .xlsx files are allowed.</div>
      </div>
    </div>
    <script>
      document.getElementById('importDataBtn').onclick = function() {
        document.getElementById('excelModal').style.display = 'flex';
      };
      document.getElementById('closeModal').onclick = function() {
        document.getElementById('excelModal').style.display = 'none';
      };
      document.getElementById('excelModal').onclick = function(e) {
        if (e.target === this) this.style.display = 'none';
      };
    </script>
    <div class="form-container">
      <div class="form-section-title">Deceased Information</div>
      <!-- Remove error-alert in form, only keep popup notification -->
      <form method="post" autocomplete="off">
        <div class="form-row">
          <div class="form-group">
            <label for="firstName">First Name</label>
            <input type="text" id="firstName" name="firstName" placeholder="First Name" value="<?php echo htmlspecialchars($_POST['firstName'] ?? ''); ?>">
          </div>
          <div class="form-group">
            <label for="lastName">Last Name</label>
            <input type="text" id="lastName" name="lastName" placeholder="Last Name" value="<?php echo htmlspecialchars($_POST['lastName'] ?? ''); ?>">
          </div>
          <div class="form-group">
            <label for="age">Age</label>
            <input type="number" id="age" name="age" placeholder="Age" value="<?php echo htmlspecialchars($_POST['age'] ?? ''); ?>">
          </div>
        </div>
        <div class="form-row-2">
          <div class="form-group">
            <label for="born">Born</label>
            <input type="date" id="born" name="born" placeholder="Born" value="<?php echo htmlspecialchars($_POST['born'] ?? ''); ?>">
          </div>
          <div class="form-group">
            <label for="residency">Residency</label>
            <input type="text" id="residency" name="residency" placeholder="Residency" value="<?php echo htmlspecialchars($_POST['residency'] ?? ''); ?>">
          </div>
          <div class="form-group">
            <label for="dateDied">Date Died</label>
            <input type="date" id="dateDied" name="dateDied" placeholder="Date Died" value="<?php echo htmlspecialchars($_POST['dateDied'] ?? ''); ?>">
          </div>
        </div>
        <div class="form-row-3">
          <div class="form-group">
            <label for="dateInternment">Date of Internment</label>
            <input type="date" id="dateInternment" name="dateInternment" placeholder="Date of Internment" value="<?php echo htmlspecialchars($_POST['dateInternment'] ?? ''); ?>">
          </div>
          <div class="form-group">
            <label for="apartmentNo">Apartment No.</label>
            <div class="niche-picker-group">
              <input type="text" id="apartmentNo" name="apartmentNo" placeholder="Apartment No." readonly value="<?php echo isset($_GET['nicheID']) ? htmlspecialchars($_GET['nicheID']) : (htmlspecialchars($_POST['apartmentNo'] ?? '')); ?>">
              <button type="button" id="pickNicheBtn" class="btn pick-niche-btn" title="Pick Niche">
                <i class="fas fa-map-marker-alt"></i>
              </button>
            </div>
          </div>
          <div class="form-group">
            <label for="informantName">Informant Name</label>
            <input type="text" id="informantName" name="informantName" placeholder="Informant Name" value="<?php echo htmlspecialchars($_POST['informantName'] ?? ''); ?>">
          </div>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn primary">Insert</button>
        </div>
      </form>
    </div>
  </div>
  <script>
    // Add this script before </body>
    document.getElementById('pickNicheBtn').onclick = function() {
      window.open('Mapping.php?pickNiche=1', 'PickNiche', 'width=900,height=700');
    };

    // Listen for message from Mapping.php
    window.addEventListener('message', function(event) {
      if (event.data && event.data.nicheID) {
        document.getElementById('apartmentNo').value = event.data.nicheID;
      }
    });
  </script>
</body>
</html>