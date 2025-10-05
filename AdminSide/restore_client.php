<?php
// restore_client.php
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['email'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request.']);
    exit;
}
$email = $_POST['email'];
$conn = new mysqli('localhost', 'root', '', 'cemeterydb');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed.']);
    exit;
}
$conn->begin_transaction();
try {
    // Fetch client from archive_clients
    $stmt = $conn->prepare('SELECT * FROM archive_clients WHERE email = ? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        // Check if user already exists in users table
        $check = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $check->bind_param('s', $email);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $check->close();
            throw new Exception('User already exists in users table.');
        }
        $check->close();
        // Insert into users table (include password)
        $insert = $conn->prepare('INSERT INTO users (first_name, last_name, email, contact_no, password, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
        $status = 'active';
        $insert->bind_param(
            'ssssss',
            $row['first_name'],
            $row['last_name'],
            $row['email'],
            $row['contact_no'],
            $row['password'],
            $status
        );
        if (!$insert->execute()) {
            throw new Exception('Failed to insert into users.');
        }
        // Delete from archive_clients
        $delete = $conn->prepare('DELETE FROM archive_clients WHERE email = ?');
        $delete->bind_param('s', $email);
        $delete->execute();
        $delete->close();
        $insert->close();
        $stmt->close();
        $conn->commit();
        echo json_encode(['success' => true]);
        exit;
    } else {
        throw new Exception('Client not found in archive.');
    }
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}
?>
