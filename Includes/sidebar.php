<?php
$current_page = basename($_SERVER['PHP_SELF']);
// Add this line to treat both as active for Clients
$clients_pages = ['Clients.php'];
$request_pages = ['ClientsRequest.php'];
// Add this line to treat both as active for Mapping
$mapping_pages = ['Mapping.php','insert.php', 'EditNiches.php', 'first_floor.php', 'second_floor.php', 'third_floor.php'];
// Add this line to treat both as active for Records
$records_pages = ['Records.php', 'Insert.php', 'EditNiches.php'];

// Check if we're in EditNiches.php and determine which section should be active
if ($current_page === 'EditNiches.php' || $current_page === 'editniches.php') {
    // Check if we came from Records.php by looking at the referrer
    $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
    if (strpos($referer, 'Records.php') !== false) {
        $current_page = 'Records.php';
    } else {
        $current_page = 'Mapping.php';
    }
}

// Check if mapping dropdown should be open
$mapping_dropdown_open = in_array($current_page, $mapping_pages);
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
      
      <!-- Mapping Dropdown -->
      <div class="nav-dropdown<?php if($mapping_dropdown_open) echo ' open'; ?>">
        <div class="nav-item dropdown-toggle">
          <i class="fas fa-map-marker-alt"></i>
          Mapping
          <i class="fas fa-chevron-down dropdown-arrow"></i>
        </div>
        <div class="dropdown-menu">
          <a href="Mapping.php" class="dropdown-item<?php if($current_page == 'Mapping.php' || $current_page == 'first_floor.php') echo ' active'; ?>">
            <i class="fas fa-building"></i>
            First Floor
          </a>
          <a href="second_floor.php" class="dropdown-item<?php if($current_page == 'second_floor.php') echo ' active'; ?>">
            <i class="fas fa-building"></i>
            Second Floor
          </a>
          <a href="third_floor.php" class="dropdown-item<?php if($current_page == 'third_floor.php') echo ' active'; ?>">
            <i class="fas fa-building"></i>
            Third Floor
          </a>
        </div>
      </div>
      
      <a href="Records.php" class="nav-item<?php if(in_array($current_page, $records_pages)) echo ' active'; ?>">
        <i class="fas fa-file-alt"></i>
        Records
      </a>
      <a href="Clients.php" class="nav-item<?php if(in_array($current_page, $clients_pages)) echo ' active'; ?>">
        <i class="fas fa-users"></i>
        Clients
      </a>
      <a href="ClientsRequest.php" class="nav-item<?php if(in_array($current_page, $request_pages)) echo ' active'; ?>">
        <i class="fas fa-spinner"></i>
        Client Request
      </a>
      <a href="Ledger.php" class="nav-item<?php if($current_page == 'Ledger.php') echo ' active'; ?>">
        <i class="fas fa-credit-card"></i>
        Ledger
      </a>
      <a href="Certificate.php" class="nav-item<?php if($current_page == 'Certificate.php') echo ' active'; ?>">
        <i class="fas fa-th-list"></i>
        Certification
      </a>
    </nav>
    <div style="margin-top: auto;">
      <a href="Settings.php" class="nav-item<?php if($current_page == 'Settings.php') echo ' active'; ?>">
        <i class="fas fa-cog"></i>
        Settings
      </a>
      <a href="./../AdminLogin.php" class="nav-item">
        <i class="fas fa-sign-out-alt"></i>
        Logout
      </a>
    </div>
  </aside>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle dropdown toggle
    const dropdownToggle = document.querySelector('.dropdown-toggle');
    const dropdown = document.querySelector('.nav-dropdown');
    
    if (dropdownToggle && dropdown) {
        dropdownToggle.addEventListener('click', function(e) {
            e.preventDefault();
            dropdown.classList.toggle('open');
        });
    }
});
</script>