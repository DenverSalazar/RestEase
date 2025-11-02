<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: ../login.php"); // Adjust the path if needed
    exit;
}
?>

<?php include '../Includes/navbar2.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Privacy & Policy - RestEase</title>
    <link rel="icon" type="image/png" href="../assets/re logo blue.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/index.css">
     <link rel="stylesheet" href="../css/navbar.css">
    <style>
        body {
            font-family: 'Poppins', Arial, sans-serif;
            background: #fff;
        }
        .privacy-container {
            max-width: 1100px;
            margin: 60px auto 0 auto;
            background: #fff;
            padding: 0 32px 32px 32px;
            margin-top: 30px; /* Added for top margin like termscondtion.php */
        }
        .privacy-row {
            display: flex;
            flex-wrap: wrap;
            gap: 32px;
        }
        .privacy-content {
            flex: 2;
            min-width: 320px;
        }
        .privacy-image {
            flex: 1;
            min-width: 300px;
            display: flex;
            align-items: flex-start; /* Ensure image aligns with top of content */
            justify-content: center;
        }
        .privacy-image img {
            width: 100%;
            max-width: 600px; /* Increased from 428px */
            height: auto;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(44,62,80,0.08);
            margin-top: 80px; /* Aligns with first privacy-section-content */
        }
        .privacy-title {
            font-size: 1.5rem; /* Changed to 1.5rem */
            font-weight: 700;
            margin-bottom: 18px;
        }
        .privacy-section-title {
            font-size: 1.5rem; /* Changed to 1.5rem */
            font-weight: 600;
            margin-top: 32px;
            margin-bottom: 12px;
        }
        .privacy-hr {
            border: 0;
            border-top: 3px solid #b0b0b0; /* Darker line */
            margin: 24px 0;
        }
        .privacy-note-bg {
            width: 100vw;
            margin-left: calc(-50vw + 50%);
            background: #eaf6fb;
            margin-bottom: 24px;
            box-sizing: border-box;
        }
        .privacy-note-inner {
            max-width: 900px;
            margin-left: 225px; /* Align to left */
            padding: 18px 24px;
            border-radius: 8px;
            font-size: 1.05rem;
            color: #222;
        }
        .back-btn {
            margin-bottom: 18px;
            padding-left: 0;
        }
        @media (max-width: 900px) {
            .privacy-row {
                flex-direction: column;
                gap: 0;
            }
            .privacy-image {
                margin-top: 24px;
                justify-content: flex-start;
            }
        }
        @media (max-width: 600px) {
            .privacy-container {
                padding: 0 12px 24px 12px;
            }
            .privacy-title {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="privacy-container">
        <div class="privacy-row">
            <div class="privacy-content">
                <div class="privacy-title">Data Privacy & Policy</div>
                <div class="privacy-section-content">
                    RestEase ensures that all cemetery records and personal information are securely stored, accessed only by authorized users, and protected in compliance with data privacy regulations. The system uses encrypted databases and role-based access to prevent unauthorized handling of sensitive data. Only authorized MPDO staff can process records, while families can access their own information through secure login.
                </div>
                <hr class="privacy-hr"> <!-- Moved here: after first content -->
                <div class="privacy-section-title">Information Collection and Use</div>
                <div class="privacy-section-content">
                    RestEase collects personal information such as names, contact details, and certificate records when families or administrators use the system. This information is used only for managing cemetery services like certificate issuance, renewals, and record updates. We do not share this information with outside parties unless required by law.
                </div>
                <hr class="privacy-hr">
                <div class="privacy-section-title">Log Data</div>
                <div class="privacy-section-content">
                    RestEase ensures that all cemetery records and personal information are securely stored, accessed only by authorized users, and protected in compliance with data privacy regulations. The system uses encrypted databases and role-based access to prevent unauthorized handling of sensitive data. Only authorized MPDO staff can process records, while families can access their own information through secure login.
                </div>
                <hr class="privacy-hr">
                <div class="privacy-section-title">Data Security</div>
                <div class="privacy-section-content">
                    RestEase applies security measures such as encrypted databases and restricted access to ensure that all records are kept safe. Only authorized personnel can handle sensitive data, and regular backups are maintained to prevent data loss.
                </div>
            </div>
            <div class="privacy-image">
                <img src="../assets/privacy.png" alt="Data Privacy Illustration">
            </div>
        </div>
    </div>
    <div class="privacy-note-bg">
        <div class="privacy-note-inner">
            <strong>Trusted Service</strong><br>
            Your data is managed responsibly and securely, giving families peace of mind while using RestEase.
        </div>
    </div>
    <?php include '../Includes/footer-client.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

