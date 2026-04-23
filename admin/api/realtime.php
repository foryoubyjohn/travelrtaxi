<?php
/**
 * Admin/Dispatch Real-Time API
 * Handles: poll_bookings, poll_drivers, get_driver_location, poll_stats
 * 
 * All endpoints require authenticated admin session.
 */
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

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

// ── GET requests ──
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = sanitize($_GET['action'] ?? '');

    // ────────────────────────────────────────────
    // POLL BOOKINGS (incremental: only changed since timestamp)
    // ────────────────────────────────────────────
    if ($action === 'poll_bookings') {
        $since  = sanitize($_GET['since'] ?? '');
        $filter = sanitize($_GET['filter'] ?? 'today');

        // Build base filter
        $where = [];
        $params = [];

        switch ($filter) {
            case 'today':
                $where[] = "b.booking_date = CURDATE()";
                break;
            case 'active':
                $where[] = "b.status IN ('assigned','accepted','on_the_way','arrived','trip_started','in_progress')";
                break;
            case 'unassigned':
                $where[] = "(b.driver_id IS NULL OR b.status IN ('pending','confirmed'))";
                break;
        }

        // Only fetch changes since last poll
        if ($since) {
            $where[] = "b.updated_at > ?";
            $params[] = $since;
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $bookings = dbFetchAll(
            "SELECT b.id, b.booking_ref, b.customer_name, b.customer_phone,
                    b.pickup_location, b.dropoff_location,
                    b.booking_date, b.booking_time, b.passengers,
                    b.service_type, b.vehicle_type, b.estimated_price,
                    b.status, b.driver_id, b.dispatcher_notes, b.updated_at,
                    d_user.first_name as driver_first, d_user.last_name as driver_last,
                    d_user.phone as driver_phone,
                    d_rec.availability as driver_availability,
                    d_rec.location_sharing as driver_location_sharing,
                    d_rec.last_latitude, d_rec.last_longitude, d_rec.last_location_at,
                    v.name as vehicle_name, v.plate_number
             FROM bookings b
             LEFT JOIN drivers d_rec ON b.driver_id = d_rec.id
             LEFT JOIN users d_user ON d_rec.user_id = d_user.id
             LEFT JOIN vehicles v ON b.vehicle_id = v.id
             {$whereClause}
             ORDER BY b.updated_at DESC
             LIMIT 100",
            $params
        );

        // Annotate each booking with driver location freshness
        foreach ($bookings as &$b) {
            $b['driver_location_live'] = false;
            $b['driver_location_stale'] = false;
            if ($b['last_location_at'] && $b['driver_location_sharing']) {
                $locAge = time() - strtotime($b['last_location_at']);
                $b['driver_location_live'] = ($locAge < 120); // < 2 min = live
                $b['driver_location_stale'] = ($locAge >= 120 && $locAge < 600); // 2-10 min = stale
                $b['location_age_seconds'] = $locAge;
                $b['last_location_at_formatted'] = date('g:i A', strtotime($b['last_location_at']));
            }
        }

        echo json_encode([
            'success' => true,
            'bookings' => $bookings,
            'count' => count($bookings),
            'server_time' => date('Y-m-d H:i:s')
        ]);
        exit;
    }

    // ────────────────────────────────────────────
    // POLL DRIVERS (availability + location status)
    // ────────────────────────────────────────────
    if ($action === 'poll_drivers') {
        $drivers = dbFetchAll(
            "SELECT d.id, d.status, d.availability, d.location_sharing,
                    d.last_latitude, d.last_longitude, d.last_location_at,
                    d.total_trips, d.rating,
                    u.first_name, u.last_name, u.phone,
                    v.name as vehicle_name, v.type as vehicle_type, v.plate_number, v.capacity
             FROM drivers d
             JOIN users u ON d.user_id = u.id
             LEFT JOIN vehicles v ON d.vehicle_id = v.id
             WHERE u.is_active = 1
             ORDER BY FIELD(d.availability, 'available','unavailable','off_duty'), u.first_name"
        );

        // Annotate location freshness
        foreach ($drivers as &$drv) {
            $drv['location_live'] = false;
            $drv['location_stale'] = false;
            if ($drv['last_location_at'] && $drv['location_sharing']) {
                $locAge = time() - strtotime($drv['last_location_at']);
                $drv['location_live'] = ($locAge < 120);
                $drv['location_stale'] = ($locAge >= 120 && $locAge < 600);
                $drv['location_age_seconds'] = $locAge;
                $drv['last_location_at_formatted'] = date('g:i A', strtotime($drv['last_location_at']));
            }
        }

        echo json_encode([
            'success' => true,
            'drivers' => $drivers,
            'server_time' => date('Y-m-d H:i:s')
        ]);
        exit;
    }

    // ────────────────────────────────────────────
    // GET DRIVER LOCATION HISTORY (for a specific driver)
    // ────────────────────────────────────────────
    if ($action === 'driver_location') {
        $driverId = intval($_GET['driver_id'] ?? 0);
        $limit = min(intval($_GET['limit'] ?? 20), 100);

        if (!$driverId) {
            echo json_encode(['success' => false, 'message' => 'Missing driver_id']);
            exit;
        }

        // Current position
        $current = dbFetchOne(
            "SELECT dl.*, d.location_sharing, d.last_location_at,
                    u.first_name, u.last_name
             FROM driver_locations dl
             JOIN drivers d ON dl.driver_id = d.id
             JOIN users u ON d.user_id = u.id
             WHERE dl.driver_id = ? AND dl.is_current = 1
             ORDER BY dl.created_at DESC LIMIT 1",
            [$driverId]
        );

        // Recent history
        $history = dbFetchAll(
            "SELECT latitude, longitude, accuracy, speed, heading, created_at
             FROM driver_locations
             WHERE driver_id = ?
             ORDER BY created_at DESC LIMIT ?",
            [$driverId, $limit]
        );

        echo json_encode([
            'success' => true,
            'current' => $current,
            'history' => $history,
            'server_time' => date('Y-m-d H:i:s')
        ]);
        exit;
    }

    // ────────────────────────────────────────────
    // POLL STATS (lightweight dispatch stats refresh)
    // ────────────────────────────────────────────
    if ($action === 'poll_stats') {
        $stats = [
            'active_trips' => dbFetchOne("SELECT COUNT(*) as c FROM bookings WHERE status IN ('assigned','accepted','on_the_way','arrived','trip_started','in_progress')")['c'],
            'unassigned' => dbFetchOne("SELECT COUNT(*) as c FROM bookings WHERE status IN ('pending','confirmed') AND driver_id IS NULL")['c'],
            'available_drivers' => dbFetchOne("SELECT COUNT(*) as c FROM drivers WHERE status = 'available' AND availability = 'available'")['c'],
            'revenue_today' => number_format(dbFetchOne("SELECT COALESCE(SUM(final_price),0) as r FROM bookings WHERE status = 'completed' AND DATE(trip_completed_at) = CURDATE()")['r'], 2),
            'today_bookings' => dbFetchOne("SELECT COUNT(*) as c FROM bookings WHERE booking_date = CURDATE()")['c'],
            'completed_today' => dbFetchOne("SELECT COUNT(*) as c FROM bookings WHERE status = 'completed' AND DATE(trip_completed_at) = CURDATE()")['c'],
        ];

        echo json_encode([
            'success' => true,
            'stats' => $stats,
            'server_time' => date('Y-m-d H:i:s')
        ]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request method']);
