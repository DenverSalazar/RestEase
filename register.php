<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = ""; // Change if you have a password set for root
$dbname = "cemeterydb"; // Updated database name

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$register_success = false;
$register_error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $terms = isset($_POST['terms']);

    // Basic validation
    if (!$first_name || !$last_name || !$email || !$password || !$confirm_password) {
        $register_error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $register_error = "Invalid email format.";
    } elseif (strlen($password) < 8) {
        $register_error = "Password must be at least 8 characters long.";
    } elseif ($password !== $confirm_password) {
        $register_error = "Passwords do not match.";
    } elseif (!$terms) {
        $register_error = "You must agree to the Terms & Conditions.";
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $register_error = "Email already registered.";
        } else {
            // Insert new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $first_name, $last_name, $email, $hashed_password);
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
                                    <input type="text" class="form-control" placeholder="First name" name="first_name" required>
                                </div>
                                <div class="col-md-6">
                                    <input type="text" class="form-control" placeholder="Last name" name="last_name" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <input type="email" class="form-control" placeholder="Email" name="email" required>
                            </div>
                            <div class="mb-3 password-container">
                                <input type="password" class="form-control" placeholder="Enter your password" id="password" name="password" required>
                                <span class="password-toggle">
                                    <i class="far fa-eye" id="togglePassword"></i>
                                </span>
                            </div>
                            <div class="mb-3 password-container">
                                <input type="password" class="form-control" placeholder="Confirm password" id="confirmPassword" name="confirm_password" required>
                                <span class="password-toggle">
                                    <i class="far fa-eye" id="toggleConfirmPassword"></i>
                                </span>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                                <label class="form-check-label" for="terms">I agree to the <a href="#" class="terms-link">Terms & Conditions</a></label>
                            </div>
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
    </script>
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
    </style>
</body>
</html>
