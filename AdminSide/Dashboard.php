<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    // Redirect to login page if not logged in
    header("Location: ../AdminLogin.php"); // Adjust the path if needed
    exit;
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
<body>
   <!-- Sidebar -->
   <?php include '../Includes/sidebar.php'; ?>

  <!-- Main Content -->
  <main class="main-content">
    <!-- Header -->
    <header class="header">
      <div class="header-left">
        <div class="greeting">
          <div class="hello-text">Hello, <span class="username">Sybau</span></div>
          <div class="datetime">
            <span class="date" id="current-date"></span>
            <span class="time" id="current-time"></span>
          </div>
        </div>
      </div>
      <div class="user-profile">
        <div class="notification-icon">
          <i class="fas fa-bell"></i>
          <span class="notification-badge">1</span>
        </div>
        <div class="profile-info">
          <img src="../assets/Default Image.jpg" alt="Profile" class="profile-avatar">
          <div>
            <div class="profile-name">Sybau</div>
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
          <div class="stat-value">652 Available Niches</div>
        </div>
        <div class="stat-card">
          <div class="stat-title">Occupied Niches</div>
          <div class="stat-value">652 Niches Occupied</div>
        </div>
        <div class="stat-card">
          <div class="stat-title">Pending Request</div>
          <div class="stat-value">652 Pending Request</div>
        </div>
        <div class="stat-card">
          <div class="stat-title">Active Clients</div>
          <div class="stat-value">652 Active Clients</div>
        </div>
      </div>
    </section>
    <section class="dashboard-grid">
      <div class="dashboard-card">
        <div class="dashboard-card-title">Recent Activity</div>
        <!-- Recent activity content here -->
      </div>
      <div class="dashboard-card">
        <!-- Empty card as in screenshot -->
      </div>
      <div class="dashboard-card dashboard-card-full">
        <div class="dashboard-card-title">Monthly Overview</div>
        <!-- Monthly overview content here -->
      </div>
    </section>
  </main>
</body>
</html>
