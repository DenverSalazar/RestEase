<?php
header('Content-Type: application/json');
include_once '../Includes/db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid request ID.']);
    exit;
}

$sql = "SELECT ar.*, u.first_name AS user_first_name, u.last_name AS user_last_name, u.email FROM accepted_request ar JOIN users u ON ar.user_id = u.id WHERE ar.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Accepted request not found.']);
    exit;
}
$row = $result->fetch_assoc();
$name = htmlspecialchars($row['user_first_name'] . ' ' . $row['user_last_name']);
$email = htmlspecialchars($row['email']);
$type = htmlspecialchars($row['type']);
$age = htmlspecialchars($row['age']);
$informant_name = htmlspecialchars($row['informant_name']);
$deceased_name = htmlspecialchars($row['first_name'] . ' ' . $row['last_name']);
$attachment_html = '';
if (!empty($row['file_upload'])) {
    $file = '../uploads/' . $row['file_upload'];
    $filename = htmlspecialchars($row['file_upload']);
    $attachment_html = "<div class='attachment-box'><a href='$file' target='_blank'><img src='https://cdn.jsdelivr.net/gh/edent/SuperTinyIcons/images/svg/pdf.svg' alt='PDF' style='height:20px;vertical-align:middle;margin-right:6px;'><span style='color:#2563eb;text-decoration:underline;cursor:pointer;'>$filename</span></a></div>";
}
echo json_encode([
    'success' => true,
    'name' => $name,
    'email' => $email,
    'type' => $type,
    'age' => $age,
    'informant_name' => $informant_name,
    'deceased_name' => $deceased_name,
    'attachment_html' => $attachment_html
]); 