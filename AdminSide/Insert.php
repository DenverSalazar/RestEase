<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../AdminLogin.php");
    exit;
}

// Database connection (adjust credentials as needed)
include_once '../Includes/db.php';
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Add date validation and conversion
  function validateAndFormatDate($dateString) {
    if (empty($dateString)) {
      return '';
    }
    
    $date = DateTime::createFromFormat('Y-m-d', $dateString);
    if ($date !== false) {
      return $date->format('Y-m-d');
    }
    
    return '';
  }

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
  $born = validateAndFormatDate(trim($_POST['born'] ?? ''));
  $residency = trim($_POST['residency'] ?? '');
  $dateDied = validateAndFormatDate(trim($_POST['dateDied'] ?? ''));
  $dateInternment = validateAndFormatDate(trim($_POST['dateInternment'] ?? ''));
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
  if ($born === '') $errors[] = "Valid Born date is required.";
  if ($residency === '') $errors[] = "Residency is required.";
  if ($dateDied === '') $errors[] = "Valid Date Died is required.";
  if ($dateInternment === '') $errors[] = "Valid Date of Internment is required.";
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
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/sidebar.css">
  <link rel="stylesheet" href="../css/Insert.css">
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

  <div class="main-content">
    <div class="cemetery-masterlist-container">
      <div style="display: flex; align-items: center; justify-content: space-between;">
        <div class="cemetery-masterlist-title">Insert Data</div>
      </div>
      <div class="cemetery-masterlist-desc">Fill up the masterlist data</div>
    </div>
    
    <div class="card">
      <div class="top-actions" style="display:flex;justify-content:space-between;align-items:center;gap:12px;width:100%;margin-bottom:60px;padding-right:0;">
        <div class="form-section-title" style="margin:0;">Deceased Information</div>
        <div style="display:flex;gap:12px;">
          <button type="button" class="btn upload" id="importDataBtn">Import Data</button>
          <a href="Records.php"><button type="button" class="btn secondary">Back</button></a>
        </div>
      </div>
      <!-- Excel Import Modal -->
      <div id="excelModal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(44,62,80,0.25); z-index:1000; align-items:center; justify-content:center;">
        <div class="import-modal-content">
          <button type="button" id="closeModal" class="modal-close-btn">&times;</button>
          <div class="modal-header">
            <i class="fas fa-file-excel" style="color:#27ae60; font-size:2.5rem; margin-bottom:12px;"></i>
            <h3>Import Excel File</h3>
            <p>Upload your Excel file to import multiple records at once</p>
          </div>
          <form action="ImportExcel.php" method="post" enctype="multipart/form-data" class="import-form">
            <div class="file-upload-area">
              <i class="fas fa-cloud-upload-alt"></i>
              <input type="file" name="excel_file" accept=".xls,.xlsx,.csv" required id="fileInput">
              <label for="fileInput" class="file-upload-label">
                <span class="upload-text">Choose File</span>
                <span class="file-name">No file selected</span>
              </label>
            </div>
            <div class="file-info">
              <i class="fas fa-info-circle"></i>
              Supported formats: CSV, XLS, XLSX files
            </div>
            <div class="modal-actions">
              <button type="button" id="cancelBtn" class="btn-cancel">Cancel</button>
              <button type="submit" class="btn-upload">
                <i class="fas fa-upload"></i>
                Upload File
              </button>
            </div>
          </form>
        </div>
      </div>
      <script>
        document.getElementById('importDataBtn').onclick = function() {
          document.getElementById('excelModal').style.display = 'flex';
        };

        document.getElementById('closeModal').onclick = function() {
          document.getElementById('excelModal').style.display = 'none';
        };

        document.getElementById('cancelBtn').onclick = function() {
          document.getElementById('excelModal').style.display = 'none';
        };

        document.getElementById('excelModal').onclick = function(e) {
          if (e.target === this) this.style.display = 'none';
        };

        // File input handler
        document.getElementById('fileInput').onchange = function() {
          const fileName = this.files[0] ? this.files[0].name : 'No file selected';
          document.querySelector('.file-name').textContent = fileName;
        };
      </script>
      <div class="form-container">
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
              <label for="residency">Residency</label>
              <input type="text" id="residency" name="residency" placeholder="Residency" value="<?php echo htmlspecialchars($_POST['residency'] ?? ''); ?>">
            </div>
          </div>
          <div class="form-row-2">
            <div class="form-group">
              <label for="born">Born</label>
              <input type="date" id="born" name="born" placeholder="Born" value="<?php echo htmlspecialchars($_POST['born'] ?? ''); ?>">
            </div>
            <div class="form-group">
              <label for="dateDied">Date Died</label>
              <input type="date" id="dateDied" name="dateDied" placeholder="Date Died" value="<?php echo htmlspecialchars($_POST['dateDied'] ?? ''); ?>">
            </div>
            <div class="form-group">
              <label for="age">Age</label>
              <input type="text" id="age" name="age" placeholder="Age will be calculated automatically" readonly>
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
            <button type="submit" class="btn upload">Insert</button>
          </div>
        </form>
      </div>
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

    // Listen for message from Mapping.php
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
  </script>

</body>
</html>
