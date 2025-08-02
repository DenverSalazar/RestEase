<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>RestEase Admin Dashboard</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/Analytics.css">
  <link rel="stylesheet" href="../css/sidebar.css">
  <!-- DataTables CSS -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
  <style>
    .cemetery-masterlist-container {
      margin-left: 50px;
      margin-top: 30px;
      padding: 0 32px;
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
      border: 1.5px solid #bfc8d2;
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
      font-family: 'Poppins', sans-serif;
    }
    .search-container input::placeholder {
      color: #b0b0b0;
      font-weight: 400;
      opacity: 1;
      font-family: 'Poppins', sans-serif;
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
      font-family: 'Poppins', sans-serif;
    }
    .cemetery-masterlist-actions button:hover {
      background: #f2f2f2;
    }
    .cemetery-masterlist-actions .export-btn {
      background: #2563eb !important;
      color: #fff !important;
      border: none !important;
    }
    .cemetery-masterlist-actions .export-btn:hover,
    .cemetery-masterlist-actions .export-btn:focus {
      background: #0f2e71ff !important;
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
      font-family: 'Poppins', sans-serif;
    }
    .cemetery-masterlist-table th, .cemetery-masterlist-table td {
      padding: 10px 12px;
      text-align: left;
      font-size: 0.98rem;
      border-bottom: 1px solid #eee;
      background: #fff;
      font-family: 'Poppins', sans-serif;
    }
    .cemetery-masterlist-table th {
      background: #f7f8fa;
      font-weight: 500;
      color: #333;
    }
    .cemetery-masterlist-table tr:last-child td {
      border-bottom: none;
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
        </div>
      </div>
      <div class="cemetery-masterlist-desc">View all Records Information.</div>
      <div class="cemetery-masterlist-controls">
         <div class="search-container">
        <i class="fas fa-search"></i>
        <input type="text" id="search-input" placeholder="Search" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
      </div>
        <div class="cemetery-masterlist-actions">
          <a href="Insert.php"><button><i class="fas fa-plus"></i> Insert</button></a>
          <a href="ExportPDF.php" target="_blank"><button type="button" class="export-btn"><i class="fas fa-file-pdf"></i> Print Masterlist</button></a>
          <button id="delete-toggle-btn" type="button"><i class="fas fa-trash"></i> Delete</button>
        </div>
      </div>
      <!-- Filter Modal -->
      <div class="modal-overlay" id="filterModalOverlay" style="display:none;">
        <div class="modal-confirm" style="max-width:340px;">
          <h2 style="color:rgb(122, 157, 192);"><i class="fas fa-filter"></i> Filter & Sort</h2>
          <form id="filterForm" style="margin-top:18px;">
            <div style="margin-bottom:16px;">
              <label for="filterField" style="font-weight:500;">Field:</label>
              <select id="filterField" name="filterField" style="margin-left:8px;padding:4px 8px;">
                <option value="nicheID">Apt No.</option>
                <option value="lastName">Last Name</option>
                <option value="firstName">First Name</option>
                <option value="age">Age</option>
                <option value="born">Date of Birth</option>
                <option value="residency">Address</option>
                <option value="informantName">Informant Name</option>
                <option value="dateDied">Date Died</option>
                <option value="dateInternment">Date Internment</option>
              </select>
            </div>
            <div style="margin-bottom:18px;">
              <label for="filterOrder" style="font-weight:500;">Order:</label>
              <select id="filterOrder" name="filterOrder" style="margin-left:8px;padding:4px 8px;">
                <option value="asc">Ascending</option>
                <option value="desc">Descending</option>
              </select>
            </div>
            <div class="modal-actions" style="justify-content:flex-end;">
              <button type="button" class="modal-btn cancel" id="filterCancelBtn">Cancel</button>
              <button type="submit" class="modal-btn confirm" style="background:rgb(122, 157, 192);color:#fff;">Apply</button>
            </div>
          </form>
        </div>
      </div>
      <?php if (isset($_GET['filterField']) && isset($_GET['filterOrder']) && $_GET['filterField'] !== '' && $_GET['filterOrder'] !== ''): ?>
        <div style="margin: 10px 0 0 0; font-size: 1rem; color:rgb(122, 157, 192); font-weight: 500;">
          Filtered by: 
          <?php
            $fieldLabels = [
              'nicheID' => 'Apt No.',
              'lastName' => 'Last Name',
              'firstName' => 'First Name',
              'age' => 'Age',
              'born' => 'Date of Birth',
              'residency' => 'Address',
              'informantName' => 'Informant Name',
              'dateDied' => 'Date Died',
              'dateInternment' => 'Date Internment'
            ];
            $f = $_GET['filterField'];
            $o = strtolower($_GET['filterOrder']);
            echo isset($fieldLabels[$f]) ? $fieldLabels[$f] : htmlspecialchars($f);
          ?>
          &nbsp;|&nbsp; Order: <?php echo $o === 'asc' ? 'Ascending' : 'Descending'; ?>
        </div>
      <?php endif; ?>
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
              <th class="delete-checkbox-col" id="delete-checkbox-header">
                <input type="checkbox" id="select-all-checkbox" style="display:none;">
              </th>
            </tr>
          </thead>
          <tbody>
            <?php
            // Handle deletion POST
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_ids']) && is_array($_POST['delete_ids'])) {
              include_once '../Includes/db.php';
              if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }
              // Ensure archive_deceased table exists
              $conn->query("CREATE TABLE IF NOT EXISTS archive_deceased LIKE deceased");
              $ids = array_map('intval', $_POST['delete_ids']);
              $idsList = implode(',', $ids);
              // Move to archive_deceased
              $conn->query("INSERT INTO archive_deceased (nicheID, lastName, firstName, age, born, residency, informantName, dateDied, dateInternment)
                SELECT nicheID, lastName, firstName, age, born, residency, informantName, dateDied, dateInternment FROM deceased WHERE id IN ($idsList)");
              // Delete from deceased
              $conn->query("DELETE FROM deceased WHERE id IN ($idsList)");
              $conn->close();
              echo "<script>window.location.href=window.location.pathname+'?deleted=1';</script>";
              exit;
            }
            include_once '../Includes/db.php';
            if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }
            // Fetch all records (no pagination/search/filter)
            $result = $conn->query("SELECT id, nicheID, lastName, firstName, age, born, residency, informantName, dateDied, dateInternment FROM deceased");
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
                // Prepare query params for edit
                $queryParams = http_build_query([
                  'id' => $row['id'],
                  'nicheID' => $row['nicheID'],
                  'lastName' => $row['lastName'],
                  'firstName' => $row['firstName'],
                  'age' => $row['age'],
                  'born' => $row['born'],
                  'residency' => $row['residency'],
                  'informantName' => $row['informantName'],
                  'dateDied' => $row['dateDied'],
                  'dateInternment' => $row['dateInternment']
                ]);
                // Add a data-href attribute for JS navigation
                echo "<tr class='record-row' data-href='EditNiches.php?{$queryParams}' style='cursor:pointer;'>
                  <td>{$apt}</td>
                  <td>{$name}</td>
                  <td>{$age}</td>
                  <td>{$born}</td>
                  <td>{$residency}</td>
                  <td>{$informant}</td>
                  <td>{$dateDied}</td>
                  <td>{$dateInternment}</td>
                  <td>{$validity}</td>
                  <td class='delete-checkbox-col'><input type='checkbox' class='delete-checkbox' name='delete_ids[]' value='{$row['id']}'></td>
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
      <!-- DataTables JS -->
      <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
      <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
      <script>
        $(document).ready(function() {
          $('#records-table').DataTable({
            "paging": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "columnDefs": [
              { "orderable": false, "targets": [9] } // Disable sort for delete checkbox column
            ]
          });
          // Toggle delete mode
          const deleteBtn = document.getElementById('delete-toggle-btn');
          const table = document.getElementById('records-table');
          const deleteCheckboxCols = table.querySelectorAll('.delete-checkbox-col');
          const deleteCheckboxHeader = document.getElementById('delete-checkbox-header');
          const deleteForm = document.getElementById('delete-form');
          const selectAllCheckbox = document.getElementById('select-all-checkbox');
          let deleteMode = false;

          function setDeleteMode(on) {
            deleteMode = on;
            // Show/hide checkbox columns
            deleteCheckboxCols.forEach(col => col.style.display = on ? '' : 'none');
            if (deleteCheckboxHeader) {
              deleteCheckboxHeader.style.display = on ? '' : 'none';
              if (selectAllCheckbox) selectAllCheckbox.style.display = on ? '' : 'none';
            }
            // Uncheck all checkboxes when exiting delete mode
            if (!on) {
              table.querySelectorAll('.delete-checkbox').forEach(cb => cb.checked = false);
              if (selectAllCheckbox) selectAllCheckbox.checked = false;
            }
          }

          // Initial state: hide checkboxes
          setDeleteMode(false);

          // Select All logic
          if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
              const checkboxes = table.querySelectorAll('.delete-checkbox');
              checkboxes.forEach(cb => cb.checked = selectAllCheckbox.checked);
            });
            // Keep select-all in sync if user manually checks/unchecks
            table.addEventListener('change', function(e) {
              if (e.target.classList.contains('delete-checkbox')) {
                const checkboxes = table.querySelectorAll('.delete-checkbox');
                const checked = table.querySelectorAll('.delete-checkbox:checked');
                selectAllCheckbox.checked = (checkboxes.length > 0 && checked.length === checkboxes.length);
              }
            });
          }

          // Custom modal logic
          const modalOverlay = document.getElementById('modalOverlay');
          const modalConfirmBtn = document.getElementById('modalConfirmBtn');
          const modalCancelBtn = document.getElementById('modalCancelBtn');
          // Update modal text for plural/singular
          const modalText = modalOverlay.querySelector('p');

          deleteBtn.addEventListener('click', function(e) {
            if (!deleteMode) {
              setDeleteMode(true);
              // Change button style to indicate active delete mode
              deleteBtn.classList.add('export-btn');
              deleteBtn.innerHTML = '<i class="fas fa-trash"></i> Delete';
            } else {
              // Check if any checkbox is checked
              const checked = table.querySelectorAll('.delete-checkbox:checked');
              if (checked.length === 0) {
                // Exit delete mode if nothing selected
                setDeleteMode(false);
                deleteBtn.classList.remove('export-btn');
                deleteBtn.innerHTML = '<i class="fas fa-trash"></i> Delete';
                return;
              }
              // Update modal text for plural/singular
              if (modalText) {
                modalText.innerHTML = `Are you sure you want to delete ${checked.length > 1 ? 'these records' : 'this record'}?<br>This action cannot be undone.`;
              }
              // Show custom confirmation modal
              modalOverlay.style.display = 'flex';
            }
          });

          modalCancelBtn.addEventListener('click', function() {
            modalOverlay.style.display = 'none';
          });

          modalConfirmBtn.addEventListener('click', function() {
            modalOverlay.style.display = 'none';
            // Submit the form
            deleteForm.submit();
          });

          // Optional: clicking outside modal closes it
          modalOverlay.addEventListener('click', function(e) {
            if (e.target === modalOverlay) modalOverlay.style.display = 'none';
          });

          // Reset delete button and mode after form submit or page reload
          window.addEventListener('DOMContentLoaded', function() {
            setDeleteMode(false);
            deleteBtn.classList.remove('export-btn');
            deleteBtn.innerHTML = '<i class="fas fa-trash"></i> Delete';
          });

          // Make table rows clickable for editing
          document.addEventListener('DOMContentLoaded', function() {
            // Add click event to each record row (except when clicking a checkbox)
            document.querySelectorAll('.record-row').forEach(function(row) {
              row.addEventListener('click', function(e) {
                // Prevent navigation if clicking on a checkbox
                if (e.target.classList.contains('delete-checkbox')) return;
                // Prevent navigation if in delete mode
                if (deleteMode) return;
                window.location = row.getAttribute('data-href');
              });
            });
          });

          // Search bar submit on enter or change
          document.getElementById('search-input').addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
              e.preventDefault();
              submitSearch();
            }
          });
          document.getElementById('search-input').addEventListener('change', function() {
            submitSearch();
          });
          function submitSearch() {
            const val = document.getElementById('search-input').value;
            const params = new URLSearchParams(window.location.search);
            if (val) {
              params.set('search', val);
              params.set('page', 1); // Reset to first page on new search
            } else {
              params.delete('search');
              params.set('page', 1);
            }
            window.location.search = params.toString();
          }

          // Filter modal logic
          const filterBtn = document.getElementById('filter-btn');
          const filterModalOverlay = document.getElementById('filterModalOverlay');
          const filterCancelBtn = document.getElementById('filterCancelBtn');
          const filterForm = document.getElementById('filterForm');
          // Set initial filter values from URL
          document.addEventListener('DOMContentLoaded', function() {
            const params = new URLSearchParams(window.location.search);
            if (params.has('filterField')) {
              document.getElementById('filterField').value = params.get('filterField');
            }
            if (params.has('filterOrder')) {
              document.getElementById('filterOrder').value = params.get('filterOrder');
            }
          });
          filterBtn.onclick = function() {
            filterModalOverlay.style.display = 'flex';
          };
          filterCancelBtn.onclick = function() {
            filterModalOverlay.style.display = 'none';
          };
          filterForm.onsubmit = function(e) {
            e.preventDefault();
            const field = document.getElementById('filterField').value;
            const order = document.getElementById('filterOrder').value;
            const params = new URLSearchParams(window.location.search);
            params.set('filterField', field);
            params.set('filterOrder', order);
            params.set('page', 1);
            window.location.search = params.toString();
          };
          // Optional: close filter modal on overlay click
          filterModalOverlay.onclick = function(e) {
            if (e.target === filterModalOverlay) filterModalOverlay.style.display = 'none';
          };
        });
      </script>
      <!-- Remove custom pagination/search/filter HTML/JS -->
    </div>
  </main>
</body>
</html>