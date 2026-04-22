<?php
$pageTitle = 'Page Not Found';
require_once 'includes/header.php';
?>

<section class="page-hero">
    <div class="container">
        <h1>404 - Page Not Found</h1>
        <p>The page you're looking for doesn't exist or has been moved.</p>
    </div>
</section>

<section class="section">
    <div class="container text-center">
        <div class="empty-state">
            <i class="fas fa-map-signs"></i>
            <h3>Looks like you took a wrong turn!</h3>
            <p>Don't worry, we'll get you back on track.</p>
            <div style="display:flex; justify-content:center; gap:15px; margin-top:20px; flex-wrap:wrap;">
                <a href="/" class="btn btn-primary"><i class="fas fa-home"></i> Go Home</a>
                <a href="/booking.php" class="btn btn-dark"><i class="fas fa-car"></i> Book a Ride</a>
                <a href="/contact.php" class="btn btn-outline"><i class="fas fa-phone"></i> Contact Us</a>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
