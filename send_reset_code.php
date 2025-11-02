<?php
session_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';      // PHPMailer
require 'Includes/db.php';          // Your DB connection (mysqli)

$email = $_POST['email'] ?? $_GET['email'] ?? null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST['email'];
    $code = rand(100000, 999999); // Generate a 6-digit code
    $expires = date("Y-m-d H:i:s", strtotime("+10 minutes"));

    // Step 1: Check if the user exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user) {
        // Step 2: Save reset code and expiration time
        $update = $conn->prepare("UPDATE users SET reset_code = ?, reset_expires = ? WHERE email = ?");
        $update->bind_param("sss", $code, $expires, $email);
        $update->execute();
        $update->close();

        // Step 3: Send the email using PHPMailer
        $mail = new PHPMailer(true);
        try {
            // SMTP configuration
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'resteasempdo@gmail.com';         // Updated Gmail address
            $mail->Password   = 'vvkblrlppiflbksu';               // Updated App Password (no spaces)
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            // Email headers
            $mail->setFrom('resteasempdo@gmail.com', 'RestEase'); // Updated sender address
            $mail->addAddress($email);                    // Recipient's email
            $mail->isHTML(true);
            $mail->Subject = 'RestEase Password Reset Code';
            $mail->Body    = "Hello,<br><br>Your password reset code is: <b>$code</b><br>This code will expire in 10 minutes.<br><br>If you did not request this, simply ignore this message.<br><br>Thanks,<br>RestEase Team";

            $mail->send();

            // ✅ Redirect to verify_code.php with email
            header("Location: verify_code.php?email=" . urlencode($email));
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Mailer Error: ' . $mail->ErrorInfo]);
        }
    } else {
        // Set error in session and redirect back
        $_SESSION['error'] = "Email not found.";
        header("Location: forgot.php");
        exit;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
