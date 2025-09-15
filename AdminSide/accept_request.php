<?php
header('Content-Type: application/json');
include_once '../Includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid request ID.']);
    exit;
}

// Fetch the request
$sql = "SELECT * FROM client_requests WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Request not found.']);
    exit;
}
$row = $result->fetch_assoc();

// Calculate age from dob and dod
function calculateAgeFromDates($dob, $dod) {
    if (!$dob || !$dod) return null;
    $birth = new DateTime($dob);
    $death = new DateTime($dod);
    $age = $death->format('Y') - $birth->format('Y');
    if (
        $death->format('m') < $birth->format('m') ||
        ($death->format('m') == $birth->format('m') && $death->format('d') < $birth->format('d'))
    ) {
        $age--;
    }
    return ($age >= 0) ? $age : null;
}
$calculated_age = calculateAgeFromDates($row['dob'], $row['dod']);

// Insert into accepted_request
$insert_sql = "INSERT INTO accepted_request 
(user_id, type, first_name, last_name, middle_name, suffix, age, dob, dod, residency, informant_name, file_upload, created_at, niche_id, current_niche_id, new_niche_id, dateInternment) 
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$insert_stmt = $conn->prepare($insert_sql);
$insert_stmt->bind_param(
    'isssssissssssssss',
    $row['user_id'],       // i
    $row['type'],          // s
    $row['first_name'],    // s
    $row['last_name'],     // s
    $row['middle_name'],   // s
    $row['suffix'],        // s 
    $calculated_age,       // i
    $row['dob'],           // s
    $row['dod'],           // s
    $row['residency'],     // s
    $row['informant_name'],// s
    $row['file_upload'],   // s
    $row['created_at'],    // s
    $row['niche_id'],      // s
    $row['current_niche_id'], // s
    $row['new_niche_id'],     // s
    $row['dateInternment']    // s
);

$success = $insert_stmt->execute();

if (!$success) {
    echo json_encode(['success' => false, 'message' => 'Failed to accept request.']);
    exit;
}

// Delete from client_requests
$delete_sql = "DELETE FROM client_requests WHERE id = ?";
$delete_stmt = $conn->prepare($delete_sql);
$delete_stmt->bind_param('i', $id);
$delete_stmt->execute();

echo json_encode(['success' => true]);