<?php
// Handle deletion logic before any output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_action'])) {
  $conn = new mysqli("localhost", "root", "", "cemeterydb");
  if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }
  if ($_POST['delete_action'] === 'selected' && !empty($_POST['selected_ids'])) {
    $ids = array_map('intval', $_POST['selected_ids']);
    $idList = implode(',', $ids);
    // Move to archive_deceased
    $conn->query("INSERT INTO archive_deceased SELECT * FROM deceased WHERE id IN ($idList)");
    // Delete from deceased
    $conn->query("DELETE FROM deceased WHERE id IN ($idList)");
  } elseif ($_POST['delete_action'] === 'all') {
    $conn->query("INSERT INTO archive_deceased SELECT * FROM deceased");
    $conn->query("DELETE FROM deceased");
  }
  $conn->close();
  // Redirect to avoid resubmission
  header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
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
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/Analytics.css">
  <link rel="stylesheet" href="../css/sidebar.css">
  <link rel="stylesheet" href="../css/records.css">
  <style>
    /* ...existing code... */
    .clickable-row { cursor: pointer; }
    .clickable-row:hover { background: #f5f5f5; }
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
          <a href="ExportPDF.php" target="_blank"><button type="button" class="export-btn"><i class="fas fa-file-pdf"></i> Export</button></a>
          <button><i class="fas fa-filter"></i> Filter</button>
          <!-- Activation Delete button -->
          <button type="button" id="activateDeleteBtn" style="background:#e57373;color:#fff;border:none;"><i class="fas fa-trash"></i> Delete</button>
          <!-- Delete action buttons, hidden by default -->
          <form id="deleteForm" method="post" style="display:inline;display:none;">
            <input type="hidden" name="delete_action" id="delete_action" value="">
            <button type="button" onclick="submitDelete('selected')" style="background:#e57373;color:#fff;border:none;"><i class="fas fa-trash"></i> Delete Selected</button>
            <button type="button" onclick="if(confirm('Delete ALL records?')) submitDelete('all');" style="background:#b71c1c;color:#fff;border:none;"><i class="fas fa-trash-alt"></i> Delete All</button>
            <button type="button" id="cancelDeleteBtn" style="background:#888;color:#fff;border:none;"><i class="fas fa-times"></i> Cancel</button>
          </form>
        </div>
      </div>
      <div style="overflow-x:auto;">
        <form id="recordsForm" method="post">
        <table class="cemetery-masterlist-table">
          <thead>
            <tr>
              <th class="delete-col" style="display:none;"><input type="checkbox" id="selectAll"></th>
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

            $result = $conn->query("SELECT id, nicheID, lastName, firstName, residency, informantName, dateDied, dateInternment FROM deceased ORDER BY id DESC LIMIT $perPage OFFSET $offset");
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
                $id = (int)$row['id'];
                echo "<tr class='clickable-row' data-id='{$id}'>
                  <td class='delete-col' style='display:none;'><input type='checkbox' name='selected_ids[]' value='{$id}' class='rowCheckbox'></td>
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
              echo '<tr><td colspan="8" style="text-align:center;">No records found.</td></tr>';
            }
            $conn->close();
            ?>
          </tbody>
        </table>
        </form>
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
  <script>
        // Select all checkboxes
        document.getElementById('selectAll').addEventListener('change', function() {
          var checked = this.checked;
          document.querySelectorAll('.rowCheckbox').forEach(function(cb) {
            cb.checked = checked;
          });
        });

        // Delete logic
        function submitDelete(action) {
          document.getElementById('delete_action').value = action;
          if (action === 'selected') {
            // Move selected checkboxes to deleteForm
            var form = document.getElementById('deleteForm');
            var recordsForm = document.getElementById('recordsForm');
            // Remove old hidden inputs
            Array.from(form.querySelectorAll('input[name="selected_ids[]"]')).forEach(e => e.remove());
            // Add checked
            var checked = recordsForm.querySelectorAll('.rowCheckbox:checked');
            if (checked.length === 0) {
              alert('Please select at least one record to delete.');
              return;
            }
            checked.forEach(function(cb) {
              var input = document.createElement('input');
              input.type = 'hidden';
              input.name = 'selected_ids[]';
              input.value = cb.value;
              form.appendChild(input);
            });
            if (!confirm('Delete selected records?')) return;
          }
          document.getElementById('deleteForm').submit();
        }

        // Delete mode activation/deactivation
        document.getElementById('activateDeleteBtn').addEventListener('click', function() {
          // Show checkboxes
          document.querySelectorAll('.delete-col').forEach(function(el) {
            el.style.display = '';
          });
          // Show delete action buttons
          document.getElementById('deleteForm').style.display = 'inline';
          // Hide activation button
          document.getElementById('activateDeleteBtn').style.display = 'none';
        });
        document.getElementById('cancelDeleteBtn').addEventListener('click', function() {
          // Hide checkboxes
          document.querySelectorAll('.delete-col').forEach(function(el) {
            el.style.display = 'none';
          });
          // Hide delete action buttons
          document.getElementById('deleteForm').style.display = 'none';
          // Uncheck all checkboxes
          document.getElementById('selectAll').checked = false;
          document.querySelectorAll('.rowCheckbox').forEach(function(cb) {
            cb.checked = false;
          });
          // Show activation button
          document.getElementById('activateDeleteBtn').style.display = '';
        });

        // On page load, ensure delete mode is off
        window.addEventListener('DOMContentLoaded', function() {
          document.querySelectorAll('.delete-col').forEach(function(el) {
            el.style.display = 'none';
          });
          document.getElementById('deleteForm').style.display = 'none';
          document.getElementById('activateDeleteBtn').style.display = '';
        });

        // Make table rows clickable
        document.addEventListener('DOMContentLoaded', function() {
          document.querySelectorAll('.clickable-row').forEach(function(row) {
            row.addEventListener('click', function(e) {
              // Prevent click if clicking on a checkbox
              if (e.target.tagName === 'INPUT' && e.target.type === 'checkbox') return;
              var id = this.getAttribute('data-id');
              if (id) {
                window.location.href = 'EditRecord.php?id=' + id;
              }
            });
          });
        });
  </script>
</body>
</html>
