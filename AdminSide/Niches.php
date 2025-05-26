<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>RestEase Admin Dashboard</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/dashboard.css">
  <link rel="stylesheet" href="../css/sidebar.css">
  <link rel="stylesheet" href="../css/Niches.css">
  <style>
    .edit-form {
      background: white;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      max-width: 600px;
      margin: 20px auto;
    }
    .form-group {
      margin-bottom: 15px;
    }
    .form-group label {
      display: block;
      margin-bottom: 5px;
      font-weight: 500;
      color: #333;
    }
    .form-group input {
      width: 100%;
      padding: 8px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 14px;
    }
    .form-actions {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      margin-top: 20px;
    }
    .btn {
      padding: 8px 16px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-weight: 500;
    }
    .btn-save {
      background-color: #4CAF50;
      color: white;
    }
    .btn-cancel {
      background-color: #f44336;
      color: white;
    }
  </style>
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
    
    <h1 style="margin-left: 230px;">Edit Niche</h1>
    <!-- Edit Form -->
    <div class="edit-form">
      <form id="editNicheForm" method="POST" action="update_niche.php">
        <div class="form-group">
          <label for="nicheID">Niche ID</label>
          <input type="text" id="nicheID" name="nicheID" readonly>
        </div>
        <div class="form-group">
          <label for="name">Name</label>
          <input type="text" id="name" name="name" required>
        </div>
        <div class="form-group">
          <label for="born">Born</label>
          <input type="text" id="born" name="born" required>
        </div>
        <div class="form-group">
          <label for="died">Died</label>
          <input type="text" id="died" name="died" required>
        </div>
        <div class="form-actions">
          <button type="button" class="btn btn-cancel" onclick="window.location.href='Mapping.php'">Cancel</button>
          <button type="submit" class="btn btn-save">Save Changes</button>
        </div>
      </form>
    </div>
  </main>

  <script>
    // Get URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    
    // Fill form with niche information
    document.getElementById('nicheID').value = urlParams.get('nicheID') || '';
    document.getElementById('name').value = urlParams.get('name') || '';
    document.getElementById('born').value = urlParams.get('born') || '';
    document.getElementById('died').value = urlParams.get('died') || '';
  </script>
</body>
</html>
