<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>RestEase Clients</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/Clients.css">
  <link rel="stylesheet" href="../css/sidebar.css">
</head>
<body>
  <!-- Sidebar -->
  <?php include '../Includes/sidebar.php'; ?>

  <!-- Main Content -->
  <main class="main-content">
    <div class="clients-header">
      <h1>Clients</h1>
      <p class="subtitle">View all Clients Information.</p>
    </div>
    <div class="clients-tabs-bar">
      <div class="clients-tabs">
        <a href="Clients.php"><button class="tab active">All Clients</button></a>
        <a href="ClientsRequest.php"><button class="tab">Request</button></a>
      </div>
    </div>
    <div class="clients-actions">
      <div class="search-container">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="Search Clients">
      </div>
      <div class="actions-right">
        <button class="date-picker-btn"><i class="fas fa-calendar"></i> <span>Mar 5, 2025 - Mar 5, 2025</span></button>
        <button class="filter-btn"><i class="fas fa-filter"></i> Filter</button>
      </div>
    </div>
    <div class="clients-table-container">
      <table class="clients-table">
        <thead>
          <tr>
            <th>Client Name</th>
            <th>Email</th>
            <th>Contact</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php
        // Connect to the database
        include_once '../Includes/db.php';
        if ($conn->connect_error) {
            echo "<tr><td colspan='5'>Database connection failed.</td></tr>";
        } else {
            $sql = "SELECT first_name, last_name, email, contact_no FROM users";
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $firstName = htmlspecialchars($row['first_name']);
                    $lastName = htmlspecialchars($row['last_name']);
                    $name = $firstName . ' ' . $lastName;
                    $email = htmlspecialchars($row['email']);
                    $contact = htmlspecialchars($row['contact_no']);
                    $initials = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));
                    $colorIndex = (abs(crc32($firstName . $lastName)) % 10) + 1;
                    $colorClass = "avatar-color-$colorIndex";
                    echo "<tr>
    <td>
        <div class=\"avatar-img avatar-google $colorClass\" style=\"display:inline-flex;\">$initials</div><span class=\"client-name\" style=\"vertical-align:middle; margin-left:4px; display:inline-block;\">$name</span>
    </td>
    <td>$email</td>
    <td>$contact</td>
    <td><span style=\"background:#d4edda;color:#155724;padding:4px 14px;border-radius:6px;font-size:0.95em;\">Active</span></td>
    <td>
        <div class=\"actions-dropdown\">
            <button class=\"actions-btn\" onclick=\"toggleActionsMenu(this); return false;\">
                <i class=\"fas fa-ellipsis-v\"></i>
            </button>
            <div class=\"actions-menu\">
                <button class=\"dropdown-item\"><i class=\"fas fa-user-slash\"></i> Disable</button>
                <button class=\"dropdown-item delete\"><i class=\"fas fa-archive\"></i> Archive</button>
            </div>
        </div>
    </td>
</tr>";
                }
            } else {
                echo "<tr><td colspan='5'>No clients found.</td></tr>";
            }
            $conn->close();
        }
        ?>
        </tbody>
      </table>
    </div>
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal-overlay" style="display:none;">
      <div class="modal-content">
        <div class="modal-header">
          <i class="fas fa-exclamation-triangle" style="color:#e74c3c;font-size:2rem;margin-bottom:8px;"></i>
          <h2 style="color:#e74c3c;margin:0;font-size:1.3rem;">Confirm Archive</h2>
        </div>
        <div class="modal-body" style="margin:18px 0 24px 0;">
          <p style="color:#444;font-size:1.07rem;margin:0;">
            Are you sure you want to archive this client?<br>
            This action will move the client to the archive section.
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
      <span><i class="fas fa-check-circle" style="margin-right:8px;"></i>Client successfully archived.</span>
      <button id="closeNotificationBtn" style="background:none;border:none;color:#fff;font-size:1.2em;cursor:pointer;margin-left:12px;">&times;</button>
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
        <span> Page 1 of 3
          </span>
        </div>
  </main>

</body>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Close all open menus if clicking outside
    document.addEventListener('click', function(e) {
        document.querySelectorAll('.actions-menu').forEach(function(menu) {
            menu.style.display = 'none';
        });
    });

    function toggleActionsMenu(btn) {
        event.stopPropagation();
        document.querySelectorAll('.actions-menu').forEach(function(menu) {
            menu.style.display = 'none';
        });
        var menu = btn.nextElementSibling;
        if (menu) {
            menu.style.display = (menu.style.display === 'flex') ? 'none' : 'flex';
        }
    }
    window.toggleActionsMenu = toggleActionsMenu;

    document.querySelectorAll('.actions-dropdown').forEach(function(drop) {
        drop.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    });

    let deleteTargetRow = null;
    let deleteTargetEmail = null;

    // Attach click event to all delete buttons
    document.querySelectorAll('.dropdown-item.delete').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var row = this.closest('tr');
            var email = row.querySelector('td:nth-child(2)').textContent.trim();
            deleteTargetRow = row;
            deleteTargetEmail = email;
            document.getElementById('deleteModal').style.display = 'flex';
        });
    });

    // Cancel button closes modal
    document.getElementById('modalCancelBtn').addEventListener('click', function() {
        document.getElementById('deleteModal').style.display = 'none';
        deleteTargetRow = null;
        deleteTargetEmail = null;
    });

    // Delete button archives client, removes row, and shows notification
    document.getElementById('modalDeleteBtn').addEventListener('click', function() {
        if (!deleteTargetEmail || !deleteTargetRow) return;
        
        const deleteBtn = this;
        const modal = document.getElementById('deleteModal');
        const cancelBtn = document.getElementById('modalCancelBtn');
        
        // Show loading state
        deleteBtn.disabled = true;
        deleteBtn.textContent = 'Archiving...';
        cancelBtn.disabled = true;
        
        fetch('archive_client.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'archive_client_email=' + encodeURIComponent(deleteTargetEmail)
        })
        .then(res => {
            if (!res.ok) {
                throw new Error('Network response was not ok');
            }
            return res.json();
        })
        .then(data => {
            if (data.status === 'success') {
                deleteTargetRow.parentNode.removeChild(deleteTargetRow);
                showSuccessNotification('Client successfully archived');
                modal.style.display = 'none';
            } else {
                showErrorNotification(data.message || 'Failed to archive client');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorNotification('An error occurred while archiving. Please try again.');
        })
        .finally(() => {
            // Reset button states
            deleteBtn.disabled = false;
            deleteBtn.textContent = 'Archive';
            cancelBtn.disabled = false;
            
            // Clear references
            deleteTargetRow = null;
            deleteTargetEmail = null;
        });
    });

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
});
</script>
<style>
/* filepath: c:\xampp\htdocs\RestEase\AdminSide\Clients.php (inline style for modal) */
.modal-overlay {
    position: fixed;
    z-index: 9999;
    left: 0; top: 0; right: 0; bottom: 0;
    background: rgba(44,62,80,0.18);
    display: flex;
    align-items: center;
    justify-content: center;
}
.modal-content {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 8px 32px rgba(60,60,60,0.18), 0 1.5px 6px rgba(0,0,0,0.08);
    padding: 32px 32px 24px 32px;
    min-width: 340px;
    max-width: 95vw;
    text-align: center;
    position: relative;
}
.modal-header h2 {
    margin: 0;
}
.modal-footer {
    margin-top: 10px;
}
.modal-delete-btn {
    background: #e74c3c;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 10px 28px;
    font-size: 1.08rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.18s;
}
.modal-delete-btn:hover {
    background: #c0392b;
}
.modal-cancel-btn {
    background: #f4f6fa;
    color: #444;
    border: none;
    border-radius: 6px;
    padding: 10px 28px;
    font-size: 1.08rem;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.18s;
}
.modal-cancel-btn:hover {
    background: #e0e0e0;
}
</style>
</html>
