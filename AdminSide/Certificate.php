<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    // Redirect to login page if not logged in
    header("Location: ../AdminLogin.php"); // Adjust the path if needed
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>RestEase Admin Dashboard</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/Reports.css">
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
    
    <h1 style="margin-left: 230px;">Certificate</h1>

    <!-- Certificate Template Form -->
    <div style="margin-left:230px; margin-bottom:30px; max-width:600px;">
    
      <form method="post" style="background:#fff; padding:20px; border-radius:8px; box-shadow:0 2px 8px #eee;">
        <label>Name:</label>
        <input type="text" name="name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required style="width:100%;margin-bottom:10px;">

        <label>Barangay:</label>
        <input type="text" name="barangay" value="<?php echo isset($_POST['barangay']) ? htmlspecialchars($_POST['barangay']) : ''; ?>" required style="width:100%;margin-bottom:10px;">

        <label>Apartment No.:</label>
        <input type="text" name="apartment" value="<?php echo isset($_POST['apartment']) ? htmlspecialchars($_POST['apartment']) : ''; ?>" required style="width:100%;margin-bottom:10px;">

        <label>Date:</label>
        <input type="date" name="date" value="<?php echo isset($_POST['date']) ? htmlspecialchars($_POST['date']) : date('Y-m-d'); ?>" required style="width:100%;margin-bottom:10px;">

        <label>Renewal Date (5 years from Date):</label>
        <?php
          $renewal = '';
          if (isset($_POST['date'])) {
            $renewal = date('Y-m-d', strtotime($_POST['date'].' +5 years'));
          } else {
            $renewal = date('Y-m-d', strtotime('+5 years'));
          }
        ?>
        <input type="date" name="renewal" value="<?php echo $renewal; ?>" readonly style="width:100%;margin-bottom:10px;">

        <label>Certificate Action:</label><br>
        <input type="checkbox" name="actions[]" value="register_death" <?php if(isset($_POST['actions']) && in_array('register_death', $_POST['actions'])) echo 'checked'; ?>> Register death and rent CRYPT for five (5) years<br>
        <input type="checkbox" name="actions[]" value="renewal_crypt" <?php if(isset($_POST['actions']) && in_array('renewal_crypt', $_POST['actions'])) echo 'checked'; ?>> Renewal of CRYPT<br>
        <input type="checkbox" name="actions[]" value="transfer_remains" <?php if(isset($_POST['actions']) && in_array('transfer_remains', $_POST['actions'])) echo 'checked'; ?>> Transfer the remains<br>
        <input type="checkbox" name="actions[]" value="reopen_tomb" <?php if(isset($_POST['actions']) && in_array('reopen_tomb', $_POST['actions'])) echo 'checked'; ?>> Re-open the tomb<br>
        <input type="checkbox" name="actions[]" value="reenter_remains" <?php if(isset($_POST['actions']) && in_array('reenter_remains', $_POST['actions'])) echo 'checked'; ?>> Re-enter the remains<br>

        <label>Deceased Name:</label>
        <input type="text" name="deceased" value="<?php echo isset($_POST['deceased']) ? htmlspecialchars($_POST['deceased']) : ''; ?>" style="width:100%;margin-bottom:10px;">

        <button type="submit" style="margin-top:15px;">Preview Certificate</button>
      </form>
    </div>

    <!-- Certificate Preview -->
    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
      <?php $mc_no = '2024-001'; // Static MC No. ?>
      <div style="margin-left:230px; max-width:700px; background:#f9f9f9; padding:20px; border-radius:8px; box-shadow:0 2px 8px #eee;">
        <div style="text-align:center;">
          <img src="../assets/PadreGarciaLogo.png" alt="Municipal Logo" style="height:70px;">
          <h3 style="margin:0;">Republic of the Philippines<br>Province of Batangas<br>MUNICIPALITY OF PADRE GARCIA</h3>
          <h2 style="margin:10px 0 0 0;">OFFICE OF THE MUNICIPAL MAYOR</h2>
          <h2 style="letter-spacing:8px; margin:20px 0;">CERTIFICATION</h2>
        </div>
        <div style="margin-top:20px;">
          <span style="float:right; background:yellow; padding:2px 8px; font-weight:bold;">
            MC No. <?php echo $mc_no; ?>
          </span>
          <p>This is to certify that <strong><?php echo htmlspecialchars($_POST['name']); ?></strong> of Barangay <strong><?php echo htmlspecialchars($_POST['barangay']); ?></strong></p>
          <ul style="list-style:none; padding-left:0;">
            <?php
              $actions = [
                'register_death' => 'register the death of <strong>' . htmlspecialchars($_POST['deceased'] ?? '') . '</strong> and rent CRYPT for five (5) years',
                'renewal_crypt' => 'renewal of CRYPT',
                'transfer_remains' => 'transfer the remains of',
                'reopen_tomb' => 're-open the tomb of',
                'reenter_remains' => 're-enter the remains of'
              ];
              if (isset($_POST['actions'])) {
                foreach ($_POST['actions'] as $act) {
                  echo '<li style="margin-bottom:5px;"><input type="checkbox" checked disabled> ' . $actions[$act] . '</li>';
                }
              }
            ?>
          </ul>
          <p>
            Who died last and was buried at the Municipal Cemetery.<br>
            Issued this <strong><?php echo htmlspecialchars($_POST['date']); ?></strong> upon the request of Mr./Ms. <strong><?php echo htmlspecialchars($_POST['name']); ?></strong> for whatever purpose it may serve.<br>
            Apartment No. <strong><?php echo htmlspecialchars($_POST['apartment']); ?></strong>
          </p>
          <div style="margin-top:30px;">
            <div style="float:left;">
              <strong>Recommending Approval:</strong><br>
              ENGR. KHRISTINE Z. TAPALLA, EnP<br>
              MPDC/ZA
            </div>
            <div style="float:right;">
              <strong>Approved by:</strong><br>
              ATTY. MARK LESTER G. MANALO<br>
              Municipal Administrator
            </div>
            <div style="clear:both;"></div>
          </div>
          <div style="margin-top:30px;">
            <strong>OR No.:</strong><br>
            <strong>Date Paid:</strong><br>
            <strong>Amount:</strong><br>
            <strong>Renewal:</strong> <?php echo date('M-Y', strtotime($renewal)); ?>
          </div>
          <div style="margin-top:30px; text-align:center;">
            <span style="background:linear-gradient(90deg, orange, yellow); color:#fff; font-size:1.3em; padding:10px 30px; border-radius:10px; font-family:'Poppins',sans-serif;">
              C<span style="color:red;">B</span>R - Continuously Bringing-up Reforms
            </span>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <!-- Reports Content -->
    <div class="dashboard-grid">
      <div class="card">
        <!-- Dashboard card content will go here -->
      </div>
      <div class="card">
        <img src="../assets/" alt="">
        <!-- Dashboard card content will go here -->
      </div>
      <div class="card">
        <img src="../assets/" alt="">
        <!-- Dashboard card content will go here -->
      </div>
      <div class="card">
        <img src="../assets/" alt="">
        <!-- Dashboard card content will go here -->
      </div>
      <div class="card">
        <img src="../assets/" alt="">
        <!-- Dashboard card content will go here -->
      </div>
      <div class="card">
        <img src="../assets/" alt="">
        <!-- Dashboard card content will go here -->
      </div>
    </div>
  </main>

</body>
</html>
