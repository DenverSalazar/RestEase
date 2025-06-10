<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['archive_client_email'])) {
    $response = ['status' => 'error', 'message' => ''];
    
    try {
        $email = $_POST['archive_client_email'];
        $conn = new mysqli("localhost", "root", "", "cemeterydb");
        
        if ($conn->connect_error) {
            throw new Exception("Database connection failed");
        }

        // Start transaction
        $conn->begin_transaction();

        // Get user data
        $checkUser = $conn->prepare("SELECT first_name, last_name, email, contact_no FROM users WHERE email = ?");
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
        
        // Insert into archive_clients
        $insertArchive = $conn->prepare("INSERT INTO archive_clients (first_name, last_name, email, contact_no, archived_at) VALUES (?, ?, ?, ?, NOW())");
        if (!$insertArchive) {
            throw new Exception("Failed to prepare archive insert query");
        }

        $insertArchive->bind_param("ssss", 
            $userData['first_name'],
            $userData['last_name'],
            $userData['email'],
            $userData['contact_no']
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