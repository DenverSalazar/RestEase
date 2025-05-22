<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>RestEase Admin Dashboard - Settings</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/Settings.css">
   <link rel="stylesheet" href="../css/sidebar.css">
</head>
<body>
   <!-- Sidebar -->
   <?php include '../Includes/sidebar.php'; ?>

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

</body>
</html>
