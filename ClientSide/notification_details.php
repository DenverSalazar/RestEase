<?php
session_start();
include '../Includes/navbar2.php';
include_once '../Includes/db.php';

$user_id = $_SESSION['user_id'] ?? null;
$id = $_GET['id'] ?? null;
$type = $_GET['type'] ?? null;
$created_at = $_GET['created_at'] ?? null;
$notif = null;
$assessment = null;

if ($user_id && $id && ($type === 'accepted' || $type === 'denied')) {
    $table = $type === 'accepted' ? 'accepted_request' : 'denied_request';
    $stmt = $conn->prepare("SELECT id, type, first_name, middle_name, last_name, created_at FROM $table WHERE id = ? AND user_id = ? LIMIT 1");
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $notif = $result->fetch_assoc();
    $stmt->close();
} elseif ($user_id && $type === 'assessment' && $created_at) {
    // Find the assessment notification
    $stmt = $conn->prepare("SELECT message, link, created_at FROM notifications WHERE user_id = ? AND created_at = ? LIMIT 1");
    $stmt->bind_param("is", $user_id, $created_at);
    $stmt->execute();
    $result = $stmt->get_result();
    $assessment = $result->fetch_assoc();
    $stmt->close();
    // If found, try to get the related request details
    if ($assessment && !empty($assessment['link'])) {
        // Extract request_id from link
        if (preg_match('/request_id=(\d+)/', $assessment['link'], $matches)) {
            $request_id = $matches[1];
            $stmt = $conn->prepare("SELECT ar.*, u.first_name AS user_first, u.last_name AS user_last, u.email FROM accepted_request ar JOIN users u ON ar.user_id = u.id WHERE ar.id = ? AND ar.user_id = ? LIMIT 1");
            $stmt->bind_param("ii", $request_id, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $details = $result->fetch_assoc();
            $stmt->close();
        } else {
            $details = null;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/footer.css">
    <style>
        /* App background + smooth rendering */
        body { background: #f6f8fb; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }

        /* Card */
        .details-card {
            max-width: 720px;
            margin: 2rem auto;
            border-radius: 16px;
            background: #fff;
            border: 1px solid #e9eef5;
            box-shadow: 0 10px 30px rgba(20, 40, 80, 0.06);
            padding: 28px 28px 22px;
        }

        /* Header w/ icon + pill */
        .details-header { display: flex; align-items: center; gap: 14px; margin-bottom: 12px; }
        .icon-circle {
            width: 40px; height: 40px; border-radius: 50%;
            display: grid; place-items: center; color: #fff; flex: none;
        }
        .icon-circle.success { background: #2ecc71; }
        .icon-circle.danger  { background: #e74c3c; }
        .icon-circle.warn    { background: #f39c12; }
        .details-title-txt { font-size: 1.15rem; font-weight: 700; color: #1f2a37; }
        .status-pill {
            margin-left: 8px; padding: 2px 10px; border-radius: 999px;
            font-size: .78rem; font-weight: 700; letter-spacing: .3px;
            display: inline-block; vertical-align: middle;
        }
        .status-pill.success { background: rgba(46,204,113,.15); color: #1e8449; }
        .status-pill.danger  { background: rgba(231,76,60,.15); color: #c0392b; }
        .status-pill.warn    { background: rgba(243,156,18,.18); color: #a56500; }

        .divider { height: 1px; background: linear-gradient(90deg,#f0f3f8, #e6ecf5, #f0f3f8); margin: 10px 0 14px; }

        /* Key/Value list */
        .kv-grid { display: grid; gap: 10px; }
        @media (min-width: 576px) { .kv-grid { grid-template-columns: 1fr; } }
        .kv-row, .detail-row { /* also upgrade existing .detail-row */
            display: flex; align-items: baseline; justify-content: space-between;
            gap: 12px; padding: 8px 0;
            border-bottom: 1px dashed #eef2f8;
        }
        .kv-row:last-child, .detail-row:last-child { border-bottom: none; }
        .kv-label, .detail-label { font-weight: 600; color: #374151; min-width: 140px; font-size: .95rem; }
        .kv-value, .detail-value { color: #566173; font-size: .95rem; text-align: right; flex: 1; }

        /* Back button */
        .btn-back { color: #2463eb; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; }
        .btn-back i { transition: transform .18s ease; }
        .btn-back:hover i { transform: translateX(-3px); }

        /* Section title inside card (for assessment) */
        .section-title { font-weight: 700; color: #25304a; font-size: 1rem; margin: 14px 0 6px; }
    </style>
</head>
<body style="background:#f6f8fa;min-height:100vh;display:flex;flex-direction:column;">
    <div class="container py-4 flex-grow-1">
        <!-- Back button above the card, leftmost -->
        <a href="javascript:history.back()" class="btn-back mb-2"><i class="fas fa-arrow-left"></i> Back</a>

        <div class="details-card">
            <?php if ($notif): ?>
                <?php $isAccepted = ($type === 'accepted'); ?>
                <!-- New styled header -->
                <div class="details-header">
                    <div class="icon-circle <?php echo $isAccepted ? 'success' : 'danger'; ?>">
                        <i class="fas <?php echo $isAccepted ? 'fa-check' : 'fa-times'; ?>"></i>
                    </div>
                    <div>
                        <div class="details-title-txt">Request <?php echo $isAccepted ? 'Accepted' : 'Denied'; ?></div>
                        <span class="status-pill <?php echo $isAccepted ? 'success' : 'danger'; ?>"><?php echo $isAccepted ? 'ACCEPTED' : 'DENIED'; ?></span>
                    </div>
                </div>
                <div class="divider"></div>

                <!-- Key/Value rows -->
                <div class="kv-grid">
                    <div class="kv-row">
                        <span class="kv-label">Type</span>
                        <span class="kv-value"><?php echo htmlspecialchars($notif['type']); ?></span>
                    </div>
                    <div class="kv-row">
                        <span class="kv-label">Name</span>
                        <span class="kv-value"><?php echo htmlspecialchars($notif['first_name'].' '.($notif['middle_name']??'').' '.$notif['last_name']); ?></span>
                    </div>
                    <div class="kv-row">
                        <span class="kv-label">Date</span>
                        <span class="kv-value"><?php echo date('M d, Y h:i A', strtotime($notif['created_at'])); ?></span>
                    </div>
                </div>

            <?php elseif ($assessment && !empty($details)): ?>
                <!-- Assessment header -->
                <div class="details-header">
                    <div class="icon-circle warn">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <div>
                        <div class="details-title-txt">Assessment of Fees</div>
                        <span class="status-pill warn">FEES</span>
                    </div>
                </div>
                <div class="divider"></div>

                <!-- Keep existing rows but benefit from upgraded styles -->
                <div class="kv-grid">
                    <div class="detail-row"><span class="detail-label">Informant Name:</span><span class="detail-value"><?php echo htmlspecialchars($details['informant_name'] ?? ''); ?></span></div>
                    <div class="detail-row"><span class="detail-label">Email:</span><span class="detail-value"><?php echo htmlspecialchars($details['email'] ?? ''); ?></span></div>
                    <div class="detail-row"><span class="detail-label">Type:</span><span class="detail-value"><?php echo htmlspecialchars($details['type'] ?? ''); ?></span></div>
                    <div class="detail-row" style="display:<?php echo !empty($details['first_name']) ? '' : 'none'; ?>;"><span class="detail-label">Name of Deceased:</span><span class="detail-value"><?php echo htmlspecialchars($details['first_name'].' '.($details['middle_name']??'').' '.$details['last_name']); ?></span></div>
                    <div class="detail-row" style="display:<?php echo !empty($details['residency']) ? '' : 'none'; ?>;"><span class="detail-label">Residency:</span><span class="detail-value"><?php echo htmlspecialchars($details['residency'] ?? ''); ?></span></div>
                    <div class="detail-row" style="display:<?php echo !empty($details['dob']) ? '' : 'none'; ?>;"><span class="detail-label">Date of Birth:</span><span class="detail-value"><?php echo htmlspecialchars($details['dob'] ?? ''); ?></span></div>
                    <div class="detail-row" style="display:<?php echo !empty($details['dod']) ? '' : 'none'; ?>;"><span class="detail-label">Date of Death:</span><span class="detail-value"><?php echo htmlspecialchars($details['dod'] ?? ''); ?></span></div>
                    <div class="detail-row"><span class="detail-label">Age:</span><span class="detail-value"><?php echo htmlspecialchars($details['age'] ?? ''); ?></span></div>
                    <div class="detail-row" style="display:<?php echo ($details['type'] === 'Transfer' || $details['type'] === 'Exhumation') ? '' : 'none'; ?>;"><span class="detail-label">Niche ID:</span><span class="detail-value"><?php echo htmlspecialchars($details['niche_id'] ?? ''); ?></span></div>
                </div>

                <div class="section-title">Computation</div>
                <div class="divider" style="margin-top:4px;"></div>

                <?php
                // Calculate fees
                $totalFee = 0;
                $renewalFee = 5000;
                if ($details['type'] === 'Relocate') {
                    $openingFee = 1000;
                    $remainsCount = intval($details['remains_count'] ?? 1);
                    $relocationFee = 500 * $remainsCount;
                    $totalFee = $openingFee + $relocationFee;
                    echo '<div class="detail-row"><span class="detail-label">Opening Fee:</span><span class="detail-value">₱ 1,000.00</span></div>';
                    echo '<div class="detail-row"><span class="detail-label">Relocation Fee:</span><span class="detail-value">₱ 500.00 x '.$remainsCount.' = ₱ '.number_format($relocationFee,2).'</span></div>';
                    echo '<div class="detail-row"><span class="detail-label">Total Fee:</span><span class="detail-value">₱ '.number_format($totalFee,2).'</span></div>';
                } else {
                    $age = intval($details['age'] ?? 0);
                    $discountNote = '';
                    $babyNote = '';
                    $isBaby = false;
                    if ($details['type'] === 'New') {
                        if ($age <= 2) {
                            $totalFee = 5000;
                            $babyNote = ' (Newborn/Baby Rate)';
                        } else {
                            $residency = trim($details['residency'] ?? '');
                            $padreGarciaBarangays = [
                                'Banaba, Padre Garcia, Batangas',
                                'Banaybanay, Padre Garcia, Batangas',
                                'Bawi, Padre Garcia, Batangas',
                                'Bukal, Padre Garcia, Batangas',
                                'Castillo, Padre Garcia, Batangas',
                                'Cawongan, Padre Garcia, Batangas',
                                'Manggas, Padre Garcia, Batangas',
                                'Maugat East, Padre Garcia, Batangas',
                                'Maugat West, Padre Garcia, Batangas',
                                'Pansol, Padre Garcia, Batangas',
                                'Payapa, Padre Garcia, Batangas',
                                'Poblacion, Padre Garcia, Batangas',
                                'Quilo-quilo North, Padre Garcia, Batangas',
                                'Quilo-quilo South, Padre Garcia, Batangas',
                                'San Felipe, Padre Garcia, Batangas',
                                'San Miguel, Padre Garcia, Batangas',
                                'Tamak, Padre Garcia, Batangas',
                                'Tangob, Padre Garcia, Batangas'
                            ];
                            $isPadreGarcia = in_array($residency, $padreGarciaBarangays);
                            if ($isPadreGarcia) {
                                $totalFee = 10000;
                                $discountNote = ' (Graciano discount applied)';
                            } else {
                                $totalFee = 15000;
                            }
                        }
                    }
                    // Expiration date (5 years from date of death)
                    $expirationDate = '';
                    if (!empty($details['dod'])) {
                        $dod = strtotime($details['dod']);
                        $exp = strtotime('+5 years', $dod);
                        $expirationDate = date('d-M-Y', $exp);
                    }
                    echo $totalFee ? '<div class="detail-row"><span class="detail-label">Total Fee:</span><span class="detail-value">₱ '.number_format($totalFee,2).$babyNote.$discountNote.'</span></div>' : '';
                    echo '<div class="detail-row"><span class="detail-label">Renewal Fee:</span><span class="detail-value">₱ 5,000.00</span></div>';
                    echo $expirationDate ? '<div class="detail-row"><span class="detail-label">Certificate Expiration:</span><span class="detail-value">'.$expirationDate.'</span></div>' : '';
                }
                ?>
            <?php else: ?>
                <div class="text-center text-danger">Notification not found or you do not have access.</div>
            <?php endif; ?>
        </div>
    </div>
    <footer style="margin-top:auto;">
        <?php include '../Includes/footer-client.php'; ?>
    </footer>
</body>
</html>
