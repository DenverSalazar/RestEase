<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['disable_client_email'])) {
    $response = ['status' => 'error', 'message' => ''];
    
    try {
        $email = $_POST['disable_client_email'];
        $action = $_POST['action'] ?? 'disable';
        include_once '../Includes/db.php';
        
        if ($conn->connect_error) {
            throw new Exception("Database connection failed: " . $conn->connect_error);
        }
        
        // First check current status
        $checkStatus = $conn->prepare("SELECT status FROM users WHERE email = ?");
        $checkStatus->bind_param("s", $email);
        $checkStatus->execute();
        $result = $checkStatus->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("User not found");
        }
        
        $currentStatus = $result->fetch_assoc()['status'] ?? 'active';
        $checkStatus->close();
        
        // Determine the new status based on action
        $newStatus = ($action === 'enable') ? 'active' : 'disabled';
        
        // Check if status is already what we want
        if ($currentStatus === $newStatus) {
            $actionText = ($action === 'enable') ? 'enabled' : 'disabled';
            $response = [
                'status' => 'success',
                'message' => "Client is already $actionText"
            ];
        } else {
            // Update user status
            $updateUser = $conn->prepare("UPDATE users SET status = ? WHERE email = ?");
            if (!$updateUser) {
                throw new Exception("Failed to prepare update query: " . $conn->error);
            }

            $updateUser->bind_param("ss", $newStatus, $email);
            
            if (!$updateUser->execute()) {
                throw new Exception("Failed to update user status: " . $updateUser->error);
            }

            $actionText = ($action === 'enable') ? 'enabled' : 'disabled';
            $response = [
                'status' => 'success',
                'message' => "Client successfully $actionText"
            ];
            
            $updateUser->close();
        }

    } catch (Exception $e) {
        $response = [
            'status' => 'error',
            'message' => $e->getMessage()
        ];
    } finally {
        if (isset($conn)) $conn->close();
    }

    echo json_encode($response);
    exit;
}
?>


