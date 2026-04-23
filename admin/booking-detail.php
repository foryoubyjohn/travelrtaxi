<?php
$pageTitle = 'Booking Detail';
require_once 'includes/admin-header.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) { header('Location: /admin/bookings.php'); exit; }

$booking = dbFetchOne("SELECT * FROM bookings WHERE id = ?", [$id]);
if (!$booking) { header('Location: /admin/bookings.php'); exit; }

// Handle updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action'] ?? '');
    if ($action === 'update') {
        $status = sanitize($_POST['status']);
        $driverId = !empty($_POST['driver_id']) ? intval($_POST['driver_id']) : null;
        $vehicleId = !empty($_POST['vehicle_id']) ? intval($_POST['vehicle_id']) : null;
        $finalPrice = !empty($_POST['final_price']) ? floatval($_POST['final_price']) : null;
        $paymentStatus = sanitize($_POST['payment_status']);

        dbExecute("UPDATE bookings SET status = ?, driver_id = ?, vehicle_id = ?, final_price = ?, payment_status = ? WHERE id = ?",
            [$status, $driverId, $vehicleId, $finalPrice, $paymentStatus, $id]);
        redirectWith("/admin/booking-detail.php?id=$id", 'success', 'Booking updated successfully.');
    }
}

$drivers = dbFetchAll("SELECT d.id, u.first_name, u.last_name FROM drivers d JOIN users u ON d.user_id = u.id ORDER BY u.first_name");
$vehicles = dbFetchAll("SELECT * FROM vehicles WHERE status = 'active' ORDER BY type, name");
?>

<div class="page-header-row">
    <div>
        <h1 class="page-title">Booking: <?php echo sanitize($booking['booking_ref']); ?></h1>
        <p class="page-subtitle">Created: <?php echo formatDate($booking['created_at']); ?></p>
    </div>
    <a href="/admin/bookings.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to Bookings</a>
</div>

<div class="detail-grid">
    <!-- Booking Info -->
    <div class="admin-card">
        <h3>Trip Details</h3>
        <div class="detail-list">
            <div class="detail-item"><span>Pickup:</span> <strong><?php echo sanitize($booking['pickup_location']); ?></strong></div>
            <div class="detail-item"><span>Drop-off:</span> <strong><?php echo sanitize($booking['dropoff_location']); ?></strong></div>
            <div class="detail-item"><span>Date:</span> <strong><?php echo formatDate($booking['booking_date']); ?></strong></div>
            <div class="detail-item"><span>Time:</span> <strong><?php echo formatTime($booking['booking_time']); ?></strong></div>
            <div class="detail-item"><span>Passengers:</span> <strong><?php echo $booking['passengers']; ?></strong></div>
            <div class="detail-item"><span>Service:</span> <strong><?php echo ucfirst($booking['service_type']); ?></strong></div>
            <div class="detail-item"><span>Vehicle Type:</span> <strong><?php echo ucfirst($booking['vehicle_type']); ?></strong></div>
            <div class="detail-item"><span>Status:</span> <?php echo statusBadge($booking['status']); ?></div>
            <div class="detail-item"><span>Estimated Price:</span> <strong><?php echo $booking['estimated_price'] > 0 ? formatPrice($booking['estimated_price']) : '-'; ?></strong></div>
            <div class="detail-item"><span>Final Price:</span> <strong><?php echo $booking['final_price'] ? formatPrice($booking['final_price']) : 'Not set'; ?></strong></div>
            <div class="detail-item"><span>Payment:</span> <?php echo statusBadge($booking['payment_status']); ?> (<?php echo ucfirst($booking['payment_method']); ?>)</div>
            <?php if ($booking['notes']): ?>
            <div class="detail-item"><span>Notes:</span> <em><?php echo sanitize($booking['notes']); ?></em></div>
            <?php endif; ?>
            <?php if (!empty($booking['tracking_token'])): ?>
            <div class="detail-item">
                <span>Tracking Link:</span>
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    <a href="/track.php?token=<?= sanitize($booking['tracking_token']) ?>" target="_blank" class="btn btn-sm btn-outline" style="font-size:0.75rem;">
                        <i class="fas fa-map-marker-alt"></i> View Tracking Page
                    </a>
                    <button type="button" onclick="copyTrackingLink(this)" data-url="<?= SITE_URL ?>/track.php?token=<?= sanitize($booking['tracking_token']) ?>" class="btn btn-sm btn-dark" style="font-size:0.75rem;">
                        <i class="fas fa-copy"></i> Copy Link
                    </button>
                    <a href="<?= getWhatsAppLink('Track your ride here: ' . SITE_URL . '/track.php?token=' . $booking['tracking_token']) ?>" target="_blank" class="btn btn-sm btn-whatsapp" style="font-size:0.75rem;">
                        <i class="fab fa-whatsapp"></i> Send via WhatsApp
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Customer Info -->
    <div class="admin-card">
        <h3>Customer Details</h3>
        <div class="detail-list">
            <div class="detail-item"><span>Name:</span> <strong><?php echo sanitize($booking['customer_name']); ?></strong></div>
            <div class="detail-item"><span>Phone:</span> <a href="tel:<?php echo sanitize($booking['customer_phone']); ?>"><?php echo sanitize($booking['customer_phone']); ?></a></div>
            <div class="detail-item"><span>Email:</span> <?php echo $booking['customer_email'] ? sanitize($booking['customer_email']) : '-'; ?></div>
        </div>
        <div class="detail-actions">
            <a href="tel:<?php echo sanitize($booking['customer_phone']); ?>" class="btn btn-dark btn-sm"><i class="fas fa-phone"></i> Call</a>
            <a href="<?php echo getWhatsAppLink('Hi ' . $booking['customer_name'] . ', regarding your booking ' . $booking['booking_ref']); ?>" class="btn btn-whatsapp btn-sm" target="_blank"><i class="fab fa-whatsapp"></i> WhatsApp</a>
        </div>
    </div>

    <!-- Update Form -->
    <div class="admin-card">
        <h3>Update Booking</h3>
        <form method="POST">
            <input type="hidden" name="action" value="update">
            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <?php foreach (['pending','confirmed','assigned','accepted','declined','on_the_way','arrived','trip_started','in_progress','completed','no_show','cancelled'] as $s): ?>
                    <option value="<?php echo $s; ?>" <?php echo $booking['status'] === $s ? 'selected' : ''; ?>><?php echo ucfirst(str_replace('_',' ',$s)); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Assign Driver</label>
                <select name="driver_id">
                    <option value="">-- None --</option>
                    <?php foreach ($drivers as $d): ?>
                    <option value="<?php echo $d['id']; ?>" <?php echo $booking['driver_id'] == $d['id'] ? 'selected' : ''; ?>><?php echo sanitize($d['first_name'] . ' ' . $d['last_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Assign Vehicle</label>
                <select name="vehicle_id">
                    <option value="">-- Auto --</option>
                    <?php foreach ($vehicles as $v): ?>
                    <option value="<?php echo $v['id']; ?>" <?php echo $booking['vehicle_id'] == $v['id'] ? 'selected' : ''; ?>><?php echo sanitize($v['name'] . ' (' . ucfirst($v['type']) . ')'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Final Price (<?php echo CURRENCY; ?>)</label>
                <input type="number" name="final_price" step="0.01" value="<?php echo $booking['final_price'] ?? $booking['estimated_price']; ?>">
            </div>
            <div class="form-group">
                <label>Payment Status</label>
                <select name="payment_status">
                    <option value="unpaid" <?php echo $booking['payment_status'] === 'unpaid' ? 'selected' : ''; ?>>Unpaid</option>
                    <option value="paid" <?php echo $booking['payment_status'] === 'paid' ? 'selected' : ''; ?>>Paid</option>
                    <option value="refunded" <?php echo $booking['payment_status'] === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Update Booking</button>
        </form>
    </div>
</div>

<?php
// Show driver action log if any
$actionLog = dbFetchAll(
    "SELECT dal.*, u.first_name, u.last_name FROM driver_action_log dal JOIN drivers d ON dal.driver_id = d.id JOIN users u ON d.user_id = u.id WHERE dal.booking_id = ? ORDER BY dal.created_at DESC",
    [$id]
);
if (!empty($actionLog)):
?>
<div class="admin-card" style="margin-top:20px;">
    <h3><i class="fas fa-history"></i> Driver Activity Log</h3>
    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr><th>Driver</th><th>Action</th><th>From</th><th>To</th><th>Notes</th><th>Time</th></tr></thead>
            <tbody>
            <?php foreach ($actionLog as $log): ?>
            <tr>
                <td><?php echo sanitize($log['first_name'] . ' ' . $log['last_name']); ?></td>
                <td><strong><?php echo ucfirst(str_replace('_', ' ', $log['action'])); ?></strong></td>
                <td><?php echo $log['old_value'] ? statusBadge($log['old_value']) : '-'; ?></td>
                <td><?php echo $log['new_value'] ? statusBadge($log['new_value']) : '-'; ?></td>
                <td><?php echo $log['notes'] ? sanitize($log['notes']) : '-'; ?></td>
                <td><?php echo date('M d, g:i A', strtotime($log['created_at'])); ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<script>
function copyTrackingLink(btn) {
    var url = btn.dataset.url;
    if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(function() {
            btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
            setTimeout(function() { btn.innerHTML = '<i class="fas fa-copy"></i> Copy Link'; }, 2000);
        });
    } else {
        var input = document.createElement('input');
        input.value = url;
        document.body.appendChild(input);
        input.select();
        document.execCommand('copy');
        document.body.removeChild(input);
        btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
        setTimeout(function() { btn.innerHTML = '<i class="fas fa-copy"></i> Copy Link'; }, 2000);
    }
}
</script>

<?php require_once 'includes/admin-footer.php'; ?>
