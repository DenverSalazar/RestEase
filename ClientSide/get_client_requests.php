<?php
header('Content-Type: application/json');
include_once '../Includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'] ?? null;
    if (!$user_id) {
        echo json_encode([]);
        exit;
    }

    $stmt = $conn->prepare("SELECT id, type, first_name, middle_name, last_name, age, dob, dod, residency, informant_name, file_upload, created_at, niche_id
    FROM client_requests
    WHERE user_id = ?
    ORDER BY created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $requests = [];
    while ($row = $result->fetch_assoc()) {
        if (!empty($row['file_upload'])) {
            $row['file_upload_url'] = 'http://192.168.100.27/RestEase/uploads/' . $row['file_upload'];
        } else {
            $row['file_upload_url'] = '';
        }
        $requests[] = $row;
    }
    echo json_encode($requests);
    $stmt->close();
    $conn->close();
    exit;
}
echo json_encode([]);
exit;
?>