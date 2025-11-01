<?php
require 'Includes/db.php';
session_start();

if (!isset($_SESSION['reset_email'])) {
    die("Unauthorized access.");
}

// Add validation containers
$error_messages = [];
$input_classes = [
    'password' => '',
    'confirm_password' => ''
];

// new: error toast vars
$error_toast_message = '';
$show_error_toast = false;

$success = '';
$show_toast = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    $email    = $_SESSION['reset_email'];

    // Validate password rules
    if (strlen($password) < 8) {
        $error_messages['password'] = "Password must be at least 8 characters.";
        $input_classes['password'] = 'is-invalid';
    } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
        $error_messages['password'] = "Password must contain both letters and numbers.";
        $input_classes['password'] = 'is-invalid';
    }

    // Confirm password match
    if ($password !== $confirm) {
        $error_messages['confirm_password'] = "Passwords do not match.";
        $input_classes['confirm_password'] = 'is-invalid';
    }

    // If there are validation errors, prepare error toast (show priority: password -> confirm)
    if (!empty($error_messages)) {
        if (!empty($error_messages['password'])) {
            $error_toast_message = $error_messages['password'];
        } else {
            $error_toast_message = reset($error_messages);
        }
        $show_error_toast = true;
    }

    // Only update when there are no validation errors
    if (empty($error_messages)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ?, reset_code = NULL, reset_expires = NULL WHERE email = ?");
        $stmt->bind_param("ss", $hashed, $email);
        $stmt->execute();
        $stmt->close();

        session_destroy();
        $success = "Your password has been successfully reset.";
        $show_toast = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="./assets/re logo blue.png">
    <title>RestEase - Create Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
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

        /* Custom Toast */
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
        .custom-toast.success .toast-icon {
            font-size: 2rem;
            margin-right: 1rem;
            color: #38d39f;
        }
        .custom-toast.error {
            border-left: 6px solid #ff6b6b;
        }
        .custom-toast.error .toast-icon {
            font-size: 2rem;
            margin-right: 1rem;
            color: #ff6b6b;
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
        .input-with-icon {
            position: relative;
        }
        .input-with-icon input.form-control {
            padding-right: 2.6rem; /* space for eye icon inside the input */
        }
        .input-with-icon .toggle-eye {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            cursor: pointer;
            font-size: 1.05rem;
            z-index: 2;
        }
        .input-with-icon .toggle-eye.fa-eye-slash {
            color: #2c3e50;
        }

        /* Show placeholder in red when input is invalid (keeps layout/icon position) */
        .form-control.is-invalid::placeholder {
            color: #dc3545 !important;
            opacity: 1; /* ensure visibility across browsers */
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
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light">
    <div class="container">
        <a class="navbar-brand" href="#">
            <img src="assets/RE Logo New.png" alt="Logo">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
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

<div class="main-content">
    <div class="login-container">
        <div class="row g-0">
            <div class="col-md-6 left-side">
                <div class="content-overlay">
                    <h1>Welcome to<br>RestEase</h1>
                    <p>Log in to your RestEase account to seamlessly handle cemetery records, manage certificates, and streamline renewal processes with ease.</p>
                </div>
            </div>

            <div class="col-md-6 right-side">
                <div class="login-form">
                    <h2>Create New Password</h2>
                    <p class="text-muted">Password must be at least 8 characters and contain both letters and numbers.</p>

                    <form method="POST" action="">
                        <!-- Password -->
                        <div class="mb-3 position-relative input-with-icon">
                            <input type="password" name="password" id="password" class="form-control <?= $input_classes['password'] ?>" placeholder="New Password" required>
                            <i class="fas fa-eye toggle-eye" data-target="password" aria-hidden="true"></i>
                        </div>

                        <!-- Confirm Password -->
                        <div class="mb-3 position-relative input-with-icon">
                            <input type="password" name="confirm_password" id="confirmPassword" class="form-control <?= $input_classes['confirm_password'] ?>" placeholder="Confirm Password" required>
                            <i class="fas fa-eye toggle-eye" data-target="confirmPassword" aria-hidden="true"></i>
                        </div>

                        <button type="submit" class="btn btn-success w-100">Reset Password</button>
                        <div class="text-center mt-3">
                            <a  href="login.php" class="btn btn-link" style="color:#506C84;font-size:1.08rem;font-weight:500; text-decoration:none;cursor:pointer;transition:color 0.18s;">← Back to Login</a>
                        </div>
                    </form>

                </div>
            </div>

        </div>
    </div>
</div>

<!-- Custom Toast (success) -->
<?php if (!empty($show_toast)) : ?>
    <div id="customToast" class="custom-toast success">
        <div class="toast-icon"><i class="fas fa-check-circle"></i></div>
        <div class="toast-message"><?php echo $success; ?> Redirecting to login...</div>
        <div class="toast-close" onclick="closeToast()">&times;</div>
    </div>
    <script>
        function closeToast() {
            document.getElementById('customToast').style.opacity = '0';
            setTimeout(function() {
                document.getElementById('customToast').style.display = 'none';
            }, 300);
        }

        document.addEventListener('DOMContentLoaded', function() {
            var toast = document.getElementById('customToast');
            toast.style.opacity = '1';
            setTimeout(closeToast, 4000);
            setTimeout(function () {
                window.location.href = 'login.php';
            }, 3500);
        });
    </script>
<?php endif; ?>

<!-- Error Toast -->
<?php if (!empty($show_error_toast)) : ?>
    <div id="errorToast" class="custom-toast error">
        <div class="toast-icon"><i class="fas fa-exclamation-circle"></i></div>
        <div class="toast-message"><?= htmlspecialchars($error_toast_message, ENT_QUOTES) ?></div>
        <div class="toast-close" onclick="closeErrorToast()">&times;</div>
    </div>
    <script>
        function closeErrorToast() {
            var t = document.getElementById('errorToast');
            if (!t) return;
            t.style.opacity = '0';
            setTimeout(function() { if (t) t.style.display = 'none'; }, 300);
        }
        document.addEventListener('DOMContentLoaded', function() {
            var t = document.getElementById('errorToast');
            if (!t) return;
            t.style.opacity = '1';
            setTimeout(closeErrorToast, 4000);
        });
    </script>
<?php endif; ?>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Generic toggle for inline eye icons (works for both password fields)
    document.querySelectorAll('.toggle-eye').forEach(function(el){
        el.addEventListener('click', function () {
            var targetId = this.getAttribute('data-target');
            var input = document.getElementById(targetId);
            if (!input) return;
            input.type = input.type === 'password' ? 'text' : 'password';
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    });
</script>

<!-- Toast Styles -->
<style>
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
    .custom-toast.success .toast-icon {
        font-size: 2rem;
        margin-right: 1rem;
        color: #38d39f;
    }
    .custom-toast.error {
        border-left: 6px solid #ff6b6b;
    }
    .custom-toast.error .toast-icon {
        font-size: 2rem;
        margin-right: 1rem;
        color: #ff6b6b;
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
    .input-with-icon {
        position: relative;
    }
    .input-with-icon input.form-control {
        padding-right: 2.6rem; /* space for eye icon inside the input */
    }
    .input-with-icon .toggle-eye {
        position: absolute;
        right: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
        color: #6c757d;
        cursor: pointer;
        font-size: 1.05rem;
        z-index: 2;
    }
    .input-with-icon .toggle-eye.fa-eye-slash {
        color: #2c3e50;
    }

    /* Show placeholder in red when input is invalid (keeps layout/icon position) */
    .form-control.is-invalid::placeholder {
        color: #dc3545 !important;
        opacity: 1; /* ensure visibility across browsers */
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
</body>
</html>