<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    // Redirect to login page if not logged in
    header("Location: ../login.php"); // Adjust the path if needed
    exit;
}

include_once '../Includes/db.php';

// --- REPLACED: handle AJAX notify requests (now requires contact_value) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['notify_niche'])) {
    $niche = trim($_POST['notify_niche']);
    $name = isset($_POST['notify_name']) ? trim($_POST['notify_name']) : '';
    $validity = isset($_POST['notify_validity']) ? trim($_POST['notify_validity']) : '';
    $contact_type = isset($_POST['contact_type']) ? trim($_POST['contact_type']) : ''; // 'email' or 'phone' or 'internal'
    $contact_value = isset($_POST['contact_value']) ? trim($_POST['contact_value']) : '';

    // server-side validation: contact_value is required
    if ($contact_value === '') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'missing_contact', 'message' => 'Contact (email or phone) is required.']);
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

    $message = "Validity expiry notice for {$name} (Apt: {$niche}) on {$validity}";

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
        } else {
            // If internal requested but user not found, keep queued
            $status = 'queued';
        }
    } elseif ($contact_type === 'email' && filter_var($contact_value, FILTER_VALIDATE_EMAIL)) {
        // Attempt to send email using mail() — replace with real SMTP if available
        $to = $contact_value;
        $subject = "RestEase: Validity Expiry Notice";
        $body = "Hello,\n\nThis is a notice that the validity for {$name} (Apt: {$niche}) is on {$validity}.\n\nRegards,\nRestEase Admin";
        $headers = "From: noreply@restease.local\r\nReply-To: noreply@restease.local";

        // suppress warnings from mail() but capture boolean result
        $mailResult = @mail($to, $subject, $body, $headers);
        if ($mailResult) {
            $status = 'sent';
            $sent = true;
        } else {
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

// Active clients
$result = $conn->query("SELECT COUNT(*) AS cnt FROM users");
$activeClients = ($result && $row = $result->fetch_assoc()) ? intval($row['cnt']) : 0;

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

// Get records whose validity is closest to today (future dates only)
$expiringRecords = [];
$today = date('Y-m-d');
$sql = "SELECT id, nicheID, lastName, firstName, middleName, suffix, dateInternment, informantName FROM deceased WHERE dateInternment IS NOT NULL AND dateInternment != '' AND dateInternment != '0000-00-00'";
$res = $conn->query($sql);
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $internmentDate = $row['dateInternment'];
        try {
            $validityDate = (new DateTime($internmentDate))->modify('+5 years')->format('Y-m-d');
            if ($validityDate >= $today) {
                $name = $row['lastName'] . ', ' . $row['firstName'];
                if (!empty($row['middleName'])) $name .= ' ' . strtoupper(substr(trim($row['middleName']), 0, 1)) . '.';
                if (!empty($row['suffix'])) $name .= ' ' . $row['suffix'];

                // Try to find a registered user who matches the informant name (best-effort)
                $clientEmail = null;
                $clientId = null;
                if (!empty($row['informantName'])) {
                    $informantTrim = trim($row['informantName']);
                    // Attempt exact match to "First Last" or "Last, First" patterns
                    // Try "First Last"
                    $stmtUser = $conn->prepare("SELECT id, email FROM users WHERE CONCAT(first_name, ' ', last_name) = ? LIMIT 1");
                    if ($stmtUser) {
                        $stmtUser->bind_param('s', $informantTrim);
                        $stmtUser->execute();
                        $resU = $stmtUser->get_result();
                        if ($ru = $resU->fetch_assoc()) {
                            $clientId = intval($ru['id']);
                            $clientEmail = $ru['email'];
                        }
                        $stmtUser->close();
                    }
                    // If not found, try "Last, First" pattern (stored informant sometimes formatted that way)
                    if (!$clientId && strpos($informantTrim, ',') !== false) {
                        $stmtUser2 = $conn->prepare("SELECT id, email FROM users WHERE CONCAT(last_name, ', ', first_name) = ? LIMIT 1");
                        if ($stmtUser2) {
                            $stmtUser2->bind_param('s', $informantTrim);
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

                $expiringRecords[] = [
                    'nicheID' => $row['nicheID'],
                    'name' => $name,
                    'validity' => $validityDate,
                    'client_id' => $clientId,
                    'client_email' => $clientEmail
                ];
            }
        } catch (Exception $e) {}
    }
    // Sort by closest validity date
    usort($expiringRecords, function($a, $b) {
        return strcmp($a['validity'], $b['validity']);
    });
    // Limit to top 10 closest
    $expiringRecords = array_slice($expiringRecords, 0, 10);

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

// --- Prepare chart data for both maps ---

// Pie chart data
$pieDataNew = [$newMapAvailable, $newMapOccupied];
$pieDataOld = [$oldMapAvailable, $oldMapOccupied];

// Area chart: Active Clients per day (last 7 days, filtered by year)
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
    // New map: users created on this date
    $res = $conn->query("SELECT COUNT(*) AS cnt FROM users WHERE DATE(created_at) = '$date'");
    $activeClientsPerDayNew[] = ($res && $row = $res->fetch_assoc()) ? intval($row['cnt']) : 0;
    // Old map: users created on this date (same logic, or adjust if needed)
    $activeClientsPerDayOld[] = $activeClientsPerDayNew[count($activeClientsPerDayNew)-1];
}

// Column chart: Requests per month (last 5 months, filtered by year)
$requestsPerMonthNew = [];
$requestsPerMonthOld = [];
$monthsLabels = [];
for ($i = 4; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $monthsLabels[] = date('M', strtotime($month));
    $year = date('Y', strtotime($month));
    if ($year != $currentYear) {
        $requestsPerMonthNew[] = 0;
        $requestsPerMonthOld[] = 0;
        continue;
    }
    // New map: requests for new map (exclude OM)
    $res = $conn->query("SELECT COUNT(*) AS cnt FROM client_requests WHERE DATE_FORMAT(created_at, '%Y-%m') = '$month' AND (niche_id NOT LIKE 'OM%' OR niche_id IS NULL)");
    $requestsPerMonthNew[] = ($res && $row = $res->fetch_assoc()) ? intval($row['cnt']) : 0;
    // Old map: requests for old map (niche_id LIKE 'OM%')
    $res = $conn->query("SELECT COUNT(*) AS cnt FROM client_requests WHERE DATE_FORMAT(created_at, '%Y-%m') = '$month' AND niche_id LIKE 'OM%'");
    $requestsPerMonthOld[] = ($res && $row = $res->fetch_assoc()) ? intval($row['cnt']) : 0;
}

// Donut chart: Request Type Distribution (filtered by year)
$requestTypeCountsNew = ['New' => 0, 'Relocate' => 0, 'Transfer' => 0];
$res = $conn->query("SELECT type, COUNT(*) AS cnt FROM client_requests WHERE YEAR(created_at) = $currentYear AND (niche_id NOT LIKE 'OM%' OR niche_id IS NULL) GROUP BY type");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        if (isset($requestTypeCountsNew[$row['type']])) {
            $requestTypeCountsNew[$row['type']] = intval($row['cnt']);
        }
    }
}
$requestTypeDataNew = array_values($requestTypeCountsNew);

$requestTypeCountsOld = ['New' => 0, 'Relocate' => 0, 'Transfer' => 0];
$res = $conn->query("SELECT type, COUNT(*) AS cnt FROM client_requests WHERE YEAR(created_at) = $currentYear AND niche_id LIKE 'OM%' GROUP BY type");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        if (isset($requestTypeCountsOld[$row['type']])) {
            $requestTypeCountsOld[$row['type']] = intval($row['cnt']);
        }
    }
}
$requestTypeDataOld = array_values($requestTypeCountsOld);
$requestTypeLabels = array_keys($requestTypeCountsNew);

// --- Bar chart: Deceased per floor (filtered by year and all years) ---
$floors = ['1F', '2F', '3F'];
$deceasedPerFloorNew = [];
$deceasedPerFloorNewAll = [];
$deceasedPerFloorOld = [];
$deceasedPerFloorOldAll = [];

// New map: exclude OM for all floors
foreach ($floors as $floor) {
    // Year filtered
    $res = $conn->query("SELECT COUNT(*) AS cnt FROM deceased WHERE nicheID LIKE '{$floor}-%' AND YEAR(dateInternment) = $currentYear AND nicheID NOT LIKE 'OM%'");
    $deceasedPerFloorNew[] = ($res && $row = $res->fetch_assoc()) ? intval($row['cnt']) : 0;
    // All years
    $resAll = $conn->query("SELECT COUNT(*) AS cnt FROM deceased WHERE nicheID LIKE '{$floor}-%' AND nicheID NOT LIKE 'OM%'");
    $deceasedPerFloorNewAll[] = ($resAll && $row = $resAll->fetch_assoc()) ? intval($row['cnt']) : 0;
}

// Old map: only OM-1F and OM-2F
$oldMapFloors = ['OM-1F', 'OM-2F'];
foreach ($oldMapFloors as $floor) {
    // Year filtered
    $res = $conn->query("SELECT COUNT(*) AS cnt FROM deceased WHERE nicheID LIKE '{$floor}-%' AND YEAR(dateInternment) = $currentYear");
    $deceasedPerFloorOld[] = ($res && $row = $res->fetch_assoc()) ? intval($row['cnt']) : 0;
    // All years
    $resAll = $conn->query("SELECT COUNT(*) AS cnt FROM deceased WHERE nicheID LIKE '{$floor}-%'");
    $deceasedPerFloorOldAll[] = ($resAll && $row = $resAll->fetch_assoc()) ? intval($row['cnt']) : 0;
}
$deceasedFloorLabels = $floors;
$deceasedFloorLabelsOld = ['1F', '2F'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>RestEase Admin Dashboard</title>
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
    #contactValue[readonly] {
      background: #f8fafc;
      cursor: default;
      color: #0f172a;
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

    @media (max-width:520px){
      .notify-modal { width: 100%; padding: 12px; border-radius: 8px; }
      .notify-modal .modal-header { gap:8px; }
    }
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
          </div>
        </div>
      </div>
      <div class="user-profile">
        <div class="profile-info">
            <!-- notification bell placed to the left of the avatar; inline styles keep layout/size unchanged -->
      <button class="notif-bell" aria-label="Notifications" title="Notifications"
        onclick="window.location.href='/RestEase/AdminSide/Notifications.php';"
        style="background:transparent;border:none;padding:0;margin-right:8px;cursor:pointer;color:inherit;">
        <i class="fa-solid fa-bell" style="font-size:1.05rem;color:inherit;"></i>
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
          </div>
          <div id="nicheCardBack" style="display:none;">
            <div class="stat-title">Available Niches (old map)</div>
            <div class="stat-value"><?php echo $oldMapAvailable; ?> Available Niches</div>
          </div>
        </div>
        <div class="stat-card" style="position: relative;">
          <div id="occupiedCardFront">
            <div class="stat-title">Occupied Niches (new map)</div>
            <div class="stat-value"><?php echo $newMapOccupied; ?> Niches Occupied</div>
          </div>
          <div id="occupiedCardBack" style="display:none;">
            <div class="stat-title">Occupied Niches (old map)</div>
            <div class="stat-value"><?php echo $oldMapOccupied; ?> Niches Occupied</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-title">Pending Request</div>
          <div class="stat-value"><?php echo $pendingRequest; ?> Pending Request</div>
        </div>
        <div class="stat-card">
          <div class="stat-title">Total Clients Registered</div>
          <div class="stat-value"><?php echo $activeClients; ?> Total Clients</div>
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
      <div class="dashboard-card dashboard-card-large">
        <div id="chart"></div>
      </div>
      <div class="dashboard-card dashboard-card-small">
        <div style="padding: 18px 14px 18px 18px; width: 100%; height: 100%; display: flex; flex-direction: column;">
          <!-- Move title and content upward, add more space below -->
          <h3 style="font-size: 1.13rem; margin-bottom: 10px; color: #374151; font-weight: 700; letter-spacing: 0.5px; padding-left: 55px; margin-top: 2px;">Upcoming Validity Expiry</h3>
          <div style="flex: 1; overflow-y: auto; max-height: 320px; margin-top: 0;">
            <?php if (count($expiringRecords) > 0): ?>
              <ul style="list-style: none; padding: 0; margin: 0;">
                <?php foreach ($expiringRecords as $rec): ?>
                  <li style="margin-bottom: 16px; display: flex; align-items: flex-start; gap: 12px;">
                    <div style="margin-top: 2px;">
                      <i class="fa-solid fa-calendar-exclamation" style="color: #eab308; font-size: 1.25rem;"></i>
                    </div>
                    <!-- modified: make content a flex row with space-between so button sits at the right -->
                    <div style="background: #f8fafc; border-radius: 10px; box-shadow: 0 1px 4px rgba(0,0,0,0.04); padding: 10px 14px; min-width: 0; flex: 1; display: flex; align-items: center; justify-content: space-between;">
                      <div style="flex: 1; min-width: 0;">
                        <div style="font-weight: 600; color: #1e293b; font-size: 1rem; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                          <?php echo htmlspecialchars($rec['name']); ?>
                        </div>
                        <div style="font-size: 0.93rem; color: #2563eb; font-weight: 500; margin-bottom: 2px;">Apt: <?php echo htmlspecialchars($rec['nicheID']); ?></div>
                        <div style="font-size: 0.93rem; color: #eab308; font-weight: 500;">Validity: <?php echo htmlspecialchars($rec['validity']); ?></div>
                      </div>

                      <!-- right-aligned column for button + status -->
                      <div style="margin-left: 16px; display:flex; flex-direction:column; align-items:flex-end; justify-content:center;">
                        <?php
                        // Determine if this expiring item was already notified
                        $key = $rec['nicheID'] . '|' . $rec['validity'];
                        $alreadyStatus = isset($notifiedMap[$key]) ? $notifiedMap[$key] : null;
                        ?>
                        <!-- Update button markup to reflect persisted status -->
                        <button
                          class="notify-btn"
                          data-niche="<?php echo htmlspecialchars($rec['nicheID']); ?>"
                          data-name="<?php echo htmlspecialchars($rec['name']); ?>"
                          data-validity="<?php echo htmlspecialchars($rec['validity']); ?>"
                          data-client-email="<?php echo htmlspecialchars($rec['client_email']); ?>"
                          data-client-id="<?php echo htmlspecialchars($rec['client_id']); ?>"
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
              <div style="color: #888; font-size: 0.97rem; text-align: center; margin-top: 40px;">No records expiring soon.</div>
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
        <div id="floorBarChart" style="width: 100%; height: 100%;"></div>
      </div>
      <div class="dashboard-card" style="height: 180px; padding: 0; align-items: stretch; justify-content: stretch;">
        <div id="pieChart" style="width: 100%; height: 100%;"></div>
      </div>
    </section>
    <!-- New chart for request type distribution and deceased per floor -->
    <section class="dashboard-grid" style="grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 24px;">
      <div class="dashboard-card" style="height: 180px; padding: 0; align-items: stretch; justify-content: stretch;">
        <div id="donutChart" style="width: 100%; height: 100%;"></div>
      </div>
      <div class="dashboard-card" style="height: 180px; padding: 0; align-items: stretch; justify-content: stretch; position:relative;">
        <!-- Place request per month filter inside the request per month card -->
        <div style="position: absolute; top: 18px; right: 32px; z-index: 2;">
          <select id="requestsPerMonthFilter" style="padding:3px 8px;border-radius:6px;border:1px solid #d1d5db;font-size:0.97rem;">
          <option value="all">All Years</option>
          <option value="year" selected><?php echo $currentYear; ?></option>

          </select>
        </div>
        <div id="columnChart" style="width: 100%; height: 100%;"></div>
      </div>
    </section>
  </main>
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

    // Chart rendering function
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
      chart.render();

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
      columnChart.render();

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
      pieChart.render();

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
      donutChart.render();

      // BAR CHART (Deceased per Floor)
      let deceasedData, deceasedLabels;
      if (isOldMap) {
        deceasedData = (deceasedFloorFilter === 'all') ? deceasedPerFloorOldAll : deceasedPerFloorOld;
        deceasedLabels = deceasedFloorLabelsOld;
      } else {
        deceasedData = (deceasedFloorFilter === 'all') ? deceasedPerFloorNewAll : deceasedPerFloorNew;
        deceasedLabels = deceasedFloorLabels;
      }
      var floorBarOptions = {
        chart: { type: 'bar', height: '100%', toolbar: { show: false } },
        series: [{
          name: 'Deceased',
          data: deceasedData
        }],
        xaxis: { categories: deceasedLabels },
        colors: ['#F59E42'],
        plotOptions: { bar: { columnWidth: '40%', borderRadius: 4 } },
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
      floorBarChart.render();
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
          btnElement: btn
        };

        recordInfo.textContent = `${currentPayload.name} — Apt: ${currentPayload.niche} — Validity: ${currentPayload.validity}`;
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
        if (!contact_value) { errorEl.textContent = 'Please enter contact email or phone.'; errorEl.style.display = ''; contactValueEl.focus(); return; }

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
        .then(data => {
          if (data && data.success) {
            if (currentPayload.btnElement) {
              currentPayload.btnElement.textContent = 'Notified';
              currentPayload.btnElement.disabled = true;
              const statusEl = currentPayload.btnElement.parentElement.querySelector('.notify-status');
              if (statusEl) { statusEl.style.display = ''; statusEl.textContent = (data.status === 'sent') ? 'Sent' : 'Queued'; }
            }
            overlay.style.display = 'none';
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
          }
        })
        .catch(err => {
          console.error(err);
          errorEl.textContent = 'An error occurred while sending notification. Check server logs.';
          errorEl.style.display = '';
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
          <h3 id="notifyModalTitle">Send Expiry Notice</h3>
          <div style="font-size:0.86rem;color:#64748b;margin-top:2px;">notify via email, SMS, or in-app</div>
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
          <option value="phone">Phone / SMS</option>
        </select>
      </div>

      <div class="notify-field">
        <label for="contactValue">Contact (email or phone)</label>
        <input id="contactValue" type="text" placeholder="Enter email address or phone number">
      </div>

      <div id="notifyModalError" role="alert" aria-live="assertive" style="display:none;"></div>

      <div style="font-size:0.9rem;color:#6b7280;margin-top:6px;">Note: Email will be attempted via PHP mail(). SMS is recorded/queued (no gateway configured).</div>

      <div class="notify-actions">
        <button id="notifyCancelBtn" class="btn-secondary">Cancel</button>
        <button id="notifySendBtn" class="btn-primary">Send</button>
      </div>
    </div>
  </div>
  </body>
  </html>
