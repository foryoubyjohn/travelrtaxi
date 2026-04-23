<?php
/**
 * Driver Real-Time API
 * Handles: update_location, poll_rides, poll_ride_detail, get_sync_state
 * 
 * All endpoints require authenticated driver session.
 */
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

require_once dirname(dirname(__DIR__)) . '/includes/config.php';
require_once dirname(dirname(__DIR__)) . '/includes/db.php';
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';
require_once dirname(dirname(__DIR__)) . '/includes/helpers.php';

// Require driver auth
if (!isLoggedIn() || !isDriver()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get driver record
$driverRecord = dbFetchOne(
    "SELECT d.* FROM drivers d JOIN users u ON d.user_id = u.id WHERE u.id = ? AND u.is_active = 1",
    [$_SESSION['user_id']]
);

if (!$driverRecord) {
    echo json_encode(['success' => false, 'message' => 'Driver not found']);
    exit;
}

$driverId = $driverRecord['id'];

// ── GET requests ──
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = sanitize($_GET['action'] ?? '');

    // ────────────────────────────────────────────
    // POLL DASHBOARD (lightweight check for ride changes)
    // Returns: active ride status, pending count, new assignments
    // ────────────────────────────────────────────
    if ($action === 'poll_dashboard') {
        $since = sanitize($_GET['since'] ?? '');

        // Active ride
        $activeRide = dbFetchOne(
            "SELECT id, booking_ref, status, pickup_location, dropoff_location, 
                    customer_name, customer_phone, booking_date, booking_time,
                    estimated_price, updated_at
             FROM bookings 
             WHERE driver_id = ? AND status IN ('accepted','on_the_way','arrived','trip_started','in_progress')
             ORDER BY booking_date ASC, booking_time ASC LIMIT 1",
            [$driverId]
        );

        // Pending rides needing acceptance
        $pendingCount = dbFetchOne(
            "SELECT COUNT(*) as c FROM bookings WHERE driver_id = ? AND status = 'assigned'",
            [$driverId]
        )['c'];

        // Today's rides count
        $todayCount = dbFetchOne(
            "SELECT COUNT(*) as c FROM bookings WHERE driver_id = ? AND booking_date = CURDATE() AND status NOT IN ('cancelled','declined','no_show')",
            [$driverId]
        )['c'];

        // Check if anything changed since last poll
        $latestChange = dbFetchOne(
            "SELECT MAX(updated_at) as last_update FROM bookings WHERE driver_id = ?",
            [$driverId]
        );

        $hasChanges = true;
        if ($since && $latestChange && $latestChange['last_update']) {
            $hasChanges = strtotime($latestChange['last_update']) > strtotime($since);
        }

        echo json_encode([
            'success' => true,
            'has_changes' => $hasChanges,
            'server_time' => date('Y-m-d H:i:s'),
            'active_ride' => $activeRide,
            'pending_count' => (int)$pendingCount,
            'today_count' => (int)$todayCount,
            'driver_status' => $driverRecord['status'],
            'driver_availability' => $driverRecord['availability']
        ]);
        exit;
    }

    // ────────────────────────────────────────────
    // POLL RIDE DETAIL (check for admin-side changes to a specific booking)
    // ────────────────────────────────────────────
    if ($action === 'poll_ride') {
        $bookingId = intval($_GET['booking_id'] ?? 0);
        $lastStatus = sanitize($_GET['last_status'] ?? '');

        if (!$bookingId) {
            echo json_encode(['success' => false, 'message' => 'Missing booking_id']);
            exit;
        }

        $ride = dbFetchOne(
            "SELECT id, status, driver_id, updated_at, dispatcher_notes
             FROM bookings WHERE id = ? AND driver_id = ?",
            [$bookingId, $driverId]
        );

        if (!$ride) {
            // Ride may have been reassigned
            echo json_encode([
                'success' => true,
                'reassigned' => true,
                'message' => 'This ride has been reassigned.'
            ]);
            exit;
        }

        $changed = ($lastStatus && $ride['status'] !== $lastStatus);

        echo json_encode([
            'success' => true,
            'reassigned' => false,
            'changed' => $changed,
            'status' => $ride['status'],
            'updated_at' => $ride['updated_at'],
            'dispatcher_notes' => $ride['dispatcher_notes'],
            'server_time' => date('Y-m-d H:i:s')
        ]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action']);
    exit;
}

// ── POST requests ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action'] ?? '');

    // ────────────────────────────────────────────
    // UPDATE LOCATION (GPS coordinates from browser)
    // ────────────────────────────────────────────
    if ($action === 'update_location') {
        $lat       = floatval($_POST['latitude'] ?? 0);
        $lng       = floatval($_POST['longitude'] ?? 0);
        $accuracy  = floatval($_POST['accuracy'] ?? 0);
        $speed     = isset($_POST['speed']) ? floatval($_POST['speed']) : null;
        $heading   = isset($_POST['heading']) ? floatval($_POST['heading']) : null;
        $bookingId = intval($_POST['booking_id'] ?? 0) ?: null;

        // Validate coordinates
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180 || ($lat == 0 && $lng == 0)) {
            echo json_encode(['success' => false, 'message' => 'Invalid coordinates']);
            exit;
        }

        // Mark old locations as not current
        dbExecute(
            "UPDATE driver_locations SET is_current = 0 WHERE driver_id = ? AND is_current = 1",
            [$driverId]
        );

        // Insert new location
        dbInsert(
            "INSERT INTO driver_locations (driver_id, booking_id, latitude, longitude, accuracy, speed, heading, is_current) 
             VALUES (?, ?, ?, ?, ?, ?, ?, 1)",
            [$driverId, $bookingId, $lat, $lng, $accuracy, $speed, $heading]
        );

        // Update driver's last known position
        dbExecute(
            "UPDATE drivers SET last_latitude = ?, last_longitude = ?, last_location_at = NOW(), location_sharing = 1 WHERE id = ?",
            [$lat, $lng, $driverId]
        );

        // Update sync checkpoint
        dbExecute(
            "INSERT INTO sync_checkpoints (entity_type, entity_id, change_hash) VALUES ('location', ?, ?)
             ON DUPLICATE KEY UPDATE last_changed_at = NOW(), change_hash = ?",
            [$driverId, md5("$lat,$lng"), md5("$lat,$lng")]
        );

        echo json_encode([
            'success' => true,
            'message' => 'Location updated',
            'server_time' => date('Y-m-d H:i:s')
        ]);
        exit;
    }

    // ────────────────────────────────────────────
    // TOGGLE LOCATION SHARING
    // ────────────────────────────────────────────
    if ($action === 'toggle_location') {
        $enabled = intval($_POST['enabled'] ?? 0);
        dbExecute(
            "UPDATE drivers SET location_sharing = ? WHERE id = ?",
            [$enabled, $driverId]
        );

        if (!$enabled) {
            // Mark all locations as not current when sharing disabled
            dbExecute(
                "UPDATE driver_locations SET is_current = 0 WHERE driver_id = ? AND is_current = 1",
                [$driverId]
            );
        }

        echo json_encode([
            'success' => true,
            'message' => $enabled ? 'Location sharing enabled' : 'Location sharing disabled'
        ]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request method']);
