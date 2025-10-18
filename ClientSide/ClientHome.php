<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: ../login.php"); // Adjust the path if needed
    exit;
}

// Personalized Welcome Section DB connection and user fetch
include '../Includes/db.php'; // make sure this connects to your DB

$user_id = $_SESSION['user_id'];
$query = $conn->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();
$user = $result->fetch_assoc();
$username = isset($user['first_name'], $user['last_name']) ? $user['first_name'] . ' ' . $user['last_name'] : 'User';
?>


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
    <style>
        /* Hero responsive tweaks */
        .main-bg-bar {
            padding-top: 140px; /* desktop default - unchanged on large screens */
            padding-bottom: 40px;
            transition: padding 200ms ease;
        }

        .dashboard-header.hero-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
        }

        .dashboard-header.hero-container h2 {
            font-size: 1.6rem;
            line-height: 1.12;
            margin: 0 0 8px 0;
        }

        .dashboard-header.hero-container p {
            margin: 0;
            color: #444;
        }

        .dashboard-header.hero-container img {
            max-width: 420px;
            width: 100%;
            height: auto;
            border-radius: 16px;
            object-fit: cover;
            display: block;
        }

        /* Medium screens */
        @media (max-width: 992px) {
            .main-bg-bar { padding-top: 110px; } /* keep medium unchanged */
            .dashboard-header.hero-container img { max-width: 320px; }
        }

        /* Small screens: stack content, center text, reduce spacing */
        @media (max-width: 768px) {
            /* use CSS variable --nav-height for the actual navbar height (set by JS) */
            .main-bg-bar { padding-top: calc(70px + var(--nav-height, 20px)); padding-bottom: 24px; }
            .dashboard-header.hero-container {
                flex-direction: column; /* text first, image below on narrow screens */
                align-items: center;
                text-align: center;
            }
            .dashboard-header.hero-container > div { width: 100%; }
            .dashboard-header.hero-container h2 {
                font-size: 1.25rem;
                line-height: 1.18;
            }
            .dashboard-header.hero-container p {
                font-size: 0.98rem;
            }
            .dashboard-header.hero-container img {
                max-width: 260px;
                width: 60%;
                margin-top: 16px; /* breathing space between text and image */
            }
        }

        /* Very small phones */
        @media (max-width: 420px) {
            /* include navbar height here as well */
            .main-bg-bar { padding-top: calc(54px + var(--nav-height, 20px)); padding-bottom: 18px; }
            .dashboard-header.hero-container h2 { font-size: 1.05rem; }
            .dashboard-header.hero-container img { max-width: 220px; width: 72%; }
        }

        /* Map responsive wrapper */
        .map-section .map-responsive {
            position: relative;
            width: 100%;
            max-width: 100%;
            /* preserve original desktop aspect ratio (450 / 1295 ≈ 34.7%) */
            padding-top: 34.7%;
            margin-bottom: 12px;
        }
        .map-section .map-responsive iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: 0;
        }

        /* Slightly taller map on small devices for usability */
        @media (max-width: 768px) {
            .map-section .map-responsive { padding-top: 56.25%; } /* 16:9 for mobile */
        }
    </style>
</head>
<body>
    <?php include '../Includes/navbar2.php'; ?>

    <div class="main-bg-bar">
        <div class="container">
            <div class="dashboard-header hero-container d-flex align-items-center justify-content-between">
                <div>
                    <h2>Your trusted digital companion<br>for cemetery mapping and memorial services</h2>
                    <p>#1 Online Platform for Niche Management & Certificate Services<br>in Padre Garcia, Batangas</p>
                </div>
                <img src="../assets/explore.png" alt="RestEase Hero" style="max-width:420px;width:100%;height:auto;border-radius:16px;">
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
                    <div class="shadow-sm rounded-4 p-4 h-100 text-center" style="background:#fff; border:2px solid #0077b6;">
                        <div style="font-weight:500;font-size:1.08rem;">
                            <img src="../assets/send.png" alt="Submit Request" style="width:22px;height:22px;margin-right:7px;vertical-align:middle;">
                            Submit Request
                        </div>
                        <div style="font-size:0.97rem;color:#6c757d;margin:12px 0 18px 0;">
                            Easily send your request for services or updates through the system.
                        </div>
                        <a href="clientrequest.php" class="btn btn-primary w-100 rounded-3 view-btn" style="background:#0077b6;border:none;">Request</a>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="shadow-sm rounded-4 p-4 h-100 text-center" style="background:#fff; border:2px solid #0077b6;">
                        <div style="font-weight:500;font-size:1.08rem;">
                            <img src="../assets/recordsicon.png" alt="Records" style="width:22px;height:22px;margin-right:7px;vertical-align:middle;">
                            Records
                        </div>
                        <div style="font-size:0.97rem;color:#6c757d;margin:12px 0 18px 0;">
                            Quickly access and review stored cemetery and client records.
                        </div>
                        <a href="clientrecords.php" class="btn btn-primary w-100 rounded-3 view-btn" style="background:#0077b6;border:none;">View</a>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="shadow-sm rounded-4 p-4 h-100 text-center" style="background:#fff; border:2px solid #0077b6;">
                        <div style="font-weight:500;font-size:1.08rem;">
                            <img src="../assets/cert.png" alt="Certificate" style="width:22px;height:22px;margin-right:7px;vertical-align:middle;">
                            Certificate
                        </div>
                        <div style="font-size:0.97rem;color:#6c757d;margin:12px 0 18px 0;">
                           View and manage issued or renewal certificates online.<br> </br>
                        </div>
                        <a href="clientcert.php" class="btn btn-primary w-100 rounded-3 view-btn" style="background:#0077b6;border:none;">View</a>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="shadow-sm rounded-4 p-4 h-100 text-center" style="background:#fff; border:2px solid #0077b6;">
                        <div style="font-weight:500;font-size:1.08rem;">
                            <img src="../assets/status.png" alt="Track Status" style="width:22px;height:22px;margin-right:7px;vertical-align:middle;">
                            Track Status
                        </div>
                        <div style="font-size:0.97rem;color:#6c757d;margin:12px 0 18px 0;">
                            Monitor the progress of your requests and see admin updates.
                        </div>
                        <a href="track.php" class="btn btn-primary w-100 rounded-3 view-btn" style="background:#0077b6;border:none;">View</a>
                    </div>
                </div>
            </div>
        </div>
        <!-- End Your Portal Section -->

        <!-- Map Section -->

        <!-- Add extra spacing between Portal and Cemetery Mapping -->
        <div style="height:48px;"></div>

        <div class="portal-title mb-1" style="font-size:1.45rem;font-weight:500;">Cemetery Mapping</div>
        <div class="portal-desc mb-4" style="color:#444;font-size:1.04rem;">
             Explore an interactive digital map that helps you easily locate burial plots, view grave details, and navigate the cemetery with ease and accuracy.
        </div>
        <section class="map-section">
            <!-- Responsive map wrapper -->
            <div class="map-responsive">
                <iframe
                    class="map-iframe"
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d978.4400336587773!2d121.21520691399589!3d13.874290423281638!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x33bd145514365b4f%3A0xbd29096ad6542262!2sPadre%20Garcia%20Municipal%20Cemetery!5e1!3m2!1sen!2sph!4v1760685714850!5m2!1sen!2sph"
                    allowfullscreen=""
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>

            <a href="ViewMap.php" style="text-decoration:none;">
                <button class="btn-map-themed" style="background:#0077b6;">
                    <i class="fas fa-map-marked-alt"></i>
                    View Cemetery Maps
                </button>
            </a>
        </section>
    </div> 

    <?php include '../Includes/footer-client.php'; ?>
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Measure navbar height and set CSS variable so mobile spacing accounts for the fixed navbar -->
    <script>
        (function() {
            function updateNavHeightVar() {
                var nav = document.querySelector('nav'); // matches your included navbar
                if (!nav) return;
                var h = nav.getBoundingClientRect().height || 0;
                // Set --nav-height on :root so CSS calc() uses the real navbar height
                document.documentElement.style.setProperty('--nav-height', h + 'px');
            }
            // Update on load and when the window resizes or orientation changes
            document.addEventListener('DOMContentLoaded', updateNavHeightVar);
            window.addEventListener('resize', updateNavHeightVar);
            window.addEventListener('orientationchange', updateNavHeightVar);
            // Also run shortly after load to handle fonts/images affecting layout
            window.setTimeout(updateNavHeightVar, 300);
        })();
    </script>
</body>
</html>