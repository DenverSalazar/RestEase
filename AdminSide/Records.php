<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../AdminLogin.php");
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
  <link rel="stylesheet" href="../css/Analytics.css">
  <link rel="stylesheet" href="../css/sidebar.css">
  <link rel="stylesheet" href="../css/Records.css">
  <link rel="stylesheet" href="../css/header.css">
  <!-- DataTables CSS -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
  <style>
    /* Highlight validity dates for upcoming expiring records (top 10) */
    .validity-expiring {
      color: #e74c3c;
      font-weight: 600;
    }
  </style>
</head>
<body>
  <!-- Sidebar -->
  <?php include '../Includes/sidebar.php'; ?>
   <?php include '../Includes/header.php'; ?>


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
          <a href="Insert.php?from=records"><button><i class="fas fa-plus"></i> Insert</button></a>
          <a href="ExportExcel.php" target="_blank"><button type="button" class="export-btn"><i class="fas fa-file-excel"></i> Export Data</button></a>
          <button id="delete-toggle-btn" type="button"><i class="fas fa-trash"></i> Delete</button>
        </div>
      </div>
      <!-- Delete Confirmation Modal -->
      <div id="deleteModal" class="modal-overlay" style="display:none;">
        <div class="modal-content">
          <div class="modal-header">
            <i class="fas fa-exclamation-triangle" style="color:#e74c3c;font-size:2rem;margin-bottom:8px;"></i>
            <h2 style="color:#e74c3c;margin:0;font-size:1.3rem;">Confirm Archive</h2>
          </div>
          <div class="modal-body" style="margin:18px 0 24px 0;">
            <p id="deleteModalText" style="color:#444;font-size:1.07rem;margin:0;">
              Are you sure you want to archive this record?<br>
              This action will move the record to the archive section.
            </p>
          </div>
          <div class="modal-footer" style="display:flex;justify-content:center;gap:16px;">
            <button id="modalDeleteBtn" class="modal-delete-btn">Archive</button>
            <button id="modalCancelBtn" class="modal-cancel-btn">Cancel</button>
          </div>
        </div>
      </div>

      <!-- Success Notification -->
      <div id="successNotification" style="display:none;position:fixed;top:32px;right:32px;z-index:10000;background:#2ecc71;color:#fff;padding:18px 32px;border-radius:8px;box-shadow:0 4px 16px rgba(46,204,113,0.15);font-size:1.1rem;font-weight:500;align-items:center;gap:16px;min-width:220px;">
        <span><i class="fas fa-check-circle" style="margin-right:8px;"></i><span id="notificationText">Records successfully archived.</span></span>
        <button id="closeNotificationBtn" style="background:none;border:none;color:#fff;font-size:1.2em;cursor:pointer;margin-left:12px;">&times;</button>
      </div>
      <form id="delete-form" method="post" style="margin:0;">
      <div style="overflow-x:auto;">
        <table class="cemetery-masterlist-table" id="records-table">
          <thead>
            <tr>
              <th>Apt No.</th>
              <th>Name of Deceased</th>
              <th>Age</th>
              <th>Date of Birth</th>
              <th>Address of Deceased</th>
              <th>Informant Name</th>
              <th>Date Died</th>
              <th>Date Internment</th>
              <th>Validity</th>
              <th>Edit</th>
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
              
              $deleteIds = array_map('intval', $_POST['delete_ids']);
              $placeholders = str_repeat('?,', count($deleteIds) - 1) . '?';
              
              // Move to archive (map columns explicitly)
              $sql = "INSERT INTO archive_deceased (id, firstName, lastName, age, born, residency, dateDied, dateInternment, nicheID, informantName) SELECT id, firstName, lastName, age, born, residency, dateDied, dateInternment, nicheID, informantName FROM deceased WHERE id IN ($placeholders)";
              $stmt = $conn->prepare($sql);
              $stmt->bind_param(str_repeat('i', count($deleteIds)), ...$deleteIds);
              $stmt->execute();
              
              // Delete from main table
              $stmt = $conn->prepare("DELETE FROM deceased WHERE id IN ($placeholders)");
              $stmt->bind_param(str_repeat('i', count($deleteIds)), ...$deleteIds);
              $stmt->execute();
              
              $conn->close();
              echo "<script>window.location.href = 'Records.php';</script>";
              exit;
            }
            
            include_once '../Includes/db.php';
            if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

            // --- New code: fetch top-10 upcoming validity expiries (validity = dateInternment + 5 years) ---
            $expiringIds = [];
            $today = date('Y-m-d');
            $expSql = "SELECT id, DATE_ADD(dateInternment, INTERVAL 5 YEAR) AS validity
                       FROM deceased
                       WHERE dateInternment IS NOT NULL
                         AND dateInternment != ''
                         AND dateInternment != '0000-00-00'
                         AND DATE_ADD(dateInternment, INTERVAL 5 YEAR) >= ?
                       ORDER BY validity ASC
                       LIMIT 10";
            if ($stmtExp = $conn->prepare($expSql)) {
                $stmtExp->bind_param('s', $today);
                $stmtExp->execute();
                $resExp = $stmtExp->get_result();
                while ($r = $resExp->fetch_assoc()) {
                    $expiringIds[intval($r['id'])] = true;
                }
                $stmtExp->close();
            }
            // --- End new code ---

            // Fetch all records (no pagination/search/filter)
            $result = $conn->query("SELECT id, nicheID, lastName, firstName, middleName, suffix, age, born, residency, informantName, dateDied, dateInternment FROM deceased");
            if ($result && $result->num_rows > 0) {
              while ($row = $result->fetch_assoc()) {
                // Compose name: LastName, FirstName MiddleInitial. Suffix
                $lastName = htmlspecialchars($row['lastName']);
                $firstName = htmlspecialchars($row['firstName']);
                $middleName = isset($row['middleName']) ? $row['middleName'] : '';
                $suffix = isset($row['suffix']) ? $row['suffix'] : '';
                $middleInitial = $middleName ? strtoupper(substr(trim($middleName), 0, 1)) . '.' : '';
                $name = $lastName . ', ' . $firstName;
                if ($middleInitial) $name .= ' ' . $middleInitial;
                if ($suffix) $name .= ' ' . htmlspecialchars($suffix);

                $apt = htmlspecialchars($row['nicheID']);
                $age = htmlspecialchars($row['age']);
                $born = htmlspecialchars($row['born']);
                $residency = htmlspecialchars($row['residency']);
                $informant = htmlspecialchars($row['informantName']);
                $dateDied = htmlspecialchars($row['dateDied']);
                $dateInternment = htmlspecialchars($row['dateInternment']);

                // Calculate validity date (5 years from internment)
                $validityDate = '';
                if (!empty($row['dateInternment']) && $row['dateInternment'] !== '0000-00-00') {
                  try {
                    $internmentDateObj = new DateTime($row['dateInternment']);
                    $internmentDateObj->modify('+5 years');
                    $validityDate = $internmentDateObj->format('Y-m-d');
                  } catch (Exception $e) {
                    $validityDate = '';
                  }
                }

                // If this record is in the top-10 upcoming expiries, wrap validity in red span
                $isExpiring = isset($expiringIds[intval($row['id'])]);
                $validityCell = $validityDate ? ($isExpiring ? "<span class='validity-expiring'>{$validityDate}</span>" : $validityDate) : '';

                // Build query parameters for EditNiches.php, include unique id and all fields
                $queryParams = http_build_query([
                  'id' => $row['id'],
                  'nicheID' => $row['nicheID'],
                  'lastName' => $row['lastName'],
                  'firstName' => $row['firstName'],
                  'middleName' => $row['middleName'],
                  'suffix' => $row['suffix'],
                  'age' => $row['age'],
                  'born' => $row['born'],
                  'residency' => $row['residency'],
                  'informantName' => $row['informantName'],
                  'dateDied' => $row['dateDied'],
                  'dateInternment' => $row['dateInternment'],
                  'from' => 'records'
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
                  <td>{$validityCell}</td>
                  <td><a href='EditNiches.php?{$queryParams}' class='edit-btn' title='Edit Record'><i class='fas fa-edit'></i></a></td>
                  <td class='delete-checkbox-col'><input type='checkbox' class='delete-checkbox' name='delete_ids[]' value='{$row['id']}'></td>
                </tr>";
              }
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
          const dataTable = $('#records-table').DataTable({
            "paging": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "dom": 'lrtip', // Remove 'f' (filter/search box)
            "columnDefs": [
              { "orderable": false, "targets": [9, 10] } // Both edit and checkbox columns non-sortable
            ]
          });

          // Connect upper search bar to DataTables search
          document.getElementById('search-input').addEventListener('keyup', function() {
            dataTable.search(this.value).draw();
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
            
            if (on) {
              // Show checkboxes on ALL rows using DataTables API
              $('#records-table').DataTable().column(10).visible(true); // Changed to column 10
              $('#records-table').DataTable().rows().nodes().to$().find('.delete-checkbox-col').show();
              if (selectAllCheckbox) selectAllCheckbox.style.display = '';
            } else {
              // Hide checkboxes on ALL rows
              $('#records-table').DataTable().column(10).visible(false); // Changed to column 10
              $('#records-table').DataTable().rows().nodes().to$().find('.delete-checkbox').prop('checked', false);
              if (selectAllCheckbox) selectAllCheckbox.checked = false;
            }
          }

          setDeleteMode(false);

          // Select All logic
          if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
              const checkboxes = table.querySelectorAll('.delete-checkbox');
              checkboxes.forEach(cb => cb.checked = selectAllCheckbox.checked);
            });
            
            table.addEventListener('change', function(e) {
              if (e.target.classList.contains('delete-checkbox')) {
                const checkboxes = table.querySelectorAll('.delete-checkbox');
                const checked = table.querySelectorAll('.delete-checkbox:checked');
                selectAllCheckbox.checked = (checkboxes.length > 0 && checked.length === checkboxes.length);
              }
            });
          }

          // Delete button click handler
          deleteBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (!deleteMode) {
              setDeleteMode(true);
              deleteBtn.classList.add('export-btn');
              deleteBtn.innerHTML = '<i class="fas fa-trash"></i> Delete';
            } else {
              const checked = table.querySelectorAll('.delete-checkbox:checked');
              if (checked.length === 0) {
                setDeleteMode(false);
                deleteBtn.classList.remove('export-btn');
                deleteBtn.innerHTML = '<i class="fas fa-trash"></i> Delete';
                return;
              }
              
              // Update modal text
              const modalText = document.getElementById('deleteModalText');
              if (modalText) {
                modalText.innerHTML = `Are you sure you want to archive ${checked.length > 1 ? 'these records' : 'this record'}?<br>This action will move the record${checked.length > 1 ? 's' : ''} to the archive section.`;
              }
              
              // Show modal
              document.getElementById('deleteModal').style.display = 'flex';
            }
          });

          // Modal handlers
          document.getElementById('modalCancelBtn').addEventListener('click', function() {
            document.getElementById('deleteModal').style.display = 'none';
          });

          document.getElementById('modalDeleteBtn').addEventListener('click', function() {
            const checked = table.querySelectorAll('.delete-checkbox:checked');
            if (checked.length === 0) return;
            
            const deleteModalBtn = this;
            const modal = document.getElementById('deleteModal');
            const cancelBtn = document.getElementById('modalCancelBtn');
            
            // Show loading state
            deleteModalBtn.disabled = true;
            deleteModalBtn.textContent = 'Archiving...';
            cancelBtn.disabled = true;
            
            // Collect IDs
            const deleteIds = Array.from(checked).map(cb => cb.value);
            
            // Create form data
            const formData = new FormData();
            deleteIds.forEach(id => {
              formData.append('delete_ids[]', id);
            });
            
            // Send request
            fetch('Records.php', {
              method: 'POST',
              body: formData
            })
            .then(response => {
              if (!response.ok) throw new Error('Network response was not ok');
              return response.text();
            })
            .then(data => {
              // Remove rows from table
              checked.forEach(cb => {
                const row = cb.closest('tr');
                if (row) row.remove();
              });
              
              // Show success notification
              const notification = document.getElementById('successNotification');
              const notificationText = document.getElementById('notificationText');
              notificationText.textContent = `${deleteIds.length} record${deleteIds.length > 1 ? 's' : ''} successfully archived.`;
              notification.style.display = 'flex';

              // Auto-hide notification after 3 seconds
              setTimeout(() => {
                notification.style.display = 'none';
              }, 3000);

              // Hide modal and reset
              modal.style.display = 'none';
              setDeleteMode(false);
              deleteBtn.classList.remove('export-btn');
              deleteBtn.innerHTML = '<i class="fas fa-trash"></i> Delete';
            })
            .catch(error => {
              console.error('Error:', error);
              alert('An error occurred while archiving. Please try again.');
            })
            .finally(() => {
              deleteModalBtn.disabled = false;
              deleteModalBtn.textContent = 'Archive';
              cancelBtn.disabled = false;
            });
          });

          // Close notification
          let notificationTimeout;
          document.getElementById('closeNotificationBtn').addEventListener('click', function() {
            document.getElementById('successNotification').style.display = 'none';
            if (notificationTimeout) {
              clearTimeout(notificationTimeout);
            }
          });

          // Close modal on overlay click
          document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
              this.style.display = 'none';
            }
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
        });
      </script>
      <!-- Remove custom pagination/search/filter HTML/JS -->
    </div>
  </main>
</body>
</html>

