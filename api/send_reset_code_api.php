<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';      // Adjust path as needed
require '../Includes/db.php';          // Adjust path as needed

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Get the email from POST data
// Decode JSON input
$input = json_decode(file_get_contents("php://input"), true);
$email = $input['email'] ?? null;

if ($_SERVER["REQUEST_METHOD"] !== "POST" || !$email) {
    echo json_encode(['success' => false, 'message' => 'Invalid request or missing email.']);
    exit;
}

// Step 1: Check if the user exists
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Email not found.']);
    exit;
}

// Step 2: Generate reset code and expiration
$code = rand(100000, 999999);
$expires = date("Y-m-d H:i:s", strtotime("+10 minutes"));

// Step 3: Store the reset code and expiration in the database
$update = $conn->prepare("UPDATE users SET reset_code = ?, reset_expires = ? WHERE email = ?");
$update->bind_param("sss", $code, $expires, $email);
$update->execute();
$update->close();

// Step 4: Send email with reset code
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'lourenzangelfrancisco@gmail.com';
    $mail->Password   = 'lbtyxpmubmrpovix';  // App password
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;

    $mail->setFrom('lourenzangelfrancisco@gmail.com', 'RestEase');
    $mail->addAddress($email);
    $mail->isHTML(true);
    $mail->Subject = 'RestEase Password Reset Code';
    $mail->Body    = "Hello,<br><br>Your password reset code is: <b>$code</b><br>This code will expire in 10 minutes.<br><br>If you did not request this, simply ignore this message.<br><br>Thanks,<br>RestEase Team";

    $mail->send();

    echo json_encode([
        'success' => true,
        'message' => 'Reset code sent to your email.',
        'email' => $email,
        'code_sent' => true
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Mailer Error: ' . $mail->ErrorInfo]);
}
