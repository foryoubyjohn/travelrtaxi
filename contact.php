<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/helpers.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        redirectWith('/contact.php', 'error', 'Invalid request. Please try again.');
    }
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $subject = sanitize($_POST['subject'] ?? '');
    $message = sanitize($_POST['message'] ?? '');

    if (empty($name) || empty($message)) {
        redirectWith('/contact.php', 'error', 'Please fill in all required fields.');
    }

    dbInsert(
        "INSERT INTO contact_messages (name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)",
        [$name, $email, $phone, $subject, $message]
    );
    redirectWith('/contact.php', 'success', 'Thank you for your message! We\'ll get back to you shortly.');
}

$pageTitle = 'Contact Us';
$pageDesc = 'Get in touch with Travelr Taxi & Tours Services. Call, WhatsApp, or send us a message.';
require_once 'includes/header.php';
?>

<section class="page-hero">
    <div class="container">
        <h1>Contact Us</h1>
        <p>We'd love to hear from you</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="contact-grid">
            <!-- Contact Info -->
            <div class="contact-info">
                <h2>Get In Touch</h2>
                <p>Have a question, need a quote, or want to book a ride? Reach out to us through any of these channels.</p>

                <div class="contact-cards">
                    <a href="<?php echo getCallLink(); ?>" class="contact-card">
                        <div class="contact-card-icon"><i class="fas fa-phone"></i></div>
                        <div>
                            <h3>Call Us</h3>
                            <p><?php echo PHONE_PRIMARY; ?></p>
                        </div>
                    </a>
                    <a href="<?php echo getWhatsAppLink(); ?>" class="contact-card whatsapp" target="_blank">
                        <div class="contact-card-icon"><i class="fab fa-whatsapp"></i></div>
                        <div>
                            <h3>WhatsApp</h3>
                            <p><?php echo PHONE_WHATSAPP; ?></p>
                        </div>
                    </a>
                    <a href="mailto:<?php echo SITE_EMAIL; ?>" class="contact-card">
                        <div class="contact-card-icon"><i class="fas fa-envelope"></i></div>
                        <div>
                            <h3>Email</h3>
                            <p><?php echo SITE_EMAIL; ?></p>
                        </div>
                    </a>
                    <div class="contact-card">
                        <div class="contact-card-icon"><i class="fas fa-map-marker-alt"></i></div>
                        <div>
                            <h3>Location</h3>
                            <p>Kingston, Jamaica</p>
                        </div>
                    </div>
                </div>

                <div class="contact-hours">
                    <h3><i class="fas fa-clock"></i> Operating Hours</h3>
                    <table>
                        <tr><td>Monday - Friday</td><td>24 Hours</td></tr>
                        <tr><td>Saturday</td><td>24 Hours</td></tr>
                        <tr><td>Sunday</td><td>24 Hours</td></tr>
                    </table>
                    <p class="text-small"><i class="fas fa-info-circle"></i> We operate 24/7 for your convenience.</p>
                </div>
            </div>

            <!-- Contact Form -->
            <div class="contact-form-wrap">
                <h2>Send Us a Message</h2>
                <form action="/contact.php" method="POST" class="contact-form">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRF(); ?>">
                    <div class="form-row">
                        <div class="form-group half">
                            <label>Your Name *</label>
                            <input type="text" name="name" required placeholder="Full name">
                        </div>
                        <div class="form-group half">
                            <label>Phone</label>
                            <input type="tel" name="phone" placeholder="Your phone number">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" placeholder="your@email.com">
                    </div>
                    <div class="form-group">
                        <label>Subject</label>
                        <input type="text" name="subject" placeholder="What is this about?">
                    </div>
                    <div class="form-group">
                        <label>Message *</label>
                        <textarea name="message" rows="5" required placeholder="How can we help you?"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg btn-block"><i class="fas fa-paper-plane"></i> Send Message</button>
                </form>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
