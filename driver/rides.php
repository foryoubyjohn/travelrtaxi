<?php
/**
 * Driver Panel - All Rides
 * Filterable list: all, today, upcoming, completed, cancelled
 */
$pageTitle = 'My Rides';
require_once __DIR__ . '/includes/driver-header.php';

$driverId = $driverRecord['id'];
$filter = sanitize($_GET['filter'] ?? 'all');
$today = date('Y-m-d');

switch ($filter) {
    case 'today':
        $rides = dbFetchAll(
            "SELECT * FROM bookings WHERE driver_id = ? AND booking_date = ? ORDER BY booking_time ASC",
            [$driverId, $today]
        );
        break;
    case 'upcoming':
        $rides = dbFetchAll(
            "SELECT * FROM bookings WHERE driver_id = ? AND booking_date >= ? AND status IN ('assigned','confirmed','accepted') ORDER BY booking_date ASC, booking_time ASC",
            [$driverId, $today]
        );
        break;
    case 'active':
        $rides = dbFetchAll(
            "SELECT * FROM bookings WHERE driver_id = ? AND status IN ('accepted','on_the_way','arrived','trip_started','in_progress') ORDER BY booking_date ASC, booking_time ASC",
            [$driverId]
        );
        break;
    case 'completed':
        $rides = dbFetchAll(
            "SELECT * FROM bookings WHERE driver_id = ? AND status = 'completed' ORDER BY booking_date DESC, booking_time DESC LIMIT 50",
            [$driverId]
        );
        break;
    case 'cancelled':
        $rides = dbFetchAll(
            "SELECT * FROM bookings WHERE driver_id = ? AND status IN ('cancelled','declined','no_show') ORDER BY booking_date DESC LIMIT 30",
            [$driverId]
        );
        break;
    default:
        $rides = dbFetchAll(
            "SELECT * FROM bookings WHERE driver_id = ? ORDER BY booking_date DESC, booking_time DESC LIMIT 50",
            [$driverId]
        );
        break;
}
?>

<div class="driver-content">

    <!-- Filter Tabs -->
    <div class="filter-tabs">
        <a href="?filter=all" class="filter-tab <?= $filter === 'all' ? 'active' : '' ?>">All</a>
        <a href="?filter=today" class="filter-tab <?= $filter === 'today' ? 'active' : '' ?>">Today</a>
        <a href="?filter=active" class="filter-tab <?= $filter === 'active' ? 'active' : '' ?>">Active</a>
        <a href="?filter=upcoming" class="filter-tab <?= $filter === 'upcoming' ? 'active' : '' ?>">Upcoming</a>
        <a href="?filter=completed" class="filter-tab <?= $filter === 'completed' ? 'active' : '' ?>">Done</a>
        <a href="?filter=cancelled" class="filter-tab <?= $filter === 'cancelled' ? 'active' : '' ?>">Cancelled</a>
    </div>

    <!-- Rides List -->
    <?php if (empty($rides)): ?>
    <div class="empty-card">
        <i class="fas fa-route"></i>
        <p>No rides found for this filter</p>
    </div>
    <?php else: ?>
    <?php foreach ($rides as $ride): ?>
    <a href="/driver/ride-detail.php?id=<?= $ride['id'] ?>" class="ride-card <?= in_array($ride['status'], ['completed']) ? 'ride-card-done' : '' ?> <?= in_array($ride['status'], ['accepted','on_the_way','arrived','trip_started','in_progress']) ? 'ride-card-active' : '' ?>">
        <div class="ride-card-header">
            <span class="ride-ref">#<?= sanitize($ride['booking_ref']) ?></span>
            <?= statusBadge($ride['status']) ?>
        </div>
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
            <span><i class="fas fa-user"></i> <?= sanitize($ride['customer_name']) ?></span>
            <span class="ride-fare"><?= formatPrice($ride['final_price'] ?? $ride['estimated_price']) ?></span>
        </div>
    </a>
    <?php endforeach; ?>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/driver-footer.php'; ?>
