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
            <h1 class="fade-in-up delay-1">RestEase: Web-Based Cemetery Records & <br> Certificate Management of Padre Garcia Batangas</h1>
            <p class="fade-in-up delay-2">Designed for managing cemetery apartment records and certificates in Padre Garcia, Batangas. It simplifies tracking niche, renewals, and documents. The system also includes a front-view niche mapping for easy reference without real-world tracking.</p>
            <div class="btn-container mb-5 fade-in-up delay-3">
                <a href="login.php" class="btn btn-primary btn-custom">Reserve Now</a>
                <a href="#explore-restease" class="btn btn-dark btn-custom">Explore</a>
            </div>
        </div>
        <div class="associated-by mt-4">
            <p class="text-center"><b>Associated By:</b></p>
            <div class="footer-icons d-flex justify-content-center align-items-center flex-wrap gap-4">
                <img src="assets/Logo garcia.png" alt="Logo 1" style="height: 60px; width: auto;">
                <img src="assets/Seal_of_Batangas.png" alt="Logo 2" style="height: 50px; width: auto;">
               
            </div>
        </div>
    </section>
    <section class="who-we-are">
        <div class="container">
            <div class="row align-items-center">
                <!-- Text Content -->
                <div class="col-md-6">
                    <h2 class="section-title">Who we are</h2>
                    <p class="section-description" style="text-align: justify;">
                        RestEase is a web-based Cemetery Records and Certificate Management System designed for the Municipal Planning and Development Office (MPDO) of Padre Garcia, Batangas. The system was created to modernize cemetery operations by shifting from manual, paper-based processes to a digital platform that ensures organized record management, efficient niche tracking, and automated renewal reminders.
                    </p>
                    <a href="about-us.php" class="btn btn-primary btn-read-more">Read More</a>
                </div>
                <!-- Image -->
                <div class="col-md-6 text-center">
                    <img src="assets/testimony-image.webp" alt="Who we are" class="img-fluid rounded">
                </div>
            </div>

            <!-- Our Services Section (Connected to Who We Are) -->
            <div class="text-center mt-5 pt-5">
                <h2 class="section-title">Our Services</h2>
                <p class="section-description">
                    RestEase offers a modern, efficient, and transparent approach to cemetery management through digital innovation.
                </p>
                <div class="row mt-4 d-flex align-items-stretch">
                    <!-- Card 1 -->
                    <div class="col-md-4 d-flex">
                        <div class="service-card flex-grow-1 text-center">
                            <div class="d-flex align-items-center justify-content-center">
                                <h3 class="service-title flex-grow-1">Record Keeping</h3>
                                <div class="icon">
                                    <img src="assets/record.png" alt="Record Keeping" class="img-fluid">
                                </div>
                            </div>
                            <p class="service-description" style="text-align: center;">
                                We provide a secure and organized digital database that allows administrators to easily store, access, and manage burial and certificate records, ensuring data accuracy and long-term preservation.
                            </p>
                        </div>
                    </div>
                    <!-- Card 2 -->
                    <div class="col-md-4 d-flex">
                        <div class="service-card flex-grow-1 text-center">
                            <div class="d-flex align-items-center justify-content-center">
                                <h3 class="service-title flex-grow-1">Cemetery Mapping</h3>
                                <div class="icon">
                                    <img src="" alt="Cemetery Mapping" class="img-fluid">
                                </div>
                            </div>
                            <p class="service-description" style="text-align: center;">
                                Using GIS technology, we offer an interactive digital map that helps users and administrators locate niches, track availability, and visualize the layout of the cemetery in real time.
                            </p>
                        </div>
                    </div>
                    <!-- Card 3 -->
                    <div class="col-md-4 d-flex">
                        <div class="service-card flex-grow-1 text-center">
                            <div class="d-flex align-items-center justify-content-center">
                                <h3 class="service-title flex-grow-1">Notifications and Reminders</h3>
                                <div class="icon">
                                    <img src="" alt="Notifications and Reminders" class="img-fluid">
                                </div>
                            </div>
                            <p class="service-description" style="text-align: center;">
                                Our automated notification system keeps families informed by sending timely alerts for certificate renewals, updates, and important announcements—ensuring no deadlines are missed.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Explore RestEase Section (NEW) -->
    <section id="explore-restease" class="explore-restease-section py-5" style="min-height: 70vh; display: flex; align-items: center;">
        <div class="container">
            <div class="row align-items-center">
                <!-- Text Content -->
                <div class="col-lg-7 col-md-12 mb-4 mb-lg-0 d-flex flex-column justify-content-center" style="height:100%;">
                    <h2 class="fw-bold mb-3">Explore RestEase</h2>
                    <!-- Carousel Start -->
                    <div id="exploreCarousel" class="carousel slide" data-bs-ride="carousel">
                        <div class="carousel-inner">
                            <div class="carousel-item active">
                                <p class="explore-carousel-text" style="text-align: justify; cursor: pointer;">
                                   RestEase is more than just a record management system, it’s a modern digital solution built to simplify and improve cemetery operations in Padre Garcia. Through its web-based platform, users can easily access burial records, request certificates, and track renewal schedules without the hassle of paperwork.

The system provides a secure and transparent way to manage cemetery information, ensuring that data remains accurate and protected. Families can locate niches, receive automated renewal notifications, and access important updates online, while administrators benefit from organized records and faster processing. </p>
                            </div>
                            <div class="carousel-item">
                                <p class="explore-carousel-text" style="text-align: justify; cursor: pointer;">
                                   RestEase is more than a traditional record management tool; it’s an innovative digital platform designed to streamline and enhance cemetery operations in Padre Garcia. Through its online interface, users can quickly view burial records, request certificates, and monitor renewal deadlines without dealing with cumbersome paperwork.

System ensures secure and reliable management of cemetery data, keeping information accurate and protected. Families can search for niches, receive timely renewal alerts, stay informed of important announcements, while administrators enjoy organized records and faster processing of requests. </p>
                            </div>
                            <div class="carousel-item">
                                <p class="explore-carousel-text" style="text-align: justify; cursor: pointer;">
                                    RestEase is not just a record management system—it’s a modern web-based solution created to simplify cemetery operations in Padre Garcia. Users can easily check burial records, submit certificate requests, and track renewal schedules efficiently without manual paperwork.

The platform provides safe and transparent management of cemetery information, guaranteeing accurate and protected data. Families can identify available niches, receive automated reminders for renewals, and access key updates online, while administrators benefit from structured records and faster workflow.</p>
                            </div>
                        </div>
                        <!-- Custom 3-dot indicators below info text -->
                        <div class="d-flex justify-content-center align-items-center mt-3" id="customExploreDots">
                            <span class="explore-dot active" style="height:10px;width:10px;background:#333;border-radius:50%;display:inline-block;margin:0 6px;transition:background 0.2s;"></span>
                            <span class="explore-dot" style="height:10px;width:10px;background:#bbb;border-radius:50%;display:inline-block;margin:0 6px;transition:background 0.2s;"></span>
                            <span class="explore-dot" style="height:10px;width:10px;background:#bbb;border-radius:50%;display:inline-block;margin:0 6px;transition:background 0.2s;"></span>
                        </div>
                    </div>
                    <!-- Carousel End -->
                </div>
                <!-- Image Content -->
                <div class="col-lg-5 col-md-12 text-center d-flex justify-content-center align-items-center" style="height:100%;">
                    <img src="assets/explore.png" alt="Explore RestEase" class="img-fluid rounded" style="max-width: 400px;">
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Form Section -->
    <section class="contact-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-7">
                    <h2>Contact Us</h2>
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
                    <div class="contact-info d-flex flex-column justify-content-center h-100" style="min-height: 400px;">
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
    <section class="testimony py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <img src="./assets/Poster.webp" alt="Testimony Image" class="img-fluid rounded">
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
    <?php include 'Includes/footer.php'; ?>
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Slow hover to next carousel item for Explore RestEase
    let exploreHoverTimeout;
    document.querySelectorAll('.explore-carousel-text').forEach(function(el) {
        el.addEventListener('mouseenter', function() {
            clearTimeout(exploreHoverTimeout);
            exploreHoverTimeout = setTimeout(function() {
                var carousel = document.getElementById('exploreCarousel');
                var bsCarousel = bootstrap.Carousel.getOrCreateInstance(carousel);
                bsCarousel.next();
            }, 1000); // 1000ms = 1 second delay
        });
        el.addEventListener('mouseleave', function() {
            clearTimeout(exploreHoverTimeout);
        });
    });

    // Sync custom dots with carousel
    var exploreCarousel = document.getElementById('exploreCarousel');
    var dots = document.querySelectorAll('#customExploreDots .explore-dot');
    if (exploreCarousel) {
        exploreCarousel.addEventListener('slid.bs.carousel', function (e) {
            dots.forEach(function(dot, idx) {
                dot.style.background = (idx === e.to) ? '#333' : '#bbb';
                dot.classList.toggle('active', idx === e.to);
            });
        });
    }
    </script>
</body>
</html>