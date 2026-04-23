<?php
/**
 * Driver Panel - Earnings Summary
 */
$pageTitle = 'Earnings';
require_once __DIR__ . '/includes/driver-header.php';

$driverId = $driverRecord['id'];
$today = date('Y-m-d');
$weekStart = date('Y-m-d', strtotime('monday this week'));
$monthStart = date('Y-m-01');

// Today's earnings
$todayEarnings = dbFetchOne(
    "SELECT COUNT(*) as trips, COALESCE(SUM(amount),0) as gross, COALESCE(SUM(commission_amount),0) as commission, COALESCE(SUM(net_amount),0) as net
     FROM driver_earnings WHERE driver_id = ? AND DATE(created_at) = ?",
    [$driverId, $today]
);

// This week
$weekEarnings = dbFetchOne(
    "SELECT COUNT(*) as trips, COALESCE(SUM(amount),0) as gross, COALESCE(SUM(commission_amount),0) as commission, COALESCE(SUM(net_amount),0) as net
     FROM driver_earnings WHERE driver_id = ? AND DATE(created_at) >= ?",
    [$driverId, $weekStart]
);

// This month
$monthEarnings = dbFetchOne(
    "SELECT COUNT(*) as trips, COALESCE(SUM(amount),0) as gross, COALESCE(SUM(commission_amount),0) as commission, COALESCE(SUM(net_amount),0) as net
     FROM driver_earnings WHERE driver_id = ? AND DATE(created_at) >= ?",
    [$driverId, $monthStart]
);

// All time from driver record
$allTimeEarnings = $driverRecord['total_earnings'] ?? 0;
$allTimeTrips = $driverRecord['total_trips'] ?? 0;

// Recent earnings list
$recentEarnings = dbFetchAll(
    "SELECT e.*, b.booking_ref, b.pickup_location, b.dropoff_location, b.booking_date
     FROM driver_earnings e
     LEFT JOIN bookings b ON e.booking_id = b.id
     WHERE e.driver_id = ?
     ORDER BY e.created_at DESC LIMIT 20",
    [$driverId]
);

$period = sanitize($_GET['period'] ?? 'today');
?>

<div class="driver-content">

    <!-- Period Tabs -->
    <div class="filter-tabs">
        <a href="?period=today" class="filter-tab <?= $period === 'today' ? 'active' : '' ?>">Today</a>
        <a href="?period=week" class="filter-tab <?= $period === 'week' ? 'active' : '' ?>">This Week</a>
        <a href="?period=month" class="filter-tab <?= $period === 'month' ? 'active' : '' ?>">This Month</a>
        <a href="?period=all" class="filter-tab <?= $period === 'all' ? 'active' : '' ?>">All Time</a>
    </div>

    <?php
    switch ($period) {
        case 'week': $data = $weekEarnings; $label = 'This Week'; break;
        case 'month': $data = $monthEarnings; $label = 'This Month'; break;
        case 'all': $data = ['trips' => $allTimeTrips, 'gross' => $allTimeEarnings, 'commission' => 0, 'net' => $allTimeEarnings]; $label = 'All Time'; break;
        default: $data = $todayEarnings; $label = 'Today'; break;
    }
    ?>

    <!-- Earnings Hero -->
    <div class="earnings-hero">
        <div class="earnings-hero-label"><?= $label ?> Earnings</div>
        <div class="earnings-hero-amount"><?= formatPrice($data['net'] ?? $data['gross'] ?? 0) ?></div>
        <div class="earnings-hero-trips"><?= intval($data['trips'] ?? 0) ?> trip<?= ($data['trips'] ?? 0) != 1 ? 's' : '' ?></div>
    </div>

    <!-- Breakdown -->
    <?php if ($period !== 'all'): ?>
    <div class="detail-card">
        <div class="detail-card-title"><i class="fas fa-chart-pie"></i> Breakdown</div>
        <div class="detail-list">
            <div class="detail-row">
                <span class="detail-label">Gross Fares</span>
                <span class="detail-value"><?= formatPrice($data['gross'] ?? 0) ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Commission</span>
                <span class="detail-value" style="color:#ef4444">-<?= formatPrice($data['commission'] ?? 0) ?></span>
            </div>
            <div class="detail-row highlight-row">
                <span class="detail-label">Net Earnings</span>
                <span class="detail-value fare-value"><?= formatPrice($data['net'] ?? 0) ?></span>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Quick Stats -->
    <div class="stats-row">
        <div class="mini-stat">
            <div class="mini-stat-number"><?= formatPrice($todayEarnings['net'] ?? 0) ?></div>
            <div class="mini-stat-label">Today</div>
        </div>
        <div class="mini-stat">
            <div class="mini-stat-number"><?= formatPrice($weekEarnings['net'] ?? 0) ?></div>
            <div class="mini-stat-label">This Week</div>
        </div>
        <div class="mini-stat">
            <div class="mini-stat-number"><?= formatPrice($monthEarnings['net'] ?? 0) ?></div>
            <div class="mini-stat-label">This Month</div>
        </div>
    </div>

    <!-- Recent Earnings -->
    <div class="detail-card">
        <div class="detail-card-title"><i class="fas fa-list"></i> Recent Trips</div>
        <?php if (empty($recentEarnings)): ?>
        <div class="empty-card">
            <i class="fas fa-wallet"></i>
            <p>No earnings recorded yet</p>
        </div>
        <?php else: ?>
        <?php foreach ($recentEarnings as $earning): ?>
        <div class="earning-item">
            <div class="earning-item-left">
                <div class="earning-ref">#<?= sanitize($earning['booking_ref'] ?? 'N/A') ?></div>
                <div class="earning-route"><?= sanitize($earning['pickup_location'] ?? '') ?> &rarr; <?= sanitize($earning['dropoff_location'] ?? '') ?></div>
                <div class="earning-date"><?= date('M d, Y', strtotime($earning['created_at'])) ?></div>
            </div>
            <div class="earning-item-right">
                <div class="earning-amount"><?= formatPrice($earning['net_amount']) ?></div>
                <div class="earning-gross">Fare: <?= formatPrice($earning['amount']) ?></div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<?php require_once __DIR__ . '/includes/driver-footer.php'; ?>
