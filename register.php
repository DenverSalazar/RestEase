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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $input['first_name'] = trim($_POST['first_name']);
    $input['last_name'] = trim($_POST['last_name']);
    $input['email'] = trim($_POST['email']);
    $input['contact_no'] = trim($_POST['contact_no']);
    $input['password'] = $_POST['password'];
    $input['confirm_password'] = $_POST['confirm_password'];
    $input['terms'] = isset($_POST['terms']);

    // Basic validation
    if (!$input['first_name'] || !$input['last_name'] || !$input['email'] || !$input['contact_no'] || !$input['password'] || !$input['confirm_password']) {
        $register_error = "All fields are required.";
        foreach ($field_errors as $k => $_) $field_errors[$k] = !$input[$k];
    } elseif (!preg_match('/^[A-Za-z]+$/', $input['first_name'])) {
        $register_error = "First name must only contain letters.";
        $field_errors['first_name'] = true;
    } elseif (!preg_match('/^[A-Za-z]+$/', $input['last_name'])) {
        $register_error = "Last name must only contain letters.";
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
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $input['email']);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $register_error = "Email already registered.";
        } else {
            // Insert new user
            $hashed_password = password_hash($input['password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, contact_no, password) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $input['first_name'], $input['last_name'], $input['email'], $input['contact_no'], $hashed_password);
            if ($stmt->execute()) {
                $register_success = true;
            } else {
                $register_error = "Registration failed. Please try again.";
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        @media (max-width: 600px) {
            .terms-modal-content {
                max-width: 98vw !important;
                height: 90vh !important;
                padding: 12px 4px 12px 4px !important;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="#">
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
                        <?php if ($register_success || $register_error): ?>
                        <div id="customToast" class="custom-toast <?php echo $register_success ? 'success' : 'error'; ?>">
                            <div class="toast-icon">
                                <?php if ($register_success): ?>
                                    <i class="fas fa-check-circle"></i>
                                <?php else: ?>
                                    <i class="fas fa-exclamation-circle"></i>
                                <?php endif; ?>
                            </div>
                            <div class="toast-message">
                                <?php if ($register_success): ?>
                                    Registration successful!
                                <?php else: ?>
                                    <?php echo $register_error; ?>
                                <?php endif; ?>
                            </div>
                            <span class="toast-close" onclick="closeToast()">&times;</span>
                        </div>
                        <?php endif; ?>
                        <!-- End Toast -->
                        <form method="POST" action="">
                            <div class="row ">
                                <div class="col-md-6">
                                    <input type="text" class="form-control <?php if($field_errors['first_name']) echo 'is-invalid'; ?>"
                                        placeholder="First name" name="first_name" required
                                        value="<?php echo htmlspecialchars($input['first_name']); ?>">
                                </div>
                                <div class="col-md-6">
                                    <input type="text" class="form-control <?php if($field_errors['last_name']) echo 'is-invalid'; ?>"
                                        placeholder="Last name" name="last_name" required
                                        value="<?php echo htmlspecialchars($input['last_name']); ?>">
                                </div>
                            </div>
                            <div class="mb-3">
                                <input type="email" class="form-control <?php if($field_errors['email']) echo 'is-invalid'; ?>"
                                    placeholder="Email" name="email" required
                                    value="<?php echo htmlspecialchars($input['email']); ?>">
                            </div>
                            <div class="mb-3">
                                <input type="text" class="form-control <?php if($field_errors['contact_no']) echo 'is-invalid'; ?>"
                                    placeholder="Contact No." name="contact_no" required
                                    value="<?php echo htmlspecialchars($input['contact_no']); ?>">
                            </div>
                            <div class="mb-3 password-container">
                                <input type="password" class="form-control <?php if($field_errors['password']) echo 'is-invalid'; ?>"
                                    placeholder="Enter your password" id="password" name="password" required
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
        <?php if ($register_success || $register_error): ?>
        document.addEventListener('DOMContentLoaded', function() {
            var toast = document.getElementById('customToast');
            toast.style.opacity = '1';
            setTimeout(closeToast, 5000); // Auto-close after 5 seconds
        });
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
        <div class="terms-modal-content" style="max-width: 480px; width: 100%; min-width: 320px; padding: 44px 32px 36px 32px; box-sizing: border-box; height: 650px; display: flex; flex-direction: column;">
            <button class="terms-modal-close" id="closeTermsModal" aria-label="Close" style="top:8px;right:18px;">&times;</button>
            <div style="flex:1; overflow-y:auto; padding-right:6px;">
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
        </div>
    </div>
</body>
</html>
