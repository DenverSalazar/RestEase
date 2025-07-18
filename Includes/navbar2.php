<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include_once '../Includes/db.php';
$user_avatar_html = '';
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $stmt = $conn->prepare('SELECT first_name, last_name, profile_picture FROM users WHERE id = ?');
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $stmt->bind_result($fn, $ln, $pp);
    $stmt->fetch();
    $stmt->close();
    $has_profile_picture = $pp && file_exists('../uploads/' . $pp);
    $initials = strtoupper(substr($fn, 0, 1) . substr($ln, 0, 1));
    if ($has_profile_picture) {
        $user_avatar_html = '<img src="../uploads/' . htmlspecialchars($pp) . '" alt="Avatar" class="navbar-avatar" style="width:36px;height:36px;border-radius:50%;object-fit:cover;border:2px solid #b2c9db;">';
    } else {
        $user_avatar_html = '<div class="navbar-avatar-initials" style="width:36px;height:36px;border-radius:50%;background:#4B7BEC;color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.1rem;font-weight:700;letter-spacing:1px;user-select:none;border:2px solid #b2c9db;">' . $initials . '</div>';
    }
}
?>
<!-- Custom Navbar -->
<nav class="custom-navbar position-relative">
    <div class="container navbar-top position-relative">
        <a href="#" class="navbar-brand">
            <img src="../assets/RE logo New.png" alt="RestEase Logo" style="height: 32px;">
        </a>
        <button class="navbar-toggler" type="button" aria-label="Toggle navigation" onclick="toggleMobileMenu()">
            <i class="fas fa-bars"></i>
        </button>
        <div class="navbar-links">
            <button class="navbar-close" type="button" aria-label="Close menu" onclick="toggleMobileMenu()">
                <i class="fas fa-times"></i>
            </button>
            <a href="ClientHome.php">Home</a>
            <a href="./clientabout-us.php">About Us</a>
            <a href="./clientcontact-us.php">Contact Us</a>
            <a href="#"><i class="fas fa-bell"></i></a>
            <a href="#"><?php echo $user_avatar_html; ?></a>
        </div>
    </div>
    <div class="navbar-overlay" onclick="toggleMobileMenu()"></div>
</nav>
<!-- End Custom Navbar -->
<script>
function toggleMobileMenu() {
    var links = document.querySelector('.navbar-links');
    var overlay = document.querySelector('.navbar-overlay');
    links.classList.toggle('show');
    overlay.classList.toggle('show');
}
</script>
