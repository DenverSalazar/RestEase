<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>RestEase Admin Dashboard - Settings</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/Settings.css">
</head>
<body>
   <!-- Sidebar -->
   <aside class="sidebar">
    <div class="logo">
      <img src="../assets/RE Logo New.png" alt="RestEase Logo">
    </div>
    <nav class="nav-links">
      <a href="Dashboard.php" class="nav-item active">
        <i class="fas fa-th-large"></i>
        Dashboard
      </a>
      <a href="Analytics.php" class="nav-item">
        <i class="fas fa-chart-line"></i>
        Analytics
      </a>
      <a href="Mapping.php" class="nav-item">
        <i class="fas fa-map-marker-alt"></i>
        Mapping
      </a>
      <a href="Transaction.php" class="nav-item">
        <i class="fas fa-exchange-alt"></i>
        Transaction
      </a>
      <a href="Renewals.php" class="nav-item">
        <i class="fas fa-sync"></i>
        Renewals
      </a>
      <a href="Reports.php" class="nav-item">
        <i class="fas fa-file-alt"></i>
        Reports
      </a>
    </nav>
    <div style="margin-top: auto;">
      <a href="Settings.php" class="nav-item">
        <i class="fas fa-cog"></i>
        Settings
      </a>
      <a href="#" class="nav-item">
        <i class="fas fa-question-circle"></i>
        Help Center
      </a>
      <a href="./../login.php" class="nav-item">
        <i class="fas fa-sign-out-alt"></i>
        Logout
      </a>
    </div>
  </aside>

  <!-- Main Content -->
  <main class="main-content">
    <!-- Header -->
    <header class="header">
      <div class="search-bar">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="Tap to search">
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

    <!-- Settings Section -->
    <section class="settings-section">
      <h1>Settings</h1>
      <div class="dashboard-grid">
        <div class="card">
          <h3>User Preferences</h3>
          <p>Manage your personal preferences, such as theme and language.</p>
          <button class="btn btn-primary">Edit Preferences</button>
        </div>
        <div class="card">
          <h3>Notifications</h3>
          <p>Control how you receive notifications and alerts always.</p>
          <button class="btn btn-primary">Manage Notifications</button>
        </div>
        <div class="card">
          <h3>Account Security</h3>
          <p>Update your password and enable two-factor authentication.</p>
          <button class="btn btn-primary">Update Security</button>
        </div>
      </div>
    </section>
  </main>

  <script>
    // Add any necessary JavaScript here
    document.addEventListener('DOMContentLoaded', function() {
      // Initialize any components that need JavaScript
    });
  </script>
</body>
</html>
