<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - RestEase</title>
    <!-- Add Google Fonts for Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/about-us.css">
</head>
<body>
    <!-- Navbar (same as index.html) -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <img src="assets/RE Logo New.png" alt="Logo">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link active" href="about-us.php">About Us</a></li>
                    <li class="nav-item"><a class="nav-link" href="contact-us.php">Contact Us</a></li>
                    <li class="nav-item"><a class="btn" href="login.php">Sign In</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- About Us Hero Section -->
    <section class="about-hero">
        <div class="container">
            <h1>About Us</h1>
            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod dolore magna aliqua</p>
        </div>
    </section>

    <!-- Team Photos Section -->
    <section class="team-photos">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-3 col-sm-6">
                    <div class="team-photo">
                        <img src="assets/abt1.jpg" alt="Team Member 1">
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="team-photo">
                        <img src="assets/abt2.jpg" alt="Team Member 2">
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="team-photo">
                        <img src="assets/abt3.jpg" alt="Team Member 3">
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="team-photo">
                        <img src="assets/abt4.jpg" alt="Team Member 4">
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

    <div class="container">
        <div class="section-divider"></div>
    </div>

    <!-- Footer (same as index.html) -->
    <footer class="footer py-4 d-flex align-items-center" style="background-color: #506C84; color: white; height: 345px;">
        <div class="container">
            <!-- Logo and Navigation Links -->
            <div class="row mb-4">
                <!-- Logo -->
                <div class="col-12 col-md-3 text-center text-md-start mb-3 mb-md-0">
                    <img src="assets/RE Logo New White.png" alt="RestEase Logo" style="height: 31px;">
                </div>
                <!-- Navigation Links -->
                <div class="col-12 col-md-6 text-center mb-3 mb-md-0">
                    <div class="d-flex flex-column flex-md-row justify-content-center gap-2 gap-md-3">
                        <a href="index.php" class="text-white text-decoration-none">Home</a>
                        <a href="about-us.php" class="text-white text-decoration-none">About Us</a>
                        <a href="#" class="text-white text-decoration-none">Terms & Condition</a>
                        <a href="#" class="text-white text-decoration-none">Data Privacy & Policy</a>
                    </div>
                </div>
                <!-- Social Media Icons -->
                <div class="col-12 col-md-3 text-center mb-3 mb-md-0">
                    <div class="d-flex justify-content-center gap-3">
                        <a href="#" class="text-white"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-linkedin"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
            </div>

            <hr style="border: 1px solid white;">

            <!-- Copyright and Credits -->
            <div class="row pt-3">
                <div class="col-12 col-md-6 text-center text-md-start mb-2 mb-md-0">
                    <p class="mb-0">&copy; 2025 RestEase. All rights reserved.</p>
                </div>
                <div class="col-12 col-md-6 text-center text-md-end">
                    <p class="mb-0">Designed By: Biringan IT Corps.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
