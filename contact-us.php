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
    <link rel="icon" type="image/png" href="./assets/re logo blue.png">
    <title>Contact Us - RestEase</title>
    <!-- Add Google Fonts for Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/contact-us.css">
    <!-- Cloudflare Turnstile -->
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
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
            <h1 class="fade-in-up delay-1">Contact Us</h1>
            <p class="fade-in-up delay-2">Have questions or need assistance? We're here to help! Reach out to us for any inquiries about our system, services, or support.</p>
        </div>
    </section>

    <!-- Contact Form Section -->
    <section class="contact-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-7">
                    <h2>Get In Touch</h2>
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

    <!-- Map Section -->
    <section class="map-section">
        <div class="container">
          <iframe src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d1956.836550080935!2d121.2130494!3d13.879448!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x33bd15e82f6453ff%3A0x62b3c469d629b656!2sExecutive%20Building!5e1!3m2!1sen!2sph!4v1760854991699!5m2!1sen!2sph"
             width="600" 
             height="450" 
             style="border:0;" 
             allowfullscreen="" 
             loading="lazy" 
             referrerpolicy="no-referrer-when-downgrade">
            </iframe>

           <!-- another code if magka issue yang nasa taas
            <iframe 
            src=" <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d978.4400336587773!2d121.21520691399589!3d13.874290423281638!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x33bd145514365b4f%3A0xbd29096ad6542262!2sPadre%20Garcia%20Municipal%20Cemetery!5e1!3m2!1sen!2sph!4v1760685714850!5m2!1sen!2sph"
            width="600" 
            height="450" 
            style="border:0;" 
            allowfullscreen="" 
            loading="lazy">
        </iframe> -->
        
        </div>
    </section>
    <?php include 'Includes/footer.php'; ?>
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Client-side validation
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