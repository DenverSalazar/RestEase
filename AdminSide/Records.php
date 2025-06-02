<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>RestEase Admin Dashboard</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/Analytics.css">
  <link rel="stylesheet" href="../css/sidebar.css">
  <style>
    .cemetery-masterlist-container {
      margin-left: 50px;
      margin-top: 30px;
      padding: 0 32px;
      font-family: 'Inter', sans-serif;
    }
    .cemetery-masterlist-title {
      font-size: 2rem;
      font-weight: 600;
      margin-bottom: 0.25rem;
    }
    .cemetery-masterlist-desc {
      font-size: 1rem;
      color: #555;
      margin-bottom: 1.5rem;
    }
    .cemetery-masterlist-controls {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1rem;
      flex-wrap: wrap;
      gap: 10px;
    }
    .search-container {
      flex: 1 1 320px;
      max-width: 420px;
      display: flex;
      align-items: center;
      background: #fff;
      border-radius: 10px;
      border: 1.5px solid #bfc8d2; /* Faded, lighter border */
      padding: 0 16px;
      height: 40px;
      min-width: 320px;
      box-shadow: 0 1px 4px rgba(60,72,88,0.03);
    }
    .search-container i {
      color: #b0b0b0;
      margin-right: 8px;
      font-size: 1.1rem;
    }
    .search-container input {
      border: none;
      background: transparent;
      outline: none;
      font-size: 1.04rem;
      width: 100%;
      color: #222;
      font-weight: 400;
      padding: 0;
      margin: 0;
    }
    .search-container input::placeholder {
      color: #b0b0b0;
      font-weight: 400;
      opacity: 1;
    }
    .cemetery-masterlist-actions button {
      background: #fff;
      border: 1px solid #ddd;
      border-radius: 8px;
      padding: 7px 18px;
      font-size: 1rem;
      margin-left: 8px;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: background 0.2s;
    }
    .cemetery-masterlist-actions button:hover {
      background: #f2f2f2;
    }
    .cemetery-masterlist-table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
      background: #fff;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 1px 4px rgba(0,0,0,0.04);
      margin-bottom: 1rem;
    }
    .cemetery-masterlist-table th, .cemetery-masterlist-table td {
      padding: 10px 12px;
      text-align: left;
      font-size: 0.98rem;
      border-bottom: 1px solid #eee;
      background: #fff;
    }
    .cemetery-masterlist-table th {
      background: #f7f8fa;
      font-weight: 500;
      color: #333;
    }
    .cemetery-masterlist-table tr:last-child td {
      border-bottom: none;
    }
    .cemetery-masterlist-pagination {
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 1rem;
      margin-bottom: 1rem;
    }
    .cemetery-masterlist-pagination button {
      border: 1px solid #ddd;
      background: #fff;
      border-radius: 6px;
      width: 32px;
      height: 32px;
      font-size: 1rem;
      cursor: pointer;
      color: #506C84;
      transition: background 0.2s;
    }
    .cemetery-masterlist-pagination button.active,
    .cemetery-masterlist-pagination button:focus {
      background: #506C84;
      color: #fff;
      border-color: #506C84;
    }
    .cemetery-masterlist-pagination button:disabled {
      color: #bbb;
      border-color: #eee;
      background: #fafbfc;
      cursor: not-allowed;
    }
    @media (max-width: 900px) {
      .cemetery-masterlist-container {
        margin-left: 0;
        padding: 0 10px;
      }
      .cemetery-masterlist-controls {
        flex-direction: column;
        align-items: stretch;
      }
      .cemetery-masterlist-search {
        width: 100%;
      }
    }
  </style>
</head>
<body>
  <!-- Sidebar -->
  <?php include '../Includes/sidebar.php'; ?>

  <!-- Main Content -->
  <main class="main-content">
    
    <!-- Cemetery Masterlist Section -->
    <div class="cemetery-masterlist-container">
      <div style="display: flex; align-items: center; justify-content: space-between;">
        <div class="cemetery-masterlist-title">Cemetery Masterlist</div>
        <div class="user-profile" style="display: flex; align-items: center;">
          <div class="notification-icon">
            <i class="fas fa-bell"></i>
            <span class="notification-badge">1</span>
          </div>
          <div class="profile-info" style="margin-left: 10px;">
            <img src="../assets/Default Image.jpg" alt="Profile" class="profile-avatar">
            <div>
              <div class="profile-name">Sybau</div>
              <div class="profile-role">Admin</div>
            </div>
          </div>
        </div>
      </div>
      <div class="cemetery-masterlist-desc">View all Records Information.</div>
      <div class="cemetery-masterlist-controls">
         <div class="search-container">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="Search">
      </div>
        <div class="cemetery-masterlist-actions">
          <a href="Insert.php"><button><i class="fas fa-plus"></i> Insert</button></a>
          <a href="ExportPDF.php" target="_blank"><button type="button"><i class="fas fa-file-pdf"></i> Export</button></a>
          <button><i class="fas fa-filter"></i> Filter</button>
        </div>
      </div>
      <div style="overflow-x:auto;">
        <table class="cemetery-masterlist-table">
          <thead>
            <tr>
              <th>Apt No.</th>
              <th>Name of Deceases</th>
              <th>Address of Deceased</th>
              <th>Informant Name</th>
              <th>Date Died</th>
              <th>Date Internment</th>
              <th>Validity</th>
            </tr>
          </thead>
          <tbody>
            <?php
            // Database connection (adjust credentials as needed)
            $conn = new mysqli("localhost", "root", "", "cemeterydb");
            if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

            $perPage = 10;
            $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
            if ($page < 1) $page = 1;

            // Get total records
            $totalResult = $conn->query("SELECT COUNT(*) as total FROM deceased");
            $totalRows = $totalResult ? (int)$totalResult->fetch_assoc()['total'] : 0;
            $totalPages = $totalRows > 0 ? ceil($totalRows / $perPage) : 1;
            if ($page > $totalPages) $page = $totalPages;

            $offset = ($page - 1) * $perPage;

            $result = $conn->query("SELECT nicheID, lastName, firstName, residency, informantName, dateDied, dateInternment FROM deceased ORDER BY id DESC LIMIT $perPage OFFSET $offset");
            if ($result && $result->num_rows > 0) {
              while ($row = $result->fetch_assoc()) {
                $name = htmlspecialchars($row['lastName'] . ', ' . $row['firstName']);
                $apt = htmlspecialchars($row['nicheID']);
                $residency = htmlspecialchars($row['residency']);
                $informant = htmlspecialchars($row['informantName']);
                $dateDied = htmlspecialchars($row['dateDied']);
                $dateInternment = htmlspecialchars($row['dateInternment']);
                // Calculate validity: 5 years after dateInternment
                $validity = '';
                if ($dateInternment && $dateInternment !== '0000-00-00') {
                  $dt = new DateTime($dateInternment);
                  $dt->modify('+5 years');
                  $validity = $dt->format('Y-m-d');
                }
                echo "<tr>
                  <td>{$apt}</td>
                  <td>{$name}</td>
                  <td>{$residency}</td>
                  <td>{$informant}</td>
                  <td>{$dateDied}</td>
                  <td>{$dateInternment}</td>
                  <td>{$validity}</td>
                </tr>";
              }
            } else {
              echo '<tr><td colspan="7" style="text-align:center;">No records found.</td></tr>';
            }
            $conn->close();
            ?>
          </tbody>
        </table>
      </div>
      <div class="cemetery-masterlist-pagination" style="justify-content: center;">
        <?php
        $baseUrl = strtok($_SERVER["REQUEST_URI"], '?');
        // Define as a closure so $baseUrl is available
        $pageLink = function($p, $label, $active = false, $disabled = false) use ($baseUrl) {
          $class = $active ? 'active' : '';
          $disabledAttr = $disabled ? 'disabled' : '';
          $url = $disabled ? '#' : htmlspecialchars($baseUrl . '?page=' . $p);
          echo "<button class='$class' $disabledAttr onclick='if(this.hasAttribute(\"disabled\"))return false;window.location=\"$url\";'>$label</button>";
        };
        // Previous button
        $pageLink($page-1, '&lt;', false, $page <= 1);
        // Page numbers (show up to 5 pages)
        $start = max(1, $page - 2);
        $end = min($totalPages, $page + 2);
        if ($start > 1) $pageLink(1, '1', $page == 1);
        if ($start > 2) echo "<span>...</span>";
        for ($i = $start; $i <= $end; $i++) {
          $pageLink($i, $i, $page == $i);
        }
        if ($end < $totalPages - 1) echo "<span>...</span>";
        if ($end < $totalPages) $pageLink($totalPages, $totalPages, $page == $totalPages);
        // Next button
        $pageLink($page+1, '&gt;', false, $page >= $totalPages);
        ?>
      </div>
      <div>
        <span>Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
      </div>
    </div>
  </main>
</body>
</html>
