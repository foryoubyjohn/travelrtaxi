<?php
/**
 * Public Tracking API
 * Token-based access for customers to track their ride.
 * No authentication required — security via unique token.
 */
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/helpers.php';

$action = sanitize($_GET['action'] ?? 'status');
$token  = sanitize($_GET['token'] ?? '');

// Validate token
if (!$token || strlen($token) < 32) {
    echo json_encode(['success' => false, 'message' => 'Invalid tracking token']);
    exit;
}

// Look up booking by token
$booking = dbFetchOne(
    "SELECT b.id, b.booking_ref, b.status, b.pickup_location, b.dropoff_location,
            b.booking_date, b.booking_time, b.passengers, b.service_type,
            b.vehicle_type, b.estimated_price, b.tracking_enabled,
            b.driver_accepted_at, b.driver_arrived_at, b.trip_started_at, b.trip_completed_at,
            b.driver_id, b.updated_at,
            d.last_latitude, d.last_longitude, d.last_location_at, d.location_sharing,
            u.first_name as driver_first, u.last_name as driver_last,
            v.name as vehicle_name, v.plate_number, v.color as vehicle_color
     FROM bookings b
     LEFT JOIN drivers d ON b.driver_id = d.id
     LEFT JOIN users u ON d.user_id = u.id
     LEFT JOIN vehicles v ON b.vehicle_id = v.id
     WHERE b.tracking_token = ?",
    [$token]
);

if (!$booking) {
    echo json_encode(['success' => false, 'message' => 'Booking not found']);
    exit;
}

if (!$booking['tracking_enabled']) {
    echo json_encode(['success' => false, 'message' => 'Tracking is disabled for this booking']);
    exit;
}

// ── STATUS: Return ride status progression ──
if ($action === 'status') {
    // Build status timeline
    $timeline = [];
    $timeline[] = ['status' => 'booked', 'label' => 'Booking Confirmed', 'completed' => true, 'time' => null];

    $statusOrder = ['assigned','accepted','on_the_way','arrived','trip_started','completed'];
    $statusLabels = [
        'assigned' => 'Driver Assigned',
        'accepted' => 'Driver Accepted',
        'on_the_way' => 'Driver On the Way',
        'arrived' => 'Driver Arrived',
        'trip_started' => 'Trip In Progress',
        'completed' => 'Trip Completed'
    ];
    $statusTimes = [
        'assigned' => null,
        'accepted' => $booking['driver_accepted_at'],
        'on_the_way' => null,
        'arrived' => $booking['driver_arrived_at'],
        'trip_started' => $booking['trip_started_at'],
        'completed' => $booking['trip_completed_at']
    ];

    $currentIdx = array_search($booking['status'], $statusOrder);
    if ($currentIdx === false) $currentIdx = -1;

    foreach ($statusOrder as $idx => $s) {
        $timeline[] = [
            'status' => $s,
            'label' => $statusLabels[$s],
            'completed' => ($idx <= $currentIdx),
            'current' => ($s === $booking['status']),
            'time' => $statusTimes[$s] ? date('g:i A', strtotime($statusTimes[$s])) : null
        ];
    }

    // Driver info (limited — no phone for security)
    $driverInfo = null;
    if ($booking['driver_first'] && in_array($booking['status'], ['assigned','accepted','on_the_way','arrived','trip_started','in_progress'])) {
        $driverInfo = [
            'name' => $booking['driver_first'] . ' ' . substr($booking['driver_last'], 0, 1) . '.',
            'vehicle' => $booking['vehicle_name'],
            'plate' => $booking['plate_number'],
            'color' => $booking['vehicle_color']
        ];
    }

    // Driver location (only if sharing and ride is active)
    $driverLocation = null;
    $activeStatuses = ['on_the_way','arrived','trip_started','in_progress'];
    if ($booking['location_sharing'] && $booking['last_latitude'] && in_array($booking['status'], $activeStatuses)) {
        $locAge = time() - strtotime($booking['last_location_at']);
        if ($locAge < 600) { // Only show if < 10 min old
            $driverLocation = [
                'latitude' => (float)$booking['last_latitude'],
                'longitude' => (float)$booking['last_longitude'],
                'updated_at' => date('g:i A', strtotime($booking['last_location_at'])),
                'is_live' => ($locAge < 120),
                'age_seconds' => $locAge
            ];
        }
    }

    // Cancelled/no-show handling
    $isCancelled = in_array($booking['status'], ['cancelled','no_show','declined']);

    echo json_encode([
        'success' => true,
        'booking' => [
            'ref' => $booking['booking_ref'],
            'status' => $booking['status'],
            'status_label' => ucfirst(str_replace('_', ' ', $booking['status'])),
            'pickup' => $booking['pickup_location'],
            'dropoff' => $booking['dropoff_location'],
            'date' => date('M d, Y', strtotime($booking['booking_date'])),
            'time' => date('g:i A', strtotime($booking['booking_time'])),
            'passengers' => $booking['passengers'],
            'service_type' => ucfirst($booking['service_type']),
            'vehicle_type' => ucfirst($booking['vehicle_type']),
            'estimated_price' => number_format($booking['estimated_price'], 2)
        ],
        'timeline' => $timeline,
        'driver' => $driverInfo,
        'location' => $driverLocation,
        'is_completed' => ($booking['status'] === 'completed'),
        'is_cancelled' => $isCancelled,
        'server_time' => date('Y-m-d H:i:s')
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);
