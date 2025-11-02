<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}
include_once '../Includes/db.php';

// helper: safe value
function safeVal($v) {
    if (!isset($v)) return '';
    $v = trim($v);
    return $v === '' ? '' : $v;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: Certificate.php');
    exit;
}

if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
    $msg = 'No file uploaded or upload error.';
    echo "<p>$msg</p><p><a href='Certificate.php'>Go back</a></p>";
    exit;
}

$allowed = ['csv','xls','xlsx'];
$origName = $_FILES['excel_file']['name'];
$ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
$uploadTmp = $_FILES['excel_file']['tmp_name'];

// path for storing uploaded non-CSV files
$uploadDir = __DIR__ . '/uploads';
if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);

$messages = [];

if ($ext === 'csv') {
    // parse CSV
    if (($handle = fopen($uploadTmp, 'r')) === false) {
        $messages[] = 'Unable to open uploaded CSV.';
    } else {
        // read header and build mapping
        $header = fgetcsv($handle);
        if (!$header) {
            $messages[] = 'CSV seems empty.';
        } else {
            // normalize header names to lower keys
            $map = [];
            foreach ($header as $i => $h) {
                $k = strtolower(trim($h));
                $map[$k] = $i;
            }
            // expected fields (possible header names)
            $fieldKeys = [
                'aptno' => ['aptno','apartment','apartmentno','nicheid','apartment_no','apartment no'],
                'nameofdeceased' => ['nameofdeceased','name of deceased','deceased','name'],
                'informantname' => ['informantname','informant name','payee'],
                'informantaddress' => ['informantaddress','informant address','barangay','informant_addr'],
                'addressofdeceased' => ['addressofdeceased','address of deceased','address'],
                'datedied' => ['datedied','date died','date_died'],
                'dateinternment' => ['dateinternment','date internment','date_internment'],
                'dnew' => ['dnew','new','register_death'],
                'drenew' => ['drenew','renew','renewal'],
                'dtransfer' => ['dtransfer','transfer'],
                'dreopen' => ['dreopen','reopen'],
                'dreenter' => ['dreenter','reenter'],
                'datepaid' => ['datepaid','date paid'],
                'payee' => ['payee'],
                'amount' => ['amount'],
                'ornumber' => ['ornumber','or','or_no','or number'],
                'validity' => ['validity'],
                'mcno' => ['mcno','mc no','mc_number']
            ];
            // determine index mapping
            $idx = [];
            foreach ($fieldKeys as $target => $names) {
                foreach ($names as $n) {
                    if (isset($map[$n])) { $idx[$target] = $map[$n]; break; }
                }
                if (!isset($idx[$target])) $idx[$target] = null;
            }

            // prepared insert
            $stmt = $conn->prepare("INSERT INTO certification (AptNo, NameOfDeceased, InformantName, InformantAddress, AddressOfDeceased, DateDied, DateInternment, DNew, DRenew, DTransfer, DReOpen, DReEnter, DatePaid, Payee, Amount, ORNumber, Validity, MCNo, AdminName) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if (!$stmt) {
                $messages[] = 'DB prepare failed: ' . $conn->error;
            } else {
                // determine admin display name as in Certificate.php
                $adminDisplayName = '';
                if (isset($_SESSION['admin_id'])) {
                    $adminId = (int)$_SESSION['admin_id'];
                    $pRes = $conn->query("SELECT display_name, first_name, last_name FROM admin_profiles WHERE admin_id = $adminId LIMIT 1");
                    if ($pRes && $pRes->num_rows > 0) {
                        $p = $pRes->fetch_assoc();
                        $adminDisplayName = !empty($p['display_name']) ? $p['display_name'] : trim($p['first_name'].' '.$p['last_name']);
                    }
                }
                $adminDisplayName = strtoupper($adminDisplayName);

                $rowCount = 0;
                while (($row = fgetcsv($handle)) !== false) {
                    // skip completely empty rows
                    $allEmpty = true;
                    foreach ($row as $c) { if (trim($c) !== '') { $allEmpty = false; break; } }
                    if ($allEmpty) continue;

                    // build values by mapping
                    $AptNo = $idx['aptno'] !== null ? safeVal($row[$idx['aptno']] ?? '') : '';
                    $NameOfDeceased = $idx['nameofdeceased'] !== null ? safeVal($row[$idx['nameofdeceased']] ?? '') : '';
                    $InformantName = $idx['informantname'] !== null ? safeVal($row[$idx['informantname']] ?? '') : '';
                    $InformantAddress = $idx['informantaddress'] !== null ? safeVal($row[$idx['informantaddress']] ?? '') : '';
                    $AddressOfDeceased = $idx['addressofdeceased'] !== null ? safeVal($row[$idx['addressofdeceased']] ?? '') : '';
                    $DateDied = $idx['datedied'] !== null ? safeVal($row[$idx['datedied']] ?? '') : '';
                    $DateInternment = $idx['dateinternment'] !== null ? safeVal($row[$idx['dateinternment']] ?? '') : '';
                    // actions normalized to '✔' if non-empty
                    $DNew = ($idx['dnew'] !== null && trim(($row[$idx['dnew']] ?? '')) !== '') ? '✔' : '';
                    $DRenew = ($idx['drenew'] !== null && trim(($row[$idx['drenew']] ?? '')) !== '') ? '✔' : '';
                    $DTransfer = ($idx['dtransfer'] !== null && trim(($row[$idx['dtransfer']] ?? '')) !== '' ) ? '✔' : '';
                    $DReOpen = ($idx['dreopen'] !== null && trim(($row[$idx['dreopen']] ?? '')) !== '' ) ? '✔' : '';
                    $DReEnter = ($idx['dreenter'] !== null && trim(($row[$idx['dreenter']] ?? '')) !== '' ) ? '✔' : '';
                    $DatePaid = $idx['datepaid'] !== null ? safeVal($row[$idx['datepaid']] ?? '') : '';
                    $Payee = $idx['payee'] !== null ? safeVal($row[$idx['payee']] ?? '') : $InformantName;
                    $Amount = $idx['amount'] !== null ? safeVal($row[$idx['amount']] ?? '') : '';
                    // numeric cleanup for amount
                    if ($Amount !== '') {
                        $Amount = preg_replace('/[^\d.\-]/', '', $Amount);
                    }
                    $ORNumber = $idx['ornumber'] !== null ? safeVal($row[$idx['ornumber']] ?? '') : '';
                    $Validity = $idx['validity'] !== null ? safeVal($row[$idx['validity']] ?? '') : '';
                    $MCNo = $idx['mcno'] !== null ? safeVal($row[$idx['mcno']] ?? '') : '';

                    // bind and execute
                    $stmt->bind_param(
                        'sssssssssssssssdsss',
                        $AptNo,
                        $NameOfDeceased,
                        $InformantName,
                        $InformantAddress,
                        $AddressOfDeceased,
                        $DateDied,
                        $DateInternment,
                        $DNew,
                        $DRenew,
                        $DTransfer,
                        $DReOpen,
                        $DReEnter,
                        $DatePaid,
                        $Payee,
                        $Amount,
                        $ORNumber,
                        $Validity,
                        $MCNo,
                        $adminDisplayName
                    );
                    if ($stmt->execute()) {
                        $rowCount++;
                    } else {
                        $messages[] = "Insert failed on row $rowCount: " . $stmt->error;
                    }
                } // end rows
                $stmt->close();
                $messages[] = "Imported rows: $rowCount";
            } // end db prepare
        } // end header valid
        fclose($handle);
    }
} else {
    // For xls/xlsx we simply move the file to uploads with timestamped name as a fallback.
    $dest = $uploadDir . '/' . date('Ymd_His') . '_' . preg_replace('/[^A-Za-z0-9\-\_\.]/', '_', $origName);
    if (move_uploaded_file($uploadTmp, $dest)) {
        $messages[] = 'File saved to uploads. To parse XLS/XLSX install PhpSpreadsheet and implement parsing.';
    } else {
        $messages[] = 'Unable to move uploaded file for XLS/XLSX.';
    }
}

// close DB and show result with redirect back to Certificate.php
$conn->close();

// build simple result page
$all = implode("\n", array_map(function($m){ return htmlspecialchars($m); }, $messages));
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Import Result</title>
  <link rel="icon" type="image/png" href="../assets/re logo blue.png">
  <style>body{font-family:Arial,Helvetica,sans-serif;background:#f7fafc;padding:24px;color:#222} .box{background:#fff;padding:18px;border-radius:10px;max-width:720px;margin:40px auto;box-shadow:0 6px 20px rgba(0,0,0,0.06)}</style>
</head>
<body>
  <div class="box">
    <h2>Import Result</h2>
    <div style="margin:12px 0;white-space:pre-wrap;"><?php echo $all; ?></div>
    <div style="margin-top:16px;">
      <a href="Certificate.php" style="display:inline-block;padding:10px 16px;background:#506C84;color:#fff;border-radius:8px;text-decoration:none;">Back to Certificates</a>
    </div>
  </div>
  <script>setTimeout(function(){ window.location.href = 'Certificate.php'; }, 2500);</script>
</body>
</html>
