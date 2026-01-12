<?php
// Database connection
session_start();
include_once 'Includes/db.php';

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$login_error = "";
$login_success = false;
// Cloudflare Turnstile keys (replace with your keys)
$turnstile_sitekey = '0x4AAAAAAB9DMwi4JEs-E7Dk';
$turnstile_secret  = '0x4AAAAAAB9DM0HH3_jtHziIMzaFQztRwcA';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $turnstile_response = $_POST['cf-turnstile-response'] ?? '';

    // Basic validation
    if (!$email || !$password) {
        $login_error = "All fields are required.";
    } elseif (empty($turnstile_response)) {
        $login_error = "Please complete the verification first(Cloudfare).";
    } else {
        // Cloudflare Turnstile verification
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

        if (!verify_turnstile($turnstile_response, $turnstile_secret, $_SERVER['REMOTE_ADDR'] ?? '')) {
            $login_error = "Turnstile verification failed. Please try again.";
        }
    }
    // Only proceed if no error
    if (!$login_error) {
        // Try admin login first - now check status
        $stmt = $conn->prepare("SELECT id, password, status FROM admin_accounts WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows == 1) {
            $stmt->bind_result($admin_id, $hashed_password, $admin_status);
            $stmt->fetch();
            if ($admin_status === 'disabled') {
                $login_error = "Your account has been disabled. Please contact the system administrator.";
            } elseif (!is_null($hashed_password) && password_verify($password, $hashed_password)) {
                $_SESSION['admin_id'] = $admin_id;
                header("Location: AdminSide/Dashboard.php");
                exit;
            } else {
                $login_error = "Incorrect password.";
            }
            $stmt->close();
        } else {
            $stmt->close();
            // Try client login
            $stmt = $conn->prepare("SELECT id, password, status FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows == 1) {
                $stmt->bind_result($user_id, $hashed_password, $user_status);
                $stmt->fetch();
                if ($user_status === 'disabled') {
                    $login_error = "Your account has been disabled. Please contact support.";
                } elseif (!is_null($hashed_password) && password_verify($password, $hashed_password)) {
                    $_SESSION['user_id'] = $user_id;
                    header("Location: ClientSide/ClientHome.php");
                    exit;
                } else {
                    $login_error = "Incorrect password.";
                }
            } else {
                $login_error = "No account found with that email.";
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
    <title>Login - RestEase</title>
    <link rel="icon" type="image/png" href="./assets/re logo blue.png">
    <!-- Add Google Fonts for Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Add Google Sign-In Meta Tag
    <meta name="google-signin-client_id" content="211739618373-kh9966m09dm8kbifi7goe7c6jeu141mi.apps.googleusercontent.com"> -->
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

                <!-- Right Side - Login Form -->
                <div class="col-md-6 right-side">
                    <div class="login-form">
                        <h2>Sign In</h2>
                        <!-- Login result toast -->
                        <?php if ($login_error || $login_success): ?>
                            <div id="customToast" class="custom-toast <?php echo $login_success ? 'success' : 'error'; ?>">
                                <div class="toast-icon">
                                    <?php if ($login_success): ?>
                                        <i class="fas fa-check-circle"></i>
                                    <?php else: ?>
                                        <i class="fas fa-exclamation-circle"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="toast-message">
                                    <?php if ($login_success): ?>
                                        Login successful!
                                    <?php else: ?>
                                        <?php echo $login_error; ?>
                                    <?php endif; ?>
                                </div>
                                <span class="toast-close" onclick="closeToast()">&times;</span>
                            </div>
                        <?php endif; ?>
                        <!-- End Toast -->
                        <form id="loginForm" method="POST" action="">
                            <div class="mb-3">
                                <input type="email" class="form-control" placeholder="Email" id="email" name="email" required
                                    value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES); ?>">
                                <div class="invalid-feedback" id="emailError" style="display:none;">
                                    Email must end with @yahoo.com, @gmail.com, @padregarcia.gov.ph, or @restease.com.
                                </div>
                            </div>
                            <div class="mb-3 password-container">
                                <input type="password" class="form-control" placeholder="Password" id="password" name="password" required autocomplete="off"
                                    value="<?php echo htmlspecialchars($_POST['password'] ?? '', ENT_QUOTES); ?>">
                                <span class="password-toggle">
                                    <i class="far fa-eye" id="togglePassword"></i>
                                </span>
                                <div class="invalid-feedback" id="passwordError" style="display:none;">
                                    Password is required.
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="remember">
                                    <label class="form-check-label" for="remember">Remember Me</label>
                                </div>
                                <a href="forgot.php" class="forgot-password">Forgot Password?</a>
                            </div>
                            <!-- Turnstile: centered & constrained to match input width -->
                            <div class="mb-3 w-100 recaptcha-fullwidth turnstile-container" aria-hidden="false">
                                <div class="cf-turnstile" data-sitekey="<?php echo htmlspecialchars($turnstile_sitekey); ?>" data-theme="light"></div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Sign In</button>
                            <div class="divider">
                                <span>or</span>
                            </div>
                            <!-- <button type="button" class="btn btn-google w-100" onclick="handleGoogleSignIn()">
                                <img src="assets/google-icon.png" alt="Google">
                                Sign in with Google
                            </button> -->
                            <p class="signup-text mt-4 text-center">
                                Don't have an account? <a href="register.php">Sign Up</a>
                            </p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Google Sign-In API -->
    <script src="https://apis.google.com/js/platform.js" async defer></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Password toggle functionality
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');

        togglePassword.addEventListener('click', function(e) {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });

        // Initialize Google Sign-In
        function init() {
            gapi.load('auth2', function() {
                gapi.auth2.init({
                    client_id: 'YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com'
                });
            });
        }

        // Handle Google Sign-In
        function handleGoogleSignIn() {
            const auth2 = gapi.auth2.getAuthInstance();
            auth2.signIn().then(function(googleUser) {
                // Get user profile information
                const profile = googleUser.getBasicProfile();
                const id_token = googleUser.getAuthResponse().id_token;

                // Here you would typically send the id_token to your server
                // for verification and to create a session
                console.log('ID Token:', id_token);
                console.log('User ID:', profile.getId());
                console.log('Name:', profile.getName());
                console.log('Email:', profile.getEmail());

                // Redirect to dashboard or handle the sign-in as needed
                // window.location.href = 'dashboard.html';
            }).catch(function(error) {
                console.error('Error:', error);
            });
        }

        // Custom Toast Logic (same as register.php)
        function closeToast() {
            document.getElementById('customToast').style.opacity = '0';
            setTimeout(function() {
                document.getElementById('customToast').style.display = 'none';
            }, 300);
        }
        <?php if ($login_error || $login_success): ?>
            document.addEventListener('DOMContentLoaded', function() {
                var toast = document.getElementById('customToast');
                toast.style.opacity = '1';
                setTimeout(closeToast, 4000);
            });
        <?php endif; ?>

        // Email validation function
        function validateEmail(email) {
            return email.endsWith('@yahoo.com') || email.endsWith('@gmail.com') || email.endsWith('@padregarcia.gov.ph') || email.endsWith('@restease.com');
        }

        let attemptedSubmit = false;
        const loginForm = document.getElementById('loginForm');
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const emailError = document.getElementById('emailError');
        const passwordError = document.getElementById('passwordError');

        function showValidation() {
            // Email validation
            if (!validateEmail(emailInput.value.trim())) {
                emailInput.classList.add('is-invalid');
                emailError.style.display = 'block';
            } else {
                emailInput.classList.remove('is-invalid');
                emailError.style.display = 'none';
            }
            // Password validation
            if (!passwordInput.value.trim()) {
                passwordInput.classList.add('is-invalid');
                passwordError.textContent = "Password is required.";
                passwordError.style.display = 'block';
            } else {
                passwordInput.classList.remove('is-invalid');
                passwordError.style.display = 'none';
            }
        }

        loginForm.addEventListener('submit', function(e) {
            attemptedSubmit = true;
            let valid = true;

            if (!validateEmail(emailInput.value.trim())) valid = false;
            if (!passwordInput.value.trim()) valid = false;

            if (!valid) {
                showValidation();
                e.preventDefault();
            }
        });

        emailInput.addEventListener('input', function() {
            if (attemptedSubmit) showValidation();
        });
        passwordInput.addEventListener('input', function() {
            if (attemptedSubmit) showValidation();
        });

        // Password validation (only highlight if server error)
        <?php if ($login_error && $login_error == "Incorrect password.") { ?>
            passwordInput.classList.add('is-invalid');
        <?php } ?>
    </script>
    <style>
        /* Custom Toast Styles (upper right corner, like register.php) */
        .custom-toast {
            position: fixed !important;
            top: 40px !important;
            right: 40px !important;
            left: auto !important;
            transform: none !important;
            min-width: 320px;
            max-width: 400px;
            display: flex;
            align-items: center;
            background: #fff;
            box-shadow: 0 8px 32px rgba(60, 60, 60, 0.18), 0 1.5px 6px rgba(0, 0, 0, 0.08);
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

        .is-invalid {
            border-color: #e74c3c !important;
            box-shadow: 0 0 0 0.2rem rgba(231, 76, 60, .25);
            background-image: none !important;
            padding-right: 0.75rem !important;
        }

        .invalid-feedback {
            color: #e74c3c;
            font-size: 0.95rem;
            margin-top: 0.25rem;
            padding-left: 0;
        }

        .btn-primary.w-100 {
            background-color: #0077b6 !important;
            border-color: #0077b6 !important;
        }

        .btn-primary.w-100:hover,
        .btn-primary.w-100:focus {
            background-color: #005a8d !important;
            border-color: #005a8d !important;
        }

        /* Turnstile alignment: center and constrain so it lines up with inputs */
        .turnstile-container {
            max-width: 420px;        /* match typical form width */
            margin: 8px auto 16px;   /* center and add vertical spacing */
            display: flex;
            justify-content: center;
            align-items: center;
        }
        /* Defensive: ensure the internal widget is centered */
        .turnstile-container .cf-turnstile {
            display: inline-flex !important;
            justify-content: center;
            align-items: center;
        }
        /* On narrow screens keep it fluid */
        @media (max-width: 480px) {
            .turnstile-container { max-width: 100%; padding: 0 12px; }
        }

        @media (max-width: 600px) {
            .custom-toast {
                right: 10px !important;
                left: 10px !important;
                min-width: unset;
                max-width: unset;
                padding: 1rem;
            }
        }
    </style>
    <!-- Cloudflare Turnstile -->
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
</body>

</html>