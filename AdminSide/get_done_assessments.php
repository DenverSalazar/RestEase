<?php
include_once '../Includes/db.php';
header('Content-Type: application/json');
$sql = "SELECT * FROM assessment ORDER BY created_at DESC";
$result = $conn->query($sql);
$data = [];
if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $data[] = $row;
  }
}
echo json_encode($data);
?>
