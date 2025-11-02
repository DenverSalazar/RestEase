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
    <link rel="icon" type="image/png" href="../assets/re logo blue.png">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/footer.css">
    <style>
      /* --- Begin: Certificate Fonts --- */
      @font-face {
        font-family: 'Bernard MT Std Condensed';
        src: url('../assets/fonts/Bernard MT Std Condensed/Bernard MT Std Condensed.otf') format('opentype');
        font-display: swap;
        font-weight: normal;
        font-style: normal;
      }
      @font-face {
        font-family: 'Rockwell Nova';
        src: url('../assets/fonts/rockwell-nova/RockwellNova-Bold.ttf') format('truetype');
        font-weight: 700;
        font-style: normal;
        font-display: swap;
      }
      /* --- End: Certificate Fonts --- */
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
      /* Certificate Preview Modal and Page Styling */
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
        background: transparent; /* make transparent so page look is exact */
        border-radius: 16px;
        padding: 18px;
        max-width: 100%;
        width: 100%;
        position: relative;
        max-height: 100vh;
        overflow: auto; /* allow scrolling around the fixed page on small devices */
        overflow-x: hidden; /* prevent horizontal scroll caused by close button/box-shadow */
        display: flex;
        align-items: flex-start;
        justify-content: center;
      }
      .cert-preview-wrapper {
        width: 900px;
        display: flex;
        justify-content: center;
        transform-origin: top center;
      }
      .cert-preview-page {
        width: 850px;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 8px 32px rgba(60,60,60,0.18),0 1.5px 6px rgba(0,0,0,0.08);
        padding: 32px 40px;
        font-family: 'Poppins', sans-serif;
        color: #222;
        box-sizing: border-box;
        position: relative;
      }
      .cert-close-btn {
        position: absolute;
        top: 12px;
        right: 20px; /* move the X further inside to avoid expanding page width */
        background: rgba(255,255,255,0.9);
        border: 0;
        padding: 6px 10px;
        font-size: 20px;
        line-height: 1;
        cursor: pointer;
        color: #444;
        border-radius: 6px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.08);
        z-index: 20;
      }
      .cert-close-btn:hover { color:#111; }
      @media print { .cert-close-btn { display:none !important; } }

      /* Header and Title Styling */
      .cert-preview-header {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 28px;
        flex-wrap: nowrap;
      }
      .cert-preview-header img {
        max-height: 80px;
        width: auto;
      }
      .cert-title {
        font-family: 'Rockwell Nova', 'Times New Roman', serif;
        font-size: 20px;
        font-weight: 700;
        margin-top: 6px;
        letter-spacing: 1px;
      }
      .bernard-title {
        font-family: 'Bernard MT Std Condensed', 'Times New Roman', serif;
        font-size: 22px;
        font-weight: 900;
        letter-spacing: 10px;
        margin-top: 0;
        margin-bottom: 0;
        white-space: nowrap;
      }
      .mc-no {
        text-align: right;
        margin-top: 8px;
        font-weight: 700;
        background: yellow;
        display: inline-block;
        padding: 4px 10px;
        font-size: 15px;
      }
      .cert-body {
        margin-top: 18px;
        font-size: 14px;
        line-height: 1.35;
      }
      .signatures {
        margin-top: 36px;
        display: flex;
        justify-content: space-between;
      }
      .signature-block {
        width: 45%;
        text-align: left;
      }
      .cert-footer {
        margin-top: 36px;
        text-align: center;
      }
      @media (max-width: 900px) {
        .cert-preview-wrapper { width: 100vw; }
        .cert-preview-page { width: 100vw; padding: 12px 2vw; }
        .cert-preview-header img { max-height: 60px; }
      }
      @media print {
        body * { visibility: hidden; }
        #certPreviewModal, #certPreviewModal * { visibility: visible; }
        #certPreviewModal { position: absolute; left:0; top:0; width:100%; }
        .cert-preview-page { box-shadow: none; border-radius: 0; margin: 0 auto; }
        .cert-close-btn { display: none !important; }
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
      /* Certificate preview modal content: keep the modal shell but provide a fixed "page" layout */
      #certPreviewContent {
        background: transparent; /* make transparent so page look is exact */
        border-radius: 16px;
        padding: 18px;
        max-width: 100%;
        width: 100%;
        position: relative;
        max-height: 100vh;
        overflow: auto; /* allow scrolling around the fixed page on small devices */
        overflow-x: hidden; /* prevent horizontal scroll caused by close button/box-shadow */
        display: flex;
        align-items: flex-start;
        justify-content: center;
      }
      /* Wrapper that will scale the fixed page to fit small viewports */
      .cert-preview-wrapper {
        width: 820px; /* design width in px (approx A4/letter sized layout) */
        display: flex;
        justify-content: center;
        transform-origin: top center;
      }
      /* The fixed page: exact layout preserved (use px units to prevent responsive text resizing) */
      .cert-preview-page {
        width: 800px; /* final page width */
        background: #fff;
        border-radius: 6px;
        box-shadow: 0 8px 32px rgba(60,60,60,0.18),0 1.5px 6px rgba(0,0,0,0.08);
        padding: 28px 36px;
        font-family: 'Poppins', sans-serif;
        color: #222;
        box-sizing: border-box;
        position: relative; /* allow internal absolute close button */
      }
      /* Close button that sits inside the certificate page (upper-right) */
      .cert-close-btn {
        position: absolute;
        top: 12px;
        right: 20px; /* move the X further inside to avoid expanding page width */
        background: rgba(255,255,255,0.9);
        border: 0;
        padding: 6px 10px;
        font-size: 20px;
        line-height: 1;
        cursor: pointer;
        color: #444;
        border-radius: 6px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.08);
        z-index: 20;
      }
      .cert-close-btn:hover { color:#111; }
      @media print { .cert-close-btn { display:none !important; } }

      /* Force header to not reflow; use fixed img heights */
      .cert-preview-header {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 28px;
        flex-wrap: nowrap;
      }
      .cert-preview-header img {
        max-height: 80px;
        width: auto;
      }
      .cert-title {
        font-family: 'Rockwell Nova', 'Times New Roman', serif;
        font-size: 20px;
        font-weight: 700;
        margin-top: 6px;
        letter-spacing: 1px;
      }
      .bernard-title {
        font-family: 'Bernard MT Std Condensed', 'Times New Roman', serif;
        font-size: 22px;
        font-weight: 900;
        letter-spacing: 10px;
        margin-top: 0;
        margin-bottom: 0;
        white-space: nowrap;
      }
      .mc-no {
        text-align: right;
        margin-top: 8px;
        font-weight: 700;
        background: yellow;
        display: inline-block;
        padding: 4px 10px;
        font-size: 15px;
      }
      .cert-body {
        margin-top: 18px;
        font-size: 14px;
        line-height: 1.35;
      }
      .signatures {
        margin-top: 36px;
        display: flex;
        justify-content: space-between;
      }
      .signature-block {
        width: 45%;
        text-align: left;
      }
      .cert-footer {
        margin-top: 36px;
        text-align: center;
      }
      @media (max-width: 900px) {
        .cert-preview-wrapper { width: 100vw; }
        .cert-preview-page { width: 100vw; padding: 12px 2vw; }
        .cert-preview-header img { max-height: 60px; }
      }
      @media print {
        body * { visibility: hidden; }
        #certPreviewModal, #certPreviewModal * { visibility: visible; }
        #certPreviewModal { position: absolute; left:0; top:0; width:100%; }
        .cert-preview-page { box-shadow: none; border-radius: 0; margin: 0 auto; }
        .cert-close-btn { display: none !important; }
      }

      .cert-list-container {
        margin-top: 24px;
        margin-bottom: 12px;
        width: 100%;
        max-width: 1300px;
        overflow-x: auto;
        padding: 0 12px;
      }
      .cert-list-title {
        font-size: 1.6rem;
        font-weight: 600;
        margin-bottom: 12px;
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
        display: flex;
        flex-direction: column;
        gap: 8px;
      }

      /* Compact card style: white interior, thin colored border similar to track.php */
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
        color: #3a4954;
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

      /* Responsive: keep compact look on small screens (buttons stack) */
      @media (max-width: 700px) {
        .cert-list-container {
          padding: 0 2vw;
        }
        .cert-list-item {
          flex-direction: column;
          align-items: flex-start;
          padding: 10px;
        }
        .cert-list-actions {
          margin-top: 8px;
          width: 100%;
          justify-content: center;
        }
        .cert-list-details { white-space: normal; font-size: 0.85rem; }
      }

      /* Move back button upward and reduce left margin on very small devices */
      @media (max-width: 480px) {
        .cert-list-back {
          display: inline-block;
          transform: translateY(-6px);
          margin-left: 12px !important;
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
                 <span class="mc-no">MC No: <strong><?php echo htmlspecialchars($cert['MCNo']); ?></strong></span>
               </div>
                 <div class="cert-list-date">
                   Date Paid: <?php echo htmlspecialchars($cert['DatePaid']); ?>
                 </div>
               </div>
               <div class="cert-list-actions">
                 <!-- Print removed: only View is available -->
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
             <div id="certPreviewBody"></div>
           </div>
         </div>
         <script>
           // Certificates data for JS
           var certificates = <?php echo json_encode($certificates); ?>;
           function showCertPreview(idx, doPrint) {
             var cert = certificates[idx];
             var actions = [
               { key: 'DNew', label: 'register the death of', tail: ' and rent CRYPT for five (5) years' },
               { key: 'DRenew', label: 'renewal of CRYPT of', tail: '' },
               { key: 'DTransfer', label: 'transfer the remains of', tail: '' },
               { key: 'DReOpen', label: 're-open the tomb of', tail: '' },
               { key: 'DReEnter', label: 're-enter the remains of', tail: '' }
             ];
             var actionsHtml = '';
             actions.forEach(function(action, i) {
               var checked = cert[action.key] === '✔' ? 'checked' : '';
               var namePart = checked ? ' <strong>' + (cert.NameOfDeceased || '') + '</strong>' : '';
               var desc = action.label + namePart + action.tail;
               actionsHtml += '<li style="margin-bottom:12px;"><input type="checkbox" ' + checked + ' disabled style="margin-right:8px;"> ' + desc + '</li>';
             });
             var adminName = cert.AdminName ? cert.AdminName.toUpperCase() : '';
             // Use Bernard font for CERTIFICATION, Rockwell Nova for office title, and layout from admin certificate
             var html = `
               <div class="cert-preview-wrapper" id="certPreviewWrapper">
                 <div class="cert-preview-page">
                   <button class="cert-close-btn" onclick="closeCertPreview()" aria-label="Close">&times;</button>
                   <!-- Certificate background image (behind content, covers area, low opacity) -->
                   <img src="../assets/certbg.png" alt="Certificate Background"
                        style="position:absolute;top:50%;left:50%;width:70%;height:auto;transform:translate(-50%,-50%);z-index:0;pointer-events:none;opacity:0.22;">
                   <div style="position:relative;z-index:1;">
                     <div class="cert-preview-header">
                       <img src="../assets/Logo garcia.png" alt="Padre Garcia Icon">
                       <div style="text-align:center;">
                         <div style="font-family:'Times New Roman', Times, serif;font-size:1.15rem;line-height:1.3;margin-bottom:2px;">
                           Republic of the Philippines<br>
                           Province of Batangas<br>
                           MUNICIPALITY OF PADRE GARCIA
                         </div>
                         <div class="cert-title">
                           OFFICE OF THE MUNICIPAL MAYOR
                         </div>
                         <hr style="border-top:4px solid #222;margin:12px 0;">
                         <div class="bernard-title">CERTIFICATION</div>
                       </div>
                       <img src="../assets/Seal_of_Batangas.png" alt="Batangas Seal">
                     </div>
                     <div style="margin-top:12px;display:flex;justify-content:flex-end; padding-right: 40px;">
                       <span class="mc-no">
                         MC No. ${cert.MCNo ? cert.MCNo : '<span style="color:#e74c3c;">No data</span>'}
                       </span>
                     </div>
                     <div class="cert-body">
                       <p>This is to certify that <strong>${cert.InformantName || ''}</strong> of Barangay <strong>${cert.InformantAddress || ''}</strong></p>
                       <ul style="list-style:none;padding-left:0;">
                         ${actionsHtml}
                       </ul>
                       <p>
                         Who died last <strong>${cert.DateDied ? cert.DateDied : ''}</strong> and was buried at the Municipal Cemetery.<br>
                         Issued this <strong>${cert.DatePaid ? cert.DatePaid : ''}</strong> upon the request of Mr./Ms. <strong>${cert.InformantName || ''}</strong> for whatever purpose it may serve.<br>
                         Apartment No. <strong>${cert.AptNo || ''}</strong>
                       </p>
                       <div class="signatures">
                         <div class="signature-block">
                           <strong>Recommending Approval:</strong><br><br>
                           <div style="height:48px;"></div>
                           <strong>${adminName}</strong><br>
                           MPDC/ZA
                         </div>
                         <div class="signature-block" style="text-align:right;">
                           <strong>Approved by:</strong><br><br>
                           <div style="height:48px;"></div>
                           <strong>${adminName}</strong><br>
                           Department Head
                         </div>
                       </div>
                       <div style="margin-top:28px;">
                         <strong>OR No.:</strong> ${cert.ORNumber ? cert.ORNumber : '<span style="color:#e74c3c;">No data</span>'}<br>
                         <strong>Date Paid:</strong> ${cert.DatePaid ? cert.DatePaid : '<span style="color:#e74c3c;">No data</span>'}<br>
                         <strong>Amount:</strong> ${cert.Amount !== null && cert.Amount !== undefined ? '₱' + parseFloat(cert.Amount).toLocaleString('en-US', {minimumFractionDigits:2}) : '<span style="color:#e74c3c;">No data</span>'}<br>
                         <strong>Renewal:</strong> ${cert.Validity ? cert.Validity.substr(0,7) : ''}
                       </div>
                       <div class="cert-footer">
                         <img src="../assets/certfooter.png" alt="Certificate Footer" style="max-width:100%;height:auto;">
                       </div>
                     </div>
                   </div>
                 </div>
               </div>
             `;
             var body = document.getElementById('certPreviewBody');
             body.innerHTML = html;

             // Compute scale so the certificate page fits the viewport as large as possible
             // but never becomes too small. Do not upscale beyond 1.
             function adjustScaleForWrapper() {
               var wrapper = document.getElementById('certPreviewWrapper');
               if (!wrapper) return;
               var page = wrapper.querySelector('.cert-preview-page');
               if (!page) return;
               // actual page dimensions
               var pageW = page.offsetWidth;
               var pageH = page.offsetHeight;
               // available viewport (leave small padding so close button isn't clipped)
               var availW = Math.max(window.innerWidth - 24, 200);
               var availH = Math.max(window.innerHeight - 48, 200);
               // compute scale that fits both width and height
               var rawScale = Math.min(availW / pageW, availH / pageH);
               // clamp scale between minScale and 1
               var minScale = 0.85; // avoid rendering too small
               var scale = Math.min(1, Math.max(minScale, rawScale));
               // apply transform
               wrapper.style.transform = 'scale(' + scale + ')';
               wrapper.style.transformOrigin = 'top center';
               // always center modal content so scaled certificate is visible and not tucked to top
               var modal = document.getElementById('certPreviewModal');
               if (modal) {
                 modal.style.alignItems = 'center';
               }
             }
             // keep reference so resize listener can reuse the same logic
             window._adjustCertScale = adjustScaleForWrapper;

            // Show modal BEFORE measuring so offsets/offsetHeight reflect the rendered certificate.
            var modalEl = document.getElementById('certPreviewModal');
            modalEl.style.display = 'flex';

            // If printing requested, set up small fallback timer and afterprint handler.
            var printTimer = null;
            var afterPrintHandler = null;
            if (doPrint) {
              // fallback in case images/events are slow
              printTimer = setTimeout(function(){ window.print(); }, 1200);
              afterPrintHandler = function() {
                // hide modal after printing
                modalEl.style.display = 'none';
                try { window.removeEventListener('afterprint', afterPrintHandler); } catch(e){}
              };
              window.addEventListener('afterprint', afterPrintHandler);
            }

            // Call adjust immediately and again after short delays to catch any late rendering.
            adjustScaleForWrapper();
            setTimeout(adjustScaleForWrapper, 60);
            setTimeout(adjustScaleForWrapper, 300);

            // Also wait for images inside the certificate to load, then adjust again and trigger print if requested.
            (function waitImagesThenAdjust(){
              var wrapper = document.getElementById('certPreviewWrapper');
              if (!wrapper) {
                if (doPrint && printTimer === null) printTimer = setTimeout(function(){ window.print(); }, 800);
                return;
              }
              var page = wrapper.querySelector('.cert-preview-page');
              if (!page) {
                if (doPrint && printTimer === null) printTimer = setTimeout(function(){ window.print(); }, 800);
                return;
              }
              var imgs = page.querySelectorAll('img');
              if (!imgs || imgs.length === 0) {
                // no images - call print after adjustments
                adjustScaleForWrapper();
                if (doPrint) {
                  clearTimeout(printTimer);
                  setTimeout(function(){
                    window.print();
                  }, 50);
                }
                return;
              }
              var remaining = imgs.length;
              function oneDone() {
                remaining--;
                if (remaining <= 0) {
                  // final adjustment after all images settle
                  adjustScaleForWrapper();
                  if (doPrint) {
                    clearTimeout(printTimer);
                    setTimeout(function(){
                      window.print();
                    }, 50);
                  }
                }
              }
              imgs.forEach(function(img){
                if (img.complete) {
                  oneDone();
                } else {
                  img.addEventListener('load', oneDone, { once: true });
                  img.addEventListener('error', oneDone, { once: true });
                }
              });
            })();
          }
          function closeCertPreview() {
            document.getElementById('certPreviewModal').style.display = 'none';
          }
          // Re-adjust scale on orientation/resize while modal open
          window.addEventListener('resize', function(){
            if (typeof window._adjustCertScale === 'function') window._adjustCertScale();
          });
        </script>
       <?php endif; ?>
     </div>
   </div>

   <?php include '../Includes/footer-client.php'; ?>
    <!-- Bootstrap JS (optional, for responsive navbar) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
</body>
</html>

