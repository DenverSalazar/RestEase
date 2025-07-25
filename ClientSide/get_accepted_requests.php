<?php
header('Content-Type: application/json');

include_once '../Includes/db.php';

// Get server host/IP dynamically
$server_host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_ADDR'] ?? 'localhost');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'] ?? null;
    if (!$user_id) {
        echo json_encode(['success' => false, 'requests' => []]);
        exit;
    }

    $stmt = $conn->prepare("SELECT id, type, first_name, middle_name, last_name, age, dob, dod, residency, informant_name, file_upload, created_at, niche_id
    FROM accepted_request
    WHERE user_id = ?
    ORDER BY created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $requests = [];
    while ($row = $result->fetch_assoc()) {
        if (!empty($row['file_upload'])) {
            $row['file_upload_url'] = 'http://' . $server_host . '/RestEase/uploads/' . $row['file_upload'];
        } else {
            $row['file_upload_url'] = '';
        }
        $requests[] = $row;
    }
    echo json_encode(['success' => true, 'requests' => $requests]);
    $stmt->close();
    $conn->close();
    exit;
}
echo json_encode(['success' => false, 'requests' => []]);
exit;
?>