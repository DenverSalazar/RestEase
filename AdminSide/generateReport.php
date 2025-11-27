<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}
?>
<?php
include_once '../Includes/db.php';

// Helper: get start/end dates from filter/value
function getDateRange($filter, $value) {
    if ($filter === 'week' && preg_match('/^(\d{4})-W(\d{2})$/', $value, $m)) {
        $year = intval($m[1]);
        $week = intval($m[2]);
        $dto = new DateTime();
        $dto->setISODate($year, $week);
        $start = $dto->format('Y-m-d');
        $dto->modify('+6 days');
        $end = $dto->format('Y-m-d');
        return [$start, $end];
    }
    if ($filter === 'month' && preg_match('/^(\d{4})-(\d{2})$/', $value, $m)) {
        $start = "{$m[1]}-{$m[2]}-01";
        $end = date('Y-m-t', strtotime($start));
        return [$start, $end];
    }
    if ($filter === 'year' && preg_match('/^\d{4}$/', $value)) {
        $start = "$value-01-01";
        $end = "$value-12-31";
        return [$start, $end];
    }
    return [null, null];
}

function formatReportDate($date) {
    if (!$date) return '';
    $ts = strtotime($date);
    return strtoupper(date('M-d-Y', $ts));
}

$filter = $_GET['filter'] ?? '';
$value = $_GET['value'] ?? '';
$types = isset($_GET['types']) ? explode(',', $_GET['types']) : [];
$allTypesSelected = in_array('All', $types, true);
list($startDate, $endDate) = getDateRange($filter, $value);

$reportRows = [];
$totalIncome = 0;
$totalCount = 0;

if ($startDate && $endDate) {
    $sql = "SELECT * FROM ledger WHERE DatePaid >= ? AND DatePaid <= ?";
    $params = [$startDate, $endDate];
    $typesFiltered = array_filter($types, function($t) { return $t !== '' && $t !== 'All'; });
    if ($typesFiltered && !$allTypesSelected) {
        // Build IN clause for Description
        $inClause = implode(',', array_fill(0, count($typesFiltered), '?'));
        $sql .= " AND Description IN ($inClause)";
        $params = array_merge($params, $typesFiltered);
    }
    $sql .= " ORDER BY DatePaid DESC";
    $stmt = $conn->prepare($sql);
    // Bind params dynamically
    $typesStr = str_repeat('s', count($params));
    $stmt->bind_param($typesStr, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $reportRows[] = $row;
        $totalIncome += floatval($row['Amount']);
        $totalCount++;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Income Report</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family:'Poppins',sans-serif;background:#f8f9fa;margin:0;padding:0; }
        .report-container { max-width:900px;margin:40px auto;background:#fff;border-radius:16px;box-shadow:0 2px 8px rgba(44,62,80,0.08);padding:32px; }
        h1 { font-size:2rem;font-weight:700;margin-bottom:8px; }
        .summary { margin-bottom:24px; }
        .summary span { display:inline-block;margin-right:32px;font-size:1.15rem; }
        table { width:100%;border-collapse:collapse;margin-top:18px; }
        th, td { padding:10px 12px;border-bottom:1px solid #e0e0e0;text-align:left; }
        th { background:#f1f9ff;font-weight:600; }
        tr:last-child td { border-bottom:none; }
        .amount { font-weight:600;color:#27ae60; }
        .back-btn { background:#0077b6;color:#fff;border:none;padding:8px 18px;border-radius:7px;font-weight:500;cursor:pointer;margin-bottom:18px; }
    </style>
</head>
<body>
    <div class="report-container">
        <button onclick="window.history.back()" class="back-btn"><i class="fas fa-arrow-left"></i> Back</button>
        <!-- Print button -->
        <button onclick="window.print()" class="back-btn" style="background:#27ae60;margin-left:8px;"><i class="fas fa-print"></i> Print Report</button>
        <h1>Income Report</h1>
        <div class="summary">
            <span>
                <strong>Date Range:</strong>
                <?php
                    echo formatReportDate($startDate) . ' to ' . formatReportDate($endDate);
                ?>
            </span>
            <span><strong>Total Payments:</strong> <?php echo $totalCount; ?></span>
            <span><strong>Total Income:</strong> <span class="amount">₱<?php echo number_format($totalIncome,2); ?></span></span>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Apt No.</th>
                    <th>Payee Name</th>
                    <th>Deceased Name</th>
                    <th>Date Paid</th>
                    <th>Amount</th>
                    <th>Description</th>
                    <th>OR Number</th>
                    <th>Validity</th>
                    <th>MC No.</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($reportRows): foreach ($reportRows as $row): ?>
                <tr>
                    <td><?php echo !empty($row['ApartmentNo']) ? htmlspecialchars($row['ApartmentNo']) : 'No data'; ?></td>
                    <td><?php echo !empty($row['Payee']) ? htmlspecialchars($row['Payee']) : 'No data'; ?></td>
                    <td><?php echo !empty($row['DeceasedName']) ? htmlspecialchars($row['DeceasedName']) : 'No data'; ?></td>
                    <td><?php echo !empty($row['DatePaid']) ? htmlspecialchars($row['DatePaid']) : 'No data'; ?></td>
                    <td class="amount">₱<?php echo isset($row['Amount']) && $row['Amount'] !== '' ? number_format($row['Amount'],2) : 'No data'; ?></td>
                    <td><?php echo !empty($row['Description']) ? htmlspecialchars($row['Description']) : 'No data'; ?></td>
                    <td><?php echo !empty($row['ORNumber']) ? htmlspecialchars($row['ORNumber']) : 'No data'; ?></td>
                    <td><?php echo !empty($row['Validity']) ? htmlspecialchars($row['Validity']) : 'No data'; ?></td>
                    <td><?php echo !empty($row['MCNo']) ? htmlspecialchars($row['MCNo']) : 'No data'; ?></td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="9" style="text-align:center;color:#888;">No payments found for this period.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <style>
        @media print {
            body { background: #fff !important; }
            .report-container { box-shadow: none !important; margin: 0 !important; }
            .back-btn { display: none !important; }
        }
    </style>
</body>
</html>