<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    // Redirect to login page if not logged in
    header("Location: ../AdminLogin.php"); // Adjust the path if needed
    exit;
}

include_once '../Includes/db.php';

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
$sql = "SELECT nicheID, lastName, firstName, middleName, suffix, dateInternment FROM deceased WHERE dateInternment IS NOT NULL AND dateInternment != '' AND dateInternment != '0000-00-00'";
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
                $expiringRecords[] = [
                    'nicheID' => $row['nicheID'],
                    'name' => $name,
                    'validity' => $validityDate
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

// Bar chart: Deceased per floor (filtered by year)
$floors = ['1F', '2F', '3F'];
$deceasedPerFloorNew = [];
$deceasedPerFloorOld = [];

// New map: exclude OM for all floors
foreach ($floors as $floor) {
    $res = $conn->query("SELECT COUNT(*) AS cnt FROM deceased WHERE nicheID LIKE '{$floor}-%' AND YEAR(dateInternment) = $currentYear AND nicheID NOT LIKE 'OM%'");
    $deceasedPerFloorNew[] = ($res && $row = $res->fetch_assoc()) ? intval($row['cnt']) : 0;
}

// Old map: only OM-1F and OM-2F
$deceasedPerFloorOld = [];
$oldMapFloors = ['OM-1F', 'OM-2F'];
foreach ($oldMapFloors as $floor) {
    $res = $conn->query("SELECT COUNT(*) AS cnt FROM deceased WHERE nicheID LIKE '{$floor}-%' AND YEAR(dateInternment) = $currentYear");
    $deceasedPerFloorOld[] = ($res && $row = $res->fetch_assoc()) ? intval($row['cnt']) : 0;
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
</head>
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
</style>
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
          <div class="stat-title">Active Clients</div>
          <div class="stat-value"><?php echo $activeClients; ?> Active Clients</div>
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
          <h3 style="font-size: 1.13rem; margin-bottom: 14px; color: #374151; font-weight: 700; letter-spacing: 0.5px; padding-left: 55px;">Upcoming Validity Expiry</h3>
          <div style="flex: 1; overflow-y: auto; max-height: 260px;">
            <?php if (count($expiringRecords) > 0): ?>
              <ul style="list-style: none; padding: 0; margin: 0;">
                <?php foreach ($expiringRecords as $rec): ?>
                  <li style="margin-bottom: 16px; display: flex; align-items: flex-start; gap: 12px;">
                    <div style="margin-top: 2px;">
                      <i class="fa-solid fa-calendar-exclamation" style="color: #eab308; font-size: 1.25rem;"></i>
                    </div>
                    <div style="background: #f8fafc; border-radius: 10px; box-shadow: 0 1px 4px rgba(0,0,0,0.04); padding: 10px 14px; min-width: 0; flex: 1;">
                      <div style="font-weight: 600; color: #1e293b; font-size: 1rem; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        <?php echo htmlspecialchars($rec['name']); ?>
                      </div>
                      <div style="font-size: 0.93rem; color: #2563eb; font-weight: 500; margin-bottom: 2px;">Apt: <?php echo htmlspecialchars($rec['nicheID']); ?></div>
                      <div style="font-size: 0.93rem; color: #eab308; font-weight: 500;">Validity: <?php echo htmlspecialchars($rec['validity']); ?></div>
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
      <div class="dashboard-card" style="height: 180px; padding: 0; align-items: stretch; justify-content: stretch;">
        <div id="columnChart" style="width: 100%; height: 100%;"></div>
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
      <div class="dashboard-card" style="height: 180px; padding: 0; align-items: stretch; justify-content: stretch;">
        <div id="floorBarChart" style="width: 100%; height: 100%;"></div>
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
    const deceasedPerFloorOld = <?php echo json_encode($deceasedPerFloorOld); ?>;
    const deceasedFloorLabels = <?php echo json_encode($deceasedFloorLabels); ?>;
    const deceasedFloorLabelsOld = <?php echo json_encode($deceasedFloorLabelsOld); ?>;

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
      var columnOptions = {
        chart: { type: 'bar', height: '100%', toolbar: { show: false } },
        series: [{
          name: 'Requests',
          data: isOldMap ? requestsDataOld : requestsDataNew
        }],
        xaxis: { categories: requestsLabels },
        colors: ['#34D399'],
        plotOptions: { bar: { columnWidth: '40%', borderRadius: 4 } },
        dataLabels: { enabled: false },
        title: {
          text: isOldMap ? 'Requests Per Month (Old Map)' : 'Requests Per Month',
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
      var floorBarOptions = {
        chart: { type: 'bar', height: '100%', toolbar: { show: false } },
        series: [{
          name: 'Deceased',
          data: isOldMap ? deceasedPerFloorOld : deceasedPerFloorNew
        }],
        xaxis: { categories: isOldMap ? deceasedFloorLabelsOld : deceasedFloorLabels },
        colors: ['#F59E42'],
        plotOptions: { bar: { columnWidth: '40%', borderRadius: 4 } },
        dataLabels: { enabled: true },
        title: {
          text: isOldMap ? 'Deceased Per Floor (Old Map)' : 'Deceased Per Floor',
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
  </script>
</body>
</html>
</body>
</html>
