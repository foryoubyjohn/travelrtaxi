<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/helpers.php';

requireCustomer();

$user = getCurrentUser();
$pageTitle = 'My Account';
require_once 'includes/header.php';
$bookings = dbFetchAll("SELECT * FROM bookings WHERE customer_id = ? ORDER BY created_at DESC", [$user['id']]);
?>

<section class="page-hero">
    <div class="container">
        <h1>My Account</h1>
        <p>Welcome back, <?php echo sanitize($user['first_name']); ?>!</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="account-grid">
            <!-- Account Info -->
            <div class="account-sidebar">
                <div class="account-card">
                    <div class="account-avatar"><i class="fas fa-user-circle"></i></div>
                    <h3><?php echo sanitize($user['first_name'] . ' ' . $user['last_name']); ?></h3>
                    <p><?php echo sanitize($user['email']); ?></p>
                    <p><?php echo sanitize($user['phone']); ?></p>
                    <p class="text-small">Member since <?php echo formatDate($user['created_at']); ?></p>
                </div>
                <div class="account-actions">
                    <a href="/booking.php" class="btn btn-primary btn-block"><i class="fas fa-car"></i> Book a Ride</a>
                    <a href="/logout.php" class="btn btn-outline btn-block"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>

            <!-- Bookings -->
            <div class="account-main">
                <h2>My Bookings</h2>
                <?php if (empty($bookings)): ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <h3>No Bookings Yet</h3>
                    <p>You haven't made any bookings. Book your first ride now!</p>
                    <a href="/booking.php" class="btn btn-primary">Book a Ride</a>
                </div>
                <?php else: ?>
                <div class="bookings-list">
                    <?php foreach ($bookings as $b): ?>
                    <div class="booking-card-horizontal">
                        <div class="booking-card-header">
                            <span class="booking-ref"><?php echo sanitize($b['booking_ref']); ?></span>
                            <?php echo statusBadge($b['status']); ?>
                        </div>
                        <div class="booking-card-body">
                            <div class="booking-route">
                                <div><i class="fas fa-map-marker-alt text-red"></i> <?php echo sanitize($b['pickup_location']); ?></div>
                                <div><i class="fas fa-map-pin text-green"></i> <?php echo sanitize($b['dropoff_location']); ?></div>
                            </div>
                            <div class="booking-meta">
                                <span><i class="fas fa-calendar"></i> <?php echo formatDate($b['booking_date']); ?></span>
                                <span><i class="fas fa-clock"></i> <?php echo formatTime($b['booking_time']); ?></span>
                                <span><i class="fas fa-users"></i> <?php echo $b['passengers']; ?> pax</span>
                                <span><i class="fas fa-tag"></i> <?php echo $b['estimated_price'] > 0 ? formatPrice($b['estimated_price']) : 'TBD'; ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
