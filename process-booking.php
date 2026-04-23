<?php
/**
 * Process Booking Form Submission
 */
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /booking.php');
    exit;
}

$errors = [];

// Validate required fields
$customerName = sanitize($_POST['customer_name'] ?? '');
$customerEmail = sanitize($_POST['customer_email'] ?? '');
$customerPhone = sanitize($_POST['customer_phone'] ?? '');
$pickup = sanitize($_POST['pickup_location'] ?? '');
$dropoff = sanitize($_POST['dropoff_location'] ?? '');
$bookingDate = sanitize($_POST['booking_date'] ?? '');
$bookingTime = sanitize($_POST['booking_time'] ?? '');
$serviceType = sanitize($_POST['service_type'] ?? 'standard');
$vehicleType = sanitize($_POST['vehicle_type'] ?? 'sedan');
$passengers = intval($_POST['passengers'] ?? 1);
$paymentMethod = sanitize($_POST['payment_method'] ?? 'cash');
$notes = sanitize($_POST['notes'] ?? '');

if (empty($customerName)) $errors[] = 'Name is required.';
if (empty($customerPhone)) $errors[] = 'Phone number is required.';
if (empty($pickup)) $errors[] = 'Pickup location is required.';
if (empty($dropoff)) $errors[] = 'Drop-off location is required.';
if (empty($bookingDate)) $errors[] = 'Date is required.';
if (empty($bookingTime)) $errors[] = 'Time is required.';

if (!empty($errors)) {
    $_SESSION['booking_errors'] = $errors;
    $_SESSION['booking_data'] = $_POST;
    header('Location: /booking.php');
    exit;
}

// Generate booking reference and tracking token
$bookingRef = generateBookingRef();
$trackingToken = bin2hex(random_bytes(32));

// Calculate estimated price
$estimatedPrice = 0;
$route = dbFetchOne(
    "SELECT pr.flat_price FROM routes r 
     JOIN pricing_rules pr ON pr.route_id = r.id AND pr.is_active = 1 
     WHERE (LOWER(r.origin) LIKE ? AND LOWER(r.destination) LIKE ?) 
        OR (LOWER(r.origin) LIKE ? AND LOWER(r.destination) LIKE ?)
     AND (pr.vehicle_type = ? OR pr.vehicle_type = 'all')
     LIMIT 1",
    ['%' . strtolower($pickup) . '%', '%' . strtolower($dropoff) . '%',
     '%' . strtolower($dropoff) . '%', '%' . strtolower($pickup) . '%',
     $vehicleType]
);

if ($route && $route['flat_price'] > 0) {
    $estimatedPrice = $route['flat_price'];
} else {
    $defaults = ['sedan' => 1500, 'van' => 2500, 'minibus' => 4000];
    $estimatedPrice = $defaults[$vehicleType] ?? 1500;
}

if ($serviceType === 'rideshare') {
    $estimatedPrice *= 0.7;
}

// Get customer ID if logged in
$customerId = null;
if (isLoggedIn()) {
    $user = getCurrentUser();
    $customerId = $user['id'];
}

// Insert booking (with tracking token)
$bookingId = dbInsert(
    "INSERT INTO bookings (booking_ref, customer_id, customer_name, customer_email, customer_phone, 
     pickup_location, dropoff_location, booking_date, booking_time, service_type, vehicle_type, 
     passengers, estimated_price, payment_method, notes, status, tracking_token) 
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)",
    [$bookingRef, $customerId, $customerName, $customerEmail, $customerPhone,
     $pickup, $dropoff, $bookingDate, $bookingTime, $serviceType, $vehicleType,
     $passengers, $estimatedPrice, $paymentMethod, $notes, $trackingToken]
);

if ($bookingId) {
    $_SESSION['booking_success'] = true;
    $_SESSION['booking_ref'] = $bookingRef;
    $_SESSION['booking_price'] = $estimatedPrice;
    $_SESSION['tracking_token'] = $trackingToken;
    $_SESSION['tracking_url'] = SITE_URL . '/track.php?token=' . $trackingToken;
    header('Location: /booking.php?success=1&ref=' . urlencode($bookingRef));
} else {
    $_SESSION['booking_errors'] = ['An error occurred. Please try again or call us directly.'];
    $_SESSION['booking_data'] = $_POST;
    header('Location: /booking.php');
}
exit;
