<?php
// Database connection
include_once 'Includes/db.php';

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$register_success = false;
$register_error = "";

// Store submitted values for repopulation
$input = [
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'contact_no' => '',
    'password' => '',
    'confirm_password' => '',
    'terms' => false
];
$field_errors = [
    'first_name' => false,
    'last_name' => false,
    'email' => false,
    'contact_no' => false,
    'password' => false,
    'confirm_password' => false,
    'terms' => false
];

require 'vendor/autoload.php'; // For PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Add a flag to track verification step
$show_verification_form = false;
$verification_error = "";
$email_sent = false; // already present

// Add: detect GET status for email sent
if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET['status']) && $_GET['status'] === 'emailsent') {
    $email_sent = true;
}

// Only process registration logic on POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // If verification code is submitted
    if (isset($_POST['verification_code']) && isset($_POST['pending_email'])) {
        $pending_email = $_POST['pending_email'];
        $verification_code = $_POST['verification_code'];
        // Check code in pending_users
        $stmt = $conn->prepare("SELECT * FROM pending_users WHERE email = ? AND verification_code = ?");
        $stmt->bind_param("ss", $pending_email, $verification_code);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            // Move user to users table
            $stmt2 = $conn->prepare("INSERT INTO users (first_name, last_name, email, contact_no, password) VALUES (?, ?, ?, ?, ?)");
            $stmt2->bind_param("sssss", $row['first_name'], $row['last_name'], $row['email'], $row['contact_no'], $row['password']);
            if ($stmt2->execute()) {
                $register_success = true;
                // Delete from pending_users
                $stmt3 = $conn->prepare("DELETE FROM pending_users WHERE email = ?");
                $stmt3->bind_param("s", $pending_email);
                $stmt3->execute();
            } else {
                $verification_error = "Registration failed. Please try again.";
            }
        } else {
            $verification_error = "Invalid verification code.";
            $show_verification_form = true;
        }
        $stmt->close();
    } else {
        $input['first_name'] = trim($_POST['first_name']);
        $input['last_name'] = trim($_POST['last_name']);
        $input['email'] = trim($_POST['email']);
        $input['contact_no'] = trim($_POST['contact_no']);
        $input['password'] = $_POST['password'];
        $input['confirm_password'] = $_POST['confirm_password'];
        $input['terms'] = isset($_POST['terms']);

        // Enforce maximum lengths
        if (mb_strlen($input['first_name']) > 30) {
            $register_error = "First name must be 30 characters or fewer.";
            $field_errors['first_name'] = true;
        }
        if (mb_strlen($input['last_name']) > 30) {
            $register_error = "Last name must be 30 characters or fewer.";
            $field_errors['last_name'] = true;
        }
        if (mb_strlen($input['email']) > 50) {
            $register_error = "Email must be 50 characters or fewer.";
            $field_errors['email'] = true;
        }

        // Basic validation
        if (!$input['first_name'] || !$input['last_name'] || !$input['email'] || !$input['contact_no'] || !$input['password'] || !$input['confirm_password']) {
            $register_error = "All fields are required.";
            foreach ($field_errors as $k => $_) $field_errors[$k] = !$input[$k];
        } elseif (!preg_match('/^[\p{L} ]+$/u', $input['first_name'])) {
            // allow letters and spaces (Unicode aware)
            $register_error = "First name must only contain letters and spaces.";
            $field_errors['first_name'] = true;
        } elseif (!preg_match('/^[\p{L} ]+$/u', $input['last_name'])) {
            // allow letters and spaces (Unicode aware)
            $register_error = "Last name must only contain letters and spaces.";
            $field_errors['last_name'] = true;
        } elseif (!filter_var($input['email'], FILTER_VALIDATE_EMAIL) ||
                  !(preg_match('/@gmail\.com$/', $input['email']) || preg_match('/@yahoo\.com$/', $input['email']))) {
            $register_error = "Email must be a valid Gmail or Yahoo address.";
            $field_errors['email'] = true;
        } elseif (!preg_match('/^09[0-9]{9}$/', $input['contact_no'])) {
            $register_error = "Contact number must start with 09 and be exactly 11 digits.";
            $field_errors['contact_no'] = true;
        } elseif (strlen($input['password']) < 8) {
            $register_error = "Password must be at least 8 characters long.";
            $field_errors['password'] = true;
        } elseif (!preg_match('/[A-Za-z]/', $input['password']) || !preg_match('/[0-9]/', $input['password'])) {
            $register_error = "Password must contain at least one letter and one number.";
            $field_errors['password'] = true;
        } elseif ($input['password'] !== $input['confirm_password']) {
            $register_error = "Passwords do not match.";
            $field_errors['confirm_password'] = true;
        } elseif (!$input['terms']) {
            $register_error = "You must agree to the Terms & Conditions.";
            $field_errors['terms'] = true;
        } else {
            // Only proceed if no error
            // Check if email already exists in users or pending_users
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $input['email']);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $register_error = "Email already registered.";
            } else {
                $stmt2 = $conn->prepare("SELECT id FROM pending_users WHERE email = ?");
                $stmt2->bind_param("s", $input['email']);
                $stmt2->execute();
                $stmt2->store_result();
                if ($stmt2->num_rows > 0) {
                    $register_error = "A verification email has already been sent to this address. Please check your inbox.";
                } else {
                    // Generate unique token
                    $verification_token = bin2hex(random_bytes(32));
                    $hashed_password = password_hash($input['password'], PASSWORD_DEFAULT);
                    // Store in pending_users
                    $stmt3 = $conn->prepare("INSERT INTO pending_users (first_name, last_name, email, contact_no, password, verification_token) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt3->bind_param("ssssss", $input['first_name'], $input['last_name'], $input['email'], $input['contact_no'], $hashed_password, $verification_token);
                    if ($stmt3->execute()) {
                        // Send email using PHPMailer
                        $mail = new PHPMailer(true);
                        try {
                            $mail->isSMTP();
                            $mail->Host       = 'smtp.gmail.com';
                            $mail->SMTPAuth   = true;
                            $mail->Username   = 'resteasempdo@gmail.com';
                            $mail->Password   = 'vvkblrlppiflbksu';
                            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                            $mail->Port       = 587;

                            $mail->setFrom('resteasempdo@gmail.com', 'RestEase');
                            $mail->addAddress($input['email'], $input['first_name'] . ' ' . $input['last_name']);

                            $verify_link = "http://localhost/RestEase/verify.php?token=$verification_token";
                            $mail->isHTML(true);
                            $mail->Subject = 'RestEase Email Verification';
                            $mail->Body = '
                                <div style="max-width:480px;margin:0 auto;font-family:Poppins,Arial,sans-serif;background:#fff;border-radius:12px;box-shadow:0 2px 8px rgba(60,60,60,0.08);padding:32px 24px;">
                                    <div style="text-align:center;margin-bottom:18px;">
                                        <img src="https://resteasegarcia.com/assets/re%20logo%20blue.png" alt="RestEase Logo" style="height:48px;margin-bottom:8px;">
                                        <h2 style="font-weight:600;color:#38d39f;margin:0 0 8px 0;">Verify your email address</h2>
                                    </div>
                                    <p style="font-size:1.08rem;color:#222;margin-bottom:24px;">
                                        Please confirm that you want to use this as your RestEase account email address.<br>
                                        Once it\'s done you will be able to start using RestEase!
                                    </p>
                                    <div style="text-align:center;margin-bottom:24px;">
                                        <a href="' . $verify_link . '" style="
                                            display:inline-block;
                                            padding:16px 32px;
                                            background:#38d39f;
                                            color:#fff;
                                            font-size:1.15rem;
                                            font-weight:600;
                                            border-radius:8px;
                                            text-decoration:none;
                                            margin-bottom:8px;
                                            box-shadow:0 2px 8px rgba(60,60,60,0.08);
                                        ">Verify my email</a>
                                    </div>
                                </div>
                            ';
                            $mail->send();
                            // Reset form fields after successful pending registration
                            $input = [
                                'first_name' => '',
                                'last_name' => '',
                                'email' => '',
                                'contact_no' => '',
                                'password' => '',
                                'confirm_password' => '',
                                'terms' => false
                            ];
                            // Redirect to avoid resubmission on refresh
                            header("Location: register.php?status=emailsent");
                            exit();
                        } catch (Exception $e) {
                            $register_error = "Verification email could not be sent. Mailer Error: {$mail->ErrorInfo}";
                        }
                    } else {
                        $register_error = "Registration failed. Please try again.";
                    }
                    $stmt3->close();
                }
                $stmt2->close();
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="./assets/re logo blue.png">
    <title>RestEase - Sign Up</title>
    <!-- Add Google Fonts for Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/register.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- <script src="https://www.google.com/recaptcha/api.js" async defer></script> -->
    <style>
        /* Custom Toast Styles */
        .custom-toast {
            position: fixed;
            top: 40px;
            right: 40px;
            min-width: 320px;
            max-width: 400px;
            display: flex;
            align-items: center;
            background: #fff;
            box-shadow: 0 8px 32px rgba(60,60,60,0.18), 0 1.5px 6px rgba(0,0,0,0.08);
            border-radius: 1rem;
            padding: 1.1rem 1.5rem;
            z-index: 9999;
            font-family: 'Poppins', sans-serif;
            font-size: 1.08rem;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .custom-toast.success {
            border-left: 6px solid #38d39f;
        }
        .custom-toast.error {
            border-left: 6px solid #e74c3c;
        }
        .custom-toast .toast-icon {
            font-size: 2rem;
            margin-right: 1rem;
            color: #38d39f;
        }
        .custom-toast.error .toast-icon {
            color: #e74c3c;
        }
        .custom-toast .toast-message {
            flex: 1;
        }
        .custom-toast .toast-close {
            font-size: 1.5rem;
            color: #888;
            cursor: pointer;
            margin-left: 1rem;
            transition: color 0.2s;
        }
        .custom-toast .toast-close:hover {
            color: #222;
        }
        @media (max-width: 600px) {
            .custom-toast {
                right: 10px;
                left: 10px;
                min-width: unset;
                max-width: unset;
            }
        }
        .is-invalid {
            border-color: #e74c3c !important;
            box-shadow: 0 0 0 0.2rem rgba(231,76,60,.25);
        }
        #termsModal {
            display: none;
            position: fixed;
            z-index: 99999;
            left: 0; top: 0; right: 0; bottom: 0;
            background: rgba(44,62,80,0.18);
            align-items: center;
            justify-content: center;
            transition: opacity 0.2s;
            padding: 20px; /* allow breathing space on very small devices */
            box-sizing: border-box;
        }
        #termsModal.show {
            opacity: 1;
        }
        .terms-modal-content {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 8px 32px rgba(60,60,60,0.18), 0 1.5px 6px rgba(0,0,0,0.08);
            position: relative;
            font-family: 'Poppins', Arial, sans-serif;
            animation: fadeInModal 0.2s;
            max-width: 480px;      /* keep desktop look */
            width: 100%;
            padding: 44px 32px 36px 32px; /* desktop padding */
            box-sizing: border-box;
            overflow: hidden;
            max-height: 650px;     /* desktop height */
            display: flex;
            flex-direction: column;

            /* added small vertical breathing space so modal doesn't touch viewport edges */
            margin: 16px 0;
        }
        @keyframes fadeInModal {
            from { opacity: 0; transform: scale(0.97);}
            to { opacity: 1; transform: scale(1);}
        }
        .terms-modal-close {
            position: absolute;
            font-size: 1.7rem;
            color: #888;
            background: none;
            border: none;
            cursor: pointer;
            transition: color 0.18s;
            top: 8px;
            right: 18px;
        }
        .terms-modal-close:hover {
            color: #222;
        }
        .terms-modal-title {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 8px;
            text-align: left;
        }
        .terms-modal-subtitle {
            color: #888;
            font-size: 1rem;
            font-weight: 500;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }
        .terms-modal-content-inner {
            font-size: 1.08rem;
            margin-bottom: 18px;
        }
        .terms-modal-list {
            margin-bottom: 18px;
            padding-left: 18px;
        }
        .terms-modal-list li {
            margin-bottom: 8px;
        }
        /* scrollable inner area */
        .terms-modal-inner-scroll {
            flex: 1;
            overflow-y: auto;
            padding-right: 8px;
            -webkit-overflow-scrolling: touch;
        }

        /* SMALL SCREENS: keep modal from covering entire viewport */
        @media (max-width: 600px) {
            #termsModal {
                padding: 12px;
            }
            .terms-modal-content {
                max-width: 98vw !important;
                width: 100% !important;
                padding: 12px 12px 14px 12px !important;
                border-radius: 12px !important;
                max-height: calc(100vh - 48px) !important; /* leave top/bottom space */
                height: auto !important;

                /* slightly reduce vertical margin on very small screens */
                margin: 12px 0 !important;
            }
            .terms-modal-title {
                font-size: 1.15rem;
            }
            .terms-modal-subtitle {
                font-size: 0.95rem;
            }
            .terms-modal-close {
                font-size: 1.4rem;
                top: 8px;
                right: 12px;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                    <img src="assets/RE Logo New.png" alt="Logo">
                </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="about-us.php">About Us</a></li>
                    <li class="nav-item"><a class="nav-link" href="contact-us.php">Contact Us</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <div class="login-container">
            <div class="row g-0">
                <!-- Left Side - Image with Text -->
                <div class="col-md-6 left-side">
                    <div class="content-overlay">
                        <h1>Welcome to<br>RestEase</h1>
                        <p>Log in to your RestEase account to seamlessly handle cemetery records, manage certificates, and streamline renewal processes with ease.</p>
                    </div>
                </div>

                <!-- Right Side - Registration Form -->
                <div class="col-md-6 right-side">
                    <div class="login-form">
                        <h2>Sign Up</h2>
                        <!-- Custom Toast Notification -->
                        <?php if ($register_success || $register_error || $verification_error || $email_sent): ?>
                        <?php
                            $is_verification_sent = $email_sent;
                            $toast_type = ($register_success || $is_verification_sent) ? 'success' : 'error';
                        ?>
                        <div id="customToast" class="custom-toast <?php echo $toast_type; ?>">
                            <div class="toast-icon">
                                <?php if ($register_success || $is_verification_sent): ?>
                                    <i class="fas fa-check-circle"></i>
                                <?php else: ?>
                                    <i class="fas fa-exclamation-circle"></i>
                                <?php endif; ?>
                            </div>
                            <div class="toast-message">
                                <?php
                                if ($register_success) {
                                    echo "Registration successful!";
                                } elseif ($email_sent) {
                                    echo "A verification email has been sent. Please check your inbox.";
                                } elseif ($register_error) {
                                    echo $register_error;
                                } elseif ($verification_error) {
                                    echo $verification_error;
                                }
                                ?>
                            </div>
                            <span class="toast-close" onclick="closeToast()">&times;</span>
                        </div>
                        <?php endif; ?>
                        <!-- End Toast -->
                        <?php if ($show_verification_form): ?>
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="verification_code">Enter the verification code sent to your email:</label>
                                    <input type="text" class="form-control" name="verification_code" id="verification_code" required>
                                    <input type="hidden" name="pending_email" value="<?php echo htmlspecialchars($input['email']); ?>">
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Verify & Complete Registration</button>
                            </form>
                        <?php else: ?>
                            <form method="POST" action="">
                                <div class="row ">
                                    <div class="col-md-6">
                                        <input type="text" maxlength="30" class="form-control <?php if($field_errors['first_name']) echo 'is-invalid'; ?>"
                                            placeholder="First name" name="first_name" required
                                            value="<?php echo htmlspecialchars($input['first_name']); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <input type="text" maxlength="30" class="form-control <?php if($field_errors['last_name']) echo 'is-invalid'; ?>"
                                            placeholder="Last name" name="last_name" required
                                            value="<?php echo htmlspecialchars($input['last_name']); ?>">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <input type="email" maxlength="50" class="form-control <?php if($field_errors['email']) echo 'is-invalid'; ?>"
                                        placeholder="Email" name="email" required
                                        value="<?php echo htmlspecialchars($input['email']); ?>">
                                </div>
                                <div class="mb-3">
                                    <input type="text" class="form-control <?php if($field_errors['contact_no']) echo 'is-invalid'; ?>"
                                        placeholder="Contact No." name="contact_no" required
                                        value="<?php echo htmlspecialchars($input['contact_no']); ?>">
                                </div>
                                <div class="mb-3 password-container">
                                    <input type="password" class="form-control <?php if($field_errors['password'])  echo 'is-invalid'; ?>"
                                        placeholder="Enter your password" id="password" name="password" required autocomplete="off"
                                        value="<?php echo htmlspecialchars($input['password']); ?>">
                                    <span class="password-toggle">
                                        <i class="far fa-eye" id="togglePassword"></i>
                                    </span>
                                </div>
                                <div class="mb-3 password-container">
                                    <input type="password" class="form-control <?php if($field_errors['confirm_password']) echo 'is-invalid'; ?>"
                                        placeholder="Confirm password" id="confirmPassword" name="confirm_password" required
                                        value="<?php echo htmlspecialchars($input['confirm_password']); ?>">
                                    <span class="password-toggle">
                                        <i class="far fa-eye" id="toggleConfirmPassword"></i>
                                    </span>
                                </div>
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input <?php if($field_errors['terms']) echo 'is-invalid'; ?>"
                                        id="terms" name="terms" required <?php if($input['terms']) echo 'checked'; ?>>
                                    <label class="form-check-label" for="terms">I agree to the <a href="#" class="terms-link" id="openTermsModal">Terms & Conditions</a></label>
                                </div>
                                <!-- reCAPTCHA widget -->
                                <!--
                                <div class="mb-3 w-100 recaptcha-fullwidth">
                                    <div class="g-recaptcha" data-sitekey="6LfMVFkrAAAAABQM916moTEIKZre2oCgfqLr_Dlj"></div>
                                </div>
                                -->
                                <button type="submit" class="btn btn-primary w-100">Create Account</button>
                                <p class="signup-text mt-4 text-center">
                                    Already have an account? <a href="login.php">Sign In</a>
                                </p>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Password Toggle Script -->
    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const toggleConfirmPassword = document.querySelector('#toggleConfirmPassword');
        const password = document.querySelector('#password');
        const confirmPassword = document.querySelector('#confirmPassword');

        togglePassword.addEventListener('click', function (e) {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });

        toggleConfirmPassword.addEventListener('click', function (e) {
            const type = confirmPassword.getAttribute('type') === 'password' ? 'text' : 'password';
            confirmPassword.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });

        // Custom Toast Logic
        function closeToast() {
            document.getElementById('customToast').style.opacity = '0';
            setTimeout(function() {
                document.getElementById('customToast').style.display = 'none';
            }, 300);
        }
        <?php if ($register_success || $register_error || $email_sent): ?>
        document.addEventListener('DOMContentLoaded', function() {
            var toast = document.getElementById('customToast');
            toast.style.opacity = '1';
            setTimeout(closeToast, 5000); // Auto-close after 5 seconds

            // Remove ?status=emailsent from URL after showing toast
            <?php if ($email_sent): ?>
            if (window.location.search.indexOf('status=emailsent') !== -1) {
                var url = window.location.origin + window.location.pathname;
                window.history.replaceState({}, document.title, url);
            }
            <?php endif; ?>
        });
        <?php endif; ?>

        <?php if ($register_success): ?>
        // Redirect to login.php after 1 second if registration is successful
        setTimeout(function() {
            window.location.href = "login.php";
        }, 1000);
        <?php endif; ?>

        // Terms & Conditions Modal Logic
        document.addEventListener('DOMContentLoaded', function() {
            var termsModal = document.getElementById('termsModal');
            var openTermsBtn = document.getElementById('openTermsModal');
            var closeTermsBtn = document.getElementById('closeTermsModal');
            openTermsBtn.addEventListener('click', function(e) {
                e.preventDefault();
                termsModal.style.display = 'flex';
                setTimeout(function() {
                    termsModal.classList.add('show');
                }, 10);
            });
            closeTermsBtn.addEventListener('click', function() {
                termsModal.classList.remove('show');
                setTimeout(function() {
                    termsModal.style.display = 'none';
                }, 200);
            });
            // Close modal when clicking outside content
            termsModal.addEventListener('click', function(e) {
                if (e.target === termsModal) {
                    closeTermsBtn.click();
                }
            });
            // Escape key closes modal
            document.addEventListener('keydown', function(e) {
                if (e.key === "Escape" && termsModal.style.display === 'flex') {
                    closeTermsBtn.click();
                }
            });
        });
    </script>
    <!-- Terms & Conditions Modal -->
    <div id="termsModal">
        <div class="terms-modal-content">
            <button class="terms-modal-close" id="closeTermsModal" aria-label="Close">&times;</button>
            <div class="terms-modal-inner-scroll">
                <div class="terms-modal-subtitle">AGREEMENT</div>
                <div class="terms-modal-title">Terms and Conditions</div>
                <div class="terms-modal-content-inner">
                    To proceed with managing cemetery records or requesting certificates through RestEase, you must first agree to these User Terms. By clicking "I AGREE", you confirm that you have read and accepted the responsibilities outlined below:
                </div>
                <div class="terms-modal-content-inner">
                    <strong>As a User, You Agree That:</strong>
                    <ul class="terms-modal-list">
                        <li>All information you provide (e.g., deceased details, applicant name, contact info) is accurate and complete.</li>
                        <li>You are authorized to request records or certificates for the deceased individuals listed.</li>
                        <li>You are using this system for legitimate and respectful purposes only.</li>
                        <li>Issuance of certificates (e.g., interment, renewal) is subject to review and approval by the Municipal Planning and Development Office (MPDO).</li>
                        <li>You will comply with all local rules and requirements related to cemetery management.</li>
                        <li>Providing false or misleading information may result in request rejection and possible account suspension.</li>
                    </ul>
                    <strong>Before Submitting Any Request:</strong>
                    <ul class="terms-modal-list">
                        <li>Ensure that all required documents are uploaded, clear, and complete.</li>
                        <li>Double-check your entries for accuracy before final submission.</li>
                        <li>Incomplete or incorrect submissions may cause delays or disapproval.</li>
                    </ul>
                    By using this system, you also agree to respect the privacy, integrity, and purpose of the platform. For questions, please contact your local MPDO office.
                </div>
            </div>
        </div>
    </div>
</body>
</html>
