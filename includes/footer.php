<!-- Footer -->
<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-col">
                <img src="/assets/images/logo.jpeg" alt="<?php echo SITE_NAME; ?>" class="footer-logo">
                <p class="footer-tagline">"<?php echo SITE_TAGLINE; ?>"</p>
                <p>Your trusted taxi and tour service across Jamaica. Safe, reliable, and affordable transportation.</p>
            </div>
            <div class="footer-col">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="/">Home</a></li>
                    <li><a href="/about.php">About Us</a></li>
                    <li><a href="/services.php">Services</a></li>
                    <li><a href="/fleet.php">Our Fleet</a></li>
                    <li><a href="/booking.php">Book a Ride</a></li>
                    <li><a href="/pricing.php">Pricing</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Service Areas</h4>
                <ul>
                    <li><i class="fas fa-map-marker-alt"></i> Portmore</li>
                    <li><i class="fas fa-map-marker-alt"></i> Kingston</li>
                    <li><i class="fas fa-map-marker-alt"></i> Spanish Town</li>
                    <li><i class="fas fa-map-marker-alt"></i> Old Harbour</li>
                    <li><i class="fas fa-globe-americas"></i> Island-Wide Tours</li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Contact Us</h4>
                <ul>
                    <li><a href="<?php echo getCallLink(); ?>"><i class="fas fa-phone"></i> <?php echo PHONE_PRIMARY; ?></a></li>
                    <li><a href="<?php echo getWhatsAppLink(); ?>"><i class="fab fa-whatsapp"></i> <?php echo PHONE_WHATSAPP; ?></a></li>
                    <li><a href="mailto:<?php echo SITE_EMAIL; ?>"><i class="fas fa-envelope"></i> <?php echo SITE_EMAIL; ?></a></li>
                    <li><i class="fas fa-map-pin"></i> Kingston, Jamaica</li>
                </ul>
                <div class="footer-social">
                    <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
        </div>
    </div>
</footer>

<!-- WhatsApp Floating Button -->
<a href="<?php echo getWhatsAppLink(); ?>" class="whatsapp-float" target="_blank" rel="noopener" aria-label="Chat on WhatsApp">
    <i class="fab fa-whatsapp"></i>
    <span class="whatsapp-tooltip">Book on WhatsApp</span>
</a>

<!-- Back to Top -->
<button class="back-to-top" id="backToTop" aria-label="Back to top">
    <i class="fas fa-chevron-up"></i>
</button>

<script src="/assets/js/main.js"></script>
<?php if (isset($extraJS)) echo $extraJS; ?>
</body>
</html>
