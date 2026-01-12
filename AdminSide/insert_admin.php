<?php
session_start();
header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Include database connection
include_once '../Includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $display_name = trim($_POST['display_name'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $role = trim($_POST['role'] ?? 'Admin');

    // Set phone to 'N/A' if empty
    if (empty($phone)) {
        $phone = 'N/A';
    }

    // Validation
    if (empty($display_name) || empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit;
    }

    // Check if email already exists
    $check_stmt = $conn->prepare("SELECT id FROM admin_accounts WHERE email = ?");
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already exists']);
        $check_stmt->close();
        exit;
    }
    $check_stmt->close();

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Start transaction
    $conn->begin_transaction();

    try {
        // Insert into admin_accounts with 'active' status
        $stmt1 = $conn->prepare("INSERT INTO admin_accounts (email, password, status) VALUES (?, ?, 'active')");
        $stmt1->bind_param("ss", $email, $hashed_password);
        
        if (!$stmt1->execute()) {
            throw new Exception('Failed to create admin account');
        }
        
        $admin_id = $conn->insert_id;
        $stmt1->close();

        // Insert into admin_profiles
        $default_pic = '../assets/Default Image.jpg';
        $stmt2 = $conn->prepare("INSERT INTO admin_profiles (admin_id, display_name, first_name, last_name, phone, role, profile_pic) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt2->bind_param("issssss", $admin_id, $display_name, $first_name, $last_name, $phone, $role, $default_pic);
        
        if (!$stmt2->execute()) {
            throw new Exception('Failed to create admin profile');
        }
        $stmt2->close();

        // Commit transaction
        $conn->commit();
        
        echo json_encode(['success' => true, 'message' => 'Admin account created successfully']);
        
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
