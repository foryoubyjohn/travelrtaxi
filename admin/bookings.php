<?php
$pageTitle = 'Manage Bookings';
require_once 'includes/admin-header.php';

// Filter
$statusFilter = sanitize($_GET['status'] ?? 'all');
$sql = "SELECT b.*, d.id as driver_record_id FROM bookings b LEFT JOIN drivers d ON b.driver_id = d.id";
$params = [];
if ($statusFilter !== 'all' && in_array($statusFilter, ['pending','confirmed','assigned','accepted','declined','on_the_way','arrived','trip_started','in_progress','completed','cancelled','no_show'])) {
    $sql .= " WHERE b.status = ?";
    $params[] = $statusFilter;
}
$sql .= " ORDER BY b.created_at DESC";
$bookings = dbFetchAll($sql, $params);

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $bookingId = intval($_POST['booking_id'] ?? 0);
    $action = sanitize($_POST['action']);

    if ($action === 'update_status' && $bookingId) {
        $newStatus = sanitize($_POST['new_status']);
        dbExecute("UPDATE bookings SET status = ? WHERE id = ?", [$newStatus, $bookingId]);
        redirectWith('/admin/bookings.php', 'success', 'Booking status updated.');
    }
    if ($action === 'assign_driver' && $bookingId) {
        $driverId = intval($_POST['driver_id']);
        dbExecute("UPDATE bookings SET driver_id = ?, status = 'assigned' WHERE id = ?", [$driverId, $bookingId]);
        redirectWith('/admin/bookings.php', 'success', 'Driver assigned successfully.');
    }
}

$drivers = dbFetchAll("SELECT d.id, u.first_name, u.last_name, d.status FROM drivers d JOIN users u ON d.user_id = u.id ORDER BY u.first_name");
?>

<h1 class="page-title">Manage Bookings</h1>

<!-- Filters -->
<div class="filter-bar">
    <a href="/admin/bookings.php" class="filter-btn <?php echo $statusFilter === 'all' ? 'active' : ''; ?>">All</a>
    <a href="?status=pending" class="filter-btn <?php echo $statusFilter === 'pending' ? 'active' : ''; ?>">Pending</a>
    <a href="?status=confirmed" class="filter-btn <?php echo $statusFilter === 'confirmed' ? 'active' : ''; ?>">Confirmed</a>
    <a href="?status=assigned" class="filter-btn <?php echo $statusFilter === 'assigned' ? 'active' : ''; ?>">Assigned</a>
    <a href="?status=accepted" class="filter-btn <?php echo $statusFilter === 'accepted' ? 'active' : ''; ?>">Accepted</a>
    <a href="?status=on_the_way" class="filter-btn <?php echo $statusFilter === 'on_the_way' ? 'active' : ''; ?>">On the Way</a>
    <a href="?status=arrived" class="filter-btn <?php echo $statusFilter === 'arrived' ? 'active' : ''; ?>">Arrived</a>
    <a href="?status=trip_started" class="filter-btn <?php echo $statusFilter === 'trip_started' ? 'active' : ''; ?>">Trip Started</a>
    <a href="?status=in_progress" class="filter-btn <?php echo $statusFilter === 'in_progress' ? 'active' : ''; ?>">In Progress</a>
    <a href="?status=completed" class="filter-btn <?php echo $statusFilter === 'completed' ? 'active' : ''; ?>">Completed</a>
    <a href="?status=no_show" class="filter-btn <?php echo $statusFilter === 'no_show' ? 'active' : ''; ?>">No Show</a>
    <a href="?status=cancelled" class="filter-btn <?php echo $statusFilter === 'cancelled' ? 'active' : ''; ?>">Cancelled</a>
</div>

<div class="admin-card">
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Ref</th>
                    <th>Customer</th>
                    <th>Phone</th>
                    <th>Route</th>
                    <th>Date/Time</th>
                    <th>Service</th>
                    <th>Vehicle</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Driver</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($bookings)): ?>
                <tr><td colspan="11" class="text-center">No bookings found.</td></tr>
                <?php else: ?>
                <?php foreach ($bookings as $b): ?>
                <tr>
                    <td><strong><?php echo sanitize($b['booking_ref']); ?></strong></td>
                    <td><?php echo sanitize($b['customer_name']); ?></td>
                    <td><a href="tel:<?php echo sanitize($b['customer_phone']); ?>"><?php echo sanitize($b['customer_phone']); ?></a></td>
                    <td class="route-cell">
                        <small><strong>From:</strong> <?php echo sanitize(substr($b['pickup_location'], 0, 25)); ?></small><br>
                        <small><strong>To:</strong> <?php echo sanitize(substr($b['dropoff_location'], 0, 25)); ?></small>
                    </td>
                    <td><?php echo formatDate($b['booking_date']); ?><br><small><?php echo formatTime($b['booking_time']); ?></small></td>
                    <td><?php echo ucfirst($b['service_type']); ?></td>
                    <td><?php echo ucfirst($b['vehicle_type']); ?></td>
                    <td><?php echo $b['estimated_price'] > 0 ? formatPrice($b['estimated_price']) : '-'; ?></td>
                    <td><?php echo statusBadge($b['status']); ?></td>
                    <td>
                        <?php if ($b['driver_id']): ?>
                            <?php
                            $driverInfo = dbFetchOne("SELECT u.first_name, u.last_name FROM drivers d JOIN users u ON d.user_id = u.id WHERE d.id = ?", [$b['driver_id']]);
                            echo $driverInfo ? sanitize($driverInfo['first_name'] . ' ' . $driverInfo['last_name']) : '-';
                            ?>
                        <?php else: ?>
                            <form method="POST" class="inline-form">
                                <input type="hidden" name="action" value="assign_driver">
                                <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                <select name="driver_id" class="input-sm">
                                    <option value="">Assign...</option>
                                    <?php foreach ($drivers as $d): ?>
                                    <option value="<?php echo $d['id']; ?>"><?php echo sanitize($d['first_name'] . ' ' . $d['last_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-sm btn-primary">Go</button>
                            </form>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="action-group">
                            <a href="/admin/booking-detail.php?id=<?php echo $b['id']; ?>" class="btn btn-sm btn-outline">View</a>
                            <form method="POST" class="inline-form">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                <select name="new_status" class="input-sm" onchange="this.form.submit()">
                                    <option value="">Status...</option>
                                    <option value="pending">Pending</option>
                                    <option value="confirmed">Confirmed</option>
                                    <option value="assigned">Assigned</option>
                                    <option value="accepted">Accepted</option>
                                    <option value="on_the_way">On the Way</option>
                                    <option value="arrived">Arrived</option>
                                    <option value="trip_started">Trip Started</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="completed">Completed</option>
                                    <option value="no_show">No Show</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/admin-footer.php'; ?>
