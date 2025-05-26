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
        <a href="Clients.php"><button class="tab">All Clients</button></a>
        <a href="ClientsRequest.php"><button class="tab active">Request</button></a>
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
            <th>Types</th>
            <th>Details</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><div class="avatar-img" style="background-image:url('https://randomuser.me/api/portraits/women/1.jpg');"></div> Rayna Dias</td>
            <td>Rayna@gmail.com</td>
            <td>Transfer</td>
            <td><button class="view-btn" onclick="openPopup()">View</button></td>
          </tr>
          <tr>
            <td><div class="avatar-img" style="background-image:url('https://randomuser.me/api/portraits/men/2.jpg');"></div> Wilson Aminoff</td>
            <td>Wilson@gmail.com</td>
            <td>Transfer</td>
            <td><button class="view-btn" onclick="openPopup()">View</button></td>
          </tr>
          <tr>
            <td><div class="avatar-img" style="background-image:url('https://randomuser.me/api/portraits/women/3.jpg');"></div> Maren Rosser</td>
            <td>Maren@gmail.com</td>
            <td>Transfer</td>
            <td><button class="view-btn" onclick="openPopup()">View</button></td>
          </tr>
          <tr>
            <td><div class="avatar-img" style="background-image:url('https://randomuser.me/api/portraits/men/4.jpg');"></div> Brandon Saris</td>
            <td>Brandon@gmail.com</td>
            <td>Transfer</td>
            <td><button class="view-btn" onclick="openPopup()">View</button></td>
          </tr>
          <tr>
            <td><div class="avatar-img" style="background-image:url('https://randomuser.me/api/portraits/men/5.jpg');"></div> Zaire Gouse</td>
            <td>Zaire@gmail.com</td>
            <td>Transfer</td>
            <td><button class="view-btn" onclick="openPopup()">View</button></td>
          </tr>
          <tr>
            <td><div class="avatar-img" style="background-image:url('https://randomuser.me/api/portraits/women/6.jpg');"></div> Ann Dias</td>
            <td>Ann@gmail.com</td>
            <td>Transfer</td>
            <td><button class="view-btn" onclick="openPopup()">View</button></td>
          </tr>
          <tr>
            <td><div class="avatar-img" style="background-image:url('https://randomuser.me/api/portraits/men/7.jpg');"></div> Zain Philips</td>
            <td>Zain@gmail.com</td>
            <td>Transfer</td>
            <td><button class="view-btn" onclick="openPopup()">View</button></td>
          </tr>
          <tr>
            <td><div class="avatar-img" style="background-image:url('https://randomuser.me/api/portraits/men/8.jpg');"></div> Alfonso Torff</td>
            <td>Alfons@gmail.com</td>
            <td>Transfer</td>
            <td><button class="view-btn" onclick="openPopup()">View</button></td>
          </tr>
          <tr>
            <td><div class="avatar-img" style="background-image:url('https://randomuser.me/api/portraits/men/9.jpg');"></div> Randy Kenter</td>
            <td>Randy@gmail.com</td>
            <td>Transfer</td>
            <td><button class="view-btn" onclick="openPopup()">View</button></td>
          </tr>
          <tr>
            <td><div class="avatar-img" style="background-image:url('https://randomuser.me/api/portraits/men/10.jpg');"></div> Wilson Lubin</td>
            <td>Wilson@gmail.com</td>
            <td>Transfer</td>
            <td><button class="view-btn" onclick="openPopup()">View</button></td>
          </tr>
        </tbody>
      </table>
    </div>
    <!-- Popup Modal -->
    <div id="popupModal" class="popup-modal" style="display:none;">
      <div class="popup-content">
        <span class="close-btn" onclick="closePopup()">&times;</span>
        <div class="popup-details" style="display: flex; flex-direction: column; gap: 5px;">
          <p><b>Name:</b> <span style="color:#888;">Jaxson Saris</span></p>
          <p><b>Age:</b> <span style="color:#888;">30</span></p>
          <p><b>Contact:</b> <span style="color:#888;">0996 523 6567</span></p>
          <p><b>Type:</b> <span style="color:#888;">Internment</span></p>
          <p><b>Name of Deceased:</b> <span style="color:#888;">Alden Recharge</span></p>
          <p><b>Attachments</b></p>
          <div class="attachment-box">
            <img src="https://cdn.jsdelivr.net/gh/edent/SuperTinyIcons/images/svg/pdf.svg" alt="PDF" style="height:20px;vertical-align:middle;margin-right:6px;">
            <span style="color:#888;">DeathCert.pdf</span>
          </div>
        </div>
        <div class="popup-actions">
          <button class="accept-btn">Accept</button>
          <button class="deny-btn">Deny</button>
        </div>
      </div>
    </div>
    <style>
      /* Popup Modal Styles */
      .popup-modal {
        position: fixed;
        z-index: 9999;
        left: 0; top: 0; width: 100vw; height: 100vh;
        background: rgba(0,0,0,0.18);
        display: flex;
        align-items: center;
        justify-content: center;
      }
      .popup-content {
        background: #fff;
        border-radius: 10px;
        padding: 48px 56px 10px 56px; /* <-- change the third value for bottom padding */
        min-width: 480px;
        max-width: 700px;
        min-height: 400px; /* increased height */
        box-shadow: 0 4px 24px rgba(0,0,0,0.09);
        position: relative;
        font-family: 'Inter', sans-serif;
      }
      .close-btn {
        position: absolute;
        top: 18px;
        right: 22px;
        font-size: 1.7rem;
        color: #e74c3c;
        cursor: pointer;
        font-weight: 400;
      }
      .popup-details p {
        margin: 0 0 10px 0;
        font-size: 1.13rem;
        font-weight: 500;
      }
      .popup-details b {
        font-weight: 600;
        color: #222;
      }
      .attachment-box {
        border: 1px solid #ddd;
        border-radius: 7px;
        padding: 8px 14px;
        background: #fafbfc;
        display: flex;
        align-items: center;
        width: fit-content;
        margin-bottom: 18px;
        margin-top: 4px;
      }
      .popup-actions {
        display: flex;
        gap: 18px;
        justify-content: flex-end;
        margin-top: 18px;
      }
      .accept-btn {
        background: #22c55e;
        color: #fff;
        border: none;
        border-radius: 7px;
        padding: 8px 32px;
        font-size: 1.1rem;
        font-weight: 500;
        cursor: pointer;
        transition: background 0.2s;
      }
      .accept-btn:hover {
        background: #16a34a;
      }
      .deny-btn {
        background: #ef4444;
        color: #fff;
        border: none;
        border-radius: 7px;
        padding: 8px 32px;
        font-size: 1.1rem;
        font-weight: 500;
        cursor: pointer;
        transition: background 0.2s;
      }
      .deny-btn:hover {
        background: #dc2626;
      }
    </style>
    <script>
      function openPopup() {
        document.getElementById('popupModal').style.display = 'flex';
      }
      function closePopup() {
        document.getElementById('popupModal').style.display = 'none';
      }
      // Optional: Close popup when clicking outside content
      window.onclick = function(event) {
        var modal = document.getElementById('popupModal');
        if (event.target === modal) {
          closePopup();
        }
      }
    </script>
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
