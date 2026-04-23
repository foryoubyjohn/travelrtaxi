<?php
/**
 * Dispatch Console - AJAX Action Handler
 * Handles all dispatch actions via JSON POST/GET.
 * Access: admin + dispatcher roles only.
 */
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

// Auth guard - admin or dispatcher
if (!isLoggedIn() || (!isAdmin() && !isDispatcher())) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$dispatcherId = (int)$_SESSION['user_id'];
$action = sanitize($_REQUEST['action'] ?? '');

// ── GET actions ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    if ($action === 'get_booking') {
        $id = (int)($_GET['id'] ?? 0);
        $b = dbFetchOne(
            "SELECT b.*,
                    u.first_name AS cust_first, u.last_name AS cust_last,
                    v.name AS vehicle_name, v.plate_number, v.type AS vehicle_type_name,
                    du.first_name AS drv_first, du.last_name AS drv_last,
                    du.phone AS drv_phone
             FROM bookings b
             LEFT JOIN users     u  ON b.customer_id = u.id
             LEFT JOIN vehicles  v  ON b.vehicle_id  = v.id
             LEFT JOIN drivers   d  ON b.driver_id   = d.id
             LEFT JOIN users     du ON d.user_id      = du.id
             WHERE b.id = ?",
            [$id]
        );
        if (!$b) { echo json_encode(['success' => false, 'error' => 'Not found']); exit; }
        echo json_encode(['success' => true, 'booking' => $b]);
        exit;
    }

    if ($action === 'get_notes') {
        $id = (int)($_GET['id'] ?? 0);
        $notes = dbFetchAll(
            "SELECT dn.*, u.first_name, u.last_name
             FROM dispatch_notes dn
             JOIN users u ON dn.dispatcher_id = u.id
             WHERE dn.booking_id = ?
             ORDER BY dn.created_at DESC",
            [$id]
        );
        echo json_encode(['success' => true, 'notes' => $notes]);
        exit;
    }

    if ($action === 'get_drivers') {
        $drivers = dbFetchAll(
            "SELECT d.id, d.status, d.rating, d.total_trips,
                    u.first_name, u.last_name, u.phone,
                    v.name AS vehicle_name, v.plate_number, v.type AS vehicle_type, v.capacity
             FROM drivers d
             JOIN users u ON d.user_id = u.id
             LEFT JOIN vehicles v ON d.vehicle_id = v.id
             WHERE u.is_active = 1
             ORDER BY
               FIELD(d.status, 'available', 'on_trip', 'offline'),
               u.first_name"
        );
        echo json_encode(['success' => true, 'drivers' => $drivers]);
        exit;
    }

    if ($action === 'get_activity_log') {
        $limit = min((int)($_GET['limit'] ?? 50), 200);
        $logs = dbFetchAll(
            "SELECT dal.*, u.first_name, u.last_name, b.booking_ref
             FROM dispatcher_action_logs dal
             LEFT JOIN users    u ON dal.dispatcher_id = u.id
             LEFT JOIN bookings b ON dal.booking_id    = b.id
             ORDER BY dal.created_at DESC
             LIMIT ?",
            [$limit]
        );
        echo json_encode(['success' => true, 'logs' => $logs]);
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Unknown action']);
    exit;
}

// ── POST actions ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$bookingId = (int)($_POST['booking_id'] ?? 0);
if (!$bookingId && !in_array($action, ['driver_status'])) {
    echo json_encode(['success' => false, 'error' => 'Missing booking_id']);
    exit;
}

// ── assign_driver ────────────────────────────────────────────
if ($action === 'assign_driver') {
    $newDriverId = (int)($_POST['driver_id'] ?? 0);
    $note        = sanitize($_POST['note'] ?? '');
    if (!$newDriverId) { echo json_encode(['success' => false, 'error' => 'No driver selected']); exit; }

    $booking = dbFetchOne("SELECT driver_id, status FROM bookings WHERE id = ?", [$bookingId]);
    if (!$booking) { echo json_encode(['success' => false, 'error' => 'Booking not found']); exit; }

    $oldDriverId = $booking['driver_id'];
    $oldStatus   = $booking['status'];
    $isReassign  = !empty($oldDriverId);

    dbExecute(
        "UPDATE bookings SET driver_id = ?, status = 'assigned', updated_at = NOW() WHERE id = ?",
        [$newDriverId, $bookingId]
    );

    // Log status change if applicable
    if ($oldStatus !== 'assigned') {
        dbInsert(
            "INSERT INTO booking_status_history (booking_id, old_status, new_status, changed_by, note) VALUES (?,?,?,?,?)",
            [$bookingId, $oldStatus, 'assigned', $dispatcherId, 'Driver ' . ($isReassign ? 're' : '') . 'assigned']
        );
    }

    // Log assignment
    dbInsert(
        "INSERT INTO assignment_history (booking_id, old_driver_id, new_driver_id, assigned_by, note) VALUES (?,?,?,?,?)",
        [$bookingId, $oldDriverId ?: null, $newDriverId, $dispatcherId, $note ?: null]
    );

    // Get driver name for log
    $drvUser = dbFetchOne(
        "SELECT u.first_name, u.last_name FROM drivers d JOIN users u ON d.user_id = u.id WHERE d.id = ?",
        [$newDriverId]
    );
    $drvName = $drvUser ? $drvUser['first_name'] . ' ' . $drvUser['last_name'] : 'Driver #' . $newDriverId;

    $logType = $isReassign ? 'reassigned' : 'assigned';
    $logDesc = ($isReassign ? 'Reassigned' : 'Assigned') . " driver $drvName to booking #$bookingId";
    if ($note) $logDesc .= " — Note: $note";

    dbInsert(
        "INSERT INTO dispatcher_action_logs (dispatcher_id, booking_id, action_type, description) VALUES (?,?,?,?)",
        [$dispatcherId, $bookingId, $logType, $logDesc]
    );

    $bRef = dbFetchOne("SELECT booking_ref FROM bookings WHERE id = ?", [$bookingId]);
    echo json_encode([
        'success'  => true,
        'message'  => ($isReassign ? 'Reassigned' : 'Driver assigned') . ' successfully.',
        'ref'      => $bRef['booking_ref'] ?? '',
        'driver'   => $drvName,
    ]);
    exit;
}

// ── update_status ────────────────────────────────────────────
if ($action === 'update_status') {
    $validStatuses = ['pending','confirmed','assigned','accepted','declined',
                      'on_the_way','arrived','trip_started','in_progress',
                      'completed','cancelled','no_show'];
    $newStatus = sanitize($_POST['new_status'] ?? '');
    $note      = sanitize($_POST['note'] ?? '');

    if (!in_array($newStatus, $validStatuses)) {
        echo json_encode(['success' => false, 'error' => 'Invalid status']);
        exit;
    }

    $booking = dbFetchOne("SELECT status, driver_id FROM bookings WHERE id = ?", [$bookingId]);
    if (!$booking) { echo json_encode(['success' => false, 'error' => 'Booking not found']); exit; }

    $oldStatus = $booking['status'];
    if ($oldStatus === $newStatus) {
        echo json_encode(['success' => false, 'error' => 'Status is already ' . $newStatus]);
        exit;
    }

    dbExecute(
        "UPDATE bookings SET status = ?, updated_at = NOW() WHERE id = ?",
        [$newStatus, $bookingId]
    );

    // Sync driver availability with booking lifecycle
    if ($booking['driver_id']) {
        if (in_array($newStatus, ['completed', 'cancelled', 'no_show', 'declined'])) {
            dbExecute("UPDATE drivers SET status = 'available' WHERE id = ?", [$booking['driver_id']]);
        } elseif (in_array($newStatus, ['trip_started', 'in_progress', 'on_the_way', 'arrived'])) {
            dbExecute("UPDATE drivers SET status = 'on_trip' WHERE id = ?", [$booking['driver_id']]);
        }
    }

    dbInsert(
        "INSERT INTO booking_status_history (booking_id, old_status, new_status, changed_by, note) VALUES (?,?,?,?,?)",
        [$bookingId, $oldStatus, $newStatus, $dispatcherId, $note ?: null]
    );

    $label = ucfirst(str_replace('_', ' ', $newStatus));
    $logDesc = "Status changed from '$oldStatus' to '$newStatus' on booking #$bookingId";
    if ($note) $logDesc .= " — Note: $note";

    dbInsert(
        "INSERT INTO dispatcher_action_logs (dispatcher_id, booking_id, action_type, description) VALUES (?,?,?,?)",
        [$dispatcherId, $bookingId, 'status_changed', $logDesc]
    );

    echo json_encode(['success' => true, 'message' => "Status updated to $label.", 'new_status' => $newStatus]);
    exit;
}

// ── add_note ─────────────────────────────────────────────────
if ($action === 'add_note') {
    $note = trim($_POST['note'] ?? '');
    if (empty($note)) { echo json_encode(['success' => false, 'error' => 'Note is empty']); exit; }
    if (mb_strlen($note) > 2000) { echo json_encode(['success' => false, 'error' => 'Note too long (max 2000 chars)']); exit; }

    dbInsert(
        "INSERT INTO dispatch_notes (booking_id, dispatcher_id, note) VALUES (?,?,?)",
        [$bookingId, $dispatcherId, $note]
    );

    dbInsert(
        "INSERT INTO dispatcher_action_logs (dispatcher_id, booking_id, action_type, description) VALUES (?,?,?,?)",
        [$dispatcherId, $bookingId, 'note_added', "Dispatcher note added to booking #$bookingId"]
    );

    $user = dbFetchOne("SELECT first_name, last_name FROM users WHERE id = ?", [$dispatcherId]);
    echo json_encode([
        'success'    => true,
        'message'    => 'Note saved.',
        'note'       => $note,
        'dispatcher' => $user ? $user['first_name'] . ' ' . $user['last_name'] : 'Dispatcher',
        'created_at' => date('Y-m-d H:i:s'),
    ]);
    exit;
}

// ── driver_status ─────────────────────────────────────────────
if ($action === 'driver_status') {
    $driverId  = (int)($_POST['driver_id'] ?? 0);
    $newStatus = sanitize($_POST['driver_status'] ?? '');
    $valid     = ['available', 'on_trip', 'offline'];
    if (!$driverId || !in_array($newStatus, $valid)) {
        echo json_encode(['success' => false, 'error' => 'Invalid input']);
        exit;
    }
    dbExecute("UPDATE drivers SET status = ? WHERE id = ?", [$newStatus, $driverId]);

    $drvUser = dbFetchOne(
        "SELECT u.first_name, u.last_name FROM drivers d JOIN users u ON d.user_id = u.id WHERE d.id = ?",
        [$driverId]
    );
    $drvName = $drvUser ? $drvUser['first_name'] . ' ' . $drvUser['last_name'] : 'Driver #' . $driverId;

    dbInsert(
        "INSERT INTO dispatcher_action_logs (dispatcher_id, booking_id, action_type, description) VALUES (?,?,?,?)",
        [$dispatcherId, null, 'driver_status_changed', "Driver $drvName status set to $newStatus"]
    );

    echo json_encode(['success' => true, 'message' => "$drvName status updated to $newStatus."]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Unknown action']);
