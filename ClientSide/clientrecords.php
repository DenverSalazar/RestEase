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
    <link rel="icon" type="image/png" href="../assets/re logo blue.png">
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

        .page-back {
            color:#506C84;
            font-size:1.08rem;
            font-weight:500;
            text-decoration:none;
            cursor:pointer;
            transition:color .18s;
            display:inline-block;
            padding:10px 12px;
            border-radius:8px;
            margin-top:18px;
            /* removed fixed desktop offset to allow responsive positioning */
            /* margin-left:215px; */
        }
        .page-back i { margin-right:2px; }
        /* Large screens: align Back with the left edge of the centered .viewmap-container */
        @media (min-width:901px) {
            /* Align to container left: (viewport - containerWidth)/2 + containerPadding */
            .page-back {
                margin-left: calc((100% - 1300px) / 2 + 12px);
            }
        }

      /* Move back button upward and reduce left margin on small devices */
      @media (max-width: 480px) {
        .cert-list-back {
          display: inline-block;
          transform: translateY(-10px);
          margin: 18px 0 0 16px !important; /* override inline margin on small screens */
        }
      }

      /* Responsive table: keep table look on large screens; stacked labeled rows on small screens */
      .table-responsive {
        overflow-x: auto;
      }
      @media (max-width: 768px) {
        .table-responsive table thead {
          display: none; /* hide header on small screens */
        }
        .table-responsive table,
        .table-responsive tbody,
        .table-responsive tr,
        .table-responsive td {
          display: block;
          width: 100%;
        }
        .table-responsive tr {
          margin-bottom: 12px;
          border: 1px solid #e6eef5;
          border-radius: 8px;
          padding: 10px 12px;
          background: #fff;
        }
        .table-responsive td {
          padding: 6px 0;
          border: none;
          text-align: left;
        }
        .table-responsive td::before {
          content: attr(data-label);
          display: inline-block;
          min-width: 110px;
          font-weight: 600;
          color: #262424ff;
          margin-right: 8px;
        }
      }
    </style>

</head>
<body>
   <?php include '../Includes/navbar2.php'; ?>
   <div style="width:100%;display:flex;justify-content:flex-start;">
        <a href="javascript:history.back()" class="page-back" aria-label="Go back">
            <i class="fas fa-arrow-left" aria-hidden="true"></i> Back
        </a>
    </div>
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
                       <td data-label="Name">
                           <?php
                               $middleInitial = '';
                               if (!empty($d['middleName'])) {
                                   $middleInitial = strtoupper(substr(trim($d['middleName']), 0, 1)) . '. ';
                               }
                               $suffix = !empty($d['suffix']) ? ' ' . htmlspecialchars($d['suffix']) : '';
                               echo htmlspecialchars($d['firstName']) . ' ' . $middleInitial . htmlspecialchars($d['lastName']) . $suffix;
                           ?>
                       </td>
                       <td data-label="Born"><?php echo htmlspecialchars($d['born']); ?></td>
                       <td data-label="Date Died"><?php echo htmlspecialchars($d['dateDied']); ?></td>
                       <td data-label="Age"><?php echo htmlspecialchars($d['age']); ?></td>
                       <td data-label="Residency"><?php echo htmlspecialchars($d['residency']); ?></td>
                       <td data-label="Date Internment"><?php echo htmlspecialchars($d['dateInternment']); ?></td>
                       <td data-label="Niche"><?php echo htmlspecialchars($d['nicheID']); ?></td>
                   </tr>
                   <?php endforeach; ?>
               </tbody>
           </table>
       </div>
       <?php endif; ?>
   </div>
   <?php include '../Includes/footer-client.php'; ?>
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
