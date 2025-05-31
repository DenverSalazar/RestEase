<?php
// Database connection (adjust credentials as needed)
$conn = new mysqli("localhost", "root", "", "cemeterydb");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $firstName = $_POST['firstName'] ?? '';
  $lastName = $_POST['lastName'] ?? '';
  $age = $_POST['age'] ?? '';
  $born = $_POST['born'] ?? '';
  $residency = $_POST['residency'] ?? '';
  $dateDied = $_POST['dateDied'] ?? '';
  $dateInternment = $_POST['dateInternment'] ?? '';
  $apartmentNo = $_POST['apartmentNo'] ?? '';
  $informantName = $_POST['informantName'] ?? '';

  $stmt = $conn->prepare("INSERT INTO deceased (firstName, lastName, age, born, residency, dateDied, dateInternment, nicheID, informantName) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
  $stmt->bind_param("ssissssss", $firstName, $lastName, $age, $born, $residency, $dateDied, $dateInternment, $apartmentNo, $informantName);
  $stmt->execute();
  $stmt->close();

  // Optional: Redirect to Mapping.php or show a success message
  header("Location: Mapping.php");
  exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>RestEase Admin Dashboard</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/sidebar.css">
  <link rel="stylesheet" href="../css/Insert.css">
  <style>
    /* Add this style block inside <head> or in your Insert.css */
    .niche-picker-group {
      display: flex;
      align-items: center;
      gap: 8px;
      width: 100%;
    }
    .niche-picker-group input[readonly] {
      flex: 1 1 0;
      min-width: 0;
      background: #f8fafc;
      border: 1.5px solid #e3e7ed;
      color: #2d3a4a;
      font-weight: 500;
      letter-spacing: 0.5px;
      /* Remove fixed width if any */
    }
    .pick-niche-btn {
      background: #f5f7fa;
      color: #2d3a4a;
      border: 1.5px solid #d3dbe2;
      border-radius: 7px;
      padding: 8px 14px;
      min-width: 44px;
      height: 42px;
      font-size: 1.1rem;
      transition: background 0.18s, color 0.18s, border 0.18s;
      box-shadow: none;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .pick-niche-btn:hover, .pick-niche-btn:focus {
      background: #e4e9ee;
      color: #1976d2;
      border-color: #bfc9d1;
    }
  </style>
</head>
<body>
  <!-- Sidebar -->
  <?php include '../Includes/sidebar.php'; ?>

  <div class="main-content">
    <div class="top-bar">
      <span class="page-title">Insert Data</span>
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
    </div>
    <div class="page-subtitle">Fill up the masterlist data</div>
    <div class="top-actions">
      <button type="button" class="btn upload">Upload Excel</button>
      <a href="Records.php"><button type="button" class="btn secondary">Back</button></a>
    </div>
    <div class="form-container">
      <div class="form-section-title">Deceased Information</div>
      <form method="post" autocomplete="off">
        <div class="form-row">
          <div class="form-group">
            <label for="firstName">First Name</label>
            <input type="text" id="firstName" name="firstName" placeholder="First Name">
          </div>
          <div class="form-group">
            <label for="lastName">Last Name</label>
            <input type="text" id="lastName" name="lastName" placeholder="Last Name">
          </div>
          <div class="form-group">
            <label for="age">Age</label>
            <input type="number" id="age" name="age" placeholder="Age">
          </div>
        </div>
        <div class="form-row-2">
          <div class="form-group">
            <label for="born">Born</label>
            <input type="date" id="born" name="born" placeholder="Born">
          </div>
          <div class="form-group">
            <label for="residency">Residency</label>
            <input type="text" id="residency" name="residency" placeholder="Residency">
          </div>
          <div class="form-group">
            <label for="dateDied">Date Died</label>
            <input type="date" id="dateDied" name="dateDied" placeholder="Date Died">
          </div>
        </div>
        <div class="form-row-3">
          <div class="form-group">
            <label for="dateInternment">Date of Internment</label>
            <input type="date" id="dateInternment" name="dateInternment" placeholder="Date of Internment">
          </div>
          <div class="form-group">
            <label for="apartmentNo">Apartment No.</label>
            <div class="niche-picker-group">
              <input type="text" id="apartmentNo" name="apartmentNo" placeholder="Apartment No." readonly>
              <button type="button" id="pickNicheBtn" class="btn pick-niche-btn" title="Pick Niche">
                <i class="fas fa-map-marker-alt"></i>
              </button>
            </div>
          </div>
          <div class="form-group">
            <label for="informantName">Informant Name</label>
            <input type="text" id="informantName" name="informantName" placeholder="Informant Name">
          </div>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn primary">Insert</button>
        </div>
      </form>
    </div>
  </div>
  <script>
    // Add this script before </body>
    document.getElementById('pickNicheBtn').onclick = function() {
      window.open('Mapping.php?pickNiche=1', 'PickNiche', 'width=900,height=700');
    };

    // Listen for message from Mapping.php
    window.addEventListener('message', function(event) {
      if (event.data && event.data.nicheID) {
        document.getElementById('apartmentNo').value = event.data.nicheID;
      }
    });
  </script>
</body>
</html>