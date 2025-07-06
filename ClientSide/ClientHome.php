<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RestEase</title>
    <!-- Add Google Fonts for Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/footer.css">
    <link rel="stylesheet" href="../css/clienthome.css">
</head>
<body>
    <?php include '../Includes/navbar.php'; ?>
    <div class="main-bg-bar" style="padding-top: 100;">
        <div class="container">
            <div class="dashboard-header">
                <h2>Your trusted digital companion<br>for cemetery mapping and memorial services</h2>
                <p>#1 Online Platform for Niche Management & Certificate Services<br>in Padre Garcia, Batangas</p>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Your Portal Section -->
        <div class="pt-4 pb-2">
            <div class="portal-title mb-1" style="font-size:1.45rem;font-weight:500;">Your Portal</div>
            <div class="portal-desc mb-4" style="color:#444;font-size:1.04rem;">
                Easily view your profile, view cemetery, and request important documents—all in one convenient area.
            </div>
            <div class="row g-4">
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="shadow-sm rounded-4 p-4 h-100 text-center" style="background:#fff;">
                        <div style="font-weight:500;font-size:1.08rem;">Submit Request</div>
                        <div style="font-size:0.97rem;color:#6c757d;margin:12px 0 18px 0;">
                            Easily send your request for services or updates through the system.
                        </div>
                        <a href="clientrequest.php" class="btn btn-primary w-100 rounded-3" style="background:#3973f4;border:none;">View</a>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="shadow-sm rounded-4 p-4 h-100 text-center" style="background:#fff;">
                        <div style="font-weight:500;font-size:1.08rem;">Records</div>
                        <div style="font-size:0.97rem;color:#6c757d;margin:12px 0 18px 0;">
                            Easily send your request for services or updates through the system.
                        </div>
                        <a href="#" class="btn btn-primary w-100 rounded-3" style="background:#3973f4;border:none;">View</a>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="shadow-sm rounded-4 p-4 h-100 text-center" style="background:#fff;">
                        <div style="font-weight:500;font-size:1.08rem;">Certificate</div>
                        <div style="font-size:0.97rem;color:#6c757d;margin:12px 0 18px 0;">
                            Easily send your request for services or updates through the system.
                        </div>
                        <a href="#" class="btn btn-primary w-100 rounded-3" style="background:#3973f4;border:none;">View</a>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="shadow-sm rounded-4 p-4 h-100 text-center" style="background:#fff;">
                        <div style="font-weight:500;font-size:1.08rem;">Billing and Processing</div>
                        <div style="font-size:0.97rem;color:#6c757d;margin:12px 0 18px 0;">
                            Easily send your request for services or updates through the system.
                        </div>
                        <a href="#" class="btn btn-primary w-100 rounded-3" style="background:#3973f4;border:none;">View</a>
                    </div>
                </div>
            </div>
        </div>
        <!-- End Your Portal Section -->

        <!-- Map Section -->
        <section class="map-section">
            <div class="map-container">
                <!-- Embed the real interactive map, view-only, zoom in/out only, no navbar/footer -->
                <iframe
                    class="map-iframe"
                    src="ClientMap.php?embed=1"
                    style="width:100%;height:400px;border:0;"
                    allowfullscreen=""
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
            <a href="ClientMap.php" style="text-decoration:none;">
                <button class="btn-map-themed">
                    <i class="fas fa-map-marked-alt"></i>
                    View Cemetery Maps
                </button>
            </a>
        </section>
    </div> 
    <style>
    /* Themed button for View Cemetery Maps */
    .btn-map-themed {
        font-family: 'Poppins', sans-serif;
        background: linear-gradient(90deg, #4d8fd3 0%, #2d8cff 100%);
        color: #fff;
        border: none;
        border-radius: 30px;
        padding: 12px 32px;
        font-size: 1.1rem;
        font-weight: 500;
        box-shadow: 0 4px 16px rgba(77,143,211,0.12);
        transition: background 0.2s, box-shadow 0.2s, transform 0.1s;
        margin: 32px auto 0 auto;
        display: flex;
        align-items: center;
        gap: 12px;
        cursor: pointer;
        letter-spacing: 0.5px;
    }
    .btn-map-themed:hover, .btn-map-themed:focus {
        background: linear-gradient(90deg, #2d8cff 0%, #4d8fd3 100%);
        box-shadow: 0 6px 24px rgba(77,143,211,0.18);
        transform: translateY(-2px) scale(1.03);
        color: #fff;
    }
    .btn-map-themed i {
        font-size: 1.2em;
    }
    </style>

    <?php include '../Includes/footer.php'; ?>
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>