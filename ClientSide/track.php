<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: ../login.php"); // Adjust the path if needed
    exit;
}

include_once '../Includes/db.php';

// Get the latest request for the logged-in user
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$pending_requests = [];
$approved_requests = [];
$denied_requests = [];
if ($user_id) {
    // Fetch all pending requests
    $stmt = $conn->prepare("SELECT * FROM client_requests WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $pending_requests[] = $row;
    }
    $stmt->close();

    // Fetch all approved requests and payment amount
    $stmt = $conn->prepare("SELECT ar.*, l.Amount as payment_amount FROM accepted_request ar LEFT JOIN ledger l ON ar.niche_id = l.ApartmentNo AND ar.informant_name = l.Payee AND l.user_id = ar.user_id WHERE ar.user_id = ? ORDER BY ar.created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $approved_requests[] = $row;
    }
    $stmt->close();

    // Fetch all denied requests
    $stmt = $conn->prepare("SELECT * FROM denied_request WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $denied_requests[] = $row;
    }
    $stmt->close();
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
    <link rel="stylesheet" href="../css/clienttrack.css">
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
      .cert-list-container {
        width: 100%;
        max-width: 1300px;
        margin: 0 auto;
        padding: 0 15px;
      }
      .cert-list-title {
        font-size: 2rem;
        font-weight: 600;
        margin: 30px 0 15px 0;
        text-align: center;
        color: #333;
      }
      .cert-list {
        list-style: none;
        padding: 0;
        margin: 0;
      }
      .cert-list-item {
        /* Match clientcert.php style */
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
      /* Color border for status */
      .cert-list-item.pending {
        border-left: 8px solid #FFC107;
        background: #FFF8E1;
      }
      .cert-list-item.approved {
        border-left: 8px solid #198754;
        background: #E9F7EF;
      }
      .cert-list-item.denied {
        border-left: 8px solid #DC3545;
        background: #FDEDEC;
      }

      /* Move back button upward and reduce left margin on small devices */
      @media (max-width: 480px) {
        .cert-list-back {
          display: inline-block;
          transform: translateY(-10px);
          margin-left: 16px !important; /* override inline margin on small screens */
        }
      }
    </style>
</head>
<body>
   <?php include '../Includes/navbar2.php'; ?>

   <div class="main-content">
     <div class="cert-list-container">
       <div style="height:32px;"></div>
       <a href="javascript:history.back()" class="cert-list-back" style="display:inline-block;color:#506C84;font-size:1.08rem;font-weight:500;margin-bottom:0px;text-decoration:none;cursor:pointer;transition:color 0.18s;">
         <i class="fas fa-arrow-left"></i> Back
       </a>
       <div class="cert-list-title text-muted">Your Requests Status</div>
       <?php if (empty($pending_requests) && empty($approved_requests) && empty($denied_requests)): ?>
         <div class="no-records-msg text-muted">
           Nothing to display. You have no requests yet.
         </div>
       <?php else: ?>
         <ul class="cert-list">
           <?php
           $all_requests = [];
           foreach ($pending_requests as $r) { $r['_status'] = 'Pending'; $all_requests[] = $r; }
           foreach ($approved_requests as $r) { $r['_status'] = 'Approved'; $all_requests[] = $r; }
           foreach ($denied_requests as $r) { $r['_status'] = 'Denied'; $all_requests[] = $r; }
           foreach ($all_requests as $idx => $req):
           ?>
             <li class="cert-list-item <?php echo strtolower($req['_status']); ?>">
               <div class="cert-list-info">
                 <div class="cert-list-name">
                   <?php echo $req['_status']; ?> Request
                 </div>
                 <div class="cert-list-details">
                   <strong>Request Type:</strong> <?php echo htmlspecialchars($req['type'] ?? 'Unknown'); ?>
                 </div>
                 <?php if ($req['_status'] === 'Denied'): ?>
                   <div class="cert-list-details">
                     <strong>Reason:</strong> <?php echo htmlspecialchars($req['reason'] ?? 'No reason provided'); ?>
                   </div>
                 <?php endif; ?>
                 <div class="cert-list-date">
                   <strong>
                     <?php
                       if ($req['_status'] === 'Pending') echo 'Date Requested:';
                       elseif ($req['_status'] === 'Approved') echo 'Accepted Date:';
                       else echo 'Denied Date:';
                     ?>
                   </strong> <?php echo htmlspecialchars($req['created_at']); ?>
                 </div>
               </div>
               <div class="cert-list-actions">
                 <button type="button" class="cert-list-btn" onclick="showRequestDetails(<?php echo $idx; ?>)">
                   <i class="fas fa-eye"></i> View
                 </button>
               </div>
             </li>
           <?php endforeach; ?>
         </ul>
         <!-- Request Details Modal -->
         <div id="requestDetailsModal" style="display:none;position:fixed;z-index:9999;left:0;top:0;width:100vw;height:100vh;background:rgba(44,62,80,0.18);align-items:center;justify-content:center;">
           <div id="requestDetailsContent" style="background:#fff;border-radius:16px;box-shadow:0 8px 32px rgba(60,60,60,0.18),0 1.5px 6px rgba(0,0,0,0.08);padding:32px 32px 24px 32px;max-width:500px;width:95vw;position:relative;max-height:90vh;overflow-y:auto;display:flex;flex-direction:column;">
             <button onclick="closeRequestDetails()" style="position:absolute;top:18px;right:18px;background:none;border:none;font-size:2rem;color:#888;cursor:pointer;">&times;</button>
             <div id="requestDetailsBody"></div>
           </div>
         </div>
         <script>
           var allRequests = <?php echo json_encode($all_requests); ?>;
           function showRequestDetails(idx) {
             var req = allRequests[idx];
             // Correct color for pending (yellow)
             var color = req._status === 'Pending' ? '#FFF8E1' : (req._status === 'Approved' ? '#E9F7EF' : '#FDEDEC');
             var statusColor = req._status === 'Pending' ? '#FFC107' : (req._status === 'Approved' ? '#198754' : '#DC3545');
             // Attachment link logic
             var attachmentHtml = '';
             if (req.file_upload) {
               var fileName = req.file_upload.split('_').slice(1).join('_');
               var fileUrl = '../uploads/' + req.file_upload;
               attachmentHtml = `<a href="${fileUrl}" target="_blank" style="color:#1976d2;text-decoration:underline;font-weight:500;">${fileName}</a>`;
             } else {
               attachmentHtml = `<span style="color:#888;">No file</span>`;
             }
             var html = `
               <div style="width:100%;background:${color};padding:18px 0 8px 0;text-align:center;border-radius:12px 12px 0 0;">
                 <span style="font-size:1.25rem;font-weight:600;color:${statusColor};">${req._status}</span>
               </div>
               <div style="margin-top:24px;">
                 <div style="margin-bottom:18px;">
                   <label style="font-weight:500;">Type</label>
                   <input type="text" class="form-control" style="margin-top:4px;" value="${req.type || ''}" readonly>
                 </div>
                 <div style="font-weight:600;font-size:1.08rem;margin-bottom:12px;">Deceased Information</div>
                 <div style="display:flex;gap:12px;margin-bottom:12px;">
                   <div style="flex:1;">
                     <label>First Name</label>
                     <input type="text" class="form-control" value="${req.first_name || ''}" readonly>
                   </div>
                   <div style="flex:1;">
                     <label>Last Name</label>
                     <input type="text" class="form-control" value="${req.last_name || ''}" readonly>
                   </div>
                 </div>
                 <div style="display:flex;gap:12px;margin-bottom:12px;">
                   <div style="flex:1;">
                     <label>Middle Name</label>
                     <input type="text" class="form-control" value="${req.middle_name || ''}" readonly>
                   </div>
                   <div style="flex:1;">
                     <label>Age</label>
                     <input type="text" class="form-control" value="${req.age || ''}" readonly>
                   </div>
                 </div>
                 <div style="display:flex;gap:12px;margin-bottom:12px;">
                   <div style="flex:1;">
                     <label>Date of Birth</label>
                     <input type="text" class="form-control" value="${req.dob || ''}" readonly>
                   </div>
                   <div style="flex:1;">
                     <label>Date Died</label>
                     <input type="text" class="form-control" value="${req.dod || ''}" readonly>
                   </div>
                 </div>
                 <div style="display:flex;gap:12px;margin-bottom:12px;">
                   <div style="flex:1;">
                     <label>Residency</label>
                     <input type="text" class="form-control" value="${req.residency || ''}" readonly>
                   </div>
                   <div style="flex:1;">
                     <label>Informant Name</label>
                     <input type="text" class="form-control" value="${req.informant_name || ''}" readonly>
                   </div>
                 </div>
                 <div style="margin-bottom:12px;">
                   <label>Attachments</label>
                   <div style="display:flex;align-items:center;gap:8px;">
                     <span style="background:#e74c3c;color:#fff;padding:2px 8px;border-radius:4px;font-size:0.95rem;font-weight:500;">PDF</span>
                     ${attachmentHtml}
                   </div>
                 </div>
                 <button onclick="closeRequestDetails()" style="width:100%;margin-top:18px;background:#ccc;color:#222;border:none;padding:10px 0;border-radius:6px;font-size:1.08rem;font-weight:500;cursor:pointer;">Close</button>
               </div>
             `;
             document.getElementById('requestDetailsBody').innerHTML = html;
             document.getElementById('requestDetailsModal').style.display = 'flex';
           }
           function closeRequestDetails() {
             document.getElementById('requestDetailsModal').style.display = 'none';
           }
         </script>
       <?php endif; ?>
     </div>
   </div>

   <?php include '../includes/footer-client.php'; ?>
   
    <!-- Bootstrap JS (optional, for responsive navbar) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>














