<?php
/**
 * Travelr Taxi & Tours Services
 * Configuration File
 */

if (defined('DB_HOST')) return;

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'agmsxxte_travelrtaxi');
define('DB_USER', 'agmsxxte_taxi');
define('DB_PASS', 'havQok-siqta8-wyngyz');
define('DB_CHARSET', 'utf8mb4');

// Site Configuration
define('SITE_NAME', 'Travelr Taxi & Tours Services');
define('SITE_TAGLINE', 'The Affordable Way To Travel');
define('SITE_URL', 'https://travelrtaxi.com');
define('SITE_EMAIL', 'info@travelrtaxi.com');

// Contact Info
define('PHONE_PRIMARY', '876-926-1438');
define('PHONE_WHATSAPP', '876-512-2324');
define('WHATSAPP_MESSAGE', 'Hi, I\'d like to book a ride with Travelr Taxi.');

// Paths
define('ROOT_PATH', dirname(__DIR__) . '/');
define('INCLUDES_PATH', ROOT_PATH . 'includes/');
define('UPLOADS_PATH', ROOT_PATH . 'uploads/');
define('ASSETS_PATH', ROOT_PATH . 'assets/');

// Payment Keys (configure in production)
define('STRIPE_PUBLIC_KEY', '');
define('STRIPE_SECRET_KEY', '');
define('SQUARE_APP_ID', '');
define('SQUARE_ACCESS_TOKEN', '');

// Currency
define('CURRENCY', 'JMD');
define('CURRENCY_SYMBOL', '$');

// Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error Reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Timezone
date_default_timezone_set('America/Jamaica');
