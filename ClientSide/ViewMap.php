<?php include '../Includes/navbar2.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cemetery Mapping - RestEase</title>
    <link rel="icon" type="image/png" href="../assets/re logo blue.png">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/footer.css">
    <style>
        /* filepath: c:\xampp\htdocs\RestEase\ClientSide\ViewMap.php */
        body {
            font-family: 'Poppins', Arial, sans-serif;
            background: #fff;
            -webkit-font-smoothing:antialiased;
            -moz-osx-font-smoothing:grayscale;
        }
        /* Removed global .container override to preserve original navbar styles.
           Use .viewmap-container to scope page padding/layout instead. */
        .viewmap-container { max-width:1300px; padding: 24px 16px; margin: 0 auto; }
        .portal-title {
            font-size: 1.6rem;
            font-weight: 600;
            margin-bottom: 6px;
        }
        .portal-desc {
            color: #444;
            font-size: 1.02rem;
            margin-bottom: 18px;
        }
        .map-section { margin-bottom: 28px; }

        /* Map iframe responsive */
        .map-iframe {
            border-radius: 12px;
            box-shadow: 0 8px 28px rgba(2,6,23,0.06);
            width: 100%;
            height: 85vh;
            min-height: 420px;
            display: block;
            border: 0;
        }

        /* Info panel */
        .info-panel { margin-top:20px; display:grid; grid-template-columns: 1fr; gap:18px; align-items:start; }
        @media(min-width:900px){ .info-panel { grid-template-columns: 1fr 360px; gap:22px; } }

        .info-left { background:#fff;border-radius:12px;padding:22px;box-shadow:0 8px 30px rgba(2,6,23,0.04); }
        .info-right { background:linear-gradient(180deg,#fff,#fbfdff);border-radius:12px;padding:18px;box-shadow:0 6px 20px rgba(2,6,23,0.04); }

        .stats-grid { display:grid; grid-template-columns: repeat(3,1fr); gap:12px; margin-bottom:14px; }
        @media(max-width:640px){ .stats-grid { grid-template-columns: repeat(2,1fr); } }
        @media(max-width:420px){ .stats-grid { grid-template-columns: 1fr; } }

        .stat-card { background:linear-gradient(180deg,#ffffff,#fbfdff); padding:14px;border-radius:10px; display:flex; align-items:center; gap:12px; border:1px solid rgba(15,23,42,0.03); min-height:64px; }
        .stat-icon { width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;background:#f1f5f9;color:#0f172a;font-weight:700;font-size:1.05rem; }
        .stat-value { font-size:1.15rem;font-weight:700; color:#0f172a; line-height:1; }
        .stat-label { font-size:0.86rem;color:#6b7280; }

        .features-list { display:flex; flex-direction:column; gap:12px; margin-top:8px; }
        .feature-item { display:flex; gap:12px; align-items:flex-start; }
        .feature-item i { color:#2563eb; font-size:1.15rem; margin-top:4px; }

        .faq { margin-top:18px; }
        .faq .card { border-radius:8px; border:1px solid #eef2f6; box-shadow:none; overflow:hidden; }
        .faq .card-header { background:transparent; border-bottom:1px solid #f5f7fa; cursor:pointer; padding:12px 16px; font-weight:600; }
        .faq .card-body { padding:12px 16px; color:#444; font-size:0.95rem; }

        .contact-card { display:flex; flex-direction:column; gap:12px; align-items:flex-start; }
        .contact-avatar { width:64px;height:64px;border-radius:12px; background:linear-gradient(135deg,#506C84,#27a3d6); color:#fff; display:flex;align-items:center;justify-content:center; font-weight:700; font-size:1.3rem; }
        .cta-btn { background:#0077b6;color:#fff;padding:10px 14px;border-radius:10px;border:none;font-weight:700; text-decoration:none; display:inline-block; width:100%; text-align:center; }
        .muted { color:#6b7280; font-size:0.95rem; }

        /* Mobile CTA: hidden by default, shown below map on small devices */
        .cta-mobile { display: none; width: 100%; margin-top: 12px; }

        /* Visitor tips card spacing */
        .info-right .muted ul { margin:0; padding-left:16px; }

        /* Back button (responsive) */
        .page-back {
            color:#506C84;
            font-size:1.08rem;
            font-weight:500;
            text-decoration:none;
            cursor:pointer;
            transition:color .18s;
            display:inline-block;
            padding:10px 12px;
            border-radius:8px;
            margin-top:18px;
            /* removed fixed desktop offset to allow responsive positioning */
            /* margin-left:215px; */
        }
        .page-back i { margin-right:8px; }

        /* Small devices: align left inside viewport and reduce left margin */
        @media (max-width:900px) {
            .page-back {
                margin-left:12px;
                padding:10px;
                font-size:1.02rem;
            }
            /* show mobile CTA under map and hide aside CTA on small screens */
            .info-right .cta-btn { display: none !important; }
            .cta-mobile { display: block !important; }
        }

        /* Large screens: align Back with the left edge of the centered .viewmap-container */
        @media (min-width:901px) {
            /* Align to container left: (viewport - containerWidth)/2 + containerPadding */
            .page-back {
                margin-left: calc((100% - 1300px) / 2 + 12px);
            }
        }

        @media (max-width:480px) {
            .page-back {
                margin-left:10px;
                padding:10px;
                font-size:1rem;
            }
        }

        /* Small-device tweaks */
        @media (max-width:900px) {
            .portal-title { font-size:1.35rem; }
            .portal-desc { font-size:0.98rem; }
            .container { padding:18px 12px; }
            .map-iframe { height:54vh; min-height:320px; }
            .info-left { padding:16px; }
        }
        @media (max-width:420px) {
            .portal-title { font-size:1.18rem; }
            .portal-desc { font-size:0.95rem; }
            .map-iframe { height:60vh; min-height:260px; }
            .info-left { padding:14px; }
            .info-right { padding:12px; }
            .stat-card { padding:12px; min-height:58px; }
        }
    </style>
</head>
<body>
    <div style="width:100%;display:flex;justify-content:flex-start;">
        <a href="javascript:history.back()" class="page-back" aria-label="Go back">
            <i class="fas fa-arrow-left" aria-hidden="true"></i> Back
        </a>
    </div>
    <div style="height:48px;"></div>
    <div class="container viewmap-container">
        <div class="portal-title mb-1" style="margin-top: -70;">Cemetery Mapping</div>
        <div class="portal-desc mb-4">
            Explore an interactive digital map that helps you easily locate burial plots, view grave details, and navigate the cemetery with ease and accuracy.
        </div>
        <section class="map-section">
            <!-- Embed the real interactive map, view-only, zoom in/out only, no navbar/footer -->
            <iframe
                class="map-iframe"
                src="ClientMap.php?embed=1"
                allowfullscreen=""
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade">
            </iframe>
        </section>

        <!-- Mobile-only CTA: shown below the map on small screens -->
        <div class="cta-mobile" aria-hidden="false">
            <a class="cta-btn" href="ClientMap.php?embed=1" style="text-align:center;">Explore Map in Fullscreen</a>
        </div>
    </div>

    <div class="container viewmap-container">
        <div class="portal-title mb-1">Other Cemetery Information</div>
        <div class="portal-desc mb-2">A compact summary of features, latest updates, and helpful resources for visitors and families.</div>

        <?php
        // Lightweight stats from DB (non-blocking, simple counts)
        include_once '../Includes/db.php';
        $totalDeceased = 0;
        $uniqueNiches = 0;
        $recentInternments = [];

        if (isset($conn)) {
            $r = $conn->query("SELECT COUNT(*) AS c FROM deceased");
            if ($r) { $row = $r->fetch_assoc(); $totalDeceased = intval($row['c']); }

            $r = $conn->query("SELECT COUNT(DISTINCT nicheID) AS c FROM deceased WHERE nicheID IS NOT NULL AND nicheID != ''");
            if ($r) { $row = $r->fetch_assoc(); $uniqueNiches = intval($row['c']); }

            $r = $conn->query("SELECT firstName, middleName, lastName, dateInternment, dateDied, nicheID FROM deceased WHERE dateInternment IS NOT NULL AND dateInternment != '' ORDER BY dateInternment DESC LIMIT 3");
            if ($r) { while ($d = $r->fetch_assoc()) $recentInternments[] = $d; }
        }
        ?>

        <div class="info-panel">
            <div class="info-left">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-users"></i></div>
                        <div>
                            <div class="stat-value"><?php echo number_format($totalDeceased); ?></div>
                            <div class="stat-label">Total Records</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-map-marker-alt"></i></div>
                        <div>
                            <div class="stat-value"><?php echo number_format($uniqueNiches); ?></div>
                            <div class="stat-label">Occupied Niches</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-history"></i></div>
                        <div>
                            <div class="stat-value"><?php echo count($recentInternments); ?></div>
                            <div class="stat-label">Recent Internments</div>
                        </div>
                    </div>
                </div>

                <h4 style="margin:8px 0 6px 0;">Key Features</h4>
                <div class="features-list">
                    <div class="feature-item"><i class="fas fa-check-circle"></i><div><strong>Interactive Map</strong><div class="muted">Search niches, view memorial plaques, and get precise locations.</div></div></div>
                    <div class="feature-item"><i class="fas fa-file-invoice"></i><div><strong>Certificate Management</strong><div class="muted">Generate and print certificates directly from the admin portal.</div></div></div>
                    <div class="feature-item"><i class="fas fa-bell"></i><div><strong>Renewal Reminders</strong><div class="muted">Renewal notices to help families maintain records.</div></div></div>
                    <div class="feature-item"><i class="fas fa-users-cog"></i><div><strong>Secure Admin Tools</strong><div class="muted">Role-based access for staff to manage records safely.</div></div></div>
                </div>

                <div class="faq" style="margin-top:16px;">
                    <div class="card">
                        <div class="card-header" data-bs-toggle="collapse" data-bs-target="#faq1">How do I search for a niche?</div>
                        <div id="faq1" class="collapse card-body">Use the search field on the map page — you can search by Niche ID or deceased name. Tap a result to open details.</div>
                    </div>
                    <div class="card" style="margin-top:8px;">
                        <div class="card-header" data-bs-toggle="collapse" data-bs-target="#faq2">Can I request a certificate online?</div>
                        <div id="faq2" class="collapse card-body">Yes. Registered users can have certificates. Admins will process and generate certificates.</div>
                    </div>
                    <div class="card" style="margin-top:8px;">
                        <div class="card-header" data-bs-toggle="collapse" data-bs-target="#faq3">Who to contact for corrections?</div>
                        <div id="faq3" class="collapse card-body">Please contact the municipal office using the details on the right panel. Provide the Niche ID and supporting documentation.</div>
                    </div>
                </div>
            </div>

            <aside class="info-right">
                <div class="contact-card">
                    <div style="display:flex;gap:12px;width:100%;align-items:center;">
                        <div class="contact-avatar"> MPDO </div>
                        <div>
                            <div style="font-weight:700;font-size:1.04rem;">Municipal Planning and Development Office</div>
                            <div class="muted">Padre Garcia, Batangas</div>
                        </div>
                    </div>
                    <div style="width:100%;margin-top:8px;">
                        <div class="muted"><strong>Phone:</strong> (043) 1234-567</div>
                        <div class="muted"><strong>Email:</strong> info@padregarcia.gov.ph</div>
                        <div class="muted"><strong>Office Hours:</strong> Mon–Fri, 8:00 AM – 5:00 PM</div>
                    </div>

                    <a class="cta-btn" href="ClientMap.php?embed=1" style="margin-top:10px;width:100%;text-align:center;">Explore Map in Fullscreen</a>

                    <div style="margin-top:12px;font-size:0.92rem;color:#6b7280;">
                        If you need corrections or assistance, please prepare the Niche ID and any supporting documents before contacting the office.
                    </div>
                </div>

                <div style="margin-top:14px;">
                    <div style="background:#fff;border-radius:10px;padding:12px;border:1px solid rgba(15,23,42,0.04);">
                        <div style="font-weight:700;margin-bottom:6px;">Visitor Tips</div>
                        <ul style="padding-left:16px;margin:0;" class="muted">
                            <li>Use the map search for quick lookups.</li>
                            <li>Export or print certificates after approval.</li>
                            <li>Respect visiting hours and facility rules.</li>
                        </ul>
                    </div>
                </div>
            </aside>
        </div>
    </div>

     <?php include '../Includes/footer-client.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>