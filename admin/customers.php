<?php
$pageTitle = 'Customer Records';
require_once 'includes/admin-header.php';

$customers = dbFetchAll("SELECT u.*, (SELECT COUNT(*) FROM bookings WHERE customer_id = u.id) as booking_count FROM users u WHERE u.role = 'customer' ORDER BY u.created_at DESC");

// Also get guest bookings (no account)
$guestBookings = dbFetchAll("SELECT customer_name, customer_email, customer_phone, COUNT(*) as booking_count, MAX(created_at) as last_booking FROM bookings WHERE customer_id IS NULL GROUP BY customer_name, customer_email, customer_phone ORDER BY last_booking DESC");
?>

<h1 class="page-title">Customer Records</h1>

<div class="admin-card">
    <h3>Registered Customers</h3>
    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Bookings</th><th>Joined</th><th>Status</th></tr></thead>
            <tbody>
                <?php if (empty($customers)): ?>
                <tr><td colspan="6" class="text-center">No registered customers yet.</td></tr>
                <?php else: ?>
                <?php foreach ($customers as $c): ?>
                <tr>
                    <td><strong><?php echo sanitize($c['first_name'] . ' ' . $c['last_name']); ?></strong></td>
                    <td><?php echo sanitize($c['email']); ?></td>
                    <td><?php echo sanitize($c['phone']); ?></td>
                    <td><?php echo $c['booking_count']; ?></td>
                    <td><?php echo formatDate($c['created_at']); ?></td>
                    <td><?php echo $c['is_active'] ? statusBadge('active') : statusBadge('offline'); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="admin-card">
    <h3>Guest Bookings</h3>
    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Bookings</th><th>Last Booking</th></tr></thead>
            <tbody>
                <?php if (empty($guestBookings)): ?>
                <tr><td colspan="5" class="text-center">No guest bookings yet.</td></tr>
                <?php else: ?>
                <?php foreach ($guestBookings as $g): ?>
                <tr>
                    <td><strong><?php echo sanitize($g['customer_name']); ?></strong></td>
                    <td><?php echo sanitize($g['customer_email']); ?></td>
                    <td><?php echo sanitize($g['customer_phone']); ?></td>
                    <td><?php echo $g['booking_count']; ?></td>
                    <td><?php echo formatDate($g['last_booking']); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/admin-footer.php'; ?>
