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
    <link rel="icon" type="image/png" href="../assets/re logo blue.png">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/footer.css">
    <link rel="stylesheet" href="../css/clienttrack.css">
    <style>
  /* Reset / base */
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
    padding-bottom: 28px; /* added gap so footer does not sit flush to content */
  }
  .footer {
    flex-shrink: 0;
  }

  /* Container / title */
  .cert-list-container {
    width: 100%;
    max-width: 1300px;
    margin: 0 auto;
    padding: 0 12px;
    margin-bottom: 12px; /* small extra spacing before footer */
  }
  .cert-list-title {
    font-size: 1.6rem;
    font-weight: 600;
    margin: 18px 0 8px 0; /* tighter */
    text-align: center;
    color: #333;
  }
  .no-records-msg {
    color: #888;
    font-size: 1rem;
    text-align: center;
    margin: 28px 0 12px 0;
    font-weight: 500;
  }

  /* List */
  .cert-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 8px; /* tighter gap */
  }

  /* Compact card style: white interior, thin colored border */
  .cert-list-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 8px 12px; /* compact vertical padding */
    border-radius: 8px;
    border: 2px solid #e9eef3;
    background: #fff;
    transition: transform .10s ease, box-shadow .10s ease;
  }
  .cert-list-item:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 10px rgba(32,45,60,0.05);
  }

  /* Info column */
  .cert-list-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
    flex: 1 1 auto;
    min-width: 0;
  }
  .cert-list-name {
    font-size: 0.95rem;
    font-weight: 600;
    color: #506C84;
    margin-bottom: 1px;
    letter-spacing: 0.1px;
    line-height: 1;
  }
  .cert-list-details {
    font-size: 0.86rem;
    color: #6f7d87; /* match date color, was #3a4954 */
    margin-bottom: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .cert-list-date {
    font-size: 0.82rem;
    color: #6f7d87;
    margin-top: 2px;
  }

  /* Actions column (smaller button) */
  .cert-list-actions {
    display: flex;
    gap: 8px;
    align-items: center;
    margin-left: 10px;
    flex: 0 0 auto;
  }
  .cert-list-btn {
    background: #0b76b3;
    color: #fff;
    border: none;
    padding: 6px 10px; /* smaller button */
    font-size: 0.85rem;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: background .10s ease, transform .06s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    box-shadow: 0 1px 6px rgba(11,118,179,0.10);
    line-height: 1;
  }
  .cert-list-btn i { font-size: 0.85rem; }
  .cert-list-btn:hover {
    background: #095f8f;
    transform: translateY(-1px);
  }

  /* Status-specific borders only (interior remains white) and matching text color */
  .cert-list-item.pending { border-color: #FFC107; background: #fff; }
  .cert-list-item.approved { border-color: #28A745; background: #fff; }
  .cert-list-item.denied { border-color: #DC3545; background: #fff; }

  /* Only color the title per status (remove color from details) */
  .cert-list-item.pending .cert-list-name { color: #CC9900; }
  .cert-list-item.approved .cert-list-name { color: #1f7f35; }
  .cert-list-item.denied .cert-list-name { color: #b02a2f; }

  /* Responsive: compact + align button on same line as Request Type for small screens */
  @media (max-width: 480px) {
    .cert-list-item {
      /* use grid so we can keep actions in right column while content stacks in left */
      display: grid;
      grid-template-columns: 1fr auto;
      gap: 6px 8px;
      align-items: center; /* center items vertically */
      padding: 8px 10px;
    }

    .cert-list-info {
      /* keep content stacked in the left column */
      order: 0;
      min-width: 0;
    }

    /* Make the first .cert-list-details (Request Type) use a single row so it lines up with the button */
    .cert-list-info .cert-list-details:first-of-type {
      display: flex;
      align-items: center;
      justify-content: flex-start;
      gap: 8px;
      white-space: nowrap; /* keep it on one line */
      overflow: hidden;
      text-overflow: ellipsis;
      margin-right: 6px; /* breathing room from the button column */
      font-size: 0.88rem;
      flex: 1;    /* take available horizontal space */
      min-width: 0; /* allow truncation */
    }

    /* Place the actions in the right grid column and center vertically */
    .cert-list-actions {
      align-self: center; /* vertically center relative to the Request Type row */
      margin: 0;
      padding: 0;
      justify-self: end; /* align to right column */
    }

    /* keep other details smaller and wrapped below */
    .cert-list-details { white-space: normal; font-size: 0.84rem; }
    .cert-list-name { font-size: 0.95rem; }
  }

  /* Slightly larger small tablets */
  @media (max-width: 768px) and (min-width: 481px) {
    .cert-list-item {
      padding: 8px 10px;
      flex-direction: column;
      align-items: stretch;
      gap: 6px;
    }
    .cert-list-actions {
      align-self: flex-end;
      margin-left: 0;
    }
  }

  /* Small tweak for back button on very small devices */
  @media (max-width: 480px) {
    .cert-list-back {
      display: inline-block;
      transform: translateY(-6px);
      margin-left: 10px !important;
    }
  }

  /* Modal close button: fixed, circular, centered vertically on header area */
  .request-modal-close {
    position: absolute;
    top: 12px;
    right: 12px;
    width: 36px;
    height: 36px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(255,255,255,0.98);
    color: #666;
    border: 1px solid rgba(0,0,0,0.06);
    border-radius: 50%;
    font-size: 18px;
    line-height: 1;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
    transition: background .12s ease, transform .08s ease, color .12s ease;
    z-index: 40;
  }
  .request-modal-close:hover {
    background: #fff;
    color: #222;
    transform: translateY(-1px);
  }

  /* Slightly adjust on very small screens so button remains inside rounded corners */
  @media (max-width: 480px) {
    .request-modal-close {
      top: 8px;
      right: 8px;
      width: 34px;
      height: 34px;
      font-size: 17px;
    }
  }

  /* Filter dropdown layout and style */
  .cert-filter-wrap {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 6px;
  }
  .cert-filter-select {
    width: 120px;        /* compact width */
    min-width: 0;        /* override previous 170px */
    padding: 4px 8px;    /* tighter */
    border: 1px solid #d6dee6;
    border-radius: 6px;  /* slightly smaller radius */
    background: #fff;
    color: #3a4954;
    font-size: 0.88rem;
  }
  @media (max-width: 480px) {
    .cert-filter-select {
      width: 100px;      /* smaller on mobile */
      font-size: 0.86rem;
    }
  }

  /* Title + controls alignment */
  .cert-title-row {
    position: relative;
    margin: 6px 0 12px;
  }
  .cert-title-row .cert-list-title {
    text-align: center;
    margin: 12px 0 8px;
  }
  /* New controls container on the right (search + dropdown) */
  .cert-title-controls {
    position: absolute;
    right: 0;
    top: 50%;
    transform: translateY(-50%);
    display: flex;
    align-items: center;
    gap: 8px;
  }
  /* Override previous absolute rule on select inside title row */
  .cert-title-row .cert-filter-select { position: static; transform: none; }

  /* Search input style */
  .cert-search {
    width: 200px;
    padding: 6px 10px;
    border: 1px solid #d6dee6;
    border-radius: 6px;
    background: #fff;
    color: #3a4954;
    font-size: 0.88rem;
  }
  @media (max-width: 480px) {
    .cert-search { width: 140px; font-size: 0.86rem; }
  }

  /* Ensure the X button is not shown (final override) */
  .request-modal-close { display: none !important; }
  @media (max-width: 480px) { .request-modal-close { display: none !important; } }

  /* ===========================
     ADDED: small-screen override
     Move search + filter under the title (do not remove anything)
     =========================== */
  @media (max-width: 480px) {
    /* make the controls flow normally below the title */
    .cert-title-controls {
      position: static !important;
      transform: none !important;
      display: flex;
      flex-direction: row;
      flex-wrap: nowrap;
      gap: 8px;
      justify-content: flex-start;
      align-items: center;
      width: 100%;
      margin-top: 8px;
      padding: 0 8px;
      box-sizing: border-box;
      /* keep visual spacing from title */
    }

    /* Ensure title stays centered and controls take their own line */
    .cert-title-row {
      text-align: center;
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    /* Slight adjustments to make inputs fit nicely on small screens */
    .cert-title-controls .cert-search {
      width: calc(100% - 128px); /* leave space for select */
      max-width: 100%;
    }
    .cert-title-controls .cert-filter-select {
      width: 120px;
      min-width: 0;
    }
  }
</style>
</head>
<body>
   <?php include '../Includes/navbar2.php'; ?>

   <div class="main-content">
     <div class="cert-list-container">
       <div style="height:32px;"></div>

       <!-- Top row: Back (left) only -->
       <div class="cert-filter-wrap">
         <a href="javascript:history.back()" class="cert-list-back" style="display:inline-block;color:#506C84;font-size:1.08rem;font-weight:500;margin-bottom:0px;text-decoration:none;cursor:pointer;transition:color 0.18s;">
           <i class="fas fa-arrow-left"></i> Back
         </a>
       </div>

       <!-- Title row with right-aligned controls (search + filter) -->
       <div class="cert-title-row">
         <div class="cert-list-title text-muted">Your Requests Status</div>
         <div class="cert-title-controls">
           <input id="requestSearch" class="cert-search" type="text" placeholder="Search..." oninput="applyFilters()">
           <select id="statusFilter" class="cert-filter-select" onchange="filterRequests(this.value)">
             <option value="all">All</option>
             <option value="pending">Pending</option>
             <option value="approved">Accepted</option>
             <option value="denied">Denied</option>
           </select>
         </div>
       </div>

       <?php if (empty($pending_requests) && empty($approved_requests) && empty($denied_requests)): ?>
         <div class="no-records-msg text-muted">
           Nothing to display. You have no requests yet.
         </div>
       <?php else: ?>
         <ul class="cert-list" id="requestList">
           <?php
           $all_requests = [];
           foreach ($pending_requests as $r) { $r['_status'] = 'Pending'; $all_requests[] = $r; }
           foreach ($approved_requests as $r) { $r['_status'] = 'Approved'; $all_requests[] = $r; }
           foreach ($denied_requests as $r) { $r['_status'] = 'Denied'; $all_requests[] = $r; }
           foreach ($all_requests as $idx => $req):
           ?>
             <li class="cert-list-item <?php echo strtolower($req['_status']); ?>"
                 data-status="<?php echo strtolower($req['_status']); ?>">
               <div class="cert-list-info">
                 <div class="cert-list-name">
                   <?php echo $req['_status']; ?> Request
                 </div>
                 <div class="cert-list-details">
                   <strong>Request Type:</strong> <?php echo htmlspecialchars($req['type'] ?? 'Unknown'); ?>
                 </div>
                 <?php if ($req['_status'] === 'Denied'): ?>
                 <?php endif; ?>
                 <div class="cert-list-date">
                   <strong>
                     <?php
                       if ($req['_status'] === 'Pending') echo 'Date Requested:';
                       elseif ($req['_status'] === 'Approved') echo 'Accepted Date:';
                       else echo 'Denied Date:';
                     ?>
                   </strong> <?php echo htmlspecialchars(date('Y-m-d', strtotime($req['created_at']))); ?>
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

         <!-- No results for current filter -->
         <div id="noFilterResults" class="no-records-msg text-muted" style="display:none;">
           No requests found for this filter.
         </div>

         <!-- Request Details Modal -->
         <div id="requestDetailsModal" style="display:none;position:fixed;z-index:9999;left:0;top:0;width:100vw;height:100vh;background:rgba(44,62,80,0.18);align-items:center;justify-content:center;">
           <div id="requestDetailsContent" style="background:#fff;border-radius:16px;box-shadow:0 8px 32px rgba(60,60,60,0.18),0 1.5px 6px rgba(0,0,0,0.08);padding:32px 32px 24px 32px;max-width:500px;width:95vw;position:relative;max-height:90vh;overflow-y:auto;display:flex;flex-direction:column;">
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

           // Keep old API; route to combined filter
           function filterRequests(val) {
             var select = document.getElementById('statusFilter');
             if (select) select.value = val;
             applyFilters();
           }

           // New: apply both status and text search
           function applyFilters() {
             var list = document.getElementById('requestList');
             if (!list) return;
             var statusVal = (document.getElementById('statusFilter') || {}).value || 'all';
             var term = (document.getElementById('requestSearch') || {}).value || '';
             term = term.trim().toLowerCase();

             var items = list.querySelectorAll('.cert-list-item');
             var shown = 0;
             items.forEach(function(li){
               var status = li.getAttribute('data-status'); // approved | denied | pending
               var matchStatus = (statusVal === 'all') || status === statusVal;
               var text = li.innerText.toLowerCase(); // search within visible text
               var matchText = !term || text.indexOf(term) !== -1;

               var show = matchStatus && matchText;
               li.style.display = show ? '' : 'none';
               if (show) shown++;
             });

             var emptyMsg = document.getElementById('noFilterResults');
             if (emptyMsg) emptyMsg.style.display = shown ? 'none' : 'block';
           }

           // Initialize
           applyFilters();
         </script>
       <?php endif; ?>
     </div>
   </div>

   <?php include '../Includes/footer-client.php'; ?>
   
    <!-- Bootstrap JS (optional, for responsive navbar) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>














