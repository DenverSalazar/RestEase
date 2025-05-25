<?php
$current_page = basename($_SERVER['PHP_SELF']);
// Add this line to treat both as active for Clients
$clients_pages = ['Clients.php', 'ClientsRequest.php'];
?>
<aside class="sidebar">
    <div class="logo">
      <img src="../assets/RE Logo New.png" alt="RestEase Logo">
    </div>
    <nav class="nav-links">
      <a href="Dashboard.php" class="nav-item<?php if($current_page == 'Dashboard.php') echo ' active'; ?>">
        <i class="fas fa-pie-chart"></i>
        Dashboard
      </a>
      <a href="Mapping.php" class="nav-item<?php if($current_page == 'Mapping.php') echo ' active'; ?>">
        <i class="fas fa-map-marker-alt"></i>
        Mapping
      </a>
      <a href="Niches.php" class="nav-item<?php if($current_page == 'Niches.php') echo ' active'; ?>">
        <i class="fas fa-th"></i>
        Niches
      </a>
      <a href="Records.php" class="nav-item<?php if($current_page == 'Records.php') echo ' active'; ?>">
        <i class="fas fa-file-alt"></i>
        Records
      </a>
      <a href="Clients.php" class="nav-item<?php if(in_array($current_page, $clients_pages)) echo ' active'; ?>">
        <i class="fas fa-users"></i>
        Clients
      </a>
      <a href="Renewals.php" class="nav-item<?php if($current_page == 'Renewals.php') echo ' active'; ?>">
        <i class="fas fa-sync"></i>
        Renewals
      </a>
      <a href="Reports.php" class="nav-item<?php if($current_page == 'Reports.php') echo ' active'; ?>">
        <i class="fas fa-th-list"></i>
        Reports
      </a>
    </nav>
    <div style="margin-top: auto;">
      <a href="Settings.php" class="nav-item<?php if($current_page == 'Settings.php') echo ' active'; ?>">
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