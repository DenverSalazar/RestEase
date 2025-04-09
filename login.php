<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - RestEase</title>
    <!-- Add Google Fonts for Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Add Google Sign-In Meta Tag -->
    <meta name="google-signin-client_id" content="YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com">
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

                <!-- Right Side - Login Form -->
                <div class="col-md-6 right-side">
                    <div class="login-form">
                        <h2>Sign In</h2>
                        <form id="loginForm">
                            <div class="mb-3">
                                <input type="email" class="form-control" placeholder="Email" id="email">
                            </div>
                            <div class="mb-3 password-container">
                                <input type="password" class="form-control" placeholder="Password" id="password">
                                <span class="password-toggle">
                                    <i class="far fa-eye" id="togglePassword"></i>
                                </span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="remember">
                                    <label class="form-check-label" for="remember">Remember Me</label>
                                </div>
                                <a href="#" class="forgot-password">Forgot Password?</a>
                            </div>
                            
                            <!-- reCAPTCHA -->
                            <div class="mb-3">
                                <div class="g-recaptcha" data-sitekey="your_site_key"></div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Sign In</button>
                            <a class="btn btn-primary w-100" style="margin-top: 10px;" href="AdminSide/Dashboard.php">TESTING LOGIN NG ADMIN</a>

                            <div class="divider">
                                <span>or</span>
                            </div>

                            <button type="button" class="btn btn-google w-100" onclick="handleGoogleSignIn()">
                                <img src="assets/google-icon.png" alt="Google">
                                Sign in with Google
                            </button>

                            <p class="signup-text mt-4 text-center">
                                Don't have an account? <a href="register.php">Sign Up</a>
                            </p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- reCAPTCHA Script -->
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <!-- Google Sign-In API -->
    <script src="https://apis.google.com/js/platform.js" async defer></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Password toggle functionality
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');

        togglePassword.addEventListener('click', function (e) {
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

        // Handle regular form submission
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            // Add your regular login form handling here
        });
    </script>
    <script>init();</script>
</body>
</html>
