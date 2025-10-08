<?php
session_start();
require 'Includes/db.php';

$email = $_GET['email'] ?? $_POST['email'] ?? '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $code = $_POST['code'] ?? '';

    // Use prepared statement with mysqli
    $stmt = $conn->prepare("SELECT reset_code, reset_expires FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    // Check if code is valid and not expired
    if ($user && $user['reset_code'] === $code && strtotime($user['reset_expires']) > time()) {
        $_SESSION['reset_email'] = $email;
        header("Location: CreatePassword.php");
        exit;
    } else {
        $error = "Invalid or expired verification code.";
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RestEase - Forgot Password</title>
    <!-- Add Google Fonts for Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/register.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .btn-success {
            background-color: #0077b6 !important;
            border: none !important;
        }
        .btn-success:hover, .btn-success:focus {
            background-color: #005f8e !important;
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

                <!-- Right Side - Verification Form -->
                <div class="col-md-6 right-side">
                    <div class="login-form">
                        <h2>Check your Email</h2>
                        <p class="text-muted">
                            We've sent the code to <strong><?php echo htmlspecialchars($email); ?></strong>.
                        </p>

                        <?php if (!empty($error)) : ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <form method="POST" action="verify_code.php">
                            <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                            <div class="mb-3">
                                <input type="text" name="code" class="form-control" placeholder="Enter verification code" required>
                            </div>
                            <button type="submit" class="btn btn-success w-100">Verify Code</button>
                        </form>

                        <!-- Resend option -->
                        <p class="mt-4 text-center">
                            Didn't receive the code?
                            <button id="resendBtn" class="btn btn-link" disabled>Resend Code (30s)</button>
                            <div id="resendStatus" class="text-muted small mt-2"></div>
                        </p>

                       <script>
    const resendBtn = document.getElementById('resendBtn');
    const resendStatus = document.getElementById('resendStatus');
    const email = "<?php echo htmlspecialchars($email); ?>";

    const startCountdown = () => {
        let countdown = 30;
        resendBtn.disabled = true;
        resendBtn.textContent = `Resend Code (${countdown}s)`;
        const interval = setInterval(() => {
            countdown--;
            resendBtn.textContent = `Resend Code (${countdown}s)`;
            if (countdown <= 0) {
                clearInterval(interval);
                resendBtn.disabled = false;
                resendBtn.textContent = "Resend Code";
            }
        }, 1000);
    };

    resendBtn.addEventListener('click', async () => {
        resendBtn.disabled = true;
        resendBtn.textContent = "Resending...";
        resendStatus.textContent = "";

        try {
            const response = await fetch("send_reset_code.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: `email=${encodeURIComponent(email)}`
            });

            const result = await response.json(); // ✅ Don't forget this

            if (result.success) {
                resendStatus.textContent = "A new code was sent to your email.";
                startCountdown(); // ✅ Restart cooldown
            } else {
                resendStatus.textContent = result.message || "Failed to resend code.";
                resendBtn.disabled = false;
                resendBtn.textContent = "Resend Code";
            }
        } catch (error) {
            resendStatus.textContent = "An error occurred. Please try again.";
            resendBtn.disabled = false;
            resendBtn.textContent = "Resend Code";
        }
    });

    // Start cooldown immediately on page load
    document.addEventListener("DOMContentLoaded", startCountdown);
</script>

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
    </script>
</body>
</html>
