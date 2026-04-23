<?php
/**
 * Dispatch Command Center - AJAX API
 * Handles: assign_driver, update_status, add_note, get_notes
 */
header('Content-Type: application/json');

require_once dirname(dirname(__DIR__)) . '/includes/config.php';
require_once dirname(dirname(__DIR__)) . '/includes/db.php';
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';
require_once dirname(dirname(__DIR__)) . '/includes/helpers.php';

// Admin and dispatcher roles both have dispatch access
if (!isLoggedIn() || !canDispatch()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$callerRole = $_SESSION['user_role'];

// ── GET requests ──
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = sanitize($_GET['action'] ?? '');

    if ($action === 'get_notes') {
        $bookingId = intval($_GET['booking_id'] ?? 0);
        if (!$bookingId) {
            echo json_encode(['success' => false, 'message' => 'Invalid booking ID']);
            exit;
        }

        $notes = dbFetchAll(
            "SELECT dn.*, CONCAT(u.first_name, ' ', u.last_name) as admin_name
             FROM dispatch_notes dn
             JOIN users u ON dn.admin_id = u.id
             WHERE dn.booking_id = ?
             ORDER BY dn.created_at DESC",
            [$bookingId]
        );

        // Format dates
        foreach ($notes as &$n) {
            $n['created_at'] = date('M d, g:i A', strtotime($n['created_at']));
        }

        echo json_encode(['success' => true, 'notes' => $notes]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action']);
    exit;
}

// ── POST requests ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action'] ?? '');

    // ────────────────────────────────────────────
    // ASSIGN / REASSIGN DRIVER
    // ────────────────────────────────────────────
    if ($action === 'assign_driver') {
        $bookingId    = intval($_POST['booking_id'] ?? 0);
        $newDriverId  = intval($_POST['driver_id'] ?? 0);
        $oldDriverId  = intval($_POST['old_driver_id'] ?? 0) ?: null;
        $assignedBy   = intval($_POST['assigned_by'] ?? 0);
        $reason       = sanitize($_POST['reason'] ?? '');

        if (!$bookingId || !$newDriverId) {
            echo json_encode(['success' => false, 'message' => 'Booking ID and Driver are required']);
            exit;
        }

        // Verify booking exists
        $booking = dbFetchOne("SELECT * FROM bookings WHERE id = ?", [$bookingId]);
        if (!$booking) {
            echo json_encode(['success' => false, 'message' => 'Booking not found']);
            exit;
        }

        // Verify driver exists
        $driver = dbFetchOne("SELECT d.*, u.first_name, u.last_name FROM drivers d JOIN users u ON d.user_id = u.id WHERE d.id = ?", [$newDriverId]);
        if (!$driver) {
            echo json_encode(['success' => false, 'message' => 'Driver not found']);
            exit;
        }

        // Get driver's vehicle
        $vehicleId = $driver['vehicle_id'] ?? $booking['vehicle_id'];

        // Update booking
        dbExecute(
            "UPDATE bookings SET driver_id = ?, vehicle_id = ?, status = 'assigned' WHERE id = ?",
            [$newDriverId, $vehicleId, $bookingId]
        );

        // If reassigning, free the old driver
        if ($oldDriverId && $oldDriverId != $newDriverId) {
            // Check if old driver has other active bookings
            $otherActive = dbFetchOne(
                "SELECT COUNT(*) as c FROM bookings WHERE driver_id = ? AND id != ? AND status IN ('assigned','accepted','on_the_way','arrived','trip_started','in_progress')",
                [$oldDriverId, $bookingId]
            );
            if (!$otherActive || $otherActive['c'] == 0) {
                dbExecute("UPDATE drivers SET status = 'available' WHERE id = ?", [$oldDriverId]);
            }
        }

        // Log assignment
        dbInsert(
            "INSERT INTO assignment_log (booking_id, old_driver_id, new_driver_id, assigned_by, reason) VALUES (?, ?, ?, ?, ?)",
            [$bookingId, $oldDriverId, $newDriverId, $assignedBy, $reason]
        );

        // Log status history
        dbInsert(
            "INSERT INTO booking_status_history (booking_id, old_status, new_status, changed_by, changed_by_role, notes) VALUES (?, ?, 'assigned', ?, ?, ?)",
            [$bookingId, $booking['status'], $assignedBy, $callerRole, $reason ?: 'Driver assigned via dispatch']
        );

        $driverName = $driver['first_name'] . ' ' . $driver['last_name'];
        $label = $oldDriverId ? 'Reassigned' : 'Assigned';
        echo json_encode(['success' => true, 'message' => "{$label} to {$driverName}"]);
        exit;
    }

    // ────────────────────────────────────────────
    // UPDATE STATUS
    // ────────────────────────────────────────────
    if ($action === 'update_status') {
        $bookingId  = intval($_POST['booking_id'] ?? 0);
        $newStatus  = sanitize($_POST['new_status'] ?? '');
        $oldStatus  = sanitize($_POST['old_status'] ?? '');
        $changedBy  = intval($_POST['changed_by'] ?? 0);
        $notes      = sanitize($_POST['notes'] ?? '');

        $validStatuses = ['pending','confirmed','assigned','accepted','declined','on_the_way','arrived','trip_started','in_progress','completed','no_show','cancelled'];
        if (!$bookingId || !in_array($newStatus, $validStatuses)) {
            echo json_encode(['success' => false, 'message' => 'Invalid booking or status']);
            exit;
        }

        // Verify booking
        $booking = dbFetchOne("SELECT * FROM bookings WHERE id = ?", [$bookingId]);
        if (!$booking) {
            echo json_encode(['success' => false, 'message' => 'Booking not found']);
            exit;
        }

        // Update booking status
        dbExecute("UPDATE bookings SET status = ? WHERE id = ?", [$newStatus, $bookingId]);

        // Update timestamp columns based on status
        switch ($newStatus) {
            case 'accepted':
                dbExecute("UPDATE bookings SET driver_accepted_at = NOW() WHERE id = ?", [$bookingId]);
                break;
            case 'arrived':
                dbExecute("UPDATE bookings SET driver_arrived_at = NOW() WHERE id = ?", [$bookingId]);
                break;
            case 'trip_started':
            case 'in_progress':
                dbExecute("UPDATE bookings SET trip_started_at = NOW() WHERE id = ?", [$bookingId]);
                break;
            case 'completed':
                dbExecute("UPDATE bookings SET trip_completed_at = NOW() WHERE id = ?", [$bookingId]);
                // Free the driver
                if ($booking['driver_id']) {
                    $otherActive = dbFetchOne(
                        "SELECT COUNT(*) as c FROM bookings WHERE driver_id = ? AND id != ? AND status IN ('assigned','accepted','on_the_way','arrived','trip_started','in_progress')",
                        [$booking['driver_id'], $bookingId]
                    );
                    if (!$otherActive || $otherActive['c'] == 0) {
                        dbExecute("UPDATE drivers SET status = 'available' WHERE id = ?", [$booking['driver_id']]);
                    }
                }
                break;
            case 'cancelled':
            case 'no_show':
                // Free the driver
                if ($booking['driver_id']) {
                    $otherActive = dbFetchOne(
                        "SELECT COUNT(*) as c FROM bookings WHERE driver_id = ? AND id != ? AND status IN ('assigned','accepted','on_the_way','arrived','trip_started','in_progress')",
                        [$booking['driver_id'], $bookingId]
                    );
                    if (!$otherActive || $otherActive['c'] == 0) {
                        dbExecute("UPDATE drivers SET status = 'available' WHERE id = ?", [$booking['driver_id']]);
                    }
                }
                break;
        }

        // Log status history
        dbInsert(
            "INSERT INTO booking_status_history (booking_id, old_status, new_status, changed_by, changed_by_role, notes) VALUES (?, ?, ?, ?, ?, ?)",
            [$bookingId, $oldStatus ?: $booking['status'], $newStatus, $changedBy, $callerRole, $notes ?: null]
        );

        $statusLabel = ucfirst(str_replace('_', ' ', $newStatus));
        echo json_encode(['success' => true, 'message' => "Status updated to {$statusLabel}"]);
        exit;
    }

    // ────────────────────────────────────────────
    // ADD DISPATCHER NOTE
    // ────────────────────────────────────────────
    if ($action === 'add_note') {
        $bookingId  = intval($_POST['booking_id'] ?? 0);
        $adminId    = intval($_POST['admin_id'] ?? 0);
        $note       = sanitize($_POST['note'] ?? '');
        $isPriority = intval($_POST['is_priority'] ?? 0);

        if (!$bookingId || !$note) {
            echo json_encode(['success' => false, 'message' => 'Booking ID and note text are required']);
            exit;
        }

        // Insert dispatch note
        dbInsert(
            "INSERT INTO dispatch_notes (booking_id, admin_id, note, is_priority) VALUES (?, ?, ?, ?)",
            [$bookingId, $adminId, $note, $isPriority]
        );

        // Also update the inline dispatcher_notes on the booking (latest note)
        $prefix = $isPriority ? '[PRIORITY] ' : '';
        dbExecute(
            "UPDATE bookings SET dispatcher_notes = ? WHERE id = ?",
            [$prefix . $note, $bookingId]
        );

        echo json_encode(['success' => true, 'message' => 'Note added successfully']);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request method']);
