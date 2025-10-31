<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: ../login.php"); // Adjust the path if needed
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../assets/re logo blue.png">
    <title>RestEase</title>
    <!-- Add Google Fonts for Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/footer.css">
    <link rel="stylesheet" href="../css/clientabout-us.css">
</head>
<body>
    <?php include '../Includes/navbar2.php'; ?>
    <!-- About Us Hero Section -->
    <section class="about-hero">
        <div class="container">
            <h1 class="fade-in-up delay-1">About Us</h1>
            <p class="fade-in-up delay-2">RestEase is a digital cemetery management system that helps Padre Garcia keep records organized, certificates updated, and families informed with secure and efficient services.</p>
        </div>
    </section>

    <!-- Team Photos Section -->
    <section class="team-photos">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-3 col-sm-6">
                    <div class="team-photo fade-in-up delay-1">
                        <img src="../assets/abt1.webp" alt="Team Member 1" loading="lazy">
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="team-photo fade-in-up delay-2">
                        <img src="../assets/abt2.webp" alt="Team Member 2" loading="lazy">
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="team-photo fade-in-up delay-3">
                        <img src="../assets/abt3.webp" alt="Team Member 3" loading="lazy">
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="team-photo fade-in-up delay-2">
                        <img src="../assets/abt4.webp" alt="Team Member 4" loading="lazy">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Who We Are Section -->
    <section class="who-we-are-section">
        <div class="container">
            <h2>Who we are</h2>
            <div class="main-description">
                <p>RestEase is an innovative online ossuary vault management system designed to streamline record-keeping, certificate issuance, and renewal processes for the Municipal Planning and Development Offices of Padre Garcia, Batangas. It simplifies tracking vaults, renewals, and documents while providing a front-view vault mapping feature for easy reference without the need for real-world tracking.</p>
            </div>

            <div class="mission-vision">
                <div class="our-mission">
                    <h3>Our Mission</h3>
                    <p>Our mission is to modernize ossuary vault management by offering a digital platform that enhances organization, transparency, and accessibility. We aim to simplify vault tracking, document management, and renewal processes while ensuring families can easily locate and honor their loved ones.</p>
                </div>
                <div class="our-vision">
                    <h3>Our Vision</h3>
                    <p>We envision a future where cemetery and vault management is seamlessly digital, reducing administrative burdens while preserving historical and cultural records. RestEase aspires to be the leading ossuary vault management system, bringing innovation, efficiency, and peace of mind to local governments and communities.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- What We Do Section -->
    <section class="what-we-do-section">
        <div class="container">
            <h3>What we do</h3>
            <div class="divider"></div>
            
            <div class="content-section">
                <p>RestEase provides a comprehensive digital solution for managing ossuary vault records, ensuring efficient tracking, documentation, and certificate issuance. Our system is designed to support local government offices in handling cemetery operations with ease and precision.
                </p>
            </div>

            <div class="content-section">
               <ul>
                <li>Digitally map and organize ossuary vaults for efficient management.</li>
                <li>Automate the issuance and renewal of niches certificates.</li>
                <li>Offer a user-friendly interface for the public to search for vaults.</li>
                <li>Improve record security and accuracy through a centralized database.</li>
               </ul>
            </div>

            <h3>Building a Future of Efficient Memorial Management</h3><br>
            <div class="content-section">
                <p>
                    RestEase is committed to transforming how cemetery and ossuary vault records are managed. By integrating modern technology into traditional processes, we eliminate inefficiencies and ensure that every record is well-documented, easily accessible, and securely stored. Our system is designed not only to assist administrators but also to provide families with a seamless way to locate and honor their loved ones without the hassle of manual record searching. As we continue to innovate, RestEase aims to set the standard for digital cemetery management—making memorial record-keeping simpler, more transparent, and future-ready for generations to come.</p>
            </div>
        </div>
    </section>

    <?php include '../Includes/footer-client.php'; ?>
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

