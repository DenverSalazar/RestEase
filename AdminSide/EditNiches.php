<?php
// Database connection (adjust credentials as needed)
$conn = new mysqli("localhost", "root", "", "cemeterydb");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// Get nicheID from query string
$nicheID = $_GET['nicheID'] ?? '';
$deceased = [
  'firstName' => '',
  'lastName' => '',
  'age' => '',
  'born' => '',
  'residency' => '',
  'dateDied' => '',
  'dateInternment' => '',
  'nicheID' => $nicheID,
  'informantName' => ''
];

// If editing, fetch data for this niche
if ($nicheID) {
  $stmt = $conn->prepare("SELECT * FROM deceased WHERE nicheID = ? LIMIT 1");
  $stmt->bind_param("s", $nicheID);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result && $row = $result->fetch_assoc()) {
    $deceased = $row;
  }
  $stmt->close();
}

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete']) && $_POST['delete'] === '1') {
  $apartmentNo = $_POST['apartmentNo'] ?? '';
  if ($apartmentNo) {
    // Fetch the record to archive
    $stmt = $conn->prepare("SELECT firstName, lastName, age, born, residency, dateDied, dateInternment, nicheID, informantName FROM deceased WHERE nicheID = ? LIMIT 1");
    $stmt->bind_param("s", $apartmentNo);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $row = $result->fetch_assoc()) {
      // Insert into archive_deceased
      $archiveStmt = $conn->prepare("INSERT INTO archive_deceased (firstName, lastName, age, born, residency, dateDied, dateInternment, nicheID, informantName) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
      $archiveStmt->bind_param(
        "ssissssss",
        $row['firstName'],
        $row['lastName'],
        $row['age'],
        $row['born'],
        $row['residency'],
        $row['dateDied'],
        $row['dateInternment'],
        $row['nicheID'],
        $row['informantName']
      );
      $archiveStmt->execute();
      $archiveStmt->close();
    }
    $stmt->close();

    // Delete from deceased
    $stmt = $conn->prepare("DELETE FROM deceased WHERE nicheID = ?");
    $stmt->bind_param("s", $apartmentNo);
    $stmt->execute();
    $stmt->close();
  }
  header("Location: Mapping.php");
  exit();
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete'])) {
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
    // If record exists, update; else, insert
    $stmt = $conn->prepare("SELECT id FROM deceased WHERE nicheID = ? LIMIT 1");
    $stmt->bind_param("s", $apartmentNo);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
      $stmt->close();
      $stmt = $conn->prepare("UPDATE deceased SET firstName=?, lastName=?, age=?, born=?, residency=?, dateDied=?, dateInternment=?, informantName=? WHERE nicheID=?");
      $stmt->bind_param("ssissssss", $firstName, $lastName, $age, $born, $residency, $dateDied, $dateInternment, $informantName, $apartmentNo);
      $stmt->execute();
      $stmt->close();
    } else {
      $stmt->close();
      $stmt = $conn->prepare("INSERT INTO deceased (firstName, lastName, age, born, residency, dateDied, dateInternment, nicheID, informantName) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
      $stmt->bind_param("ssissssss", $firstName, $lastName, $age, $born, $residency, $dateDied, $dateInternment, $apartmentNo, $informantName);
      $stmt->execute();
      $stmt->close();
    }

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
  <link rel="stylesheet" href="../css/dashboard.css">
  <link rel="stylesheet" href="../css/sidebar.css">
  <link rel="stylesheet" href="../css/Niches.css">
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
    .btn.delete-btn {
      background: #f8d7da;
      color: #d9534f;
      border: none;
      margin-right: 0;
      min-width: 90px;
      font-weight: 500;
      transition: background 0.18s, color 0.18s;
      box-shadow: none;
    }
    .btn.delete-btn:hover, .btn.delete-btn:focus {
      background: #f5c6cb;
      color: #b52a2a;
    }
    .btn.save-btn {
      background: #c8f7d8;
      color: #2ecc40;
      border: none;
      min-width: 90px;
      font-weight: 500;
      margin-right: 8px;
      transition: background 0.18s, color 0.18s;
      box-shadow: none;
    }
    .btn.save-btn:hover, .btn.save-btn:focus {
      background: #b2f2c9;
      color: #1e9c31;
    }
    .btn.cancel-btn {
      background: #f8d7da;
      color: #d9534f;
      border: none;
      min-width: 90px;
      font-weight: 500;
      text-align: center;
      display: inline-block;
      padding: 8px 22px;
      border-radius: 7px;
      text-decoration: none;
      transition: background 0.18s, color 0.18s;
      box-shadow: none;
    }
    .btn.cancel-btn:hover, .btn.cancel-btn:focus {
      background: #f5c6cb;
      color: #b52a2a;
      text-decoration: none;
    }
  </style>
  <style>
    /* Custom modal styles */
    .modal-overlay {
      position: fixed;
      top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(44, 62, 80, 0.35);
      z-index: 1000;
      display: none;
      align-items: center;
      justify-content: center;
    }
    .modal-confirm {
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 8px 32px rgba(44,62,80,0.18);
      padding: 32px 28px 24px 28px;
      max-width: 370px;
      width: 90%;
      text-align: center;
      position: relative;
      animation: modalPop .18s cubic-bezier(.4,1.4,.6,1.0);
    }
    @keyframes modalPop {
      0% { transform: scale(0.85); opacity: 0; }
      100% { transform: scale(1); opacity: 1; }
    }
    .modal-confirm h2 {
      margin: 0 0 12px 0;
      font-size: 1.25rem;
      color: #d9534f;
      font-weight: 600;
      letter-spacing: 0.5px;
    }
    .modal-confirm p {
      color: #2d3a4a;
      margin-bottom: 24px;
      font-size: 1rem;
      line-height: 1.5;
    }
    .modal-actions {
      display: flex;
      gap: 12px;
      justify-content: center;
    }
    .modal-btn {
      padding: 8px 24px;
      border-radius: 7px;
      border: none;
      font-weight: 500;
      font-size: 1rem;
      cursor: pointer;
      transition: background 0.18s, color 0.18s;
    }
    .modal-btn.confirm {
      background: #d9534f;
      color: #fff;
    }
    .modal-btn.confirm:hover, .modal-btn.confirm:focus {
      background: #b52a2a;
    }
    .modal-btn.cancel {
      background: #f5f7fa;
      color: #2d3a4a;
    }
    .modal-btn.cancel:hover, .modal-btn.cancel:focus {
      background: #e4e9ee;
      color: #1976d2;
    }
  </style>
  <style>
    /* Error popup styles */
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

  <!-- Main Content -->
  <main class="main-content">
    <!-- Header -->
    <header class="header">
      <div class="search-bar">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="Tap to search">
      </div>
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
    </header>
    
    <div class="top-actions">
      <form id="deleteForm" method="post" style="display:inline;">
        <input type="hidden" name="apartmentNo" value="<?php echo htmlspecialchars($deceased['nicheID']); ?>">
        <input type="hidden" name="delete" value="1">
        <button type="button" class="btn delete-btn" style="margin-left:auto;" id="deleteBtn">Delete</button>
      </form>
    </div>
    <div class="form-container">
      <div class="form-section-title">Deceased Information</div>
      <form method="post" autocomplete="off" id="editForm">
        <div class="form-row">
          <div class="form-group">
            <label for="firstName">First Name</label>
            <input type="text" id="firstName" name="firstName" placeholder="First Name" value="<?php echo htmlspecialchars($deceased['firstName']); ?>">
          </div>
          <div class="form-group">
            <label for="lastName">Last Name</label>
            <input type="text" id="lastName" name="lastName" placeholder="Last Name" value="<?php echo htmlspecialchars($deceased['lastName']); ?>">
          </div>
          <div class="form-group">
            <label for="age">Age</label>
            <input type="number" id="age" name="age" placeholder="Age" value="<?php echo htmlspecialchars($deceased['age']); ?>">
          </div>
        </div>
        <div class="form-row-2">
          <div class="form-group">
            <label for="born">Born</label>
            <input type="date" id="born" name="born" placeholder="Born" value="<?php echo htmlspecialchars($deceased['born']); ?>">
          </div>
          <div class="form-group">
            <label for="residency">Residency</label>
            <input type="text" id="residency" name="residency" placeholder="Residency" value="<?php echo htmlspecialchars($deceased['residency']); ?>">
          </div>
          <div class="form-group">
            <label for="dateDied">Date Died</label>
            <input type="date" id="dateDied" name="dateDied" placeholder="Date Died" value="<?php echo htmlspecialchars($deceased['dateDied']); ?>">
          </div>
        </div>
        <div class="form-row-3">
          <div class="form-group">
            <label for="dateInternment">Date of Internment</label>
            <input type="date" id="dateInternment" name="dateInternment" placeholder="Date of Internment" value="<?php echo htmlspecialchars($deceased['dateInternment']); ?>">
          </div>
          <div class="form-group">
            <label for="apartmentNo">Apartment No.</label>
            <div class="niche-picker-group">
              <input type="text" id="apartmentNo" name="apartmentNo" placeholder="Apartment No." readonly value="<?php echo htmlspecialchars($deceased['nicheID']); ?>">
              <button type="button" id="pickNicheBtn" class="btn pick-niche-btn" title="Pick Niche">
                <i class="fas fa-map-marker-alt"></i>
              </button>
            </div>
          </div>
          <div class="form-group">
            <label for="informantName">Informant Name</label>
            <input type="text" id="informantName" name="informantName" placeholder="Informant Name" value="<?php echo htmlspecialchars($deceased['informantName']); ?>">
          </div>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn save-btn">Save</button>
          <a href="Mapping.php" class="btn cancel-btn" style="margin-left:12px;">Cancel</a>
        </div>
      </form>
    </div>
  </div>
  <!-- Custom Modal for Delete Confirmation -->
  <div class="modal-overlay" id="modalOverlay">
    <div class="modal-confirm">
      <h2><i class="fas fa-exclamation-triangle"></i> Confirm Delete</h2>
      <p>Are you sure you want to delete this record?<br>This action cannot be undone.</p>
      <div class="modal-actions">
        <button class="modal-btn confirm" id="modalConfirmBtn">Delete</button>
        <button class="modal-btn cancel" id="modalCancelBtn">Cancel</button>
      </div>
    </div>
  </div>
  <script>
    document.getElementById('pickNicheBtn').onclick = function() {
      window.open('Mapping.php?pickNiche=1', 'PickNiche', 'width=900,height=700');
    };
    window.addEventListener('message', function(event) {
      if (event.data && event.data.nicheID) {
        document.getElementById('apartmentNo').value = event.data.nicheID;
      }
    });

    // Custom modal logic
    const modalOverlay = document.getElementById('modalOverlay');
    const deleteBtn = document.getElementById('deleteBtn');
    const modalConfirmBtn = document.getElementById('modalConfirmBtn');
    const modalCancelBtn = document.getElementById('modalCancelBtn');
    deleteBtn.onclick = function() {
      modalOverlay.style.display = 'flex';
    };
    modalCancelBtn.onclick = function() {
      modalOverlay.style.display = 'none';
    };
    modalConfirmBtn.onclick = function() {
      document.getElementById('deleteForm').submit();
    };
    // Optional: close modal on overlay click
    modalOverlay.onclick = function(e) {
      if (e.target === modalOverlay) modalOverlay.style.display = 'none';
    };
    // Optional: ESC key closes modal
    document.addEventListener('keydown', function(e) {
      if (e.key === "Escape") modalOverlay.style.display = 'none';
    });
  </script>
  </main>
</body>
</html>
