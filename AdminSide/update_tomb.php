<?php
require_once('../db_config.php');

// Get the raw POST data
$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

// Validate the data
if (!$data || !isset($data['tomb_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data received']);
    exit;
}

try {
    // Combine first and last name
    $dname = trim($data['first_name'] . ' ' . $data['last_name']);
    
    // Prepare the SQL statement
    $sql = "UPDATE niches SET 
            niche_no = :tomb_id,
            section = :section,
            row = :row,
            column = :column,
            status = :status,
            occupancy = :occupancy,
            dname = :dname,
            birth_date = :birth_date,
            death_date = :death_date
            WHERE niche_no = :tomb_id";
    
    $stmt = $pdo->prepare($sql);
    
    // Execute the statement with the data
    $success = $stmt->execute([
        'tomb_id' => $data['tomb_id'],
        'section' => $data['section'],
        'row' => $data['row'],
        'column' => $data['column'],
        'status' => $data['status'],
        'occupancy' => $data['occupancy'],
        'dname' => $dname,
        'birth_date' => $data['birth_date'] ?: null,
        'death_date' => $data['death_date'] ?: null
    ]);
    
    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update the tomb']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 