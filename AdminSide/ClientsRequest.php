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
        <span class="clients-tab-title">Clients Request</span>
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
    <?php
    include_once '../Includes/db.php';
    // Fetch all client requests with user info
    $sql = "SELECT cr.*, u.first_name, u.last_name, u.email FROM client_requests cr JOIN users u ON cr.user_id = u.id ORDER BY cr.created_at DESC";
    $result = $conn->query($sql);
    ?>
    <div class="clients-table-container">
      <table class="clients-table">
        <thead>
          <tr>
            <th>Client Name</th>
            <th>Email</th>
            <th>Type</th>
            <th>Status</th>
            <th>Details</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
              <?php
                $firstName = htmlspecialchars($row['first_name']);
                $lastName = htmlspecialchars($row['last_name']);
                $name = $firstName . ' ' . $lastName;
                $email = htmlspecialchars($row['email']);
                $initials = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));
                $colorIndex = (abs(crc32($firstName . $lastName)) % 10) + 1;
                $colorClass = "avatar-color-$colorIndex";
              ?>
              <tr>
                <td>
                  <div class="avatar-img avatar-google <?php echo $colorClass; ?>" style="display:inline-flex;"><?php echo $initials; ?></div>
                  <span class="client-name" style="vertical-align:middle; margin-left:4px; display:inline-block;"><?php echo $name; ?></span>
                </td>
                <td><?php echo $email; ?></td>
                <td><?php echo htmlspecialchars($row['type']); ?></td>
                <td><span class="status-badge status-pending">Pending</span></td>
                <td><button class="view-btn" onclick="openPopup(<?php echo $row['id']; ?>)">View</button></td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr><td colspan="5">No client requests found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <!-- Popup Modal -->
    <div id="popupModal" class="popup-modal" style="display:none;">
      <div class="popup-content">
        <span class="close-btn" onclick="closePopup()">&times;</span>
        <div class="popup-details" style="display: flex; flex-direction: column; gap: 5px;">
          <p><b>Client Name:</b> <span id="popupClientName" style="color:#888;"></span></p>
          <p><b>Email:</b> <span id="popupEmail" style="color:#888;"></span></p>
          <p><b>Type:</b> <span id="popupType" style="color:#888;"></span></p>
          <p><b>Age:</b> <span id="popupAge" style="color:#888;"></span></p>
          <p><b>Informant Name:</b> <span id="popupInformant" style="color:#888;"></span></p>
          <p><b>Name of Deceased:</b> <span id="popupDeceased" style="color:#888;"></span></p>
          <p><b>Attachments:</b></p>
          <div id="popupAttachment"></div>
        </div>
        <div class="popup-actions" style="display: flex; gap: 18px; justify-content: flex-end; margin-top: 18px;">
          <button class="accept-btn" onclick="acceptRequest()">Accept</button>
          <button class="deny-btn" onclick="denyRequest()">Deny</button>
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
        border-radius: 18px;
        padding: 48px 56px 32px 56px;
        min-width: 420px;
        max-width: 95vw;
        min-height: 340px;
        box-shadow: 0 8px 32px rgba(60,60,60,0.18), 0 1.5px 6px rgba(0,0,0,0.08);
        position: relative;
        font-family: 'Inter', sans-serif;
        border-top: 6px solid #506C84;
        transition: box-shadow 0.2s;
      }
      .close-btn {
        position: absolute;
        top: 18px;
        right: 22px;
        font-size: 2.1rem;
        color: #e74c3c;
        cursor: pointer;
        font-weight: 400;
        transition: color 0.18s;
      }
      .close-btn:hover {
        color: #b91c1c;
      }
      .popup-details p {
        margin: 0 0 18px 0;
        font-size: 1.13rem;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 8px;
      }
      .popup-details b {
        font-weight: 600;
        color: #222;
        min-width: 170px;
        display: inline-block;
        opacity: 0.7;
      }
      .popup-details span {
        color: #666;
        font-weight: 500;
        font-size: 1.13rem;
      }
      .attachment-box {
        border: 1px solid #e5e7eb;
        border-radius: 7px;
        padding: 10px 18px;
        background: #fafbfc;
        display: flex;
        align-items: center;
        width: fit-content;
        margin-bottom: 18px;
        margin-top: 4px;
        font-size: 1.08rem;
      }
      .popup-actions {
        display: flex;
        gap: 22px;
        justify-content: flex-end;
        margin-top: 28px;
      }
      .accept-btn, .deny-btn {
        min-width: 120px;
        font-size: 1.13rem;
        padding: 12px 0;
        border-radius: 8px;
        font-weight: 600;
        box-shadow: 0 1.5px 6px rgba(34,197,94,0.04);
      }
      .accept-btn {
        background: #a6f4c5;
        color: #22c55e;
        border: none;
        transition: background 0.2s, color 0.2s;
      }
      .accept-btn:hover {
        background: #22c55e;
        color: #fff;
      }
      .deny-btn {
        background: #fecaca;
        color: #dc2626;
        border: none;
        transition: background 0.2s, color 0.2s;
      }
      .deny-btn:hover {
        background: #dc2626;
        color: #fff;
      }
      .clients-tab-title {
        font-weight: 600;
        font-size: 1.08rem;
        padding: 10px 28px;
        border-radius: 8px;
        background: #f4f6fa;
        color: #222;
        display: inline-block;
        margin-bottom: 0;
        margin-top: 0;
        box-shadow: 0 1.5px 6px rgba(0,0,0,0.04);
      }
    </style>
    <script>
      function openPopup(requestId) {
        fetch('get_request_details.php?id=' + requestId)
          .then(response => response.json())
          .then(data => {
            if (data && data.success) {
              document.getElementById('popupClientName').textContent = data.name;
              document.getElementById('popupEmail').textContent = data.email;
              document.getElementById('popupType').textContent = data.type;
              document.getElementById('popupAge').textContent = data.age;
              document.getElementById('popupInformant').textContent = data.informant_name;
              document.getElementById('popupDeceased').textContent = data.deceased_name;
              document.getElementById('popupAttachment').innerHTML = data.attachment_html;
              document.getElementById('popupModal').style.display = 'flex';
            }
          });
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
      function acceptRequest() {
        // To be implemented
      }
      function denyRequest() {
        // To be implemented
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
