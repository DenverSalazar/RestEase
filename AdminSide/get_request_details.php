<?php
header('Content-Type: application/json');
include_once '../Includes/db.php';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) {
    echo json_encode(['success' => false, 'error' => 'Invalid ID']);
    exit;
}
$sql = "SELECT cr.*, u.first_name AS user_first, u.last_name AS user_last, u.email FROM client_requests cr JOIN users u ON cr.user_id = u.id WHERE cr.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $name = htmlspecialchars($row['user_first'] . ' ' . $row['user_last']);
    $email = htmlspecialchars($row['email']);
    $type = htmlspecialchars($row['type']);
    $age = htmlspecialchars($row['age']);
    $informant = htmlspecialchars($row['informant_name']);
    $deceased = htmlspecialchars($row['first_name'] . ' ' . $row['last_name']);
    $attachment_html = 'No attachment';
    if (!empty($row['file_upload'])) {
        $file = '../uploads/' . $row['file_upload'];
        $filename = htmlspecialchars($row['file_upload']);
        $attachment_html = '<div class="attachment-box"><a href="' . $file . '" target="_blank"><img src="https://cdn.jsdelivr.net/gh/edent/SuperTinyIcons/images/svg/pdf.svg" alt="PDF" style="height:20px;vertical-align:middle;margin-right:6px;"></a><span style="color:#888;">' . $filename . '</span></div>';
    }
    echo json_encode([
        'success' => true,
        'name' => $name,
        'email' => $email,
        'type' => $type,
        'age' => $age,
        'informant_name' => $informant,
        'deceased_name' => $deceased,
        'attachment_html' => $attachment_html
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Request not found']);
}
$stmt->close(); 