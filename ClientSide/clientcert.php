<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: ../login.php"); // Adjust the path if needed
    exit;
}
include_once '../Includes/db.php';

// Get user info
$userId = $_SESSION['user_id'];
$userRes = $conn->query("SELECT first_name, last_name, email FROM users WHERE id = $userId LIMIT 1");
$user = $userRes ? $userRes->fetch_assoc() : null;

// Find certificates for this user by matching InformantName and/or email
$certificates = [];
if ($user) {
    $informantName = trim($user['first_name'] . ' ' . $user['last_name']);
    // Try to match by InformantName (exact), fallback to email if you store email in certification table
    $certRes = $conn->prepare("SELECT * FROM certification WHERE InformantName = ? ORDER BY id DESC");
    $certRes->bind_param('s', $informantName);
    $certRes->execute();
    $result = $certRes->get_result();
    while ($row = $result->fetch_assoc()) {
        $certificates[] = $row;
    }
    $certRes->close();
}
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
        margin: 0;
        color: #222;
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
      .no-cert-msg {
        color: #888;
        font-size: 1.15rem;
        text-align: center;
        margin: 48px 0 24px 0;
        font-weight: 500;
      }
      /* Responsive and scrollable certificate preview modal */
      #certPreviewModal {
        display: none;
        position: fixed;
        z-index: 9999;
        left: 0; top: 0;
        width: 100vw; height: 100vh;
        background: rgba(44,62,80,0.18);
        align-items: center;
        justify-content: center;
      }
      #certPreviewContent {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 8px 32px rgba(60,60,60,0.18),0 1.5px 6px rgba(0,0,0,0.08);
        padding: 32px 32px 24px 32px;
        max-width: 900px;
        width: 95vw;
        position: relative;
        max-height: 90vh;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        font-family: 'Poppins', sans-serif; /* Ensure modal uses Poppins by default */
      }
      @media (max-width: 700px) {
        #certPreviewContent {
          padding: 16px 4vw 16px 4vw;
          max-width: 98vw;
        }
        .cert-preview-header img {
          height: 40px !important;
        }
        .cert-preview-header .cert-title {
          font-size: 1.1rem !important;
        }
      }
      /* Make certificate content responsive */
      .cert-preview-header {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 18px;
        flex-wrap: wrap;
        font-family: 'Poppins', sans-serif; /* Ensure header uses Poppins */
      }
      .cert-title {
        font-family: 'Times New Roman', Times, serif; /* Only the title uses Times New Roman */
        font-size: 1.7rem;
        font-weight: 900;
        letter-spacing: 1px;
        margin: 8px 0;
      }
      .cert-list-container {
        margin-top: 32px;
        margin-bottom: 32px;
        width: 100%;
        max-width: 1300px;
        overflow-x: auto; /* Add horizontal scroll for responsiveness */
      }
      .cert-list-title {
        font-size: 2rem;
        font-weight: 600;
        margin-bottom: 18px;
        color: #333;
        text-align: center;
      }
      .cert-list-back {
        display: inline-block;
        color: #506C84;
        font-size: 1.08rem;
        font-weight: 500;
        margin-bottom: 12px;
        text-decoration: none;
        cursor: pointer;
        transition: color 0.18s;
      }
      .cert-list-back:hover {
        color: #39546a;
        text-decoration: none;
      }
      .cert-list {
        list-style: none;
        padding: 0;
        margin: 0;
      }
      .cert-list-item {
        background: #f4fbff;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(44,62,80,0.08);
        margin-bottom: 18px;
        padding: 18px 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        transition: box-shadow 0.18s;
        border: 1px solid #e0e7ef;
      }
      .cert-list-item:hover {
        box-shadow: 0 4px 16px rgba(44,62,80,0.12);
      }
      .cert-list-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
      }
      .cert-list-name {
        font-size: 1.08rem;
        font-weight: 600;
        color: #506C84;
        margin-bottom: 2px;
      }
      .cert-list-details {
        font-size: 0.98rem;
        color: #222;
        margin-bottom: 2px;
      }
      .cert-list-date {
        font-size: 0.95rem;
        color: #888;
      }
      .cert-list-actions {
        display: flex;
        gap: 10px;
        align-items: center;
      }
      .cert-list-btn {
        background: #1976d2;
        color: #fff;
        border: none;
        padding: 8px 24px;
        font-size: 1rem;
        border-radius: 8px;
        font-weight: 500;
        cursor: pointer;
        transition: background 0.18s;
        display: flex;
        align-items: center;
        gap: 8px;
      }
      .cert-list-btn:hover {
        background: #115293;
      }
      @media (max-width: 700px) {
        .cert-list-container {
          padding: 0 2vw;
          overflow-x: auto; /* Ensure scroll on small screens */
        }
        .cert-list-item {
          flex-direction: column;
          align-items: flex-start;
          padding: 14px 8px;
        }
        .cert-list-actions {
          margin-top: 10px;
        }
      }
    </style>
</head>
<body>
   <?php include '../Includes/navbar2.php'; ?>

   <!-- Main Content -->
   <div class="main-content">
     <div class="cert-list-container">
       <a href="javascript:history.back()" class="cert-list-back"><i class="fas fa-arrow-left"></i> Back</a>
       <div class="cert-list-title text-muted">Your Certificate</div>
       <?php if (count($certificates) === 0): ?>
         <div class="no-cert-msg text-muted">
           No certificate available yet.<br>
           Please contact the administrator or check back later.
         </div>
       <?php else: ?>
         <ul class="cert-list">
           <?php foreach ($certificates as $idx => $cert): ?>
             <li class="cert-list-item">
               <div class="cert-list-info">
                 <!-- Show deceased's name instead of informant -->
                 <div class="cert-list-name">
                   <?php echo htmlspecialchars($cert['NameOfDeceased']); ?>
                 </div>
                 <div class="cert-list-details">
                   Apartment No: <strong><?php echo htmlspecialchars($cert['AptNo']); ?></strong>
                   | MC No: <strong><?php echo htmlspecialchars($cert['MCNo']); ?></strong>
                 </div>
                 <div class="cert-list-date">
                   Date Paid: <?php echo htmlspecialchars($cert['DatePaid']); ?>
                 </div>
               </div>
               <div class="cert-list-actions">
                 <button type="button" class="cert-list-btn" onclick="window.print()">
                   <i class="fas fa-print"></i> Print
                 </button>
                 <button type="button" class="cert-list-btn" onclick="showCertPreview(<?php echo $idx; ?>)">
                   <i class="fas fa-eye"></i> View
                 </button>
               </div>
             </li>
           <?php endforeach; ?>
         </ul>
         <!-- Certificate Preview Modal -->
         <div id="certPreviewModal">
           <div id="certPreviewContent">
             <button onclick="closeCertPreview()" style="position:absolute;top:18px;right:18px;background:none;border:none;font-size:2rem;color:#888;cursor:pointer;">&times;</button>
             <div id="certPreviewBody"></div>
           </div>
         </div>
         <script>
           // Certificates data for JS
           var certificates = <?php echo json_encode($certificates); ?>;
           function showCertPreview(idx) {
             var cert = certificates[idx];
             var actions = [
               {
                 key: 'DNew',
                 label: 'register the death of <strong>' + (cert.NameOfDeceased || '') + '</strong> and rent CRYPT for five (5) years'
               },
               {
                 key: 'DRenew',
                 label: 'renewal of CRYPT'
               },
               {
                 key: 'DTransfer',
                 label: 'transfer the remains of'
               },
               {
                 key: 'DReOpen',
                 label: 're-open the tomb of'
               },
               {
                 key: 'DReEnter',
                 label: 're-enter the remains of'
               }
             ];
             var actionsHtml = '';
             actions.forEach(function(action, i) {
               var checked = cert[action.key] === '✔' ? 'checked' : '';
               actionsHtml += '<li style="margin-bottom:14px;"><input type="checkbox" ' + checked + ' disabled style="margin-right:8px;"> ' + action.label + '</li>';
             });
             // Admin name logic: uppercase, fallback to empty string
             var adminName = cert.AdminName ? cert.AdminName.toUpperCase() : '';
             var html = `
               <div class="cert-preview-header">
                 <img src="../css/images/garciaIcon.jpg" alt="Padre Garcia Icon" style="height:60px;width:auto;">
                 <div style="flex:1;text-align:center;">
                   <div style="font-family:'Times New Roman', Times, serif;font-size:1.1rem;line-height:1.3;">
                     Republic of the Philippines<br>
                     Province of Batangas<br>
                     MUNICIPALITY OF PADRE GARCIA
                   </div>
                   <div class="cert-title">
                     OFFICE OF THE MUNICIPAL MAYOR
                   </div>
                   <hr style="border:0; border-top:4px solid #222; margin:12px 0;">
                   <div style="font-family:'Times New Roman', Times, serif;font-size:1.7rem;font-weight:900;letter-spacing:12px;">
                     CERTIFICATION
                   </div>
                 </div>
                 <img src="../css/images/Seal_of_Batangas.png" alt="Batangas Seal" style="height:60px;width:auto;">
               </div>
               <div style="margin-top:18px;">
                 <div style="text-align:right;">
                   <span style="background:yellow; padding:2px 18px; font-weight:bold; font-size:1.08rem;">
                     MC No. ${cert.MCNo}
                   </span>
                 </div>
                 <p style="margin-top:18px;">
                   This is to certify that <strong>${cert.InformantName}</strong>
                   of Barangay <strong>${cert.InformantAddress}</strong>
                 </p>
                 <ul style="list-style:none; padding-left:0;">
                   ${actionsHtml}
                 </ul>
                 <p>
                   Who died last <strong>${cert.DateDied ? cert.DateDied : ''}</strong> and was buried at the Municipal Cemetery.<br>
                   Issued this <strong>${cert.DatePaid ? cert.DatePaid : ''}</strong> upon the request of Mr./Ms. <strong>${cert.InformantName}</strong> for whatever purpose it may serve.<br>
                   Apartment No. <strong>${cert.AptNo}</strong>
                 </p>
                 <div style="margin-top:24px;display:flex;justify-content:space-between;flex-wrap:wrap;">
                   <div>
                     <strong>Recommending Approval:</strong><br>
                     <div style="height:32px;"></div>
                     <span style="font-weight:600;">${adminName}</span><br>
                     MPDC/ZA
                   </div>
                   <div>
                     <strong>Approved by:</strong><br>
                     <div style="height:32px;"></div>
                     <span style="font-weight:600;">ATTY. MARK LESTER G. MANALO</span><br>
                     Municipal Administrator
                   </div>
                 </div>
                 <div style="margin-top:24px;">
                   <strong>OR No.:</strong> ${cert.ORNumber}<br>
                   <strong>Date Paid:</strong> ${cert.DatePaid}<br>
                   <strong>Amount:</strong> ${cert.Amount !== null ? '₱' + parseFloat(cert.Amount).toLocaleString('en-US', {minimumFractionDigits:2}) : ''}<br>
                   <strong>Renewal:</strong> ${cert.Validity ? cert.Validity.substr(0,7) : ''}
                 </div>
                 <div style="margin-top:24px;text-align:center;">
                   <img src="../css/images/CertFooter.png" alt="Certificate Footer" style="max-width:100%;height:auto;">
                 </div>
               </div>
             `;
             document.getElementById('certPreviewBody').innerHTML = html;
             document.getElementById('certPreviewModal').style.display = 'flex';
           }
           function closeCertPreview() {
             document.getElementById('certPreviewModal').style.display = 'none';
           }
         </script>
       <?php endif; ?>
     </div>
   </div>

   <?php include '../Includes/footer-client.php'; ?>
    <!-- Bootstrap JS (optional, for responsive navbar) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

