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

// Get original nicheID from query string
$originalNicheID = $_GET['nicheID'] ?? '';

// If editing, fetch data for this niche
if ($nicheID) {
  $stmt = $conn->prepare("SELECT * FROM deceased WHERE nicheID = ? LIMIT 1");
  $stmt->bind_param("s", $originalNicheID);
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
  $apartmentNo = trim($_POST['apartmentNo'] ?? ''); // This is the new nicheID
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
    // If the nicheID (apartmentNo) was changed, move the record
    if ($originalNicheID !== $apartmentNo && $originalNicheID !== '') {
      // Check if new nicheID already exists
      $stmt = $conn->prepare("SELECT id FROM deceased WHERE nicheID = ? LIMIT 1");
      $stmt->bind_param("s", $apartmentNo);
      $stmt->execute();
      $stmt->store_result();
      if ($stmt->num_rows > 0) {
        $errors[] = "The selected Apartment No. is already occupied.";
        $stmt->close();
      } else {
        $stmt->close();
        // Update the original record's nicheID to the new one
        $stmt = $conn->prepare("UPDATE deceased SET firstName=?, lastName=?, age=?, born=?, residency=?, dateDied=?, dateInternment=?, informantName=?, nicheID=? WHERE nicheID=?");
        $stmt->bind_param("ssisssssss", $firstName, $lastName, $age, $born, $residency, $dateDied, $dateInternment, $informantName, $apartmentNo, $originalNicheID);
        $stmt->execute();
        $stmt->close();
        header("Location: Mapping.php");
        exit();
      }
    } else {
      // If not changed, just update as usual
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
  <link rel="stylesheet" href="../css/EditNiches.css">
  <link rel="stylesheet" href="../css/EditNiches.css">
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
  
    <div class="card" style="max-width: 1200px; margin: 36px 100px 36px auto; background: #fff; border-radius: 16px; box-shadow: 0 4px 24px rgba(44,62,80,0.09); padding: 24px 40px 24px 18px;">
    <div class="main-content">
     <div class="top-bar">
        <span class="page-title">Edit Deceased Data</span>
      </div>
      
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
  </div>
</body>
</html>
