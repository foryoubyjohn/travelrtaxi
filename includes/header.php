<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

$flash = getFlash();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? sanitize($pageTitle) . ' | ' : ''; ?><?php echo SITE_NAME; ?></title>
    <meta name="description" content="<?php echo isset($pageDesc) ? sanitize($pageDesc) : SITE_NAME . ' - ' . SITE_TAGLINE; ?>">
    <link rel="icon" type="image/jpeg" href="/assets/images/logo.jpeg">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800;900&family=Open+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <?php if (isset($extraCSS)) echo $extraCSS; ?>
</head>
<body class="page-<?php echo $currentPage; ?>">

<!-- Top Bar -->
<div class="top-bar">
    <div class="container">
        <div class="top-bar-left">
            <a href="<?php echo getCallLink(); ?>" class="top-link"><i class="fas fa-phone"></i> <?php echo PHONE_PRIMARY; ?></a>
            <a href="<?php echo getWhatsAppLink(); ?>" class="top-link whatsapp-link"><i class="fab fa-whatsapp"></i> <?php echo PHONE_WHATSAPP; ?></a>
        </div>
        <div class="top-bar-right">
            <span class="top-link"><i class="fas fa-map-marker-alt"></i> Portmore | Kingston | Spanish Town | Old Harbour</span>
            <?php if (isLoggedIn()): ?>
                <a href="/account.php" class="top-link"><i class="fas fa-user"></i> <?php echo sanitize($_SESSION['user_name']); ?></a>
                <a href="/logout.php" class="top-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
            <?php else: ?>
                <a href="/login.php" class="top-link"><i class="fas fa-user"></i> Login</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Navigation -->
<nav class="main-nav" id="mainNav">
    <div class="container">
        <a href="/" class="nav-logo">
            <img src="/assets/images/logo.jpeg" alt="<?php echo SITE_NAME; ?>">
        </a>
        <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">
            <span></span><span></span><span></span>
        </button>
        <ul class="nav-menu" id="navMenu">
            <li><a href="/" class="<?php echo $currentPage === 'index' ? 'active' : ''; ?>">Home</a></li>
            <li><a href="/about.php" class="<?php echo $currentPage === 'about' ? 'active' : ''; ?>">About</a></li>
            <li><a href="/services.php" class="<?php echo $currentPage === 'services' ? 'active' : ''; ?>">Services</a></li>
            <li><a href="/fleet.php" class="<?php echo $currentPage === 'fleet' ? 'active' : ''; ?>">Fleet</a></li>
            <li><a href="/booking.php" class="<?php echo $currentPage === 'booking' ? 'active' : ''; ?>">Book Now</a></li>
            <li><a href="/pricing.php" class="<?php echo $currentPage === 'pricing' ? 'active' : ''; ?>">Pricing</a></li>
            <li><a href="/testimonials.php" class="<?php echo $currentPage === 'testimonials' ? 'active' : ''; ?>">Reviews</a></li>
            <li><a href="/faq.php" class="<?php echo $currentPage === 'faq' ? 'active' : ''; ?>">FAQ</a></li>
            <li><a href="/contact.php" class="<?php echo $currentPage === 'contact' ? 'active' : ''; ?>">Contact</a></li>
            <li><a href="/booking.php" class="nav-cta">Book a Ride</a></li>
        </ul>
    </div>
</nav>

<?php if ($flash): ?>
<div class="flash-message flash-<?php echo $flash['type']; ?>" id="flashMessage">
    <div class="container">
        <p><?php echo sanitize($flash['message']); ?></p>
        <button onclick="this.parentElement.parentElement.remove()" class="flash-close">&times;</button>
    </div>
</div>
<?php endif; ?>
