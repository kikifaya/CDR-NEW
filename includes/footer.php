<?php if ($currentPage == "admin") { ?>
        </div>
    </div>
<?php } else { ?>
    <footer class="site-footer" id="newsletter">
        <div class="footer-ghost" aria-hidden="true">C</div>
        <div class="container">
            <div class="footer-intro">
                <img class="footer-mark" src="images/logo-mark-white.png" alt="">
                <p class="footer-statement">Côte d'Or Retreat, a family-run home on <em>Praslin</em>.</p>
            </div>

            <div class="row g-5 footer-grid">
                <div class="col-12 col-lg-4">
                    <p class="eyebrow">Island letters</p>
                    <p class="footer-text">Seasonal offers, new rooms and quiet escapes. A few emails a year, never more.</p>

                    <form class="newsletter-form" action="subscribe.php" method="POST">
                        <label for="newsletterEmail" class="visually-hidden">Your email address</label>
                        <input type="email" class="form-control" id="newsletterEmail" name="email" placeholder="Your email address" required>
                        <button type="submit" class="btn btn-lux">Subscribe</button>
                    </form>
                </div>

                <div class="col-6 col-lg-2 offset-lg-1">
                    <h6 class="footer-heading">Explore</h6>
                    <ul class="footer-links">
                        <li><a href="index.php">Home</a></li>
                        <li><a href="rooms.php">Rooms</a></li>
                        <li><a href="experiences.html">Experiences</a></li>
                        <li><a href="gallery.php">Gallery</a></li>
                        <li><a href="booking.php">Book a stay</a></li>
                    </ul>
                </div>

                <div class="col-6 col-lg-2">
                    <h6 class="footer-heading">Guests</h6>
                    <ul class="footer-links">
                        <li><a href="login.php">Log in</a></li>
                        <li><a href="register.php">Create account</a></li>
                        <li><a href="my_bookings.php">My bookings</a></li>
                        <li><a href="account.php">My account</a></li>
                        <li><a href="policies.html">Hotel policies</a></li>
                    </ul>
                </div>

                <div class="col-12 col-lg-3">
                    <h6 class="footer-heading">Visit</h6>
                    <p class="footer-text">
                        Anse Volbert, Praslin<br>
                        Seychelles<br>
                        <a href="tel:<?php echo htmlspecialchars(str_replace(" ", "", $settings["hotel_phone"])); ?>"><?php echo htmlspecialchars($settings["hotel_phone"]); ?></a><br>
                        <a href="mailto:<?php echo htmlspecialchars($settings["hotel_email"]); ?>" class="footer-email"><?php echo htmlspecialchars($settings["hotel_email"]); ?></a>
                    </p>
                    <ul class="footer-social">
                        <li><a href="https://www.instagram.com/" target="_blank" rel="noopener">Instagram</a></li>
                        <li><a href="https://www.facebook.com/" target="_blank" rel="noopener">Facebook</a></li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <span>&copy; <?php echo date("Y"); ?> Côte d'Or Retreat</span>
                <span>Check-in <?php echo htmlspecialchars($settings["checkin_time"]); ?> &middot; Check-out <?php echo htmlspecialchars($settings["checkout_time"]); ?></span>
                <a href="#" onclick="window.scrollTo({ top: 0, behavior: 'smooth' }); return false;">Back to top &uarr;</a>
            </div>
        </div>
    </footer>

    <!-- the small label that follows the mouse over photos you can click (see setupCursorLabel() in script.js) -->
    <div class="cursor-label" id="cursorLabel" aria-hidden="true"></div>

    <a class="whatsapp-float" href="https://wa.me/<?php echo whatsappNumber($settings); ?>?text=Hello%20C%C3%B4te%20d%27Or%20Retreat%2C%20I%20have%20a%20question%20about%20a%20stay." target="_blank" rel="noopener" aria-label="Chat with us on WhatsApp">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm0 18.2a8.2 8.2 0 0 1-4.2-1.1l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.2-.4.2-.4.7-1.3.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.7 11.8 11.8 0 0 0 4.5 4c1.7.7 2.3.8 3.2.6.5-.1 1.5-.6 1.7-1.2.2-.6.2-1.1.2-1.2-.1-.1-.3-.2-.5-.3z"/></svg>
        <span>WhatsApp us</span>
    </a>
<?php } ?>

    <script src="js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js"></script>
</body>
</html>
