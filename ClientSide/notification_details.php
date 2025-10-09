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
        body, .details-card, .details-title, .details-label, .detail-label, .detail-value {
            font-family: 'Poppins', Arial, sans-serif !important;
        }
        .details-card { max-width: 600px; margin: 2rem auto; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.10); }
        .details-title { font-size: 1.3rem; font-weight: 600; margin-bottom: 1rem; }
        .details-label { font-weight: 500; color: #4B7BEC; }
        .detail-row { display: flex; justify-content: space-between; align-items: flex-start; padding: 8px 0; }
        .detail-label { font-weight: 600; color: #374151; min-width: 120px; font-size: 0.95rem; }
        .detail-value { color: #6b7280; font-size: 0.95rem; text-align: right; flex: 1; margin-left: 16px; }
    </style>
</head>
<body style="background:#f6f8fa;min-height:100vh;display:flex;flex-direction:column;">
    <div class="container py-4 flex-grow-1">
        <!-- Back button above the card, leftmost -->
        <button type="button" onclick="window.history.back();" class="btn btn-link" style="font-size:1.1rem;padding:0;margin-bottom:10px;">
            <i class="fas fa-arrow-left"></i> Back
        </button>
        <div class="details-card bg-white p-4" style="margin-left:auto;margin-right:auto;">
            <?php if ($notif): ?>
                <div class="details-title mb-3 text-center">
                    <?php if ($type === 'accepted'): ?>
                        <i class="fas fa-check-circle" style="color:#2ecc71;"></i> Request Accepted
                    <?php else: ?>
                        <i class="fas fa-times-circle" style="color:#e74c3c;"></i> Request Denied
                    <?php endif; ?>
                </div>
                <div class="mb-2"><span class="details-label">Type:</span> <?php echo htmlspecialchars($notif['type']); ?></div>
                <div class="mb-2"><span class="details-label">Name:</span> <?php echo htmlspecialchars($notif['first_name'].' '.($notif['middle_name']??'').' '.$notif['last_name']); ?></div>
                <div class="mb-2"><span class="details-label">Date:</span> <?php echo date('M d, Y h:i A', strtotime($notif['created_at'])); ?></div>
            <?php elseif ($assessment && !empty($details)): ?>
                <div class="details-title mb-3 text-center">
                    <i class="fas fa-file-invoice-dollar" style="color:#f39c12;"></i> Assessment of Fees
                </div>
                <div class="detail-row"><span class="detail-label">Informant Name:</span><span class="detail-value"><?php echo htmlspecialchars($details['informant_name'] ?? ''); ?></span></div>
                <div class="detail-row"><span class="detail-label">Email:</span><span class="detail-value"><?php echo htmlspecialchars($details['email'] ?? ''); ?></span></div>
                <div class="detail-row"><span class="detail-label">Type:</span><span class="detail-value"><?php echo htmlspecialchars($details['type'] ?? ''); ?></span></div>
                <div class="detail-row" style="display:<?php echo !empty($details['first_name']) ? '' : 'none'; ?>;"><span class="detail-label">Name of Deceased:</span><span class="detail-value"><?php echo htmlspecialchars($details['first_name'].' '.($details['middle_name']??'').' '.$details['last_name']); ?></span></div>
                <div class="detail-row" style="display:<?php echo !empty($details['residency']) ? '' : 'none'; ?>;"><span class="detail-label">Residency:</span><span class="detail-value"><?php echo htmlspecialchars($details['residency'] ?? ''); ?></span></div>
                <div class="detail-row" style="display:<?php echo !empty($details['dob']) ? '' : 'none'; ?>;"><span class="detail-label">Date of Birth:</span><span class="detail-value"><?php echo htmlspecialchars($details['dob'] ?? ''); ?></span></div>
                <div class="detail-row" style="display:<?php echo !empty($details['dod']) ? '' : 'none'; ?>;"><span class="detail-label">Date of Death:</span><span class="detail-value"><?php echo htmlspecialchars($details['dod'] ?? ''); ?></span></div>
                <div class="detail-row"><span class="detail-label">Age:</span><span class="detail-value"><?php echo htmlspecialchars($details['age'] ?? ''); ?></span></div>
                <div class="detail-row" style="display:<?php echo ($details['type'] === 'Transfer' || $details['type'] === 'Exhumation') ? '' : 'none'; ?>;"><span class="detail-label">Niche ID:</span><span class="detail-value"><?php echo htmlspecialchars($details['niche_id'] ?? ''); ?></span></div>
                <hr style="margin:24px 0;">
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
