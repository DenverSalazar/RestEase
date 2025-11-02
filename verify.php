<?php
include_once 'Includes/db.php';

$verified = false;
$error = "";

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    // Find pending user by token
    $stmt = $conn->prepare("SELECT * FROM pending_users WHERE verification_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        // Move user to users table
        $stmt2 = $conn->prepare("INSERT INTO users (first_name, last_name, email, contact_no, password) VALUES (?, ?, ?, ?, ?)");
        $stmt2->bind_param("sssss", $row['first_name'], $row['last_name'], $row['email'], $row['contact_no'], $row['password']);
        if ($stmt2->execute()) {
            $verified = true;
            // Delete from pending_users
            $stmt3 = $conn->prepare("DELETE FROM pending_users WHERE email = ?");
            $stmt3->bind_param("s", $row['email']);
            $stmt3->execute();
        } else {
            $error = "Registration failed. Please try again.";
        }
    } else {
        $error = "Invalid or expired verification link.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>RestEase Email Verification</title>
     <link rel="icon" type="image/png" href="./assets/re logo blue.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', Arial, sans-serif;
            background: #fff;
        }
        .verify-container {
            max-width: 520px;
            margin: 60px auto 0 auto;
            text-align: center;
        }
        .verify-logo {
            margin-bottom: 16px;
        }
        .verify-logo img {
            height: 48px;
        }
        .verify-check {
            font-size: 3.5rem;
            color: #38d39f;
            margin-bottom: 18px;
        }
        .verify-message {
            font-size: 1.25rem;
            font-weight: 600;
            color: #222;
            margin-bottom: 24px;
        }
        .verify-spinner {
            margin: 18px auto 8px auto;
            width: 40px;
            height: 40px;
            border: 4px solid #eee;
            border-top: 4px solid #38d39f;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .verify-redirect-msg {
            color: #444;
            font-size: 1.05rem;
            margin-bottom: 8px;
        }
        .verify-error {
            color: #e74c3c;
            font-weight: 700;
            font-size: 1.35rem;
            margin-top: 32px;
            padding: 18px 0;
            border-radius: 12px;
            background: linear-gradient(90deg, #ffe5e5 0%, #fff 100%);
            box-shadow: 0 2px 12px rgba(231,76,60,0.08);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            animation: shake 0.4s;
        }
        .verify-error-icon {
            font-size: 2.2rem;
            color: #e74c3c;
            margin-right: 8px;
        }
        @keyframes shake {
            0% { transform: translateX(0); }
            25% { transform: translateX(-6px); }
            50% { transform: translateX(6px); }
            75% { transform: translateX(-6px); }
            100% { transform: translateX(0); }
        }
    </style>
</head>
<body>
    <div class="verify-container">
        <?php if ($verified): ?>
            <div class="verify-check">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="verify-message">
                Your account has been verified. You will be automatically redirected to Home page.
            </div>
            <div class="verify-spinner"></div>
            <div class="verify-redirect-msg">
                You will be redirected in <span id="redirect-secs">5</span> secs.
            </div>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js"></script>
            <script>
                let secs = 5;
                let span = document.getElementById('redirect-secs');
                let interval = setInterval(function() {
                    secs--;
                    if (secs <= 0) {
                        clearInterval(interval);
                        if (window.opener && !window.opener.closed) {
                            window.opener.location.href = "login.php";
                            window.close();
                        } else {
                            window.location.href = "login.php";
                        }
                    } else {
                        span.textContent = secs;
                    }
                }, 1000);
            </script>
        <?php else: ?>
            <div class="verify-error">
                <span class="verify-error-icon"><i class="fas fa-times-circle"></i></span>
                Verification Failed
            </div>
            <div class="verify-redirect-msg">
                <?php echo htmlspecialchars($error); ?>
                <br>
                You will be redirected in <span id="error-redirect-secs">5</span> secs.
            </div>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js"></script>
            <script>
                let errorSecs = 5;
                let errorSpan = document.getElementById('error-redirect-secs');
                let errorInterval = setInterval(function() {
                    errorSecs--;
                    if (errorSecs <= 0) {
                        clearInterval(errorInterval);
                        if (window.opener && !window.opener.closed) {
                            window.opener.location.href = "register.php";
                            window.close();
                        } else {
                            window.location.href = "register.php";
                        }
                    } else {
                        errorSpan.textContent = errorSecs;
                    }
                }, 1000);
            </script>
        <?php endif; ?>
    </div>
</body>
</html>
