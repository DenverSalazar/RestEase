<?php //dapat hindi ito makita ng kahit sino kasi may mahlaagang information
header('Content-Type: application/json');
include_once '../includes/db.php'; // Adjust path if needed

// Only allow GET requests for search
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // You can add search parameters here if you want to filter on the server side
    // For now, return all niches with a name

    $stmt = $conn->prepare("SELECT nicheID, CONCAT(firstName, ' ', lastName) AS Name 
                            FROM deceased 
                            WHERE firstName IS NOT NULL AND firstName != '' 
                              AND lastName IS NOT NULL AND lastName != ''");
    $stmt->execute();
    $result = $stmt->get_result();

    $niches = [];
    while ($row = $result->fetch_assoc()) {
        $niches[] = $row;
    }
    echo json_encode(['niches' => $niches]);
    $stmt->close();
    $conn->close();
    exit;
}
echo json_encode(['niches' => []]);
exit;
?>