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
        if ($fullName !== '') $informantSuggestions[$fullName] = true; // fixed variable name
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

        // 2. If not found in deceased, try to fetch from assessment using multiple fallbacks
        if (!$deceased) {
            $payee = trim($ledger['Payee'] ?? '');
            $foundUserId = null;

            // Clean payee for name search
            $payeeClean = preg_replace('/\s+/', ' ', trim($payee));

            if ($payeeClean !== '') {
                // A) Try to find user by exact full name (case-insensitive) or by email equal to payee
                $likeName = mb_strtolower($payeeClean, 'UTF-8');
                $stmt = $conn->prepare("SELECT id FROM users WHERE LOWER(CONCAT(first_name, ' ', last_name)) = ? LIMIT 1");
                if ($stmt) {
                    $stmt->bind_param('s', $likeName);
                    $stmt->execute();
                    $r = $stmt->get_result()->fetch_assoc();
                    if ($r) $foundUserId = $r['id'];
                    $stmt->close();
                }
                // B) If not found, try matching email exactly (some payees might be email)
                if (!$foundUserId) {
                    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
                    if ($stmt) {
                        $stmt->bind_param('s', $payeeClean);
                        $stmt->execute();
                        $r = $stmt->get_result()->fetch_assoc();
                        if ($r) $foundUserId = $r['id'];
                        $stmt->close();
                    }
                }
                // C) If still not found, try loose LIKE on concatenated name (first + last) or first/last parts
                if (!$foundUserId) {
                    $likeParam = '%' . $payeeClean . '%';
                    $stmt = $conn->prepare("SELECT id FROM users WHERE LOWER(CONCAT(first_name, ' ', last_name)) LIKE ? LIMIT 1");
                    if ($stmt) {
                        $stmt->bind_param('s', $likeParam);
                        $stmt->execute();
                        $r = $stmt->get_result()->fetch_assoc();
                        if ($r) $foundUserId = $r['id'];
                        $stmt->close();
                    }
                }
            }

            // If we found a user id, fetch their latest assessment
            if ($foundUserId) {
                $stmt = $conn->prepare("SELECT * FROM assessment WHERE user_id = ? ORDER BY id DESC LIMIT 1");
                if ($stmt) {
                    $stmt->bind_param('i', $foundUserId);
                    $stmt->execute();
                    $assessment = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                }
                // if assessment found and has request_id, fetch accepted_request for richer context
                if ($assessment && !empty($assessment['request_id'])) {
                    $stmt = $conn->prepare("SELECT * FROM accepted_request WHERE id = ? LIMIT 1");
                    if ($stmt) {
                        $stmt->bind_param('i', $assessment['request_id']);
                        $stmt->execute();
                        $accepted_request = $stmt->get_result()->fetch_assoc();
                        $stmt->close();
                    }
                }
            }

            // 3. If still no assessment, try finding assessment by informant_name = Payee (exact), then LIKE
            if (!$assessment && $payee !== '') {
                // exact informant_name
                $stmt = $conn->prepare("SELECT * FROM assessment WHERE informant_name = ? ORDER BY id DESC LIMIT 1");
                if ($stmt) {
                    $stmt->bind_param('s', $payee);
                    $stmt->execute();
                    $assessment = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                }
                // fallback: informant_name LIKE
                if (!$assessment) {
                    $likeParam = '%' . $payee . '%';
                    $stmt = $conn->prepare("SELECT * FROM assessment WHERE informant_name LIKE ? ORDER BY id DESC LIMIT 1");
                    if ($stmt) {
                        $stmt->bind_param('s', $likeParam);
                        $stmt->execute();
                        $assessment = $stmt->get_result()->fetch_assoc();
                        $stmt->close();
                    }
                }
                // if assessment found, try accepted_request as well
                if ($assessment && !empty($assessment['request_id'])) {
                    $stmt = $conn->prepare("SELECT * FROM accepted_request WHERE id = ? LIMIT 1");
                    if ($stmt) {
                        $stmt->bind_param('i', $assessment['request_id']);
                        $stmt->execute();
                        $accepted_request = $stmt->get_result()->fetch_assoc();
                        $stmt->close();
                    }
                }
            }

            // 4. Final fallback: try matching by deceased name stored in ledger.Description (if ledger.Description contains deceased name)
            if (!$assessment && !empty($ledger['Description'])) {
                $desc = trim($ledger['Description']);
                $stmt = $conn->prepare("SELECT * FROM assessment WHERE deceased_name = ? ORDER BY id DESC LIMIT 1");
                if ($stmt) {
                    $stmt->bind_param('s', $desc);
                    $stmt->execute();
                    $assessment = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                }
                if ($assessment && !empty($assessment['request_id'])) {
                    $stmt = $conn->prepare("SELECT * FROM accepted_request WHERE id = ? LIMIT 1");
                    if ($stmt) {
                        $stmt->bind_param('i', $assessment['request_id']);
                        $stmt->execute();
                        $accepted_request = $stmt->get_result()->fetch_assoc();
                        $stmt->close();
                    }
                }
            }
        }

        // --- Split deceased_name for type New (existing logic continues) ---
        if ($assessment && isset($assessment['type']) && $assessment['type'] === 'New' && !empty($assessment['deceased_name'])) {
            $name = trim($assessment['deceased_name']);
            $parts = preg_split('/\s+/', $name, -1, PREG_SPLIT_NO_EMPTY);

            // Known suffixes (compare uppercased without dots)
            $suffixes = ['JR','SR','III','IV','II','V','VI','VII','VIII','IX','X'];
            $suffix = '';
            // Remove suffix if present
            if (count($parts) >= 2) {
                $lastClean = strtoupper(str_replace('.', '', $parts[count($parts) - 1]));
                if (in_array($lastClean, $suffixes, true)) {
                    $suffix = array_pop($parts);
                }
            }

            // Detect compound surname patterns (e.g. "de la Cruz", "del Cruz", "Dela Cruz", "van Helsing")
            $lower = array_map('strtolower', $parts);
            $n = count($parts);
            $lastName = '';
            $givenParts = [];
            // handle "de la X" (three-token surname)
            if ($n >= 3 && $lower[$n-3] === 'de' && $lower[$n-2] === 'la') {
                $lastName = $parts[$n-3] . ' ' . $parts[$n-2] . ' ' . $parts[$n-1];
                $givenParts = array_slice($parts, 0, $n-3);
            } else {
                // common two-token prefixes for compound surnames
                $prefixes = ['de','del','dela','da','van','von','la','le','mc'];
                if ($n >= 2 && in_array($lower[$n-2], $prefixes, true)) {
                    $lastName = $parts[$n-2] . ' ' . $parts[$n-1];
                    $givenParts = array_slice($parts, 0, $n-2);
                } else {
                    // default: last token is surname
                    if ($n >= 2) {
                        $lastName = $parts[$n-1];
                        $givenParts = array_slice($parts, 0, $n-1);
                    } else {
                        // single token — treat as first name
                        $lastName = '';
                        $givenParts = $parts;
                    }
                }
            }

            // Heuristic: when multiple given tokens exist, treat the LAST given token as middleName
            // and the preceding tokens (one or more) as firstName.
            // Example: ["John","Loyd","Abs"] -> firstName="John Loyd", middleName="Abs"
            $firstName = '';
            $middleNameAssign = '';
            $gCount = count($givenParts);
            if ($gCount === 0) {
                // no given parts (rare) - leave empty or fallback
                $firstName = '';
                $middleNameAssign = '';
            } elseif ($gCount === 1) {
                $firstName = $givenParts[0];
                $middleNameAssign = '';
            } else {
                $middleNameAssign = array_pop($givenParts); // last given becomes middle
                $firstName = trim(implode(' ', $givenParts)); // rest become firstName
            }

            // Final fallbacks
            if ($firstName === '' && $lastName === '' && !empty($parts)) {
                $firstName = $parts[0];
            }

            $parsedAssessmentName['firstName'] = $firstName;
            $parsedAssessmentName['middleName'] = $middleNameAssign;
            $parsedAssessmentName['lastName'] = $lastName;
            $parsedAssessmentName['suffix'] = $suffix;
        }
    }
}

// After attempts to populate $deceased, $assessment and $accepted_request
// Normalize/choose a date of internment from available sources and possible column names
$dateInternmentPrefill = '';
$possibleKeys = ['dateInternment', 'date_internment', 'internment_date', 'dateInternment', 'date_of_internment'];

$sources = [
  $deceased ?? [],
  $accepted_request ?? [],
  $assessment ?? [],
  $_POST ?? []
];

foreach ($sources as $src) {
  if (!is_array($src)) continue;
  foreach ($possibleKeys as $k) {
    if (isset($src[$k]) && $src[$k] !== '' && $src[$k] !== '0000-00-00' && $src[$k] !== null) {
      $dateInternmentPrefill = $src[$k];
      break 2;
    }
  }
}

// If value exists and is in DATETIME format, try to convert to YYYY-MM-DD for the date input
if ($dateInternmentPrefill) {
  // common case: already Y-m-d — keep it; if contains space/time, extract date part
  if (preg_match('/^\d{4}-\d{2}-\d{2}\s/', $dateInternmentPrefill)) {
    $dateInternmentPrefill = substr($dateInternmentPrefill, 0, 10);
  } elseif (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $dateInternmentPrefill)) {
    // convert MM/DD/YYYY -> YYYY-MM-DD
    $parts = explode('/', $dateInternmentPrefill);
    $dateInternmentPrefill = $parts[2] . '-' . $parts[0] . '-' . $parts[1];
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Insert Data</title>
  <link rel="icon" type="image/png" href="../assets/re logo blue.png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/sidebar.css">
  <link rel="stylesheet" href="../css/Insert.css">
  <link rel="stylesheet" href="../css/Clients.css">
  <link rel="stylesheet" href="../css/header.css">
  <style>
    body { font-family: 'Poppins', sans-serif; background: #f7f8fa; }
    /* Improved form design, keep container size unchanged */
    .form-group label {
      font-size: 1.05rem;
      font-weight: 500;
      color: #34495e;
      margin-bottom: 7px;
      letter-spacing: 0.2px;
    }
    .form-group input[type="text"],
    .form-group input[type="date"],
    .form-group .form-control {
      padding: 10px 13px;
      border: 1.5px solid #e3e7ed;
      border-radius: 7px;
      font-size: 1.02rem;
      background: #f8fafc;
      outline: none;
      transition: border-color 0.2s, box-shadow 0.2s;
      box-sizing: border-box;
    }
    .form-group input:focus {
      border-color: #3498db;
      box-shadow: 0 0 0 2px rgba(52,152,219,0.10);
      background: #fff;
    }
    .input-error {
      border-color: #e74c3c !important;
      box-shadow: 0 0 0 2px rgba(231,76,60,0.12);
      background: #fff;
    }
    .field-error {
      color: #e74c3c;
      font-size: 0.92em;
      margin-top: 4px;
      margin-bottom: 0;
      font-weight: 500;
      letter-spacing: 0.1px;
    }
    .form-row {
      display: flex;
      gap: 24px;
      margin-bottom: 18px;
      flex-wrap: wrap;
    }
    .form-group {
      flex: 1 1 220px;
      display: flex;
      flex-direction: column;
      margin-bottom: 0;
      min-width: 180px;
    }
    .form-actions {
      display: flex;
      justify-content: flex-end;
      gap: 12px;
      margin-top: 18px;
    }
    .btn.upload {
      background: linear-gradient(90deg,#3498db 0%,#27ae60 100%);
      color: #fff;
      border: none;
      border-radius: 7px;
      padding: 10px 28px;
      font-size: 1.08rem;
      font-weight: 500;
      cursor: pointer;
      box-shadow: 0 2px 8px rgba(44,62,80,0.08);
      transition: background 0.2s, box-shadow 0.2s;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .btn.upload:hover {
      background: linear-gradient(90deg,#2980b9 0%,#219150 100%);
      box-shadow: 0 4px 16px rgba(44,62,80,0.12);
    }
    .btn.secondary {
      background: #f7f8fa;
      color: #34495e;
      border: 1px solid #e1e4ea;
      border-radius: 7px;
      padding: 10px 22px;
      font-size: 1.05rem;
      font-weight: 500;
      cursor: pointer;
      transition: background 0.2s, border-color 0.2s;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .btn.secondary:hover {
      background: #e1e4ea;
      border-color: #bfc9d2;
    }
    .niche-picker-group {
      display: flex;
      align-items: center;
      gap: 8px;
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
      background: #3498db;
      color: #fff;
      border: none;
      border-radius: 6px;
      padding: 7px 12px;
      font-size: 1.1em;
      cursor: pointer;
      transition: background 0.2s;
    }
    @media (max-width: 900px) {
      .form-row { gap: 12px; }
    }
    @media (max-width: 600px) {
      .form-row { flex-direction: column; gap: 0; }
      .form-group { min-width: 0; }
      .form-actions { flex-direction: column; gap: 8px; }
    }
  </style>
</head>
<body>
  <!-- Sidebar -->
  <?php include '../Includes/sidebar.php'; ?>
   <?php include '../Includes/header.php'; ?>

  <div class="main-content">
    <div class="cemetery-masterlist-container">
      <div style="display: flex; align-items: center; justify-content: space-between;">
        <div class="cemetery-masterlist-title">Insert Data</div>
      </div>
      <div class="cemetery-masterlist-desc" style="color:#6c7a89;font-size:1.08rem;margin-top:2px;">Fill up the masterlist data</div>
    </div>
    <div class="card">
      <div class="top-actions" style="display:flex;justify-content:space-between;align-items:center;gap:12px;width:100%;margin-bottom:38px;padding-right:0;">
        <div class="form-section-title" style="margin:0;">Deceased Information</div>
        <div style="display:flex;gap:12px;">
          <button type="button" class="btn upload" id="importDataBtn"><i class="fas fa-file-import" style="margin-right:7px;"></i>Import Data</button>
          <button type="button" class="btn secondary" id="backBtn" data-referrer="<?php echo htmlspecialchars($_SERVER['HTTP_REFERER'] ?? ''); ?>"><i class="fas fa-arrow-left" style="margin-right:7px;"></i>Back</button>
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

        // Back button behavior: go to referring page if available, otherwise history.back(), otherwise fallback to Records.php
        (function() {
          var backBtn = document.getElementById('backBtn');
          if (backBtn) {
            backBtn.addEventListener('click', function() {
              var ref = this.getAttribute('data-referrer');
              // If server-provided referrer exists and looks like a same-origin or valid URL, use it
              if (ref) {
                try {
                  // optional: basic safety check - only navigate if it's not empty
                  window.location.href = ref;
                  return;
                } catch (e) {
                  // ignore and fallback
                }
              }
              // If no referrer from server, try history
              if (history.length > 1) {
                history.back();
                return;
              }
              // Final fallback
              window.location.href = 'Records.php';
            });
          }
        })();
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
              <?php /* Middle Name is now optional, so don't show error */ ?>
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
            <div class="form-group" style="position:relative;">
              <label for="suffixDisplay">Suffix</label>
              <div style="position:relative;">
                <input type="text" id="suffixDisplay" readonly placeholder="Select Suffix" style="padding-right:36px; background:#f8fafc; cursor:pointer; z-index:2; position:relative;"
                  value="<?php echo htmlspecialchars($deceased['suffix'] ?? $parsedAssessmentName['suffix'] ?? $_POST['suffix'] ?? ''); ?>">
                <input type="hidden" id="suffix" name="suffix" value="<?php echo htmlspecialchars($deceased['suffix'] ?? $parsedAssessmentName['suffix'] ?? $_POST['suffix'] ?? ''); ?>">
                <button type="button" id="suffix-dropdown-btn" style="position:absolute;top:50%;right:6px;transform:translateY(-50%);background:transparent;border:none;padding:0;cursor:pointer;z-index:3;">
                  <i class="fas fa-chevron-down" style="font-size:1.1em;color:#888;"></i>
                </button>
                <ul id="suffix-dropdown-list" style="display:none;position:absolute;top:100%;left:0;width:100%;background:#fff;box-shadow:0 2px 8px rgba(0,0,0,0.08);border-radius:6px;max-height:220px;overflow-y:auto;z-index:10;margin:2px 0 0 0;padding:0;list-style:none;">
                  <li data-value="">(None)</li>
                  <li data-value="Sr.">Sr.</li>
                  <li data-value="Jr.">Jr.</li>
                  <li data-value="II">II</li>
                  <li data-value="III">III</li>
                  <li data-value="IV">IV</li>
                  <li data-value="V">V</li>
                  <li data-value="VI">VI</li>
                  <li data-value="VII">VII</li>
                  <li data-value="VIII">VIII</li>
                  <li data-value="IX">IX</li>
                  <li data-value="X">X</li>
                </ul>
              </div>
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
                value="<?php echo htmlspecialchars($dateInternmentPrefill !== '' ? $dateInternmentPrefill : ($deceased['dateInternment'] ?? ($accepted_request['dateInternment'] ?? ($assessment['dateInternment'] ?? $_POST['dateInternment'] ?? '')))); ?>"
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
            <button type="submit" class="btn upload"><i class="fas fa-plus" style="margin-right:7px;"></i>Insert</button>
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

    // Add event listeners for both change and input events
    document.getElementById('born').addEventListener('change', calculateAge);
    document.getElementById('born').addEventListener('input', calculateAge);
    document.getElementById('dateDied').addEventListener('change', calculateAge);
    document.getElementById('dateDied').addEventListener('input', calculateAge);

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
  <script>
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
        input.value = this.getAttribute('data-value');
        hiddenInput.value = this.getAttribute('data-value');
        hideSuffixDropdown();
      };
    });
    document.addEventListener('mousedown', function(e) {
      if (!list.contains(e.target) && e.target !== btn && e.target !== input) {
        hideSuffixDropdown();
      }
    });
    input.addEventListener('focus', function() { hideSuffixDropdown(); });
  }
})();
  </script>

</body>
</html>
