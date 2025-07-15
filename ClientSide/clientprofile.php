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
    <link rel="stylesheet" href="../css/clientprofile.css">
</head>
<body>
   <?php include '../Includes/navbar2.php'; ?>

    <div class="profile-container">
        <div class="profile-header"></div>
        <div class="profile-content">
            <h2>My Profile</h2>
            <p class="subtitle">Real-time information and activities of your property.</p>
            <div class="profile-main">
                <div class="profile-avatar-section">
                    <img src="../assets/abt1.jpg" alt="Profile Avatar" class="profile-avatar">
                    <div class="profile-info">
                        <div class="profile-name">Dysania</div>
                        <div class="profile-email">dysania@gmail.com</div>
                    </div>
                    <div class="profile-buttons">
                        <button class="btn-upload">Upload new picture</button>
                        <button class="btn-delete">Delete</button>
                    </div>
                </div>
                <form class="profile-form">
                    <div class="form-section">
                        <label>Personal Information</label>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="firstName">First Name</label>
                                <input type="text" id="firstName" value="Dysania">
                            </div>
                            <div class="form-group">
                                <label for="lastName">Last Name</label>
                                <input type="text" id="lastName" value="Beans">
                            </div>
                        </div>
                    </div>
                    <div class="form-section">
                        <label>Contact Email</label>
                        <span class="form-desc">Manage your contact email address here</span>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" value="dysania@gmail.com">
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="text" id="phone" value="+976 631 2517">
                            </div>
                        </div>
                    </div>
                    <div class="form-section">
                        <label>Password</label>
                        <span class="form-desc">Modify your password</span>
                        <div class="form-group password-group">
                            <label for="currentPassword">Current password</label>
                            <div class="password-wrapper">
                                <input type="password" id="currentPassword" value="dysania123">
                                <span class="toggle-password" onclick="togglePassword()"><i class="fas fa-eye"></i></span>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    <link rel="stylesheet" href="../css/clientprofile.css">
    <script>
    function togglePassword() {
        var pwd = document.getElementById('currentPassword');
        var icon = document.querySelector('.toggle-password i');
        if (pwd.type === 'password') {
            pwd.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            pwd.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
    </script>
    <!-- Bootstrap JS (optional, for responsive navbar) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
