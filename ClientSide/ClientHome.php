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
        <div class="portal-section">
            <div class="portal-title mb-1">Your Portal</div>
            <div class="portal-desc">
                Easily view your profile, view cemetery, and request important documents—all in one convenient area.
            </div>
            <div class="row g-4">
                <!-- Profile Card -->
                <div class="col-lg-5">
                    <div class="profile-card">
                        <div class="profile-info">
                            <img src="https://ui-avatars.com/api/?name=Dy+sania&background=4d8fd3&color=fff&rounded=true&size=64" alt="Avatar" class="profile-avatar">
                            <div>
                                <div style="font-weight: 500;">Dy sania</div>
                                <div style="font-size: 0.97rem; color: #6c757d;">dysania@gmail.com</div>
                            </div>
                        </div>
                        <div class="profile-details">
                            <div><strong>Status:</strong> <span style="color: #28a745;">Approved</span></div>
                            <div><strong>Location:</strong> Biningan City</div>
                            <div><strong>Registered Since:</strong> April 21, 2020</div>
                        </div>
                        <button class="btn-view-profile">View Profile</button>
                    </div>
                </div>
                <!-- Request Card -->
                <div class="col-lg-7">
                    <div class="request-card">
                        <div>
                            <h5>Click Here to Fill Up a Request</h5>
                            <p>
                                Need a certificate, renewal, or niche assistance? We're here to help. Click the button below to access the request form. Fill out the required details, upload any supporting documents, and submit your request in just a few steps.
                            </p>
                        </div>
                        <div>
                            <button class="btn-request">Request Now</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

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