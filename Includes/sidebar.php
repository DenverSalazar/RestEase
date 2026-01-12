<?php
$current_page = basename($_SERVER['PHP_SELF']);
// Add this line to treat both as active for Clients
$clients_pages = ['Clients.php'];
$request_pages = ['ClientsRequest.php'];
// Add this line to treat both as active for Mapping
$mapping_pages = ['Mapping.php','insert.php', 'EditNiches.php', 'first_floor.php', 'second_floor.php', 'third_floor.php', 'OldMap.php'];
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
    <div class="logo" style="display: flex; flex-direction: row; align-items: center; padding: 0.75rem 0 0.5rem 0.5rem;">
      <img src="../assets/re logo blue.png" alt="RestEase" style="width: 55px; height: 55px; border-radius: 50%; margin-right: 0.85rem;">
      <div style="display: flex; flex-direction: column; align-items: flex-start;">
        <div style="font-size: 1.35em; font-weight: 700; color: #222; line-height: 1;">RestEase</div>
        <div style="font-size: 0.95em; color: #000000ff; font-weight: 500; margin-top: 0.15rem; letter-spacing: 1px;">MPDO</div>
      </div>
    </div>
    <nav class="nav-links">
        <!-- Main Menu Section -->
      <div class="nav-section text-muted" style="padding: 0.10rem 1rem 0.25rem; font-size: 0.95em; color: #000000ff; letter-spacing: 1px; font-weight: 600;">Main Menu</div>
      <a href="Dashboard.php" class="nav-item<?php if($current_page == 'Dashboard.php') echo ' active'; ?>">
        <i class="fas fa-pie-chart"></i>
        Dashboard
      </a>
       <a href="Records.php" class="nav-item<?php if(in_array($current_page, $records_pages)) echo ' active'; ?>">
        <i class="fas fa-file-alt"></i>
        Records
      </a>
      
      <!-- Mapping Section -->
      <div class="nav-section text-muted" style="padding: 0.15rem 1rem 0.25rem; font-size: 0.95em; color: #000000ff; letter-spacing: 1px; font-weight: 600;">Mapping</div>
      <!-- New Cemetery Dropdown -->
      <div class="nav-dropdown<?php if(in_array($current_page, ['Mapping.php','first_floor.php','second_floor.php','third_floor.php'])) echo ' open'; ?>" id="new-cemetery-dropdown">
        <div class="nav-item dropdown-toggle">
          
      <i class="fa-solid fa-map-location-dot"></i>
          New Cemetery
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
      <!-- Old Cemetery Dropdown -->
      <div class="nav-dropdown<?php if($current_page == 'OldMap.php') echo ' open'; ?>" id="old-cemetery-dropdown">
        <div class="nav-item dropdown-toggle">
      <i class="fa-solid fa-map-location-dot"></i>
          Old Cemetery
          <i class="fas fa-chevron-down dropdown-arrow"></i>
        </div>
        <div class="dropdown-menu">
          <a href="OldMap.php" class="dropdown-item<?php if($current_page == 'OldMap.php') echo ' active'; ?>">
            <i class="fas fa-building"></i>
            Old Map
          </a>
        </div>
      </div>
      
       <!-- General Section -->
      <div class="nav-section text-muted" style="padding: 0.15rem 1rem 0.25rem; font-size: 0.95em; color: #000000ff; letter-spacing: 1px; font-weight: 600;">General</div>
     
      <a href="Clients.php" class="nav-item<?php if(in_array($current_page, $clients_pages)) echo ' active'; ?>">
        <i class="fas fa-users"></i>
        Users
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
      <a href="Settings.php" class="nav-item<?php if($current_page == 'Settings.php') echo ' active'; ?>" style="position:relative;">
        <i class="fas fa-cog"></i>
        Settings
      </a>
      <a href="./../login.php" class="nav-item">
        <i class="fas fa-sign-out-alt"></i>
        Logout
      </a>
    </div>
  </aside>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle all dropdown toggles
    document.querySelectorAll('.dropdown-toggle').forEach(function(toggle) {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            var parentDropdown = toggle.closest('.nav-dropdown');
            if (parentDropdown) {
                parentDropdown.classList.toggle('open');
            }
        });
    });
});

// notif badge removed from sidebar; no badge logic needed
</script>