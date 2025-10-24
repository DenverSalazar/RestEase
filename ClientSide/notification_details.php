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

// Small helpers: current user name and account label for header
$appName = 'RestEase';
$currentUserName = 'there';
if ($user_id) {
    if ($stmt = $conn->prepare("SELECT first_name FROM users WHERE id = ? LIMIT 1")) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($u = $res->fetch_assoc()) {
            $currentUserName = trim($u['first_name'] ?: 'there');
        }
        $stmt->close();
    }
}

/* ===== New helpers for assessment view ===== */
function peso($n) {
    if ($n === null || $n === '') return '-';
    return '₱ ' . number_format((float)$n, 2);
}
function table_exists(mysqli $conn, $name) {
    $name = $conn->real_escape_string($name);
    $res = $conn->query("SHOW TABLES LIKE '{$name}'");
    return $res && $res->num_rows > 0;
}
function full_name($first = '', $middle = '', $last = '') {
    return trim(preg_replace('/\s+/', ' ', trim($first . ' ' . $middle . ' ' . $last)));
}
function pick($arr, $keys) {
    foreach ($keys as $k) if (!empty($arr[$k])) return $arr[$k];
    return null;
}
function calc_age($dob, $dod = null) {
    if (empty($dob)) return null;
    try {
        $start = new DateTime($dob);
        $end = $dod ? new DateTime($dod) : new DateTime();
        return $start->diff($end)->y;
    } catch (Exception $e) { return null; }
}

/* ===== Build assessment data if available ===== */
$assessView = [
    'informant' => null, 'email' => null, 'type' => null,
    'deceased' => null, 'residency' => null, 'dob' => null, 'dod' => null, 'age' => null,
    'fees' => ['opening' => null, 'reloc_rate' => null, 'reloc_count' => 0, 'total' => null]
];

if (!empty($assessment) && !empty($details)) {
    $assessView['informant'] = full_name($details['user_first'] ?? '', '', $details['user_last'] ?? '');
    $assessView['email'] = $details['email'] ?? '';
    $assessView['type'] = ucfirst($details['type'] ?? '');
    $assessView['deceased'] = full_name($details['first_name'] ?? '', $details['middle_name'] ?? '', $details['last_name'] ?? '');
    $assessView['residency'] = pick($details, ['residency', 'address', 'residence']) ?? '-';
    $assessView['dob'] = pick($details, ['date_of_birth', 'dob', 'birth_date']);
    $assessView['dod'] = pick($details, ['date_of_death', 'dod', 'death_date']);
    $assessView['age'] = calc_age($assessView['dob'], $assessView['dod']);

    // Fees: prefer assessment_fees table; else sensible defaults
    $opening = 1000;
    $relocRate = 500;
    $relocCount = (stripos($assessView['type'] ?? '', 'relocate') !== false) ? 1 : 0;
    $total = $opening + ($relocRate * $relocCount);

    if (table_exists($conn, 'assessment_fees')) {
        if ($stmt = $conn->prepare("SELECT opening_fee, relocation_fee_rate, relocation_count, total_fee FROM assessment_fees WHERE request_id = ? AND user_id = ? ORDER BY id DESC LIMIT 1")) {
            $request_id = $details['id'] ?? null;
            $stmt->bind_param("ii", $request_id, $user_id);
            if ($stmt->execute()) {
                $res = $stmt->get_result();
                if ($row = $res->fetch_assoc()) {
                    $opening = $row['opening_fee'] ?? $opening;
                    $relocRate = $row['relocation_fee_rate'] ?? $relocRate;
                    $relocCount = (int)($row['relocation_count'] ?? $relocCount);
                    $total = $row['total_fee'] ?? ($opening + ($relocRate * $relocCount));
                }
            }
            $stmt->close();
        }
    }
    $assessView['fees'] = [
        'opening' => (float)$opening,
        'reloc_rate' => (float)$relocRate,
        'reloc_count' => (int)$relocCount,
        'total' => (float)$total
    ];
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

        /* Email-like header (kept for accepted/denied) */
        .email-head { text-align:center; margin-bottom:18px; }
        .bubble-icon {
            width: 54px; height: 54px; border-radius: 14px;
            display:grid; place-items:center; margin: 0 auto 10px;
            background:#eef2ff; color:#4f46e5; font-size:22px;
        }
        .eyebrow { color:#ef6c00; font-weight:600; font-size:.95rem; }
        .email-title { font-weight:800; color:#111827; font-size:1.6rem; margin:6px 0 6px; }
        .email-sub { color:#6b7280; margin:0; }

        .email-body-box {
            background:#f8fafc; border:1px solid #e5e7eb; border-radius:12px;
            padding:16px; color:#374151; line-height:1.55; margin:16px 0 12px;
        }
        .email-meta { color:#6b7280; font-size:.95rem; margin:6px 0 16px; }

        /* Assessment header + grid */
        .assess-title { font-weight: 800; color: #111827; font-size: 1.5rem; margin: 4px 0 18px; }
        .kv-grid { display: grid; gap: 6px; }
        .kv-row { display:flex; align-items:baseline; justify-content:space-between; gap:12px; padding:6px 0; }
        .kv-label { color:#374151; min-width:200px; font-weight:600; }
        .kv-value { color:#111827; text-align:right; flex:1; }
        .divider { border-top:1px solid #e5e7eb; margin:14px 0; }
        .total-row .kv-label, .total-row .kv-value { font-weight:800; }
        .muted { color:#6b7280; font-size:.9rem; }
    </style>
</head>
<body style="background:#f6f8fa;min-height:100vh;display:flex;flex-direction:column;">
    <div class="container py-4 flex-grow-1">
        <!-- Back button above the card, leftmost -->
        <a href="javascript:history.back()" class="btn-back mb-2"><i class="fas fa-arrow-left"></i> Back</a>

        <div class="details-card">
            <?php if ($notif): ?>
                <?php
                    $isAccepted = ($type === 'accepted');
                    $fullName = trim(($notif['first_name'] ?? '').' '.($notif['middle_name'] ?? '').' '.($notif['last_name'] ?? ''));
                    $msgTxt = $isAccepted
                        ? "Good news! Your request for {$notif['type']} regarding {$fullName} has been accepted."
                        : "We’re sorry. Your request for {$notif['type']} regarding {$fullName} has been denied.";
                    $sentOn = date('M d, Y h:i A', strtotime($notif['created_at']));
                ?>
                <div class="email-head">
                    <div class="bubble-icon"><i class="far fa-comment-dots"></i></div>
                    <div class="eyebrow">Hi there, <?php echo htmlspecialchars($currentUserName); ?>.</div>
                    <h1 class="email-title">You have a new message.</h1>
                    <p class="email-sub">New message at <?php echo htmlspecialchars($appName); ?></p>
                </div>

                <div class="email-body-box">
                    <?php echo htmlspecialchars($msgTxt); ?>
                </div>

                <div class="email-meta">
                    Sent by Admin on <?php echo htmlspecialchars($sentOn); ?>.
                </div>

            <?php elseif ($assessment && !empty($details)): ?>
                <!-- ===== Redesigned Assessment of Fees ===== -->
                <h2 class="assess-title">Assessment of Fees</h2>

                <div class="kv-grid">
                    <div class="kv-row"><div class="kv-label">Informant Name:</div><div class="kv-value"><?php echo htmlspecialchars($assessView['informant'] ?: '-'); ?></div></div>
                    <div class="kv-row"><div class="kv-label">Email:</div><div class="kv-value"><?php echo htmlspecialchars($assessView['email'] ?: '-'); ?></div></div>
                    <div class="kv-row"><div class="kv-label">Type:</div><div class="kv-value"><?php echo htmlspecialchars($assessView['type'] ?: '-'); ?></div></div>
                    <div class="kv-row"><div class="kv-label">Name of Deceased:</div><div class="kv-value"><?php echo htmlspecialchars($assessView['deceased'] ?: '-'); ?></div></div>
                    <div class="kv-row"><div class="kv-label">Residency:</div><div class="kv-value"><?php echo htmlspecialchars($assessView['residency'] ?: '-'); ?></div></div>
                    <div class="kv-row"><div class="kv-label">Date of Birth:</div><div class="kv-value"><?php echo htmlspecialchars($assessView['dob'] ?: '-'); ?></div></div>
                    <div class="kv-row"><div class="kv-label">Date of Death:</div><div class="kv-value"><?php echo htmlspecialchars($assessView['dod'] ?: '-'); ?></div></div>
                    <div class="kv-row"><div class="kv-label">Age:</div><div class="kv-value"><?php echo htmlspecialchars(($assessView['age'] !== null ? $assessView['age'] : '-')); ?></div></div>
                </div>

                <div class="divider"></div>

                <div class="kv-grid">
                    <div class="kv-row"><div class="kv-label">Opening Fee:</div><div class="kv-value"><?php echo peso($assessView['fees']['opening']); ?></div></div>
                    <?php if (!empty($assessView['fees']['reloc_count'])): ?>
                        <?php
                            $rate = $assessView['fees']['reloc_rate'];
                            $cnt = $assessView['fees']['reloc_count'];
                            $line = peso($rate) . " x {$cnt} = " . peso($rate * $cnt);
                        ?>
                        <div class="kv-row"><div class="kv-label">Relocation Fee:</div><div class="kv-value"><?php echo $line; ?></div></div>
                    <?php endif; ?>
                    <div class="kv-row total-row"><div class="kv-label">Total Fee:</div><div class="kv-value"><?php echo peso($assessView['fees']['total']); ?></div></div>
                </div>

                <div class="divider"></div>
                <div class="muted">Generated on <?php echo htmlspecialchars(date('M d, Y h:i A', strtotime($assessment['created_at']))); ?></div>

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
