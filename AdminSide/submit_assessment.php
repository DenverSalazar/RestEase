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

// Save assessment (optional, you can expand this logic)
// Example: Insert into assessments table
// $stmt = $conn->prepare("INSERT INTO assessments (request_id, user_id, total_fee, created_at) VALUES (?, ?, ?, NOW())");
// $stmt->bind_param('iid', $request_id, $user_id, $total_fee);
// $stmt->execute();
// $stmt->close();

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
            $mail->Username   = 'lourenzangelfrancisco@gmail.com';         // 🔁 Replace with your Gmail address
            $mail->Password   = 'lbtyxpmubmrpovix';    // 🔁 Use your App Password (no spaces)
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            // Email headers
            $mail->setFrom('lourenzangelfrancisco@gmail.com', 'RestEase'); // 🔁 Same as above
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
