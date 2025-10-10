<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../AdminLogin.php");
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

$errors = [];
$fieldErrors = []; // Track errors per field

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
  $middleName = trim($_POST['middleName'] ?? '');
  $lastName = trim($_POST['lastName'] ?? '');
  $suffix = isset($_POST['suffix']) ? trim($_POST['suffix']) : null;
  if ($suffix === '' || strtolower($suffix) === '0' || $suffix === '0') {
    $suffix = null;
  }
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
  if ($firstName === '') {
    $errors[] = "First Name is required.";
    $fieldErrors['firstName'] = "First Name is required.";
  }
  if ($middleName === '') {
    $errors[] = "Middle Name is required.";
    $fieldErrors['middleName'] = "Middle Name is required.";
  }
  if ($lastName === '') {
    $errors[] = "Last Name is required.";
    $fieldErrors['lastName'] = "Last Name is required.";
  }
  if ($born === '') {
    $errors[] = "Valid Born date is required.";
    $fieldErrors['born'] = "Valid Born date is required.";
  }
  if ($residency === '') {
    $errors[] = "Residency is required.";
    $fieldErrors['residency'] = "Residency is required.";
  }
  if ($dateDied === '') {
    $errors[] = "Valid Date Died is required.";
    $fieldErrors['dateDied'] = "Valid Date Died is required.";
  }
  if ($dateInternment === '') {
    $errors[] = "Valid Date of Internment is required.";
    $fieldErrors['dateInternment'] = "Valid Date of Internment is required.";
  }
  if ($apartmentNo === '') {
    $errors[] = "Apartment No. is required.";
    $fieldErrors['apartmentNo'] = "Apartment No. is required.";
  }
  if ($informantName === '') {
    $errors[] = "Informant Name is required.";
    $fieldErrors['informantName'] = "Informant Name is required.";
  }

  if (empty($errors)) {
    $stmt = $conn->prepare("INSERT INTO deceased (firstName, middleName, lastName, suffix, age, born, residency, dateDied, dateInternment, nicheID, informantName) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssissssss", $firstName, $middleName, $lastName, $suffix, $age, $born, $residency, $dateDied, $dateInternment, $apartmentNo, $informantName);
    $stmt->execute();
    $stmt->close();

    // Redirect to correct page
    if (!empty($from) && $from === 'records') {
      header("Location: Records.php");
    } else {
      header("Location: Mapping.php");
    }
    exit();
  }
}
// Get 'from' parameter to determine redirect destination
$from = $_GET['from'] ?? '';

// Fetch and display details from ledger, deceased, or assessment_done based on ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$ledger = null;
$deceased = null;
$assessment = null;
$accepted_request = null;
$parsedAssessmentName = [
    'firstName' => '',
    'middleName' => '',
    'lastName' => '',
    'suffix' => ''
];

if ($id) {
    // Fetch ledger entry
    $stmt = $conn->prepare("SELECT * FROM ledger WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $ledger = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($ledger) {
        // 1. Try to fetch from deceased/records table by ApartmentNo (nicheID)
        if (!empty($ledger['ApartmentNo'])) {
            $stmt = $conn->prepare("SELECT * FROM deceased WHERE nicheID = ? LIMIT 1");
            $stmt->bind_param('s', $ledger['ApartmentNo']);
            $stmt->execute();
            $deceased = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        }
        // 2. If not found in deceased, try to fetch from assessment table using user_id (foreign key)
        if (!$deceased) {
            // Try to get user_id from users table by matching Payee (informant name)
            $payee = $ledger['Payee'];
            $user_id = null;
            $nameParts = explode(' ', $payee, 2);
            if (count($nameParts) == 2) {
                $first = $nameParts[0];
                $last = $nameParts[1];
                $stmt = $conn->prepare("SELECT id FROM users WHERE first_name = ? AND last_name = ? LIMIT 1");
                $stmt->bind_param('ss', $first, $last);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    $user_id = $row['id'];
                }
                $stmt->close();
            }
            // If user_id found, get latest assessment for that user
            if ($user_id) {
                $stmt = $conn->prepare("SELECT * FROM assessment WHERE user_id = ? ORDER BY id DESC LIMIT 1");
                $stmt->bind_param('i', $user_id);
                $stmt->execute();
                $assessment = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                // Fetch accepted_request for this assessment if possible
                if ($assessment && isset($assessment['request_id'])) {
                    $stmt = $conn->prepare("SELECT * FROM accepted_request WHERE id = ? LIMIT 1");
                    $stmt->bind_param('i', $assessment['request_id']);
                    $stmt->execute();
                    $accepted_request = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                }
            } else {
                // Fallback: try to match by informant_name and deceased_name
                $stmt = $conn->prepare("SELECT * FROM assessment WHERE informant_name = ? AND deceased_name = ? LIMIT 1");
                $stmt->bind_param('ss', $ledger['Payee'], $ledger['Description']);
                $stmt->execute();
                $assessment = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                // Fetch accepted_request for this assessment if possible
                if ($assessment && isset($assessment['request_id'])) {
                    $stmt = $conn->prepare("SELECT * FROM accepted_request WHERE id = ? LIMIT 1");
                    $stmt->bind_param('i', $assessment['request_id']);
                    $stmt->execute();
                    $accepted_request = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                }
            }
            // --- Split deceased_name for type New ---
            if ($assessment && isset($assessment['type']) && $assessment['type'] === 'New' && !empty($assessment['deceased_name'])) {
                $name = trim($assessment['deceased_name']);
                // Try to split by space, handle suffix (Jr., Sr., III, etc.)
                $parts = preg_split('/\s+/', $name);
                $suffixes = ['JR.', 'SR.', 'III', 'IV', 'JR', 'SR', 'II', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'JR,', 'SR,'];
                $suffix = '';
                if (count($parts) > 2) {
                    // If last part is a suffix
                    $lastPart = strtoupper(str_replace('.', '', end($parts)));
                    if (in_array($lastPart, $suffixes)) {
                        $suffix = array_pop($parts);
                    }
                }
                // Assign
                $parsedAssessmentName['firstName'] = $parts[0] ?? '';
                $parsedAssessmentName['middleName'] = (count($parts) > 2) ? $parts[1] : '';
                $parsedAssessmentName['lastName'] = (count($parts) > 2) ? $parts[2] : ($parts[1] ?? '');
                $parsedAssessmentName['suffix'] = $suffix;
            }
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
  <link rel="stylesheet" href="../css/Insert.css">
  <link rel="stylesheet" href="../css/Clients.css">
  <style>
    body { font-family: 'Poppins', sans-serif; background: #f7f8fa; }
    .container { max-width: 700px; margin: 40px auto; background: #fff; border-radius: 16px; box-shadow: 0 2px 8px rgba(44,62,80,0.08); padding: 32px; }
    h2 { font-size: 1.3rem; font-weight: 600; margin-bottom: 18px; }
    .details-table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
    .details-table th, .details-table td { padding: 8px 10px; font-size: 0.95rem; border-bottom: 1px solid #eee; text-align: left; }
    .details-table th { background: #f7f8fa; font-weight: 500; }
    .details-table tr:last-child td { border-bottom: none; }
    .field-error {
      color: #e74c3c;
      font-size: 0.92em;
      margin-top: 4px;
      margin-bottom: 0;
      font-weight: 500;
      letter-spacing: 0.1px;
    }
    .input-error {
      border-color: #e74c3c !important;
      box-shadow: 0 0 0 2px rgba(231,76,60,0.12);
    }
  </style>
</head>
<body>
  <!-- Sidebar -->
  <?php include '../Includes/sidebar.php'; ?>

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
        <form method="post" autocomplete="off" id="insertForm">
          <div class="form-row">
            <div class="form-group">
              <label for="firstName">First Name</label>
              <input type="text" id="firstName" name="firstName" placeholder="First Name"
                value="<?php echo htmlspecialchars($deceased['firstName'] ?? ($parsedAssessmentName['firstName'] ?? ($assessment['deceased_name'] ?? $_POST['firstName'] ?? ''))); ?>"
                class="<?php echo isset($fieldErrors['firstName']) ? 'input-error' : ''; ?>">
              <?php if (isset($fieldErrors['firstName'])): ?>
                <div class="field-error"><?php echo $fieldErrors['firstName']; ?></div>
              <?php endif; ?>
            </div>
            <div class="form-group">
              <label for="middleName">Middle Name</label>
              <input type="text" id="middleName" name="middleName" placeholder="Middle Name"
                value="<?php echo htmlspecialchars($deceased['middleName'] ?? ($parsedAssessmentName['middleName'] ?? $_POST['middleName'] ?? '')); ?>"
                class="<?php echo isset($fieldErrors['middleName']) ? 'input-error' : ''; ?>">
              <?php if (isset($fieldErrors['middleName'])): ?>
                <div class="field-error"><?php echo $fieldErrors['middleName']; ?></div>
              <?php endif; ?>
            </div>
            <div class="form-group">
              <label for="lastName">Last Name</label>
              <input type="text" id="lastName" name="lastName" placeholder="Last Name"
                value="<?php echo htmlspecialchars($deceased['lastName'] ?? ($parsedAssessmentName['lastName'] ?? $_POST['lastName'] ?? '')); ?>"
                class="<?php echo isset($fieldErrors['lastName']) ? 'input-error' : ''; ?>">
              <?php if (isset($fieldErrors['lastName'])): ?>
                <div class="field-error"><?php echo $fieldErrors['lastName']; ?></div>
              <?php endif; ?>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="suffix">Suffix</label>
              <input type="text" id="suffix" name="suffix" placeholder="e.g. Jr, Sr, III"
                value="<?php echo htmlspecialchars($deceased['suffix'] ?? ($parsedAssessmentName['suffix'] ?? $_POST['suffix'] ?? '')); ?>">
            </div>
            <div class="form-group" style="position:relative;">
              <label for="residency">Residency</label>
              <div style="position:relative;">
                <input type="text" id="residency" name="residency" class="form-control <?php echo isset($fieldErrors['residency']) ? 'input-error' : ''; ?>"
                  placeholder="Enter Residency" required
                  value="<?php echo htmlspecialchars($deceased['residency'] ?? $assessment['residency'] ?? $_POST['residency'] ?? ''); ?>"
                  autocomplete="off" style="padding-right:36px;">
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
              <?php if (isset($fieldErrors['residency'])): ?>
                <div class="field-error"><?php echo $fieldErrors['residency']; ?></div>
              <?php endif; ?>
            </div>
            <div class="form-group">
              <label for="born">Born</label>
              <input type="date" id="born" name="born" placeholder="Born"
                value="<?php echo htmlspecialchars($deceased['born'] ?? $assessment['dob'] ?? $_POST['born'] ?? ''); ?>"
                class="<?php echo isset($fieldErrors['born']) ? 'input-error' : ''; ?>">
              <?php if (isset($fieldErrors['born'])): ?>
                <div class="field-error"><?php echo $fieldErrors['born']; ?></div>
              <?php endif; ?>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="dateDied">Date Died</label>
              <input type="date" id="dateDied" name="dateDied" placeholder="Date Died"
                value="<?php echo htmlspecialchars($deceased['dateDied'] ?? $assessment['dod'] ?? $_POST['dateDied'] ?? ''); ?>"
                class="<?php echo isset($fieldErrors['dateDied']) ? 'input-error' : ''; ?>">
              <?php if (isset($fieldErrors['dateDied'])): ?>
                <div class="field-error"><?php echo $fieldErrors['dateDied']; ?></div>
              <?php endif; ?>
            </div>
            <div class="form-group">
              <label for="age">Age</label>
              <input type="text" id="age" name="age" placeholder="Age" readonly
                value="<?php echo htmlspecialchars($deceased['age'] ?? $assessment['age'] ?? $_POST['age'] ?? ''); ?>">
            </div>
            <div class="form-group">
              <label for="dateInternment">Date of Internment</label>
              <input type="date" id="dateInternment" name="dateInternment" placeholder="Date of Internment"
                value="<?php echo htmlspecialchars($deceased['dateInternment'] ?? ($accepted_request['dateInternment'] ?? ($assessment['dateInternment'] ?? $_POST['dateInternment'] ?? ''))); ?>"
                class="<?php echo isset($fieldErrors['dateInternment']) ? 'input-error' : ''; ?>">
              <?php if (isset($fieldErrors['dateInternment'])): ?>
                <div class="field-error"><?php echo $fieldErrors['dateInternment']; ?></div>
              <?php endif; ?>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="apartmentNo">Apartment No.</label>
              <div class="niche-picker-group">
                <input type="text" id="apartmentNo" name="apartmentNo" placeholder="Apartment No." readonly
                  value="<?php echo htmlspecialchars($deceased['nicheID'] ?? $ledger['ApartmentNo'] ?? $_POST['apartmentNo'] ?? ''); ?>"
                  class="<?php echo isset($fieldErrors['apartmentNo']) ? 'input-error' : ''; ?>">
                <button type="button" id="pickNicheBtn" class="btn pick-niche-btn" title="Pick Niche">
                  <i class="fas fa-map-marker-alt"></i>
                </button>
              </div>
              <?php if (isset($fieldErrors['apartmentNo'])): ?>
                <div class="field-error"><?php echo $fieldErrors['apartmentNo']; ?></div>
              <?php endif; ?>
            </div>
            <div class="form-group">
              <label for="informantName">Informant Name</label>
              <input type="text" id="informantName" name="informantName" placeholder="Informant Name"
                value="<?php echo htmlspecialchars($deceased['informantName'] ?? $assessment['informant_name'] ?? $ledger['Payee'] ?? $_POST['informantName'] ?? ''); ?>"
                autocomplete="off" list="informantNameList"
                class="<?php echo isset($fieldErrors['informantName']) ? 'input-error' : ''; ?>">
              <datalist id="informantNameList">
                <?php foreach ($informantSuggestions as $suggestion): ?>
                  <option value="<?php echo htmlspecialchars($suggestion); ?>"></option>
                <?php endforeach; ?>
              </datalist>
              <?php if (isset($fieldErrors['informantName'])): ?>
                <div class="field-error"><?php echo $fieldErrors['informantName']; ?></div>
              <?php endif; ?>
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

    // Listen for message from Mapping.php (niche picker)
    window.addEventListener('message', function(event) {
      if (event.data && event.data.nicheID) {
        var aptField = document.getElementById('apartmentNo'); // <-- use lowercase 'a'
        if (aptField) aptField.value = event.data.nicheID;
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

    function setResidencyFromDropdown(select) {
      if (select.value) {
        document.getElementById('residency').value = select.value;
        select.selectedIndex = 0;
      }
    }

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

    // Optionally, enhance informantName field with autocomplete dropdown (for browsers that don't support datalist well)
    (function() {
      var input = document.getElementById('informantName');
      var datalist = document.getElementById('informantNameList');
      if (input && datalist) {
        input.addEventListener('input', function() {
          // Optionally, custom JS autocomplete logic can be added here if needed
        });
      }
    })();

    // --- Prevent sidebar navigation during insert ---
    document.addEventListener('DOMContentLoaded', function() {
      // Find all sidebar links
      var sidebar = document.querySelector('.sidebar');
      if (sidebar) {
        var sidebarLinks = sidebar.querySelectorAll('a');
        sidebarLinks.forEach(function(link) {
          // Only intercept if not the current page
          if (!link.classList.contains('active')) {
            link.addEventListener('click', function(e) {
              e.preventDefault();
              showSidebarBlockModal(link.href);
            });
          }
        });
      }
    });

    // Modal for blocking sidebar navigation
    function showSidebarBlockModal(targetHref) {
      // Create modal if not exists
      if (!document.getElementById('sidebarBlockModal')) {
        var modal = document.createElement('div');
        modal.id = 'sidebarBlockModal';
        modal.style = 'position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(44,62,80,0.25);z-index:9999;display:flex;align-items:center;justify-content:center;';
        modal.innerHTML = `
          <div style="background:#fff;padding:32px 28px 24px 28px;border-radius:12px;box-shadow:0 8px 32px rgba(44,62,80,0.18);max-width:370px;width:90%;text-align:center;position:relative;">
            <h2 style="margin:0 0 12px 0;font-size:1.25rem;color:#e74c3c;font-weight:600;letter-spacing:0.5px;">
              <i class="fas fa-exclamation-triangle" style="margin-right:8px;"></i>Complete or Cancel First
            </h2>
            <p style="color:#2d3a4a;margin-bottom:24px;font-size:1rem;line-height:1.5;">
              Please complete the insertion or click "Back" to cancel before navigating to another section.
            </p>
            <button id="sidebarBlockCloseBtn" style="background:#e74c3c;color:#fff;padding:8px 24px;border-radius:7px;border:none;font-weight:500;font-size:1rem;cursor:pointer;">OK</button>
          </div>
        `;
        document.body.appendChild(modal);
        document.getElementById('sidebarBlockCloseBtn').onclick = function() {
          modal.style.display = 'none';
        };
        modal.onclick = function(e) {
          if (e.target === modal) modal.style.display = 'none';
        };
      } else {
        document.getElementById('sidebarBlockModal').style.display = 'flex';
      }
    }

    document.addEventListener('DOMContentLoaded', function() {
    // Get nicheID from URL
    var params = new URLSearchParams(window.location.search);
    var nicheID = params.get('nicheID');
    if (nicheID) {
        var aptField = document.getElementById('apartmentNo'); // <-- use lowercase 'a'
        if (aptField) aptField.value = nicheID;
    }
});
  </script>

</body>
</html>
