<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['archive_client_email'])) {
    $response = ['status' => 'error', 'message' => ''];
    
    try {
        $email = $_POST['archive_client_email'];
        include_once '../Includes/db.php';
        
        // Start transaction
        $conn->begin_transaction();

        // Get user data and id (including password)
        $checkUser = $conn->prepare("SELECT id, first_name, last_name, email, contact_no, password FROM users WHERE email = ?");
        if (!$checkUser) {
            throw new Exception("Failed to prepare user check query");
        }

        $checkUser->bind_param("s", $email);
        $checkUser->execute();
        $userResult = $checkUser->get_result();
        
        if ($userResult->num_rows === 0) {
            throw new Exception("User not found");
        }

        $userData = $userResult->fetch_assoc();
        $userId = $userData['id'];
        
        // Delete all client_requests for this user
        $delReq = $conn->prepare('DELETE FROM client_requests WHERE user_id = ?');
        $delReq->bind_param('i', $userId);
        $delReq->execute();
        $delReq->close();
        
        // Insert into archive_clients (include password)
        $insertArchive = $conn->prepare("INSERT INTO archive_clients (first_name, last_name, email, contact_no, password, archived_at) VALUES (?, ?, ?, ?, ?, NOW())");
        if (!$insertArchive) {
            throw new Exception("Failed to prepare archive insert query");
        }

        $insertArchive->bind_param("sssss", 
            $userData['first_name'],
            $userData['last_name'],
            $userData['email'],
            $userData['contact_no'],
            $userData['password']
        );

        if (!$insertArchive->execute()) {
            throw new Exception("Failed to insert into archive");
        }

        // Delete from users
        $deleteUser = $conn->prepare("DELETE FROM users WHERE email = ?");
        if (!$deleteUser) {
            throw new Exception("Failed to prepare delete query");
        }

        $deleteUser->bind_param("s", $email);
        if (!$deleteUser->execute()) {
            throw new Exception("Failed to delete user");
        }

        // If we got here, everything worked
        $conn->commit();
        $response = [
            'status' => 'success',
            'message' => 'Client successfully archived'
        ];

    } catch (Exception $e) {
        if (isset($conn)) {
            $conn->rollback();
        }
        $response = [
            'status' => 'error',
            'message' => $e->getMessage()
        ];
    } finally {
        // Clean up
        if (isset($checkUser)) $checkUser->close();
        if (isset($insertArchive)) $insertArchive->close();
        if (isset($deleteUser)) $deleteUser->close();
        if (isset($conn)) $conn->close();
    }

    echo json_encode($response);
    exit;
}
?>