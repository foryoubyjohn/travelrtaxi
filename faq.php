<?php
$pageTitle = 'FAQ';
$pageDesc = 'Frequently asked questions about Travelr Taxi & Tours Services. Find answers about booking, pricing, service areas, and more.';
require_once 'includes/header.php';
?>

<section class="page-hero">
    <div class="container">
        <h1>Frequently Asked Questions</h1>
        <p>Everything you need to know about our services</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="faq-container">

            <div class="faq-category">
                <h2><i class="fas fa-car"></i> Booking &amp; Rides</h2>

                <div class="faq-item">
                    <button class="faq-question">
                        How do I book a ride?
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        <p>You can book a ride in three easy ways:</p>
                        <ul>
                            <li><strong>Online:</strong> Use our <a href="/booking.php">online booking form</a></li>
                            <li><strong>WhatsApp:</strong> Message us at <a href="<?php echo getWhatsAppLink(); ?>"><?php echo PHONE_WHATSAPP; ?></a></li>
                            <li><strong>Phone:</strong> Call us at <a href="<?php echo getCallLink(); ?>"><?php echo PHONE_PRIMARY; ?></a></li>
                        </ul>
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question">
                        How far in advance should I book?
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        <p>We recommend booking at least 2 hours in advance for standard rides and 24 hours for airport transfers and tours. However, we also accommodate last-minute bookings when drivers are available.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question">
                        Can I cancel or modify my booking?
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        <p>Yes! You can cancel or modify your booking up to 1 hour before the scheduled pickup time at no charge. Contact us via phone or WhatsApp to make changes.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question">
                        Do you offer round-trip service?
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        <p>Yes, we offer round-trip service for all our routes. Simply mention it when booking, and we'll arrange both legs of your journey at a discounted rate.</p>
                    </div>
                </div>
            </div>

            <div class="faq-category">
                <h2><i class="fas fa-dollar-sign"></i> Pricing &amp; Payment</h2>

                <div class="faq-item">
                    <button class="faq-question">
                        How is the fare calculated?
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        <p>We offer multiple pricing models:</p>
                        <ul>
                            <li><strong>Flat rates</strong> for popular routes (e.g., airport transfers)</li>
                            <li><strong>Distance-based</strong> pricing for custom routes</li>
                            <li><strong>Hourly rates</strong> for tours and chauffeur service</li>
                        </ul>
                        <p>Visit our <a href="/pricing.php">pricing page</a> for detailed rates.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question">
                        What payment methods do you accept?
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        <p>We accept cash, credit/debit cards (via Stripe and Square), and offer a "Pay Later" option for registered customers. Payment can be made before or after your ride.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question">
                        Are there any hidden fees?
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        <p>Absolutely not! The price quoted is the price you pay. There are no hidden fees, surge pricing, or surprise charges. What you see is what you get.</p>
                    </div>
                </div>
            </div>

            <div class="faq-category">
                <h2><i class="fas fa-map-marker-alt"></i> Service Areas</h2>

                <div class="faq-item">
                    <button class="faq-question">
                        What areas do you serve?
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        <p>Our primary service areas are <strong>Portmore, Kingston, Spanish Town, and Old Harbour</strong>. We also provide island-wide tour services to destinations like Ocho Rios, Montego Bay, Negril, and more.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question">
                        Do you provide airport transfers?
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        <p>Yes! We provide reliable airport transfer service to and from Norman Manley International Airport (KIN). Our drivers track your flight and will be waiting for you upon arrival.</p>
                    </div>
                </div>
            </div>

            <div class="faq-category">
                <h2><i class="fas fa-shield-alt"></i> Safety &amp; Vehicles</h2>

                <div class="faq-item">
                    <button class="faq-question">
                        Are your drivers licensed?
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        <p>Yes, all our drivers are fully licensed, background-checked, and professionally trained. Your safety is our top priority.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question">
                        What types of vehicles do you have?
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        <p>Our fleet includes:</p>
                        <ul>
                            <li><strong>60+ Sedans</strong> (4 passengers) — Toyota Corolla, Axio, Honda Fit</li>
                            <li><strong>30+ 7-Seater Vans</strong> — Toyota Noah, Voxy, Nissan Serena</li>
                            <li><strong>10+ Mini Buses</strong> (14-25 passengers) — Toyota HiAce, Coaster</li>
                        </ul>
                        <p>Visit our <a href="/fleet.php">fleet page</a> for more details.</p>
                    </div>
                </div>
            </div>

        </div>

        <!-- Still Have Questions -->
        <div class="faq-cta">
            <h2>Still Have Questions?</h2>
            <p>We're here to help! Reach out to us anytime.</p>
            <div class="cta-buttons">
                <a href="/contact.php" class="btn btn-primary btn-lg"><i class="fas fa-envelope"></i> Contact Us</a>
                <a href="<?php echo getWhatsAppLink(); ?>" class="btn btn-whatsapp btn-lg" target="_blank"><i class="fab fa-whatsapp"></i> WhatsApp</a>
                <a href="<?php echo getCallLink(); ?>" class="btn btn-dark btn-lg"><i class="fas fa-phone"></i> Call Us</a>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
