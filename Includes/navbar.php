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
        <div class="navbar-bottom">
            <div class="container d-flex align-items-center justify-content-between flex-wrap">
                <form class="d-flex flex-grow-1" role="search">
                    <div class="input-group search-group">
                        <span class="input-group-text">
                            <i class="fas fa-search" style="color: #6c757d;"></i>
                        </span>
                        <input class="form-control" type="search" placeholder="Search anything..." aria-label="Search">
                    </div>
                </form>
                <div class="dropdown status-dropdown">
                    <button class="status-btn dropdown-toggle" type="button" id="statusDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        Status
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="statusDropdown">
                        <li><a class="dropdown-item" href="#">Approved</a></li>
                        <li><a class="dropdown-item" href="#">Pending</a></li>
                        <li><a class="dropdown-item" href="#">Rejected</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
    <!-- End Custom Navbar -->
