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
    .cemetery-masterlist-actions .export-btn {
      background: #e57373 !important; /* desaturated red */
      color: #fff !important;
      border: none !important;
    }
    .cemetery-masterlist-actions .export-btn:hover,
    .cemetery-masterlist-actions .export-btn:focus {
      background: #d06060 !important;
      color: #fff !important;
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
          <a href="ExportPDF.php" target="_blank"><button type="button" class="export-btn"><i class="fas fa-file-pdf"></i> Print Masterlist</button></a>
          <button><i class="fas fa-filter"></i> Filter</button>
          <button id="delete-toggle-btn" type="button"><i class="fas fa-trash"></i> Delete</button>
        </div>
      </div>
      <form id="delete-form" method="post" style="margin:0;">
      <div style="overflow-x:auto;">
        <table class="cemetery-masterlist-table" id="records-table">
          <thead>
            <tr>
              <th>Apt No.</th>
              <th>Name of Deceases</th>
              <th>Age</th>
              <th>Date of Birth</th>
              <th>Address of Deceased</th>
              <th>Informant Name</th>
              <th>Date Died</th>
              <th>Date Internment</th>
              <th>Validity</th>
              <th class="delete-checkbox-col" style="display:none;" id="delete-checkbox-header"></th>
            </tr>
          </thead>
          <tbody>
            <?php
            // Handle deletion POST
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_ids']) && is_array($_POST['delete_ids'])) {
              $conn = new mysqli("localhost", "root", "", "cemeterydb");
              if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }
              // Ensure archive_deceased table exists
              $conn->query("CREATE TABLE IF NOT EXISTS archive_deceased LIKE deceased");
              $ids = array_map('intval', $_POST['delete_ids']);
              $idsList = implode(',', $ids);
              // Move to archive_deceased
              $conn->query("INSERT INTO archive_deceased (nicheID, lastName, firstName, residency, informantName, dateDied, dateInternment)
                SELECT nicheID, lastName, firstName, residency, informantName, dateDied, dateInternment FROM deceased WHERE id IN ($idsList)");
              // Delete from deceased
              $conn->query("DELETE FROM deceased WHERE id IN ($idsList)");
              $conn->close();
              echo "<script>window.location.href=window.location.pathname+'?deleted=1';</script>";
              exit;
            }
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

            $result = $conn->query("SELECT id, nicheID, lastName, firstName, age, born, residency, informantName, dateDied, dateInternment FROM deceased ORDER BY id DESC LIMIT $perPage OFFSET $offset");
            if ($result && $result->num_rows > 0) {
              while ($row = $result->fetch_assoc()) {
                $name = htmlspecialchars($row['lastName'] . ', ' . $row['firstName']);
                $apt = htmlspecialchars($row['nicheID']);
                $age = htmlspecialchars($row['age']);
                $born = htmlspecialchars($row['born']);
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
                  <td>{$age}</td>
                  <td>{$born}</td>
                  <td>{$residency}</td>
                  <td>{$informant}</td>
                  <td>{$dateDied}</td>
                  <td>{$dateInternment}</td>
                  <td>{$validity}</td>
                  <td class='delete-checkbox-col' style='display:none;'><input type='checkbox' class='delete-checkbox' name='delete_ids[]' value='{$row['id']}'></td>
                </tr>";
              }
            } else {
              echo '<tr><td colspan="10" style="text-align:center;">No records found.</td></tr>';
            }
            $conn->close();
            ?>
          </tbody>
        </table>
      </div>
      </form>
      <style>
        .delete-checkbox-col {
          width: 40px;
          text-align: center;
        }
        .delete-checkbox {
          transform: scale(1.2);
          cursor: pointer;
        }
        /* Confirmation Modal Styles */
        .modal-confirm-bg {
          display: none;
          position: fixed;
          z-index: 9999;
          left: 0; top: 0; right: 0; bottom: 0;
          background: rgba(0,0,0,0.35);
        }
        .modal-confirm-box {
          position: absolute;
          top: 50%; left: 50%;
          transform: translate(-50%, -50%);
          background: #fff;
          border-radius: 12px;
          box-shadow: 0 4px 24px rgba(0,0,0,0.13);
          padding: 28px 20px 20px 20px;
          min-width: 220px;
          max-width: 320px;
          width: 95vw;
          text-align: center;
        }
        .modal-confirm-icon {
          font-size: 2.6rem;
          color: #e57373;
          margin-bottom: 12px;
        }
        .modal-confirm-title {
          font-size: 1.25rem;
          font-weight: 600;
          margin-bottom: 8px;
        }
        .modal-confirm-msg {
          font-size: 1rem;
          color: #444;
          margin-bottom: 22px;
        }
        .modal-confirm-actions {
          display: flex;
          justify-content: center;
          gap: 18px;
        }
        .modal-btn-yes {
          background: #e57373;
          color: #fff;
          border: none;
          border-radius: 6px;
          padding: 8px 28px;
          font-size: 1rem;
          font-weight: 500;
          cursor: pointer;
          transition: background 0.18s;
        }
        .modal-btn-yes:hover {
          background: #d06060;
        }
        .modal-btn-no {
          background: #e0e0e0;
          color: #444;
          border: none;
          border-radius: 6px;
          padding: 8px 28px;
          font-size: 1rem;
          font-weight: 500;
          cursor: pointer;
          transition: background 0.18s;
        }
        .modal-btn-no:hover {
          background: #cccccc;
        }
      </style>
      <!-- Confirmation Modal and deletion logic removed -->
      <script>
        // All deletion logic removed
      </script>
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
