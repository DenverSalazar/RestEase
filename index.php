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
    <link rel="stylesheet" href="css/index.css">
</head>
<body>
    <section class="hero">
        <nav class="navbar navbar-expand-lg navbar-light">
            <div class="container-fluid">
                <a class="navbar-brand" href="#">
                    <img src="assets/RE Logo New.png" alt="Logo">
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                    <ul class="navbar-nav">
                        <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                        <li class="nav-item"><a class="nav-link" href="about-us.php">About Us</a></li>
                        <li class="nav-item"><a class="nav-link" href="contact-us.php">Contact Us</a></li>
                        <li class="nav-item"><a class="btn" href="login.php">Sign In</a></li>
                    </ul>
                </div>
            </div>
        </nav>
        <div class="hero-content">
            <h1>RestEase: Web-Based Cemetery Records & <br> Certificate Management of Padre Garcia Batangas</h1>
            <p>Designed for managing ossuary vault records and certificates in Padre Garcia, Batangas. It simplifies tracking vaults, renewals, and documents. The system also includes a front-view vault mapping for easy reference without real-world tracking.</p>
            <div class="btn-container mb-5">
                <a href="#" class="btn btn-primary btn-custom">Reserve Now</a>
                <a href="#" class="btn btn-dark btn-custom">Explore</a>
            </div>
        </div>
        <div class="associated-by mt-4">
            <p class="text-center"><b>Associated By:</b></p>
            <div class="footer-icons d-flex justify-content-center align-items-center flex-wrap gap-4">
                <img src="assets/Logo garcia.png" alt="Logo 1" style="height: 60px; width: auto;">
                <img src="assets/Logo garcia.png" alt="Logo 2" style="height: 60px; width: auto;">
                <img src="assets/Logo garcia.png" alt="Logo 3" style="height: 60px; width: auto;">
                <img src="assets/Logo garcia.png" alt="Logo 4" style="height: 60px; width: auto;">
                <img src="assets/Logo garcia.png" alt="Logo 5" style="height: 60px; width: auto;">
                <img src="assets/Logo garcia.png" alt="Logo 5" style="height: 60px; width: auto;">

            </div>
        </div>
    </section>
    <section class="who-we-are">
        <div class="container">
            <div class="row align-items-center">
                <!-- Text Content -->
                <div class="col-md-6">
                    <h2 class="section-title">Who we are</h2>
                    <p class="section-description">
                        Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
                    </p>
                    <a href="#" class="btn btn-primary btn-read-more">Read More</a>
                </div>
                <!-- Image -->
                <div class="col-md-6 text-center">
                    <img src="assets/testimony-image.png" alt="Who we are" class="img-fluid rounded">
                </div>
            </div>

            <!-- Our Services Section (Connected to Who We Are) -->
            <div class="text-center mt-5 pt-5">
                <h2 class="section-title">Our Services</h2>
                <p class="section-description">
                    Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do <br> eiusmod dolore magna aliqua.
                </p>
                <div class="row mt-4 d-flex align-items-stretch">
                    <!-- Card 1 -->
                    <div class="col-md-4 d-flex">
                        <div class="service-card flex-grow-1">
                            <div class="d-flex align-items-center">
                                <h3 class="service-title flex-grow-1">Certificate Issuance</h3>
                                <div class="icon">
                                    <img src="assets/certification.png" alt="Certificate Issuance" class="img-fluid">
                                </div>
                            </div>
                            <p class="service-description">
                                Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
                            </p>
                        </div>
                    </div>
                    <!-- Card 2 -->
                    <div class="col-md-4 d-flex">
                        <div class="service-card flex-grow-1">
                            <div class="d-flex align-items-center">
                                <h3 class="service-title flex-grow-1">Renewal Management</h3>
                                <div class="icon">
                                    <img src="assets/renewal.png" alt="Renewal Management" class="img-fluid">
                                </div>
                            </div>
                            <p class="service-description">
                                Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
                            </p>
                        </div>
                    </div>
                    <!-- Card 3 -->
                    <div class="col-md-4 d-flex">
                        <div class="service-card flex-grow-1">
                            <div class="d-flex align-items-center">
                                <h3 class="service-title flex-grow-1">Record Keeping</h3>
                                <div class="icon">
                                    <img src="assets/record.png" alt="Record Keeping" class="img-fluid">
                                </div>
                            </div>
                            <p class="service-description">
                                Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="contact-us py-5">
        <div class="container">
            <div class="row ">
                <!-- Contact Information and Description -->
                <div class="col-md-6">
                    <div class="mb-4">
                        <h2 class="section-title text-start">Contact Us</h2>
                        <p class="section-description text-start">
                            Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod dolore magna aliqua.
                        </p>
                    </div>
                    <form>
                        <div class="row mb-3">
                            <div class="col">
                                <input type="text" class="form-control" placeholder="Name">
                            </div>
                            <div class="col">
                                <input type="text" class="form-control" placeholder="Contact">
                            </div>
                        </div>
                        <div class="mb-3">
                            <input type="email" class="form-control" placeholder="Email Address">
                        </div>
                        <div class="mb-3">
                            <textarea class="form-control" rows="5" placeholder="Message"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Get In Touch</button>
                    </form>
                </div>
                <!-- Contact Information -->
                <div class="col-md-6 mt-10">
                    <div class="p-4 rounded background-color: #D9D9D9;">
                        <h4>Address</h4>
                        <p>V6MF+8JH, Banaba, Padre Garcia, Batangas</p>
                        <h4>Contact</h4>
                        <p>Phone: +0123-456-789</p>
                        <p>Email: restease@gmail.com</p>
                        <h4>Open Time</h4>
                        <p>Monday - Sunday : 8:00am - 10:00pm</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="testimony py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <img src="assets/Poster.jpeg" alt="Testimony Image" class="img-fluid rounded">
                </div>
                <div class="col-md-8">
                    <blockquote class="blockquote">
                        <p class="mb-4" style="font-style: italic;">
                            "In a world where time moves fast, we ensure that remembering and honoring the past is effortless. Through innovation and organization, we provide a seamless way to preserve legacies and manage what truly matters."
                        </p>
                        <footer class="blockquote-footer">RestEase</footer>
                    </blockquote>
                </div>
            </div>
        </div>
    </section>
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