<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

// Database connection (adjust credentials as needed)
include_once '../Includes/db.php';
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// --- Suggestion Data for Informant Name ---
$informantSuggestions = [];
$userResult = $conn->query("SELECT first_name, last_name FROM users");
if ($userResult && $userResult->num_rows > 0) {
    while ($row = $userResult->fetch_assoc()) {
        $fullName = trim($row['first_name'] . ' ' . $row['last_name']);
        if ($fullName !== '') $informantSuggestions[$fullName] = true;
    }
}
$informantResult = $conn->query("SELECT DISTINCT informantName FROM deceased WHERE informantName IS NOT NULL AND informantName != ''");
if ($informantResult && $informantResult->num_rows > 0) {
    while ($row = $informantResult->fetch_assoc()) {
        $name = trim($row['informantName']);
        if ($name !== '') $informantSuggestions[$name] = true;
    }
}
$informantSuggestions = array_keys($informantSuggestions);

// Get record ID or nicheID from query string
$recordId = $_GET['id'] ?? '';
$nicheID = $_GET['nicheID'] ?? '';
$from = $_GET['from'] ?? ''; // Track where the user came from

// Gather all possible deceased fields from query string
$queryFields = [
  'firstName' => $_GET['firstName'] ?? '',
  'middleName' => $_GET['middleName'] ?? '',
  'lastName' => $_GET['lastName'] ?? '',
  'suffix' => $_GET['suffix'] ?? '',
  'born' => $_GET['born'] ?? '',
  'dateDied' => $_GET['dateDied'] ?? '',
  'age' => $_GET['age'] ?? '',
  'residency' => $_GET['residency'] ?? '',
  'dateInternment' => $_GET['dateInternment'] ?? '',
  'nicheID' => $_GET['nicheID'] ?? '',
  'informantName' => $_GET['informantName'] ?? ''
];

$deceased = [
  'firstName' => '',
  'middleName' => '',
  'lastName' => '',
  'suffix' => '',
  'age' => '',
  'born' => '',
  'residency' => '',
  'dateDied' => '',
  'dateInternment' => '',
  'nicheID' => $nicheID,
  'informantName' => ''
];

// Get original nicheID from query string or from database
$originalNicheID = $_GET['nicheID'] ?? '';

// Add this variable to store the editing record's id
$editingRecordId = null;

// If all deceased fields are present, search for a matching record
if (
  $queryFields['firstName'] !== '' &&
  $queryFields['middleName'] !== '' &&
  $queryFields['lastName'] !== '' &&
  $queryFields['born'] !== '' &&
  $queryFields['dateDied'] !== ''
) {
  // Build SQL WHERE clause for all fields
  $sql = "SELECT * FROM deceased WHERE firstName=? AND middleName=? AND lastName=? AND born=? AND dateDied=? AND nicheID=? LIMIT 1";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param(
    "ssssss",
    $queryFields['firstName'],
    $queryFields['middleName'],
    $queryFields['lastName'],
    $queryFields['born'],
    $queryFields['dateDied'],
    $queryFields['nicheID']
  );
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result && $row = $result->fetch_assoc()) {
    $deceased = $row;
    $originalNicheID = $row['nicheID'];
    $editingRecordId = $row['id']; // Store the id for later update
  }
  $stmt->close();
} elseif ($recordId) {
  // If editing by ID, fetch data for this record
  $stmt = $conn->prepare("SELECT * FROM deceased WHERE id = ? LIMIT 1");
  $stmt->bind_param("i", $recordId);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result && $row = $result->fetch_assoc()) {
    $deceased = $row;
    $originalNicheID = $row['nicheID'];
    $editingRecordId = $row['id'];
  }
  $stmt->close();
} elseif ($nicheID) {
  // If editing by nicheID, fetch data for this niche
  $stmt = $conn->prepare("SELECT * FROM deceased WHERE nicheID = ? LIMIT 1");
  $stmt->bind_param("s", $originalNicheID);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result && $row = $result->fetch_assoc()) {
    $deceased = $row;
    $editingRecordId = $row['id'];
  }
  $stmt->close();
}

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete']) && $_POST['delete'] === '1') {
  $deleteId = $_POST['deleteId'] ?? '';
  if ($deleteId) {
    // Fetch the record to archive
    $stmt = $conn->prepare("SELECT firstName, lastName, age, born, residency, dateDied, dateInternment, nicheID, informantName FROM deceased WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $deleteId);
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

    // Delete only the specific record by id
    $stmt = $conn->prepare("DELETE FROM deceased WHERE id = ?");
    $stmt->bind_param("i", $deleteId);
    $stmt->execute();
    $stmt->close();
  }
  header("Location: Mapping.php");
  exit();
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete'])) {
  // Add date range validation
  function validateDateRange($dateString, $fieldName) {
    global $errors;
    
    if (empty($dateString)) {
      return false;
    }
    
    $date = new DateTime($dateString);
    $currentDate = new DateTime();
    $minDate = new DateTime('1900-01-01');
    
    // Set current date to end of day for proper comparison
    $currentDate->setTime(23, 59, 59);
    
    if ($date > $currentDate) {
      $errors[] = "$fieldName cannot be in the future.";
      return false;
    }
    
    if ($date < $minDate) {
      $errors[] = "$fieldName cannot be before year 1900.";
      return false;
    }
    
    return true;
  }

  $firstName = trim($_POST['firstName'] ?? '');
  $middleName = trim($_POST['middleName'] ?? '');
  $lastName = trim($_POST['lastName'] ?? '');
  $suffix = isset($_POST['suffix']) ? trim($_POST['suffix']) : null;
  if ($suffix === '' || strtolower($suffix) === '0' || $suffix === '0') {
    $suffix = null;
  }
  $born = trim($_POST['born'] ?? '');
  $residency = trim($_POST['residency'] ?? '');
  $dateDied = trim($_POST['dateDied'] ?? '');
  $dateInternment = trim($_POST['dateInternment'] ?? '');
  $apartmentNo = trim($_POST['apartmentNo'] ?? '');
  $informantName = trim($_POST['informantName'] ?? '');

  // Validate date ranges
  if ($born) validateDateRange($born, 'Born date');
  if ($dateDied) validateDateRange($dateDied, 'Date died');
  // Remove date range validation for dateInternment to allow future dates

  // Validate date logic only if born or dateDied changed
  $isBornChanged = isset($deceased['born']) && $born !== $deceased['born'];
  $isDiedChanged = isset($deceased['dateDied']) && $dateDied !== $deceased['dateDied'];
  if (($born && $dateDied) && ($isBornChanged || $isDiedChanged)) {
    $bornDate = new DateTime($born);
    $diedDate = new DateTime($dateDied);
    if ($diedDate <= $bornDate) {
      $errors[] = "Date died must be after born date.";
    }
  }

  if ($dateDied && $dateInternment) {
    $diedDate = new DateTime($dateDied);
    $internmentDate = new DateTime($dateInternment);
    if ($internmentDate < $diedDate) {
      $errors[] = "Date of internment cannot be before date died.";
    }
  }

  // Calculate age from born and dateDied only if either is changed
  $age = $deceased['age'] ?? '';
  $isBornChanged = isset($deceased['born']) && $born !== $deceased['born'];
  $isDiedChanged = isset($deceased['dateDied']) && $dateDied !== $deceased['dateDied'];
  if (($born && $dateDied) && ($isBornChanged || $isDiedChanged)) {
    $bornDate = new DateTime($born);
    $diedDate = new DateTime($dateDied);
    $interval = $bornDate->diff($diedDate);
    $years = $interval->y;
    $months = $interval->m;
    // Validate age is reasonable (max 150 years)
    if ($years > 150) {
      $errors[] = "Age cannot exceed 150 years. Please check the born and died dates.";
    } else {
      if ($years == 0) {
        $age = $months . " months old";
      } else {
        $age = $years . " years old";
      }
    }
  }

  // Simple required validation
  if ($firstName === '') $errors[] = "First Name is required.";
  // Remove middle name required validation
  // if ($middleName === '') $errors[] = "Middle Name is required.";
  if ($lastName === '') $errors[] = "Last Name is required.";
  if ($born === '') $errors[] = "Born date is required.";
  if ($residency === '') $errors[] = "Residency is required.";
  if ($dateDied === '') $errors[] = "Date Died is required.";
  if ($dateInternment === '') $errors[] = "Date of Internment is required.";
  if ($apartmentNo === '') $errors[] = "Apartment No. is required.";
  if ($informantName === '') $errors[] = "Informant Name is required.";

  if (empty($errors)) {
    // If the nicheID (apartmentNo) was changed, move the record
    if ($originalNicheID !== $apartmentNo && $originalNicheID !== '') {
      // Only update the specific record by its ID
      if ($editingRecordId) {
        $updateStmt = $conn->prepare("UPDATE deceased SET firstName=?, middleName=?, lastName=?, suffix=?, age=?, born=?, residency=?, dateDied=?, dateInternment=?, informantName=?, nicheID=? WHERE id=?");
        $updateStmt->bind_param("ssssissssssi", $firstName, $middleName, $lastName, $suffix, $age, $born, $residency, $dateDied, $dateInternment, $informantName, $apartmentNo, $editingRecordId);
        $updateStmt->execute();
        $updateStmt->close();
        
        // Redirect to correct page
        if (!empty($_GET['from']) && $_GET['from'] === 'records') {
          header("Location: Records.php");
        } else {
          header("Location: Mapping.php");
        }
        exit();
      }
    } else {
      // If not changed, just update as usual
      if ($editingRecordId) {
        // Update only the specific record by id
        $stmt = $conn->prepare("UPDATE deceased SET firstName=?, middleName=?, lastName=?, suffix=?, age=?, born=?, residency=?, dateDied=?, dateInternment=?, informantName=?, nicheID=? WHERE id=?");
        $stmt->bind_param("ssssissssssi", $firstName, $middleName, $lastName, $suffix, $age, $born, $residency, $dateDied, $dateInternment, $informantName, $apartmentNo, $editingRecordId);
        $stmt->execute();
        $stmt->close();
      } else {
        // Insert new record if not found (should not happen for normal edit)
        $stmt = $conn->prepare("INSERT INTO deceased (firstName, middleName, lastName, suffix, age, born, residency, dateDied, dateInternment, nicheID, informantName) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssissssss", $firstName, $middleName, $lastName, $suffix, $age, $born, $residency, $dateDied, $dateInternment, $apartmentNo, $informantName);
        $stmt->execute();
        $stmt->close();
      }
      // Redirect to correct page
      if (!empty($_GET['from']) && $_GET['from'] === 'records') {
        header("Location: Records.php");
      } else {
        header("Location: Mapping.php");
      }
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
  <title>Edit Niches</title>
  <link rel="icon" type="image/png" href="../assets/re logo blue.png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/sidebar.css">
  <link rel="stylesheet" href="../css/EditNiches.css">
  <link rel="stylesheet" href="../css/header.css">
</head>
<body style="min-height: 100vh; background: #fff; overflow: hidden;">
   <!-- Sidebar -->
   <?php include '../Includes/sidebar.php'; ?>
    <?php include '../Includes/header.php'; ?>

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
  <div class="main-content">
    <div class="cemetery-masterlist-container">
      <div style="display: flex; align-items: center; justify-content: space-between;">
        <div class="cemetery-masterlist-title">Edit Data</div>
      </div>
      <div class="cemetery-masterlist-desc" style="color:#6c7a89;font-size:1.08rem;margin-top:2px;">Edit the masterlist data</div>
    </div>
    
    <div class="card">
           <div class="top-actions" style="display:flex;justify-content:space-between;align-items:center;gap:12px;width:100%;margin-bottom:38px;padding-right:0;">
        <div class="form-section-title" style="margin-top: 9px;">Deceased Information</div>
        <div style="display:flex;gap:12px;">
          <form id="deleteForm" method="post" style="display:inline;">
            <input type="hidden" name="deleteId" value="<?php echo htmlspecialchars($deceased['id'] ?? ''); ?>">
            <input type="hidden" name="delete" value="1">
            <button type="button" class="btn delete-btn" id="deleteBtn">Delete</button>
          </form>
        </div>
      </div>
      <form method="post" autocomplete="off" id="editForm">
        <div class="form-row">
          <div class="form-group">
            <label for="firstName">First Name</label>
            <input type="text" id="firstName" name="firstName" placeholder="First Name" value="<?php echo htmlspecialchars($deceased['firstName'] ?? ''); ?>">
          </div>
          <div class="form-group">
            <label for="middleName">Middle Name</label>
            <input type="text" id="middleName" name="middleName" placeholder="Middle Name" value="<?php echo htmlspecialchars($deceased['middleName'] ?? ''); ?>">
          </div>
          <div class="form-group">
            <label for="lastName">Last Name</label>
            <input type="text" id="lastName" name="lastName" placeholder="Last Name" value="<?php echo htmlspecialchars($deceased['lastName'] ?? ''); ?>">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label for="suffix">Suffix</label>
            <div style="position:relative;">
              <input type="text" id="suffixDisplay" placeholder="e.g. Jr, Sr, III" readonly value="<?php echo htmlspecialchars($deceased['suffix'] ?? ''); ?>" style="padding-right:36px;">
              <input type="hidden" id="suffix" name="suffix" value="<?php echo htmlspecialchars($deceased['suffix'] ?? ''); ?>">
              <button type="button" id="suffix-dropdown-btn" style="position:absolute;top:50%;right:6px;transform:translateY(-50%);background:transparent;border:none;padding:0;cursor:pointer;z-index:2;">
                <i class="fas fa-chevron-down" style="font-size:1.1em;color:#888;"></i>
              </button>
              <ul id="suffix-dropdown-list" style="display:none;position:absolute;top:100%;left:0;width:100%;background:#fff;box-shadow:0 2px 8px rgba(0,0,0,0.08);border-radius:6px;max-height:220px;overflow-y:auto;z-index:10;margin:2px 0 0 0;padding:0;list-style:none;">
                <li data-value="">None</li>
                <li data-value="Jr.">Jr.</li>
                <li data-value="Sr.">Sr.</li>
                <li data-value="II">II</li>
                <li data-value="III">III</li>
                <li data-value="IV">IV</li>
                <li data-value="V">V</li>
                <li data-value="VI">VI</li>
                <li data-value="VII">VII</li>
              </ul>
            </div>
          </div>
          <div class="form-group" style="position:relative;">
            <label for="residency">Residency</label>
            <div style="position:relative;">
              <input type="text" id="residency" name="residency" class="form-control" placeholder="Enter Residency" required value="<?php echo htmlspecialchars($deceased['residency'] ?? ''); ?>" autocomplete="off" style="padding-right:36px;">
              <button type="button" id="residency-dropdown-btn" style="position:absolute;top:50%;right:6px;transform:translateY(-50%);background:transparent;border:none;padding:0;cursor:pointer;z-index:2;">
                <i class="fas fa-chevron-down" style="font-size:1.1em;color:#888;"></i>
              </button>
              <ul id="residency-dropdown-list" style="display:none;position:absolute;top:100%;left:0;width:100%;background:#fff;box-shadow:0 2px 8px rgba(0,0,0,0.08);border-radius:6px;max-height:220px;overflow-y:auto;z-index:10;margin:2px 0 0 0;padding:0;list-style:none;">
                <li data-value="Banaba, Padre Garcia, Batangas">Banaba, Padre Garcia, Batangas</li>
                <li data-value="Banaybanay, Padre Garcia, Batangas">Banaybanay, Padre Garcia, Batangas</li>
                <li data-value="Bawi, Padre Garcia, Batangas">Bawi, Padre Garcia, Batangas</li>
                <li data-value="Bukal, Padre Garcia, Batangas">Bukal, Padre Garcia, Batangas</li>
                <li data-value="Castillo, Padre Garcia, Batangas">Castillo, Padre Garcia, Batangas</li>
                <li data-value="Cawongan, Padre Garcia, Batangas">Cawongan, Padre Garcia, Batangas</li>
                <li data-value="Manggas, Padre Garcia, Batangas">Manggas, Padre Garcia, Batangas</li>
                <li data-value="Maugat East, Padre Garcia, Batangas">Maugat East, Padre Garcia, Batangas</li>
                <li data-value="Maugat West, Padre Garcia, Batangas">Maugat West, Padre Garcia, Batangas</li>
                <li data-value="Pansol, Padre Garcia, Batangas">Pansol, Padre Garcia, Batangas</li>
                <li data-value="Payapa, Padre Garcia, Batangas">Payapa, Padre Garcia, Batangas</li>
                <li data-value="Poblacion, Padre Garcia, Batangas">Poblacion, Padre Garcia, Batangas</li>
                <li data-value="Quilo-quilo North, Padre Garcia, Batangas">Quilo-quilo North, Padre Garcia, Batangas</li>
                <li data-value="Quilo-quilo South, Padre Garcia, Batangas">Quilo-quilo South, Padre Garcia, Batangas</li>
                <li data-value="San Felipe, Padre Garcia, Batangas">San Felipe, Padre Garcia, Batangas</li>
                <li data-value="San Miguel, Padre Garcia, Batangas">San Miguel, Padre Garcia, Batangas</li>
                <li data-value="Tamak, Padre Garcia, Batangas">Tamak, Padre Garcia, Batangas</li>
                <li data-value="Tangob, Padre Garcia, Batangas">Tangob, Padre Garcia, Batangas</li>
              </ul>
            </div>
          </div>
          <div class="form-group">
            <label for="born">Born</label>
            <input type="date" id="born" name="born" placeholder="Born" value="<?php echo htmlspecialchars($deceased['born'] ?? ''); ?>">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label for="dateDied">Date Died</label>
            <input type="date" id="dateDied" name="dateDied" placeholder="Date Died" value="<?php echo htmlspecialchars($deceased['dateDied'] ?? ''); ?>">
          </div>
          <div class="form-group">
            <label for="age">Age</label>
            <input type="text" id="age" name="age" placeholder="Age" readonly value="<?php echo htmlspecialchars($deceased['age'] ?? ''); ?>">
          </div>
          <div class="form-group">
            <label for="dateInternment">Date of Internment</label>
            <input type="date" id="dateInternment" name="dateInternment" placeholder="Date of Internment" value="<?php echo htmlspecialchars($deceased['dateInternment'] ?? ''); ?>">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label for="apartmentNo">Apartment No.</label>
            <div class="niche-picker-group">
              <input type="text" id="apartmentNo" name="apartmentNo" placeholder="Apartment No." readonly value="<?php echo htmlspecialchars($deceased['nicheID'] ?? ''); ?>">
              <button type="button" id="pickNicheBtn" class="btn pick-niche-btn" title="Pick Niche">
                <i class="fas fa-map-marker-alt"></i>
              </button>
            </div>
          </div>
          <div class="form-group">
            <label for="informantName">Informant Name</label>
            <input type="text" id="informantName" name="informantName" placeholder="Informant Name" value="<?php echo htmlspecialchars($deceased['informantName'] ?? ''); ?>" autocomplete="off" list="informantNameList">
            <datalist id="informantNameList">
              <?php foreach ($informantSuggestions as $suggestion): ?>
                <option value="<?php echo htmlspecialchars($suggestion); ?>"></option>
              <?php endforeach; ?>
            </datalist>
          </div>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn upload">Save</button>
          <a href="<?php echo (!empty($from) && $from === 'records') ? 'Records.php' : 'Mapping.php'; ?>" class="btn secondary" style="margin-left:12px;">Cancel</a>
        </div>
      </form>

      <!-- Custom Modal for Delete Confirmation -->
      <div class="modal-overlay" id="modalOverlay">
        <div class="modal-confirm">
          <h2><i class="fas fa-exclamation-triangle"></i> Confirm Archive</h2>
          <p>Are you sure you want to archive this record?<br>This action will move the record to the archive section.</p>
          <div class="modal-actions">
            <button class="modal-btn confirm" id="modalConfirmBtn">Archive</button>
            <button class="modal-btn cancel" id="modalCancelBtn">Cancel</button>
          </div>
        </div>
      </div>

      <!-- Success Notification -->
      <div id="successNotification" style="display:none;position:fixed;top:32px;right:32px;z-index:10000;background:#2ecc71;color:#fff;padding:18px 32px;border-radius:8px;box-shadow:0 4px 16px rgba(46,204,113,0.15);font-size:1.1rem;font-weight:500;align-items:center;gap:16px;min-width:220px;">
        <span><i class="fas fa-check-circle" style="margin-right:8px;"></i>Record saved successfully!</span>
        <button id="closeNotificationBtn" style="background:none;border:none;color:#fff;font-size:1.2em;cursor:pointer;margin-left:12px;">&times;</button>
      </div>

      <!-- Save Confirmation Modal -->
      <div class="modal-overlay" id="saveModalOverlay" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(44,62,80,0.35);z-index:1000;align-items:center;justify-content:center;">
        <div class="modal-confirm" style="background:#fff;border-radius:12px;box-shadow:0 8px 32px rgba(44,62,80,0.18);padding:32px 28px 24px 28px;max-width:370px;width:90%;text-align:center;position:relative;animation:modalPop .18s cubic-bezier(.4,1.4,.6,1.0);">
          <h2 style="margin:0 0 12px 0;font-size:1.25rem;color:#27ae60;font-weight:600;letter-spacing:0.5px;"><i class="fas fa-check-circle" style="margin-right:8px;"></i>Confirm Save</h2>
          <p style="color:#2d3a4a;margin-bottom:24px;font-size:1rem;line-height:1.5;">Are you sure you want to save these changes?</p>
          <div class="modal-actions" style="display:flex;gap:12px;justify-content:center;">
            <button class="modal-btn confirm" id="saveModalConfirmBtn" style="background:#27ae60;color:#fff;padding:8px 24px;border-radius:7px;border:none;font-weight:500;font-size:1rem;cursor:pointer;transition:background 0.18s,color 0.18s;">Save</button>
            <button class="modal-btn cancel" id="saveModalCancelBtn" style="background:#f5f7fa;color:#2d3a4a;padding:8px 24px;border-radius:7px;border:none;font-weight:500;font-size:1rem;cursor:pointer;transition:background 0.18s,color 0.18s;">Cancel</button>
          </div>
        </div>
      </div>
    </div>
  </div>
  <script>
    // Add this at the start of your script
    let isSubmitting = false;

    document.getElementById('editForm').addEventListener('submit', function(e) {
      e.preventDefault();
      
      if (isSubmitting) {
        return; // Prevent multiple submissions
      }
      
      // Show save confirmation modal
      const saveModalOverlay = document.getElementById('saveModalOverlay');
      const saveModalConfirmBtn = document.getElementById('saveModalConfirmBtn');
      const saveModalCancelBtn = document.getElementById('saveModalCancelBtn');
      
      saveModalOverlay.style.display = 'flex';
      
      // Handle save confirmation
      saveModalConfirmBtn.onclick = function() {
        if (isSubmitting) {
          return; // Prevent multiple clicks
        }
        
        isSubmitting = true;
        saveModalConfirmBtn.disabled = true;
        saveModalConfirmBtn.textContent = 'Saving...';
        
        const formData = new FormData(document.getElementById('editForm'));
        
        fetch('EditNiches.php' + window.location.search, {
          method: 'POST',
          body: formData
        })
        .then(response => response.text())
        .then(html => {
          showSuccessNotification('Record saved successfully!');
          saveModalOverlay.style.display = 'none';
          setTimeout(function() {
            // Redirect to correct page
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('from') === 'records') {
              window.location.href = 'Records.php';
            } else {
              window.location.href = 'Mapping.php';
            }
          }, 1000);
        })
        .catch(error => {
          console.error('Error:', error);
          showErrorNotification('Error saving record. Please try again.');
          saveModalOverlay.style.display = 'none';
          isSubmitting = false;
          saveModalConfirmBtn.disabled = false;
          saveModalConfirmBtn.textContent = 'Save';
        });
      };
      
      // Handle save cancellation
      saveModalCancelBtn.onclick = function() {
        saveModalOverlay.style.display = 'none';
        isSubmitting = false;
      };
      
      // Close save modal on overlay click
      saveModalOverlay.onclick = function(e) {
        if (e.target === saveModalOverlay) {
          saveModalOverlay.style.display = 'none';
          isSubmitting = false;
        }
      };
      
      // Close save modal on ESC key
      document.addEventListener('keydown', function(e) {
        if (e.key === "Escape") {
          saveModalOverlay.style.display = 'none';
          isSubmitting = false;
        }
      });
    });

    // Remove any existing event listeners
    const oldForm = document.getElementById('editForm');
    const newForm = oldForm.cloneNode(true);
    oldForm.parentNode.replaceChild(newForm, oldForm);

    document.getElementById('pickNicheBtn').onclick = function() {
      window.open('Mapping.php?pickNiche=1', 'PickNiche', 'width=900,height=700');
    };
    window.addEventListener('message', function(event) {
      if (event.data && event.data.nicheID) {
        document.getElementById('apartmentNo').value = event.data.nicheID;
      }
    });

    // Store initial values to detect changes
    let initialBorn = document.getElementById('born').value;
    let initialDied = document.getElementById('dateDied').value;
    let initialAge = document.getElementById('age').value;

    // Auto-calculate age when born or dateDied changes
    function calculateAge() {
      const bornInput = document.getElementById('born');
      const diedInput = document.getElementById('dateDied');
      const ageInput = document.getElementById('age');

      // Only recalculate if either born or died changed from initial
      if (
        bornInput.value !== initialBorn ||
        diedInput.value !== initialDied
      ) {
        if (bornInput.value && diedInput.value) {
          const bornDate = new Date(bornInput.value);
          const diedDate = new Date(diedInput.value);
          const currentDate = new Date();
          const minDate = new Date('1900-01-01');

          // Validate date ranges (born and died cannot be in future)
          if (bornDate > currentDate || diedDate > currentDate) {
            ageInput.value = initialAge;
            ageInput.style.borderColor = '#e74c3c';
            ageInput.title = 'Born and died dates cannot be in the future';
            return;
          }

          if (bornDate < minDate || diedDate < minDate) {
            ageInput.value = initialAge;
            ageInput.style.borderColor = '#e74c3c';
            ageInput.title = 'Dates cannot be before year 1900';
            return;
          }

          if (diedDate >= bornDate) {
            let years = diedDate.getFullYear() - bornDate.getFullYear();
            let months = diedDate.getMonth() - bornDate.getMonth();
            let days = diedDate.getDate() - bornDate.getDate();

            let finalYears = years;
            let finalMonths = months;

            if (days < 0) {
              finalMonths--;
            }
            if (finalMonths < 0) {
              finalYears--;
              finalMonths += 12;
            }

            // Limit age to 150 years
            if (finalYears > 150) {
              ageInput.value = initialAge;
              ageInput.style.borderColor = '#e74c3c';
              ageInput.title = 'Age cannot exceed 150 years';
            } else {
              if (finalYears == 0) {
                ageInput.value = finalMonths + ' months old';
              } else {
                ageInput.value = finalYears + ' years old';
              }
              ageInput.style.borderColor = '';
              ageInput.title = '';
            }
          } else {
            ageInput.value = initialAge;
            ageInput.style.borderColor = '#e74c3c';
            ageInput.title = 'Date died must be after born date';
          }
        } else {
          ageInput.value = initialAge;
          ageInput.style.borderColor = '';
          ageInput.title = '';
        }
      } else {
        // If not changed, keep the original age
        ageInput.value = initialAge;
        ageInput.style.borderColor = '';
        ageInput.title = '';
      }
    }

    // Calculate age on page load
    calculateAge();

    // Add event listeners
    document.getElementById('born').addEventListener('change', calculateAge);
    document.getElementById('dateDied').addEventListener('change', calculateAge);

    // Custom modal logic
    const modalOverlay = document.getElementById('modalOverlay');
    const deleteBtn = document.getElementById('deleteBtn');
    const modalConfirmBtn = document.getElementById('modalConfirmBtn');
    const modalCancelBtn = document.getElementById('modalCancelBtn');

    // Show notification logic
    function showSuccessNotification(message) {
      const notif = document.getElementById('successNotification');
      notif.querySelector('span').innerHTML = `<i class="fas fa-check-circle" style="margin-right:8px;"></i>${message}`;
      notif.style.display = 'flex';
      notif.style.background = '#2ecc71';
      
      // Auto-close after 3 seconds
      const timeout = setTimeout(() => {
        notif.style.display = 'none';
      }, 3000);
      
      document.getElementById('closeNotificationBtn').onclick = function() {
        notif.style.display = 'none';
        clearTimeout(timeout);
      };
    }

    function showErrorNotification(message) {
      const notif = document.getElementById('successNotification');
      notif.querySelector('span').innerHTML = `<i class="fas fa-exclamation-circle" style="margin-right:8px;"></i>${message}`;
      notif.style.display = 'flex';
      notif.style.background = '#e74c3c';
      
      // Auto-close after 3 seconds
      const timeout = setTimeout(() => {
        notif.style.display = 'none';
      }, 3000);
      
      document.getElementById('closeNotificationBtn').onclick = function() {
        notif.style.display = 'none';
        clearTimeout(timeout);
      };
    }

    deleteBtn.onclick = function() {
      modalOverlay.style.display = 'flex';
    };

    modalCancelBtn.onclick = function() {
      modalOverlay.style.display = 'none';
    };

    modalConfirmBtn.onclick = function() {
      const form = document.getElementById('deleteForm');
      const formData = new FormData(form);
      
      // Show loading state
      modalConfirmBtn.disabled = true;
      modalConfirmBtn.textContent = 'Archiving...';
      modalCancelBtn.disabled = true;

      fetch('EditNiches.php', {
        method: 'POST',
        body: formData
      })
      .then(response => {
        if (!response.ok) {
          throw new Error('Network response was not ok');
        }
        return response.text();
      })
      .then(() => {
        showSuccessNotification('Record successfully archived');
        modalOverlay.style.display = 'none';
        // Redirect after a short delay
        setTimeout(() => {
          window.location.href = 'Mapping.php';
        }, 1000);
      })
      .catch(error => {
        console.error('Error:', error);
        showErrorNotification('Failed to archive record. Please try again.');
        // Reset button states
        modalConfirmBtn.disabled = false;
        modalConfirmBtn.textContent = 'Archive';
        modalCancelBtn.disabled = false;
      });
    };

    // Optional: close modal on overlay click
    modalOverlay.onclick = function(e) {
      if (e.target === modalOverlay) modalOverlay.style.display = 'none';
    };

    // Optional: ESC key closes modal
    document.addEventListener('keydown', function(e) {
      if (e.key === "Escape") modalOverlay.style.display = 'none';
    });

    // Residency dropdown as icon logic
(function() {
  var btn = document.getElementById('residency-dropdown-btn');
  var list = document.getElementById('residency-dropdown-list');
  var input = document.getElementById('residency');
  if (btn && list && input) {
    btn.onclick = function(e) {
      e.preventDefault();
      list.style.display = (list.style.display === 'block') ? 'none' : 'block';
    };
    list.querySelectorAll('li').forEach(function(item) {
      item.onclick = function() {
        input.value = this.getAttribute('data-value');
        list.style.display = 'none';
      };
    });
    document.addEventListener('mousedown', function(e) {
      if (!list.contains(e.target) && e.target !== btn && e.target !== input) {
        list.style.display = 'none';
      }
    });
    input.addEventListener('focus', function() { list.style.display = 'none'; });
  }
})();

    // Suffix dropdown logic
    (function() {
      var btn = document.getElementById('suffix-dropdown-btn');
      var list = document.getElementById('suffix-dropdown-list');
      var input = document.getElementById('suffixDisplay');
      var hiddenInput = document.getElementById('suffix');
      
      function showSuffixDropdown() {
        list.style.display = 'block';
      }
      
      function hideSuffixDropdown() {
        list.style.display = 'none';
      }

      if (btn && list && input && hiddenInput) {
        btn.onclick = function(e) {
          e.preventDefault();
          if (list.style.display === 'block') {
            hideSuffixDropdown();
          } else {
            showSuffixDropdown();
          }
        };

        input.onclick = function(e) {
          e.preventDefault();
          if (list.style.display === 'block') {
            hideSuffixDropdown();
          } else {
            showSuffixDropdown();
          }
        };

        list.querySelectorAll('li').forEach(function(item) {
          item.onclick = function() {
            input.value = this.textContent;
            hiddenInput.value = this.getAttribute('data-value');
            hideSuffixDropdown();
          };
        });

        document.addEventListener('mousedown', function(e) {
          if (!list.contains(e.target) && e.target !== btn && e.target !== input) {
            hideSuffixDropdown();
          }
        });

        input.addEventListener('focus', function() {
          input.style.borderColor = '#3498db';
          input.style.boxShadow = '0 0 0 2px rgba(52,152,219,0.10)';
          input.style.background = '#fff';
        });

        input.addEventListener('blur', function() {
          if (list.style.display !== 'block') {
            input.style.borderColor = '#e3e7ed';
            input.style.boxShadow = 'none';
            input.style.background = '#f8fafc';
          }
        });
      }
    })();
  </script>
</body>
</html>