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
            <a href="#"><img src="../assets/Default Image.jpg" alt="Avatar" class="navbar-avatar"></a>
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
