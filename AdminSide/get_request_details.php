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
    // Build deceased name as First Middle Last Suffix
    $deceased = htmlspecialchars(trim(
        $row['first_name'] .
        (isset($row['middle_name']) && $row['middle_name'] ? ' ' . $row['middle_name'] : '') .
        (isset($row['last_name']) && $row['last_name'] ? ' ' . $row['last_name'] : '') .
        (isset($row['suffix']) && $row['suffix'] && $row['suffix'] !== '0' ? ' ' . $row['suffix'] : '')
    ));
    $attachment_html = 'No attachment';
    if (!empty($row['file_upload'])) {
        $file = '../uploads/' . $row['file_upload'];
        $filename = htmlspecialchars($row['file_upload']);
        $attachment_html = '<div class="attachment-box"><a href="' . $file . '" target="_blank"><img src="https://cdn.jsdelivr.net/gh/edent/SuperTinyIcons/images/svg/pdf.svg" alt="PDF" style="height:20px;vertical-align:middle;margin-right:6px;"><span style="color:#2563eb;text-decoration:underline;cursor:pointer;">' . $filename . '</span></a></div>';
    }
    $niche_id = isset($row['niche_id']) ? htmlspecialchars($row['niche_id']) : '';
    $middle_name = isset($row['middle_name']) ? htmlspecialchars($row['middle_name']) : '';
    $suffix = isset($row['suffix']) ? htmlspecialchars($row['suffix']) : '';
    $residency = isset($row['residency']) ? htmlspecialchars($row['residency']) : '';
    $dob = isset($row['dob']) ? htmlspecialchars($row['dob']) : '';
    $dod = isset($row['dod']) ? htmlspecialchars($row['dod']) : '';
    $internment_date = '';
    if (isset($row['internment_date'])) {
        $internment_date = htmlspecialchars($row['internment_date']);
    } elseif (isset($row['date_of_internment'])) {
        $internment_date = htmlspecialchars($row['date_of_internment']);
    } elseif (isset($row['dateInternment'])) {
        $internment_date = htmlspecialchars($row['dateInternment']);
    }
    echo json_encode([
        'success' => true,
        'id' => $row['id'],
        'user_id' => $row['user_id'],
        'name' => $name,
        'email' => $email,
        'type' => $type,
        'age' => $age,
        'informant_name' => $informant,
        'deceased_name' => $deceased,
        'attachment_html' => $attachment_html,
        'niche_id' => $niche_id,
        'middle_name' => $middle_name,
        'suffix' => $suffix,
        'residency' => $residency,
        'dob' => $dob,
        'dod' => $dod,
        'internment_date' => $internment_date,
        'current_niche_id' => isset($row['current_niche_id']) ? htmlspecialchars($row['current_niche_id']) : '',
        'new_niche_id' => isset($row['new_niche_id']) ? htmlspecialchars($row['new_niche_id']) : ''
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Request not found']);
}
$stmt->close();