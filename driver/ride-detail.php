<?php
/**
 * Driver Panel - Ride Detail
 * Full ride info + driver action buttons (Accept, Decline, On the Way, Arrived, Trip Started, Completed, No Show)
 */
$pageTitle = 'Ride Detail';
require_once __DIR__ . '/includes/driver-header.php';

$driverId = $driverRecord['id'];
$bookingId = intval($_GET['id'] ?? 0);

// Fetch booking (must belong to this driver)
$ride = dbFetchOne(
    "SELECT b.*, v.name AS vehicle_name, v.plate_number
     FROM bookings b
     LEFT JOIN vehicles v ON b.vehicle_id = v.id
     WHERE b.id = ? AND b.driver_id = ?",
    [$bookingId, $driverId]
);

if (!$ride) {
    redirectWith('/driver/rides.php', 'error', 'Ride not found or not assigned to you.');
}

// Handle driver actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action'] ?? '');
    $driverNote = sanitize($_POST['driver_note'] ?? '');
    $now = date('Y-m-d H:i:s');
    $oldStatus = $ride['status'];
    $newStatus = '';

    switch ($action) {
        case 'accept':
            $newStatus = 'accepted';
            dbExecute("UPDATE bookings SET status = 'accepted', driver_accepted_at = ? WHERE id = ?", [$now, $bookingId]);
            dbExecute("UPDATE drivers SET status = 'on_trip' WHERE id = ?", [$driverId]);
            break;

        case 'decline':
            $newStatus = 'declined';
            dbExecute("UPDATE bookings SET status = 'declined', driver_id = NULL WHERE id = ?", [$bookingId]);
            break;

        case 'on_the_way':
            $newStatus = 'on_the_way';
            dbExecute("UPDATE bookings SET status = 'on_the_way' WHERE id = ?", [$bookingId]);
            dbExecute("UPDATE drivers SET status = 'on_trip' WHERE id = ?", [$driverId]);
            break;

        case 'arrived':
            $newStatus = 'arrived';
            dbExecute("UPDATE bookings SET status = 'arrived', driver_arrived_at = ? WHERE id = ?", [$now, $bookingId]);
            break;

        case 'trip_started':
            $newStatus = 'trip_started';
            dbExecute("UPDATE bookings SET status = 'trip_started', trip_started_at = ? WHERE id = ?", [$now, $bookingId]);
            break;

        case 'completed':
            $newStatus = 'completed';
            $finalPrice = $ride['final_price'] ?? $ride['estimated_price'];
            dbExecute("UPDATE bookings SET status = 'completed', trip_completed_at = ?, final_price = ? WHERE id = ?", [$now, $finalPrice, $bookingId]);
            dbExecute("UPDATE drivers SET status = 'available', total_trips = total_trips + 1, total_earnings = total_earnings + ? WHERE id = ?", [$finalPrice, $driverId]);
            // Record earnings
            $commissionRate = 15.00; // 15% default
            $commissionAmt = $finalPrice * ($commissionRate / 100);
            $netAmt = $finalPrice - $commissionAmt;
            dbInsert(
                "INSERT INTO driver_earnings (driver_id, booking_id, amount, commission_rate, commission_amount, net_amount) VALUES (?, ?, ?, ?, ?, ?)",
                [$driverId, $bookingId, $finalPrice, $commissionRate, $commissionAmt, $netAmt]
            );
            break;

        case 'no_show':
            $newStatus = 'no_show';
            dbExecute("UPDATE bookings SET status = 'no_show', trip_completed_at = ? WHERE id = ?", [$now, $bookingId]);
            dbExecute("UPDATE drivers SET status = 'available' WHERE id = ?", [$driverId]);
            break;
    }

    // Log the action
    if ($newStatus) {
        dbInsert(
            "INSERT INTO driver_action_log (driver_id, booking_id, action, old_value, new_value, notes) VALUES (?, ?, ?, ?, ?, ?)",
            [$driverId, $bookingId, $action, $oldStatus, $newStatus, $driverNote ?: null]
        );
    }

    // Save driver note if provided
    if (!empty($driverNote)) {
        dbInsert(
            "INSERT INTO driver_notes (driver_id, booking_id, note) VALUES (?, ?, ?)",
            [$driverId, $bookingId, $driverNote]
        );
    }

    if ($action === 'decline') {
        redirectWith('/driver/', 'info', 'Ride declined. It will be reassigned.');
    } else {
        redirectWith('/driver/ride-detail.php?id=' . $bookingId, 'success', 'Status updated to: ' . ucfirst(str_replace('_', ' ', $newStatus)));
    }
}

// Fetch action history for this ride
$actionLog = dbFetchAll(
    "SELECT * FROM driver_action_log WHERE booking_id = ? AND driver_id = ? ORDER BY created_at DESC",
    [$bookingId, $driverId]
);

// Fetch driver notes
$driverNotes = dbFetchAll(
    "SELECT * FROM driver_notes WHERE booking_id = ? AND driver_id = ? ORDER BY created_at DESC",
    [$bookingId, $driverId]
);

// Define which actions are available based on current status
$availableActions = [];
switch ($ride['status']) {
    case 'assigned':
        $availableActions = ['accept', 'decline'];
        break;
    case 'accepted':
    case 'confirmed':
        $availableActions = ['on_the_way'];
        break;
    case 'on_the_way':
        $availableActions = ['arrived'];
        break;
    case 'arrived':
        $availableActions = ['trip_started', 'no_show'];
        break;
    case 'trip_started':
    case 'in_progress':
        $availableActions = ['completed'];
        break;
}

// Action button config
$actionConfig = [
    'accept'       => ['icon' => 'fa-check-circle',    'label' => 'Accept Ride',    'class' => 'btn-accept',    'confirm' => 'Accept this ride?'],
    'decline'      => ['icon' => 'fa-times-circle',     'label' => 'Decline',        'class' => 'btn-decline',   'confirm' => 'Decline this ride? It will be reassigned.'],
    'on_the_way'   => ['icon' => 'fa-car',              'label' => 'On the Way',     'class' => 'btn-onway',     'confirm' => 'Confirm you are on the way?'],
    'arrived'      => ['icon' => 'fa-map-pin',           'label' => 'Arrived',        'class' => 'btn-arrived',   'confirm' => 'Confirm you have arrived at pickup?'],
    'trip_started' => ['icon' => 'fa-play-circle',       'label' => 'Start Trip',     'class' => 'btn-start',     'confirm' => 'Start the trip now?'],
    'completed'    => ['icon' => 'fa-flag-checkered',    'label' => 'Complete Trip',  'class' => 'btn-complete',  'confirm' => 'Mark this trip as completed?'],
    'no_show'      => ['icon' => 'fa-user-slash',        'label' => 'No Show',        'class' => 'btn-noshow',    'confirm' => 'Mark customer as no-show?'],
];
?>

<div class="driver-content">

    <!-- Status Banner -->
    <div class="ride-status-banner status-<?= $ride['status'] ?>">
        <div class="status-icon">
            <?php
            $statusIcons = [
                'assigned' => 'fa-bell', 'accepted' => 'fa-thumbs-up', 'confirmed' => 'fa-check',
                'on_the_way' => 'fa-car', 'arrived' => 'fa-map-pin', 'trip_started' => 'fa-play',
                'in_progress' => 'fa-spinner fa-spin', 'completed' => 'fa-flag-checkered',
                'cancelled' => 'fa-ban', 'declined' => 'fa-times', 'no_show' => 'fa-user-slash'
            ];
            $icon = $statusIcons[$ride['status']] ?? 'fa-info';
            ?>
            <i class="fas <?= $icon ?>"></i>
        </div>
        <div class="status-info">
            <span class="status-label"><?= ucfirst(str_replace('_', ' ', $ride['status'])) ?></span>
            <span class="status-ref">#<?= sanitize($ride['booking_ref']) ?></span>
        </div>
    </div>

    <!-- Route Card -->
    <div class="detail-card">
        <div class="detail-card-title"><i class="fas fa-route"></i> Route</div>
        <div class="ride-route-detail">
            <div class="route-point pickup">
                <i class="fas fa-circle"></i>
                <div>
                    <strong>Pickup</strong>
                    <span><?= sanitize($ride['pickup_location']) ?></span>
                </div>
            </div>
            <div class="route-line-vertical"></div>
            <div class="route-point dropoff">
                <i class="fas fa-map-marker-alt"></i>
                <div>
                    <strong>Drop-off</strong>
                    <span><?= sanitize($ride['dropoff_location']) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Customer Card -->
    <div class="detail-card">
        <div class="detail-card-title"><i class="fas fa-user"></i> Customer</div>
        <div class="customer-info">
            <div class="customer-name"><?= sanitize($ride['customer_name']) ?></div>
            <div class="customer-actions">
                <?php if ($ride['customer_phone']): ?>
                <a href="tel:<?= sanitize($ride['customer_phone']) ?>" class="btn-icon btn-call">
                    <i class="fas fa-phone"></i> Call
                </a>
                <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $ride['customer_phone']) ?>" class="btn-icon btn-whatsapp" target="_blank">
                    <i class="fab fa-whatsapp"></i> WhatsApp
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Trip Details Card -->
    <div class="detail-card">
        <div class="detail-card-title"><i class="fas fa-info-circle"></i> Trip Details</div>
        <div class="detail-list">
            <div class="detail-row">
                <span class="detail-label">Date</span>
                <span class="detail-value"><?= formatDate($ride['booking_date']) ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Time</span>
                <span class="detail-value"><?= formatTime($ride['booking_time']) ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Passengers</span>
                <span class="detail-value"><?= $ride['passengers'] ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Service</span>
                <span class="detail-value"><?= ucfirst($ride['service_type']) ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Vehicle</span>
                <span class="detail-value"><?= sanitize($ride['vehicle_name'] ?? 'Not assigned') ?> <?= $ride['plate_number'] ? '(' . sanitize($ride['plate_number']) . ')' : '' ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Payment</span>
                <span class="detail-value"><?= ucfirst($ride['payment_method']) ?></span>
            </div>
            <div class="detail-row highlight-row">
                <span class="detail-label">Estimated Fare</span>
                <span class="detail-value fare-value"><?= formatPrice($ride['estimated_price']) ?></span>
            </div>
            <?php if ($ride['final_price']): ?>
            <div class="detail-row highlight-row">
                <span class="detail-label">Final Fare</span>
                <span class="detail-value fare-value"><?= formatPrice($ride['final_price']) ?></span>
            </div>
            <?php endif; ?>
            <?php if ($ride['notes']): ?>
            <div class="detail-row">
                <span class="detail-label">Customer Notes</span>
                <span class="detail-value"><?= sanitize($ride['notes']) ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Driver Action Buttons -->
    <?php if (!empty($availableActions)): ?>
    <div class="detail-card">
        <div class="detail-card-title"><i class="fas fa-hand-pointer"></i> Actions</div>

        <!-- Optional note before action -->
        <div class="driver-note-input" id="noteSection" style="display:none; margin-bottom: 15px;">
            <textarea id="driverNoteField" placeholder="Add a note (optional)..." rows="2"></textarea>
        </div>

        <div class="action-buttons-grid">
            <?php foreach ($availableActions as $action): ?>
            <?php $cfg = $actionConfig[$action]; ?>
            <form method="POST" class="action-form">
                <input type="hidden" name="action" value="<?= $action ?>">
                <input type="hidden" name="driver_note" value="" class="note-field">
                <button type="submit" class="btn-driver-action <?= $cfg['class'] ?>" data-confirm="<?= $cfg['confirm'] ?>">
                    <i class="fas <?= $cfg['icon'] ?>"></i>
                    <span><?= $cfg['label'] ?></span>
                </button>
            </form>
            <?php endforeach; ?>
        </div>

        <button type="button" class="btn-add-note" onclick="toggleNote()">
            <i class="fas fa-sticky-note"></i> Add Note
        </button>
    </div>
    <?php endif; ?>

    <!-- Timeline / Action History -->
    <?php if (!empty($actionLog)): ?>
    <div class="detail-card">
        <div class="detail-card-title"><i class="fas fa-history"></i> Activity Log</div>
        <div class="timeline">
            <?php foreach ($actionLog as $log): ?>
            <div class="timeline-item">
                <div class="timeline-dot"></div>
                <div class="timeline-content">
                    <strong><?= ucfirst(str_replace('_', ' ', $log['action'])) ?></strong>
                    <?php if ($log['notes']): ?>
                    <p class="timeline-note"><?= sanitize($log['notes']) ?></p>
                    <?php endif; ?>
                    <span class="timeline-time"><?= date('M d, g:i A', strtotime($log['created_at'])) ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Driver Notes -->
    <?php if (!empty($driverNotes)): ?>
    <div class="detail-card">
        <div class="detail-card-title"><i class="fas fa-sticky-note"></i> My Notes</div>
        <?php foreach ($driverNotes as $note): ?>
        <div class="driver-note-item">
            <p><?= sanitize($note['note']) ?></p>
            <span class="note-time"><?= date('M d, g:i A', strtotime($note['created_at'])) ?></span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Back Button -->
    <a href="/driver/" class="btn-back-link">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>

</div>

<script>
function toggleNote() {
    const section = document.getElementById('noteSection');
    section.style.display = section.style.display === 'none' ? 'block' : 'none';
    if (section.style.display === 'block') {
        document.getElementById('driverNoteField').focus();
    }
}

// Sync note field to all action forms
document.getElementById('driverNoteField')?.addEventListener('input', function() {
    document.querySelectorAll('.note-field').forEach(f => f.value = this.value);
});
</script>

<?php require_once __DIR__ . '/includes/driver-footer.php'; ?>
