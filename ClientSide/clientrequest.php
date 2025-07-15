<?php
session_start();
include_once '../Includes/db.php';
$success = '';
$error = '';
// Check if this is an API request (e.g., by a custom header or a query param)
$isApi = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $type = $_POST['type'] ?? '';
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $middle_name = $_POST['middle_name'] ?? '';
    $age = $_POST['age'] ?? '';
    $dob = $_POST['dob'] ?? '';
    $dod = $_POST['dod'] ?? '';
    $residency = $_POST['residency'] ?? '';
    $informant_name = $_POST['informant_name'] ?? '';
    $file_upload = '';

    // Handle file upload
    if (isset($_FILES['file_upload']) && $_FILES['file_upload']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "../uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $file_name = time() . '_' . basename($_FILES["file_upload"]["name"]);
        $target_file = $target_dir . $file_name;
        if (move_uploaded_file($_FILES["file_upload"]["tmp_name"], $target_file)) {
            $file_upload = $file_name;
        } else {
            $error = "File upload failed.";
        }
    }

   // Insert into database if no error
$user_id = $_POST['user_id'] ?? ($_SESSION['user_id'] ?? null);
if (!$error) {
    if (!$user_id) {
        $error = "User not logged in.";
    } else {
        $stmt = $conn->prepare("INSERT INTO client_requests (user_id, type, first_name, last_name, middle_name, age, dob, dod, residency, informant_name, file_upload) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssisssss", $user_id, $type, $first_name, $last_name, $middle_name, $age, $dob, $dod, $residency, $informant_name, $file_upload);
        if ($stmt->execute()) {
            $success = "Request submitted successfully!";
        } else {
            $error = "Database error: " . $conn->error;
        }
        $stmt->close();
    }
}

    // If API request, return JSON and exit
    if ($isApi) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            'error' => $error
        ]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RestEase</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/footer.css">
    <link rel="stylesheet" href="../css/clientrequest.css">
</head>
<body>
    <!-- Custom Navbar -->
    <nav class="custom-navbar position-relative">
        <div class="container navbar-top position-relative">
            <a href="#" class="navbar-brand">
                <img src="../assets/RE logo New.png" alt="RestEase Logo" style="height: 32px;">
            </a>
            <button class="navbar-toggler" type="button" aria-label="Toggle navigation" onclick="document.querySelector('.navbar-links').classList.toggle('show')">
                <i class="fas fa-bars"></i>
            </button>
            <div class="navbar-links">
                <a href="ClientHome.php">Home</a>
                <a href="./clientabout-us.php">About Us</a>
                <a href="./clientcontact-us.php">Contact Us</a>
                <a href="#"><i class="fas fa-bell"></i></a>
                <a href="#"><img src="../assets/Default Image.jpg" alt="Avatar" class="navbar-avatar"></a>
            </div>
        </div>
    </nav>
    <!-- End Custom Navbar -->

    <div class="client-request-outer">
        <div class="client-request-card">
            <div class="client-request-form-card">
                <h2>Fill up form</h2>
                <p>Please complete the form below with accurate information to proceed with your request.</p>
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php elseif ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                <form method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="type" class="form-label">Type</label>
                        <select id="type" name="type" class="form-control" required>
                            <option value="" disabled selected>Select type</option>
                            <option value="Interment">Interment</option>
                            <option value="Transfer">Transfer</option>
                            <option value="Exhumation">Exhumation</option>
                        </select>
                    </div>
                    <div class="section-title">Deceased Information</div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" id="first_name" name="first_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" id="last_name" name="last_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="middle_name" class="form-label">Middle Name</label>
                            <input type="text" id="middle_name" name="middle_name" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label for="age" class="form-label">Age</label>
                            <input type="number" id="age" name="age" min="0" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="dob" class="form-label">Date of Birth</label>
                            <input type="text" id="dob" name="dob" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="dod" class="form-label">Date Died</label>
                            <input type="text" id="dod" name="dod" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="residency" class="form-label">Residency</label>
                            <input type="text" id="residency" name="residency" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="informant_name" class="form-label">Informant Name</label>
                            <input type="text" id="informant_name" name="informant_name" class="form-control" required>
                        </div>
                    </div>
                    <div class="section-title">Upload Files</div>
                    <div class="upload-area mb-2">
                        <label for="file-upload" class="upload-label">
                            <span class="upload-icon"><i class="fas fa-upload"></i></span>
                            Upload file
                            <input type="file" id="file-upload" name="file_upload">
                        </label>
                    </div>
                    <div class="file-note mb-3">
                        Attach file. File size of your documents should not exceed 10MB
                    </div>
                    <button type="submit" class="submit-btn">Submit</button>
                </form>
            </div>
            <div class="client-request-image">
                <img src="../assets/garcia.jpg" alt="Flag Ceremony" />
            </div>
        </div>
    </div>
    <?php include '../includes/footer.php'; ?>
    <!-- Bootstrap JS (optional, for responsive navbar) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
