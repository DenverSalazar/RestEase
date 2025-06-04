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
  <title>Edit Niche</title>
  <link rel="stylesheet" href="../css/records.css">
  <link rel="stylesheet" href="../css/sidebar.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    body, html {
      font-family: 'Inter', sans-serif;
    }
    .edit-card {
      max-width: 1100px;
      margin-left: 400px;
      margin-right: auto;
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.07);
      padding: 40px 32px;
      margin-top: 40px;
      position: relative; /* Added for absolute positioning of delete button */
    }
    .delete-btn-card {
      position: absolute;
      top: 24px;
      right: 32px;
      z-index: 2;
      background: #ffd6d6;
      color: #b71c1c;
      border: none;
      border-radius: 8px;
      min-width: 90px;
      padding: 8px 0;
      font-size: 1em;
      font-weight: 500;
      cursor: pointer;
      transition: background 0.2s;
      display: block;
    }
    .delete-btn-card:hover {
      background: #ffb3b3;
    }
    .edit-card h2 {
      margin-bottom: 32px;
      font-size: 1.5em;
      font-weight: 600;
      color: #2d3846;
    }
    .edit-form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 24px 32px;
    }
    .edit-form-group {
      display: flex;
      flex-direction: column;
    }
    .edit-form-group label {
      font-weight: 500;
      margin-bottom: 6px;
      color: #374151;
    }
    .edit-form-group input,
    .edit-form-group select {
      background: #f8fafc;
      border: 1px solid #e5e7eb;
      border-radius: 6px;
      padding: 10px 12px;
      font-size: 1em;
      color: #222;
      outline: none;
      transition: border 0.2s;
    }
    .edit-form-group input:focus,
    .edit-form-group select:focus {
      border-color: #1976d2;
    }
    .edit-form-actions {
      display: flex;
      justify-content: flex-end;
      gap: 16px;
      margin-top: 32px;
    }
    .edit-form-actions button {
      min-width: 120px;
      padding: 10px 0;
      border: none;
      border-radius: 8px;
      font-size: 1em;
      font-weight: 500;
      cursor: pointer;
      transition: background 0.2s;
    }
    .edit-form-actions .save-btn {
      background: #b6f5c3;
      color: #256029;
    }
    .edit-form-actions .save-btn:hover {
      background: #8ee6a3;
    }
    .edit-form-actions .cancel-btn {
      background: #ffd6d6;
      color: #b71c1c;
      display: inline-block;
      text-align: center;
      line-height: normal;
      padding: 10px 0;
      border-radius: 8px;
      min-width: 120px;
      font-size: 1em;
      font-weight: 500;
      text-decoration: none;
      border: none;
    }
    .edit-form-actions .cancel-btn:hover {
      background: #ffb3b3;
    }
    .apt-group {
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .apt-group .apt-icon {
      background: #e5e7eb;
      border: none;
      border-radius: 6px;
      padding: 8px 10px;
      font-size: 1.1em;
      color: #1976d2;
      cursor: default;
      display: flex;
      align-items: center;
      height: 38px;
    }
    @media (max-width: 900px) {
      .edit-form-grid { grid-template-columns: 1fr; }
      .edit-card { 
        margin-left: 0; 
        margin-right: 0;
      }
    }
    /* Modal styles for delete confirmation */
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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
   <?php include '../Includes/sidebar.php'; ?>
  <!-- Delete button moved inside .edit-card -->
  <div class="edit-card">
    <form id="deleteForm" method="post" style="display:inline;">
      <input type="hidden" name="apartmentNo" value="<?php echo htmlspecialchars($deceased['nicheID']); ?>">
      <input type="hidden" name="delete" value="1">
      <button type="button" class="btn delete-btn-card" id="deleteBtn">Delete</button>
    </form>
    <h2>Deceased Information</h2>
    <form method="post" autocomplete="off" id="editForm">
      <div class="edit-form-grid">
        <div class="edit-form-group">
          <label for="firstName">First Name</label>
          <input type="text" id="firstName" name="firstName" placeholder="First Name" value="<?php echo htmlspecialchars($deceased['firstName']); ?>">
        </div>
        <div class="edit-form-group">
          <label for="lastName">Last Name</label>
          <input type="text" id="lastName" name="lastName" placeholder="Last Name" value="<?php echo htmlspecialchars($deceased['lastName']); ?>">
        </div>
        <div class="edit-form-group">
          <label for="age">Age</label>
          <input type="number" id="age" name="age" placeholder="Age" value="<?php echo htmlspecialchars($deceased['age']); ?>">
        </div>
        <div class="edit-form-group">
          <label for="born">Born</label>
          <input type="date" id="born" name="born" placeholder="Born" value="<?php echo htmlspecialchars($deceased['born']); ?>">
        </div>
        <div class="edit-form-group">
          <label for="residency">Residency</label>
          <input type="text" id="residency" name="residency" placeholder="Residency" value="<?php echo htmlspecialchars($deceased['residency']); ?>">
        </div>
        <div class="edit-form-group">
          <label for="dateDied">Date Died</label>
          <input type="date" id="dateDied" name="dateDied" placeholder="Date Died" value="<?php echo htmlspecialchars($deceased['dateDied']); ?>">
        </div>
        <div class="edit-form-group">
          <label for="dateInternment">Date of Internment</label>
          <input type="date" id="dateInternment" name="dateInternment" placeholder="Date of Internment" value="<?php echo htmlspecialchars($deceased['dateInternment']); ?>">
        </div>
        <div class="edit-form-group">
          <label for="apartmentNo">Apartment No.</label>
          <div class="apt-group">
            <input type="text" id="apartmentNo" name="apartmentNo" placeholder="Apartment No." readonly value="<?php echo htmlspecialchars($deceased['nicheID']); ?>">
            <button type="button" id="pickNicheBtn" class="btn pick-niche-btn" title="Pick Niche" style="background:#f5f7fa;border:1.5px solid #d3dbe2;border-radius:7px;padding:8px 14px;min-width:44px;height:38px;margin-left:4px;display:flex;align-items:center;justify-content:center;">
              <i class="fas fa-map-marker-alt"></i>
            </button>
            <span class="apt-icon" style="display:none;"><i class="fas fa-map-marker-alt"></i></span>
          </div>
        </div>
        <div class="edit-form-group">
          <label for="informantName">Informant Name</label>
          <input type="text" id="informantName" name="informantName" placeholder="Informant Name" value="<?php echo htmlspecialchars($deceased['informantName']); ?>">
        </div>
      </div>
      <div class="edit-form-actions">
        <button type="submit" class="save-btn">Save</button>
        <a href="Mapping.php" class="cancel-btn">Cancel</a>
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
    modalOverlay.onclick = function(e) {
      if (e.target === modalOverlay) modalOverlay.style.display = 'none';
    };
    document.addEventListener('keydown', function(e) {
      if (e.key === "Escape") modalOverlay.style.display = 'none';
    });
  </script>
</body>
</html>
