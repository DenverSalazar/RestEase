<?php
session_start();
include_once '../Includes/db.php';

// Get the latest request for the logged-in user
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$request = null;
if ($user_id) {
    $stmt = $conn->prepare("SELECT * FROM client_requests WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $request = $result->fetch_assoc();
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
    <link rel="stylesheet" href="../css/clientbilling.css">
    <style>
    .status-card.no-request {
        background: linear-gradient(135deg, #fff0f3 0%, #ffe3e6 100%) !important;
        border: none !important;
        color: #d7263d !important;
        box-shadow: 0 4px 24px rgba(215,38,61,0.08), 0 1.5px 8px rgba(215,38,61,0.04);
        border-radius: 18px !important;
        display: flex;
        align-items: center;
        gap: 18px;
        padding: 28px 32px;
        margin-bottom: 18px;
    }
    .status-card.no-request .status-title {
        color: #d7263d !important;
        font-size: 1.25rem;
        font-weight: 600;
        letter-spacing: 0.5px;
    }
    .status-card.no-request .status-type {
        color: #b71c1c !important;
        font-size: 1.08rem;
        font-weight: 500;
    }
    .no-request-container {
        background: linear-gradient(135deg, #fff0f3 0%, #ffe3e6 100%);
        border: none;
        color: #d7263d;
        border-radius: 22px;
        padding: 64px 36px 56px 36px;
        text-align: center;
        font-size: 1.22rem;
        font-weight: 500;
        margin-top: 48px;
        margin-bottom: 48px;
        box-shadow: 0 4px 24px rgba(215,38,61,0.08), 0 1.5px 8px rgba(215,38,61,0.04);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }
    .no-request-container .no-request-icon {
        font-size: 3.2rem;
        color: #d7263d;
        margin-bottom: 18px;
        background: #fff;
        border-radius: 50%;
        box-shadow: 0 2px 12px rgba(215,38,61,0.07);
        padding: 18px 22px 14px 22px;
        display: inline-block;
    }
    .no-request-container .no-request-title {
        font-size: 1.35rem;
        font-weight: 600;
        margin-bottom: 8px;
        color: #d7263d;
        letter-spacing: 0.2px;
    }
    .no-request-container .no-request-desc {
        font-size: 1.08rem;
        color: #b71c1c;
        font-weight: 400;
        margin-bottom: 0;
    }
    </style>
</head>
<body>
   <?php include '../Includes/navbar2.php'; ?>

   <div class="billing-container">
       <div class="billing-left">
           <h2>Billing and Processing</h2>
           <?php if (!$request): ?>
               <div class="status-card no-request">
                   <span class="no-request-icon"><i class="fas fa-exclamation-triangle"></i></span>
                   <div>
                       <div class="status-title">No Request</div>
                       <div class="status-type">Type: <span>-</span></div>
                   </div>
               </div>
           <?php else: ?>
               <div class="status-card pending">
                   <div class="status-title">Pending Request</div>
                   <div class="status-type">Type: <span><?php echo htmlspecialchars($request['type']); ?></span></div>
                   <button class="pay-btn" type="button" onclick="showPendingRequestForm()">View</button>
               </div>
               <div class="status-card approved">
                   <div>
                       <div class="status-title">Request Approve</div>
                       <div class="status-type">Type: <span><?php echo htmlspecialchars($request['type']); ?></span></div>
                   </div>
               </div>
           <?php endif; ?>
       </div>
       <div class="billing-right" id="billingRight">
           <?php if (!$request): ?>
               <div class="no-request-container">
                   <span class="no-request-icon"><i class="fas fa-exclamation-triangle"></i></span>
                   <div class="no-request-title">No Pending Requests</div>
                   <div class="no-request-desc">You have no pending requests at this time.<br>Submit a new request to see it here.</div>
               </div>
           <?php else: ?>
           <div class="pending-info">Your request is still pending please wait...</div>
           <form class="billing-form" onsubmit="return false;">
               <div class="form-group">
                   <label>Type</label>
                   <input type="text" value="<?php echo htmlspecialchars($request['type']); ?>" readonly>
               </div>
               <div class="form-section">
                   <div class="section-title">Deceased Information</div>
                   <div class="form-row">
                       <div class="form-group">
                           <label>First Name</label>
                           <input type="text" value="<?php echo htmlspecialchars($request['first_name']); ?>" readonly>
                       </div>
                       <div class="form-group">
                           <label>Last Name</label>
                           <input type="text" value="<?php echo htmlspecialchars($request['last_name']); ?>" readonly>
                       </div>
                   </div>
                   <div class="form-row">
                       <div class="form-group">
                           <label>Middle Name</label>
                           <input type="text" value="<?php echo htmlspecialchars($request['middle_name']); ?>" readonly>
                       </div>
                       <div class="form-group">
                           <label>Age</label>
                           <input type="text" value="<?php echo htmlspecialchars($request['age']); ?>" readonly>
                       </div>
                   </div>
                   <div class="form-row">
                       <div class="form-group">
                           <label>Date of Birth</label>
                           <input type="text" value="<?php echo htmlspecialchars($request['dob']); ?>" readonly>
                       </div>
                       <div class="form-group">
                           <label>Date Died</label>
                           <input type="text" value="<?php echo htmlspecialchars($request['dod']); ?>" readonly>
                       </div>
                   </div>
                   <div class="form-row">
                       <div class="form-group">
                           <label>Residency</label>
                           <input type="text" value="<?php echo htmlspecialchars($request['residency']); ?>" readonly>
                       </div>
                       <div class="form-group">
                           <label>Informant Name</label>
                           <input type="text" value="<?php echo htmlspecialchars($request['informant_name']); ?>" readonly>
                       </div>
                   </div>
               </div>
               <div class="form-section">
                   <div class="section-title">Uploaded Files</div>
                   <div class="uploaded-file">
                       <i class="fas fa-file-pdf"></i>
                       <span class="file-name"><?php echo $request['file_upload'] ? htmlspecialchars($request['file_upload']) : 'BirthCert.pdf'; ?></span>
                   </div>
               </div>
               <button class="cancel-btn" type="button" onclick="showCancelModal()">Cancel</button>
           </form>
           <?php endif; ?>
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

   <!-- Payment Confirmation Modal -->
   <div class="modal-overlay" id="confirmModalOverlay"></div>
   <div class="confirm-modal" id="confirmModal">
       <div class="confirm-modal-header">
           <span class="confirm-check">
               <svg width="64" height="64" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                   <circle cx="32" cy="32" r="32" fill="none"/>
                   <path d="M20 34L29 43L44 25" stroke="#5EDC8C" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
               </svg>
           </span>
       </div>
       <div class="confirm-modal-message">Payment Submitted!</div>
       <button class="confirm-modal-close" type="button" onclick="hideConfirmModal()">Close</button>
   </div>

   <!-- Payment Action Confirmation Modal -->
   <div class="modal-overlay" id="payConfirmModalOverlay"></div>
   <div class="pay-confirm-modal" id="payConfirmModal">
       <div class="pay-confirm-modal-header">
           <span class="pay-confirm-check">
               <svg width="48" height="48" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                   <circle cx="32" cy="32" r="32" fill="none"/>
                   <path d="M20 34L29 43L44 25" stroke="#5EDC8C" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
               </svg>
           </span>
       </div>
       <div class="pay-confirm-modal-message">Are you sure you want to submit your payment?</div>
       <div class="pay-confirm-modal-actions">
           <button class="pay-confirm-modal-confirm" type="button" onclick="showSubmittedModal()">Confirm</button>
           <button class="pay-confirm-modal-back" type="button" onclick="hidePayConfirmModal()">Go Back</button>
       </div>
   </div>

   <?php include '../includes/footer.php'; ?>
   
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

    // On page load, set the initial state of the status cards
    function setInitialStatusCards() {
        document.querySelector('.status-card.pending').classList.add('active');
        document.querySelector('.status-card.approved').classList.remove('active');
        document.querySelector('.status-card.pending').innerHTML = `
            <div>
                <div class="status-title">Pending Request</div>
                <div class="status-type">Type: <span>Internment</span></div>
            </div>
            <span class="status-card-btn"></span>
        `;
        document.querySelector('.status-card.approved').innerHTML = `
            <div>
                <div class="status-title">Request Approve</div>
                <div class="status-type">Type: <span>Internment</span></div>
            </div>
            <span class="status-card-btn"><button class="pay-btn" type="button" onclick="showPayForm()">Pay</button></span>
        `;
    }
    setInitialStatusCards();

    function showPayForm() {
        document.querySelector('.status-card.pending').classList.remove('active');
        document.querySelector('.status-card.approved').classList.add('active');
        document.querySelector('.status-card.pending').innerHTML = `
            <div>
                <div class="status-title">Pending Request</div>
                <div class="status-type">Type: <span>Internment</span></div>
            </div>
            <span class="status-card-btn"><button class="pay-btn" type="button" onclick="showPendingRequestForm()">View</button></span>
        `;
        document.querySelector('.status-card.approved').innerHTML = `
            <div>
                <div class="status-title">Request Approve</div>
                <div class="status-type">Type: <span>Internment</span></div>
            </div>
            <span class="status-card-btn"></span>
        `;
        document.getElementById('billingRight').innerHTML = `
            <div class="pay-modal-header" style="border-radius: 24px 24px 0 0;">
                <span>Payment</span>
            </div>
            <form class="pay-modal-form" onsubmit="showPayConfirmModal(); return false;">
                <div class="pay-modal-row">
                    <div class="pay-modal-group">
                        <label>Niche Id</label>
                        <input type="text" value="1F-01FB" readonly>
                    </div>
                </div>
                <div class="pay-modal-row">
                    <div class="pay-modal-group">
                        <label>Type</label>
                        <input type="text" value="Internment" readonly>
                    </div>
                    <div class="pay-modal-group">
                        <label>Payee Name</label>
                        <input type="text" value="Josephine Damdam Y." readonly>
                    </div>
                </div>
                <div class="pay-modal-section-title">Upload Files</div>
                <div class="pay-modal-upload">
                    <label for="pay-upload" class="pay-upload-box">
                        Upload Receipt here <span class="choose-files">choose files</span>
                        <input id="pay-upload" type="file" style="display:none;">
                    </label>
                    <div class="pay-upload-desc">Attach file. File size of your documents should not exceed 10MB</div>
                </div>
                <button class="pay-modal-submit" type="submit">Submit</button>
                <button class="pay-modal-cancel" type="button" onclick="showPendingRequestForm()">Cancel</button>
            </form>
        `;
    }
    function showPendingRequestForm() {
        setInitialStatusCards();
        document.querySelector('.status-card.pending').classList.add('active');
        document.querySelector('.status-card.approved').classList.remove('active');
        document.getElementById('billingRight').innerHTML = `
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
        `;
    }
    function showPayConfirmModal() {
        document.getElementById('payConfirmModal').classList.add('show');
        document.getElementById('payConfirmModalOverlay').classList.add('show');
    }
    function hidePayConfirmModal() {
        document.getElementById('payConfirmModal').classList.remove('show');
        document.getElementById('payConfirmModalOverlay').classList.remove('show');
    }
    function showSubmittedModal() {
        hidePayConfirmModal();
        document.getElementById('confirmModal').classList.add('show');
        document.getElementById('confirmModalOverlay').classList.add('show');
    }
    function hideConfirmModal() {
        document.getElementById('confirmModal').classList.remove('show');
        document.getElementById('confirmModalOverlay').classList.remove('show');
    }
    document.getElementById('payConfirmModalOverlay').onclick = hidePayConfirmModal;
    document.getElementById('confirmModalOverlay').onclick = hideConfirmModal;
    </script>
</body>
</html>