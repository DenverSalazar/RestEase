<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    // Redirect to login page if not logged in
    header("Location: ../login.php"); // Adjust the path if needed
    exit;
}

include_once '../Includes/db.php';

// --- Add PHPMailer ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once __DIR__ . '/../vendor/autoload.php';

// --- Add: compute project web base and settings URL (same logic as Includes/header.php) ---
$projectRootFs = realpath(__DIR__ . '/..') ?: '';
$docRootFs = realpath($_SERVER['DOCUMENT_ROOT']) ?: '';
$appBase = '';
if ($projectRootFs && $docRootFs && strpos($projectRootFs, $docRootFs) === 0) {
    $appBase = str_replace('\\', '/', substr($projectRootFs, strlen($docRootFs)));
    $appBase = $appBase === '' ? '' : ('/' . ltrim($appBase, '/'));
} else {
    $parts = explode('/', trim($_SERVER['SCRIPT_NAME'] ?? '', '/'));
    $appBase = isset($parts[0]) && $parts[0] !== '' ? '/' . $parts[0] : '';
}
$settingsUrl = $appBase . '/AdminSide/Settings.php?tab=notification';
// --- end added block ---

// --- REPLACED: handle AJAX notify requests (now requires contact_value) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['notify_niche'])) {
    $niche = trim($_POST['notify_niche']);
    $name = isset($_POST['notify_name']) ? trim($_POST['notify_name']) : '';
    $validity = isset($_POST['notify_validity']) ? trim($_POST['notify_validity']) : '';
    $contact_type = isset($_POST['contact_type']) ? trim($_POST['contact_type']) : '';
    $contact_value = isset($_POST['contact_value']) ? trim($_POST['contact_value']) : '';

    // server-side validation: contact_value is required
    if ($contact_value === '') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'missing_contact', 'message' => 'Contact (email) is required.']);
        exit;
    }

    // Ensure expiry_notifications table exists (record audit & queued notifications)
    $conn->query("CREATE TABLE IF NOT EXISTS expiry_notifications (
      id INT AUTO_INCREMENT PRIMARY KEY,
      nicheID VARCHAR(255),
      name VARCHAR(255),
      validity DATE,
      contact_type VARCHAR(50),
      contact_value VARCHAR(255),
      admin_id INT,
      message TEXT,
      status VARCHAR(50),
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Determine if expired or upcoming
    $today = date('Y-m-d');
    $isExpired = ($validity < $today);

    // Prepare subject and body based on expiry status
    if ($isExpired) {
        $subject = "Notice of Expired Lease";
        $bodyHtml = "Dear " . htmlspecialchars($name) . ",<br><br>"
            . "This is to inform you that your lease for <b>Apt. No/ Niche: " . htmlspecialchars($niche) . "</b> has expired as of <b>" . htmlspecialchars($validity) . "</b>.<br><br>"
            . "Please take the necessary action to renew or settle your account to avoid any inconvenience. If payment or renewal has already been made, kindly disregard this message.<br><br>"
            . "For assistance or inquiries, you may contact us at <a href='mailto:resteasempdo@gmail.com'>resteasempdo@gmail.com</a>.<br><br>"
            . "Thank you for your prompt attention.<br><br>"
            . "Sincerely,<br>RestEase MPDO";
        $bodyText = "Dear {$name},\n\n"
            . "This is to inform you that your lease for Apt. No/ Niche: {$niche} has expired as of {$validity}.\n\n"
            . "Please take the necessary action to renew or settle your account to avoid any inconvenience. If payment or renewal has already been made, kindly disregard this message.\n\n"
            . "For assistance or inquiries, you may contact us at resteasempdo@gmail.com.\n\n"
            . "Thank you for your prompt attention.\n\n"
            . "Sincerely,\nRestEase MPDO";
    } else {
        $subject = "Reminder: Lease Expiring Soon";
        $bodyHtml = "Dear " . htmlspecialchars($name) . ",<br><br>"
            . "We would like to remind you that your lease for <b>Apt. No/ Niche: " . htmlspecialchars($niche) . "</b> is set to expire on <b>" . htmlspecialchars($validity) . "</b>.<br><br>"
            . "To ensure continuous service and avoid interruption, please process your renewal or payment before the expiration date.<br><br>"
            . "If you have already completed the renewal, kindly ignore this reminder.<br><br>"
            . "Thank you for your continued support.<br><br>"
            . "Best regards,<br>resteasempdo@gmail.com";
        $bodyText = "Dear {$name},\n\n"
            . "We would like to remind you that your lease for Apt. No/ Niche: {$niche} is set to expire on {$validity}.\n\n"
            . "To ensure continuous service and avoid interruption, please process your renewal or payment before the expiration date.\n\n"
            . "If you have already completed the renewal, kindly ignore this reminder.\n\n"
            . "Thank you for your continued support.\n\n"
            . "Best regards,\nresteasempdo@gmail.com";
    }

    $message = $isExpired
        ? "Expired lease notice for {$name} (Apt: {$niche}) on {$validity}"
        : "Upcoming expiry notice for {$name} (Apt: {$niche}) on {$validity}";

    $status = 'queued';
    $sent = false;

    // If contact_value corresponds to a registered user email or contact_type === 'internal'
    $userTargetId = null;
    if (!empty($contact_value)) {
        $stmtU = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        if ($stmtU) {
            $stmtU->bind_param('s', $contact_value);
            $stmtU->execute();
            $resU = $stmtU->get_result();
            if ($rowU = $resU->fetch_assoc()) {
                $userTargetId = intval($rowU['id']);
            }
            $stmtU->close();
        }
    }

    if ($contact_type === 'internal' || $userTargetId) {
        // Create a notifications entry for the user so it appears in their client-side notifications
        if ($userTargetId) {
            $notifLink = ''; // optional link for client (leave empty or point to a page)
            $notifMsg = $message;
            $stmtNotif = $conn->prepare("INSERT INTO notifications (user_id, message, link, is_read, created_at) VALUES (?, ?, ?, 0, NOW())");
            if ($stmtNotif) {
                $stmtNotif->bind_param('iss', $userTargetId, $notifMsg, $notifLink);
                $notifResult = $stmtNotif->execute();
                $stmtNotif->close();
                if ($notifResult) {
                    $status = 'sent';
                    $sent = true;
                } else {
                    $status = 'failed';
                }
            } else {
                $status = 'failed';
            }

            // --- NEW: Also send expiry notice to their email account using PHPMailer ---
            // Only send if email is valid
            if (filter_var($contact_value, FILTER_VALIDATE_EMAIL)) {
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'resteasempdo@gmail.com';
                    $mail->Password   = 'vvkblrlppiflbksu';
                    $mail->SMTPSecure = 'tls';
                    $mail->Port       = 587;
                    $mail->setFrom('resteasempdo@gmail.com', 'RestEase');
                    $mail->addAddress($contact_value);
                    $mail->isHTML(true);
                    $mail->Subject = $subject;
                    $mail->Body    = $bodyHtml;
                    $mail->AltBody = $bodyText;
                    $mail->send();
                } catch (Exception $e) {
                    // ignore email errors for now, main status is for in-app
                }
            }
            // --- END NEW ---
        } else {
            // If internal requested but user not found, keep queued
            $status = 'queued';
        }
    } elseif ($contact_type === 'email' && filter_var($contact_value, FILTER_VALIDATE_EMAIL)) {
        // --- Use PHPMailer for email notifications ---
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'resteasempdo@gmail.com';
            $mail->Password   = 'vvkblrlppiflbksu';
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;
            $mail->setFrom('resteasempdo@gmail.com', 'RestEase');
            $mail->addAddress($contact_value);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $bodyHtml;
            $mail->AltBody = $bodyText;
            $mailResult = $mail->send();
            if ($mailResult) {
                $status = 'sent';
                $sent = true;
            } else {
                $status = 'failed';
            }
        } catch (Exception $e) {
            $status = 'failed';
        }
    } else {
        // For phone numbers or non-validated emails, keep queued status (admin can use other channels)
        $status = 'queued';
    }

    // Record notification into expiry_notifications (contact_type and contact_value are saved)
    $stmtN = $conn->prepare("INSERT INTO expiry_notifications (nicheID, name, validity, contact_type, contact_value, admin_id, message, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $adminIdParam = isset($_SESSION['admin_id']) ? intval($_SESSION['admin_id']) : 0;
    $stmtN->bind_param('sssssiss', $niche, $name, $validity, $contact_type, $contact_value, $adminIdParam, $message, $status);
    $result = $stmtN->execute();
    $stmtN->close();

    header('Content-Type: application/json');
    echo json_encode(['success' => $result && ($sent || $status === 'queued'), 'status' => $status]);
    $conn->close();
    exit;
}
// --- end replaced handler ---

// Total physical niches calculation
// Based on your layout: 72x72 niches per section, with 22 sections total
// Plus baby niches: Section 1 and 4 each have additional 4x29 upper and lower = 232 each
// Baby niches total: 232 + 232 = 464 additional niches
$totalPhysicalNiches = (144 * 22) + 464; // 3,168 + 464 = 3,632 total niches

// Occupied niches for new map (exclude IDs starting with 'OM')
$occupiedNichesArr = [];
$resOcc = $conn->query("SELECT DISTINCT nicheID FROM deceased WHERE nicheID IS NOT NULL AND nicheID != '' AND nicheID != 'null'");
if ($resOcc) {
    while ($row = $resOcc->fetch_assoc()) {
        if (strpos($row['nicheID'], 'OM') === 0) continue;
        $occupiedNichesArr[$row['nicheID']] = true;
    }
}
$occupiedNiches = count($occupiedNichesArr);

// Occupied niches for old map (IDs starting with 'OM')
$occupiedNichesOldMapArr = [];
$resOccOld = $conn->query("SELECT DISTINCT nicheID FROM deceased WHERE nicheID IS NOT NULL AND nicheID != '' AND nicheID != 'null' AND nicheID LIKE 'OM%'");
if ($resOccOld) {
    while ($row = $resOccOld->fetch_assoc()) {
        $occupiedNichesOldMapArr[$row['nicheID']] = true;
    }
}
$occupiedNichesOldMap = count($occupiedNichesOldMapArr);

// Available niches = Total physical niches - Currently occupied niches
$availableNiches = $totalPhysicalNiches - $occupiedNiches;
if ($availableNiches < 0) $availableNiches = 0;

// Pending requests
$result = $conn->query("SELECT COUNT(*) AS cnt FROM client_requests");
$pendingRequest = ($result && $row = $result->fetch_assoc()) ? intval($row['cnt']) : 0;

// REPLACED: compute active clients as users + walk-in_clients
$usersRes = $conn->query("SELECT COUNT(*) AS cnt FROM users");
$usersCnt = ($usersRes && $rowU = $usersRes->fetch_assoc()) ? intval($rowU['cnt']) : 0;
$walkinRes = $conn->query("SELECT COUNT(*) AS cnt FROM walkin_clients");
$walkinCnt = ($walkinRes && $rowW = $walkinRes->fetch_assoc()) ? intval($rowW['cnt']) : 0;
$activeClients = $usersCnt + $walkinCnt;

// --- MOVED: Prepare data for both new map and old map (compute before baselines) ---
$newMapOccupiedArr = [];
$resNewEarly = $conn->query("SELECT DISTINCT nicheID FROM deceased WHERE nicheID IS NOT NULL AND nicheID != '' AND nicheID != 'null' AND nicheID NOT LIKE 'OM%'");
if ($resNewEarly) {
    while ($row = $resNewEarly->fetch_assoc()) {
        $newMapOccupiedArr[$row['nicheID']] = true;
    }
}
$newMapOccupied = count($newMapOccupiedArr);
$newMapAvailable = $totalPhysicalNiches - $newMapOccupied;
if ($newMapAvailable < 0) $newMapAvailable = 0;

// Old map: nicheID starting with 'OM'
$oldMapOccupiedArr = [];
$resOldEarly = $conn->query("SELECT DISTINCT nicheID FROM deceased WHERE nicheID IS NOT NULL AND nicheID != '' AND nicheID != 'null' AND nicheID LIKE 'OM%'");
if ($resOldEarly) {
    while ($row = $resOldEarly->fetch_assoc()) {
        $oldMapOccupiedArr[$row['nicheID']] = true;
    }
}
$oldMapOccupied = count($oldMapOccupiedArr);

// Old map available: 2307 minus occupied
$totalOldMapNiches = 2307;
$oldMapAvailable = $totalOldMapNiches - $oldMapOccupied;
if ($oldMapAvailable < 0) $oldMapAvailable = 0;
// --- end moved block ---

// --- NEW: compute simple baselines and changes for stat indicators ---
// New map occupied 30 days ago (to derive previous available)
$occupied30Arr = [];
$resOcc30 = $conn->query("SELECT DISTINCT nicheID FROM deceased WHERE nicheID IS NOT NULL AND nicheID != '' AND nicheID != 'null' AND nicheID NOT LIKE 'OM%' AND dateInternment <= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
if ($resOcc30) {
    while ($r = $resOcc30->fetch_assoc()) {
        $occupied30Arr[$r['nicheID']] = true;
    }
}
$occupied30 = count($occupied30Arr);
$prevNewMapAvailable = $totalPhysicalNiches - $occupied30;
if ($prevNewMapAvailable < 0) $prevNewMapAvailable = 0;
$newMapAvailableChange = $newMapAvailable - $prevNewMapAvailable;

// Pending requests: compare last 7 days vs previous 7 days
$resLast7 = $conn->query("SELECT COUNT(*) AS cnt FROM client_requests WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
$last7 = ($resLast7 && $r = $resLast7->fetch_assoc()) ? intval($r['cnt']) : 0;
$resPrev7 = $conn->query("SELECT COUNT(*) AS cnt FROM client_requests WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY) AND created_at < DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
$prev7 = ($resPrev7 && $r = $resPrev7->fetch_assoc()) ? intval($r['cnt']) : 0;
$pendingRequestChange = $last7 - $prev7;

// Active clients change: new users in last 30 days vs previous 30-day window
$resClientsLast30 = $conn->query("SELECT COUNT(*) AS cnt FROM users WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
$clientsLast30 = ($resClientsLast30 && $r = $resClientsLast30->fetch_assoc()) ? intval($r['cnt']) : 0;
$resClientsPrev30 = $conn->query("SELECT COUNT(*) AS cnt FROM users WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 60 DAY) AND created_at < DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
$clientsPrev30 = ($resClientsPrev30 && $r = $resClientsPrev30->fetch_assoc()) ? intval($r['cnt']) : 0;
$activeClientsChange = $clientsLast30 - $clientsPrev30;

// Occupied niches change (new map): compare current occupied vs occupied 30 days ago
$newMapOccupiedChange = $newMapOccupied - $occupied30;
// --- end new section ---

// Get admin name
$adminId = $_SESSION['admin_id'];
$adminName = 'Admin';
$adminProfilePic = '../assets/Default Image.jpg';
// Fetch display_name and profile_pic from admin_profiles
$stmt = $conn->prepare('SELECT display_name, profile_pic FROM admin_profiles WHERE admin_id = ? LIMIT 1');
$stmt->bind_param('i', $adminId);
$stmt->execute();
$stmt->bind_result($displayName, $profilePic);
if ($stmt->fetch()) {
    $adminName = $displayName ? $displayName : $adminName;
    $adminProfilePic = $profilePic ? $profilePic : $adminProfilePic;
}
$stmt->close();

// Get records whose validity is expired or expiring within 1 year (from today)
$expiringRecords = [];
$today = date('Y-m-d');
$oneYearFromNow = date('Y-m-d', strtotime('+1 year'));
$sql = "SELECT id, nicheID, lastName, firstName, middleName, suffix, dateInternment, informantName FROM deceased WHERE dateInternment IS NOT NULL AND dateInternment != '' AND dateInternment != '0000-00-00'";
$res = $conn->query($sql);
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $internmentDate = $row['dateInternment'];
        try {
            $validityDate = (new DateTime($internmentDate))->modify('+5 years')->format('Y-m-d');
            // Only include if validity is expired or will expire within 1 year from today
            if ($validityDate <= $oneYearFromNow) {
                $name = $row['lastName'] . ', ' . $row['firstName'];
                if (!empty($row['middleName'])) $name .= ' ' . strtoupper(substr(trim($row['middleName']), 0, 1)) . '.';
                if (!empty($row['suffix'])) $name .= ' ' . $row['suffix'];

                // Try to find a registered user who matches the informant name (best-effort)
                $clientEmail = null;
                $clientId = null;
                $informantRaw = trim($row['informantName'] ?? '');

                if (!empty($informantRaw)) {
                    $stmtUser = $conn->prepare("SELECT id, email FROM users WHERE CONCAT(first_name, ' ', last_name) = ? LIMIT 1");
                    if ($stmtUser) {
                        $stmtUser->bind_param('s', $informantRaw);
                        $stmtUser->execute();
                        $resU = $stmtUser->get_result();
                        if ($ru = $resU->fetch_assoc()) {
                            $clientId = intval($ru['id']);
                            $clientEmail = $ru['email'];
                        }
                        $stmtUser->close();
                    }
                    if (!$clientId && strpos($informantRaw, ',') !== false) {
                        $stmtUser2 = $conn->prepare("SELECT id, email FROM users WHERE CONCAT(last_name, ', ', first_name) = ? LIMIT 1");
                        if ($stmtUser2) {
                            $stmtUser2->bind_param('s', $informantRaw);
                            $stmtUser2->execute();
                            $resU2 = $stmtUser2->get_result();
                            if ($ru2 = $resU2->fetch_assoc()) {
                                $clientId = intval($ru2['id']);
                                $clientEmail = $ru2['email'];
                            }
                            $stmtUser2->close();
                        }
                    }
                }

                // Include informant name in the record for accurate display in the notify popup
                $expiringRecords[] = [
                    'nicheID' => $row['nicheID'],
                    'name' => $name,
                    'validity' => $validityDate,
                    'client_id' => $clientId,
                    'client_email' => $clientEmail,
                    'informant' => $informantRaw
                ];
            }
        } catch (Exception $e) {}
    }
    // Sort by closest validity date (expired first, then soonest expiry)
    usort($expiringRecords, function($a, $b) {
        return strcmp($a['validity'], $b['validity']);
    });

    // --- NEW: ensure expiry_notifications table exists and load notified statuses (persist one-time notifications) ---
    $conn->query("CREATE TABLE IF NOT EXISTS expiry_notifications (
      id INT AUTO_INCREMENT PRIMARY KEY,
      nicheID VARCHAR(255),
      name VARCHAR(255),
      validity DATE,
      contact_type VARCHAR(50),
      contact_value VARCHAR(255),
      admin_id INT,
      message TEXT,
      status VARCHAR(50),
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $notifiedMap = []; // key = "niche|validity" => status ('sent','queued','failed',...)
    if (!empty($expiringRecords)) {
        $stmtLookup = $conn->prepare("SELECT status FROM expiry_notifications WHERE nicheID = ? AND validity = ? ORDER BY created_at DESC LIMIT 1");
        foreach ($expiringRecords as $rec) {
            $keyN = $rec['nicheID'];
            $keyV = $rec['validity'];
            if ($stmtLookup) {
                $stmtLookup->bind_param('ss', $keyN, $keyV);
                $stmtLookup->execute();
                $resN = $stmtLookup->get_result();
                if ($rowN = $resN->fetch_assoc()) {
                    $notifiedMap[$keyN . '|' . $keyV] = $rowN['status'];
                }
            }
        }
        if ($stmtLookup) $stmtLookup->close();
    }
    // --- end new code ---
}

// Year filter logic
$currentYear = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
$yearOptions = [];
for ($y = 1900; $y <= intval(date('Y')); $y++) {
    $yearOptions[] = $y;
}

// RESTORE: Prepare chart data for both maps (values used by JS below)
$pieDataNew = [$newMapAvailable, $newMapOccupied];

// Old map metrics depend on $oldMapAvailable/$oldMapOccupied computed above
$pieDataOld = [$oldMapAvailable, $oldMapOccupied];

// Area chart: Active Clients per day (last 7 days)
$activeClientsPerDayNew = [];
$activeClientsPerDayOld = [];
$daysLabels = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $daysLabels[] = date('D', strtotime($date));
    if (date('Y', strtotime($date)) != $currentYear) {
        $activeClientsPerDayNew[] = 0;
        $activeClientsPerDayOld[] = 0;
        continue;
    }
    $resTmp = $conn->query("SELECT COUNT(*) AS cnt FROM users WHERE DATE(created_at) = '$date'");
    $cnt = ($resTmp && $row = $resTmp->fetch_assoc()) ? intval($row['cnt']) : 0;
    $activeClientsPerDayNew[] = $cnt;
    // old map uses same series (accounts are not map-specific)
    $activeClientsPerDayOld[] = $cnt;
}

// Column chart: Requests per month (last 5 months)
$requestsPerMonthNew = [];
$requestsPerMonthOld = [];
$monthsLabels = [];
for ($i = 4; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $monthsLabels[] = date('M', strtotime($month));
    $yearM = date('Y', strtotime($month));
    if ($yearM != $currentYear) {
        $requestsPerMonthNew[] = 0;
        $requestsPerMonthOld[] = 0;
        continue;
    }
    // New map requests (exclude OM or null)
    $resR1 = $conn->query("SELECT COUNT(*) AS cnt FROM client_requests WHERE DATE_FORMAT(created_at, '%Y-%m') = '$month' AND (niche_id NOT LIKE 'OM%' OR niche_id IS NULL)");
    $requestsPerMonthNew[] = ($resR1 && $row = $resR1->fetch_assoc()) ? intval($row['cnt']) : 0;
    // Old map requests (OM%)
    $resR2 = $conn->query("SELECT COUNT(*) AS cnt FROM client_requests WHERE DATE_FORMAT(created_at, '%Y-%m') = '$month' AND niche_id LIKE 'OM%'");
    $requestsPerMonthOld[] = ($resR2 && $row = $resR2->fetch_assoc()) ? intval($row['cnt']) : 0;
}

// Donut chart: Request Type Distribution (filtered by year)
$requestTypeCountsNew = ['New' => 0, 'Relocate' => 0, 'Transfer' => 0];
$resRTN = $conn->query("SELECT type, COUNT(*) AS cnt FROM client_requests WHERE YEAR(created_at) = $currentYear AND (niche_id NOT LIKE 'OM%' OR niche_id IS NULL) GROUP BY type");
if ($resRTN) {
    while ($row = $resRTN->fetch_assoc()) {
        if (isset($requestTypeCountsNew[$row['type']])) $requestTypeCountsNew[$row['type']] = intval($row['cnt']);
    }
}
$requestTypeDataNew = array_values($requestTypeCountsNew);

$requestTypeCountsOld = ['New' => 0, 'Relocate' => 0, 'Transfer' => 0];
$resRTO = $conn->query("SELECT type, COUNT(*) AS cnt FROM client_requests WHERE YEAR(created_at) = $currentYear AND niche_id LIKE 'OM%' GROUP BY type");
if ($resRTO) {
    while ($row = $resRTO->fetch_assoc()) {
        if (isset($requestTypeCountsOld[$row['type']])) $requestTypeCountsOld[$row['type']] = intval($row['cnt']);
    }
}
$requestTypeDataOld = array_values($requestTypeCountsOld);
$requestTypeLabels = array_keys($requestTypeCountsNew);

// Bar chart: Deceased per floor (year and all years)
$floors = ['1F', '2F', '3F'];
$deceasedPerFloorNew = [];
$deceasedPerFloorNewAll = [];
foreach ($floors as $floor) {
    $resY = $conn->query("SELECT COUNT(*) AS cnt FROM deceased WHERE nicheID LIKE '{$floor}-%' AND YEAR(dateInternment) = $currentYear AND nicheID NOT LIKE 'OM%'");
    $deceasedPerFloorNew[] = ($resY && $row = $resY->fetch_assoc()) ? intval($row['cnt']) : 0;
    $resA = $conn->query("SELECT COUNT(*) AS cnt FROM deceased WHERE nicheID LIKE '{$floor}-%' AND nicheID NOT LIKE 'OM%'");
    $deceasedPerFloorNewAll[] = ($resA && $row = $resA->fetch_assoc()) ? intval($row['cnt']) : 0;
}

$oldMapFloors = ['OM-1F', 'OM-2F'];
$deceasedPerFloorOld = [];
$deceasedPerFloorOldAll = [];
foreach ($oldMapFloors as $floor) {
    $resYO = $conn->query("SELECT COUNT(*) AS cnt FROM deceased WHERE nicheID LIKE '{$floor}-%' AND YEAR(dateInternment) = $currentYear");
    $deceasedPerFloorOld[] = ($resYO && $row = $resYO->fetch_assoc()) ? intval($row['cnt']) : 0;
    $resAO = $conn->query("SELECT COUNT(*) AS cnt FROM deceased WHERE nicheID LIKE '{$floor}-%'");
    $deceasedPerFloorOldAll[] = ($resAO && $row = $resAO->fetch_assoc()) ? intval($row['cnt']) : 0;
}
$deceasedFloorLabels = $floors;
$deceasedFloorLabelsOld = ['1F', '2F'];

// --- Prepare data for both new map and old map ---

// New map: exclude nicheID starting with 'OM'
$newMapOccupiedArr = [];
$resNew = $conn->query("SELECT DISTINCT nicheID FROM deceased WHERE nicheID IS NOT NULL AND nicheID != '' AND nicheID != 'null' AND nicheID NOT LIKE 'OM%'");
if ($resNew) {
    while ($row = $resNew->fetch_assoc()) {
        $newMapOccupiedArr[$row['nicheID']] = true;
    }
}
$newMapOccupied = count($newMapOccupiedArr);
$newMapAvailable = $totalPhysicalNiches - $newMapOccupied;
if ($newMapAvailable < 0) $newMapAvailable = 0;

// Old map: nicheID starting with 'OM'
$oldMapOccupiedArr = [];
$resOld = $conn->query("SELECT DISTINCT nicheID FROM deceased WHERE nicheID IS NOT NULL AND nicheID != '' AND nicheID != 'null' AND nicheID LIKE 'OM%'");
if ($resOld) {
    while ($row = $resOld->fetch_assoc()) {
        $oldMapOccupiedArr[$row['nicheID']] = true;
    }
}
$oldMapOccupied = count($oldMapOccupiedArr);

// Old map available: 2307 minus occupied
$totalOldMapNiches = 2307;
$oldMapAvailable = $totalOldMapNiches - $oldMapOccupied;
if ($oldMapAvailable < 0) $oldMapAvailable = 0;

// NEW: simple percentages for progress bars
$availPctNew = $totalPhysicalNiches > 0 ? round(($newMapAvailable / $totalPhysicalNiches) * 100, 1) : 0;
$occPctNew   = $totalPhysicalNiches > 0 ? round(($newMapOccupied / $totalPhysicalNiches) * 100, 1) : 0;
$availPctOld = $totalOldMapNiches   > 0 ? round(($oldMapAvailable / $totalOldMapNiches) * 100, 1) : 0;
$occPctOld   = $totalOldMapNiches   > 0 ? round(($oldMapOccupied / $totalOldMapNiches) * 100, 1) : 0;

// --- end new section ---

// --- NEW: compute simple baselines and changes for stat indicators ---
// New map occupied 30 days ago (to derive previous available)
$occupied30Arr = [];
$resOcc30 = $conn->query("SELECT DISTINCT nicheID FROM deceased WHERE nicheID IS NOT NULL AND nicheID != '' AND nicheID != 'null' AND nicheID NOT LIKE 'OM%' AND dateInternment <= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
if ($resOcc30) {
    while ($r = $resOcc30->fetch_assoc()) {
        $occupied30Arr[$r['nicheID']] = true;
    }
}
$occupied30 = count($occupied30Arr);
$prevNewMapAvailable = $totalPhysicalNiches - $occupied30;
if ($prevNewMapAvailable < 0) $prevNewMapAvailable = 0;
$newMapAvailableChange = $newMapAvailable - $prevNewMapAvailable;

// Pending requests: compare last 7 days vs previous 7 days
$resLast7 = $conn->query("SELECT COUNT(*) AS cnt FROM client_requests WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
$last7 = ($resLast7 && $r = $resLast7->fetch_assoc()) ? intval($r['cnt']) : 0;
$resPrev7 = $conn->query("SELECT COUNT(*) AS cnt FROM client_requests WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY) AND created_at < DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
$prev7 = ($resPrev7 && $r = $resPrev7->fetch_assoc()) ? intval($r['cnt']) : 0;
$pendingRequestChange = $last7 - $prev7;

// Active clients change: new users in last 30 days vs previous 30-day window
$resClientsLast30 = $conn->query("SELECT COUNT(*) AS cnt FROM users WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
$clientsLast30 = ($resClientsLast30 && $r = $resClientsLast30->fetch_assoc()) ? intval($r['cnt']) : 0;
$resClientsPrev30 = $conn->query("SELECT COUNT(*) AS cnt FROM users WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 60 DAY) AND created_at < DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
$clientsPrev30 = ($resClientsPrev30 && $r = $resClientsPrev30->fetch_assoc()) ? intval($r['cnt']) : 0;
$activeClientsChange = $clientsLast30 - $clientsPrev30;

// Occupied niches change (new map): compare current occupied vs occupied 30 days ago
$newMapOccupiedChange = $newMapOccupied - $occupied30;
// --- end new section ---

// Get admin name
$adminId = $_SESSION['admin_id'];
$adminName = 'Admin';
$adminProfilePic = '../assets/Default Image.jpg';
// Fetch display_name and profile_pic from admin_profiles
$stmt = $conn->prepare('SELECT display_name, profile_pic FROM admin_profiles WHERE admin_id = ? LIMIT 1');
$stmt->bind_param('i', $adminId);
$stmt->execute();
$stmt->bind_result($displayName, $profilePic);
if ($stmt->fetch()) {
    $adminName = $displayName ? $displayName : $adminName;
    $adminProfilePic = $profilePic ? $profilePic : $adminProfilePic;
}
$stmt->close();

// Get records whose validity is expired or expiring within 1 year (from today)
$expiringRecords = [];
$today = date('Y-m-d');
$oneYearFromNow = date('Y-m-d', strtotime('+1 year'));
$sql = "SELECT id, nicheID, lastName, firstName, middleName, suffix, dateInternment, informantName FROM deceased WHERE dateInternment IS NOT NULL AND dateInternment != '' AND dateInternment != '0000-00-00'";
$res = $conn->query($sql);
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $internmentDate = $row['dateInternment'];
        try {
            $validityDate = (new DateTime($internmentDate))->modify('+5 years')->format('Y-m-d');
            // Only include if validity is expired or will expire within 1 year from today
            if ($validityDate <= $oneYearFromNow) {
                $name = $row['lastName'] . ', ' . $row['firstName'];
                if (!empty($row['middleName'])) $name .= ' ' . strtoupper(substr(trim($row['middleName']), 0, 1)) . '.';
                if (!empty($row['suffix'])) $name .= ' ' . $row['suffix'];

                // Try to find a registered user who matches the informant name (best-effort)
                $clientEmail = null;
                $clientId = null;
                $informantRaw = trim($row['informantName'] ?? '');

                if (!empty($informantRaw)) {
                    $stmtUser = $conn->prepare("SELECT id, email FROM users WHERE CONCAT(first_name, ' ', last_name) = ? LIMIT 1");
                    if ($stmtUser) {
                        $stmtUser->bind_param('s', $informantRaw);
                        $stmtUser->execute();
                        $resU = $stmtUser->get_result();
                        if ($ru = $resU->fetch_assoc()) {
                            $clientId = intval($ru['id']);
                            $clientEmail = $ru['email'];
                        }
                        $stmtUser->close();
                    }
                    if (!$clientId && strpos($informantRaw, ',') !== false) {
                        $stmtUser2 = $conn->prepare("SELECT id, email FROM users WHERE CONCAT(last_name, ', ', first_name) = ? LIMIT 1");
                        if ($stmtUser2) {
                            $stmtUser2->bind_param('s', $informantRaw);
                            $stmtUser2->execute();
                            $resU2 = $stmtUser2->get_result();
                            if ($ru2 = $resU2->fetch_assoc()) {
                                $clientId = intval($ru2['id']);
                                $clientEmail = $ru2['email'];
                            }
                            $stmtUser2->close();
                        }
                    }
                }

                // Include informant name in the record for accurate display in the notify popup
                $expiringRecords[] = [
                    'nicheID' => $row['nicheID'],
                    'name' => $name,
                    'validity' => $validityDate,
                    'client_id' => $clientId,
                    'client_email' => $clientEmail,
                    'informant' => $informantRaw
                ];
            }
        } catch (Exception $e) {}
    }
    // Sort by closest validity date (expired first, then soonest expiry)
    usort($expiringRecords, function($a, $b) {
        return strcmp($a['validity'], $b['validity']);
    });

    // --- NEW: ensure expiry_notifications table exists and load notified statuses (persist one-time notifications) ---
    $conn->query("CREATE TABLE IF NOT EXISTS expiry_notifications (
      id INT AUTO_INCREMENT PRIMARY KEY,
      nicheID VARCHAR(255),
      name VARCHAR(255),
      validity DATE,
      contact_type VARCHAR(50),
      contact_value VARCHAR(255),
      admin_id INT,
      message TEXT,
      status VARCHAR(50),
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $notifiedMap = []; // key = "niche|validity" => status ('sent','queued','failed',...)
    if (!empty($expiringRecords)) {
        $stmtLookup = $conn->prepare("SELECT status FROM expiry_notifications WHERE nicheID = ? AND validity = ? ORDER BY created_at DESC LIMIT 1");
        foreach ($expiringRecords as $rec) {
            $keyN = $rec['nicheID'];
            $keyV = $rec['validity'];
            if ($stmtLookup) {
                $stmtLookup->bind_param('ss', $keyN, $keyV);
                $stmtLookup->execute();
                $resN = $stmtLookup->get_result();
                if ($rowN = $resN->fetch_assoc()) {
                    $notifiedMap[$keyN . '|' . $keyV] = $rowN['status'];
                }
            }
        }
        if ($stmtLookup) $stmtLookup->close();
    }
    // --- end new code ---
}

// Year filter logic
$currentYear = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
$yearOptions = [];
for ($y = 1900; $y <= intval(date('Y')); $y++) {
    $yearOptions[] = $y;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>RestEase Admin Dashboard</title>
  <link rel="icon" type="image/png" href="../assets/re logo blue.png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/dashboard.css">
  <link rel="stylesheet" href="../css/sidebar.css">
  <style>
    .dashboard-grid {
      display: grid;
      grid-template-columns: 2fr 1fr;
      gap: 24px;
      margin-top: 24px;
    }
    .dashboard-card {
      background: #fff;
      border-radius: 18px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.07);
      padding: 0;
      min-height: 340px;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .dashboard-card-large {
      padding: 24px 32px 8px 32px;
      min-height: 340px;
    }
    .dashboard-card-small {
      min-height: 340px;
      /* You can add content or leave empty for now */
    }
    #chart {
      width: 100%;
      max-width: 100%;
      margin: 0;
    }

    /* Notify button styles */
    .notify-btn {
      background: #2563eb;
      color: #fff;
      border: none;
      padding: 6px 10px;
      border-radius: 6px;
      cursor: pointer;
      font-size: 0.92rem;
      font-weight: 600;
    }
    .notify-btn[disabled] {
      opacity: 0.65;
      cursor: not-allowed;
    }
    /* changed: show status under the button when aligned to the right */
    .notify-status {
      color: #10b981;
      font-weight: 700;
      font-size: 0.92rem;
      margin-top: 6px;
      text-align: right;
    }

    /* Modal for notify contact input (SIMPLIFIED & aligned) */
    .notify-modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.35);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 10000;
      padding: 18px;
    }
    .notify-modal {
      background: #ffffff;
      border-radius: 10px;
      padding: 14px;
      width: 420px;
      max-width: calc(100% - 32px);
      box-shadow: 0 6px 18px rgba(2,6,23,0.08);
      border: 1px solid #e6eef6;
      font-family: "Poppins", sans-serif;
      color: #0f172a;
    }
    .notify-modal .modal-header {
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:12px;
      margin-bottom: 8px;
    }
    .notify-modal h3 { margin: 0; font-size: 1rem; font-weight:600; color:#0f172a; }
    .notify-close {
      background: transparent;
      border: none;
      color: #475569;
      font-size: 16px;
      cursor: pointer;
      padding: 6px;
      border-radius: 6px;
    }
    .notify-close:hover { color:#111827; }

    .notify-field { margin-bottom: 10px; }
    .notify-field label { display:block; font-size:0.88rem; margin-bottom:6px; color:#374151; font-weight:600; }

    /* record box and input use same sizing & padding to align */
    .record-box,
    .notify-field input,
    .notify-field select {
      box-sizing: border-box;
      width: 100%;
      padding: 10px 12px;
      border-radius: 8px;
      border: 1px solid #e6eef9;
      font-size:0.95rem;
      color:#0f172a;
      background: #fff;
    }

    /* Keep long contact values from breaking layout; show ellipsis and preserve title for hover */
    .notify-field input {
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .notify-field input::placeholder { color:#94a3b8; }
    .notify-field input:focus, .notify-field select:focus {
      outline: none;
      border-color: rgba(99,102,241,0.6);
      box-shadow: 0 6px 14px rgba(99,102,241,0.06);
    }
    #contactType[readonly] { /* keep parity with contactValue readonly look if needed */
      background: #f8fafc;
      cursor: default;
      color: #0f172a;
    }

    /* Move the dropdown caret slightly inside for the Contact Type select.
       Uses a small inline SVG as the caret and positions it a few pixels left. */
    #contactType {
      -webkit-appearance: none;
      -moz-appearance: none;
      appearance: none;
      background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 20 20'><path d='M5 7l5 5 5-5' stroke='%23475369' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round' fill='none'/></svg>");
      background-repeat: no-repeat;
      /* move the caret a bit left from the very edge */
      background-position: right 14px center;
      /* ensure select text doesn't overlap the caret */
      padding-right: 40px;
      cursor: pointer;
    }

    #notifyModalError { color:#ef4444; margin-top:6px; display:none; font-size:0.92rem; }

    .notify-actions {
      display:flex;
      justify-content:flex-end;
      gap:10px;
      margin-top:10px;
    }
    .btn-secondary {
      background:#ffffff;
      border:1px solid #e6eef9;
      padding:8px 12px;
      border-radius:8px;
      cursor:pointer;
      color:#0f172a;
      font-weight:600;
    }
    .btn-primary {
      background:#2563eb;
      color:#fff;
      padding:8px 12px;
      border-radius:8px;
      border:none;
      cursor:pointer;
      font-weight:700;
    }

    /* Stat indicator small arrow + color */
    .stat-indicator { display:flex; align-items:center; gap:8px; font-size:0.9rem; margin-top:6px; }
    .stat-indicator .icon { font-size:0.95rem; }
    .stat-indicator .up { color:#10b981; }      /* green for up */
    .stat-indicator .down { color:#ef4444; }    /* red for down */
    .stat-indicator .neutral { color:#6b7280; } /* gray for no change */
    .stat-indicator .change-value { font-weight:700; }
    /* ensure small arrow aligns nicely inside stat card */
    .stat-card .stat-meta { margin-top:6px; }

    /* Thin progress bars for KPI cards */
    .kpi-progress { margin-top: 8px; }
    .kpi-progress .progress {
      width: 100%;
      height: 8px;
      background: #eef2f7;
      border-radius: 999px;
      overflow: hidden;
    }
    .kpi-progress .progress-bar {
      height: 100%;
      transition: width 300ms ease;
      background: linear-gradient(90deg, #60A5FA, #2563eb);
    }
    .kpi-progress .progress-bar.warn { background: linear-gradient(90deg, #FCA5A5, #EF4444); }
    .kpi-progress .progress-label {
      margin-top: 6px;
      font-size: 0.86rem;
      color: #6b7280;
      font-weight: 600;
    }

    /* Skeleton loaders for charts */
    .skeleton {
      position: relative;
      overflow: hidden;
      background: #f3f4f6;
    }
    .skeleton::after {
      content: '';
      position: absolute;
      inset: 0;
      transform: translateX(-100%);
      background: linear-gradient(90deg, rgba(255,255,255,0), rgba(255,255,255,0.6), rgba(255,255,255,0));
      animation: shimmer 1.2s infinite;
    }
    @keyframes shimmer { 100% { transform: translateX(100%);} }

    /* Subtle hover on notify */
    .notify-btn:hover { filter: brightness(1.05); }

    /* Small utilities */
    .row { display:flex; align-items:center; gap:10px; }

    @media (max-width:520px){
      .notify-modal { width: 100%; padding: 12px; border-radius: 8px; }
      .notify-modal .modal-header { gap:8px; }
    }

    /* Layout variables for the expiring-records list */
    :root {
      --exp-item-height: 86px;     /* approximate per-record height (adjust if you tweak item padding) */
      --exp-item-gap: 16px;        /* gap between items */
      --exp-list-rows: 3;          /* how many rows to reserve by default (image #1 shows 3 items) */
      --exp-list-padding-vertical: 12px;
    }

    /* Ensure container keeps a stable area even when filtering - reserve space for 3 rows */
    #expListContainer {
      min-height: calc((var(--exp-item-height) + var(--exp-item-gap)) * var(--exp-list-rows) + (var(--exp-list-padding-vertical) * 2));
      padding: var(--exp-list-padding-vertical) 55px;
      box-sizing: border-box;
      overflow-y: auto;
    }

    /* Keep UL clean in case inline styles differ elsewhere */
    #expListContainer > ul { list-style: none; margin: 0; padding: 0; }

    /* Make each record a consistent box so spacing/width don't jump */
    #expListContainer > ul > li[data-exp-item] {
      min-height: var(--exp-item-height);
      box-sizing: border-box;
      margin-bottom: var(--exp-item-gap);
      display: flex;
      align-items: flex-start;
      gap: 12px;
    }

    /* Keep hidden class but let the container min-height preserve overall layout */
    .exp-hidden { display: none !important; }

    /* No-results message sits inside container and uses same vertical padding */
    #expNoResults { padding: var(--exp-list-padding-vertical) 0; color: #888; }

    /* Consistent small width for the floor dropdown (prevent jump when content changes) */
    #expFloorFilter {
      min-width: 64px;
      width: 64px;
      max-width: 120px;
      margin-left: 8px;
      padding: 6px 10px;
      border-radius: 6px;
      border: 1px solid #d1d5db;
      background: #fff;
      box-sizing: border-box;
    }

    /* ADDED: status filter styling to match floor filter */
    #expStatusFilter {
      min-width: 84px;
      width: 84px;
      max-width: 140px;
      margin-left: 8px;
      padding: 6px 10px;
      border-radius: 6px;
      border: 1px solid #d1d5db;
      background: #fff;
      box-sizing: border-box;
    }

    /* New: style for the View Map button */
    .view-map-btn {
      background: #003591ff;          
      color: #ffffff;                /* text color */
      border: none;
      padding: 10px 14px;
      border-radius: 10px;
      font-weight: 700;
      cursor: pointer;
      box-shadow: 0 4px 10px rgba(16,185,129,0.12);
      transition: transform 120ms ease, box-shadow 120ms ease, filter 120ms ease;
    }
    .view-map-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 18px rgba(16,185,129,0.14);
      filter: brightness(0.97);
    }
    .view-map-btn:active {
      transform: translateY(0);
      box-shadow: 0 4px 8px rgba(16,185,129,0.10);
    }

    /* ===== ADDED: reduce large side paddings and widen expiring cards ===== */
    /* Make the title not push content to the right (overrides inline padding-left) */
    .dashboard-card-small h3 {
      padding-left: 93px !important;
    }

    /* Reduce the filter row horizontal padding so controls and list can use more width */
    .dashboard-card-small .row {
      padding: 0 16px 8px 16px !important;
    }

    /* Reduce the expiring-list container side padding (was 55px) */
    #expListContainer {
      padding-left: 16px !important;
      padding-right: 16px !important;
      /* keep the reserved min-height */
    }

    /* Ensure each list item uses full width and the inner "card" area expands */
    #expListContainer > ul > li[data-exp-item] {
      width: 100%;
      margin-bottom: var(--exp-item-gap);
      display: flex;
      align-items: flex-start;
      gap: 12px;
      box-sizing: border-box;
    }

    /* Target the main inner card (it's the 2nd child div inside the li) and make it full width */
    #expListContainer > ul > li[data-exp-item] > div:nth-child(2) {
      padding: 10px 14px !important;
      min-width: 0;
      flex: 1 1 auto;
      display: flex;
      align-items: center;
      justify-content: space-between;
      background: #f8fafc; /* keep existing look */
      border-radius: 10px;
      box-shadow: 0 1px 4px rgba(0,0,0,0.04);
      box-sizing: border-box;
    }

    /* Slightly reduce badge/button right margin so content fits on one line on narrower viewports */
    #expListContainer .notify-btn {
      padding: 8px 10px;
      font-size: 0.92rem;
      margin-left: 8px;
      white-space: nowrap;
    }

    /* Responsive: keep small screens comfortable */
    @media (max-width: 520px) {
      .dashboard-card-small h3 { padding-left: 12px !important; }
      .dashboard-card-small .row { padding: 0 10px 8px 10px !important; }
      #expListContainer { padding-left: 10px !important; padding-right: 10px !important; }
      #expListContainer > ul > li[data-exp-item] > div:nth-child(2) { padding: 10px 10px !important; }
      #expListContainer .notify-btn { padding: 7px 10px; font-size: 0.88rem; }
    }
    /* ===== end ADDED ===== */

    /* ...existing styles... */
  </style>
</head>
<body>
   <!-- Sidebar -->
   <?php include '../Includes/sidebar.php'; ?>

  <!-- Main Content -->
  <main class="main-content">
    <!-- Header -->
    <header class="header">
      <div class="header-left">
        <div class="greeting">
          <div class="hello-text">Hello, <span class="username"><?php echo htmlspecialchars($adminName); ?></span></div>
          <div class="datetime">
            <span class="date" id="current-date"></span>
            <span class="time" id="current-time"></span>
            <!-- NEW: quick Refresh -->
            <button title="Refresh" onclick="location.reload()" style="margin-left:10px;border:1px solid #e5e7eb;background:#fff;padding:4px 8px;border-radius:6px;cursor:pointer;color:#374151;">
              <i class="fa-solid fa-rotate-right"></i>
            </button>
          </div>
        </div>
      </div>
      <div class="user-profile">
        <div class="profile-info">
            <!-- notification bell placed to the left of the avatar; inline styles keep layout/size unchanged -->
      <button class="notif-bell" aria-label="Notifications" title="Notifications"
         onclick="window.location.href='<?php echo htmlspecialchars($settingsUrl, ENT_QUOTES); ?>';"
        style="background:transparent;border:none;padding:0;margin-right:8px;cursor:pointer;color:inherit;position:relative;">
        <i class="fa-solid fa-bell" style="font-size:1.05rem;color:inherit;"></i>
        <!-- changed: small red dot (no number) for dashboard bell -->
        <span id="notifBellCountDashboard"
              aria-hidden="true"
              title="You have unread notifications"
              style="display:none;position:absolute;top:-6px;right:-6px;background:#e74c3c;width:10px;height:10px;border-radius:50%;box-shadow:0 1px 3px rgba(0,0,0,0.12);z-index:1000;pointer-events:none;border:2px solid #fff;line-height:0;">
        </span>
      </button>
          <img src="<?php echo htmlspecialchars($adminProfilePic); ?>" alt="Profile" class="profile-avatar">
          <div>
            <div class="profile-name"><?php echo htmlspecialchars($adminName); ?></div>
            <div class="profile-role">Admin</div>
          </div>
        </div>
      </div>
    </header>
    <script>
      // Manila timezone (UTC+8)
      function updateDateTime() {
        const now = new Date();
        // Convert to Manila time
        const utc = now.getTime() + (now.getTimezoneOffset() * 60000);
        const manila = new Date(utc + (3600000 * 8));
        const days = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
        const months = [
          "January", "February", "March", "April", "May", "June",
          "July", "August", "September", "October", "November", "December"
        ];
        const day = days[manila.getDay()];
        const month = months[manila.getMonth()];
        const date = manila.getDate();
        const year = manila.getFullYear();
        let hours = manila.getHours();
        let minutes = manila.getMinutes();
        let ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12;
        hours = hours ? hours : 12;
        minutes = minutes < 10 ? '0'+minutes : minutes;
        document.getElementById('current-date').textContent = `${day}, ${month} ${date}, ${year}`;
        document.getElementById('current-time').textContent = `${hours}:${minutes} ${ampm}`;
      }
      updateDateTime();
      setInterval(updateDateTime, 1000);
    </script>

    <!-- Dashboard notification bell badge updater -->
    <script>
    (function(){
      function getUnreadCountFromLocalStorage() {
        try {
          var raw = localStorage.getItem('systemNotifs');
          if (raw) {
            var arr = JSON.parse(raw);
            if (Array.isArray(arr) && arr.length) {
              var c = 0;
              arr.forEach(function(notif){
                var readKey = 'notif_read_' + (notif.id ?? notif.ID ?? notif._id ?? '');
                if (readKey && localStorage.getItem(readKey) !== '1') c++;
              });
              return c;
            }
          }
        } catch (e) {}
        var unread = 0;
        for (var i = 0; i < localStorage.length; i++) {
          var key = localStorage.key(i);
          if (key && key.indexOf('notif_read_') === 0) {
            if (localStorage.getItem(key) !== '1') unread++;
          }
        }
        return unread;
      }

      function updateBellCountDashboard() {
        var el = document.getElementById('notifBellCountDashboard');
        if (!el) return;
        var count = getUnreadCountFromLocalStorage();

        if (count > 0) {
          // show small red dot (no numeric text)
          el.textContent = '';
          el.style.display = 'block';
          el.style.width = '10px';
          el.style.height = '10px';
          el.style.padding = '0';
          el.style.opacity = '1';
          el.setAttribute('aria-hidden', 'false');
          el.setAttribute('aria-label', count + ' unread notifications');
        } else {
          el.textContent = '';
          el.style.display = 'none';
          el.setAttribute('aria-hidden', 'true');
          el.removeAttribute('aria-label');
        }
      }

      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', updateBellCountDashboard);
      } else {
        updateBellCountDashboard();
      }

      window.addEventListener('storage', function(e){
        if (!e.key || e.key === 'systemNotifs' || e.key.startsWith('notif_read_')) {
          updateBellCountDashboard();
        }
      });

      // Poll fallback for updates when storage events may not fire
      setInterval(updateBellCountDashboard, 5000);
    })();
    </script>

    <!-- Dashboard Content -->
    <section class="dashboard-welcome">
      <div class="welcome-banner">
        <div>
          <h2>Welcome to RestEase!</h2>
          <p>Let's keep everything organized and running smoothly.</p>
          <a href="Mapping.php"><button class="view-map-btn">View Map</button></a>
        </div>
      </div>
    </section>
    <section class="dashboard-stats">
      <div class="stats-row">
        <div class="stat-card" style="position: relative;">
          <!-- Arrow flip icon -->
          <div id="nicheCardArrows" style="position: absolute; top: 10px; right: 14px; z-index: 2;">
            <span id="nicheCardFlip" style="cursor:pointer; font-size: 1.1rem; color: #888;">
              <i class="fa-solid fa-arrow-right-arrow-left"></i>
            </span>
          </div>
          <div id="nicheCardFront">
            <div class="stat-title">Available Niches (new map)</div>
            <div class="stat-value"><?php echo $newMapAvailable; ?> Available Niches</div>
            <!-- NEW: capacity bar (Available %) -->
            <div class="kpi-progress">
              <div class="progress"><div class="progress-bar" style="width: <?php echo $availPctNew; ?>%;"></div></div>
              <div class="progress-label"><?php echo $availPctNew; ?>% available</div>
            </div>
            <div class="stat-meta">
              <?php
                // compute display for change
                if ($newMapAvailableChange > 0) {
                  $cls = 'up'; $sym = '▲'; $disp = '+' . $newMapAvailableChange;
                } elseif ($newMapAvailableChange < 0) {
                  $cls = 'down'; $sym = '▼'; $disp = (string)$newMapAvailableChange;
                } else {
                  $cls = 'neutral'; $sym = '•'; $disp = '0';
                }
              ?>
              <div class="stat-indicator"><span class="icon <?php echo $cls; ?>"><?php echo $sym; ?></span><span class="change-value <?php echo $cls; ?>"><?php echo htmlspecialchars($disp); ?></span><span style="color:#6b7280;">vs 30d</span></div>
            </div>
          </div>
          <div id="nicheCardBack" style="display:none;">
            <div class="stat-title">Available Niches (old map)</div>
            <div class="stat-value"><?php echo $oldMapAvailable; ?> Available Niches</div>
            <!-- NEW: capacity bar (Available % old map) -->
            <div class="kpi-progress">
              <div class="progress"><div class="progress-bar" style="width: <?php echo $availPctOld; ?>%;"></div></div>
              <div class="progress-label"><?php echo $availPctOld; ?>% available</div>
            </div>
            <!-- For old map we reuse simple comparison vs previous occupied (use same occupied30 as approximation) -->
            <?php
              $oldPrev = $totalOldMapNiches - $occupied30; if ($oldPrev < 0) $oldPrev = 0;
              $oldChange = $oldMapAvailable - $oldPrev;
              if ($oldChange > 0) { $cls2='up'; $sym2='▲'; $disp2='+' . $oldChange; }
              elseif ($oldChange < 0) { $cls2='down'; $sym2='▼'; $disp2=(string)$oldChange; }
              else { $cls2='neutral'; $sym2='•'; $disp2='0'; }
            ?>
                       <div class="stat-meta"><div class="stat-indicator"><span class="icon <?php echo $cls2; ?>"><?php echo $sym2; ?></span><span class="change-value <?php echo $cls2; ?>"><?php echo htmlspecialchars($disp2); ?></span><span style="color:#6b7280;">vs 30d</span></div></div>
          </div>
        </div>

        <div class="stat-card" style="position: relative;">
          <div id="occupiedCardFront">
            <div class="stat-title">Occupied Niches (new map)</div>
            <div class="stat-value"><?php echo $newMapOccupied; ?> Niches Occupied</div>
            <!-- NEW: capacity bar (Occupied %) -->
            <div class="kpi-progress">
              <div class="progress"><div class="progress-bar warn" style="width: <?php echo $occPctNew; ?>%;"></div></div>
              <div class="progress-label"><?php echo $occPctNew; ?>% occupied</div>
            </div>
            <?php
              if ($newMapOccupiedChange > 0) { $ccls='up'; $csym='▲'; $cdisp='+' . $newMapOccupiedChange; }
              elseif ($newMapOccupiedChange < 0) { $ccls='down'; $csym='▼'; $cdisp=(string)$newMapOccupiedChange; }
              else { $ccls='neutral'; $csym='•'; $cdisp='0'; }
            ?>
            <div class="stat-meta"><div class="stat-indicator"><span class="icon <?php echo $ccls; ?>"><?php echo $csym; ?></span><span class="change-value <?php echo $ccls; ?>"><?php echo htmlspecialchars($cdisp); ?></span><span style="color:#6b7280;">vs 30d</span></div></div>
          </div>
          <div id="occupiedCardBack" style="display:none;">
            <div class="stat-title">Occupied Niches (old map)</div>
            <div class="stat-value"><?php echo $oldMapOccupied; ?> Niches Occupied</div>
            <!-- NEW: capacity bar (Occupied % old map) -->
            <div class="kpi-progress">
              <div class="progress"><div class="progress-bar warn" style="width: <?php echo $occPctOld; ?>%;"></div></div>
              <div class="progress-label"><?php echo $occPctOld; ?>% occupied</div>
            </div>
            <?php
              $oldOccPrev = $occupied30; // approximation
              $oldOccChange = $oldMapOccupied - $oldOccPrev;
              if ($oldOccChange > 0) { $oc='up'; $os='▲'; $od='+' . $oldOccChange; }
              elseif ($oldOccChange < 0) { $oc='down'; $os='▼'; $od=(string)$oldOccChange; }
              else { $oc='neutral'; $os='•'; $od='0'; }
            ?>
            <div class="stat-meta"><div class="stat-indicator"><span class="icon <?php echo $oc; ?>"><?php echo $os; ?></span><span class="change-value <?php echo $oc; ?>"><?php echo htmlspecialchars($od); ?></span><span style="color:#6b7280;">vs 30d</span></div></div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-title">Pending Request</div>
          <div class="stat-value"><?php echo $pendingRequest; ?> Pending Request</div>
          <?php
            if ($pendingRequestChange > 0) { $pcls='up'; $psym='▲'; $pdisp='+' . $pendingRequestChange; }
            elseif ($pendingRequestChange < 0) { $pcls='down'; $psym='▼'; $pdisp=(string)$pendingRequestChange; }
            else { $pcls='neutral'; $psym='•'; $pdisp='0'; }
          ?>
          <div class="stat-meta"><div class="stat-indicator"><span class="icon <?php echo $pcls; ?>"><?php echo $psym; ?></span><span class="change-value <?php echo $pcls; ?>"><?php echo htmlspecialchars($pdisp); ?></span><span style="color:#6b7280;">vs prev 7d</span></div></div>
        </div>

        <div class="stat-card">
          <div class="stat-title">Total Clients Registered</div>
          <div class="stat-value"><?php echo $activeClients; ?> Total Clients</div>
          <?php
            if ($activeClientsChange > 0) { $acls='up'; $asym='▲'; $adisp='+' . $activeClientsChange; }
            elseif ($activeClientsChange < 0) { $acls='down'; $asym='▼'; $adisp=(string)$activeClientsChange; }
            else { $acls='neutral'; $asym='•'; $adisp='0'; }
          ?>
          <div class="stat-meta"><div class="stat-indicator"><span class="icon <?php echo $acls; ?>"><?php echo $asym; ?></span><span class="change-value <?php echo $acls; ?>"><?php echo htmlspecialchars($adisp); ?></span><span style="color:#6b7280;">vs prev 30d</span></div></div>
        </div>
      </div>
    </section>
    <!-- Year Filter Dropdown -->
    <div style="margin: 18px 0 0 0; display: flex; align-items: center; gap: 12px; margin-left: 59px;">
      <label for="yearFilter" style="font-weight: 500; color: #374151;">Filter Year:</label>
      <select id="yearFilter" style="padding: 6px 12px; border-radius: 6px; border: 1px solid #d1d5db; font-size: 1rem;">
        <?php foreach ($yearOptions as $y): ?>
          <option value="<?php echo $y; ?>" <?php if ($y == $currentYear) echo 'selected'; ?>><?php echo $y; ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <script>
      document.getElementById('yearFilter').addEventListener('change', function() {
        const year = this.value;
        const url = new URL(window.location.href);
        url.searchParams.set('year', year);
        window.location.href = url.toString();
      });
    </script>
    <section class="dashboard-grid">
      <div class="dashboard-card dashboard-card-medium">
        <!-- add skeleton shimmer until charts render -->
        <div id="chart" class="skeleton"></div>
      </div>
      <div class="dashboard-card dashboard-card-small">
        <div style="padding: 18px 14px 18px 18px; width: 100%; height: 100%; display: flex; flex-direction: column;">
          <h3 style="font-size: 1.13rem; margin-bottom: 10px; color: #374151; font-weight: 700; letter-spacing: 0.5px; padding-left: 95px; margin-top: 2px;">Upcoming Validity Expiry</h3>
          <!-- NEW: quick filter + export -->
          <div class="row" style="padding: 0 55px 8px 55px; align-items: center;">
            <input id="expSearch" type="text" placeholder="Search name or Apt..." style="flex:1;padding:6px 10px;border:1px solid #d1d5db;border-radius:6px; box-sizing: border-box;">
            <!-- width controlled by CSS (#expFloorFilter) to keep it stable -->
            <select id="expFloorFilter">
               <option value="all">All</option>
               <option value="1F">1F — 1st Floor</option>
               <option value="2F">2F — 2nd Floor</option>
               <option value="3F">3F — 3rd Floor</option>
               <option value="OM">OM — Old Map</option>
             </select>

             <!-- ADDED: new status filter dropdown (All / Expired / Upcoming) -->
             <select id="expStatusFilter" title="Filter by status">
               <option value="all">All</option>
               <option value="expired">Expired</option>
               <option value="upcoming">Upcoming</option>
             </select>
           </div>
          <!-- keep a stable container so layout/padding doesn't collapse after filtering -->
          <div id="expListContainer" style="flex: 1; max-height: 320px; margin-top: 0; box-sizing: border-box;">
            <?php if (count($expiringRecords) > 0): ?>
              <ul style="list-style: none; padding: 0; margin: 0;">
                <?php foreach ($expiringRecords as $rec): ?>
                  <?php
                    // Determine validity color: expired = red, upcoming = orange
                    $validityColor = ($rec['validity'] < $today) ? '#ef4444' : '#eab308';
                  ?>
                  <li data-exp-item="1"
                      data-name="<?php echo htmlspecialchars($rec['name']); ?>"
                      data-apt="<?php echo htmlspecialchars($rec['nicheID']); ?>"
                      data-validity="<?php echo htmlspecialchars($rec['validity']); ?>"
                      >
                    <div style="margin-top: 2px;">
                      <i class="fa-solid fa-calendar-exclamation" style="color: #eab308; font-size: 1.25rem;"></i>
                    </div>
                    <div style="background: #f8fafc; border-radius: 10px; box-shadow: 0 1px 4px rgba(0,0,0,0.04); padding: 10px 14px; min-width: 0; flex: 1; display: flex; align-items: center; justify-content: space-between;">
                      <div style="flex: 1; min-width: 0;">
                        <div style="font-weight: 600; color: #1e293b; font-size: 1rem; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                          <?php echo htmlspecialchars($rec['name']); ?>
                        </div>
                        <div style="font-size: 0.93rem; color: #2563eb; font-weight: 500; margin-bottom: 2px;">Apt: <?php echo htmlspecialchars($rec['nicheID']); ?></div>
                        <div style="font-size: 0.93rem; font-weight: 500; color: <?php echo $validityColor; ?>;">
                          Validity: <?php echo htmlspecialchars($rec['validity']); ?>
                        </div>
                      </div>
                      <div style="margin-left: 16px; display:flex; flex-direction:column; align-items:flex-end; justify-content:center;">
                        <?php
                          $key = $rec['nicheID'] . '|' . $rec['validity'];
                          $alreadyStatus = isset($notifiedMap[$key]) ? $notifiedMap[$key] : null;
                        ?>
                        <button
                          class="notify-btn"
                          data-niche="<?php echo htmlspecialchars($rec['nicheID']); ?>"
                          data-name="<?php echo htmlspecialchars($rec['name']); ?>"
                          data-validity="<?php echo htmlspecialchars($rec['validity']); ?>"
                          data-client-email="<?php echo htmlspecialchars($rec['client_email']); ?>"
                          data-client-id="<?php echo htmlspecialchars($rec['client_id']); ?>"
                          data-informant="<?php echo htmlspecialchars($rec['informant']); ?>"
                          <?php if ($alreadyStatus): ?> disabled <?php endif; ?>>
                          <?php echo $alreadyStatus ? 'Notified' : 'Notify'; ?>
                        </button>
                        <span class="notify-status" style="<?php echo $alreadyStatus ? '' : 'display:none;'; ?>">
                          <?php echo $alreadyStatus ? (ucfirst($alreadyStatus)) : ''; ?>
                        </span>
                      </div>
                    </div>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php else: ?>
              <div id="expEmptyPlaceholder" style="color: #888; font-size: 0.97rem; text-align: center; margin-top: 12px;">No records expiring soon.</div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </section>
 
    <!-- Add gap between grids -->
    <div style="height: 24px;"></div>
    <!-- Lower grid for column and pie cards, smaller size -->
    <section class="dashboard-grid" style="grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 0;">
       <div class="dashboard-card" style="height: 180px; padding: 0; align-items: stretch; justify-content: stretch; position:relative;">
        <!-- Set filter default to 'all' for deceased per floor -->
        <div style="position: absolute; top: 18px; right: 32px; z-index: 2;">
          <select id="deceasedFloorFilter" style="padding:3px 8px;border-radius:6px;border:1px solid #d1d5db;font-size:0.97rem;">
            <option value="year"><?php echo $currentYear; ?></option>
            <option value="all" selected>All Years</option>
          </select>
        </div>
        <div id="floorBarChart" class="skeleton" style="width: 100%; height: 100%;"></div>
      </div>
      <div class="dashboard-card" style="height: 180px; padding: 0; align-items: stretch; justify-content: stretch;">
        <div id="pieChart" class="skeleton" style="width: 100%; height: 100%;"></div>
      </div>
    </section>
    <!-- New chart for request type distribution and deceased per floor -->
    <section class="dashboard-grid" style="grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 24px;">
      <div class="dashboard-card" style="height: 180px; padding: 0; align-items: stretch; justify-content: stretch;">
        <div id="donutChart" class="skeleton" style="width: 100%; height: 100%;"></div>
      </div>
      <div class="dashboard-card" style="height: 180px; padding: 0; align-items: stretch; justify-content: stretch; position:relative;">
        <!-- Place request per month filter inside the request per month card -->
        <div style="position: absolute; top: 18px; right: 32px; z-index: 2;">
          <select id="requestsPerMonthFilter" style="padding:3px 8px;border-radius:6px;border:1px solid #d1d5db;font-size:0.97rem;">
          <option value="all">All Years</option>
          <option value="year" selected><?php echo $currentYear; ?></option>

          </select>
        </div>
        <div id="columnChart" class="skeleton" style="width: 100%; height: 100%;"></div>
      </div>
    </section>
  </main>
  <!-- TOAST CONTAINER -->
  <div id="toastContainer" style="position:fixed;top:24px;right:24px;z-index:99999;display:flex;flex-direction:column;gap:10px;pointer-events:none;"></div>

  <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
  <script>
    // Pass PHP data to JS for both maps
    const pieDataNew = <?php echo json_encode($pieDataNew); ?>;
    const pieDataOld = <?php echo json_encode($pieDataOld); ?>;
    const activeClientsDataNew = <?php echo json_encode($activeClientsPerDayNew); ?>;
    const activeClientsDataOld = <?php echo json_encode($activeClientsPerDayOld); ?>;
    const requestsDataNew = <?php echo json_encode($requestsPerMonthNew); ?>;
    const requestsDataOld = <?php echo json_encode($requestsPerMonthOld); ?>;
    const requestsLabels = <?php echo json_encode($monthsLabels); ?>;
    const activeClientsLabels = <?php echo json_encode($daysLabels); ?>;
    const requestTypeDataNew = <?php echo json_encode($requestTypeDataNew); ?>;
    const requestTypeDataOld = <?php echo json_encode($requestTypeDataOld); ?>;
    const requestTypeLabels = <?php echo json_encode($requestTypeLabels); ?>;
    const deceasedPerFloorNew = <?php echo json_encode($deceasedPerFloorNew); ?>;
    const deceasedPerFloorNewAll = <?php echo json_encode($deceasedPerFloorNewAll); ?>;
    const deceasedPerFloorOld = <?php echo json_encode($deceasedPerFloorOld); ?>;
    const deceasedPerFloorOldAll = <?php echo json_encode($deceasedPerFloorOldAll); ?>;
    const deceasedFloorLabels = <?php echo json_encode($deceasedFloorLabels); ?>;
    const deceasedFloorLabelsOld = <?php echo json_encode($deceasedFloorLabelsOld); ?>;

    // Set filter state to 'all' by default for deceased per floor
    let deceasedFloorFilter = 'all';

    // Prepare request per month data for all years
    const requestsPerMonthNewAll = <?php
      $requestsPerMonthNewAll = [];
      for ($i = 4; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $res = $conn->query("SELECT COUNT(*) AS cnt FROM client_requests WHERE DATE_FORMAT(created_at, '%Y-%m') = '$month' AND (niche_id NOT LIKE 'OM%' OR niche_id IS NULL)");
        $requestsPerMonthNewAll[] = ($res && $row = $res->fetch_assoc()) ? intval($row['cnt']) : 0;
      }
      echo json_encode($requestsPerMonthNewAll);
    ?>;
    const requestsPerMonthOldAll = <?php
      $requestsPerMonthOldAll = [];
      for ($i = 4; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $res = $conn->query("SELECT COUNT(*) AS cnt FROM client_requests WHERE DATE_FORMAT(created_at, '%Y-%m') = '$month' AND niche_id LIKE 'OM%'");
        $requestsPerMonthOldAll[] = ($res && $row = $res->fetch_assoc()) ? intval($row['cnt']) : 0;
      }
      echo json_encode($requestsPerMonthOldAll);
    ?>;

    let requestsPerMonthFilter = 'year';

    // Chart rendering with skeleton clearing
    function renderCharts(isOldMap) {
      // SPLINE CHART
      var options = {
        chart: { type: 'area', height: 350, toolbar: { show: false } },
       
        series: [{
          name: 'Active Clients',
          data: isOldMap ? activeClientsDataOld : activeClientsDataNew
        }],
        xaxis: { categories: activeClientsLabels },
        stroke: { curve: 'smooth' },
        fill: {
          type: 'gradient',
          gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.5,
            opacityTo: 0.1,
            stops: [0, 90, 100]
          }
        },
        colors: ['#4F46E5'],
        dataLabels: { enabled: false },
        tooltip: { theme: 'light' },
        title: {
          text: isOldMap ? 'Active Clients (Old Map)' : 'Active Clients (Last 7 Days)',
          align: 'center',
          style: { fontSize: '16px', fontWeight: 'bold', color: '#374151' }
        }
      };
      document.querySelector("#chart").innerHTML = '';
      var chart = new ApexCharts(document.querySelector("#chart"), options);

      // COLUMN CHART
      let requestsData;
      if (isOldMap) {
        requestsData = (requestsPerMonthFilter === 'all') ? requestsPerMonthOldAll : requestsDataOld;
      } else {
        requestsData = (requestsPerMonthFilter === 'all') ? requestsPerMonthNewAll : requestsDataNew;
      }
      var columnOptions = {
        chart: { type: 'bar', height: '100%', toolbar: { show: false } },
        series: [{
          name: 'Requests',
          data: requestsData
        }],
        xaxis: { categories: requestsLabels },
        colors: ['#34D399'],
        plotOptions: { bar: { columnWidth: '40%', borderRadius: 4 } },
        dataLabels: { enabled: false },
        title: {
          text: isOldMap
            ? (requestsPerMonthFilter === 'all' ? 'Requests Per Month (Old Map, All Years)' : 'Requests Per Month (Old Map, Year)')
            : (requestsPerMonthFilter === 'all' ? 'Requests Per Month (All Years)' : 'Requests Per Month (Year)'),
          align: 'center',
          style: { fontSize: '16px', fontWeight: 'bold', color: '#374151' }

        }
      };
      document.querySelector("#columnChart").innerHTML = '';
      var columnChart = new ApexCharts(document.querySelector("#columnChart"), columnOptions);

      // PIE CHART
      var pieOptions = {
        chart: { type: 'pie', height: '100%', toolbar: { show: false } },
        series: isOldMap ? pieDataOld : pieDataNew,
        labels: ['Available', 'Occupied'],
        colors: ['#60A5FA', '#F87171'],
        legend: { position: 'bottom' },
        title: {
          text: isOldMap ? 'Niche Status Distribution (Old Map)' : 'Niche Status Distribution',
          align: 'center',
          style: { fontSize: '16px', fontWeight: 'bold', color: '#374151' }
        }
      };
      document.querySelector("#pieChart").innerHTML = '';
      var pieChart = new ApexCharts(document.querySelector("#pieChart"), pieOptions);

      // DONUT CHART
      var donutOptions = {
        chart: { type: 'donut', height: '100%', toolbar: { show: false } },
        series: isOldMap ? requestTypeDataOld : requestTypeDataNew,
        labels: requestTypeLabels,
        colors: ['#6366F1', '#F59E42', '#10B981'],
        legend: { position: 'bottom' },
        title: {
          text: isOldMap ? 'Request Type Distribution (Old Map)' : 'Request Type Distribution',
          align: 'center',
          style: { fontSize: '16px', fontWeight: 'bold', color: '#374151' }
        }
      };
      document.querySelector("#donutChart").innerHTML = '';
      var donutChart = new ApexCharts(document.querySelector("#donutChart"), donutOptions);

      // BAR CHART (Deceased per Floor)
      let deceasedData, deceasedLabels;
      if (isOldMap) {
        deceasedData = (deceasedFloorFilter === 'all') ? deceasedPerFloorOldAll : deceasedPerFloorOld;
        deceasedLabels = deceasedFloorLabelsOld;
      } else {
        deceasedData = (deceasedFloorFilter === 'all') ? deceasedPerFloorNewAll : deceasedPerFloorNew;
        deceasedLabels = deceasedFloorLabels;
      }
      // Use distributed bars so each floor gets its own distinct color
      var floorBarOptions = {
        chart: { type: 'bar', height: '100%', toolbar: { show: false } },
        series: [{
          name: 'Deceased',
          data: deceasedData
        }],
        xaxis: { categories: deceasedLabels },
        // Provide distinct palettes for new-map (3 floors) and old-map (2 floors)
        colors: isOldMap
          ? ['#2563EB', '#EF4444']                 // Old map: blue, red (2 floors)
          : ['#F97316', '#2563EB', '#10B981'],     // New map: orange, blue, green (3 floors)
        plotOptions: { bar: { columnWidth: '40%', borderRadius: 4, distributed: true } },
        dataLabels: { enabled: true },
        title: {
          text: isOldMap
            ? (deceasedFloorFilter === 'all' ? 'Deceased Per Floor (Old Map, All Years)' : 'Deceased Per Floor (Old Map, Year)')
            : (deceasedFloorFilter === 'all' ? 'Deceased Per Floor (All Years)' : 'Deceased Per Floor (Year)'),
          align: 'center',
          style: { fontSize: '16px', fontWeight: 'bold', color: '#374151' }
        }
      };
      document.querySelector("#floorBarChart").innerHTML = '';
      var floorBarChart = new ApexCharts(document.querySelector("#floorBarChart"), floorBarOptions);

      // Render and then clear skeletons
      Promise.all([
        chart.render(),
        columnChart.render(),
        pieChart.render(),
        donutChart.render(),
        floorBarChart.render()
      ]).then(function(){
        ['#chart','#columnChart','#pieChart','#donutChart','#floorBarChart'].forEach(function(sel){
          var el = document.querySelector(sel);
          if (el) el.classList.remove('skeleton');
        });
      });
    }

    // Initial chart render (new map)
    renderCharts(false);

    // Flip card logic for available and occupied + charts
    const nicheCardFront = document.getElementById('nicheCardFront');
    const nicheCardBack = document.getElementById('nicheCardBack');
    const occupiedCardFront = document.getElementById('occupiedCardFront');
    const occupiedCardBack = document.getElementById('occupiedCardBack');
    let flipped = false;
    document.getElementById('nicheCardFlip').onclick = function() {
      flipped = !flipped;
      nicheCardFront.style.display = flipped ? 'none' : '';
      nicheCardBack.style.display = flipped ? '' : 'none';
      occupiedCardFront.style.display = flipped ? 'none' : '';
      occupiedCardBack.style.display = flipped ? '' : 'none';
      renderCharts(flipped);
    };

    // Add event listener for deceased per floor filter
    document.getElementById('deceasedFloorFilter').addEventListener('change', function() {
      deceasedFloorFilter = this.value;
      renderCharts(flipped);
    });

    // Add event listener for requests per month filter
    document.getElementById('requestsPerMonthFilter').addEventListener('change', function() {
      requestsPerMonthFilter = this.value;
      renderCharts(flipped);
    });

    // --- TOAST FUNCTION ---
    function showToast(msg, type) {
      const container = document.getElementById('toastContainer');
      if (!container) return;
      const toast = document.createElement('div');
      toast.textContent = msg;
      toast.style.cssText = `
        min-width:220px;
        max-width:340px;
        background:${type==='success' ? '#10b981':'#ef4444'};
        color:#fff;
        font-weight:600;
        padding:12px 18px;
        border-radius:8px;
        box-shadow:0 4px 16px rgba(0,0,0,0.13);
        font-size:1rem;
        margin-bottom:2px;
        opacity:1;
        pointer-events:auto;
        cursor:pointer;
        transition:opacity 0.3s;
        position:relative;
        z-index:100000;
      `;
      // Animate in
      toast.style.transform = 'translateY(-20px)';
      toast.style.opacity = '0';
      setTimeout(() => {
        toast.style.transform = 'translateY(0)';
        toast.style.opacity = '1';
      }, 10);

      toast.onclick = () => { 
        toast.style.opacity = 0; 
        setTimeout(()=>toast.remove(),200); 
      };
      container.appendChild(toast);
      setTimeout(()=>{
        toast.style.opacity = 0;
        setTimeout(()=>toast.remove(),300);
      }, 3200);
    }

    // New: modal flow for notify (opens modal, posts contact info)
    document.addEventListener('DOMContentLoaded', function() {
      const overlay = document.getElementById('notifyModalOverlay');
      if (!overlay) return;
     
      const recordInfo = document.getElementById('notifyRecordInfo');
      const contactTypeEl = document.getElementById('contactType');
      const contactValueEl = document.getElementById('contactValue');
      const sendBtn = document.getElementById('notifySendBtn');
      const cancelBtn = document.getElementById('notifyCancelBtn');
      const errorEl = document.getElementById('notifyModalError');

      let currentPayload = null;

      // keep input title in sync so long values are visible on hover
      contactValueEl.addEventListener('input', function(){ contactValueEl.title = contactValueEl.value; });

      // Delegate click on notify buttons
      document.addEventListener('click', function(e){
        const btn = e.target.closest('.notify-btn');
        if (!btn) return;
        e.preventDefault();
        if (btn.disabled) return;

        const clientEmail = btn.getAttribute('data-client-email') || '';
        const clientId = btn.getAttribute('data-client-id') || '';

        currentPayload = {
          niche: btn.getAttribute('data-niche') || '',
          name: btn.getAttribute('data-name') || '',
          validity: btn.getAttribute('data-validity') || '',
          client_email: clientEmail,
          client_id: clientId,
          informant: btn.getAttribute('data-informant') || '',
          btnElement: btn
        };

        // Display detailed record info in the modal including Informant name (only in popup)
        // escape helper to avoid injecting HTML
        function escapeHtml(str) {
          if (!str && str !== 0) return '';
          return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
        }

        const escName = escapeHtml(currentPayload.name);
        const escApt = escapeHtml(currentPayload.niche);
        const escValidity = escapeHtml(currentPayload.validity);
        const escInformant = escapeHtml(currentPayload.informant);

        // Render as multiple lines for better readability
        recordInfo.innerHTML = `
          <div style="font-weight:700; margin-bottom:6px;">${escName}</div>
          <div style="color:#2563eb; margin-bottom:4px;">Apt: ${escApt}</div>
          <div style="color:#eab308; margin-bottom:4px;">Validity: ${escValidity}</div>
          ${escInformant ? `<div style="color:#6b7280; font-size:0.95rem;">Informant: ${escInformant}</div>` : ''}
        `;

        if (currentPayload.client_id && currentPayload.client_email) {
          contactTypeEl.value = 'internal';
          contactValueEl.value = currentPayload.client_email;
          contactValueEl.readOnly = true;
          contactValueEl.title = contactValueEl.value;
        } else {
          contactTypeEl.value = 'email';
          contactValueEl.value = '';
          contactValueEl.readOnly = false;
          contactValueEl.title = '';
        }
         errorEl.style.display = 'none';
         overlay.style.display = 'flex';
         contactValueEl.focus();
      });

      cancelBtn.addEventListener('click', function(){ overlay.style.display = 'none'; currentPayload = null; errorEl.style.display = 'none'; });

      sendBtn.addEventListener('click', function(){
        if (!currentPayload) return;
        const contact_type = contactTypeEl.value;
        const contact_value = contactValueEl.value.trim();
        if (!contact_value) { errorEl.textContent = 'Please enter contact email.'; errorEl.style.display = ''; contactValueEl.focus(); return; }

        sendBtn.disabled = true;
        sendBtn.textContent = 'Sending...';
        errorEl.style.display = 'none';

        const params = new URLSearchParams();
        params.append('notify_niche', currentPayload.niche);
        params.append('notify_name', currentPayload.name);
        params.append('notify_validity', currentPayload.validity);
        params.append('contact_type', contact_type);
        params.append('contact_value', contact_value);

        fetch(window.location.pathname + window.location.search, {
          method: 'POST',
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          body: params
        })
        .then(r => r.json())
        .then ( data => {
          if (data && data.success) {
            if (currentPayload.btnElement) {
              currentPayload.btnElement.textContent = 'Notified';
              currentPayload.btnElement.disabled = true;
              const statusEl = currentPayload.btnElement.parentElement.querySelector('.notify-status');
              if (statusEl) { statusEl.style.display = ''; statusEl.textContent = (data.status === 'sent') ? 'Sent' : 'Queued'; }
            }
            overlay.style.display = 'none';
            showToast('Expiry notice sent successfully!', 'success');
            currentPayload = null;
          } else {
            if (data && data.message) {
              errorEl.textContent = data.message;
              errorEl.style.display = '';
            } else if (data && data.error) {
              errorEl.textContent = data.error;
              errorEl.style.display = '';
            } else {
              errorEl.textContent = 'Failed to send notification. Please try again.';
              errorEl.style.display = '';
            }
            showToast('Failed to send expiry notice.', 'error');
          }
        })
        .catch(err => {
          console.error(err);
          errorEl.textContent = 'An error occurred while sending notification. Check server logs.';
          errorEl.style.display = '';
          showToast('Failed to send expiry notice.', 'error');
        })
        .finally(()=> {
          sendBtn.disabled = false;
          sendBtn.textContent = 'Send';
        });
      });

      // Close modal with Escape
      document.addEventListener('keydown', function(e){ if (e.key === 'Escape') { overlay.style.display='none'; currentPayload=null; errorEl.style.display = 'none'; } });
    });
  </script>

  <!-- notify modal (inserted once) -->
  <div id="notifyModalOverlay" class="notify-modal-overlay" aria-hidden="true" style="display:none;">
    <div class="notify-modal" role="dialog" aria-modal="true" aria-labelledby="notifyModalTitle">
      <div class="modal-header">
        <div>
          <h3 id="notifyModalTitle">Send Expiry Notice!</h3>
          <div style="font-size:0.86rem;color:#64748b;margin-top:2px;">Notify via email or in-app</div>
        </div>
        <button class="notify-close" title="Close" onclick="document.getElementById('notifyCancelBtn').click()">✕</button>
      </div>

    <div class="notify-field">
      <label>Record</label>
      <div id="notifyRecordInfo" class="record-box" style="font-weight:600;color:#0f172a;"></div>
    </div>

      <div class="notify-field">
        <label for="contactType">Contact Type</label>
        <select id="contactType">
          <option value="internal">Registered Client (In-app)</option>
          <option value="email">Email</option>
        </select>
      </div>

      <div class="notify-field">
        <label for="contactValue">Contact (email)</label>
        <input id="contactValue" type="text" placeholder="Enter email address">
      </div>

      <div id="notifyModalError" role="alert" aria-live="assertive" style="display:none;"></div>

      <div style="font-size:0.9rem;color:#6b7280;margin-top:6px;">Note: Email will be sent through the configured messaging gateway. Ensure contact details are valid.</div>

      <div class="notify-actions">
        <button id="notifyCancelBtn" class="btn-secondary">Cancel</button>
        <button id="notifySendBtn" class="btn-primary">Send</button>
      </div>
    </div>
  </div>

  <script>
(function(){
  const expInput = document.getElementById('expSearch');
  const floorSelect = document.getElementById('expFloorFilter');
  const statusSelect = document.getElementById('expStatusFilter'); // ADDED
  const listItemsSelector = 'li[data-exp-item]';
  const firstLi = document.querySelector(listItemsSelector);
  // stable list container (keeps padding/box model consistent)
  const listContainer = document.getElementById('expListContainer');
  const ul = firstLi ? firstLi.closest('ul') : null;
  let noResultsEl = null;

  function ensureNoResultsEl(parent){
    if (!noResultsEl){
      noResultsEl = document.createElement('div');
      noResultsEl.id = 'expNoResults';
      noResultsEl.style.color = '#888';
      noResultsEl.style.fontSize = '0.97rem';
      noResultsEl.style.textAlign = 'center';
      // smaller top margin to match list spacing and ensure it is placed INSIDE the scrolling area
      noResultsEl.style.margin = '12px 0';
      noResultsEl.textContent = 'No matching records.';
      if (listContainer) listContainer.appendChild(noResultsEl);
      else if (parent) parent.parentNode.appendChild(noResultsEl);
      else document.body.appendChild(noResultsEl);
    }
  }

  // q = text query, floor = 'all'|'1F'|'2F'|'3F'|'OM', status = 'all'|'expired'|'upcoming'
  function filterExpiringRecords(q, floor, status){
    const query = String(q || '').trim().toLowerCase();
    const floorFilter = (floor || 'all').toUpperCase();
    const statusFilter = (status || 'all').toLowerCase();

    const items = Array.from(document.querySelectorAll(listItemsSelector));
    if (!items.length){
      ensureNoResultsEl(ul);
      if (noResultsEl) noResultsEl.style.display = '';
      return;
    }
    let anyVisible = false;
    // Today's date in YYYY-MM-DD format for comparison
    const todayStr = (new Date()).toISOString().slice(0,10);

    items.forEach(function(li){
      const name = (li.getAttribute('data-name') || '').toLowerCase();
      const apt = (li.getAttribute('data-apt') || '').toLowerCase();
      const validity = (li.getAttribute('data-validity') || '').toLowerCase();
      const matchesText = !query || name.indexOf(query) !== -1 || apt.indexOf(query) !== -1 || validity.indexOf(query) !== -1;
      let matchesFloor = true;
      if (floorFilter && floorFilter !== 'ALL') {
        if (floorFilter === 'OM') {
          matchesFloor = apt.indexOf('om') === 0 || apt.indexOf('om-') === 0;
        } else {
          // match floor prefix like "1f-" or "1f" at start
          matchesFloor = apt.indexOf(floorFilter.toLowerCase() + '-') === 0 || apt.indexOf(floorFilter.toLowerCase()) === 0;
        }
      }
      // Determine status of this record by comparing validity against today
      let matchesStatus = true;
      if (statusFilter && statusFilter !== 'all') {
        // validity is in 'yyyy-mm-dd' — compare lexicographically
        if (statusFilter === 'expired') {
          matchesStatus = validity < todayStr;
        } else if (statusFilter === 'upcoming') {
          matchesStatus = validity >= todayStr;
        }
      }

      const matches = matchesText && matchesFloor;
      // use class toggle to avoid interfering with other inline styles
      if (matches && matchesStatus) {
        li.classList.remove('exp-hidden');
        li.style.display = '';
      } else {
        li.classList.add('exp-hidden');
        li.style.display = 'none';
      }
      if (matches) anyVisible = true;
    });
    if (!anyVisible){
      ensureNoResultsEl(ul);
      if (noResultsEl) noResultsEl.style.display = '';
    } else if (noResultsEl){
      noResultsEl.style.display = 'none';
    }
  }

  if (expInput){
    expInput.addEventListener('input', function(){ filterExpiringRecords(this.value, floorSelect ? floorSelect.value : 'all', statusSelect ? statusSelect.value : 'all'); });
    expInput.addEventListener('keydown', function(e){
      if (e.key === 'Enter'){
        e.preventDefault();
        filterExpiringRecords(this.value, floorSelect ? floorSelect.value : 'all', statusSelect ? statusSelect.value : 'all');
      }
      // Ctrl/Cmd+E export shortcut
      if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'e'){
        e.preventDefault();
        exportVisibleExpiringRecords();
      }
    });
  }

  // Filter when the floor dropdown changes (no separate button needed)
  if (floorSelect) {
    floorSelect.addEventListener('change', function() {
      filterExpiringRecords(expInput ? expInput.value : '', floorSelect.value, statusSelect ? statusSelect.value : 'all');
    });
  }

  // Filter when the status dropdown changes
  if (statusSelect) {
    statusSelect.addEventListener('change', function() {
      filterExpiringRecords(expInput ? expInput.value : '', floorSelect ? floorSelect.value : 'all', this.value);
    });
  }

  // Export visible items to CSV (used by shortcut Ctrl/Cmd+E)
  function exportVisibleExpiringRecords(){
    const visible = Array.from(document.querySelectorAll(listItemsSelector)).filter(li => li.style.display !== 'none');
    if (!visible.length){
      alert('No records to export.');
      return;
    }
    const rows = [['Name','Apt','Validity','Informant','Status']];
    visible.forEach(li => {
      const name = li.getAttribute('data-name') || '';
      const apt = li.getAttribute('data-apt') || '';
      const validity = li.getAttribute('data-validity') || '';
      const notifyBtn = li.querySelector('.notify-btn');
      const informant = notifyBtn ? (notifyBtn.getAttribute('data-informant') || '') : '';
      const statusEl = li.querySelector('.notify-status');
      const status = statusEl ? statusEl.textContent.trim() : '';
      rows.push([name, apt, validity, informant, status]);
    });
    const csv = rows.map(r => r.map(cell => `"${String(cell).replace(/"/g,'""')}"`).join(',')).join('\r\n');
    const blob = new Blob([csv], {type:'text/csv;charset=utf-8;'});
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'expiring_records_' + (new Date()).toISOString().slice(0,10) + '.csv';
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
  }

  // If there is an older "expExport" button (from prior versions), wire it up too.
  const expExportBtn = document.getElementById('expExport');
  if (expExportBtn){
    expExportBtn.addEventListener('click', function(e){ e.preventDefault(); exportVisibleExpiringRecords(); });
  }
})();
  </script>
</body>
</html>