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
    $residency = $_POST['residency'] ?? '';
    $informant_name = $_POST['informant_name'] ?? '';
    $niche_id = $_POST['niche_id'] ?? '';
    $current_niche_id = $_POST['current_niche_id'] ?? '';
    $new_niche_id = $_POST['new_niche_id'] ?? '';
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
        if ($type === 'Relocate' || $type === 'Transfer') {
            if ($suffix === null) {
                $stmt = $conn->prepare("INSERT INTO client_requests (user_id, type, first_name, last_name, middle_name, suffix, age, dob, dod, residency, informant_name, file_upload, niche_id, current_niche_id, new_niche_id) VALUES (?, ?, ?, ?, ?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("issssssssssssss", 
                    $user_id, $type, $first_name, $last_name, $middle_name, 
                    $age, $dob, $dod, $residency, $informant_name, $file_upload, $niche_id, $current_niche_id, $new_niche_id
                );
            } else {
                $stmt = $conn->prepare("INSERT INTO client_requests (user_id, type, first_name, last_name, middle_name, suffix, age, dob, dod, residency, informant_name, file_upload, niche_id, current_niche_id, new_niche_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("issssssssssssss", 
                    $user_id, $type, $first_name, $last_name, $middle_name, 
                    $suffix, $age, $dob, $dod, $residency, $informant_name, $file_upload, $niche_id, $current_niche_id, $new_niche_id
                );
            }
        } else {
            if ($suffix === null) {
                $stmt = $conn->prepare("INSERT INTO client_requests (user_id, type, first_name, last_name, middle_name, suffix, age, dob, dod, residency, informant_name, file_upload, niche_id) VALUES (?, ?, ?, ?, ?, NULL, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("issssissssss", 
                    $user_id, $type, $first_name, $last_name, $middle_name, 
                    $age, $dob, $dod, $residency, $informant_name, $file_upload, $niche_id
                );
            } else {
                $stmt = $conn->prepare("INSERT INTO client_requests (user_id, type, first_name, last_name, middle_name, suffix, age, dob, dod, residency, informant_name, file_upload, niche_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssssissssss", 
                    $user_id, $type, $first_name, $last_name, $middle_name, 
                    $suffix, $age, $dob, $dod, $residency, $informant_name, $file_upload, $niche_id
                );
            }
        }
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
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php elseif ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
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
                            <input type="text" id="first_name" name="first_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="middle_name" class="form-label">Middle Name</label>
                            <input type="text" id="middle_name" name="middle_name" class="form-control">
                        </div>
                         <div class="col-md-6">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" id="last_name" name="last_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="suffix" class="form-label">Suffix</label>
                            <input type="text" id="suffix" name="suffix" class="form-control" placeholder="e.g. Jr, Sr, III">
                        </div>
                       <div class="col-md-6">
                            <label for="dob" class="form-label">Date of Birth</label>
                            <input type="date" id="dob" name="dob" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="dod" class="form-label">Date Died</label>
                            <input type="date" id="dod" name="dod" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="age_display" class="form-label">Age</label>
                            <!-- Visible but not editable -->
                            <input type="number" id="age_display" class="form-control" required disabled>
                            <!-- Hidden field that actually submits -->
                            <input type="hidden" id="age" name="age">
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
                    <div class="alert alert-warning" style="font-size: 0.95rem;">
                        Please double check any of the following information before submitting to avoid any conflict.
                    </div>
                    <input type="hidden" id="current_niche_id" name="current_niche_id">
                    <input type="hidden" id="new_niche_id" name="new_niche_id">
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
        if (type === 'Relocate' || type === 'Transfer') {
            deceasedSelector.style.display = '';
            deceasedInfoFields.style.display = '';
            deceasedInfoSection.style.display = '';
            nichePicker.style.display = '';
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
    document.getElementById('pickNicheBtn').onclick = function() {
        window.open('../AdminSide/Mapping.php?pickNiche=1', 'PickNiche', 'width=900,height=700');
    };
    window.addEventListener('message', function(event) {
        if (event.data && event.data.nicheID) {
            document.getElementById('niche_picker').value = event.data.nicheID;
            document.getElementById('new_niche_id').value = event.data.nicheID;
        }
    });
    </script>
</body>
</html>
