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

// Occupied niches: unique nicheIDs in deceased table only (current occupants)
$occupiedNichesArr = [];
$resOcc = $conn->query("SELECT DISTINCT nicheID FROM deceased WHERE nicheID IS NOT NULL AND nicheID != '' AND nicheID != 'null'");
if ($resOcc) {
    while ($row = $resOcc->fetch_assoc()) {
        $occupiedNichesArr[$row['nicheID']] = true;
    }
}
$occupiedNiches = count($occupiedNichesArr);

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
        <div class="stat-card">
          <div class="stat-title">Available Niches</div>
          <div class="stat-value"><?php echo $availableNiches; ?> Available Niches</div>
        </div>
        <div class="stat-card">
          <div class="stat-title">Occupied Niches</div>
          <div class="stat-value"><?php echo $occupiedNiches; ?> Niches Occupied</div>
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
    <section class="dashboard-grid">
      <div class="dashboard-card dashboard-card-large">
        <div id="chart"></div>
      </div>
      <div class="dashboard-card dashboard-card-small">
        <div style="padding: 18px 14px 18px 18px; width: 100%; height: 100%; display: flex; flex-direction: column;">
          <h3 style="font-size: 1.13rem; margin-bottom: 14px; color: #374151; font-weight: 700; letter-spacing: 0.5px;">Upcoming Validity Expiry</h3>
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
  </main>
  <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
  <script>
    // SPLINE CHART
  var options = {
    chart: {
      type: 'area',
      height: 350,
      toolbar: {
        show: false
      }
    },
    series: [{
      name: 'Active Clients',
      data: [31, 40, 28, 51, 42, 85, 77]
    }],
    xaxis: {
      categories: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
    },
    stroke: {
      curve: 'smooth'
    },
    fill: {
      type: 'gradient',
      gradient: {
        shadeIntensity: 1,
        opacityFrom: 0.5,
        opacityTo: 0.1,
        stops: [0, 90, 100]
      }
    },
    colors: ['#4F46E5'], // Indigo tone
    dataLabels: {
      enabled: false
    },
    tooltip: {
      theme: 'light'
    }
  };

  var chart = new ApexCharts(document.querySelector("#chart"), options);
  chart.render()

   // COLUMN CHART
    var columnOptions = {
      chart: {
        type: 'bar',
        height: '100%', // Use full container height
        toolbar: { show: false }
      },
      series: [{
        name: 'Requests',
        data: [10, 20, 15, 30, 25]
      }],
      xaxis: {
        categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May']
      },
      colors: ['#34D399'], // Teal Green
      plotOptions: {
        bar: {
          columnWidth: '40%',
          borderRadius: 4
        }
      },
      dataLabels: {
        enabled: false
      }
    };
    var columnChart = new ApexCharts(document.querySelector("#columnChart"), columnOptions);
    columnChart.render();

    // PIE CHART
    var pieOptions = {
      chart: {
        type: 'pie',
        height: '100%', // Use full container height
        toolbar: { show: false }
      },
      series: [44, 33, 23],
      labels: ['Available', 'Occupied', 'Pending'],
      colors: ['#60A5FA', '#F87171', '#FBBF24'], // Blue, Red, Yellow
      legend: {
        position: 'bottom'
      }
    };
    var pieChart = new ApexCharts(document.querySelector("#pieChart"), pieOptions);
    pieChart.render();
  </script>
</body>
</html>
