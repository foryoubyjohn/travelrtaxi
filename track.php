<?php
/**
 * Customer Ride Tracking Page
 * Public access via secure token — no login required.
 * URL: /track.php?token=XXXX
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

$token = sanitize($_GET['token'] ?? '');

// Validate token format
if (!$token || strlen($token) < 32) {
    $error = 'Invalid or missing tracking link.';
}

// Look up booking
$booking = null;
if (!isset($error)) {
    $booking = dbFetchOne(
        "SELECT b.*, v.name as vehicle_name, v.plate_number, v.color as vehicle_color,
                d.last_latitude, d.last_longitude, d.last_location_at, d.location_sharing,
                u.first_name as driver_first, u.last_name as driver_last
         FROM bookings b
         LEFT JOIN drivers d ON b.driver_id = d.id
         LEFT JOIN users u ON d.user_id = u.id
         LEFT JOIN vehicles v ON b.vehicle_id = v.id
         WHERE b.tracking_token = ?",
        [$token]
    );
    if (!$booking) {
        $error = 'Booking not found. Please check your tracking link.';
    } elseif (!$booking['tracking_enabled']) {
        $error = 'Tracking has been disabled for this booking.';
    }
}

$isActive = $booking && in_array($booking['status'], ['assigned','accepted','on_the_way','arrived','trip_started','in_progress']);
$isCompleted = $booking && $booking['status'] === 'completed';
$isCancelled = $booking && in_array($booking['status'], ['cancelled','no_show','declined']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Your Ride - <?= SITE_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: #f0f2f5;
            color: #1a1a2e;
            min-height: 100vh;
        }

        /* Header */
        .track-header {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            padding: 20px;
            text-align: center;
            color: #fff;
        }
        .track-header .logo-text {
            font-size: 1.3rem;
            font-weight: 700;
            color: #FFD700;
        }
        .track-header .logo-text span { color: #fff; }
        .track-header p {
            font-size: 0.85rem;
            color: rgba(255,255,255,0.7);
            margin-top: 4px;
        }

        /* Container */
        .track-container {
            max-width: 480px;
            margin: 0 auto;
            padding: 16px;
        }

        /* Error */
        .track-error {
            text-align: center;
            padding: 60px 20px;
        }
        .track-error i {
            font-size: 3rem;
            color: #ef4444;
            margin-bottom: 16px;
        }
        .track-error h2 { font-size: 1.2rem; margin-bottom: 8px; }
        .track-error p { color: #666; }

        /* Booking ref bar */
        .booking-ref-bar {
            background: #fff;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
        .booking-ref-bar .ref { font-weight: 700; font-size: 1.1rem; }
        .booking-ref-bar .status-pill {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            color: #fff;
        }

        /* Status colors */
        .sp-pending { background: #f59e0b; }
        .sp-confirmed { background: #3b82f6; }
        .sp-assigned { background: #8b5cf6; }
        .sp-accepted { background: #3b82f6; }
        .sp-on_the_way { background: #8b5cf6; }
        .sp-arrived { background: #06b6d4; }
        .sp-trip_started, .sp-in_progress { background: #10b981; }
        .sp-completed { background: #059669; }
        .sp-cancelled, .sp-no_show, .sp-declined { background: #ef4444; }

        /* Timeline */
        .timeline-card {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
        .timeline-card h3 {
            font-size: 0.85rem;
            text-transform: uppercase;
            color: #888;
            margin-bottom: 16px;
            letter-spacing: 0.5px;
        }
        .track-timeline {
            position: relative;
            padding-left: 32px;
        }
        .track-timeline::before {
            content: '';
            position: absolute;
            left: 11px;
            top: 4px;
            bottom: 4px;
            width: 2px;
            background: #e5e7eb;
        }
        .tl-step {
            position: relative;
            padding-bottom: 20px;
        }
        .tl-step:last-child { padding-bottom: 0; }
        .tl-dot {
            position: absolute;
            left: -32px;
            top: 2px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1;
        }
        .tl-dot i { font-size: 10px; color: #fff; display: none; }
        .tl-step.completed .tl-dot {
            background: #10b981;
        }
        .tl-step.completed .tl-dot i { display: block; }
        .tl-step.current .tl-dot {
            background: #FFD700;
            box-shadow: 0 0 0 4px rgba(255,215,0,0.3);
            animation: tlPulse 2s infinite;
        }
        .tl-step.current .tl-dot i { display: block; color: #1a1a2e; }
        @keyframes tlPulse {
            0%, 100% { box-shadow: 0 0 0 4px rgba(255,215,0,0.3); }
            50% { box-shadow: 0 0 0 8px rgba(255,215,0,0.1); }
        }
        .tl-label { font-weight: 600; font-size: 0.9rem; }
        .tl-time { font-size: 0.75rem; color: #888; margin-top: 2px; }
        .tl-step:not(.completed):not(.current) .tl-label { color: #aaa; }

        /* Driver card */
        .driver-card {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
        .driver-card h3 {
            font-size: 0.85rem;
            text-transform: uppercase;
            color: #888;
            margin-bottom: 12px;
            letter-spacing: 0.5px;
        }
        .driver-info-row {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .driver-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #FFD700;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            color: #1a1a2e;
            font-weight: 700;
        }
        .driver-details .driver-name { font-weight: 700; font-size: 1rem; }
        .driver-details .vehicle-info { font-size: 0.85rem; color: #666; margin-top: 2px; }
        .driver-details .plate-info {
            display: inline-block;
            background: #f0f2f5;
            padding: 2px 8px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 0.8rem;
            margin-top: 4px;
        }

        /* Location indicator */
        .location-indicator {
            margin-top: 12px;
            padding: 10px 14px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
        }
        .location-indicator.loc-live {
            background: #ecfdf5;
            color: #059669;
        }
        .location-indicator.loc-stale {
            background: #fffbeb;
            color: #d97706;
        }
        .location-indicator .loc-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .loc-live .loc-dot { background: #10b981; animation: locBlink 1.5s infinite; }
        .loc-stale .loc-dot { background: #f59e0b; }
        @keyframes locBlink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }

        /* Route card */
        .route-card {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
        .route-card h3 {
            font-size: 0.85rem;
            text-transform: uppercase;
            color: #888;
            margin-bottom: 12px;
            letter-spacing: 0.5px;
        }
        .route-point {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 8px 0;
        }
        .route-point i { margin-top: 3px; font-size: 0.9rem; }
        .route-point.pickup i { color: #10b981; }
        .route-point.dropoff i { color: #ef4444; }
        .route-point strong { display: block; font-size: 0.75rem; color: #888; text-transform: uppercase; }
        .route-point span { font-size: 0.95rem; }
        .route-divider {
            border-left: 2px dashed #e5e7eb;
            height: 16px;
            margin-left: 7px;
        }

        /* Details grid */
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 12px;
        }
        .detail-item {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 8px;
        }
        .detail-item .label { font-size: 0.7rem; color: #888; text-transform: uppercase; }
        .detail-item .value { font-weight: 600; font-size: 0.95rem; margin-top: 2px; }

        /* Completed/Cancelled banner */
        .result-banner {
            text-align: center;
            padding: 24px;
            border-radius: 12px;
            margin-bottom: 12px;
            color: #fff;
        }
        .result-banner.completed { background: linear-gradient(135deg, #059669, #10b981); }
        .result-banner.cancelled { background: linear-gradient(135deg, #dc2626, #ef4444); }
        .result-banner i { font-size: 2.5rem; margin-bottom: 8px; }
        .result-banner h2 { font-size: 1.2rem; }
        .result-banner p { font-size: 0.85rem; opacity: 0.9; margin-top: 4px; }

        /* Auto-refresh indicator */
        .refresh-indicator {
            text-align: center;
            padding: 8px;
            font-size: 0.75rem;
            color: #aaa;
        }
        .refresh-indicator .refresh-dot {
            display: inline-block;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #10b981;
            margin-right: 4px;
            animation: locBlink 2s infinite;
        }

        /* WhatsApp CTA */
        .whatsapp-cta {
            display: block;
            background: #25D366;
            color: #fff;
            text-align: center;
            padding: 14px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            margin-top: 12px;
        }
        .whatsapp-cta:hover { background: #1ebe5d; }
        .whatsapp-cta i { margin-right: 6px; }

        /* Footer */
        .track-footer {
            text-align: center;
            padding: 20px;
            font-size: 0.8rem;
            color: #aaa;
        }
        .track-footer a { color: #FFD700; text-decoration: none; }

        /* Responsive */
        @media (max-width: 360px) {
            .details-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="track-header">
    <div class="logo-text">Travelr <span>Taxi</span></div>
    <p>Track Your Ride</p>
</div>

<div class="track-container">

<?php if (isset($error)): ?>
    <div class="track-error">
        <i class="fas fa-exclamation-circle"></i>
        <h2>Tracking Unavailable</h2>
        <p><?= sanitize($error) ?></p>
        <a href="/" class="whatsapp-cta" style="margin-top:24px; background:#1a1a2e;">
            <i class="fas fa-home"></i> Go to Homepage
        </a>
    </div>
<?php else: ?>

    <!-- Booking Ref Bar -->
    <div class="booking-ref-bar">
        <span class="ref">#<?= sanitize($booking['booking_ref']) ?></span>
        <span class="status-pill sp-<?= $booking['status'] ?>">
            <?= ucfirst(str_replace('_', ' ', $booking['status'])) ?>
        </span>
    </div>

    <?php if ($isCompleted): ?>
    <div class="result-banner completed">
        <i class="fas fa-flag-checkered"></i>
        <h2>Trip Completed</h2>
        <p>Thank you for riding with Travelr Taxi!</p>
    </div>
    <?php elseif ($isCancelled): ?>
    <div class="result-banner cancelled">
        <i class="fas fa-times-circle"></i>
        <h2>Trip <?= ucfirst($booking['status']) ?></h2>
        <p>This booking has been <?= str_replace('_', ' ', $booking['status']) ?>.</p>
    </div>
    <?php endif; ?>

    <!-- Status Timeline -->
    <div class="timeline-card" id="timelineCard">
        <h3><i class="fas fa-list-check"></i> Ride Progress</h3>
        <div class="track-timeline">
            <?php
            $steps = [
                ['status' => 'booked', 'label' => 'Booking Confirmed', 'icon' => 'fa-check'],
                ['status' => 'assigned', 'label' => 'Driver Assigned', 'icon' => 'fa-user-check'],
                ['status' => 'accepted', 'label' => 'Driver Accepted', 'icon' => 'fa-thumbs-up'],
                ['status' => 'on_the_way', 'label' => 'Driver On the Way', 'icon' => 'fa-car'],
                ['status' => 'arrived', 'label' => 'Driver Arrived', 'icon' => 'fa-map-pin'],
                ['status' => 'trip_started', 'label' => 'Trip In Progress', 'icon' => 'fa-play'],
                ['status' => 'completed', 'label' => 'Trip Completed', 'icon' => 'fa-flag-checkered'],
            ];
            $statusOrder = array_column($steps, 'status');
            $currentIdx = array_search($booking['status'], $statusOrder);
            if ($currentIdx === false) $currentIdx = 0; // At least booked

            $timestamps = [
                'booked' => $booking['created_at'] ?? null,
                'assigned' => null,
                'accepted' => $booking['driver_accepted_at'],
                'on_the_way' => null,
                'arrived' => $booking['driver_arrived_at'],
                'trip_started' => $booking['trip_started_at'],
                'completed' => $booking['trip_completed_at'],
            ];

            foreach ($steps as $idx => $step):
                $isStepCompleted = ($idx <= $currentIdx);
                $isCurrent = ($step['status'] === $booking['status']) || ($idx === 0 && $currentIdx >= 0);
                if ($idx === 0) $isCurrent = false; // Booked is always just completed
                if ($step['status'] === $booking['status']) $isCurrent = true;
                $cls = $isStepCompleted ? 'completed' : '';
                if ($isCurrent && !$isCompleted && !$isCancelled) $cls .= ' current';
                $time = $timestamps[$step['status']] ?? null;
            ?>
            <div class="tl-step <?= $cls ?>">
                <div class="tl-dot"><i class="fas <?= $step['icon'] ?>"></i></div>
                <div class="tl-label"><?= $step['label'] ?></div>
                <?php if ($time): ?>
                <div class="tl-time"><?= date('g:i A', strtotime($time)) ?></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Driver Info -->
    <?php if ($booking['driver_first'] && !$isCancelled): ?>
    <div class="driver-card" id="driverCard">
        <h3><i class="fas fa-id-card"></i> Your Driver</h3>
        <div class="driver-info-row">
            <div class="driver-avatar">
                <?= strtoupper(substr($booking['driver_first'], 0, 1)) ?>
            </div>
            <div class="driver-details">
                <div class="driver-name"><?= sanitize($booking['driver_first']) ?> <?= substr(sanitize($booking['driver_last']), 0, 1) ?>.</div>
                <?php if ($booking['vehicle_name']): ?>
                <div class="vehicle-info">
                    <?= sanitize($booking['vehicle_color'] ?? '') ?> <?= sanitize($booking['vehicle_name']) ?>
                </div>
                <?php endif; ?>
                <?php if ($booking['plate_number']): ?>
                <span class="plate-info"><?= sanitize($booking['plate_number']) ?></span>
                <?php endif; ?>
            </div>
        </div>

        <?php
        // Show location indicator for active rides
        if ($isActive && $booking['location_sharing'] && $booking['last_latitude']):
            $locAge = time() - strtotime($booking['last_location_at']);
            $isLive = ($locAge < 120);
            $isStale = ($locAge >= 120 && $locAge < 600);
        ?>
        <?php if ($isLive || $isStale): ?>
        <div class="location-indicator <?= $isLive ? 'loc-live' : 'loc-stale' ?>" id="locationIndicator">
            <span class="loc-dot"></span>
            <span id="locationText">
                <?= $isLive ? 'Driver location is live' : 'Last seen ' . date('g:i A', strtotime($booking['last_location_at'])) ?>
            </span>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Route Info -->
    <div class="route-card">
        <h3><i class="fas fa-route"></i> Trip Details</h3>
        <div class="route-point pickup">
            <i class="fas fa-circle"></i>
            <div>
                <strong>Pickup</strong>
                <span><?= sanitize($booking['pickup_location']) ?></span>
            </div>
        </div>
        <div class="route-divider"></div>
        <div class="route-point dropoff">
            <i class="fas fa-map-marker-alt"></i>
            <div>
                <strong>Drop-off</strong>
                <span><?= sanitize($booking['dropoff_location']) ?></span>
            </div>
        </div>

        <div class="details-grid">
            <div class="detail-item">
                <div class="label">Date</div>
                <div class="value"><?= formatDate($booking['booking_date']) ?></div>
            </div>
            <div class="detail-item">
                <div class="label">Time</div>
                <div class="value"><?= formatTime($booking['booking_time']) ?></div>
            </div>
            <div class="detail-item">
                <div class="label">Passengers</div>
                <div class="value"><?= $booking['passengers'] ?></div>
            </div>
            <div class="detail-item">
                <div class="label">Est. Fare</div>
                <div class="value"><?= formatPrice($booking['estimated_price']) ?></div>
            </div>
        </div>
    </div>

    <!-- WhatsApp Contact -->
    <a href="<?= getWhatsAppLink('Hi, I need help with booking #' . $booking['booking_ref']) ?>" class="whatsapp-cta" target="_blank">
        <i class="fab fa-whatsapp"></i> Contact Us on WhatsApp
    </a>

    <!-- Auto-refresh indicator for active rides -->
    <?php if ($isActive): ?>
    <div class="refresh-indicator" id="refreshIndicator">
        <span class="refresh-dot"></span> Live tracking — updates every 15 seconds
    </div>
    <?php endif; ?>

<?php endif; ?>

</div><!-- /.track-container -->

<div class="track-footer">
    <p>&copy; <?= date('Y') ?> <a href="/"><?= SITE_NAME ?></a>. <?= SITE_TAGLINE ?></p>
</div>

<?php if ($booking && $isActive): ?>
<!-- Real-time polling for active rides -->
<script>
(function() {
    const token = '<?= sanitize($token) ?>';
    const pollInterval = 15000;
    let lastStatus = '<?= $booking['status'] ?>';

    function pollStatus() {
        const xhr = new XMLHttpRequest();
        xhr.open('GET', '/api/tracking.php?action=status&token=' + encodeURIComponent(token), true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState !== 4 || xhr.status !== 200) return;
            try {
                const res = JSON.parse(xhr.responseText);
                if (!res.success) return;

                // Check if status changed
                if (res.booking.status !== lastStatus) {
                    lastStatus = res.booking.status;
                    // Reload page to reflect new state
                    location.reload();
                    return;
                }

                // Update location indicator
                if (res.location) {
                    const indicator = document.getElementById('locationIndicator');
                    const locText = document.getElementById('locationText');
                    if (indicator && locText) {
                        indicator.className = 'location-indicator ' + (res.location.is_live ? 'loc-live' : 'loc-stale');
                        locText.textContent = res.location.is_live
                            ? 'Driver location is live'
                            : 'Last seen ' + res.location.updated_at;
                    }
                }

                // Update status pill
                const pill = document.querySelector('.status-pill');
                if (pill) {
                    pill.className = 'status-pill sp-' + res.booking.status;
                    pill.textContent = res.booking.status_label;
                }

            } catch(e) {}
        };
        xhr.send();
    }

    // Start polling
    setInterval(pollStatus, pollInterval);

    // Also poll immediately after 3 seconds (catch quick changes)
    setTimeout(pollStatus, 3000);
})();
</script>
<?php endif; ?>

</body>
</html>
