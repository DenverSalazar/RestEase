<?php
header('Content-Type: application/json');

$db_server = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "rest_ease"; // Database name

try {
    $conn = mysqli_connect($db_server, $db_user, $db_pass, $db_name);

    if (!$conn) {
        throw new Exception("Database connection failed: " . mysqli_connect_error());
    }

    $query = "SELECT id, niche_no, section, status, occupancy, informant, dname, validity FROM niches";
    $result = mysqli_query($conn, $query);

    if (!$result) {
        throw new Exception("Query failed: " . mysqli_error($conn));
    }

    $niches = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $niches[] = $row;
    }

    echo json_encode($niches);
} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
} finally {
    if ($conn) {
        mysqli_close($conn);
    }
}
?>
