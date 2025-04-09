<?php
require_once('../db_config.php');

// Get the raw POST data
$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

// Validate the data
if (!$data || !isset($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data received']);
    exit;
}

try {
    // Combine the name parts into dname
    $dname = trim($data['firstName'] . ' ' . $data['middleName'] . ' ' . $data['lastName']);
    
    // Prepare the SQL statement
    $sql = "UPDATE niches SET 
            niche_no = :niche_no,
            section = :section,
            dname = :dname,
            death_date = :death_date,
            status = :status,
            occupancy = :occupancy
            WHERE id = :id";
    
    $stmt = $pdo->prepare($sql);
    
    // Execute the statement with the data
    $success = $stmt->execute([
        'id' => $data['id'],
        'niche_no' => $data['niche_no'],
        'section' => $data['section'],
        'dname' => $dname,
        'death_date' => $data['deathDate'],
        'status' => $data['status'],
        'occupancy' => $data['occupancy']
    ]);
    
    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update the niche']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 