<?php
header('Content-Type: application/json');
$mysqli = new mysqli("localhost", "root", "", "cemeterydb"); // adjust user/pass as needed

if ($mysqli->connect_errno) {
    echo json_encode(["error" => "Failed to connect to MySQL"]);
    exit();
}

// For demo: get email from GET or POST (replace with session/token in production)
$email = $_GET['email'] ?? $_POST['email'] ?? null;
if (!$email) {
    echo json_encode(["error" => "No email provided"]);
    exit();
}

$stmt = $mysqli->prepare("SELECT first_name, last_name, email, profile_picture FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    // Build full URL for profile picture using current server IP/host
    $server_host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_ADDR'] ?? 'localhost');
    $base_url = "http://$server_host/RestEase/uploads/";
    $row['profile_picture'] = $row['profile_picture'] ? $base_url . $row['profile_picture'] : null;
    echo json_encode($row);
} else {
    echo json_encode(["error" => "User not found"]);
}
$stmt->close();
$mysqli->close();
?>