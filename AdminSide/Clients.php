<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>RestEase Clients</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/Clients.css">
  <link rel="stylesheet" href="../css/sidebar.css">
  <!-- DataTables CSS -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
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
        <span class="clients-tab-title">Manage Clients Account</span>
      </div>
    </div>
    <div class="clients-actions">
      <div class="search-container">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="Search Clients" id="search-input">
      </div>
      <div class="actions-right">
        <div class="date-filter-container">
          <input type="date" id="registration-date-filter" class="date-input">
          <button type="button" id="clear-date-filter" class="clear-date-btn" style="display:none;">
            <i class="fas fa-times"></i>
          </button>
        </div>
      </div>
    </div>
    
    <!-- Show entries dropdown positioned exactly like Records.php -->
    <div style="margin-bottom: 16px;">
      <div class="dataTables_length">
        <label>Show <select name="clients-table_length"><option value="10">10</option><option value="25">25</option><option value="50">50</option><option value="100">100</option></select> entries</label>
      </div>
    </div>
    
    <div class="clients-table-container">
      <table class="clients-table" id="clients-table">
        <thead>
          <tr>
            <th>Client Name</th>
            <th>Email</th>
            <th>Contact</th>
            <th>Registration Date</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php
        // Connect to the database
        include_once '../Includes/db.php';
        if ($conn->connect_error) {
            echo "<tr><td colspan='6'>Database connection failed.</td></tr>";
        } else {
            $sql = "SELECT first_name, last_name, email, contact_no, created_at, profile_picture, status FROM users ORDER BY created_at DESC";
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $firstName = htmlspecialchars($row['first_name']);
                    $lastName = htmlspecialchars($row['last_name']);
                    $name = $firstName . ' ' . $lastName;
                    $email = htmlspecialchars($row['email']);
                    $contact = htmlspecialchars($row['contact_no']);
                    $registrationDate = htmlspecialchars($row['created_at'] ? date('Y-m-d', strtotime($row['created_at'])) : 'N/A');
                    $profilePicture = htmlspecialchars($row['profile_picture']);
                    $status = htmlspecialchars($row['status']);
                    
                    // Check if user has profile picture
                    $hasProfilePicture = $profilePicture && file_exists('../uploads/' . $profilePicture);
                    
                    if ($hasProfilePicture) {
                        $avatarHtml = '<img src="../uploads/' . $profilePicture . '" alt="Profile" class="avatar-img" style="width:38px;height:38px;border-radius:50%;object-fit:cover;">';
                    } else {
                        $initials = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));
                        $colorIndex = (abs(crc32($firstName . $lastName)) % 10) + 1;
                        $colorClass = "avatar-color-$colorIndex";
                        $avatarHtml = '<div class="avatar-img avatar-google ' . $colorClass . '" style="display:inline-flex;">' . $initials . '</div>';
                    }
                    
                    // Status display based on actual database value
                    if ($status === 'disabled') {
                        $statusHtml = '<span style="background:#f8d7da;color:#721c24;padding:4px 14px;border-radius:6px;font-size:0.95em;">Disabled</span>';
                        $disableButtonText = '<i class="fas fa-user-check"></i> Enable';
                        $disableButtonClass = 'enable';
                    } else {
                        $statusHtml = '<span style="background:#d4edda;color:#155724;padding:4px 14px;border-radius:6px;font-size:0.95em;">Active</span>';
                        $disableButtonText = '<i class="fas fa-user-slash"></i> Disable';
                        $disableButtonClass = 'disable';
                    }
                    
                    echo "<tr data-registration-date='$registrationDate'>
                    <td style='white-space: nowrap;'>
                        $avatarHtml<span class=\"client-name\" style=\"vertical-align:middle; margin-left:4px; display:inline-block;\">$name</span>
                    </td>
                    <td>$email</td>
                    <td>$contact</td>
                    <td>$registrationDate</td>
                    <td>$statusHtml</td>
                    <td>
                        <div class=\"actions-dropdown\">
                            <button class=\"actions-btn\" onclick=\"toggleActionsMenu(this); return false;\">
                                <i class=\"fas fa-ellipsis-v\"></i>
                            </button>
                            <div class=\"actions-menu\">
                                <button class=\"dropdown-item $disableButtonClass\">$disableButtonText</button>
                                <button class=\"dropdown-item delete\"><i class=\"fas fa-archive\"></i> Archive</button>
                            </div>
                        </div>
                    </td>
                </tr>";
                }
            }
        }
        $conn->close();
        ?>
        </tbody>
      </table>
    </div>
    
    <!-- Move pagination controls to bottom exactly like Records.php -->
    <div class="dataTables_wrapper">
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
    <!-- DataTables JS -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
  </main>

</body>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTables
    const dataTable = $('#clients-table').DataTable({
        "paging": true,
        "searching": true,
        "ordering": true,
        "info": true,
        "dom": 'rtip', // Show table, info and pagination
        "columnDefs": [
            { "orderable": false, "targets": [5] }
        ],
        "drawCallback": function() {
            // Move pagination outside table container while preserving functionality
            const tableWrapper = $('#clients-table').closest('.clients-table-container');
            const externalWrapper = tableWrapper.next('.dataTables_wrapper');
            
            // Move info and pagination to external wrapper
            const info = $('#clients-table_info').detach();
            const paginate = $('#clients-table_paginate').detach();
            
            externalWrapper.empty().append(info).append(paginate);
        }
    });

    // Connect existing search bar to DataTables
    document.getElementById('search-input').addEventListener('keyup', function() {
        dataTable.search(this.value).draw();
    });

    // New date filter functionality
    const dateInput = document.getElementById('registration-date-filter');
    const clearDateBtn = document.getElementById('clear-date-filter');

    dateInput.addEventListener('change', function() {
        const selectedDate = this.value;
        if (selectedDate) {
            // Show clear button
            clearDateBtn.style.display = 'block';
            
            // Filter table by registration date
            dataTable.column(3).search(selectedDate, false, false).draw();
        } else {
            clearDateBtn.style.display = 'none';
            dataTable.column(3).search('').draw();
        }
    });

    clearDateBtn.addEventListener('click', function() {
        dateInput.value = '';
        this.style.display = 'none';
        dataTable.column(3).search('').draw();
    });

    // Connect entries dropdown to DataTables
    document.querySelector('select[name="clients-table_length"]').addEventListener('change', function() {
        dataTable.page.len(parseInt(this.value)).draw();
    });

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

    // Attach click event to all disable/enable buttons
    document.querySelectorAll('.dropdown-item.disable, .dropdown-item.enable').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var row = this.closest('tr');
            var email = row.querySelector('td:nth-child(2)').textContent.trim();
            var isDisable = this.classList.contains('disable');
            var action = isDisable ? 'disable' : 'enable';
            
            // Show loading state
            this.disabled = true;
            this.textContent = isDisable ? 'Disabling...' : 'Enabling...';
            
            fetch('disable_client.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'disable_client_email=' + encodeURIComponent(email) + '&action=' + action
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    // Update status display
                    var statusCell = row.querySelector('td:nth-child(5)');
                    if (isDisable) {
                        statusCell.innerHTML = '<span style="background:#f8d7da;color:#721c24;padding:4px 14px;border-radius:6px;font-size:0.95em;">Disabled</span>';
                        this.innerHTML = '<i class="fas fa-user-check"></i> Enable';
                        this.classList.remove('disable');
                        this.classList.add('enable');
                    } else {
                        statusCell.innerHTML = '<span style="background:#d4edda;color:#155724;padding:4px 14px;border-radius:6px;font-size:0.95em;">Active</span>';
                        this.innerHTML = '<i class="fas fa-user-slash"></i> Disable';
                        this.classList.remove('enable');
                        this.classList.add('disable');
                    }
                    showSuccessNotification(data.message);
                } else {
                    showErrorNotification(data.message || 'Failed to update client status');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showErrorNotification('An error occurred. Please try again.');
            })
            .finally(() => {
                this.disabled = false;
            });
        });
    });
});
</script>
<style>
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
.clients-tab-title {
    background: none;
    border: none;
    font-size: 1.08rem;
    padding: 16px 0 12px 0;
    color: #222;
    font-weight: 600;
    border-bottom: 2px solid transparent;
    cursor: pointer;
    opacity: 0.7;
    transition: border-bottom 0.18s, opacity 0.18s, color 0.18s;
    border-radius: 0;
    box-shadow: none;
    margin-bottom: 0;
    margin-top: 0;
    display: inline-block;
}
.clients-tab-title.active {
    border-bottom: 2.5px solid #506C84;
    color: #506C84;
    opacity: 1;
}
.date-filter-container {
    position: relative;
    display: flex;
    align-items: center;
}

.date-input {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
    background: #fff;
    cursor: pointer;
}

</style>
</html>
