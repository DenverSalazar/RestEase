<?php
include_once '../Includes/db.php';
header('Content-Type: application/json');
$sql = "SELECT cr.*, u.first_name, u.last_name, u.email, u.profile_picture FROM client_requests cr JOIN users u ON cr.user_id = u.id ORDER BY cr.created_at DESC";
$result = $conn->query($sql);
$data = [];
if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $data[] = $row;
  }
}
echo json_encode($data);
?>
