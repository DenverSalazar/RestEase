<?php include '../Includes/navbar2.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cemetery Mapping - RestEase</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/footer.css">
    <style>
        body {
            font-family: 'Poppins', Arial, sans-serif;
            background: #fff;
        }
        .portal-title {
            font-size: 1.45rem;
            font-weight: 500;
        }
        .portal-desc {
            color: #444;
            font-size: 1.04rem;
        }
        .map-section {
            margin-bottom: 48px;
        }
        .map-iframe {
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(44,62,80,0.08);
        }
        @media (max-width: 600px) {
            .map-iframe {
                height: 260px !important;
            }
        }
    </style>
</head>
<body>
    <div style="width:100%;display:flex;justify-content:flex-start;">
        <a href="javascript:history.back()" class="cert-list-back" style="color:#506C84;font-size:1.08rem;font-weight:500;margin:18px 0 0 120px;text-decoration:none;cursor:pointer;transition:color 0.18s;">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>
    <div style="height:48px;"></div>
    <div class="container">
        <div class="portal-title mb-1">Cemetery Mapping</div>
        <div class="portal-desc mb-4">
            Explore an interactive digital map that helps you easily locate burial plots, view grave details, and navigate the cemetery with ease and accuracy.
        </div>
        <section class="map-section">
            <!-- Embed the real interactive map, view-only, zoom in/out only, no navbar/footer -->
            <iframe
                class="map-iframe"
                src="ClientMap.php?embed=1"
                style="width:100%;height:500px;border:0;"
                allowfullscreen=""
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade">
            </iframe>
        </section>
    </div>

     <div class="container">
        <div class="portal-title mb-1">Other Cemtery Information Here</div>
        <div class="portal-desc mb-4">
            Explore an interactive digital map that helps you easily locate burial plots, view grave details, and navigate the cemetery with ease and accuracy.
        </div>
     </div>
    
    <?php include '../Includes/footer-client.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>