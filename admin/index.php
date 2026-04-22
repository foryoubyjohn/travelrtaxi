<?php
$pageTitle = 'Dashboard';
require_once 'includes/admin-header.php';

// Dashboard stats
$totalBookings = dbFetchOne("SELECT COUNT(*) as c FROM bookings")['c'];
$pendingBookings = dbFetchOne("SELECT COUNT(*) as c FROM bookings WHERE status = 'pending'")['c'];
$activeTrips = dbFetchOne("SELECT COUNT(*) as c FROM bookings WHERE status = 'in_progress'")['c'];
$completedBookings = dbFetchOne("SELECT COUNT(*) as c FROM bookings WHERE status = 'completed'")['c'];
$totalRevenue = dbFetchOne("SELECT COALESCE(SUM(final_price), 0) as total FROM bookings WHERE status = 'completed'")['total'];
$totalCustomers = dbFetchOne("SELECT COUNT(*) as c FROM users WHERE role = 'customer'")['c'];
$totalDrivers = dbFetchOne("SELECT COUNT(*) as c FROM drivers")['c'];
$totalVehicles = dbFetchOne("SELECT COUNT(*) as c FROM vehicles WHERE status = 'active'")['c'];
$unreadMessages = dbFetchOne("SELECT COUNT(*) as c FROM contact_messages WHERE is_read = 0")['c'];
$pendingReviews = dbFetchOne("SELECT COUNT(*) as c FROM testimonials WHERE is_approved = 0")['c'];

// Recent bookings
$recentBookings = dbFetchAll("SELECT * FROM bookings ORDER BY created_at DESC LIMIT 10");

// Today's bookings
$todayBookings = dbFetchOne("SELECT COUNT(*) as c FROM bookings WHERE booking_date = CURDATE()")['c'];
?>

<h1 class="page-title">Dashboard</h1>
<p class="page-subtitle">Welcome back, <?php echo sanitize($_SESSION['user_name']); ?>!</p>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card stat-primary">
        <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
        <div class="stat-info">
            <h3><?php echo $totalBookings; ?></h3>
            <p>Total Bookings</p>
        </div>
    </div>
    <div class="stat-card stat-warning">
        <div class="stat-icon"><i class="fas fa-clock"></i></div>
        <div class="stat-info">
            <h3><?php echo $pendingBookings; ?></h3>
            <p>Pending</p>
        </div>
    </div>
    <div class="stat-card stat-info">
        <div class="stat-icon"><i class="fas fa-road"></i></div>
        <div class="stat-info">
            <h3><?php echo $activeTrips; ?></h3>
            <p>Active Trips</p>
        </div>
    </div>
    <div class="stat-card stat-success">
        <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
        <div class="stat-info">
            <h3><?php echo $completedBookings; ?></h3>
            <p>Completed</p>
        </div>
    </div>
    <div class="stat-card stat-revenue">
        <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
        <div class="stat-info">
            <h3><?php echo formatPrice($totalRevenue); ?></h3>
            <p>Total Revenue</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div class="stat-info">
            <h3><?php echo $totalCustomers; ?></h3>
            <p>Customers</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-id-card"></i></div>
        <div class="stat-info">
            <h3><?php echo $totalDrivers; ?></h3>
            <p>Drivers</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-car"></i></div>
        <div class="stat-info">
            <h3><?php echo $totalVehicles; ?></h3>
            <p>Active Vehicles</p>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="quick-actions">
    <h2>Quick Actions</h2>
    <div class="action-buttons">
        <a href="/admin/bookings.php" class="action-btn"><i class="fas fa-calendar-plus"></i> View Bookings <?php if ($pendingBookings > 0): ?><span class="badge"><?php echo $pendingBookings; ?></span><?php endif; ?></a>
        <a href="/admin/messages.php" class="action-btn"><i class="fas fa-envelope"></i> Messages <?php if ($unreadMessages > 0): ?><span class="badge"><?php echo $unreadMessages; ?></span><?php endif; ?></a>
        <a href="/admin/testimonials.php" class="action-btn"><i class="fas fa-star"></i> Reviews <?php if ($pendingReviews > 0): ?><span class="badge"><?php echo $pendingReviews; ?></span><?php endif; ?></a>
        <a href="/admin/fleet.php?action=add" class="action-btn"><i class="fas fa-plus"></i> Add Vehicle</a>
        <a href="/admin/drivers.php?action=add" class="action-btn"><i class="fas fa-user-plus"></i> Add Driver</a>
    </div>
</div>

<!-- Recent Bookings -->
<div class="admin-card">
    <div class="card-header">
        <h2>Recent Bookings</h2>
        <a href="/admin/bookings.php" class="btn btn-sm btn-outline">View All</a>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Ref</th>
                    <th>Customer</th>
                    <th>Route</th>
                    <th>Date</th>
                    <th>Service</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentBookings)): ?>
                <tr><td colspan="8" class="text-center">No bookings yet.</td></tr>
                <?php else: ?>
                <?php foreach ($recentBookings as $b): ?>
                <tr>
                    <td><strong><?php echo sanitize($b['booking_ref']); ?></strong></td>
                    <td><?php echo sanitize($b['customer_name']); ?></td>
                    <td class="route-cell">
                        <small><?php echo sanitize(substr($b['pickup_location'], 0, 20)); ?> → <?php echo sanitize(substr($b['dropoff_location'], 0, 20)); ?></small>
                    </td>
                    <td><?php echo formatDate($b['booking_date']); ?></td>
                    <td><?php echo ucfirst($b['service_type']); ?></td>
                    <td><?php echo $b['estimated_price'] > 0 ? formatPrice($b['estimated_price']) : '-'; ?></td>
                    <td><?php echo statusBadge($b['status']); ?></td>
                    <td>
                        <a href="/admin/booking-detail.php?id=<?php echo $b['id']; ?>" class="btn btn-sm btn-outline">View</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/admin-footer.php'; ?>
