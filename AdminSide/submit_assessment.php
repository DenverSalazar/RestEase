<?php
// submit_assessment.php
header('Content-Type: application/json');
include_once '../Includes/db.php';

// Add PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../vendor/autoload.php';
// Add TCPDF
use TCPDF;

// Get POST data
$request_id = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;
$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
$total_fee = isset($_POST['total_fee']) ? floatval($_POST['total_fee']) : 0;

if (!$request_id || !$user_id || !$total_fee) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit;
}

// Save assessment to database
$type = isset($_POST['type']) ? $_POST['type'] : '';
$informant_name = isset($_POST['informant_name']) ? $_POST['informant_name'] : '';
$email = isset($_POST['email']) ? $_POST['email'] : '';
$deceased_name = isset($_POST['deceased_name']) ? $_POST['deceased_name'] : '';
$residency = isset($_POST['residency']) ? $_POST['residency'] : '';
$dob = isset($_POST['dob']) ? $_POST['dob'] : null;
$dod = isset($_POST['dod']) ? $_POST['dod'] : null;
$internment_date = isset($_POST['internment_date']) && $_POST['internment_date'] !== '' ? $_POST['internment_date'] : null;
$age = isset($_POST['age']) ? $_POST['age'] : '';
// Normalize niche_id: treat empty or '0' as NULL to avoid storing 0
$niche_id = isset($_POST['niche_id']) ? trim($_POST['niche_id']) : null;
if ($niche_id === '' || $niche_id === '0') $niche_id = null;
$expiration = isset($_POST['expiration']) ? $_POST['expiration'] : '';
$renewal_fee = isset($_POST['renewal_fee']) ? floatval($_POST['renewal_fee']) : 0.0;
$total_fee = isset($_POST['total_fee']) ? floatval($_POST['total_fee']) : 0.0;

// updated INSERT: include internment_date column and placeholder (same order as below)
$stmt = $conn->prepare("INSERT INTO assessment 
(request_id, user_id, type, informant_name, email, deceased_name, residency, dob, dod, internment_date, age, niche_id, total_fee, expiration, renewal_fee, created_at) 
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

if (!$stmt) {
    echo json_encode(['success'=>false,'message'=>'DB prepare error: '.$conn->error]);
    exit;
}

// Bind types: request_id (i), user_id (i), then strings..., niche_id is string (s) or NULL, total_fee (d), expiration (s), renewal_fee (d)
$stmt->bind_param(
    'iissssssssssdsd',
    $request_id,
    $user_id,
    $type,
    $informant_name,
    $email,
    $deceased_name,
    $residency,
    $dob,
    $dod,
    $internment_date,
    $age,
    $niche_id,
    $total_fee,
    $expiration,
    $renewal_fee
);

$stmt->execute();
$stmt->close();

// Insert notification for the user
$notif_message = "Your assessment of fees is ready. Total fee: ₱ " . number_format($total_fee, 2);
$notif_link = "clientbilling.php?request_id=$request_id"; // Adjust link as needed

$stmt = $conn->prepare("INSERT INTO notifications (user_id, message, link, is_read, created_at) VALUES (?, ?, ?, 0, NOW())");
$stmt->bind_param('iss', $user_id, $notif_message, $notif_link);
$success = $stmt->execute();
$stmt->close();

if ($success) {
    // Fetch user's email
    $stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->bind_result($user_email);
    $stmt->fetch();
    $stmt->close();

    if ($user_email) {
        // Generate PDF with assessment details
        $pdf = new TCPDF();
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 12);
        $html = "
            <h2>Assessment of Fees</h2>
            <p>Hello,</p>
            <p>Your assessment of fees is ready.</p>
            <p><b>Total fee: ₱ " . number_format($total_fee, 2) . "</b></p>
            <p>You may view the details online or see the attached PDF.</p>
            <br>
            <p>Thanks,<br>RestEase Team</p>
        ";
        $pdf->writeHTML($html, true, false, true, false, '');
        // Save PDF to a temp file
        $pdf_path = sys_get_temp_dir() . "/assessment_" . uniqid() . ".pdf";
        $pdf->Output($pdf_path, 'F');

        // Send email using PHPMailer
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
            $mail->addAddress($user_email);                    // Recipient's email
            $mail->isHTML(true);
            $mail->Subject = 'RestEase Assessment of Fees';
            $mail->Body    = "Hello,<br><br>Your assessment of fees is ready.<br>Total fee: <b>₱ " . number_format($total_fee, 2) . "</b><br><br>You may view the details <a href='http://{$_SERVER['HTTP_HOST']}/RestEase/$notif_link'>here</a>.<br><br>See attached PDF for details.<br><br>Thanks,<br>RestEase Team";

            // Attach the PDF
            $mail->addAttachment($pdf_path, 'Assessment_of_Fees.pdf');

            $mail->send();

            // Delete temp PDF file
            unlink($pdf_path);

            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            // Delete temp PDF file if exists
            if (file_exists($pdf_path)) unlink($pdf_path);
            echo json_encode(['success' => false, 'message' => 'Mailer Error: ' . $mail->ErrorInfo]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'User email not found.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to notify user.']);
}
?>
