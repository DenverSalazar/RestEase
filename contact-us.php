<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - RestEase</title>
    <!-- Add Google Fonts for Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/contact-us.css">
</head>
<body>
    <!-- Navbar -->
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
                    <li class="nav-item"><a class="nav-link" href="about-us.php">About Us</a></li>
                    <li class="nav-item"><a class="nav-link active" href="contact-us.php">Contact Us</a></li>
                    <li class="nav-item"><a class="btn" href="login.php">Sign In</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Contact Hero Section -->
    <section class="contact-hero" style="padding-top: 110px;">
        <div class="container">
            <h1>Contact Us</h1>
            <p>Have questions or need assistance? We're here to help! Reach out to us for any inquiries about our system, services, or support.</p>
        </div>
    </section>

    <!-- Contact Form Section -->
    <section class="contact-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-7">
                    <h2>Get In Touch</h2>
                    <p class="mb-4">Connect with us for more information or assistance. Whether you have concerns, suggestions, or need help, we're just a message away!</p>
                    
                    <form>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <input type="text" class="form-control" placeholder="Name">
                            </div>
                            <div class="col-md-6">
                                <input type="text" class="form-control" placeholder="Contact">
                            </div>
                        </div>
                        <div class="mb-3">
                            <input type="email" class="form-control" placeholder="Email Address">
                        </div>
                        <div class="mb-3">
                            <textarea class="form-control" rows="6" placeholder="Message"></textarea>
                        </div>
                        <button type="submit" class="submit-btn">Submit</button>
                    </form>
                </div>
                
                <div class="col-lg-5">
                    <div class="contact-info">
                        <h3>Address</h3>
                        <p>
                            <a href="https://maps.app.goo.gl/gKD6GszPE12M2GRn9" 
                               target="_blank" 
                               rel="noopener noreferrer" 
                               style="text-decoration: none; color: inherit;">
                                V6MF+8JH, Banaba, Padre Garcia, Batangas
                            </a>
                        </p>
                        
                        <h3>Contact</h3>
                        <p>Phone: +0923-456-789</p>
                        <p>Email: restease@gmail.com</p>
                        
                        <h3>Open Time</h3>
                        <p>Monday - Sunday : 8:00am - 5:00pm</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Map Section -->
    <section class="map-section">
        <div class="container">
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d4090.1235927031694!2d121.2215291108287!3d13.883317694178464!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x33bd1503355a800d%3A0xaf01fd3a0484847b!2sV6MF%2B8JH%2C%20Padre%20Garcia%2C%20Batangas!5e1!3m2!1sen!2sph!4v1743494319859!5m2!1sen!2sph"
             width="600" 
             height="450" 
             style="border:0;" 
             allowfullscreen="" 
             loading="lazy" 
             referrerpolicy="no-referrer-when-downgrade">
            </iframe>

           <!-- another code if magka issue yang nasa taas
            <iframe 
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d4090.1235927031694!2d121.2215291108287!3d13.883317694178464!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x33bd1503355a800d%3A0xaf01fd3a0484847b!2sV6MF%2B8JH%2C%20Padre%20Garcia%2C%20Batangas!5e0!3m2!1sen!2sph!4v1743494319859!5m2!1sen!2sph" 
            width="600" 
            height="450" 
            style="border:0;" 
            allowfullscreen="" 
            loading="lazy">
        </iframe> -->
        
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer py-4 d-flex align-items-center" style="background-color: #506C84; color: white; height: 345px;">
        <div class="container">
            <!-- Logo and Navigation Links -->
            <div class="row mb-4">
                <div class="col-12 col-md-3 text-center text-md-start mb-3 mb-md-0">
                    <img src="assets/RE Logo New White.png" alt="RestEase Logo" style="height: 31px;">
                </div>
                <div class="col-12 col-md-6 text-center mb-3 mb-md-0">
                    <div class="d-flex flex-column flex-md-row justify-content-center gap-2 gap-md-3">
                        <a href="index.php" class="text-white text-decoration-none">Home</a>
                        <a href="about-us.php" class="text-white text-decoration-none">About Us</a>
                        <a href="#" class="text-white text-decoration-none">Terms & Condition</a>
                        <a href="#" class="text-white text-decoration-none">Data Privacy & Policy</a>
                    </div>
                </div>
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