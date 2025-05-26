<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>RestEase Clients</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/Clients.css">
  <link rel="stylesheet" href="../css/sidebar.css">
</head>
<body>
  <!-- Sidebar -->
  <?php include '../Includes/sidebar.php'; ?>

  <!-- Main Content -->
  <main class="main-content">
    <div class="clients-header">
      <h1>Clients</h1>
      <p class="subtitle">View all Clients Information.</p>
    </div>
    <div class="clients-tabs-bar">
      <div class="clients-tabs">
        <a href="Clients.php"><button class="tab active">All Clients</button></a>
        <a href="ClientsRequest.php"><button class="tab">Request</button></a>
      </div>
    </div>
    <div class="clients-actions">
      <div class="search-container">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="Search Clients">
      </div>
      <div class="actions-right">
        <button class="date-picker-btn"><i class="fas fa-calendar"></i> <span>Mar 5, 2025 - Mar 5, 2025</span></button>
        <button class="filter-btn"><i class="fas fa-filter"></i> Filter</button>
      </div>
    </div>
    <div class="clients-table-container">
      <table class="clients-table">
        <thead>
          <tr>
            <th>Client Name</th>
            <th>Email</th>
            <th>Contact</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><div class="avatar-img" style="background-image:url('https://randomuser.me/api/portraits/women/1.jpg');"></div> Rayna Dias</td>
            <td>Rayna@gmail.com</td>
            <td>0917 123 4567</td>
            <td class="status-approved">Approved</td>
          </tr>
          <tr>
            <td><div class="avatar-img" style="background-image:url('https://randomuser.me/api/portraits/men/2.jpg');"></div> Wilson Aminoff</td>
            <td>Wilson@gmail.com</td>
            <td>0995 876 5432</td>
            <td class="status-approved">Approved</td>
          </tr>
          <tr>
            <td><div class="avatar-img" style="background-image:url('https://randomuser.me/api/portraits/women/3.jpg');"></div> Maren Rosser</td>
            <td>Maren@gmail.com</td>
            <td>0908 234 7890</td>
            <td class="status-pending">Pending</td>
          </tr>
          <tr>
            <td><div class="avatar-img" style="background-image:url('https://randomuser.me/api/portraits/men/4.jpg');"></div> Brandon Saris</td>
            <td>Brandon@gmail.com</td>
            <td>0936 321 0987</td>
            <td class="status-approved">Approved</td>
          </tr>
          <tr>
            <td><div class="avatar-img" style="background-image:url('https://randomuser.me/api/portraits/men/5.jpg');"></div> Zaire Gouse</td>
            <td>Zaire@gmail.com</td>
            <td>0927 456 1234</td>
            <td class="status-denied">Denied</td>
          </tr>
          <tr>
            <td><div class="avatar-img" style="background-image:url('https://randomuser.me/api/portraits/women/6.jpg');"></div> Ann Dias</td>
            <td>Ann@gmail.com</td>
            <td>0945 789 6543</td>
            <td class="status-pending">Pending</td>
          </tr>
          <tr>
            <td><div class="avatar-img" style="background-image:url('https://randomuser.me/api/portraits/men/7.jpg');"></div> Zain Philips</td>
            <td>Zain@gmail.com</td>
            <td>0918 567 8901</td>
            <td class="status-approved">Approved</td>
          </tr>
          <tr>
            <td><div class="avatar-img" style="background-image:url('https://randomuser.me/api/portraits/men/8.jpg');"></div> Alfonso Torff</td>
            <td>Alfons@gmail.com</td>
            <td>0956 234 5678</td>
            <td class="status-pending">Pending</td>
          </tr>
          <tr>
            <td><div class="avatar-img" style="background-image:url('https://randomuser.me/api/portraits/men/9.jpg');"></div> Randy Kenter</td>
            <td>Randy@gmail.com</td>
            <td>0906 345 6789</td>
            <td class="status-denied">Denied</td>
          </tr>
          <tr>
            <td><div class="avatar-img" style="background-image:url('https://randomuser.me/api/portraits/men/10.jpg');"></div> Wilson Lubin</td>
            <td>Wilson@gmail.com</td>
            <td>0931 678 9012</td>
            <td class="status-approved">Approved</td>
          </tr>
        </tbody>
      </table>
    </div>
    <div class="clients-pagination-bar">
      <div class="pagination">
        <button class="page-btn"><i class="fas fa-angle-left"></i></button>
        <button class="page-btn">1</button>
        <button class="page-btn active">2</button>
        <button class="page-btn">3</button>
        <button class="page-btn"><i class="fas fa-angle-right"></i></button>
      </div>
    </div>
    <div>
        <span> Page 1 of 3
          </span>
        </div>
  </main>

</body>
</html>
