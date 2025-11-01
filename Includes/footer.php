<!-- Add Font Awesome CDN for social icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<!-- Add: consistent contact + tagline text style -->
<style>
/* Consistent styling for contact info lines and taglines */
.footer-contact,
.footer-tagline {
	font-size: 0.95rem; /* adjust if needed */
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
	font-weight: 300;       /* lighter / thin */
	opacity: 0.85;          /* slightly muted */
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

/* horizontal footer rule */
.footer-hr {
	border: 0;
	border-top: 1px solid rgba(255,255,255,0.14); /* thin white line */
	margin: 2.5rem 0 1.5rem 0; /* spacing above/below */
	width: 100%;
	opacity: 1;
}

/* remove vertical separator, no .footer-sep needed */
/* remove separator on small screens */
@media (max-width: 767.98px) {
	.footer-sep { border-left: none; padding-left: 0; margin-left: 0; }
}
@media (max-width: 576px) {
	.footer-logo { height: 40px; } /* smaller on mobile */
}

/* Align footer content to the page grid: shift container left on large screens only.
   Tweak the -24px value until the vertical edges align exactly with your guideline. */
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
                    <img src="./assets/white.png" alt="RestEase Logo" class="footer-logo">
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
                        <li class="mb-2"><a href="index.php" class="text-white text-decoration-none">Home</a></li>
                        <li class="mb-2"><a href="about-us.php" class="text-white text-decoration-none">About Us</a></li>
                        <li class="mb-2"><a href="termscondtion.php" class="text-white text-decoration-none">Terms & Conditions</a></li>
                        <li><a href="privacy_policy.php" class="text-white text-decoration-none">Privacy Policy</a></li>
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