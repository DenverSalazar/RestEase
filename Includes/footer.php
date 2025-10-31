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
.footer-contact i { font-size: 1rem; margin-right: .5rem; color: inherit; }

/* Footer horizontal alignment: adjust the --footer-h-pad-md value to line up with your testimonials yellow guide */
:root {
	--footer-h-pad-sm: 1rem;   /* small screens */
	--footer-h-pad-md: 4.5rem; /* desktop — tweak this value (e.g. 4rem / 5rem / 120px) to match the yellow line */
}
.footer .container {
	padding-left: var(--footer-h-pad-sm);
	padding-right: var(--footer-h-pad-sm);
}
@media (min-width: 768px) {
	.footer .container {
		padding-left: var(--footer-h-pad-md);
		padding-right: var(--footer-h-pad-md);
	}
}
</style>
<footer class="footer py-5" style="background-color: #03045e; color: white;">
    <div class="container px-4 px-md-5">
        <div class="row align-items-start">
            <!-- Logo and Tagline Section -->
            <div class="col-12 col-md-4 mb-4">
                <img src="./assets/white.png" alt="RestEase Logo" style="height: 35px;">
                <p class="footer-tagline mt-3 mb-0">Honoring memories, simplifying legacy.</p>
                <p class="footer-tagline mb-0">RestEase brings clarity, care, and convenience to every remembrance in Padre Garcia.</p>
            </div>

            <!-- Quick Links Section -->
            <div class="col-12 col-md-3 mb-4 mx-md-auto text-center">
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
                <div class="d-flex gap-3">
                    <a href="https://www.facebook.com/PadreGarcia" class="text-white"><i class="fab fa-facebook"></i></a>
                    <a href="#" class="text-white"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="text-white"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-white"><i class="fab fa-linkedin"></i></a>
                    <a href="#" class="text-white"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
        </div>

        <hr style="border-color: rgba(255,255,255,0.1);">

        <!-- Copyright Section -->
        <div class="row pt-3">
            <div class="col-12 col-md-6 text-center text-md-start">
                <p class="mb-0">&copy; 2025 RestEase. All rights reserved.</p>
            </div>
            <div class="col-12 col-md-6 text-center text-md-end">
                <p class="mb-0">Designed By: RestEase Team.</p>
            </div>
        </div>
    </div>
</footer>