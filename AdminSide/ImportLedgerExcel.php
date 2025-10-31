<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../AdminLogin.php");
    exit;
}
include_once '../Includes/db.php';

require_once '../vendor/autoload.php'; // For PhpSpreadsheet (composer install required)

use PhpOffice\PhpSpreadsheet\IOFactory;

function clean($val) {
    if ($val === null) return '';
    if (is_string($val) && strtolower(trim($val)) === 'null') return '';
    return trim($val);
}

$successCount = 0;
$errorCount = 0;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] === UPLOAD_ERR_OK) {
    $fileTmp = $_FILES['excel_file']['tmp_name'];
    $fileType = $_FILES['excel_file']['type'];
    $fileName = $_FILES['excel_file']['name'];

    try {
        $spreadsheet = IOFactory::load($fileTmp);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        // Always skip first row (header), use fixed column letters
        // A: apartmentno, B: payee, C: amount, D: ornumber, E: mcno, F: validity, G: description, H: datepaid
        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            if (!is_array($row)) continue;

            $ApartmentNo = clean($row['A'] ?? '');
            $Payee = clean($row['B'] ?? '');
            $Amount = str_replace([',', '₱', ' '], '', clean($row['C'] ?? ''));
            $ORNumber = clean($row['D'] ?? '');
            $MCNo = clean($row['E'] ?? '');
            $Validity = clean($row['F'] ?? '');
            $Description = clean($row['G'] ?? '');
            $DatePaid = clean($row['H'] ?? '');

            // Skip row if all required columns are empty
            if ($Payee === '' && $Amount === '' && $ORNumber === '') {
                continue;
            }

            // Only require Payee, Amount, ORNumber
            $missing = [];
            if ($Payee === '') $missing[] = 'Payee';
            if ($Amount === '') $missing[] = 'Amount';
            if ($ORNumber === '') $missing[] = 'ORNumber';

            if (!empty($missing)) {
                $errorCount++;
                $errors[] = "Row " . ($i+1) . " skipped: missing " . implode(', ', $missing) . ". Data: " . htmlspecialchars(json_encode($row));
                continue;
            }

            // ApartmentNo and MCNo can be null
            $ApartmentNo_db = ($ApartmentNo === '') ? null : $ApartmentNo;
            $MCNo_db = ($MCNo === '') ? null : $MCNo;

            $stmt = $conn->prepare("INSERT INTO ledger (ApartmentNo, Payee, Amount, ORNumber, MCNo, Validity, Description, DatePaid) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param(
                'ssdsssss',
                $ApartmentNo_db,
                $Payee,
                $Amount,
                $ORNumber,
                $MCNo_db,
                $Validity,
                $Description,
                $DatePaid
            );
            if ($stmt->execute()) {
                $successCount++;
            } else {
                $errorCount++;
                $errors[] = "Row " . ($i+1) . " insert error: " . $stmt->error;
            }
            $stmt->close();
        }
    } catch (Exception $e) {
        $errors[] = "Error reading file: " . $e->getMessage();
    }
} else {
    $errors[] = "No file uploaded or upload error.";
}

// Show result and redirect back
?>
<!DOCTYPE html>
<html>
<head>
    <title>Import Ledger Excel</title>
    <link rel="icon" type="image/png" href="../assets/re logo blue.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #f7f8fa; }
        .modal-content { background:#fff; border-radius:16px; box-shadow:0 8px 32px rgba(44,62,80,0.18); padding:32px 32px 24px 32px; min-width:340px; max-width:95vw; text-align:center; position:relative; margin:60px auto; }
        .success { color:#27ae60; font-weight:600; font-size:1.15rem; }
        .error { color:#e74c3c; font-weight:500; font-size:1.07rem; }
        .btn { background:#506C84; color:#fff; border:none; padding:12px 32px; border-radius:8px; cursor:pointer; font-weight:500; font-size:1rem; margin-top:18px; }
        /* Notification modal styles (copied from Records.php) */
        #successNotification, #failNotification {
            display:none;
            position:fixed;
            top:32px;
            right:32px;
            z-index:10000;
            background:#2ecc71;
            color:#fff;
            padding:18px 32px;
            border-radius:8px;
            box-shadow:0 4px 16px rgba(46,204,113,0.15);
            font-size:1.1rem;
            font-weight:500;
            align-items:center;
            gap:16px;
            min-width:220px;
        }
        #failNotification {
            background:#e74c3c;
        }
        #successNotification .close-btn,
        #failNotification .close-btn {
            background:none;
            border:none;
            color:#fff;
            font-size:1.2em;
            cursor:pointer;
            margin-left:12px;
        }
    </style>
</head>
<body>
    <!-- Success Notification Modal -->
    <div id="successNotification">
        <span>
            <i class="fas fa-check-circle" style="margin-right:8px;"></i>
            <span id="successNotificationText">
                <?php
                if ($successCount > 0) {
                    echo "$successCount record(s) imported.";
                } else {
                    echo ""; // Don't show success if none imported
                }
                ?>
            </span>
        </span>
        <button class="close-btn" onclick="document.getElementById('successNotification').style.display='none';">&times;</button>
    </div>
    <!-- Fail Notification Modal -->
    <div id="failNotification" style="background:#e74c3c;color:#fff;">
        <span>
            <i class="fas fa-times-circle" style="margin-right:8px;"></i>
            <span id="failNotificationText">
                <?php
                if ($successCount === 0) {
                    echo "Fail to Import Data";
                } else if (count($errors) > 0) {
                    echo "Import failed: " . htmlspecialchars($errors[0]);
                }
                ?>
            </span>
        </span>
        <button class="close-btn" onclick="document.getElementById('failNotification').style.display='none';">&times;</button>
    </div>
    <script>
        // Show notification modals after import
        window.onload = function() {
            <?php if ($successCount > 0): ?>
                document.getElementById('successNotification').style.display = 'flex';
                setTimeout(function() {
                    document.getElementById('successNotification').style.display = 'none';
                    window.location.href = 'Ledger.php?tab=payment';
                }, 1000);
            <?php else: ?>
                document.getElementById('failNotification').style.display = 'flex';
                setTimeout(function() {
                    document.getElementById('failNotification').style.display = 'none';
                    window.location.href = 'Ledger.php?tab=payment';
                }, 1000);
            <?php endif; ?>
        };
    </script>
</body>
</html>
