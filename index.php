<?php
$pageTitle = 'Home';
$pageDesc = 'Travelr Taxi & Tours Services - The Affordable Way To Travel. Book reliable taxi service across Kingston, Portmore, Spanish Town, Old Harbour and island-wide tours in Jamaica.';
require_once 'includes/header.php';

// Fetch testimonials
$testimonials = dbFetchAll("SELECT * FROM testimonials WHERE is_approved = 1 ORDER BY created_at DESC LIMIT 6");
// Fetch fleet counts
$fleetCounts = dbFetchAll("SELECT type, COUNT(*) as count FROM vehicles WHERE status = 'active' GROUP BY type");
$fleetMap = [];
foreach ($fleetCounts as $fc) { $fleetMap[$fc['type']] = $fc['count']; }
?>

<!-- Hero Section -->
<section class="hero" id="hero">
    <div class="hero-overlay"></div>
    <div class="hero-particles" id="heroParticles"></div>
    <div class="container hero-content">
        <div class="hero-text">
            <img src="/assets/images/logo.jpeg" alt="<?php echo SITE_NAME; ?>" class="hero-logo animate-fadeInDown">
            <h1 class="animate-fadeInUp"><?php echo SITE_NAME; ?></h1>
            <p class="hero-tagline animate-fadeInUp delay-1">"<?php echo SITE_TAGLINE; ?>"</p>
            <p class="hero-sub animate-fadeInUp delay-2">Reliable taxi service &amp; island-wide tours across Jamaica</p>
            <div class="hero-cta animate-fadeInUp delay-3">
                <a href="/booking.php" class="btn btn-primary btn-lg pulse-glow"><i class="fas fa-car"></i> Book a Ride</a>
                <a href="<?php echo getWhatsAppLink(); ?>" class="btn btn-whatsapp btn-lg" target="_blank"><i class="fab fa-whatsapp"></i> WhatsApp Us</a>
                <a href="<?php echo getCallLink(); ?>" class="btn btn-dark btn-lg"><i class="fas fa-phone"></i> Call Now</a>
            </div>
        </div>
        <div class="hero-booking-widget animate-fadeInRight delay-2">
            <h3><i class="fas fa-taxi"></i> Quick Booking</h3>
            <form action="/booking.php" method="GET" class="quick-book-form">
                <div class="form-group">
                    <label><i class="fas fa-map-marker-alt text-red"></i> Pickup</label>
                    <input type="text" name="pickup" placeholder="Enter pickup location" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-map-pin text-green"></i> Drop-off</label>
                    <input type="text" name="dropoff" placeholder="Enter destination" required>
                </div>
                <div class="form-row">
                    <div class="form-group half">
                        <label><i class="fas fa-calendar"></i> Date</label>
                        <input type="date" name="date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group half">
                        <label><i class="fas fa-clock"></i> Time</label>
                        <input type="time" name="time" value="<?php echo date('H:i'); ?>" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-search"></i> Get Quote &amp; Book</button>
            </form>
        </div>
    </div>
    <div class="hero-scroll">
        <a href="#services-preview"><i class="fas fa-chevron-down"></i></a>
    </div>
</section>

<!-- Service Areas Ticker -->
<div class="ticker-bar">
    <div class="ticker-content">
        <span><i class="fas fa-map-marker-alt"></i> Portmore</span>
        <span><i class="fas fa-map-marker-alt"></i> Kingston</span>
        <span><i class="fas fa-map-marker-alt"></i> Spanish Town</span>
        <span><i class="fas fa-map-marker-alt"></i> Old Harbour</span>
        <span><i class="fas fa-globe-americas"></i> Island-Wide Tours</span>
        <span><i class="fas fa-plane-arrival"></i> Airport Transfers</span>
        <span><i class="fas fa-map-marker-alt"></i> Portmore</span>
        <span><i class="fas fa-map-marker-alt"></i> Kingston</span>
        <span><i class="fas fa-map-marker-alt"></i> Spanish Town</span>
        <span><i class="fas fa-map-marker-alt"></i> Old Harbour</span>
        <span><i class="fas fa-globe-americas"></i> Island-Wide Tours</span>
        <span><i class="fas fa-plane-arrival"></i> Airport Transfers</span>
    </div>
</div>

<!-- Services Preview -->
<section class="section services-preview" id="services-preview">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Our Services</h2>
            <p class="section-subtitle">Professional transportation solutions for every need</p>
        </div>
        <div class="services-grid">
            <div class="service-card" data-aos="fade-up">
                <div class="service-icon"><i class="fas fa-taxi"></i></div>
                <h3>Standard Taxi</h3>
                <p>Reliable point-to-point taxi service across Kingston, Portmore, Spanish Town, and Old Harbour.</p>
                <a href="/services.php" class="btn btn-outline">Learn More</a>
            </div>
            <div class="service-card" data-aos="fade-up" data-delay="100">
                <div class="service-icon"><i class="fas fa-plane-arrival"></i></div>
                <h3>Airport Transfers</h3>
                <p>Comfortable airport pickup and drop-off at Norman Manley International Airport.</p>
                <a href="/services.php" class="btn btn-outline">Learn More</a>
            </div>
            <div class="service-card" data-aos="fade-up" data-delay="200">
                <div class="service-icon"><i class="fas fa-umbrella-beach"></i></div>
                <h3>Island Tours</h3>
                <p>Explore Jamaica's beautiful attractions with our guided island-wide tour packages.</p>
                <a href="/services.php" class="btn btn-outline">Learn More</a>
            </div>
            <div class="service-card" data-aos="fade-up" data-delay="300">
                <div class="service-icon"><i class="fas fa-user-tie"></i></div>
                <h3>Chauffeur Service</h3>
                <p>Premium hourly chauffeur service for business meetings, events, and special occasions.</p>
                <a href="/services.php" class="btn btn-outline">Learn More</a>
            </div>
        </div>
    </div>
</section>

<!-- Why Choose Us -->
<section class="section why-us dark-section">
    <div class="container">
        <div class="section-header light">
            <h2 class="section-title">Why Choose Travelr Taxi?</h2>
            <p class="section-subtitle">Jamaica's most trusted taxi and tour service</p>
        </div>
        <div class="features-grid">
            <div class="feature-item">
                <div class="feature-number">01</div>
                <div class="feature-icon"><i class="fas fa-dollar-sign"></i></div>
                <h3>Affordable Rates</h3>
                <p>Competitive pricing with no hidden charges. Know your fare before you ride.</p>
            </div>
            <div class="feature-item">
                <div class="feature-number">02</div>
                <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
                <h3>Safe &amp; Secure</h3>
                <p>Licensed drivers, well-maintained vehicles, and full insurance coverage.</p>
            </div>
            <div class="feature-item">
                <div class="feature-number">03</div>
                <div class="feature-icon"><i class="fas fa-clock"></i></div>
                <h3>Always On Time</h3>
                <p>Punctual pickups and reliable service, 24 hours a day, 7 days a week.</p>
            </div>
            <div class="feature-item">
                <div class="feature-number">04</div>
                <div class="feature-icon"><i class="fas fa-car"></i></div>
                <h3>Large Fleet</h3>
                <p>Over 100 vehicles including sedans, vans, and mini buses for any group size.</p>
            </div>
            <div class="feature-item">
                <div class="feature-number">05</div>
                <div class="feature-icon"><i class="fas fa-mobile-alt"></i></div>
                <h3>Easy Booking</h3>
                <p>Book online, by phone, or WhatsApp. Quick and hassle-free.</p>
            </div>
            <div class="feature-item">
                <div class="feature-number">06</div>
                <div class="feature-icon"><i class="fas fa-star"></i></div>
                <h3>5-Star Service</h3>
                <p>Professional, friendly drivers committed to your comfort and satisfaction.</p>
            </div>
        </div>
    </div>
</section>

<!-- Fleet Preview -->
<section class="section fleet-preview">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Our Fleet</h2>
            <p class="section-subtitle">Modern, well-maintained vehicles for your comfort</p>
        </div>
        <div class="fleet-categories">
            <div class="fleet-cat-card">
                <div class="fleet-cat-icon"><i class="fas fa-car"></i></div>
                <h3>Sedans</h3>
                <p class="fleet-count"><?php echo $fleetMap['sedan'] ?? 60; ?>+ Vehicles</p>
                <p>Comfortable 4-passenger sedans perfect for daily commutes and airport transfers.</p>
                <a href="/fleet.php" class="btn btn-outline">View Fleet</a>
            </div>
            <div class="fleet-cat-card">
                <div class="fleet-cat-icon"><i class="fas fa-shuttle-van"></i></div>
                <h3>7-Seater Vans</h3>
                <p class="fleet-count"><?php echo $fleetMap['van'] ?? 30; ?>+ Vehicles</p>
                <p>Spacious vans ideal for families, groups, and extra luggage.</p>
                <a href="/fleet.php" class="btn btn-outline">View Fleet</a>
            </div>
            <div class="fleet-cat-card">
                <div class="fleet-cat-icon"><i class="fas fa-bus"></i></div>
                <h3>Mini Buses</h3>
                <p class="fleet-count"><?php echo $fleetMap['minibus'] ?? 10; ?>+ Vehicles</p>
                <p>Toyota HiAce and Coaster buses for large groups, tours, and events.</p>
                <a href="/fleet.php" class="btn btn-outline">View Fleet</a>
            </div>
        </div>
    </div>
</section>

<!-- CTA Banner -->
<section class="cta-banner">
    <div class="container">
        <div class="cta-content">
            <h2>Ready to Ride?</h2>
            <p>Book your taxi or tour now and experience the affordable way to travel Jamaica!</p>
            <div class="cta-buttons">
                <a href="/booking.php" class="btn btn-primary btn-lg"><i class="fas fa-car"></i> Book Online</a>
                <a href="<?php echo getCallLink(); ?>" class="btn btn-light btn-lg"><i class="fas fa-phone"></i> <?php echo PHONE_PRIMARY; ?></a>
                <a href="<?php echo getWhatsAppLink(); ?>" class="btn btn-whatsapp btn-lg" target="_blank"><i class="fab fa-whatsapp"></i> WhatsApp</a>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials -->
<?php if (!empty($testimonials)): ?>
<section class="section testimonials-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">What Our Customers Say</h2>
            <p class="section-subtitle">Real reviews from real riders</p>
        </div>
        <div class="testimonials-slider" id="testimonialsSlider">
            <?php foreach ($testimonials as $t): ?>
            <div class="testimonial-card">
                <div class="testimonial-stars">
                    <?php for ($i = 0; $i < $t['rating']; $i++): ?><i class="fas fa-star"></i><?php endfor; ?>
                </div>
                <p class="testimonial-text">"<?php echo sanitize($t['message']); ?>"</p>
                <div class="testimonial-author">
                    <div class="testimonial-avatar"><i class="fas fa-user-circle"></i></div>
                    <div>
                        <strong><?php echo sanitize($t['customer_name']); ?></strong>
                        <span><?php echo sanitize($t['location']); ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
