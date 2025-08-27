<?php
session_start();
include_once '../Includes/db.php';

// Get the latest request for the logged-in user
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$request = null;
$approved_request = null;
if ($user_id) {
    // Check for pending requests
    $stmt = $conn->prepare("SELECT * FROM client_requests WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $request = $result->fetch_assoc();
    $stmt->close();
    
    // Check for approved requests and get payment amount for THIS USER ONLY
    $stmt = $conn->prepare("SELECT ar.*, l.Amount as payment_amount FROM accepted_request ar LEFT JOIN ledger l ON ar.niche_id = l.ApartmentNo AND ar.informant_name = l.Payee AND l.user_id = ar.user_id WHERE ar.user_id = ? ORDER BY ar.created_at DESC LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $approved_request = $result->fetch_assoc();
    $stmt->close();
    
    // Debug: Check if we got the amount
    if ($approved_request) {
        error_log("Approved request found. Payment amount: " . ($approved_request['payment_amount'] ?? 'NULL'));
        error_log("Niche ID: " . ($approved_request['niche_id'] ?? 'NULL'));
        error_log("Informant: " . ($approved_request['informant_name'] ?? 'NULL'));
    }
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
           <?php if (!$request && !$approved_request): ?>
               <div class="status-card no-request">
                   <span class="no-request-icon"><i class="fas fa-exclamation-triangle"></i></span>
                   <div>
                       <div class="status-title">No Request</div>
                       <div class="status-type">Type: <span>-</span></div>
                   </div>
               </div>
           <?php elseif ($approved_request): ?>
               <div class="status-card pending">
                   <div class="status-title">Pending Request</div>
                   <div class="status-type">Type: <span><?php echo htmlspecialchars($approved_request['type']); ?></span></div>
                   <button class="pay-btn" type="button" onclick="showPendingRequestForm()">View</button>
               </div>
               <div class="status-card approved active">
                   <div>
                       <div class="status-title">Request Approved</div>
                       <div class="status-type">Type: <span><?php echo htmlspecialchars($approved_request['type']); ?></span></div>
                   </div>
                   <button class="pay-btn" type="button" onclick="showPayForm()">Pay</button>
               </div>
           <?php else: ?>
               <div class="status-card pending active">
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
           <?php if (!$request && !$approved_request): ?>
               <div class="no-request-container">
                   <span class="no-request-icon"><i class="fas fa-exclamation-triangle"></i></span>
                   <div class="no-request-title">No Pending Requests</div>
                   <div class="no-request-desc">You have no pending requests at this time.<br>Submit a new request to see it here.</div>
               </div>
           <?php elseif ($approved_request): ?>
           <div class="pending-info" style="background: #d4edda; color: #155724; border: 1px solid #c3e6cb;">Your request has been approved! You can now proceed with payment.</div>
           <form class="billing-form" onsubmit="return false;">
               <div class="form-group">
                   <label>Type</label>
                   <input type="text" value="<?php echo htmlspecialchars($approved_request['type']); ?>" readonly>
               </div>
               <div class="form-section">
                   <div class="section-title">Deceased Information</div>
                   <div class="form-row">
                       <div class="form-group">
                           <label>First Name</label>
                           <input type="text" value="<?php echo htmlspecialchars($approved_request['first_name']); ?>" readonly>
                       </div>
                       <div class="form-group">
                           <label>Last Name</label>
                           <input type="text" value="<?php echo htmlspecialchars($approved_request['last_name']); ?>" readonly>
                       </div>
                   </div>
                   <div class="form-row">
                       <div class="form-group">
                           <label>Niche ID</label>
                           <input type="text" value="<?php echo htmlspecialchars($approved_request['niche_id'] ?? 'N/A'); ?>" readonly>
                       </div>
                       <div class="form-group">
                           <label>Informant</label>
                           <input type="text" value="<?php echo htmlspecialchars($approved_request['informant_name']); ?>" readonly>
                       </div>
                   </div>
               </div>
               <div class="form-section">
                   <div class="section-title">Uploaded Files</div>
                   <div class="uploaded-file">
                       <i class="fas fa-file-pdf"></i>
                       <span class="file-name"><?php echo $approved_request['file_upload'] ? htmlspecialchars($approved_request['file_upload']) : 'BirthCert.pdf'; ?></span>
                   </div>
               </div>
           </form>
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
            <button class="pay-btn" type="button" onclick="showPendingRequestForm()">View</button>
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

    // Prepare JS variables with PHP values for the pending request
    const pendingRequest = {
        type: <?php echo json_encode($request['type'] ?? ''); ?>,
        first_name: <?php echo json_encode($request['first_name'] ?? ''); ?>,
        last_name: <?php echo json_encode($request['last_name'] ?? ''); ?>,
        middle_name: <?php echo json_encode($request['middle_name'] ?? ''); ?>,
        age: <?php echo json_encode($request['age'] ?? ''); ?>,
        dob: <?php echo json_encode($request['dob'] ?? ''); ?>,
        dod: <?php echo json_encode($request['dod'] ?? ''); ?>,
        residency: <?php echo json_encode($request['residency'] ?? ''); ?>,
        informant_name: <?php echo json_encode($request['informant_name'] ?? ''); ?>,
        file_upload: <?php echo json_encode($request['file_upload'] ?? 'BirthCert.pdf'); ?>
    };

    function showPendingRequestForm() {
        document.querySelector('.status-card.pending').classList.add('active');
        document.querySelector('.status-card.approved').classList.remove('active');
        document.getElementById('billingRight').innerHTML = `
            <div class="pending-info">Your request is still pending please wait...</div>
            <form class="billing-form" onsubmit="return false;">
                <div class="form-group">
                    <label>Type</label>
                    <input type="text" value="${pendingRequest.type}" readonly>
                </div>
                <div class="form-section">
                    <div class="section-title">Deceased Information</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>First Name</label>
                            <input type="text" value="${pendingRequest.first_name}" readonly>
                        </div>
                        <div class="form-group">
                            <label>Last Name</label>
                            <input type="text" value="${pendingRequest.last_name}" readonly>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Middle Name</label>
                            <input type="text" value="${pendingRequest.middle_name}" readonly>
                        </div>
                        <div class="form-group">
                            <label>Age</label>
                            <input type="text" value="${pendingRequest.age}" readonly>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Date of Birth</label>
                            <input type="text" value="${pendingRequest.dob}" readonly>
                        </div>
                        <div class="form-group">
                            <label>Date Died</label>
                            <input type="text" value="${pendingRequest.dod}" readonly>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Residency</label>
                            <input type="text" value="${pendingRequest.residency}" readonly>
                        </div>
                        <div class="form-group">
                            <label>Informant Name</label>
                            <input type="text" value="${pendingRequest.informant_name}" readonly>
                        </div>
                    </div>
                </div>
                <div class="form-section">
                    <div class="section-title">Uploaded Files</div>
                    <div class="uploaded-file">
                        <i class="fas fa-file-pdf"></i>
                        <span class="file-name">${pendingRequest.file_upload ? pendingRequest.file_upload : 'BirthCert.pdf'}</span>
                    </div>
                </div>
                <button class="cancel-btn" type="button" onclick="showCancelModal()">Cancel</button>
            </form>
        `;
    }
    
    function showPayForm() {
        // Get the real amount from PHP - ensure it's not empty
        const realAmount = '<?php 
            if (isset($approved_request['payment_amount']) && $approved_request['payment_amount'] && $approved_request['payment_amount'] > 0) {
                echo number_format($approved_request['payment_amount'], 2);
            } else {
                echo "0.00"; // Show 0.00 if no amount found to make it obvious
            }
        ?>';
        
        console.log('Real amount from PHP:', realAmount); // Debug log
        
        document.querySelector('.status-card.pending').classList.remove('active');
        document.querySelector('.status-card.approved').classList.add('active');
        document.getElementById('billingRight').innerHTML = `
            <div class="pay-modal-header" style="border-radius: 24px 24px 0 0;">
                <span>Payment</span>
            </div>
            <form class="pay-modal-form" onsubmit="showPayConfirmModal(); return false;">
                <div class="pay-modal-row">
                    <div class="pay-modal-group">
                        <label>Niche Id</label>
                        <input type="text" value="<?php echo htmlspecialchars($approved_request['niche_id'] ?? 'N/A'); ?>" readonly>
                    </div>
                </div>
                <div class="pay-modal-row">
                    <div class="pay-modal-group">
                        <label>Type</label>
                        <input type="text" value="<?php echo htmlspecialchars($approved_request['type'] ?? 'N/A'); ?>" readonly>
                    </div>
                    <div class="pay-modal-group">
                        <label>Payee Name</label>
                        <input type="text" value="<?php echo htmlspecialchars($approved_request['informant_name'] ?? 'N/A'); ?>" readonly>
                    </div>
                </div>
                
                <!-- GCash Payment Section -->
                <div class="pay-modal-section-title">GCash Payment</div>
                <div class="gcash-payment-container" style="background: #f8f9fa; border-radius: 12px; padding: 20px; margin-bottom: 20px; text-align: center;">
                    <div class="gcash-header" style="display: flex; align-items: center; justify-content: center; margin-bottom: 15px;">
                        <img src="../assets/gcash-logo.png" alt="GCash" style="height: 30px; margin-right: 10px;" onerror="this.style.display='none'">
                        <h4 style="color: #007bff; margin: 0;">Scan QR Code to Pay</h4>
                    </div>
                    <div class="qr-code-container" style="background: white; padding: 20px; border-radius: 8px; display: inline-block; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                        <img src="../assets/gcash-qr.png" alt="GCash QR Code" style="width: 200px; height: 200px;" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjBmMGYwIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzk5OSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPkdDYXNoIFFSPC90ZXh0Pjwvc3ZnPg=='">
                    </div>
                    <div class="payment-instructions" style="margin-top: 15px; color: #666; font-size: 14px;">
                        <p style="margin: 5px 0;"><strong>Instructions:</strong></p>
                        <p style="margin: 5px 0;">1. Open your GCash app</p>
                        <p style="margin: 5px 0;">2. Scan the QR code above</p>
                        <p style="margin: 5px 0;">3. Complete the payment</p>
                        <p style="margin: 5px 0;">4. Take a screenshot of the receipt</p>
                        <p style="margin: 5px 0;">5. Upload the receipt below</p>
                    </div>
                    <div class="amount-info" style="background: #e3f2fd; padding: 10px; border-radius: 6px; margin-top: 15px;">
                        <strong style="color: #1976d2;">Amount: ₱<?php 
                            if (isset($approved_request['payment_amount']) && $approved_request['payment_amount'] && $approved_request['payment_amount'] > 0) {
                                echo number_format($approved_request['payment_amount'], 2);
                            } else {
                                echo "0.00"; // Show 0.00 if no amount found
                            }
                        ?></strong>
                        <br><small style="color: #666;">Cemetery Service Fee</small>
                        <br><small style="color: #999; font-size: 11px;">Debug: <?php 
                            echo "Niche: " . ($approved_request['niche_id'] ?? 'NULL') . 
                                 ", Amount: " . ($approved_request['payment_amount'] ?? 'NULL');
                        ?></small>
                    </div>
                </div>
                
                <div class="pay-modal-section-title">Upload Payment Receipt</div>
                <div class="pay-modal-upload">
                    <label for="pay-upload" class="pay-upload-box">
                        Upload GCash Receipt here <span class="choose-files">choose files</span>
                        <input id="pay-upload" type="file" accept="image/*,.pdf" style="display:none;">
                    </label>
                    <div class="pay-upload-desc">Upload your GCash payment receipt. Accepted formats: JPG, PNG, PDF. Max size: 10MB</div>
                    <div id="upload-preview" style="margin-top: 10px; display: none;">
                        <div style="background: #e8f5e8; padding: 10px; border-radius: 6px; display: flex; align-items: center;">
                            <i class="fas fa-check-circle" style="color: #28a745; margin-right: 8px;"></i>
                            <span id="upload-filename"></span>
                        </div>
                    </div>
                </div>
                <button class="pay-modal-submit" type="submit">Submit Payment</button>
                <button class="pay-modal-cancel" type="button" onclick="showApprovedRequestForm()">Cancel</button>
            </form>
        `;
        
        // Add file upload preview functionality
        document.getElementById('pay-upload').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                document.getElementById('upload-filename').textContent = file.name;
                document.getElementById('upload-preview').style.display = 'block';
            }
        });
    }
    
    function showApprovedRequestForm() {
        document.querySelector('.status-card.pending').classList.remove('active');
        document.querySelector('.status-card.approved').classList.add('active');
        location.reload(); // Reload to show approved request form
    }
    function showPayConfirmModal() {
        const uploadInput = document.getElementById('pay-upload');
        if (!uploadInput.files.length) {
            alert('Please upload your GCash payment receipt first.');
            return false;
        }
        
        // Show confirmation modal
        const confirmModal = document.createElement('div');
        confirmModal.innerHTML = `
            <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; display: flex; align-items: center; justify-content: center;">
                <div style="background: white; padding: 30px; border-radius: 12px; max-width: 400px; text-align: center;">
                    <i class="fas fa-check-circle" style="font-size: 48px; color: #28a745; margin-bottom: 15px;"></i>
                    <h3 style="margin-bottom: 15px;">Payment Submitted!</h3>
                    <p style="color: #666; margin-bottom: 20px;">Your payment receipt has been uploaded successfully. We will verify your payment and update your request status.</p>
                    <button onclick="this.parentElement.parentElement.remove(); location.reload();" style="background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer;">OK</button>
                </div>
            </div>
        `;
        document.body.appendChild(confirmModal);
        
        // Here you would typically submit the form data to a PHP script
        // For now, we'll just show the confirmation
        return false;
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














