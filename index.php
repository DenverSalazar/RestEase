<?php
session_start();
require 'vendor/autoload.php';      // PHPMailer via Composer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Cloudflare Turnstile keys (replace with your keys)
$turnstile_sitekey = '0x4AAAAAAB9DMwi4JEs-E7Dk';
$turnstile_secret  = '0x4AAAAAAB9DM0HH3_jtHziIMzaFQztRwcA';

$successMsg = $_SESSION['successMsg'] ?? '';
$errorMsg = $_SESSION['errorMsg'] ?? '';
unset($_SESSION['successMsg'], $_SESSION['errorMsg']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Simple server-side validation
    $name = trim($_POST['name'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $turnstile_response = $_POST['cf-turnstile-response'] ?? '';

    // Cloudflare Turnstile verification function
    function verify_turnstile($token, $secret, $remoteip = null) {
        if (empty($token) || empty($secret)) return false;
        $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        $post = ['secret' => $secret, 'response' => $token];
        if ($remoteip) $post['remoteip'] = $remoteip;
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $resp = curl_exec($ch);
        curl_close($ch);
        $obj = json_decode($resp);
        return ($obj && !empty($obj->success));
    }

    if (!$name) {
        $_SESSION['errorMsg'] = 'Name is required.';
    } elseif (!$contact) {
        $_SESSION['errorMsg'] = 'Contact is required.';
    } elseif (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['errorMsg'] = 'Valid email is required.';
    } elseif (!$message) {
        $_SESSION['errorMsg'] = 'Message is required.';
    } elseif (empty($turnstile_response) || !verify_turnstile($turnstile_response, $turnstile_secret, $_SERVER['REMOTE_ADDR'] ?? '')) {
        $_SESSION['errorMsg'] = 'Please complete the Cloudflare verification before submitting.';
    } else {
        // Send email via PHPMailer
        /*
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'resteasempdo@gmail.com';
            $mail->Password = 'vvkblrlppiflbksu';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('resteasempdo@gmail.com', 'RestEase Contact Form');
            $mail->addAddress('resteasempdo@gmail.com');

            $mail->Subject = 'New Contact Form Submission';
            $mail->Body = "Name: $name\nContact: $contact\nEmail: $email\nMessage:\n$message";

            $mail->send();
            $_SESSION['successMsg'] = 'Your message has been sent successfully!';
        } catch (Exception $e) {
            $_SESSION['errorMsg'] = 'Message could not be sent. Please try again later.';
        }
        */
        // Optionally, set a message indicating email sending is disabled
        $_SESSION['errorMsg'] = 'Message sending is currently disabled.';
    }
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RestEase</title>
    <link rel="icon" type="image/png" href="./assets/re logo blue.png">
    <!-- Add Google Fonts for Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/index.css">
    <!-- Desktop-only extra spacing for Associated By logos -->
    <style>
        @media (min-width: 992px) {
            /* increased gap between logos on desktop only */
            .associated-by .footer-icons {
                gap: 4.5rem !important; /* increased from 3rem */
            }
            /* ensure images remain visually consistent */
            .associated-by .footer-icons img {
                max-height: 80px;
                width: auto;
                display: inline-block;
            }
        }
    </style>
    <!-- Cloudflare Turnstile -->
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
</head>
<body>
    <section class="hero scroll-animate fade-up">
        <div class="section-bg-objects">
            <div class="section-bg-object circle automove1" style="width:140px;height:140px;top:10%;left:8%;"></div>
            <div class="section-bg-object square automove2" style="width:110px;height:110px;top:60%;left:70%;"></div>
            <div class="section-bg-object triangle automove3" style="top:40%;left:30%;width:0;height:0;"></div>
        </div>
        <nav class="navbar navbar-expand-lg navbar-light">
            <div class="container-fluid">
                <a class="navbar-brand" href="index.php">
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
            <p class="fade-in-up delay-2">Designed for managing cemetery apartment records and certificates in Padre Garcia, Batangas. It simplifies tracking niche, renewals, and documents.</p>
            <div class="btn-container mb-5 fade-in-up delay-3">
                <a href="login.php" class="btn btn-primary btn-custom">Request Now</a>
                <a href="#explore-restease" class="btn btn-dark btn-custom">Explore</a>
            </div>
        </div>
        <div class="associated-by mt-4">
            <p class="text-center"><b>Associated By:</b></p>
            <div class="footer-icons d-flex justify-content-center align-items-center flex-wrap gap-4">
                <img src="assets/Logo garcia.png" alt="Logo 1" style="height: 52px; width: auto;">
                <img src="assets/re logo blue.png" alt="Logo 3" style="height: 60px; width: auto;">
                   <img src="assets/BSU Logo.png" alt="Logo 3" style="height: 53px; width: auto;">
                      <img src="assets/Seal_of_Batangas.png" alt="Logo 3" style="height: 53px; width: auto;">
            </div>
        </div>
    </section>
    <section class="who-we-are scroll-animate fade-up">
        <div class="section-bg-objects">
            <div class="section-bg-object circle automove2" style="width:110px;height:110px;top:20%;left:80%;"></div>
            <div class="section-bg-object square automove3" style="width:80px;height:80px;top:70%;left:15%;"></div>
            <div class="section-bg-object triangle automove1" style="top:55%;left:60%;width:0;height:0;"></div>
        </div>
        <div class="container">
            <div class="row align-items-center scroll-animate-stagger fade-up">
                <!-- Text Content -->
                <div class="col-md-6">
                    <h2 class="section-title">Who we are</h2>
                    <p class="section-description" style="text-align: justify;">
                        RestEase is a web-based Cemetery Records and Certificate Management System designed for the Municipal Planning and Development Office (MPDO) of Padre Garcia, Batangas. The system was created to modernize cemetery operations by shifting from manual, paper-based processes to a digital platform that ensures organized record management, efficient niche tracking, and renewal reminders.
                    </p>
                    <a href="about-us.php" class="btn btn-primary btn-read-more">Read More</a>
                </div>
                <!-- Image -->
                <div class="col-md-6 text-center">
                    <img src="assets/testimony-image.webp" alt="Who we are" class="img-fluid rounded">
                </div>
            </div>

            <!-- Our Services Section (Connected to Who We Are) -->
            <div class="text-center mt-5 pt-5 scroll-animate fade-down">
                <h2 class="section-title">Our Services</h2>
                <p class="section-description">
                    RestEase offers a modern, efficient, and transparent approach to cemetery management through digital innovation.
                </p>
                <div class="row mt-4 d-flex align-items-stretch scroll-animate-stagger scale-in">
                    <!-- Card 1 -->
                    <div class="col-md-4 d-flex">
                        <div class="service-card flex-grow-1 text-center">
                            <div class="d-flex align-items-center justify-content-center">
                                <h3 class="service-title flex-grow-1">Record Keeping</h3>
                                <div class="icon">
                                    <img src="assets/recordsicon.png" alt="Record Keeping" class="img-fluid">
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
                                    <img src="assets/mappicon.png" alt="Cemetery Mapping" class="img-fluid">
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
                                    <img src="assets/notificon.png" alt="Notifications and Reminders" class="img-fluid">
                                </div>
                            </div>
                            <p class="service-description" style="text-align: center;">
                                Our notification system keeps families informed by sending timely alerts for certificate renewals, updates, and important announcements—ensuring no deadlines are missed.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Explore RestEase Section (NEW) -->
    <section id="explore-restease" class="explore-restease-section py-5 scroll-animate fade-up" style="min-height: 70vh; display: flex; align-items: center;">
        <div class="section-bg-objects">
            <div class="section-bg-object circle automove3" style="width:90px;height:90px;top:15%;left:75%;"></div>
            <div class="section-bg-object square automove1" style="width:65px;height:65px;top:80%;left:25%;"></div>
            <div class="section-bg-object triangle automove2" style="top:60%;left:50%;width:0;height:0;"></div>
        </div>
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

The system provides a secure and transparent way to manage cemetery information, ensuring that data remains accurate and protected. Families can locate niches, receive renewal notifications, and access important updates online, while administrators benefit from organized records and faster processing. </p>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Image Content -->
                <div class="col-lg-5 col-md-12 text-center d-flex justify-content-center align-items-center" style="height:100%;">
                    <img src="assets/explore.png" alt="Explore RestEase" class="img-fluid rounded" style="max-width: 350px;">
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Form Section -->
    <section class="contact-section scroll-animate fade-down">
        <div class="section-bg-objects">
            <div class="section-bg-object circle automove1" style="width:120px;height:120px;top:30%;left:10%;"></div>
            <div class="section-bg-object square automove2" style="width:85px;height:85px;top:75%;left:80%;"></div>
            <div class="section-bg-object triangle automove3" style="top:65%;left:40%;width:0;height:0;"></div>
        </div>
        <div class="container">
            <div class="row">
                <div class="col-lg-7">
                    <h2>Contact Us</h2>
                    <p class="mb-4">Connect with us for more information or assistance. Whether you have concerns, suggestions, or need help, we're just a message away!</p>
                    
                    <?php if ($successMsg): ?>
                        <div class="alert alert-success"><?php echo $successMsg; ?></div>
                    <?php elseif ($errorMsg): ?>
                        <div class="alert alert-danger"><?php echo $errorMsg; ?></div>
                    <?php endif; ?>

                    <form method="POST" id="contactForm" novalidate>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <input type="text" class="form-control" name="name" placeholder="Name" required>
                                <div class="invalid-feedback">Name is required.</div>
                            </div>
                            <div class="col-md-6">
                                <input type="text" class="form-control" name="contact" placeholder="Contact" required>
                                <div class="invalid-feedback">Contact is required.</div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <input type="email" class="form-control" name="email" placeholder="Email Address" required>
                            <div class="invalid-feedback">Valid email is required.</div>
                        </div>
                        <div class="mb-3">
                            <textarea class="form-control" name="message" rows="6" placeholder="Message" required></textarea>
                            <div class="invalid-feedback">Message is required.</div>
                        </div>
                        <!-- Cloudflare Turnstile widget -->
                        <div class="mb-3 w-100 turnstile-container" aria-hidden="false">
                            <div class="cf-turnstile" data-sitekey="<?php echo htmlspecialchars($turnstile_sitekey); ?>" data-theme="light"></div>
                        </div>
                        <button type="submit" class="submit-btn">Submit</button>
                    </form>
                </div>
                
                <div class="col-lg-5">
                    <div class="contact-info d-flex flex-column justify-content-center h-100" style="min-height: 400px;">
                        <h3>Address</h3>
                        <p>
                            <a href="https://maps.app.goo.gl/JpSWRWs45M6FuBQe8" 
                               target="_blank" 
                               rel="noopener noreferrer" 
                               style="text-decoration: none; color: inherit;">
                               281 V. Luna St., Padre Garcia, 4224 Batangas
                            </a>
                             <a href="https://maps.app.goo.gl/mDY9KqeRBaXTm2JR6" 
                               target="_blank" 
                               rel="noopener noreferrer" 
                               style="text-decoration: none; color: inherit;">
                             <p>V6F8+P38, Padre Garcia, Batangas</p>
                             </a>
                              <a href="https://maps.app.goo.gl/gKD6GszPE12M2GRn9" 
                               target="_blank" 
                               rel="noopener noreferrer" 
                               style="text-decoration: none; color: inherit;">
                                <p> V6MF+8JH, Banaba, Padre Garcia, Batangas</p>
                            </a>
                        </p>
                        
                        <h3>Contact</h3>
                        <p>Phone: +0923-456-789</p>
                        <p>Email: resteasempdo@gmail.com</p>
                        
                        <h3>Open Time</h3>
                        <p>Monday - Sunday : 8:00am - 5:00pm</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="testimony py-5 scroll-animate scale-in">
        <div class="section-bg-objects">
            <div class="section-bg-object circle automove2" style="width:110px;height:110px;top:25%;left:60%;"></div>
            <div class="section-bg-object square automove3" style="width:65px;height:65px;top:85%;left:20%;"></div>
            <div class="section-bg-object triangle automove1" style="top:70%;left:70%;width:0;height:0;"></div>
        </div>
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

    // Multi-effect Scroll Animation Script
    let lastScrollY = window.scrollY;
    function animateOnScroll() {
        var elements = document.querySelectorAll('.scroll-animate, .scroll-animate-stagger');
        var windowHeight = window.innerHeight;
        let currentScrollY = window.scrollY;
        let direction = currentScrollY > lastScrollY ? 'down' : 'up';
        elements.forEach(function(el) {
            var rect = el.getBoundingClientRect();
            if (rect.top < windowHeight - 60) {
                el.classList.add('visible');
                // Multi-effect: toggle effect class based on scroll direction
                if (el.classList.contains('fade-up') || el.classList.contains('fade-down') || el.classList.contains('scale-in')) {
                    el.classList.remove('fade-up', 'fade-down', 'scale-in');
                    if (direction === 'down') {
                        el.classList.add('fade-up');
                    } else {
                        el.classList.add('fade-down');
                    }
                }
            } else {
                el.classList.remove('visible');
            }
        });
        lastScrollY = currentScrollY;
    }
    document.addEventListener('scroll', animateOnScroll);
    document.addEventListener('DOMContentLoaded', animateOnScroll);
    window.addEventListener('resize', animateOnScroll);
    setTimeout(animateOnScroll, 100);

    // Client-side validation for contact form
    document.getElementById('contactForm').addEventListener('submit', function(e) {
        let valid = true;
        const form = e.target;
        // Name
        if (!form.name.value.trim()) {
            form.name.classList.add('is-invalid');
            valid = false;
        } else {
            form.name.classList.remove('is-invalid');
        }
        // Contact
        if (!form.contact.value.trim()) {
            form.contact.classList.add('is-invalid');
            valid = false;
        } else {
            form.contact.classList.remove('is-invalid');
        }
        // Email
        if (!form.email.value.trim() || !form.email.value.match(/^[^@]+@[^@]+\.[^@]+$/)) {
            form.email.classList.add('is-invalid');
            valid = false;
        } else {
            form.email.classList.remove('is-invalid');
        }
        // Message
        if (!form.message.value.trim()) {
            form.message.classList.add('is-invalid');
            valid = false;
        } else {
            form.message.classList.remove('is-invalid');
        }
        if (!valid) {
            e.preventDefault();
        }
    });
    </script>
    <style>
        /* Turnstile alignment: center and constrain so it lines up with inputs */
        .turnstile-container {
            max-width: 420px;
            margin: 8px auto 16px;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .turnstile-container .cf-turnstile {
            display: inline-flex !important;
            justify-content: center;
            align-items: center;
        }
        @media (max-width: 480px) {
            .turnstile-container { max-width: 100%; padding: 0 12px; }
        }
    </style>
</body>
</html>