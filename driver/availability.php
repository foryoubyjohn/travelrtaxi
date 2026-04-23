<?php
/**
 * Driver Panel - Availability Toggle
 * Available / Unavailable / Off Duty
 */
$pageTitle = 'My Status';
require_once __DIR__ . '/includes/driver-header.php';

$driverId = $driverRecord['id'];

// Handle status change
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newAvailability = sanitize($_POST['availability'] ?? '');
    $validStatuses = ['available', 'unavailable', 'off_duty'];

    if (in_array($newAvailability, $validStatuses)) {
        $oldAvailability = $driverRecord['availability'] ?? 'available';

        // Update availability
        dbExecute("UPDATE drivers SET availability = ? WHERE id = ?", [$newAvailability, $driverId]);

        // Also update legacy status field for admin compatibility
        $statusMap = ['available' => 'available', 'unavailable' => 'offline', 'off_duty' => 'offline'];
        dbExecute("UPDATE drivers SET status = ? WHERE id = ?", [$statusMap[$newAvailability], $driverId]);

        // Track shift start/end
        if ($newAvailability === 'available' && $oldAvailability !== 'available') {
            dbExecute("UPDATE drivers SET shift_started_at = NOW() WHERE id = ?", [$driverId]);
        } elseif ($newAvailability === 'off_duty' && $oldAvailability === 'available') {
            dbExecute("UPDATE drivers SET shift_ended_at = NOW() WHERE id = ?", [$driverId]);
        }

        // Log the action
        dbInsert(
            "INSERT INTO driver_action_log (driver_id, action, old_value, new_value) VALUES (?, 'availability_change', ?, ?)",
            [$driverId, $oldAvailability, $newAvailability]
        );

        redirectWith('/driver/availability.php', 'success', 'Status updated to: ' . ucfirst(str_replace('_', ' ', $newAvailability)));
    }
}

// Reload driver record after potential update
$driverRecord = dbFetchOne(
    "SELECT d.*, v.name AS vehicle_name, v.plate_number
     FROM drivers d LEFT JOIN vehicles v ON d.vehicle_id = v.id
     WHERE d.id = ?", [$driverId]
);

$currentAvailability = $driverRecord['availability'] ?? 'available';

// Check if driver has an active ride
$hasActiveRide = dbFetchOne(
    "SELECT id FROM bookings WHERE driver_id = ? AND status IN ('accepted','on_the_way','arrived','trip_started','in_progress') LIMIT 1",
    [$driverId]
);
?>

<div class="driver-content">

    <!-- Current Status Display -->
    <div class="status-display status-display-<?= $currentAvailability ?>">
        <div class="status-display-icon">
            <?php
            $avIcons = ['available' => 'fa-check-circle', 'unavailable' => 'fa-pause-circle', 'off_duty' => 'fa-moon'];
            ?>
            <i class="fas <?= $avIcons[$currentAvailability] ?? 'fa-circle' ?>"></i>
        </div>
        <div class="status-display-text">
            <?= ucfirst(str_replace('_', ' ', $currentAvailability)) ?>
        </div>
        <div class="status-display-sub">
            <?php if ($currentAvailability === 'available'): ?>
                You are accepting new rides
            <?php elseif ($currentAvailability === 'unavailable'): ?>
                You won't receive new ride assignments
            <?php else: ?>
                You are off duty
            <?php endif; ?>
        </div>
    </div>

    <?php if ($hasActiveRide): ?>
    <div class="driver-alert driver-alert-warning">
        <i class="fas fa-exclamation-triangle"></i>
        You have an active ride. Complete it before going off duty.
    </div>
    <?php endif; ?>

    <!-- Status Options -->
    <div class="detail-card">
        <div class="detail-card-title"><i class="fas fa-signal"></i> Change Status</div>

        <form method="POST">
            <input type="hidden" name="availability" value="available">
            <button type="submit" class="status-option <?= $currentAvailability === 'available' ? 'status-option-active' : '' ?>" <?= $hasActiveRide ? '' : '' ?>>
                <div class="status-option-icon available-icon"><i class="fas fa-check-circle"></i></div>
                <div class="status-option-info">
                    <strong>Available</strong>
                    <span>Ready to accept rides</span>
                </div>
                <?php if ($currentAvailability === 'available'): ?>
                <i class="fas fa-check current-check"></i>
                <?php endif; ?>
            </button>
        </form>

        <form method="POST">
            <input type="hidden" name="availability" value="unavailable">
            <button type="submit" class="status-option <?= $currentAvailability === 'unavailable' ? 'status-option-active' : '' ?>">
                <div class="status-option-icon unavailable-icon"><i class="fas fa-pause-circle"></i></div>
                <div class="status-option-info">
                    <strong>Unavailable</strong>
                    <span>Temporarily not accepting rides</span>
                </div>
                <?php if ($currentAvailability === 'unavailable'): ?>
                <i class="fas fa-check current-check"></i>
                <?php endif; ?>
            </button>
        </form>

        <form method="POST">
            <input type="hidden" name="availability" value="off_duty">
            <button type="submit" class="status-option <?= $currentAvailability === 'off_duty' ? 'status-option-active' : '' ?>" <?= $hasActiveRide ? 'disabled' : '' ?>>
                <div class="status-option-icon offduty-icon"><i class="fas fa-moon"></i></div>
                <div class="status-option-info">
                    <strong>Off Duty</strong>
                    <span>End your shift for the day</span>
                </div>
                <?php if ($currentAvailability === 'off_duty'): ?>
                <i class="fas fa-check current-check"></i>
                <?php endif; ?>
            </button>
        </form>
    </div>

    <!-- Shift Info -->
    <div class="detail-card">
        <div class="detail-card-title"><i class="fas fa-clock"></i> Shift Info</div>
        <div class="detail-list">
            <?php if ($driverRecord['shift_started_at']): ?>
            <div class="detail-row">
                <span class="detail-label">Shift Started</span>
                <span class="detail-value"><?= date('g:i A', strtotime($driverRecord['shift_started_at'])) ?></span>
            </div>
            <?php endif; ?>
            <?php if ($driverRecord['shift_ended_at']): ?>
            <div class="detail-row">
                <span class="detail-label">Last Shift Ended</span>
                <span class="detail-value"><?= date('M d, g:i A', strtotime($driverRecord['shift_ended_at'])) ?></span>
            </div>
            <?php endif; ?>
            <div class="detail-row">
                <span class="detail-label">Vehicle</span>
                <span class="detail-value"><?= sanitize($driverRecord['vehicle_name'] ?? 'Not assigned') ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Total Trips</span>
                <span class="detail-value"><?= intval($driverRecord['total_trips']) ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Rating</span>
                <span class="detail-value"><i class="fas fa-star" style="color:#FFD400"></i> <?= number_format($driverRecord['rating'], 1) ?></span>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/includes/driver-footer.php'; ?>
