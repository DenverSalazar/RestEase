<?php
$conn = new mysqli("localhost", "root", "", "cemeterydb");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) { echo "Invalid record ID."; exit; }

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nicheID = $_POST['nicheID'];
  $lastName = $_POST['lastName'];
  $firstName = $_POST['firstName'];
  $residency = $_POST['residency'];
  $informantName = $_POST['informantName'];
  $dateDied = $_POST['dateDied'];
  $dateInternment = $_POST['dateInternment'];
  $age = $_POST['age'];
  $born = $_POST['born'];
  $stmt = $conn->prepare("UPDATE deceased SET nicheID=?, lastName=?, firstName=?, residency=?, informantName=?, dateDied=?, dateInternment=?, age=?, born=? WHERE id=?");
  $stmt->bind_param('ssssssssis', $nicheID, $lastName, $firstName, $residency, $informantName, $dateDied, $dateInternment, $age, $born, $id);
  $stmt->execute();
  $stmt->close();
  header("Location: Records.php");
  exit;
}

// Fetch record
$stmt = $conn->prepare("SELECT * FROM deceased WHERE id=?");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
if (!$result || $result->num_rows == 0) { echo "Record not found."; exit; }
$row = $result->fetch_assoc();
$stmt->close();

// If 'age' and 'born' are not in your DB, calculate or leave blank
$age = isset($row['age']) ? $row['age'] : '';
$born = isset($row['born']) ? $row['born'] : '';

// Fetch available niches (reuse logic from InsertData.php)
$niches = [];
$nicheResult = $conn->query("SHOW TABLES LIKE 'niche'");
if ($nicheResult && $nicheResult->num_rows > 0) {
  $nicheResult = $conn->query("SELECT nicheID FROM niche");
  while ($nicheRow = $nicheResult->fetch_assoc()) {
    $niches[] = $nicheRow['nicheID'];
  }
  $nicheDropdown = true;
} else {
  $nicheDropdown = false;
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Record</title>
  <link rel="stylesheet" href="../css/records.css">
  <link rel="stylesheet" href="../css/sidebar.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    body, html {
      font-family: 'Inter', sans-serif;
    }
    .edit-card {
      max-width: 1100px;
      margin: 40px auto;
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.07);
      padding: 40px 32px;
      margin-left: 400px; /* Move card to the right for sidebar (default sidebar width + gap) */
    }
    .edit-card h2 {
      margin-bottom: 32px;
      font-size: 1.5em;
      font-weight: 600;
      color: #2d3846;
    }
    .edit-form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 24px 32px;
    }
    .edit-form-group {
      display: flex;
      flex-direction: column;
    }
    .edit-form-group label {
      font-weight: 500;
      margin-bottom: 6px;
      color: #374151;
    }
    .edit-form-group input,
    .edit-form-group select {
      background: #f8fafc;
      border: 1px solid #e5e7eb;
      border-radius: 6px;
      padding: 10px 12px;
      font-size: 1em;
      color: #222;
      outline: none;
      transition: border 0.2s;
    }
    .edit-form-group input:focus,
    .edit-form-group select:focus {
      border-color: #1976d2;
    }
    .edit-form-actions {
      display: flex;
      justify-content: flex-end;
      gap: 16px;
      margin-top: 32px;
    }
    .edit-form-actions button {
      min-width: 120px;
      padding: 10px 0;
      border: none;
      border-radius: 8px;
      font-size: 1em;
      font-weight: 500;
      cursor: pointer;
      transition: background 0.2s;
    }
    .edit-form-actions .save-btn {
      background: #b6f5c3;
      color: #256029;
    }
    .edit-form-actions .save-btn:hover {
      background: #8ee6a3;
    }
    .edit-form-actions .cancel-btn {
      background: #ffd6d6;
      color: #b71c1c;
      display: inline-block;
      text-align: center;
      line-height: normal;
      padding: 10px 0;
      border-radius: 8px;
      min-width: 120px;
      font-size: 1em;
      font-weight: 500;
      text-decoration: none;
      border: none;
    }
    .edit-form-actions .cancel-btn:hover {
      background: #ffb3b3;
    }
    .apt-group {
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .apt-group .apt-icon {
      background: #e5e7eb;
      border: none;
      border-radius: 6px;
      padding: 8px 10px;
      font-size: 1.1em;
      color: #1976d2;
      cursor: default;
      display: flex;
      align-items: center;
      height: 38px;
    }
    @media (max-width: 900px) {
      .edit-form-grid { grid-template-columns: 1fr; }
      .edit-card { margin-left: 0; } /* Remove left margin on small screens */
    }
  </style>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
  <?php include '../Includes/sidebar.php'; ?>
  <div class="edit-card">
    <h2>Deceased Information</h2>
    <form method="post">
      <div class="edit-form-grid">
        <div class="edit-form-group">
          <label for="firstName">First Name</label>
          <input type="text" name="firstName" id="firstName" value="<?php echo htmlspecialchars($row['firstName']); ?>" required>
        </div>
        <div class="edit-form-group">
          <label for="lastName">Last Name</label>
          <input type="text" name="lastName" id="lastName" value="<?php echo htmlspecialchars($row['lastName']); ?>" required>
        </div>
        <div class="edit-form-group">
          <label for="age">Age</label>
          <input type="number" name="age" id="age" value="<?php echo htmlspecialchars($age); ?>" min="0">
        </div>
        <div class="edit-form-group">
          <label for="born">Born</label>
          <input type="date" name="born" id="born" value="<?php echo htmlspecialchars($born); ?>">
        </div>
        <div class="edit-form-group">
          <label for="residency">Residency</label>
          <input type="text" name="residency" id="residency" value="<?php echo htmlspecialchars($row['residency']); ?>" required>
        </div>
        <div class="edit-form-group">
          <label for="dateDied">Date Died</label>
          <input type="date" name="dateDied" id="dateDied" value="<?php echo htmlspecialchars($row['dateDied']); ?>" required>
        </div>
        <div class="edit-form-group">
          <label for="dateInternment">Date of Internment</label>
          <input type="date" name="dateInternment" id="dateInternment" value="<?php echo htmlspecialchars($row['dateInternment']); ?>" required>
        </div>
        <div class="edit-form-group">
          <label for="nicheID">Apartment No.</label>
          <div class="apt-group">
            <?php if ($nicheDropdown && count($niches) > 0): ?>
              <select name="nicheID" id="nicheID" required>
                <?php
                foreach ($niches as $niche) {
                  $selected = ($niche == $row['nicheID']) ? 'selected' : '';
                  echo "<option value=\"" . htmlspecialchars($niche) . "\" $selected>" . htmlspecialchars($niche) . "</option>";
                }
                ?>
              </select>
            <?php else: ?>
              <input type="text" name="nicheID" id="nicheID" value="<?php echo htmlspecialchars($row['nicheID']); ?>" required>
            <?php endif; ?>
            <button type="button" id="pickNicheBtn" title="Pick Niche" style="background:#f5f7fa;border:1.5px solid #d3dbe2;border-radius:7px;padding:8px 14px;min-width:44px;height:38px;margin-left:4px;display:flex;align-items:center;justify-content:center;">
              <i class="fas fa-map-marker-alt"></i>
            </button>
            <span class="apt-icon" style="display:none;"><i class="fas fa-map-marker-alt"></i></span>
          </div>
          <?php if (!$nicheDropdown): ?>
            <div style="color:#b71c1c;font-size:0.95em;margin-top:4px;">
              Niche table not found. Please enter Apt No.
            </div>
          <?php endif; ?>
        </div>
        <div class="edit-form-group">
          <label for="informantName">Informant Name</label>
          <input type="text" name="informantName" id="informantName" value="<?php echo htmlspecialchars($row['informantName']); ?>" required>
        </div>
      </div>
      <div class="edit-form-actions">
        <button type="submit" class="save-btn">Save</button>
        <a href="Records.php" class="cancel-btn" style="display:inline-block;text-align:center;line-height:normal;padding:10px 0;border-radius:8px;min-width:120px;font-size:1em;font-weight:500;text-decoration:none;border:none;">Cancel</a>
      </div>
    </form>
  </div>
  <script>
    document.getElementById('pickNicheBtn').onclick = function() {
      window.open('Mapping.php?pickNiche=1', 'PickNiche', 'width=900,height=700');
    };
    window.addEventListener('message', function(event) {
      if (event.data && event.data.nicheID) {
        var nicheInput = document.getElementById('nicheID');
        if (nicheInput.tagName === 'SELECT') {
          // If dropdown, select the option if it exists, else add it
          var found = false;
          for (var i = 0; i < nicheInput.options.length; i++) {
            if (nicheInput.options[i].value === event.data.nicheID) {
              nicheInput.selectedIndex = i;
              found = true;
              break;
            }
          }
          if (!found) {
            var opt = document.createElement('option');
            opt.value = event.data.nicheID;
            opt.text = event.data.nicheID;
            opt.selected = true;
            nicheInput.add(opt);
          }
        } else {
          nicheInput.value = event.data.nicheID;
        }
      }
    });
  </script>
</body>
</html>
