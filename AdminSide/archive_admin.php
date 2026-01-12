<?php
session_start();
header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

// Include database connection
include_once '../Includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admin_id = trim($_POST['archive_admin_id'] ?? '');

    // Validation
    if (empty($admin_id)) {
        echo json_encode(['status' => 'error', 'message' => 'Admin ID is required']);
        exit;
    }

    // Prevent admin from archiving themselves
    if ($admin_id == $_SESSION['admin_id']) {
        echo json_encode(['status' => 'error', 'message' => 'You cannot archive your own account']);
        exit;
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Get admin data before deleting
        $stmt = $conn->prepare("SELECT aa.email, ap.display_name, ap.first_name, ap.last_name, ap.phone 
                                FROM admin_accounts aa 
                                LEFT JOIN admin_profiles ap ON aa.id = ap.admin_id 
                                WHERE aa.id = ?");
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception('Admin account not found');
        }
        
        $admin_data = $result->fetch_assoc();
        $stmt->close();

        // Insert into archive_admin table (create if needed)
        $archive_stmt = $conn->prepare("INSERT INTO archive_admin (email, display_name, first_name, last_name, phone, archived_at) 
                                        VALUES (?, ?, ?, ?, ?, NOW())");
        $archive_stmt->bind_param("sssss", 
            $admin_data['email'],
            $admin_data['display_name'],
            $admin_data['first_name'],
            $admin_data['last_name'],
            $admin_data['phone']
        );
        
        if (!$archive_stmt->execute()) {
            throw new Exception('Failed to archive admin data');
        }
        $archive_stmt->close();

        // Delete from admin_profiles (will cascade due to foreign key)
        $delete_profile = $conn->prepare("DELETE FROM admin_profiles WHERE admin_id = ?");
        $delete_profile->bind_param("i", $admin_id);
        $delete_profile->execute();
        $delete_profile->close();

        // Delete from admin_accounts
        $delete_account = $conn->prepare("DELETE FROM admin_accounts WHERE id = ?");
        $delete_account->bind_param("i", $admin_id);
        
        if (!$delete_account->execute()) {
            throw new Exception('Failed to delete admin account');
        }
        $delete_account->close();

        // Commit transaction
        $conn->commit();
        
        echo json_encode(['status' => 'success', 'message' => 'Admin account archived successfully']);
        
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }

    $conn->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>
