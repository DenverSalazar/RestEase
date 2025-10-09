<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: ../login.php"); // Adjust the path if needed
    exit;
}
include_once '../Includes/db.php';
// Fetch user's full name
$user_fullname = '';
$stmt = $conn->prepare("SELECT first_name, last_name FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($first_name, $last_name);
if ($stmt->fetch()) {
    $user_fullname = trim($first_name . ' ' . $last_name);
}
$stmt->close();
$deceased_list = [];
$stmt = $conn->prepare("SELECT firstName, lastName, middleName, suffix, age, born, residency, dateDied, dateInternment, nicheID FROM deceased WHERE informantName = ?");
$stmt->bind_param("s", $user_fullname);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $deceased_list[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RestEase</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/footer.css">
    <style>
      body {
        font-family: 'Poppins', sans-serif;
        background: #fafbfc;
        color: #222;
        margin: 0;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
      }
      .main-content {
        flex: 1 0 auto;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-start;
        min-height: 60vh;
      }
      .footer {
        flex-shrink: 0;
      }
      .no-records-msg {
        color: #888;
        font-size: 1.15rem;
        text-align: center;
        margin: 48px 0 24px 0;
        font-weight: 500;
      }
    </style>

</head>
<body>
   <?php include '../Includes/navbar2.php'; ?>
   <div style="width:100%;display:flex;justify-content:flex-start;">
     <a href="javascript:history.back()" class="cert-list-back" style="color:#506C84;font-size:1.08rem;font-weight:500;margin:18px 0 0 120px;text-decoration:none;cursor:pointer;transition:color 0.18s;">
       <i class="fas fa-arrow-left"></i> Back
     </a>
   </div>
   <div class="main-content container my-4 text-muted">
       <div style="display: flex; align-items: center; gap: 18px; margin-bottom: 12px;">
         <h2 class="mb-0" style="font-weight:600;">My Deceased Records</h2>
       </div>
       <?php if (count($deceased_list) === 0): ?>
           <div class="no-records-msg text-muted">
             No records available yet.<br>
             Please contact the administrator or check back later.
           </div>
       <?php else: ?>
       <div class="mb-3">
           <input type="text" id="searchInput" class="form-control" placeholder="Search by name...">
       </div>
       <div class="table-responsive">
           <table class="table table-bordered table-striped" id="deceasedTable">
               <thead>
                   <tr>
                       <th>Name</th>
                       <th>Born</th>
                       <th>Date Died</th>
                       <th>Age</th>
                       <th>Residency</th>
                       <th>Date Internment</th>
                       <th>Niche</th>
                   </tr>
               </thead>
               <tbody>
                   <?php foreach ($deceased_list as $d): ?>
                   <tr>
                       <td>
                           <?php
                               $middleInitial = '';
                               if (!empty($d['middleName'])) {
                                   $middleInitial = strtoupper(substr(trim($d['middleName']), 0, 1)) . '. ';
                               }
                               $suffix = !empty($d['suffix']) ? ' ' . htmlspecialchars($d['suffix']) : '';
                               echo htmlspecialchars($d['firstName']) . ' ' . $middleInitial . htmlspecialchars($d['lastName']) . $suffix;
                           ?>
                       </td>
                       <td><?php echo htmlspecialchars($d['born']); ?></td>
                       <td><?php echo htmlspecialchars($d['dateDied']); ?></td>
                       <td><?php echo htmlspecialchars($d['age']); ?></td>
                       <td><?php echo htmlspecialchars($d['residency']); ?></td>
                       <td><?php echo htmlspecialchars($d['dateInternment']); ?></td>
                       <td><?php echo htmlspecialchars($d['nicheID']); ?></td>
                   </tr>
                   <?php endforeach; ?>
               </tbody>
           </table>
       </div>
       <?php endif; ?>
   </div>
   <?php include '../includes/footer-client.php'; ?>
   <script>
   // Simple client-side search for deceased name
   document.addEventListener('DOMContentLoaded', function() {
       var searchInput = document.getElementById('searchInput');
       if (!searchInput) return;
       searchInput.addEventListener('keyup', function() {
           var filter = searchInput.value.toLowerCase();
           var rows = document.querySelectorAll('#deceasedTable tbody tr');
           rows.forEach(function(row) {
               var nameCell = row.cells[0].textContent.toLowerCase();
               row.style.display = nameCell.includes(filter) ? '' : 'none';
           });
       });
   });
   </script>
    <!-- Bootstrap JS (optional, for responsive navbar) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
