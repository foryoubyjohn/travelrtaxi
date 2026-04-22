<?php
$pageTitle = 'About Us';
$pageDesc = 'Learn about Travelr Taxi & Tours Services - Jamaica\'s trusted taxi and tour company serving Portmore, Kingston, Spanish Town, and Old Harbour.';
require_once 'includes/header.php';
?>

<section class="page-hero">
    <div class="container">
        <h1>About Us</h1>
        <p>Get to know Jamaica's most trusted taxi and tour service</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="about-grid">
            <div class="about-content">
                <h2>Who We Are</h2>
                <p>Travelr Taxi &amp; Tours Services is Jamaica's premier transportation company, proudly serving the communities of <strong>Portmore, Kingston, Spanish Town, and Old Harbour</strong>. We believe that quality transportation should be accessible to everyone, which is why we've built our business around the promise of affordable, reliable, and safe travel.</p>
                <p>With a fleet of over <strong>100 vehicles</strong> including sedans, 7-seater vans, and mini buses, we're equipped to handle any transportation need — from daily commutes and airport transfers to island-wide tours and special events.</p>
                <p>Our team of professional, licensed drivers are committed to providing you with a comfortable and enjoyable travel experience. Every ride with Travelr Taxi is backed by our dedication to punctuality, safety, and customer satisfaction.</p>
            </div>
            <div class="about-image">
                <img src="/assets/images/promo.jpeg" alt="Travelr Taxi Service">
            </div>
        </div>
    </div>
</section>

<section class="section dark-section">
    <div class="container">
        <div class="section-header light">
            <h2 class="section-title">Our Mission</h2>
        </div>
        <div class="mission-grid">
            <div class="mission-card">
                <div class="mission-icon"><i class="fas fa-bullseye"></i></div>
                <h3>Our Mission</h3>
                <p>To provide safe, reliable, and affordable transportation services that connect communities across Jamaica, making travel accessible to everyone.</p>
            </div>
            <div class="mission-card">
                <div class="mission-icon"><i class="fas fa-eye"></i></div>
                <h3>Our Vision</h3>
                <p>To become Jamaica's leading taxi and tour service, recognized for excellence in customer service, innovation, and community impact.</p>
            </div>
            <div class="mission-card">
                <div class="mission-icon"><i class="fas fa-heart"></i></div>
                <h3>Our Values</h3>
                <p>Safety first, customer satisfaction, reliability, affordability, and respect for our drivers, passengers, and communities.</p>
            </div>
        </div>
    </div>
</section>

<section class="section stats-section">
    <div class="container">
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-number" data-count="100">0</div>
                <div class="stat-label">Vehicles</div>
            </div>
            <div class="stat-item">
                <div class="stat-number" data-count="50">0</div>
                <div class="stat-label">Professional Drivers</div>
            </div>
            <div class="stat-item">
                <div class="stat-number" data-count="10000">0</div>
                <div class="stat-label">Happy Customers</div>
            </div>
            <div class="stat-item">
                <div class="stat-number" data-count="4">0</div>
                <div class="stat-label">Service Areas</div>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="cta-banner">
    <div class="container">
        <div class="cta-content">
            <h2>Ready to Experience the Difference?</h2>
            <p>Book your ride today and see why thousands trust Travelr Taxi!</p>
            <div class="cta-buttons">
                <a href="/booking.php" class="btn btn-primary btn-lg"><i class="fas fa-car"></i> Book a Ride</a>
                <a href="<?php echo getCallLink(); ?>" class="btn btn-light btn-lg"><i class="fas fa-phone"></i> Call Us</a>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
