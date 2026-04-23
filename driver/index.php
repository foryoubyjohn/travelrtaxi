<?php
/**
 * Driver Panel - Dashboard
 * Shows today's rides, upcoming, and completed
 */
$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/driver-header.php';

$driverId = $driverRecord['id'];
$today = date('Y-m-d');

// Current active ride (in_progress, on_the_way, arrived, trip_started, accepted)
$activeRide = dbFetchOne(
    "SELECT * FROM bookings WHERE driver_id = ? AND status IN ('accepted','on_the_way','arrived','trip_started','in_progress') ORDER BY booking_date ASC, booking_time ASC LIMIT 1",
    [$driverId]
);

// Today's assigned rides (not yet started)
$todayRides = dbFetchAll(
    "SELECT * FROM bookings WHERE driver_id = ? AND booking_date = ? AND status IN ('assigned','confirmed','accepted') ORDER BY booking_time ASC",
    [$driverId, $today]
);

// Upcoming rides (future dates)
$upcomingRides = dbFetchAll(
    "SELECT * FROM bookings WHERE driver_id = ? AND booking_date > ? AND status IN ('assigned','confirmed','accepted') ORDER BY booking_date ASC, booking_time ASC LIMIT 10",
    [$driverId, $today]
);

// Completed today
$completedToday = dbFetchAll(
    "SELECT * FROM bookings WHERE driver_id = ? AND booking_date = ? AND status IN ('completed','no_show') ORDER BY booking_time DESC",
    [$driverId, $today]
);

// Quick stats
$statsToday = dbFetchOne(
    "SELECT COUNT(*) as total, SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) as done,
     SUM(CASE WHEN status='completed' THEN COALESCE(final_price, estimated_price) ELSE 0 END) as earned
     FROM bookings WHERE driver_id = ? AND booking_date = ?",
    [$driverId, $today]
);

// Pending rides needing acceptance
$pendingAccept = dbFetchAll(
    "SELECT * FROM bookings WHERE driver_id = ? AND status = 'assigned' ORDER BY booking_date ASC, booking_time ASC LIMIT 5",
    [$driverId]
);
?>

<div class="driver-content" data-auto-refresh="true">

    <!-- Location Sharing Card -->
    <div class="location-sharing-card">
        <div class="location-sharing-header">
            <h4><i class="fas fa-satellite-dish"></i> Location Sharing</h4>
            <label class="loc-toggle">
                <input type="checkbox" id="locationToggle" <?= ($driverRecord['location_sharing'] ?? 0) ? 'checked' : '' ?>>
                <span class="loc-toggle-slider"></span>
            </label>
        </div>
        <div class="location-status-row">
            <span class="loc-status-dot <?= ($driverRecord['location_sharing'] ?? 0) ? 'loc-live' : 'loc-off' ?>" id="locationStatusDot"></span>
            <span id="locationStatusText"><?= ($driverRecord['location_sharing'] ?? 0) ? 'Sharing your location' : 'Location sharing off' ?></span>
            <span id="locationLastUpdate" style="margin-left:auto;font-size:0.7rem;color:#64748b;">
                <?= ($driverRecord['last_location_at'] ?? null) ? date('g:i A', strtotime($driverRecord['last_location_at'])) : '' ?>
            </span>
        </div>
    </div>

    <!-- Pending count badge for real-time updates -->
    <span id="pendingCountBadge" style="display:none;"><?= count($pendingAccept) ?></span>
    <span id="todayCountValue" style="display:none;"><?= intval($statsToday['total'] ?? 0) ?></span>

    <!-- Availability Quick Toggle -->
    <div class="availability-strip availability-<?= $driverRecord['availability'] ?? 'available' ?>">
        <span class="avail-dot"></span>
        <span class="avail-text"><?= ucfirst($driverRecord['availability'] ?? 'available') ?></span>
        <a href="/driver/availability.php" class="avail-change">Change <i class="fas fa-chevron-right"></i></a>
    </div>

    <!-- Stats Row -->
    <div class="stats-row">
        <div class="mini-stat">
            <div class="mini-stat-number"><?= intval($statsToday['total'] ?? 0) ?></div>
            <div class="mini-stat-label">Today's Rides</div>
        </div>
        <div class="mini-stat">
            <div class="mini-stat-number"><?= intval($statsToday['done'] ?? 0) ?></div>
            <div class="mini-stat-label">Completed</div>
        </div>
        <div class="mini-stat highlight">
            <div class="mini-stat-number"><?= formatPrice($statsToday['earned'] ?? 0) ?></div>
            <div class="mini-stat-label">Earned Today</div>
        </div>
    </div>

    <?php if ($activeRide): ?>
    <!-- Active Ride Card (prominent) -->
    <div class="section-label"><i class="fas fa-car-side"></i> Active Ride</div>
    <a href="/driver/ride-detail.php?id=<?= $activeRide['id'] ?>" class="ride-card ride-card-active">
        <div class="ride-card-status"><?= statusBadge($activeRide['status']) ?></div>
        <div class="ride-card-route">
            <div class="route-point pickup">
                <i class="fas fa-circle"></i>
                <span><?= sanitize($activeRide['pickup_location']) ?></span>
            </div>
            <div class="route-line"></div>
            <div class="route-point dropoff">
                <i class="fas fa-map-marker-alt"></i>
                <span><?= sanitize($activeRide['dropoff_location']) ?></span>
            </div>
        </div>
        <div class="ride-card-meta">
            <span><i class="fas fa-user"></i> <?= sanitize($activeRide['customer_name']) ?></span>
            <span><i class="fas fa-clock"></i> <?= formatTime($activeRide['booking_time']) ?></span>
            <span class="ride-fare"><i class="fas fa-money-bill"></i> <?= formatPrice($activeRide['estimated_price']) ?></span>
        </div>
        <div class="ride-card-action">
            <span class="tap-hint">Tap to manage <i class="fas fa-chevron-right"></i></span>
        </div>
    </a>
    <?php endif; ?>

    <?php if (count($pendingAccept) > 0): ?>
    <!-- Pending Acceptance -->
    <div class="section-label"><i class="fas fa-bell"></i> Needs Your Response</div>
    <?php foreach ($pendingAccept as $ride): ?>
    <a href="/driver/ride-detail.php?id=<?= $ride['id'] ?>" class="ride-card ride-card-pending">
        <div class="ride-card-status"><?= statusBadge('assigned') ?> <span class="new-badge">NEW</span></div>
        <div class="ride-card-route">
            <div class="route-point pickup">
                <i class="fas fa-circle"></i>
                <span><?= sanitize($ride['pickup_location']) ?></span>
            </div>
            <div class="route-line"></div>
            <div class="route-point dropoff">
                <i class="fas fa-map-marker-alt"></i>
                <span><?= sanitize($ride['dropoff_location']) ?></span>
            </div>
        </div>
        <div class="ride-card-meta">
            <span><i class="fas fa-calendar"></i> <?= formatDate($ride['booking_date']) ?></span>
            <span><i class="fas fa-clock"></i> <?= formatTime($ride['booking_time']) ?></span>
            <span class="ride-fare"><i class="fas fa-money-bill"></i> <?= formatPrice($ride['estimated_price']) ?></span>
        </div>
        <div class="ride-card-action">
            <span class="tap-hint">Tap to Accept/Decline <i class="fas fa-chevron-right"></i></span>
        </div>
    </a>
    <?php endforeach; ?>
    <?php endif; ?>

    <!-- Today's Rides -->
    <div class="section-label"><i class="fas fa-calendar-day"></i> Today's Schedule</div>
    <?php if (empty($todayRides) && !$activeRide): ?>
    <div class="empty-card">
        <i class="fas fa-coffee"></i>
        <p>No rides scheduled for today</p>
    </div>
    <?php else: ?>
    <?php foreach ($todayRides as $ride): ?>
    <?php if ($activeRide && $ride['id'] === $activeRide['id']) continue; ?>
    <a href="/driver/ride-detail.php?id=<?= $ride['id'] ?>" class="ride-card">
        <div class="ride-card-route">
            <div class="route-point pickup">
                <i class="fas fa-circle"></i>
                <span><?= sanitize($ride['pickup_location']) ?></span>
            </div>
            <div class="route-line"></div>
            <div class="route-point dropoff">
                <i class="fas fa-map-marker-alt"></i>
                <span><?= sanitize($ride['dropoff_location']) ?></span>
            </div>
        </div>
        <div class="ride-card-meta">
            <span><i class="fas fa-clock"></i> <?= formatTime($ride['booking_time']) ?></span>
            <span><i class="fas fa-users"></i> <?= $ride['passengers'] ?> pax</span>
            <span class="ride-fare"><?= formatPrice($ride['estimated_price']) ?></span>
        </div>
    </a>
    <?php endforeach; ?>
    <?php endif; ?>

    <!-- Upcoming Rides -->
    <?php if (!empty($upcomingRides)): ?>
    <div class="section-label"><i class="fas fa-calendar-alt"></i> Upcoming</div>
    <?php foreach ($upcomingRides as $ride): ?>
    <a href="/driver/ride-detail.php?id=<?= $ride['id'] ?>" class="ride-card">
        <div class="ride-card-route">
            <div class="route-point pickup">
                <i class="fas fa-circle"></i>
                <span><?= sanitize($ride['pickup_location']) ?></span>
            </div>
            <div class="route-line"></div>
            <div class="route-point dropoff">
                <i class="fas fa-map-marker-alt"></i>
                <span><?= sanitize($ride['dropoff_location']) ?></span>
            </div>
        </div>
        <div class="ride-card-meta">
            <span><i class="fas fa-calendar"></i> <?= formatDate($ride['booking_date']) ?></span>
            <span><i class="fas fa-clock"></i> <?= formatTime($ride['booking_time']) ?></span>
            <span class="ride-fare"><?= formatPrice($ride['estimated_price']) ?></span>
        </div>
    </a>
    <?php endforeach; ?>
    <?php endif; ?>

    <!-- Completed Today -->
    <?php if (!empty($completedToday)): ?>
    <div class="section-label"><i class="fas fa-check-circle"></i> Completed Today</div>
    <?php foreach ($completedToday as $ride): ?>
    <a href="/driver/ride-detail.php?id=<?= $ride['id'] ?>" class="ride-card ride-card-done">
        <div class="ride-card-route">
            <div class="route-point pickup">
                <i class="fas fa-circle"></i>
                <span><?= sanitize($ride['pickup_location']) ?></span>
            </div>
            <div class="route-line"></div>
            <div class="route-point dropoff">
                <i class="fas fa-map-marker-alt"></i>
                <span><?= sanitize($ride['dropoff_location']) ?></span>
            </div>
        </div>
        <div class="ride-card-meta">
            <span><i class="fas fa-clock"></i> <?= formatTime($ride['booking_time']) ?></span>
            <span><?= statusBadge($ride['status']) ?></span>
            <span class="ride-fare"><?= formatPrice($ride['final_price'] ?? $ride['estimated_price']) ?></span>
        </div>
    </a>
    <?php endforeach; ?>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/driver-footer.php'; ?>
