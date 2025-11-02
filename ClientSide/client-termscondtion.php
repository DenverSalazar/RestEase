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
    <title>Terms and Conditions - RestEase</title>
    <link rel="icon" type="image/png" href="../assets/re logo blue.png">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/index.css">
     <link rel="stylesheet" href="../css/navbar.css">
    <style>
        body {
            font-family: 'Poppins', Arial, sans-serif;
            background: #fff;
        }
        .terms-container {
            max-width: 900px;
            margin: 60px auto 0 auto;
            background: #fff;
            padding: 0 32px 32px 32px;
            margin-top: 40px; /* Extra margin to avoid navbar overlap */
        }
        .terms-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 18px;
        }
        .terms-subtitle {
            color: #888;
            font-size: 1rem;
            font-weight: 500;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }
        .terms-content {
            font-size: 1.08rem;
            margin-bottom: 32px;
        }
        .terms-list {
            margin-bottom: 24px;
        }
        .terms-list li {
            margin-bottom: 8px;
        }
        .terms-buttons {
            display: flex;
            gap: 16px;
            margin-top: 24px;
        }
        .terms-buttons .btn {
            min-width: 150px;
            font-size: 1rem;
            font-weight: 500;
            border-radius: 8px;
        }
        .terms-buttons .btn-outline-secondary {
            background: #f8f9fa;
        }
        @media (max-width: 600px) {
            .terms-container {
                padding: 0 12px 24px 12px;
            }
            .terms-title {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>

    <div class="terms-container">
        <div class="terms-subtitle">AGREEMENT</div>
        <div class="terms-title">Terms and Conditions</div>
        <div class="terms-content">
            To proceed with managing cemetery records or requesting certificates through RestEase, you must first agree to these User Terms. By clicking "I AGREE", you confirm that you have read and accepted the responsibilities outlined below:
        </div>
        <div class="terms-content">
            <strong>As a User, You Agree That:</strong>
            <ul class="terms-list">
                <li>All information you provide (e.g., deceased details, applicant name, contact info) is accurate and complete.</li>
                <li>You are authorized to request records or certificates for the deceased individuals listed.</li>
                <li>You are using this system for legitimate and respectful purposes only.</li>
                <li>Issuance of certificates (e.g., interment, renewal) is subject to review and approval by the Municipal Planning and Development Office (MPDO).</li>
                <li>You will comply with all local rules and requirements related to cemetery management.</li>
                <li>Providing false or misleading information may result in request rejection and possible account suspension.</li>
            </ul>
            <strong>Before Submitting Any Request:</strong>
            <ul class="terms-list">
                <li>Ensure that all required documents are uploaded, clear, and complete.</li>
                <li>Double-check your entries for accuracy before final submission.</li>
                <li>Incomplete or incorrect submissions may cause delays or disapproval.</li>
            </ul>
            By using this system, you also agree to respect the privacy, integrity, and purpose of the platform. For questions, please contact your local MPDO office.
        </div>
    </div>
    <?php include '../Includes/footer-client.php'; ?>
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>