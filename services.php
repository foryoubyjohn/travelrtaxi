<?php
$pageTitle = 'Our Services';
$pageDesc = 'Explore our range of taxi and tour services including standard rides, airport transfers, island tours, and chauffeur services across Jamaica.';
require_once 'includes/header.php';
?>

<section class="page-hero">
    <div class="container">
        <h1>Our Services</h1>
        <p>Professional transportation solutions for every need</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="services-detail-grid">

            <div class="service-detail-card" id="standard">
                <div class="service-detail-icon"><i class="fas fa-taxi"></i></div>
                <div class="service-detail-content">
                    <h2>Standard Taxi Service</h2>
                    <p>Our core service provides reliable point-to-point transportation across our primary service areas. Whether you need a ride to work, the market, a doctor's appointment, or anywhere in between, our professional drivers are ready to take you there safely and on time.</p>
                    <ul class="service-features">
                        <li><i class="fas fa-check"></i> Available 24/7</li>
                        <li><i class="fas fa-check"></i> Portmore, Kingston, Spanish Town &amp; Old Harbour</li>
                        <li><i class="fas fa-check"></i> Metered or flat-rate pricing</li>
                        <li><i class="fas fa-check"></i> Air-conditioned vehicles</li>
                        <li><i class="fas fa-check"></i> Professional, licensed drivers</li>
                    </ul>
                    <a href="/booking.php?service=standard" class="btn btn-primary">Book Standard Ride</a>
                </div>
            </div>

            <div class="service-detail-card" id="airport">
                <div class="service-detail-icon"><i class="fas fa-plane-arrival"></i></div>
                <div class="service-detail-content">
                    <h2>Airport Transfers</h2>
                    <p>Start or end your trip stress-free with our reliable airport transfer service. We provide pickups and drop-offs at Norman Manley International Airport (KIN) with meet-and-greet service, flight tracking, and competitive flat rates.</p>
                    <ul class="service-features">
                        <li><i class="fas fa-check"></i> Norman Manley Airport (KIN)</li>
                        <li><i class="fas fa-check"></i> Flight tracking &amp; monitoring</li>
                        <li><i class="fas fa-check"></i> Meet &amp; greet service</li>
                        <li><i class="fas fa-check"></i> Fixed rates — no surprises</li>
                        <li><i class="fas fa-check"></i> Luggage assistance</li>
                    </ul>
                    <a href="/booking.php?service=airport" class="btn btn-primary">Book Airport Transfer</a>
                </div>
            </div>

            <div class="service-detail-card" id="tours">
                <div class="service-detail-icon"><i class="fas fa-umbrella-beach"></i></div>
                <div class="service-detail-content">
                    <h2>Island Tours</h2>
                    <p>Discover the beauty of Jamaica with our guided island-wide tour packages. From the Blue Mountains to Dunn's River Falls, our knowledgeable drivers will take you on unforgettable adventures across the island.</p>
                    <ul class="service-features">
                        <li><i class="fas fa-check"></i> Full-day &amp; half-day tours</li>
                        <li><i class="fas fa-check"></i> Popular attractions &amp; hidden gems</li>
                        <li><i class="fas fa-check"></i> Customizable itineraries</li>
                        <li><i class="fas fa-check"></i> Knowledgeable local drivers</li>
                        <li><i class="fas fa-check"></i> Group packages available</li>
                    </ul>
                    <div class="tour-destinations">
                        <h4>Popular Destinations:</h4>
                        <div class="destination-tags">
                            <span>Ocho Rios</span>
                            <span>Montego Bay</span>
                            <span>Negril</span>
                            <span>Port Antonio</span>
                            <span>Blue Mountains</span>
                            <span>Dunn's River Falls</span>
                            <span>Bob Marley Museum</span>
                            <span>Devon House</span>
                        </div>
                    </div>
                    <a href="/booking.php?service=tour" class="btn btn-primary">Book a Tour</a>
                </div>
            </div>

            <div class="service-detail-card" id="chauffeur">
                <div class="service-detail-icon"><i class="fas fa-user-tie"></i></div>
                <div class="service-detail-content">
                    <h2>Chauffeur / Hourly Service</h2>
                    <p>Need a driver for the day? Our hourly chauffeur service is perfect for business meetings, wedding transportation, shopping trips, or any occasion where you need a dedicated driver at your disposal.</p>
                    <ul class="service-features">
                        <li><i class="fas fa-check"></i> Dedicated driver for your schedule</li>
                        <li><i class="fas fa-check"></i> Hourly billing — pay only for time used</li>
                        <li><i class="fas fa-check"></i> Professional, well-dressed drivers</li>
                        <li><i class="fas fa-check"></i> Ideal for events &amp; business</li>
                        <li><i class="fas fa-check"></i> Multiple vehicle options</li>
                    </ul>
                    <a href="/booking.php?service=hourly" class="btn btn-primary">Book Chauffeur</a>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- CTA -->
<section class="cta-banner">
    <div class="container">
        <div class="cta-content">
            <h2>Need a Custom Solution?</h2>
            <p>Contact us for corporate accounts, event transportation, or custom tour packages.</p>
            <div class="cta-buttons">
                <a href="/contact.php" class="btn btn-primary btn-lg"><i class="fas fa-envelope"></i> Contact Us</a>
                <a href="<?php echo getWhatsAppLink(); ?>" class="btn btn-whatsapp btn-lg" target="_blank"><i class="fab fa-whatsapp"></i> WhatsApp</a>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
