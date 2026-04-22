<?php
/**
 * Process Contact Form Submission
 */
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /contact.php');
    exit;
}

$name = sanitize($_POST['name'] ?? '');
$email = sanitize($_POST['email'] ?? '');
$phone = sanitize($_POST['phone'] ?? '');
$subject = sanitize($_POST['subject'] ?? '');
$message = sanitize($_POST['message'] ?? '');

if (empty($name) || empty($message)) {
    redirectWith('/contact.php', 'error', 'Please fill in all required fields.');
}

dbInsert("INSERT INTO contact_messages (name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)",
    [$name, $email, $phone, $subject, $message]);

redirectWith('/contact.php', 'success', 'Thank you for your message! We will get back to you shortly.');
