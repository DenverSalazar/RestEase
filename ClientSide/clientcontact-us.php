<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: ../login.php"); // Adjust the path if needed
    exit;
}

// Cloudflare Turnstile keys (replace with your keys)
$turnstile_sitekey = '0x4AAAAAAB9DMwi4JEs-E7Dk';
$turnstile_secret  = '0x4AAAAAAB9DM0HH3_jtHziIMzaFQztRwcA';

$successMsg = $_SESSION['successMsg'] ?? '';
$errorMsg = $_SESSION['errorMsg'] ?? '';
unset($_SESSION['successMsg'], $_SESSION['errorMsg']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        // Send email (use PHPMailer if available, or mail())
        // For demo, use PHP mail()
        $to = 'resteasempdo@gmail.com';
        $subject = 'Client Contact Form Submission';
        $body = "Name: $name\nContact: $contact\nEmail: $email\nMessage:\n$message";
        $headers = "From: $email\r\n";
        if (mail($to, $subject, $body, $headers)) {
            $_SESSION['successMsg'] = 'Your message has been sent successfully!';
        } else {
            $_SESSION['errorMsg'] = 'Message could not be sent. Please try again later.';
        }
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
    <title>Contact Us - RestEase</title>
    <link rel="icon" type="image/png" href="../assets/re logo blue.png">
     <!-- Add Google Fonts for Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/footer.css">
    <link rel="stylesheet" href="../css/clientcontact-us.css">
    <!-- Cloudflare Turnstile -->
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
</head>
<body>
   <?php include '../Includes/navbar2.php'; ?>
   
    <!-- Contact Hero Section -->
    <section class="contact-hero">
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
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d4090.1235927031694!2d121.2215291108287!3d13.883317694178464!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x33bd1503355a800d%3A0xaf01fd3a0484847b!2sV6MF%2B8JH%2C%20Padre%20Garcia%2C%20Batangas!5e0!3m2!1sen!2sph!4v1743494319859!5m2!1sen!2sph" 
            width="600" 
            height="450" 
            style="border:0;" 
            allowfullscreen="" 
            loading="lazy">
        </iframe> -->
        
        </div>
    </section>

    <?php include '../Includes/footer-client.php'; ?>
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Client-side validation
    document.getElementById('contactForm').addEventListener('submit', function(e) {
        let valid = true;
        const form = e.target;
        if (!form.name.value.trim()) {
            form.name.classList.add('is-invalid');
            valid = false;
        } else {
            form.name.classList.remove('is-invalid');
        }
        if (!form.contact.value.trim()) {
            form.contact.classList.add('is-invalid');
            valid = false;
        } else {
            form.contact.classList.remove('is-invalid');
        }
        if (!form.email.value.trim() || !form.email.value.match(/^[^@]+@[^@]+\.[^@]+$/)) {
            form.email.classList.add('is-invalid');
            valid = false;
        } else {
            form.email.classList.remove('is-invalid');
        }
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
</body>
</html>