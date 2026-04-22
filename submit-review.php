<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /testimonials.php');
    exit;
}

if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
    redirectWith('/testimonials.php', 'error', 'Invalid request. Please try again.');
}

$name = sanitize($_POST['customer_name'] ?? '');
$location = sanitize($_POST['location'] ?? '');
$rating = intval($_POST['rating'] ?? 5);
$message = sanitize($_POST['message'] ?? '');

if (empty($name) || empty($message)) {
    redirectWith('/testimonials.php', 'error', 'Please fill in all required fields.');
}

$rating = max(1, min(5, $rating));

dbInsert(
    "INSERT INTO testimonials (customer_name, location, rating, message, is_approved) VALUES (?, ?, ?, ?, 0)",
    [$name, $location, $rating, $message]
);

redirectWith('/testimonials.php', 'success', 'Thank you for your review! It will appear after approval.');
