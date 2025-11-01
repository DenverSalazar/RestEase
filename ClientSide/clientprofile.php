<?php
session_start();
include '../Includes/db.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$show_toast = false;
$show_error = false;
$error_msg = '';

// Handle profile update (Save button)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {
    $new_first_name = trim($_POST['first_name']);
    $new_last_name = trim($_POST['last_name']);
    $new_email = trim($_POST['email']);
    $new_contact_no = trim($_POST['contact_no']);
    // Backend validation
    if (!preg_match('/^[a-zA-Z ]+$/', $new_first_name)) {
        $show_error = true;
        $error_msg = 'First name must contain only letters and spaces.';
    } elseif (!preg_match('/^[a-zA-Z ]+$/', $new_last_name)) {
        $show_error = true;
        $error_msg = 'Last name must contain only letters and spaces.';
    } elseif (!preg_match('/^[0-9+\-() ]+$/', $new_contact_no)) {
        $show_error = true;
        $error_msg = 'Phone number must contain only numbers and allowed symbols.';
    } else {
        // Fetch old name before update
        $stmt = $conn->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($old_first_name, $old_last_name);
        $stmt->fetch();
        $stmt->close();
        $old_fullname = trim($old_first_name . ' ' . $old_last_name);
        $new_fullname = trim($new_first_name . ' ' . $new_last_name);

        // Handle profile picture upload or delete
        $profile_picture_action = $_POST['profile_picture_action'] ?? '';
        $current_profile_picture = $_POST['current_profile_picture'] ?? '';
        $new_profile_picture = $current_profile_picture;
        if ($profile_picture_action === 'delete') {
            if ($current_profile_picture && file_exists('../uploads/' . $current_profile_picture)) {
                unlink('../uploads/' . $current_profile_picture);
            }
            $new_profile_picture = null;
        } elseif ($profile_picture_action === 'upload' && isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['profile_picture'];
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
            $max_size = 2 * 1024 * 1024; // 2MB
            if (!in_array(mime_content_type($file['tmp_name']), $allowed_types)) {
                $show_error = true;
                $error_msg = 'Only JPG, PNG, and GIF files are allowed.';
            } elseif ($file['size'] > $max_size) {
                $show_error = true;
                $error_msg = 'File size must be less than 2MB.';
            } else {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = time() . '_' . uniqid() . '.' . $ext;
                $target = '../uploads/' . $filename;
                if (move_uploaded_file($file['tmp_name'], $target)) {
                    if ($current_profile_picture && file_exists('../uploads/' . $current_profile_picture)) {
                        unlink('../uploads/' . $current_profile_picture);
                    }
                    $new_profile_picture = $filename;
                } else {
                    $show_error = true;
                    $error_msg = 'Failed to upload file.';
                }
            }
        }
        if (!$show_error) {
            // Update users table
            $stmt = $conn->prepare('UPDATE users SET first_name = ?, last_name = ?, email = ?, contact_no = ?, profile_picture = ? WHERE id = ?');
            $stmt->bind_param('sssssi', $new_first_name, $new_last_name, $new_email, $new_contact_no, $new_profile_picture, $user_id);
            $stmt->execute();
            $stmt->close();
            $show_toast = true;

            // If name changed, update all related tables
            if ($old_fullname !== $new_fullname) {
                // Update informantName in deceased
                $stmt = $conn->prepare("UPDATE deceased SET informantName = ? WHERE informantName = ?");
                $stmt->bind_param("ss", $new_fullname, $old_fullname);
                $stmt->execute();
                $stmt->close();

                // Update Payee in ledger
                $stmt = $conn->prepare("UPDATE ledger SET Payee = ? WHERE Payee = ?");
                $stmt->bind_param("ss", $new_fullname, $old_fullname);
                $stmt->execute();
                $stmt->close();

                // Update informant_name in client_requests
                $stmt = $conn->prepare("UPDATE client_requests SET informant_name = ? WHERE informant_name = ?");
                $stmt->bind_param("ss", $new_fullname, $old_fullname);
                $stmt->execute();
                $stmt->close();

                // Update informant_name in accepted_request
                $stmt = $conn->prepare("UPDATE accepted_request SET informant_name = ? WHERE informant_name = ?");
                $stmt->bind_param("ss", $new_fullname, $old_fullname);
                $stmt->execute();
                $stmt->close();

                // Update informant_name in assessment
                $stmt = $conn->prepare("UPDATE assessment SET informant_name = ? WHERE informant_name = ?");
                $stmt->bind_param("ss", $new_fullname, $old_fullname);
                $stmt->execute();
                $stmt->close();

                // Update InformantName and Payee in certification
                $stmt = $conn->prepare("UPDATE certification SET InformantName = ? WHERE InformantName = ?");
                $stmt->bind_param("ss", $new_fullname, $old_fullname);
                $stmt->execute();
                $stmt->close();

                $stmt = $conn->prepare("UPDATE certification SET Payee = ? WHERE Payee = ?");
                $stmt->bind_param("ss", $new_fullname, $old_fullname);
                $stmt->execute();
                $stmt->close();

                // Update name in archive_clients (all matching old name, case-insensitive)
                $stmt = $conn->prepare("UPDATE archive_clients SET first_name = ?, last_name = ? WHERE LOWER(TRIM(first_name)) = LOWER(?) AND LOWER(TRIM(last_name)) = LOWER(?)");
                $stmt->bind_param("ssss", $new_first_name, $new_last_name, $old_first_name, $old_last_name);
                $stmt->execute();
                $stmt->close();

                // Update informantName in archive_deceased (all matching old_fullname, case-insensitive)
                $stmt = $conn->prepare("UPDATE archive_deceased SET informantName = ? WHERE LOWER(TRIM(informantName)) = LOWER(?)");
                $stmt->bind_param("ss", $new_fullname, $old_fullname);
                $stmt->execute();
                $stmt->close();

                // Update informant_name in denied_request (archive requests)
                $stmt = $conn->prepare("UPDATE denied_request SET informant_name = ? WHERE LOWER(TRIM(informant_name)) = LOWER(?)");
                $stmt->bind_param("ss", $new_fullname, $old_fullname);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
}

// Fetch user data (again, in case of update)
$stmt = $conn->prepare('SELECT first_name, last_name, email, contact_no, profile_picture FROM users WHERE id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($first_name, $last_name, $email, $contact_no, $profile_picture);
$stmt->fetch();
$stmt->close();

$has_profile_picture = $profile_picture && file_exists('../uploads/' . $profile_picture);
$profile_img = $has_profile_picture ? '../uploads/' . htmlspecialchars($profile_picture) . '?v=' . time() : '';
$initials = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));
$avatar_html = $has_profile_picture
    ? '<img src="' . $profile_img . '" alt="Profile Avatar" class="profile-avatar" style="width:56px;height:56px;">'
    : '<div class="profile-avatar-initials" style="width:56px;height:56px;">' . $initials . '</div>';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../assets/re logo blue.png">
    <title>RestEase</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../css/footer.css">
    <link rel="stylesheet" href="../css/clientprofile.css">
    <style>
        /* Custom Toast Styles (copied from register.php) */
        .custom-toast {
            position: fixed;
            top: 40px;
            right: 40px;
            min-width: 320px;
            max-width: 400px;
            display: flex;
            align-items: center;
            background: #fff;
            box-shadow: 0 8px 32px rgba(60,60,60,0.18), 0 1.5px 6px rgba(0,0,0,0.08);
            border-radius: 1rem;
            padding: 1.1rem 1.5rem;
            z-index: 9999;
            font-family: 'Poppins', sans-serif;
            font-size: 1.08rem;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .custom-toast.success {
            border-left: 6px solid #38d39f;
        }
        .custom-toast .toast-icon {
            font-size: 2rem;
            margin-right: 1rem;
            color: #38d39f;
        }
        .custom-toast .toast-message {
            flex: 1;
        }
        .custom-toast .toast-close {
            font-size: 1.5rem;
            color: #888;
            cursor: pointer;
            margin-left: 1rem;
            transition: color 0.2s;
        }
        .custom-toast .toast-close:hover {
            color: #222;
        }
        @media (max-width: 600px) {
            /* make room at the top so heading/subtitle appear lower on small devices */
            .profile-content {
                padding-top: 48px !important; /* adjust value as needed */
            }

            /* small visual tweak to subtitle spacing */
            .profile-content .subtitle {
                margin-top: 6px !important;
            }
        }
        /* Confirmation Modal */
        .modal-confirm {
            color: #636363;
            width: 400px;
        }
        .modal-confirm .modal-content {
            padding: 20px;
            border-radius: 5px;
            border: none;
        }
        .modal-confirm .modal-header {
            border-bottom: none;
            position: relative;
        }
        .modal-confirm h4 {
            text-align: center;
            font-size: 26px;
            margin: 30px 0 -10px;
        }
        .modal-confirm .close {
            position: absolute;
            top: -5px;
            right: -2px;
        }
        .modal-confirm .modal-footer {
            border: none;
            text-align: center;
            border-radius: 5px;
            font-size: 13px;
        }
        .modal-confirm .modal-footer a {
            color: #999;
        }
        .modal-confirm .icon-box {
            color: #fff;
            position: absolute;
            margin: 0 auto;
            left: 0;
            right: 0;
            top: -70px;
            width: 95px;
            height: 95px;
            border-radius: 50%;
            z-index: 9;
            background: #38d39f;
            padding: 15px;
            text-align: center;
            box-shadow: 0px 2px 2px rgba(0, 0, 0, 0.1);
        }
        .modal-confirm .icon-box i {
            font-size: 58px;
            position: relative;
            top: 3px;
        }
        .modal-confirm .btn {
            color: #fff;
            border-radius: 4px;
            background: #38d39f;
            text-decoration: none;
            transition: all 0.4s;
            line-height: normal;
            border: none;
        }
        .modal-confirm .btn-secondary {
            background: #bbb;
        }
        .modal-confirm .btn-secondary:hover, .modal-confirm .btn-secondary:focus {
            background: #999;
        }
        .modal-confirm .btn-success:hover, .modal-confirm .btn-success:focus {
            background: #2ec487;
        }
        /* --- Begin pay-confirm-modal styles from clientbilling.css --- */
        .modal-overlay#profileConfirmModalOverlay {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100vw; height: 100vh;
            background: rgba(80,108,132,0.13);
            z-index: 3000;
            transition: opacity 0.2s;
            opacity: 0;
        }
        .modal-overlay#profileConfirmModalOverlay.show {
            display: block;
            opacity: 1;
        }
        .pay-confirm-modal#profilePayConfirmModal {
            display: none;
            position: fixed;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%) scale(0.98);
            min-width: 340px;
            max-width: 95vw;
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 4px 32px rgba(80,108,132,0.13);
            z-index: 3100;
            padding: 0 0 36px 0;
            text-align: center;
            transition: opacity 0.2s, transform 0.2s;
            opacity: 0;
            overflow: hidden;
        }
        .pay-confirm-modal#profilePayConfirmModal.show {
            display: block;
            opacity: 1;
            transform: translate(-50%, -50%) scale(1);
        }
        .pay-confirm-modal-header {
            background: #C7F5D2;
            border-top-left-radius: 24px;
            border-top-right-radius: 24px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .pay-confirm-check {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
        }
        .pay-confirm-check svg {
            margin-top: 0;
        }
        .pay-confirm-modal-message {
            color: #2ecc71;
            font-size: 1.08rem;
            font-weight: 500;
            margin: 32px 0 32px 0;
            text-align: center;
            letter-spacing: 0.01em;
            max-width: 90%;
            word-break: break-word;
            padding: 0 12px;
            display: inline-block;
        }
        .pay-confirm-modal-actions {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 18px;
            margin: 0 0 24px 0;
        }
        .pay-confirm-modal-confirm, .pay-confirm-modal-back {
            min-width: 120px;
            height: 44px;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 22px;
            padding: 0 24px;
            box-shadow: none;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .pay-confirm-modal-confirm {
            background: #4B7BEC;
            color: #fff;
            transition: background 0.18s;
        }
        .pay-confirm-modal-confirm:hover {
            background: #3867d6;
        }
        .pay-confirm-modal-back {
            background: #f3f7fa;
            color: #b0b0b0;
            transition: background 0.18s, color 0.18s;
            cursor: pointer;
        }
        .pay-confirm-modal-back:hover {
            background: #e3e8ee;
            color: #222;
        }
        @media (max-width: 600px) {
            .pay-confirm-modal#profilePayConfirmModal {
                min-width: 0;
                width: 96vw;
                padding: 0 0 18px 0;
            }
            .pay-confirm-modal-header {
                height: 54px;
            }
        }
        .profile-avatar-initials {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: #4B7BEC;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            font-weight: 700;
            letter-spacing: 1px;
            user-select: none;
            margin: 0 auto 12px auto;
            box-shadow: 0 2px 8px rgba(80,108,132,0.08);
            border: 2px solid #b2c9db;
        }

        /* Ensure avatar initials are perfectly centered even if other styles interfere */
        #profileAvatarContainer .profile-avatar-initials,
        .profile-avatar-initials {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            text-align: center !important;
            line-height: 1 !important; /* prevent baseline shift */
            padding: 0 !important;
            box-sizing: border-box !important;
        }

        /* Keep images centered/contained */
        #profileAvatarContainer img.profile-avatar {
            display: block !important;
            margin: 0 auto !important;
            object-fit: cover !important;
            border-radius: 50% !important;
        }

        /* Responsive: center avatar, name/email and buttons on small devices (improved override) */
        @media (max-width: 600px) {
            .profile-avatar-section {
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                text-align: center !important;
                gap: 12px;
                padding: 0 12px;
            }

            /* Ensure avatar container and image/initials are centered and constrained to 56x56 */
            #profileAvatarContainer {
                display: block !important;
                margin: 0 auto !important;
                width: 56px !important;
                height: 56px !important;
            }
            #profileAvatarContainer img.profile-avatar,
            #profileAvatarContainer .profile-avatar-initials {
                display: block !important;
                margin: 0 auto !important;
                width: 56px !important;
                height: 56px !important;
                max-width: 56px !important;
                max-height: 56px !important;
                object-fit: cover !important;
                border-radius: 50% !important;
                line-height: 1 !important;
                padding: 0 !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
            }

            /* Center name and email */
            .profile-avatar-section .profile-info {
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                text-align: center !important;
                width: auto !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .profile-avatar-section .profile-info .profile-name,
            .profile-avatar-section .profile-info .profile-email {
                margin: 0 !important;
                width: auto !important;
            }

            /* Center buttons and keep them compact (no forced min-width) */
            .profile-avatar-section .profile-buttons {
                display: flex !important;
                gap: 10px !important;
                justify-content: center !important;
                width: 100% !important;
                margin-top: 6px !important;
            }
            .profile-avatar-section .profile-buttons .btn-upload,
            .profile-avatar-section .profile-buttons .btn-delete {
                min-width: unset !important;    /* remove forced large width */
                width: auto !important;
                padding: 6px 12px !important;  /* compact, consistent padding */
                font-size: 0.95rem !important;  /* keep text readable but compact */
                line-height: 1.2 !important;
                box-sizing: border-box !important;
            }
        }

        /* ensure profile-content can be used as positioning context */
        .profile-content {
            position: relative;
        }

        /* place back button at upper-right of profile-content */
        .cert-list-back {
            position: absolute;
            top: 16px;
            right: 38px;
            z-index: 50;
            display: inline-block;
            padding: 6px 10px;
            border-radius: 6px;
            background: transparent;
            transition: background 0.15s, color 0.15s;
        }
        .cert-list-back:hover {
            background: rgba(0,0,0,0.04);
            text-decoration: none;
        }

        @media (max-width: 600px) {
            .cert-list-back {
                top: 10px;
                right: 10px;
                padding: 6px 8px;
                font-size: 0.98rem;
            }
        }
    </style>
</head>
<body>
   <?php include '../Includes/navbar2.php'; ?>

    <div class="profile-container">
        <div class="profile-header"></div>
        <div class="profile-content">
            <!-- Add Back Button -->
            <a href="ClientHome.php" class="cert-list-back" style="color:#506C84;font-size:1.08rem;font-weight:500;text-decoration:none;cursor:pointer;transition:color 0.18s;">
                &larr; Back
            </a>
            <h2>My Profile</h2>
            <p class="subtitle">Real-time information and activities of your property.</p>
            <?php if ($show_toast): ?>
            <div id="customToast" class="custom-toast success">
                <div class="toast-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="toast-message">
                    Profile updated successfully!
                </div>
                <span class="toast-close" onclick="closeToast()">&times;</span>
            </div>
            <?php endif; ?>
            <?php if ($show_error): ?>
            <div id="customToast" class="custom-toast error" style="opacity:1;">
                <div class="toast-icon">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <div class="toast-message">
                    <?php echo htmlspecialchars($error_msg); ?>
                </div>
                <span class="toast-close" onclick="closeToast()">&times;</span>
            </div>
            <?php endif; ?>
            <div class="profile-main">
                <div class="profile-avatar-section">
                    <div id="profileAvatarContainer">
                        <?php echo $avatar_html; ?>
                    </div>
                    <div class="profile-info">
                        <div class="profile-name"><?php echo htmlspecialchars($first_name . ' ' . $last_name); ?></div>
                        <div class="profile-email"><?php echo htmlspecialchars($email); ?></div>
                    </div>
                    <div class="profile-buttons">
                        <input type="file" name="profile_picture" accept="image/*" style="display:none;" id="uploadInput">
                        <button type="button" class="btn-upload" id="uploadBtn">Upload new picture</button>
                        <button type="button" class="btn-delete" id="deleteBtn">Delete</button>
                    </div>
                </div>
                <form class="profile-form" id="profileForm" method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="profile_picture_action" id="profile_picture_action" value="">
                    <input type="hidden" name="current_profile_picture" id="current_profile_picture" value="<?php echo htmlspecialchars($profile_picture); ?>">
                    <div class="form-section">
                        <label>Personal Information</label>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="firstName">First Name</label>
                                <input type="text" id="firstName" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>" required pattern="^[a-zA-Z ]+$" title="First name must contain only letters and spaces.">
                            </div>
                            <div class="form-group">
                                <label for="lastName">Last Name</label>
                                <input type="text" id="lastName" name="last_name" value="<?php echo htmlspecialchars($last_name); ?>" required pattern="^[a-zA-Z ]+$" title="Last name must contain only letters and spaces.">
                            </div>
                        </div>
                    </div>
                    <div class="form-section">
                        <label>Contact Email</label>
                        <span class="form-desc">Manage your contact email address here</span>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="text" id="phone" name="contact_no" value="<?php echo htmlspecialchars($contact_no); ?>" required pattern="^[0-9+\-() ]+$" title="Phone number must contain only numbers and allowed symbols.">
                            </div>
                        </div>
                    </div>
                    <div class="form-section d-flex justify-content-end gap-2 mt-4">
                        <!-- Hide Cancel and Save buttons by default -->
                        <button type="button" class="btn btn-secondary" id="cancelBtn" style="display:none;">Cancel</button>
                        <button type="button" class="btn btn-success" id="saveBtn" style="display:none;">Save</button>
                        <button type="submit" id="realSubmit" name="save_profile" style="display:none;"></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Profile Save Confirmation Modal -->
    <div class="modal-overlay" id="profileConfirmModalOverlay"></div>
    <div class="pay-confirm-modal" id="profilePayConfirmModal">
        <div class="pay-confirm-modal-header">
            <span class="pay-confirm-check">
                <svg width="48" height="48" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="32" cy="32" r="32" fill="none"/>
                    <path d="M20 34L29 43L44 25" stroke="#5EDC8C" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </span>
        </div>
        <div class="pay-confirm-modal-message">Are you sure you want to save your changes?</div>
        <div class="pay-confirm-modal-actions">
            <button class="pay-confirm-modal-confirm" type="button" id="profileConfirmSaveBtn">Confirm</button>
            <button class="pay-confirm-modal-back" type="button" id="profileGoBackBtn">Go Back</button>
        </div>
    </div>

    <?php include '../Includes/footer-client.php'; ?>
    <link rel="stylesheet" href="../css/clientprofile.css">
    <script>
    // Custom Toast Logic (copied from register.php)
    function closeToast() {
        var toast = document.getElementById('customToast');
        if (toast) {
            toast.style.opacity = '0';
            setTimeout(function() {
                toast.style.display = 'none';
            }, 300);
        }
    }
    <?php if ($show_toast): ?>
    document.addEventListener('DOMContentLoaded', function() {
        var toast = document.getElementById('customToast');
        toast.style.opacity = '1';
        setTimeout(closeToast, 5000); // Auto-close after 5 seconds
    });
    <?php endif; ?>

    document.addEventListener('DOMContentLoaded', function() {
        var form = document.getElementById('profileForm');
        var cancelBtn = document.getElementById('cancelBtn');
        var saveBtn = document.getElementById('saveBtn');
        var uploadInput = document.getElementById('uploadInput');
        var profilePictureAction = document.getElementById('profile_picture_action');
        var initial = {
            first_name: form.first_name.value,
            last_name: form.last_name.value,
            email: form.email.value,
            contact_no: form.contact_no.value,
            profile_picture: document.getElementById('current_profile_picture').value,
            profile_img: document.getElementById('profileAvatarContainer').innerHTML
        };

        // Helper to check if any field or profile picture changed
        function isChanged() {
            return (
                form.first_name.value !== initial.first_name ||
                form.last_name.value !== initial.last_name ||
                form.email.value !== initial.email ||
                form.contact_no.value !== initial.contact_no ||
                profilePictureAction.value !== ''
            );
        }

        // Show/hide Cancel and Save buttons based on changes
        function updateActionButtons() {
            if (isChanged()) {
                cancelBtn.style.display = '';
                saveBtn.style.display = '';
            } else {
                cancelBtn.style.display = 'none';
                saveBtn.style.display = 'none';
            }
        }

        // Listen for changes on all profile fields
        ['first_name', 'last_name', 'email', 'contact_no'].forEach(function(field) {
            form[field].addEventListener('input', updateActionButtons);
        });

        // Profile picture change listeners
        uploadInput.addEventListener('change', function() {
            profilePictureAction.value = 'upload';
            updateActionButtons();
        });
        document.getElementById('deleteBtn').addEventListener('click', function() {
            profilePictureAction.value = 'delete';
            updateActionButtons();
        });

        // Cancel button logic: reset form to original values
        cancelBtn.addEventListener('click', function(e) {
            form.first_name.value = initial.first_name;
            form.last_name.value = initial.last_name;
            form.email.value = initial.email;
            form.contact_no.value = initial.contact_no;
            profilePictureAction.value = '';
            document.getElementById('current_profile_picture').value = initial.profile_picture;
            document.getElementById('profileAvatarContainer').innerHTML = initial.profile_img;
            uploadInput.value = '';
            updateActionButtons();
        });

        // Profile picture preview logic
        var uploadBtn = document.getElementById('uploadBtn');
        var profileAvatar = document.getElementById('profileAvatarContainer'); // Target the container
        var originalImg = profileAvatar.innerHTML; // Get the current avatar HTML
        var fileToUpload = null;

        uploadBtn.addEventListener('click', function() {
            uploadInput.click();
        });
        uploadInput.addEventListener('change', function() {
            if (uploadInput.files && uploadInput.files[0]) {
                var file = uploadInput.files[0];
                if (!file.type.startsWith('image/')) {
                    alert('Only image files (JPG, PNG, GIF, etc.) are allowed.');
                    uploadInput.value = '';
                    return;
                }
                var reader = new FileReader();
                reader.onload = function(e) {
                    // Swap initials with image live
                    var avatarContainer = document.getElementById('profileAvatarContainer');
                    avatarContainer.innerHTML = '<img src="' + e.target.result + '" alt="Profile Avatar" class="profile-avatar" style="width:56px;height:56px;">';
                };
                reader.readAsDataURL(file);
                profilePictureAction.value = 'upload';
                fileToUpload = file;
            }
        });
        document.getElementById('deleteBtn').addEventListener('click', function() {
            // Swap image with initials live
            var avatarContainer = document.getElementById('profileAvatarContainer');
            avatarContainer.innerHTML = '<div class="profile-avatar-initials" style="width:56px;height:56px;">' + '<?php echo $initials; ?>' + '</div>';
            profilePictureAction.value = 'delete';
            uploadInput.value = '';
            fileToUpload = null;
        });

        // Save button logic with confirmation modal (pay-confirm-modal style)
        var realSubmit = document.getElementById('realSubmit');
        var profileConfirmModal = document.getElementById('profilePayConfirmModal');
        var profileConfirmModalOverlay = document.getElementById('profileConfirmModalOverlay');
        var profileConfirmSaveBtn = document.getElementById('profileConfirmSaveBtn');
        var profileGoBackBtn = document.getElementById('profileGoBackBtn');

        function showProfileConfirmModal() {
            profileConfirmModal.classList.add('show');
            profileConfirmModalOverlay.classList.add('show');
        }
        function hideProfileConfirmModal() {
            profileConfirmModal.classList.remove('show');
            profileConfirmModalOverlay.classList.remove('show');
        }
        saveBtn.addEventListener('click', function(e) {
            // Frontend validation
            var fname = form.first_name.value.trim();
            var lname = form.last_name.value.trim();
            var phone = form.contact_no.value.trim();
            var nameRegex = /^[a-zA-Z ]+$/;
            var phoneRegex = /^[0-9+\-() ]+$/;
            if (!nameRegex.test(fname)) {
                alert('First name must contain only letters and spaces.');
                return false;
            }
            if (!nameRegex.test(lname)) {
                alert('Last name must contain only letters and spaces.');
                return false;
            }
            if (!phoneRegex.test(phone)) {
                alert('Phone number must contain only numbers and allowed symbols.');
                return false;
            }
            showProfileConfirmModal();
        });
        profileGoBackBtn.addEventListener('click', hideProfileConfirmModal);
        profileConfirmModalOverlay.onclick = hideProfileConfirmModal;
        profileConfirmSaveBtn.addEventListener('click', function() {
            // If a new file is selected, append it to the form and submit via JS
            if (profilePictureAction.value === 'upload' && uploadInput.files.length > 0) {
                var fd = new FormData(form);
                fd.delete('profile_picture'); // Remove if any
                fd.append('profile_picture', uploadInput.files[0]);
                fd.set('profile_picture_action', 'upload'); // Ensure action is set
                fd.append('save_profile', '1'); // Ensure backend logic runs
                var xhr = new XMLHttpRequest();
                xhr.open('POST', '', true);
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        location.reload();
                    }
                };
                xhr.send(fd);
            } else {
                realSubmit.click();
            }
            hideProfileConfirmModal();
        });
    });
    </script>
    <!-- Bootstrap JS (optional, for responsive navbar) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


