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
    <link rel="stylesheet" href="../css/clientbilling.css">
</head>
<body>
   <?php include '../Includes/navbar2.php'; ?>

   <div class="billing-container">
       <div class="billing-left">
           <h2>Billing and Processing</h2>
           <div class="status-card pending">
               <div class="status-title">Pending Request</div>
               <div class="status-type">Type: <span>Internment</span></div>
           </div>
           <div class="status-card approved">
               <div>
                   <div class="status-title">Request Approve</div>
                   <div class="status-type">Type: <span>Internment</span></div>
               </div>
               <button class="pay-btn">Pay</button>
           </div>
       </div>
       <div class="billing-right">
           <div class="pending-info">Your request is still pending please wait...</div>
           <form class="billing-form" onsubmit="return false;">
               <div class="form-group">
                   <label>Type</label>
                   <input type="text" value="Internment" readonly>
               </div>
               <div class="form-section">
                   <div class="section-title">Deceased Information</div>
                   <div class="form-row">
                       <div class="form-group">
                           <label>First Name</label>
                           <input type="text" value="Josephine" readonly>
                       </div>
                       <div class="form-group">
                           <label>Last Name</label>
                           <input type="text" value="Damdaman" readonly>
                       </div>
                   </div>
                   <div class="form-row">
                       <div class="form-group">
                           <label>Middle Name</label>
                           <input type="text" value="Yow" readonly>
                       </div>
                       <div class="form-group">
                           <label>Age</label>
                           <input type="text" value="34" readonly>
                       </div>
                   </div>
                   <div class="form-row">
                       <div class="form-group">
                           <label>Date of Birth</label>
                           <input type="text" value="April 27, 1977" readonly>
                       </div>
                       <div class="form-group">
                           <label>Date Died</label>
                           <input type="text" value="April 19, 2012" readonly>
                       </div>
                   </div>
                   <div class="form-row">
                       <div class="form-group">
                           <label>Residency</label>
                           <input type="text" value="Ohio, Mexico Pampanga" readonly>
                       </div>
                       <div class="form-group">
                           <label>Informant Name</label>
                           <input type="text" value="Dysania Beans" readonly>
                       </div>
                   </div>
               </div>
               <div class="form-section">
                   <div class="section-title">Uploaded Files</div>
                   <div class="uploaded-file">
                       <i class="fas fa-file-pdf"></i>
                       <span class="file-name">BirthCert.pdf</span>
                   </div>
               </div>
               <button class="cancel-btn" type="button" onclick="showCancelModal()">Cancel</button>
           </form>
       </div>
   </div>

   <!-- Cancel Confirmation Modal -->
   <div class="modal-overlay" id="cancelModalOverlay"></div>
   <div class="cancel-modal" id="cancelModal">
       <div class="modal-header">
           <span class="modal-x-large">&times;</span>
       </div>
       <div class="modal-message">Are you sure you want to<br>cancel your request?</div>
       <div class="modal-actions">
           <button class="modal-confirm">Confirm</button>
           <button class="modal-back" type="button" onclick="hideCancelModal()">Go Back</button>
       </div>
   </div>

   <?php include '../Includes/footer.php'; ?>
   <!-- Bootstrap JS (optional, for responsive navbar) -->
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
   <script>
    function showCancelModal() {
        document.getElementById('cancelModal').classList.add('show');
        document.getElementById('cancelModalOverlay').classList.add('show');
    }
    function hideCancelModal() {
        document.getElementById('cancelModal').classList.remove('show');
        document.getElementById('cancelModalOverlay').classList.remove('show');
    }
    document.getElementById('cancelModalOverlay').onclick = hideCancelModal;
    // Optionally, enable Go Back button and add handler if needed
    </script>
</body>
</html>

