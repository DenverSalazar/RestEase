<?php
// Database connection (adjust credentials as needed)
include_once '../Includes/db.php';
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
      // First, get the ID of the record we want to update
      $stmt = $conn->prepare("SELECT id FROM deceased WHERE nicheID = ? LIMIT 1");
      $stmt->bind_param("s", $originalNicheID);
      $stmt->execute();
      $result = $stmt->get_result();
      $record = $result->fetch_assoc();
      $stmt->close();

      if ($record) {
        $recordId = $record['id'];
        
        // Check if new nicheID is already occupied
        $checkStmt = $conn->prepare("SELECT id FROM deceased WHERE nicheID = ? AND id != ? LIMIT 1");
        $checkStmt->bind_param("si", $apartmentNo, $recordId);
        $checkStmt->execute();
        $checkStmt->store_result();
        
        if ($checkStmt->num_rows > 0) {
          $errors[] = "The selected Apartment No. is already occupied.";
          $checkStmt->close();
        } else {
          $checkStmt->close();
          
          // Update the specific record by its ID
          $updateStmt = $conn->prepare("UPDATE deceased SET firstName=?, lastName=?, age=?, born=?, residency=?, dateDied=?, dateInternment=?, informantName=?, nicheID=? WHERE id=?");
          $updateStmt->bind_param("ssissssssi", $firstName, $lastName, $age, $born, $residency, $dateDied, $dateInternment, $informantName, $apartmentNo, $recordId);
          $updateStmt->execute();
          $updateStmt->close();
          
          // Redirect without highlight parameter
          header("Location: Mapping.php");
          exit();
        }
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
      // Redirect without highlight parameter
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
<body style="min-height: 100vh; background: #fff; overflow: hidden;">
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
  <div class="form-container" style="width: 1230px; max-width: 100%; margin: 40px 10px 10px auto; padding: 18px; box-sizing: border-box;">
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
        
        fetch('EditNiches.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.text())
        .then(html => {
          showSuccessNotification('Record saved successfully!');
          saveModalOverlay.style.display = 'none';
          setTimeout(function() {
            window.location.href = 'Mapping.php';
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
  </script>
</body>
</html>
