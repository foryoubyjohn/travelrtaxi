<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/helpers.php';

// Pre-fill from query params
$pickup = sanitize($_GET['pickup'] ?? '');
$dropoff = sanitize($_GET['dropoff'] ?? '');
$date = sanitize($_GET['date'] ?? date('Y-m-d'));
$time = sanitize($_GET['time'] ?? date('H:i'));
$serviceType = sanitize($_GET['service'] ?? 'standard');
$vehicleType = sanitize($_GET['vehicle_type'] ?? 'any');

// Get routes for dropdown
$routes = dbFetchAll("SELECT * FROM routes WHERE is_active = 1 ORDER BY origin, destination");

// Handle booking submission
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $customerName = sanitize($_POST['customer_name'] ?? '');
        $customerEmail = sanitize($_POST['customer_email'] ?? '');
        $customerPhone = sanitize($_POST['customer_phone'] ?? '');
        $pickup = sanitize($_POST['pickup_location'] ?? '');
        $dropoff = sanitize($_POST['dropoff_location'] ?? '');
        $date = sanitize($_POST['booking_date'] ?? '');
        $time = sanitize($_POST['booking_time'] ?? '');
        $passengers = intval($_POST['passengers'] ?? 1);
        $serviceType = sanitize($_POST['service_type'] ?? 'standard');
        $vehicleType = sanitize($_POST['vehicle_type'] ?? 'any');
        $routeId = !empty($_POST['route_id']) ? intval($_POST['route_id']) : null;
        $notes = sanitize($_POST['notes'] ?? '');
        $paymentMethod = sanitize($_POST['payment_method'] ?? 'cash');

        // Validation
        if (empty($customerName)) $errors[] = 'Name is required.';
        if (empty($customerPhone)) $errors[] = 'Phone number is required.';
        if (empty($pickup)) $errors[] = 'Pickup location is required.';
        if (empty($dropoff)) $errors[] = 'Drop-off location is required.';
        if (empty($date)) $errors[] = 'Date is required.';
        if (empty($time)) $errors[] = 'Time is required.';
        if ($passengers < 1) $errors[] = 'At least 1 passenger required.';

        // Suggest vehicle type based on passengers
        $suggestedVehicle = 'sedan';
        if ($passengers > 4 && $passengers <= 7) $suggestedVehicle = 'van';
        if ($passengers > 7) $suggestedVehicle = 'minibus';
        if ($vehicleType === 'any') $vehicleType = $suggestedVehicle;

        // Calculate price
        $distanceKm = 0;
        $durationMin = 0;
        if ($routeId) {
            $route = dbFetchOne("SELECT * FROM routes WHERE id = ?", [$routeId]);
            if ($route) {
                $distanceKm = $route['distance_km'];
                $durationMin = $route['estimated_time_min'];
                if (empty($pickup)) $pickup = $route['origin'];
                if (empty($dropoff)) $dropoff = $route['destination'];
            }
        }

        $priceCalc = calculatePrice($serviceType, $distanceKm, $durationMin, $vehicleType, $routeId);
        $estimatedPrice = $priceCalc['price'];

        if (empty($errors)) {
            $bookingRef    = generateBookingRef();
            $customerId    = isLoggedIn() ? $_SESSION['user_id'] : null;
            $trackingToken = bin2hex(random_bytes(16));

            $bookingId = dbInsert(
                "INSERT INTO bookings (booking_ref, customer_id, customer_name, customer_email, customer_phone, pickup_location, dropoff_location, booking_date, booking_time, passengers, service_type, vehicle_type, estimated_price, payment_method, notes, status, tracking_token) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)",
                [$bookingRef, $customerId, $customerName, $customerEmail, $customerPhone, $pickup, $dropoff, $date, $time, $passengers, $serviceType, $vehicleType, $estimatedPrice, $paymentMethod, $notes, $trackingToken]
            );

            if ($bookingId) {
                $success = true;
                $_SESSION['last_booking_ref']     = $bookingRef;
                $_SESSION['last_booking_price']   = $estimatedPrice;
                $_SESSION['last_tracking_token']  = $trackingToken;
            } else {
                $errors[] = 'Failed to create booking. Please try again.';
            }
        }
    }
}

$pageTitle = 'Book a Ride';
$pageDesc = 'Book your taxi ride or tour with Travelr Taxi & Tours Services. Easy online booking with instant price estimates.';
require_once 'includes/header.php';
?>

<section class="page-hero">
    <div class="container">
        <h1>Book a Ride</h1>
        <p>Fill in the details below and we'll get you moving!</p>
    </div>
</section>

<section class="section">
    <div class="container">

        <?php if ($success): ?>
        <!-- Booking Confirmation -->
        <div class="booking-confirmation">
            <div class="confirmation-icon"><i class="fas fa-check-circle"></i></div>
            <h2>Booking Confirmed!</h2>
            <p>Your booking reference is:</p>
            <div class="booking-ref"><?php echo $_SESSION['last_booking_ref']; ?></div>
            <?php if ($_SESSION['last_booking_price'] > 0): ?>
            <p class="booking-price">Estimated Fare: <strong><?php echo formatPrice($_SESSION['last_booking_price']); ?></strong></p>
            <?php endif; ?>
            <p>We'll contact you shortly to confirm your ride. You can also reach us via:</p>
            <div class="confirmation-actions">
                <a href="<?php echo getCallLink(); ?>" class="btn btn-dark btn-lg"><i class="fas fa-phone"></i> Call <?php echo PHONE_PRIMARY; ?></a>
                <a href="<?php echo getWhatsAppLink('Hi, I just booked a ride. My reference is ' . $_SESSION['last_booking_ref']); ?>" class="btn btn-whatsapp btn-lg" target="_blank"><i class="fab fa-whatsapp"></i> Confirm on WhatsApp</a>
            </div>
            <?php if (!empty($_SESSION['last_tracking_token'])): ?>
            <div style="margin-top:20px;">
                <a href="/track.php?token=<?php echo urlencode($_SESSION['last_tracking_token']); ?>" class="btn btn-outline btn-lg"><i class="fas fa-map-marker-alt"></i> Track My Ride</a>
            </div>
            <?php endif; ?>
            <a href="/booking.php" class="btn btn-outline" style="margin-top:12px;">Book Another Ride</a>
        </div>

        <?php else: ?>
        <!-- Booking Form -->
        <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $e): ?>
                <li><?php echo $e; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div class="booking-layout">
            <form action="/booking.php" method="POST" class="booking-form" id="bookingForm">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRF(); ?>">

                <!-- Step 1: Trip Details -->
                <div class="booking-step" id="step1">
                    <h2><span class="step-number">1</span> Trip Details</h2>

                    <div class="form-group">
                        <label>Select Route (Optional)</label>
                        <select name="route_id" id="routeSelect" onchange="updateRoute()">
                            <option value="">-- Custom Route --</option>
                            <?php foreach ($routes as $r): ?>
                            <option value="<?php echo $r['id']; ?>" data-origin="<?php echo sanitize($r['origin']); ?>" data-dest="<?php echo sanitize($r['destination']); ?>" data-distance="<?php echo $r['distance_km']; ?>" data-time="<?php echo $r['estimated_time_min']; ?>">
                                <?php echo sanitize($r['origin']); ?> → <?php echo sanitize($r['destination']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group half">
                            <label><i class="fas fa-map-marker-alt text-red"></i> Pickup Location *</label>
                            <input type="text" name="pickup_location" id="pickupInput" value="<?php echo $pickup; ?>" required placeholder="Enter pickup address">
                        </div>
                        <div class="form-group half">
                            <label><i class="fas fa-map-pin text-green"></i> Drop-off Location *</label>
                            <input type="text" name="dropoff_location" id="dropoffInput" value="<?php echo $dropoff; ?>" required placeholder="Enter destination">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group half">
                            <label><i class="fas fa-calendar"></i> Date *</label>
                            <input type="date" name="booking_date" value="<?php echo $date; ?>" min="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="form-group half">
                            <label><i class="fas fa-clock"></i> Time *</label>
                            <input type="time" name="booking_time" value="<?php echo $time; ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group half">
                            <label><i class="fas fa-users"></i> Passengers *</label>
                            <select name="passengers" id="passengerSelect" onchange="suggestVehicle()">
                                <?php for ($i = 1; $i <= 25; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?> Passenger<?php echo $i > 1 ? 's' : ''; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="form-group half">
                            <label><i class="fas fa-concierge-bell"></i> Service Type</label>
                            <select name="service_type" id="serviceType">
                                <option value="standard" <?php echo $serviceType === 'standard' ? 'selected' : ''; ?>>Standard Taxi</option>
                                <option value="airport" <?php echo $serviceType === 'airport' ? 'selected' : ''; ?>>Airport Transfer</option>
                                <option value="tour" <?php echo $serviceType === 'tour' ? 'selected' : ''; ?>>Island Tour</option>
                                <option value="hourly" <?php echo $serviceType === 'hourly' ? 'selected' : ''; ?>>Hourly / Chauffeur</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-car"></i> Vehicle Preference</label>
                        <select name="vehicle_type" id="vehicleType">
                            <option value="any" <?php echo $vehicleType === 'any' ? 'selected' : ''; ?>>Auto-Select (Best Match)</option>
                            <option value="sedan" <?php echo $vehicleType === 'sedan' ? 'selected' : ''; ?>>Sedan (1-4 passengers)</option>
                            <option value="van" <?php echo $vehicleType === 'van' ? 'selected' : ''; ?>>7-Seater Van (5-7 passengers)</option>
                            <option value="minibus" <?php echo $vehicleType === 'minibus' ? 'selected' : ''; ?>>Mini Bus (8-25 passengers)</option>
                        </select>
                        <div id="vehicleSuggestion" class="form-hint" style="display:none;"></div>
                    </div>

                    <button type="button" onclick="goToStep(2)" class="btn btn-primary btn-lg">Continue <i class="fas fa-arrow-right"></i></button>
                </div>

                <!-- Step 2: Your Details -->
                <div class="booking-step" id="step2" style="display:none;">
                    <h2><span class="step-number">2</span> Your Details</h2>

                    <div class="form-row">
                        <div class="form-group half">
                            <label>Full Name *</label>
                            <input type="text" name="customer_name" required placeholder="Your full name" value="<?php echo isLoggedIn() ? sanitize($_SESSION['user_name']) : ''; ?>">
                        </div>
                        <div class="form-group half">
                            <label>Phone Number *</label>
                            <input type="tel" name="customer_phone" required placeholder="e.g., 876-555-1234">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Email (Optional)</label>
                        <input type="email" name="customer_email" placeholder="your@email.com" value="<?php echo isLoggedIn() ? sanitize($_SESSION['user_email'] ?? '') : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label>Payment Method</label>
                        <select name="payment_method">
                            <option value="cash">Cash</option>
                            <option value="stripe">Credit/Debit Card (Stripe)</option>
                            <option value="square">Credit/Debit Card (Square)</option>
                            <option value="pay_later">Pay Later</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Special Instructions</label>
                        <textarea name="notes" rows="3" placeholder="Any special requests or instructions..."></textarea>
                    </div>

                    <div class="form-buttons">
                        <button type="button" onclick="goToStep(1)" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back</button>
                        <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-check"></i> Confirm Booking</button>
                    </div>
                </div>
            </form>

            <!-- Booking Summary Sidebar -->
            <div class="booking-sidebar">
                <div class="booking-summary-card">
                    <h3><i class="fas fa-receipt"></i> Booking Summary</h3>
                    <div class="summary-items">
                        <div class="summary-item">
                            <span>Service</span>
                            <strong id="summaryService">Standard Taxi</strong>
                        </div>
                        <div class="summary-item">
                            <span>Vehicle</span>
                            <strong id="summaryVehicle">Auto-Select</strong>
                        </div>
                        <div class="summary-item">
                            <span>Date</span>
                            <strong id="summaryDate"><?php echo $date; ?></strong>
                        </div>
                        <div class="summary-item">
                            <span>Time</span>
                            <strong id="summaryTime"><?php echo $time; ?></strong>
                        </div>
                        <div class="summary-item">
                            <span>Passengers</span>
                            <strong id="summaryPassengers">1</strong>
                        </div>
                    </div>
                    <div class="summary-divider"></div>
                    <div class="summary-price">
                        <span>Estimated Fare</span>
                        <strong id="summaryPrice">Contact for quote</strong>
                    </div>
                    <p class="summary-note">Final price confirmed upon booking.</p>
                </div>

                <!-- Quick Contact -->
                <div class="booking-help-card">
                    <h4>Need Help Booking?</h4>
                    <p>Our team is ready to assist you.</p>
                    <a href="<?php echo getCallLink(); ?>" class="btn btn-dark btn-block"><i class="fas fa-phone"></i> <?php echo PHONE_PRIMARY; ?></a>
                    <a href="<?php echo getWhatsAppLink(); ?>" class="btn btn-whatsapp btn-block" target="_blank"><i class="fab fa-whatsapp"></i> WhatsApp Us</a>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>
</section>

<script>
function goToStep(step) {
    document.getElementById('step1').style.display = step === 1 ? 'block' : 'none';
    document.getElementById('step2').style.display = step === 2 ? 'block' : 'none';
    window.scrollTo({top: 300, behavior: 'smooth'});
    updateSummary();
}

function updateRoute() {
    const sel = document.getElementById('routeSelect');
    const opt = sel.options[sel.selectedIndex];
    if (opt.value) {
        document.getElementById('pickupInput').value = opt.dataset.origin;
        document.getElementById('dropoffInput').value = opt.dataset.dest;
    }
    updateSummary();
}

function suggestVehicle() {
    const p = parseInt(document.getElementById('passengerSelect').value);
    const hint = document.getElementById('vehicleSuggestion');
    if (p > 7) {
        hint.textContent = 'Recommended: Mini Bus for ' + p + ' passengers';
        hint.style.display = 'block';
    } else if (p > 4) {
        hint.textContent = 'Recommended: 7-Seater Van for ' + p + ' passengers';
        hint.style.display = 'block';
    } else {
        hint.style.display = 'none';
    }
    updateSummary();
}

function updateSummary() {
    const services = {standard:'Standard Taxi', airport:'Airport Transfer', tour:'Island Tour', hourly:'Hourly/Chauffeur'};
    const vehicles = {any:'Auto-Select', sedan:'Sedan', van:'7-Seater Van', minibus:'Mini Bus'};
    document.getElementById('summaryService').textContent = services[document.getElementById('serviceType').value] || 'Standard';
    document.getElementById('summaryVehicle').textContent = vehicles[document.getElementById('vehicleType').value] || 'Auto';
    document.getElementById('summaryPassengers').textContent = document.getElementById('passengerSelect').value;

    const dateInput = document.querySelector('input[name="booking_date"]');
    const timeInput = document.querySelector('input[name="booking_time"]');
    if (dateInput.value) document.getElementById('summaryDate').textContent = dateInput.value;
    if (timeInput.value) document.getElementById('summaryTime').textContent = timeInput.value;
}

// Attach listeners
document.getElementById('serviceType').addEventListener('change', updateSummary);
document.getElementById('vehicleType').addEventListener('change', updateSummary);
document.querySelector('input[name="booking_date"]').addEventListener('change', updateSummary);
document.querySelector('input[name="booking_time"]').addEventListener('change', updateSummary);

// Init
updateSummary();
</script>

<?php require_once 'includes/footer.php'; ?>
