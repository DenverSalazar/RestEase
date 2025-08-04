<?php
// Database connection (adjust credentials as needed)
include_once '../Includes/db.php';
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// Get record ID or nicheID from query string
$recordId = $_GET['id'] ?? '';
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

// Get original nicheID from query string or from database
$originalNicheID = $_GET['nicheID'] ?? '';

// If editing by ID, fetch data for this record
if ($recordId) {
  $stmt = $conn->prepare("SELECT * FROM deceased WHERE id = ? LIMIT 1");
  $stmt->bind_param("i", $recordId);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result && $row = $result->fetch_assoc()) {
    $deceased = $row;
    $originalNicheID = $row['nicheID'];
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
  $lastName = trim($_POST['lastName'] ?? '');
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

  // Validate date logic
  if ($born && $dateDied) {
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

  // Calculate age from born and dateDied
  $age = '';
  if ($born && $dateDied) {
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
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/sidebar.css">
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
    <div class="main-content">
    <div class="cemetery-masterlist-container">
      <div style="display: flex; align-items: center; justify-content: space-between;">
        <div class="cemetery-masterlist-title">Edit Data</div>
      </div>
      <div class="cemetery-masterlist-desc">Edit the masterlist data</div>
    </div>
    
    <div class="card">
            <div class="top-actions" style="display:flex;justify-content:space-between;align-items:center;gap:12px;width:100%;margin-bottom:60px;padding-right:0;">
        <div class="form-section-title" style="margin:0;">Deceased Information</div>
        <div style="display:flex;gap:12px;">
          <form id="deleteForm" method="post" style="display:inline;">
            <input type="hidden" name="apartmentNo" value="<?php echo htmlspecialchars($deceased['nicheID']); ?>">
            <input type="hidden" name="delete" value="1">
            <button type="button" class="btn delete-btn" id="deleteBtn">Delete</button>
          </form>
        </div>
      </div>
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
            <label for="residency">Residency</label>
            <input type="text" id="residency" name="residency" placeholder="Residency" value="<?php echo htmlspecialchars($deceased['residency']); ?>">
          </div>
        </div>
        <div class="form-row-2">
          <div class="form-group">
            <label for="born">Born</label>
            <input type="date" id="born" name="born" placeholder="Born" value="<?php echo htmlspecialchars($deceased['born']); ?>">
          </div>
          <div class="form-group">
            <label for="dateDied">Date Died</label>
            <input type="date" id="dateDied" name="dateDied" placeholder="Date Died" value="<?php echo htmlspecialchars($deceased['dateDied']); ?>">
          </div>
          <div class="form-group">
            <label for="age">Age</label>
            <input type="text" id="age" name="age" placeholder="Age will be calculated automatically" readonly value="<?php echo htmlspecialchars($deceased['age']); ?>">
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

    // Auto-calculate age when born or dateDied changes
    function calculateAge() {
      const bornInput = document.getElementById('born');
      const diedInput = document.getElementById('dateDied');
      const ageInput = document.getElementById('age');
      
      if (bornInput.value && diedInput.value) {
        const bornDate = new Date(bornInput.value);
        const diedDate = new Date(diedInput.value);
        const currentDate = new Date();
        const minDate = new Date('1900-01-01');
        
        // Validate date ranges (born and died cannot be in future)
        if (bornDate > currentDate || diedDate > currentDate) {
          ageInput.value = '';
          ageInput.style.borderColor = '#e74c3c';
          ageInput.title = 'Born and died dates cannot be in the future';
          return;
        }
        
        if (bornDate < minDate || diedDate < minDate) {
          ageInput.value = '';
          ageInput.style.borderColor = '#e74c3c';
          ageInput.title = 'Dates cannot be before year 1900';
          return;
        }
        
        if (diedDate >= bornDate) {
          const years = diedDate.getFullYear() - bornDate.getFullYear();
          const months = diedDate.getMonth() - bornDate.getMonth();
          const days = diedDate.getDate() - bornDate.getDate();
          
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
            ageInput.value = '';
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
          ageInput.value = '';
          ageInput.style.borderColor = '#e74c3c';
          ageInput.title = 'Date died must be after born date';
        }
      } else {
        ageInput.value = '';
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
  </script>
</body>
</html>
