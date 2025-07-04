<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>RestEase Ledger</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/Clients.css">
  <link rel="stylesheet" href="../css/sidebar.css">
  <style>
    /* ...existing code... */
    /* Use the same styles as in Clients.css for tabs, table, etc. */
    .ledger-header h1 {
      font-size: 2rem;
      font-weight: 700;
      margin-bottom: 0px;
    }
    .ledger-header .subtitle {
      font-size: 1.04rem;
      color: #6b7280;
      margin-bottom: 18px;
    }
    .ledger-table-container {
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.04);
      margin-top: 18px;
      overflow: visible;
      border: 1px solid #ececec;
    }
    .ledger-table {
      width: 100%;
      border-collapse: collapse;
    }
    .ledger-table th, .ledger-table td {
      text-align: left;
      padding: 14px 12px;
      font-size: 1.02rem;
      border-bottom: 1px solid #f0f0f0;
    }
    .ledger-table th {
      font-size: 0.98rem;
      letter-spacing: 0.01em;
      background: #f7f8fa;
      color: #b0b0b0;
      font-weight: 600;
      border-bottom: 1px solid #ececec;
    }
    .ledger-table td {
      vertical-align: middle;
      font-size: 1.02rem;
      color: #222;
      font-weight: 500;
    }
    .ledger-table tr:last-child td {
      border-bottom: none;
    }
    .ledger-table tr:hover {
      background: #f5f7fa;
    }
    .ledger-search-container {
      flex: 1 1 320px;
      max-width: 420px;
      display: flex;
      align-items: center;
      background: #fff;
      border-radius: 10px;
      border: 1px solid #ececec;
      padding: 0 16px;
      height: 40px;
      min-width: 320px;
      box-shadow: 0 1px 4px rgba(60,72,88,0.03);
      margin-bottom: 18px;
    }
    .ledger-search-container i {
      color: #b0b0b0;
      margin-right: 8px;
      font-size: 1.1rem;
    }
    .ledger-search-container input {
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
    .ledger-search-container input::placeholder {
      color: #b0b0b0;
      font-weight: 400;
      opacity: 1;
    }
    @media (max-width: 1100px) {
      .main-content {
        min-width: 0;
        max-width: 100vw;
        padding: 24px 8px 0 8px;
      }
      .ledger-header h1 {
        font-size: 1.3rem;
      }
      .ledger-table th, .ledger-table td {
        padding: 10px 6px;
        font-size: 0.95rem;
      }
      .ledger-search-container {
        min-width: 0;
        padding: 0 8px;
        height: 34px;
      }
    }
  </style>
</head>
<body>
  <!-- Sidebar -->
  <?php include '../Includes/sidebar.php'; ?>

  <!-- Main Content -->
  <main class="main-content">
    <div class="ledger-header">
      <h1>Ledger</h1>
      <p class="subtitle">Fill up the ledger information</p>
    </div>
    <!-- Tabs -->
    <div class="clients-tabs-bar">
      <div class="clients-tabs">
        <button class="tab active" id="transactionsTabBtn" onclick="showLedgerTab('transactions')">Insert</button>
        <button class="tab" id="formTabBtn" onclick="showLedgerTab('form')">Table</button>
      </div>
    </div>
    <!-- Insert Tab: Ledger Insert Form -->
    <div id="ledgerTransactionsTab" style="display:block;">
      <div class="card" style="width: 100%; max-width: 100%; margin: 24px 0; background: #fff; border-radius: 16px; box-shadow: 0 8px 32px rgba(44,62,80,0.10); padding: 24px; box-sizing: border-box;">
        <div class="form-section-title" style="font-size:1.25rem;font-weight:600;margin-bottom:18px;letter-spacing:0.5px;">Ledger Entry</div>
        <form id="ledgerForm" method="post" action="" autocomplete="off" style="width: 100%;">
          <div style="display:flex;gap:16px;flex-wrap:wrap;width:100%;">
            <div style="flex:1;min-width:0;width:100%;">
              <label for="formApartmentNo" style="font-weight:500;">Apartment No.</label>
              <input type="text" id="formApartmentNo" name="ApartmentNo" class="search-container" required placeholder="e.g. A-101" style="width:100%;box-sizing:border-box;">
            </div>
            <div style="flex:1;min-width:0;width:100%;">
              <label for="formPayee" style="font-weight:500;">Payee Name</label>
              <input type="text" id="formPayee" name="Payee" class="search-container" required placeholder="Payee Name" style="width:100%;box-sizing:border-box;">
            </div>
          </div>
          <div style="display:flex;gap:16px;flex-wrap:wrap;margin-top:16px;width:100%;">
            <div style="flex:1;min-width:0;width:100%;">
              <label for="formDatePaid" style="font-weight:500;">Date Paid</label>
              <input type="date" id="formDatePaid" name="DatePaid" class="search-container" required style="width:100%;box-sizing:border-box;">
            </div>
            <div style="flex:1;min-width:0;width:100%;">
              <label for="formAmount" style="font-weight:500;">Amount</label>
              <div style="display:flex;align-items:center;width:100%;">
                <input type="number" step="0.01" id="formAmount" name="Amount" class="search-container" required placeholder="₱ 0.00" style="width:100%;box-sizing:border-box;padding-left:13px;">
              </div>
            </div>
          </div>
          <div style="display:flex;gap:16px;flex-wrap:wrap;margin-top:16px;width:100%;">
            <div style="flex:1;min-width:0;width:100%;">
              <label for="formORNumber" style="font-weight:500;">OR Number</label>
              <input type="text" id="formORNumber" name="ORNumber" class="search-container" placeholder="Official Receipt No." style="width:100%;box-sizing:border-box;">
            </div>
            <div style="flex:1;min-width:0;width:100%;">
              <label for="formValidity" style="font-weight:500;">Validity</label>
              <input type="date" id="formValidity" name="Validity" class="search-container" style="width:100%;box-sizing:border-box;">
            </div>
          </div>
          <div style="display:flex;gap:16px;flex-wrap:wrap;margin-top:16px;width:100%;">
            <div style="flex:1;min-width:0;width:100%;">
              <label for="formMCNo" style="font-weight:500;">MC No.</label>
              <input type="text" id="formMCNo" name="MCNo" class="search-container" placeholder="MC Number" style="width:100%;box-sizing:border-box;">
            </div>
            <div style="flex:1;min-width:0;width:100%;">
              <label for="formDescription" style="font-weight:500;">Description</label>
              <input type="text" id="formDescription" name="Description" class="search-container" placeholder="Description" style="width:100%;box-sizing:border-box;">
            </div>
          </div>
          <div style="margin-top:24px;text-align:right;">
            <button type="submit" class="btn upload" style="min-width:120px;font-size:1.08rem;background:#506C84;color:#fff;border-radius:8px;">Save</button>
          </div>
        </form>
        <script>
          document.getElementById('ledgerForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Show confirmation modal
            const modalOverlay = document.getElementById('modalOverlay');
            const modalConfirmBtn = document.getElementById('modalConfirmBtn');
            const modalCancelBtn = document.getElementById('modalCancelBtn');
            
            modalOverlay.style.display = 'flex';
            
            // Handle confirmation
            modalConfirmBtn.onclick = function() {
              const formData = new FormData(document.getElementById('ledgerForm'));
              
              fetch('Ledger.php', {
                method: 'POST',
                body: formData
              })
              .then(response => response.text())
              .then(html => {
                // Show success notification
                showSuccessNotification('Record saved successfully!');
                
                // Clear the form
                document.getElementById('ledgerForm').reset();
                
                // Hide modal
                modalOverlay.style.display = 'none';
              })
              .catch(error => {
                showErrorNotification('Error saving record. Please try again.');
                console.error('Error:', error);
                modalOverlay.style.display = 'none';
              });
            };
            
            // Handle cancellation
            modalCancelBtn.onclick = function() {
              modalOverlay.style.display = 'none';
            };
            
            // Close modal on overlay click
            modalOverlay.onclick = function(e) {
              if (e.target === modalOverlay) {
                modalOverlay.style.display = 'none';
              }
            };
            
            // Close modal on ESC key
            document.addEventListener('keydown', function(e) {
              if (e.key === "Escape") {
                modalOverlay.style.display = 'none';
              }
            });
          });
        </script>
        <?php
        // Handle insert
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ApartmentNo'])) {
          include_once '../Includes/db.php';
          if (!$conn->connect_error) {
            $stmt = $conn->prepare("INSERT INTO ledger (ApartmentNo, DatePaid, Payee, Amount, Description, ORNumber, Validity, MCNo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param(
              "sssdssss",
              $_POST['ApartmentNo'],
              $_POST['DatePaid'],
              $_POST['Payee'],
              $_POST['Amount'],
              $_POST['Description'],
              $_POST['ORNumber'],
              $_POST['Validity'],
              $_POST['MCNo']
            );
            if ($stmt->execute()) {
              // Return success response
              echo json_encode(['status' => 'success']);
            } else {
              // Return error response
              echo json_encode(['status' => 'error', 'message' => $stmt->error]);
            }
            $stmt->close();
            $conn->close();
            exit;
          }
        }
        ?>
      </div>
    </div>
    <!-- Table Tab: Show all ledger records -->
    <div id="ledgerFormTab" style="display:none;">
      <div class="clients-actions">
        <div class="search-container">
          <i class="fas fa-search"></i>
          <input type="text" id="ledgerSearchInput" placeholder="Search Ledger">
        </div>
        <div class="actions-right">
          <button class="date-picker-btn"><i class="fas fa-calendar"></i>
            <span>
              <?php
                date_default_timezone_set('Asia/Manila');
                echo date('M d, Y');
              ?>
            </span>
          </button>
          <button class="filter-btn"><i class="fas fa-filter"></i> Filter</button>
        </div>
      </div>
      <div class="ledger-table-container">
        <table class="ledger-table" id="ledgerTable">
          <thead>
            <tr>
              <th>Apartment No.</th>
              <th>Payee Name</th>
              <th>Date Paid</th>
              <th>Amount</th>
              <th>OR Number</th>
              <th>Validity</th>
              <th>MC No.</th>
              <th>Description</th>
            </tr>
          </thead>
          <tbody>
          <?php
          $conn = include_once '../Includes/db.php';
          if ($conn->connect_error) {
              echo "<tr><td colspan='8'>Database connection failed.</td></tr>";
          } else {
              $sql = "SELECT * FROM ledger ORDER BY DatePaid DESC";
              $result = $conn->query($sql);
              if ($result && $result->num_rows > 0) {
                  while ($row = $result->fetch_assoc()) {
                      echo "<tr>";
                      echo "<td>" . htmlspecialchars($row['ApartmentNo']) . "</td>";
                      echo "<td>" . htmlspecialchars($row['Payee']) . "</td>";
                      echo "<td>" . htmlspecialchars(date('F d, Y', strtotime($row['DatePaid']))) . "</td>";
                      echo "<td>₱ " . number_format($row['Amount'], 2) . "</td>";
                      echo "<td>" . htmlspecialchars($row['ORNumber']) . "</td>";
                      echo "<td>" . ($row['Validity'] ? htmlspecialchars(date('F d, Y', strtotime($row['Validity']))) : '') . "</td>";
                      echo "<td>" . htmlspecialchars($row['MCNo']) . "</td>";
                      echo "<td>" . htmlspecialchars($row['Description']) . "</td>";
                      echo "</tr>";
                  }
              } else {
                  echo "<tr><td colspan='8'>No ledger records found.</td></tr>";
              }
              $conn->close();
          }
          ?>
          </tbody>
        </table>
      </div>
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
        <span> Page 1 of 3 </span>
      </div>
    </div>
    <!-- Success Notification -->
    <div id="successNotification" style="display:none;position:fixed;top:32px;right:32px;z-index:10000;background:#2ecc71;color:#fff;padding:18px 32px;border-radius:8px;box-shadow:0 4px 16px rgba(46,204,113,0.15);font-size:1.1rem;font-weight:500;align-items:center;gap:16px;min-width:220px;">
      <span><i class="fas fa-check-circle" style="margin-right:8px;"></i>Record saved successfully!</span>
      <button id="closeNotificationBtn" style="background:none;border:none;color:#fff;font-size:1.2em;cursor:pointer;margin-left:12px;">&times;</button>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal-overlay" id="modalOverlay" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(44,62,80,0.35);z-index:1000;align-items:center;justify-content:center;">
      <div class="modal-confirm" style="background:#fff;border-radius:12px;box-shadow:0 8px 32px rgba(44,62,80,0.18);padding:32px 28px 24px 28px;max-width:370px;width:90%;text-align:center;position:relative;animation:modalPop .18s cubic-bezier(.4,1.4,.6,1.0);">
        <h2 style="margin:0 0 12px 0;font-size:1.25rem;color:#27ae60;font-weight:600;letter-spacing:0.5px;"><i class="fas fa-check-circle" style="margin-right:8px;"></i>Confirm Save</h2>
        <p style="color:#2d3a4a;margin-bottom:24px;font-size:1rem;line-height:1.5;">Are you sure you want to save this record?</p>
        <div class="modal-actions" style="display:flex;gap:12px;justify-content:center;">
          <button class="modal-btn confirm" id="modalConfirmBtn" style="background:#27ae60;color:#fff;padding:8px 24px;border-radius:7px;border:none;font-weight:500;font-size:1rem;cursor:pointer;transition:background 0.18s,color 0.18s;">Save</button>
          <button class="modal-btn cancel" id="modalCancelBtn" style="background:#f5f7fa;color:#2d3a4a;padding:8px 24px;border-radius:7px;border:none;font-weight:500;font-size:1rem;cursor:pointer;transition:background 0.18s,color 0.18s;">Cancel</button>
        </div>
      </div>
    </div>
  </main>
  <script>
    // Tab switching logic
    function showLedgerTab(tab) {
      document.getElementById('ledgerTransactionsTab').style.display = (tab === 'transactions') ? '' : 'none';
      document.getElementById('ledgerFormTab').style.display = (tab === 'form') ? '' : 'none';
      document.getElementById('transactionsTabBtn').classList.toggle('active', tab === 'transactions');
      document.getElementById('formTabBtn').classList.toggle('active', tab === 'form');
    }

    // Check URL parameter for tab
    window.onload = function() {
      const urlParams = new URLSearchParams(window.location.search);
      const tab = urlParams.get('tab');
      if (tab === 'form') {
        showLedgerTab('form');
      }
    }

    // Show notification logic
    function showSuccessNotification(message) {
      const notif = document.getElementById('successNotification');
      notif.querySelector('span').innerHTML = `<i class="fas fa-check-circle" style="margin-right:8px;"></i>${message}`;
      notif.style.display = 'flex';
      notif.style.background = '#2ecc71';
      
      // Auto-close after 3 seconds
      const timeout = setTimeout(() => {
        notif.style.display = 'none';
      }, 3000);
      
      document.getElementById('closeNotificationBtn').onclick = function() {
        notif.style.display = 'none';
        clearTimeout(timeout);
      };
    }

    function showErrorNotification(message) {
      const notif = document.getElementById('successNotification');
      notif.querySelector('span').innerHTML = `<i class="fas fa-exclamation-circle" style="margin-right:8px;"></i>${message}`;
      notif.style.display = 'flex';
      notif.style.background = '#e74c3c';
      
      // Auto-close after 3 seconds
      const timeout = setTimeout(() => {
        notif.style.display = 'none';
      }, 3000);
      
      document.getElementById('closeNotificationBtn').onclick = function() {
        notif.style.display = 'none';
        clearTimeout(timeout);
      };
    }

    // Simple search filter for the ledger table (Table tab)
    document.getElementById('ledgerSearchInput').addEventListener('input', function() {
      const filter = this.value.toLowerCase();
      const rows = document.querySelectorAll('#ledgerTable tbody tr');
      rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
      });
    });
  </script>
</body>
</html>
