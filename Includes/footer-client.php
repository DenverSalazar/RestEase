<!-- Add Font Awesome CDN for social icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<!-- Add: consistent contact + tagline text style -->
<style>
/* Consistent styling for contact info lines and taglines */
.footer-contact,
.footer-tagline {
	font-size: 0.95rem;
	font-weight: 400;
	font-family: "Segoe UI", Roboto, Arial, sans-serif;
	opacity: 0.7;
	margin-bottom: 0.5rem;
}
/* Title for logo area */
.footer-title {
	font-size: 1.25rem;
	font-weight: 700;
	margin-bottom: .25rem;
	font-family: "Segoe UI", Roboto, Arial, sans-serif;
	color: #ffffff;
}
/* subtitle under the title */
.footer-subtitle {
	font-size: 0.95rem;
	font-weight: 400;
	opacity: 0.85;
	margin: 0;
	font-family: "Segoe UI", Roboto, Arial, sans-serif;
	color: rgba(255,255,255,0.95);
}
/* Make quick-links text light / thin */
.footer .list-unstyled a {
	font-weight: 300;                        /* lighter / thin */
	color: rgba(255,255,255,0.95) !important; /* keep white but slightly muted */
	opacity: 0.95;
	font-family: "Segoe UI", Roboto, Arial, sans-serif;
}
/* Make copyright / designer text light & thin */
.footer-credit {
	font-weight: 300;
	opacity: 0.85;
	font-family: "Segoe UI", Roboto, Arial, sans-serif;
	-webkit-font-smoothing: antialiased;
	-moz-osx-font-smoothing: grayscale;
}
.footer-contact i { font-size: 1rem; margin-right: .5rem; color: inherit; }
/* make logo bigger and slightly lighter/brighter */
.footer-logo {
	height: 48px;               /* bigger on desktop */
	width: auto;
	filter: brightness(1.12);   /* slightly lighter */
	transition: transform .15s ease;
}
@media (max-width: 576px) {
	.footer-logo { height: 40px; } /* smaller on mobile */
}

/* horizontal footer rule */
.footer-hr {
	border: 0;
	border-top: 1px solid rgba(255,255,255,0.14);
	margin: 2.5rem 0 1.5rem 0;
	width: 100%;
	opacity: 1;
}

/* Add: Align footer content to the page grid on large screens only.
   Tweak -24px to match your guide exactly. */
@media (min-width: 992px) {
	.footer .container {
		position: relative;
		left: -24px; /* adjust this value to fine-tune alignment */
	}
}
</style>

<footer class="footer py-5" style="background-color: #03045e; color: white;">
    <div class="container">
        <div class="row align-items-start">
            <!-- Logo and Title + Taglines Section -->
            <div class="col-12 col-md-4 mb-4">
                <div class="d-flex align-items-center">
                    <img src="../assets/white.png" alt="RestEase Logo" class="footer-logo">
                    <div class="ms-3">
                        <h4 class="footer-title mb-0">RestEase</h4>
                        <p class="footer-subtitle mb-0">MPDO</p>
                    </div>
                </div>
                <p class="footer-tagline mt-3 mb-0">Honoring memories, simplifying legacy.</p>
                <p class="footer-tagline mb-0">RestEase brings clarity, care, and convenience to every remembrance in Padre Garcia.</p>
            </div>

            <!-- Quick Links Section -->
            <div class="col-12 col-md-3 mb-4 mx-md-auto text-md-center">
                <div class="d-inline-block text-start">
                    <h5 class="mb-3" style="opacity: 0.7;">Quick Links</h5>
                    <ul class="list-unstyled ps-0 mb-0">
                        <li class="mb-2"><a href="ClientHome.php" class="text-white text-decoration-none">Home</a></li>
                        <li class="mb-2"><a href="clientabout-us.php" class="text-white text-decoration-none">About Us</a></li>
                        <li class="mb-2"><a href="client-termscondtion.php" class="text-white text-decoration-none">Terms & Conditions</a></li>
                        <li><a href="client-privacy_policy.php" class="text-white text-decoration-none">Privacy Policy</a></li>
                    </ul>
                </div>
            </div>

            <!-- Contact Info Section -->
            <div class="col-12 col-md-auto mb-4 ms-md-auto text-md-start">
                <h5 class="mb-3" style="opacity: 0.7;">Contact Info</h5>
                <p class="footer-contact mb-2"><i class="fas fa-map-marker-alt"></i>V6MF+8JH, Banaba, Padre Garcia, Batangas</p>
                <p class="footer-contact mb-2"><i class="fas fa-envelope"></i>resteasempdo@gmail.com</p>
                <p class="footer-contact mb-4"><i class="fas fa-phone"></i>+0923-456-789</p>
                
                <!-- Social Media Icons -->
                <!-- Social media icons removed -->
            </div>
        </div>

        <hr class="footer-hr">

        <!-- Copyright Section -->
        <div class="row pt-3">
            <div class="col-12 col-md-6 text-center text-md-start">
                <p class="mb-0 footer-credit">&copy; 2025 RestEase. All rights reserved.</p>
            </div>
            <div class="col-12 col-md-6 text-center text-md-end">
                <p class="mb-0 footer-credit">Designed By: RestEase Team.</p>
            </div>
        </div>
    </div>
</footer>