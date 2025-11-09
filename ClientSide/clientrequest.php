<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: ../login.php"); // Adjust the path if needed
    exit;
}
include_once '../Includes/db.php';
$success = '';
$error = '';

// Handle redirect messages
if (isset($_GET['success'])) {
    $success = htmlspecialchars($_GET['success']);
}
if (isset($_GET['error'])) {
    $error = htmlspecialchars($_GET['error']);
}

// Check if this is an API request (e.g., by a custom header or a query param)
$isApi = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $type = $_POST['type'] ?? '';
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $middle_name = $_POST['middle_name'] ?? '';
    $suffix = isset($_POST['suffix']) ? trim($_POST['suffix']) : null;
    if ($suffix === '' || strtolower($suffix) === '0' || $suffix === '0') {
        $suffix = null;
    }
    $age = $_POST['age'] ?? '';
    $dob = $_POST['dob'] ?? '';
    $dod = $_POST['dod'] ?? '';
    $dateInternment = $_POST['date_internment'] ?? '';
    $residency = $_POST['residency'] ?? '';
    $informant_name = $_POST['informant_name'] ?? '';
    $niche_id = $_POST['niche_id'] ?? '';
    $current_niche_id = $_POST['current_niche_id'] ?? '';
    $new_niche_id = $_POST['new_niche_id'] ?? '';
    $file_upload = '';

    // Handle file upload
    if (isset($_FILES['file_upload']) && $_FILES['file_upload']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];
        $file_type = mime_content_type($_FILES['file_upload']['tmp_name']);
        if (!in_array($file_type, $allowed_types)) {
            $error = "Only PDF or image files are allowed.";
        } else {
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
    }

   // Insert into database if no error
$user_id = $_POST['user_id'] ?? ($_SESSION['user_id'] ?? null);
if (!$error) {
    if (!$user_id) {
        $error = "User not logged in.";
    } else {
        if ($type === 'Relocate' || $type === 'Transfer') {
            if ($suffix === null) {
                $stmt = $conn->prepare("INSERT INTO client_requests (user_id, type, first_name, last_name, middle_name, suffix, age, dob, dod, dateInternment, residency, informant_name, file_upload, niche_id, current_niche_id, new_niche_id) VALUES (?, ?, ?, ?, ?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("issssssssssssss", 
                    $user_id, $type, $first_name, $last_name, $middle_name, 
                    $age, $dob, $dod, $dateInternment, $residency, $informant_name, $file_upload, $niche_id, $current_niche_id, $new_niche_id
                );
            } else {
                $stmt = $conn->prepare("INSERT INTO client_requests (user_id, type, first_name, last_name, middle_name, suffix, age, dob, dod, dateInternment, residency, informant_name, file_upload, niche_id, current_niche_id, new_niche_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssssssssssssss", 
                    $user_id, $type, $first_name, $last_name, $middle_name, 
                    $suffix, $age, $dob, $dod, $dateInternment, $residency, $informant_name, $file_upload, $niche_id, $current_niche_id, $new_niche_id
                );
            }
        } else { // For type === 'New'
                if ($suffix === null) {
                    $stmt = $conn->prepare("INSERT INTO client_requests (
                        user_id, type, first_name, last_name, middle_name, suffix, 
                        age, dob, dod, dateInternment, residency, informant_name, file_upload, niche_id
                    ) VALUES (?, ?, ?, ?, ?, NULL, ?, ?, ?, ?, ?, ?, ?, ?)");

                    $stmt->bind_param("issssisssssss", 
                        $user_id, $type, $first_name, $last_name, $middle_name, 
                        $age, $dob, $dod, $dateInternment, $residency, 
                        $informant_name, $file_upload, $niche_id
                    );
                } else {
                    $stmt = $conn->prepare("INSERT INTO client_requests (
                        user_id, type, first_name, last_name, middle_name, suffix, 
                        age, dob, dod, dateInternment, residency, informant_name, file_upload, niche_id
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                    $stmt->bind_param("isssssisssssss", 
                        $user_id, $type, $first_name, $last_name, $middle_name, 
                        $suffix, $age, $dob, $dod, $dateInternment, $residency, 
                        $informant_name, $file_upload, $niche_id
                    );
                }
            }
                    if ($stmt->execute()) {
            $success = "Request submitted successfully!";
            // Redirect to avoid resubmission and persistent message
            header("Location: clientrequest.php?success=" . urlencode($success));
            exit;
        } else {
            $error = "Database error: " . $conn->error;
            header("Location: clientrequest.php?error=" . urlencode($error));
            exit;
        }
        $stmt->close();
    }
}

    // Validate date logic
    if ($dob && $dod && strtotime($dod) < strtotime($dob)) {
        $error = "Date of death cannot be before date of birth.";
    }
    if ($dob && $dateInternment && strtotime($dateInternment) < strtotime($dob)) {
        $error = "Date of internment cannot be before date of birth.";
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

// Fetch user's full name
$user_fullname = '';
$stmt = $conn->prepare("SELECT first_name, last_name FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($first_name, $last_name);
if ($stmt->fetch()) {
    $user_fullname = trim($first_name . ' ' . $last_name);
}
$stmt->close();
$deceased_list = [];
$stmt = $conn->prepare("SELECT firstName, middleName, lastName, suffix, age, born, residency, dateDied, dateInternment, nicheID FROM deceased WHERE informantName = ?");
$stmt->bind_param("s", $user_fullname);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $deceased_list[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RestEase</title>
    <link rel="icon" type="image/png" href="../assets/re logo blue.png">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/footer.css">
    <link rel="stylesheet" href="../css/clientrequest.css">
    <style>
        /* Hide the decorative request image on small devices and show on larger screens */
        @media (max-width: 768px) {
            .client-request-image { display: none !important; }
        }
        @media (min-width: 769px) {
            .client-request-image { display: block; }
        }

        /* Move the back button upward on small devices only */
        @media (max-width: 480px) {
            .cert-list-back {
                display: inline-block; /* ensure transform applies cleanly */
                transform: translateY(-30px); /* move upward */
                /* small visual tweak: slightly reduce font-size on very small screens if needed */
                /* font-size: 1rem; */
            }
        }
    </style>
</head>
<body>
    <?php include '../Includes/navbar2.php'; ?>
    <div class="client-request-outer">
        <div class="client-request-card">
            <div class="client-request-form-card">
                <div style="display:flex;align-items:center;gap:12px;">
                 
                        <a href="ClientHome.php" class="cert-list-back" style="color:#506C84;font-size:1.08rem;font-weight:500;text-decoration:none;cursor:pointer;transition:color 0.18s;  transform: translateY(-30px);">
    <i class="fas fa-arrow-left"></i> Back
</a>
                 </div>
                    <h2 style="margin-bottom:0;">Fill up form</h2>
                
                <p>Please complete the form below with accurate information to proceed with your request.</p>
                <div id="type-explanation-new" class="alert alert-info mb-3" style="font-size:0.98rem; display:none;">
                    <b>New</b> – Request to register your loved one and lease a burial plot.
                </div>
                <div id="type-explanation-relocate" class="alert alert-info mb-3" style="font-size:0.98rem; display:none;">
                    <b>Relocate</b> – Request to move your loved one within the cemetery, usually to place family members together.
                </div>
                <div id="type-explanation-transfer" class="alert alert-info mb-3" style="font-size:0.98rem; display:none;">
                    <b>Transfer</b> – Request to move your loved one’s remains from this cemetery to another one.
                </div>
                <?php if ($success): ?>
                    <div class="alert alert-success" id="success-msg"><?php echo $success; ?></div>
                <?php elseif ($error): ?>
                    <div class="alert alert-danger" id="error-msg"><?php echo $error; ?></div>
                <?php endif; ?>
                <form method="post" enctype="multipart/form-data" id="client-request-form">
                    <div class="mb-3">
                        <label for="type" class="form-label">Type</label>
                        <select id="type" name="type" class="form-control" required onchange="toggleNicheIdField(); handleRelocateMode();">
                            <option value="" disabled selected>Select type</option>
                            <option value="New">New</option>
                            <option value="Relocate">Relocate</option>
                            <option value="Transfer">Transfer</option>
                        </select>
                    </div>
                    <!-- Deceased selector for Relocate -->
                    <div class="mb-3" id="deceasedSelectorField" style="display:none;">
                        <label for="deceased_selector" class="form-label">Select Deceased Family Member</label>
                        <select id="deceased_selector" class="form-control" onchange="fillDeceasedInfoFromSelector()">
                            <option value="">Select deceased</option>
                            <?php foreach ($deceased_list as $d): ?>
                                <option value='<?php echo json_encode($d, JSON_HEX_APOS | JSON_HEX_QUOT); ?>'><?php echo htmlspecialchars($d['firstName'] . ' ' . $d['lastName']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3" id="nicheIdField" style="display:none;">
                        <label for="niche_id" class="form-label">Niche ID</label>
                        <input type="text" id="niche_id" name="niche_id" class="form-control" placeholder="Enter Niche ID">
                    </div>
                    <div class="section-title" id="deceasedInfoSection">Deceased Information</div>
                    <div class="row g-3" id="deceasedInfoFields">
                        <div class="col-md-6">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" id="first_name" name="first_name" class="form-control" placeholder="First Name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="middle_name" class="form-label">Middle Name</label>
                            <input type="text" id="middle_name" name="middle_name" class="form-control" placeholder="Middle Name">
                        </div>
                        <div class="col-md-6">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" id="last_name" name="last_name" class="form-control" placeholder="Last Name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="suffix" class="form-label">Suffix</label>
                            <input type="text" id="suffix" name="suffix" class="form-control" placeholder="Suffix (e.g. Jr, Sr, III)">
                        </div>
                        <div class="col-md-6">
                            <label for="dob" class="form-label">Date of Birth</label>
                            <input type="date" id="dob" name="dob" class="form-control" placeholder="Date of Birth" required>
                        </div>
                        <div class="col-md-6">
                            <label for="dod" class="form-label">Date Died</label>
                            <input type="date" id="dod" name="dod" class="form-control" placeholder="Date Died" required>
                        </div>
                        <div class="col-md-6">
                            <label for="age_display" class="form-label">Age</label>
                            <!-- Visible but not editable -->
                            <input type="number" id="age_display" class="form-control" placeholder="Age" required disabled>
                            <!-- Hidden field that actually submits -->
                            <input type="hidden" id="age" name="age">
                        </div>
                        <div class="col-md-6">
                            <label for="date_internment" class="form-label">Date of Internment</label>
                            <input type="date" id="date_internment" name="date_internment" class="form-control" placeholder="Date of Internment" required>
                        </div>
                        <div class="col-md-6">
                            <label for="residency" class="form-label">Residency</label>
                            <div class="input-group mb-2">
                                <input type="text" id="residency" name="residency" class="form-control" placeholder="Enter Residency" required>
                                <select id="barangay-dropdown" class="form-select" style="width: 40px; min-width: 40px; max-width: 40px; padding-left: 0; padding-right: 0;" onchange="setResidencyFromDropdown(this)">
                                    <option value=""></option>
                                    <option value="Banaba, Padre Garcia, Batangas">Banaba, Padre Garcia, Batangas</option>
                                    <option value="Banaybanay, Padre Garcia, Batangas">Banaybanay, Padre Garcia, Batangas</option>
                                    <option value="Bawi, Padre Garcia, Batangas">Bawi, Padre Garcia, Batangas</option>
                                    <option value="Bukal, Padre Garcia, Batangas">Bukal, Padre Garcia, Batangas</option>
                                    <option value="Castillo, Padre Garcia, Batangas">Castillo, Padre Garcia, Batangas</option>
                                    <option value="Cawongan, Padre Garcia, Batangas">Cawongan, Padre Garcia, Batangas</option>
                                    <option value="Manggas, Padre Garcia, Batangas">Manggas, Padre Garcia, Batangas</option>
                                    <option value="Maugat East, Padre Garcia, Batangas">Maugat East, Padre Garcia, Batangas</option>
                                    <option value="Maugat West, Padre Garcia, Batangas">Maugat West, Padre Garcia, Batangas</option>
                                    <option value="Pansol, Padre Garcia, Batangas">Pansol, Padre Garcia, Batangas</option>
                                    <option value="Payapa, Padre Garcia, Batangas">Payapa, Padre Garcia, Batangas</option>
                                    <option value="Poblacion, Padre Garcia, Batangas">Poblacion, Padre Garcia, Batangas</option>
                                    <option value="Quilo-quilo North, Padre Garcia, Batangas">Quilo-quilo North, Padre Garcia, Batangas</option>
                                    <option value="Quilo-quilo South, Padre Garcia, Batangas">Quilo-quilo South, Padre Garcia, Batangas</option>
                                    <option value="San Felipe, Padre Garcia, Batangas">San Felipe, Padre Garcia, Batangas</option>
                                    <option value="San Miguel, Padre Garcia, Batangas">San Miguel, Padre Garcia, Batangas</option>
                                    <option value="Tamak, Padre Garcia, Batangas">Tamak, Padre Garcia, Batangas</option>
                                    <option value="Tangob, Padre Garcia, Batangas">Tangob, Padre Garcia, Batangas</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="informant_name" class="form-label">Informant Name</label>
                            <input type="text" id="informant_name" name="informant_name" class="form-control" required value="<?php echo htmlspecialchars($user_fullname, ENT_QUOTES); ?>" readonly>
                        </div>
                        <div class="col-md-6" id="currentNicheField" style="display:none;">
                            <label for="current_niche" class="form-label">Current Niche</label>
                            <input type="text" id="current_niche" class="form-control" readonly>
                        </div>
                    </div>
                    <!-- Niche picker for Relocate -->
                    <div class="mb-3" id="nichePickerField" style="display:none;">
                        <label for="niche_picker" class="form-label">Select New Niche Location</label>
                        <div class="input-group">
                            <input type="text" id="niche_picker" class="form-control" name="niche_id" placeholder="Select niche or pick from map" readonly>
                            <button type="button" id="pickNicheBtn" class="btn btn-outline-secondary" title="Pick Niche from Map">
                                <i class="fas fa-map-marker-alt"></i> Pick Niche
                            </button>
                        </div>
                    </div>
                    <div class="section-title">Upload Death Certificate</div>
                    <div class="upload-area mb-2" id="upload-area">
                        <label for="file-upload" class="upload-label">
                            <span class="upload-icon"><i class="fas fa-upload"></i></span>
                            Upload file
                            <input type="file" id="file-upload" name="file_upload" accept="image/*,application/pdf">
                        </label>
                        <div id="file-preview" style="margin-top:10px;"></div>
                        <div id="upload-required-msg" style="display:none;color:#d32f2f;font-size:0.97rem;margin-top:10px;">
        Uploading a document is required for "New" requests.
    </div>
                    </div>
                    <div class="file-note mb-3">
                        Attach file. File size of your documents should not exceed 10MB
                    </div>
                    <div class="alert alert-warning" style="font-size: 0.95rem;">
                        Please double check any of the following information before submitting to avoid any conflict.
                    </div>
                    <input type="hidden" id="current_niche_id" name="current_niche_id">
                    <input type="hidden" id="new_niche_id" name="new_niche_id">
                    <button type="submit" class="submit-btn">Submit</button>
                </form>
            </div>
            <div class="client-request-image">
                <img src="../assets/garcia.webp" alt="Flag Ceremony" />
            </div>
        </div>
    </div>
    <?php include '../Includes/footer-client.php'; ?>
    <!-- Bootstrap JS (optional, for responsive navbar) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function toggleNicheIdField() {
        var type = document.getElementById('type').value;
        var nicheField = document.getElementById('nicheIdField');
        if (type === 'Transfer' || type === 'Exhumation') {
            nicheField.style.display = '';
            document.getElementById('niche_id').required = true;
        } else {
            nicheField.style.display = 'none';
            document.getElementById('niche_id').required = false;
            document.getElementById('niche_id').value = '';
        }
    }
    function handleRelocateMode() {
        var type = document.getElementById('type').value;
        var deceasedSelector = document.getElementById('deceasedSelectorField');
        var deceasedInfoFields = document.getElementById('deceasedInfoFields');
        var deceasedInfoSection = document.getElementById('deceasedInfoSection');
        var nichePicker = document.getElementById('nichePickerField');
        var nicheIdField = document.getElementById('nicheIdField');
        var currentNicheField = document.getElementById('currentNicheField');
        if (type === 'Relocate') {
            deceasedSelector.style.display = '';
            deceasedInfoFields.style.display = '';
            deceasedInfoSection.style.display = '';
            nichePicker.style.display = '';
            nicheIdField.style.display = 'none';
            currentNicheField.style.display = '';
        } else if (type === 'Transfer') {
            deceasedSelector.style.display = '';
            deceasedInfoFields.style.display = '';
            deceasedInfoSection.style.display = '';
            nichePicker.style.display = 'none'; // Hide new niche picker for Transfer
            nicheIdField.style.display = 'none';
            currentNicheField.style.display = '';
        } else {
            deceasedSelector.style.display = 'none';
            nichePicker.style.display = 'none';
            deceasedInfoFields.style.display = '';
            deceasedInfoSection.style.display = '';
            nicheIdField.style.display = 'none';
            currentNicheField.style.display = 'none';
        }
    }
    function setResidencyFromDropdown(select) {
        if (select.value) {
            document.getElementById('residency').value = select.value;
            select.selectedIndex = 0; // Reset dropdown to default after selection
        }
    }

    function calculateAge() {
        var dob = document.getElementById('dob').value;
        var dod = document.getElementById('dod').value;
        var ageDisplay = document.getElementById('age_display');
        var ageHidden = document.getElementById('age');

        var age = '';
        if (dob && dod) {
            var birth = new Date(dob);
            var death = new Date(dod);
            age = death.getFullYear() - birth.getFullYear();
            var m = death.getMonth() - birth.getMonth();
            if (m < 0 || (m === 0 && death.getDate() < birth.getDate())) {
                age--;
            }
            if (age < 0) age = '';
        }

        ageDisplay.value = age; // show user
        ageHidden.value = age;  // save in DB
    }

    function fillDeceasedInfoFromSelector() {
        var selector = document.getElementById('deceased_selector');
        var value = selector.value;
        if (!value) return;
        var data = JSON.parse(value);
        document.getElementById('first_name').value = data.firstName || '';
        document.getElementById('last_name').value = data.lastName || '';
        document.getElementById('middle_name').value = data.middleName || '';
        document.getElementById('suffix').value = (data.suffix && data.suffix.trim() !== '' && data.suffix !== '0') ? data.suffix : '';
        document.getElementById('dob').value = data.born || '';
        document.getElementById('dod').value = data.dateDied || '';
        document.getElementById('age_display').value = data.age || '';
        document.getElementById('age').value = data.age || '';
        document.getElementById('residency').value = data.residency || '';
        document.getElementById('niche_id').value = data.nicheID || '';
        document.getElementById('current_niche').value = data.nicheID || '';
        document.getElementById('current_niche_id').value = data.nicheID || '';
        document.getElementById('date_internment').value = data.dateInternment || '';
    }

    function showTypeExplanation() {
        var type = document.getElementById('type').value;
        document.getElementById('type-explanation-new').style.display = (type === 'New') ? '' : 'none';
        document.getElementById('type-explanation-relocate').style.display = (type === 'Relocate') ? '' : 'none';
        document.getElementById('type-explanation-transfer').style.display = (type === 'Transfer') ? '' : 'none';
    }

    document.getElementById('type').addEventListener('change', showTypeExplanation);
    document.addEventListener('DOMContentLoaded', function() {
        toggleNicheIdField();
        handleRelocateMode();
        showTypeExplanation();
    });
    document.getElementById('dob').addEventListener('change', calculateAge);
    document.getElementById('dod').addEventListener('change', calculateAge);
    // Replace admin map popup with client-facing map so users are not required to log in as admin
    document.getElementById('pickNicheBtn').onclick = function() {
        // Open the client map (same folder) in a popup for niche selection
        window.open('ClientMap.php?pickNiche=1', 'PickNiche', 'width=900,height=700');
    };
    
    window.addEventListener('message', function(event) {
        if (event.data && event.data.nicheID) {
            document.getElementById('niche_picker').value = event.data.nicheID;
            document.getElementById('new_niche_id').value = event.data.nicheID;
        }
    });

    // File preview logic
    document.getElementById('file-upload').addEventListener('change', function(e) {
        const preview = document.getElementById('file-preview');
        preview.innerHTML = '';
        const file = e.target.files[0];
        if (!file) return;
        // Only allow image or PDF preview
        if (!file.type.startsWith('image/') && file.type !== 'application/pdf') {
            preview.textContent = 'Only PDF or image files are allowed.';
            e.target.value = '';
            return;
        }
        // Show file name
        const nameDiv = document.createElement('div');
        nameDiv.textContent = 'Selected file: ' + file.name;
        preview.appendChild(nameDiv);
        // If image, show thumbnail
        if (file.type.startsWith('image/')) {
            const img = document.createElement('img');
            img.style.maxWidth = '180px';
            img.style.maxHeight = '120px';
            img.style.marginTop = '8px';
            img.src = URL.createObjectURL(file);
            preview.appendChild(img);
        }
        // If PDF, show icon
        if (file.type === 'application/pdf') {
            const pdfIcon = document.createElement('span');
            pdfIcon.innerHTML = '<i class="fas fa-file-pdf" style="font-size:2rem;color:#d32f2f;margin-top:8px;"></i>';
            preview.appendChild(pdfIcon);
        }
    });

    function checkUploadRequirement() {
    var type = document.getElementById('type').value;
    var fileInput = document.getElementById('file-upload');
    var uploadArea = document.getElementById('upload-area');
    var msg = document.getElementById('upload-required-msg');
    if (type === 'New' && (!fileInput.files || fileInput.files.length === 0)) {
        uploadArea.classList.add('upload-required');
        msg.style.display = '';
    } else {
        uploadArea.classList.remove('upload-required');
        msg.style.display = 'none';
    }
}
document.getElementById('type').addEventListener('change', checkUploadRequirement);
document.getElementById('file-upload').addEventListener('change', checkUploadRequirement);
    document.getElementById('client-request-form').addEventListener('submit', function(e) {
        var type = document.getElementById('type').value;
        var fileInput = document.getElementById('file-upload');
        if (type === 'New' && (!fileInput.files || fileInput.files.length === 0)) {
            checkUploadRequirement();
            fileInput.focus();
            e.preventDefault();
            return false;
        }
    });

    function validateDates() {
        var dob = document.getElementById('dob').value;
        var dod = document.getElementById('dod').value;
        var dateInternment = document.getElementById('date_internment').value;
        var errorMsg = '';

        if (dob && dod && new Date(dod) < new Date(dob)) {
            errorMsg = 'Date of death cannot be before date of birth.';
        }
        if (dob && dateInternment && new Date(dateInternment) < new Date(dob)) {
            errorMsg = 'Date of internment cannot be before date of birth.';
        }

        if (errorMsg) {
            alert(errorMsg);
            return false;
        }
        return true;
    }

    document.getElementById('client-request-form').addEventListener('submit', function(e) {
        if (!validateDates()) {
            e.preventDefault();
            return false;
        }
    });

    // Remove ?success=... or ?error=... from URL after showing message
    document.addEventListener('DOMContentLoaded', function() {
        // ...existing code...
        var hasMsg = document.getElementById('success-msg') || document.getElementById('error-msg');
        if (hasMsg && window.location.search.match(/(\?|&)success=|(\?|&)error=/)) {
            if (window.history.replaceState) {
                var url = window.location.origin + window.location.pathname;
                window.history.replaceState({}, document.title, url);
            }
        }
    });
    </script>
</body>
</html>