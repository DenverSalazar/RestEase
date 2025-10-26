<?php
include_once '../Includes/db.php';
header('Content-Type: application/json');
$sql = "SELECT * FROM assessment ORDER BY created_at DESC";
$result = $conn->query($sql);
$data = [];
if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    // normalize niche_id for JSON: do not expose numeric 0
    if (isset($row['niche_id']) && ($row['niche_id'] === '0' || $row['niche_id'] === 0)) {
      $row['niche_id'] = '';
    }
    // also normalize current_niche_id if present
    if (isset($row['current_niche_id']) && ($row['current_niche_id'] === '0' || $row['current_niche_id'] === 0)) {
      $row['current_niche_id'] = '';
    }
    $data[] = $row;
  }
}
echo json_encode($data);
?>
