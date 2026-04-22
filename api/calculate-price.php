<?php
/**
 * AJAX Price Calculator Endpoint
 * Returns estimated price for a booking
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$serviceType = sanitize($_POST['service_type'] ?? '');
$vehicleType = sanitize($_POST['vehicle_type'] ?? 'sedan');
$pickup = sanitize($_POST['pickup'] ?? '');
$dropoff = sanitize($_POST['dropoff'] ?? '');
$passengers = intval($_POST['passengers'] ?? 1);

$price = 0;
$breakdown = [];

// Try to find a matching route for flat pricing
$route = dbFetchOne(
    "SELECT r.*, pr.flat_price FROM routes r 
     JOIN pricing_rules pr ON pr.route_id = r.id AND pr.is_active = 1 
     WHERE (LOWER(r.origin) LIKE ? AND LOWER(r.destination) LIKE ?) 
        OR (LOWER(r.origin) LIKE ? AND LOWER(r.destination) LIKE ?)
     AND (pr.vehicle_type = ? OR pr.vehicle_type = 'all')
     LIMIT 1",
    ['%' . strtolower($pickup) . '%', '%' . strtolower($dropoff) . '%',
     '%' . strtolower($dropoff) . '%', '%' . strtolower($pickup) . '%',
     $vehicleType]
);

if ($route && $route['flat_price'] > 0) {
    $price = $route['flat_price'];
    $breakdown[] = ['item' => 'Flat rate (' . $route['origin'] . ' to ' . $route['destination'] . ')', 'amount' => $price];
} else {
    // Use distance-based pricing
    $rule = dbFetchOne(
        "SELECT * FROM pricing_rules WHERE type = 'distance' AND is_active = 1 AND (vehicle_type = ? OR vehicle_type = 'all') LIMIT 1",
        [$vehicleType]
    );

    if ($rule) {
        $baseFare = $rule['base_fare'];
        $perKm = $rule['per_km_rate'];
        $estimatedKm = 15; // Default estimate

        $price = $baseFare + ($perKm * $estimatedKm);
        $breakdown[] = ['item' => 'Base fare', 'amount' => $baseFare];
        $breakdown[] = ['item' => "Distance (~{$estimatedKm} km x " . formatPrice($perKm) . "/km)", 'amount' => $perKm * $estimatedKm];
    } else {
        // Default pricing
        $defaults = [
            'sedan' => ['base' => 500, 'per_km' => 60],
            'van' => ['base' => 800, 'per_km' => 90],
            'minibus' => ['base' => 1500, 'per_km' => 120]
        ];
        $d = $defaults[$vehicleType] ?? $defaults['sedan'];
        $estimatedKm = 15;
        $price = $d['base'] + ($d['per_km'] * $estimatedKm);
        $breakdown[] = ['item' => 'Base fare', 'amount' => $d['base']];
        $breakdown[] = ['item' => "Distance estimate (~{$estimatedKm} km)", 'amount' => $d['per_km'] * $estimatedKm];
    }
}

// Vehicle surcharge
$surcharges = ['van' => 1.3, 'minibus' => 1.8];
if (isset($surcharges[$vehicleType]) && !$route) {
    $multiplier = $surcharges[$vehicleType];
    $surcharge = $price * ($multiplier - 1);
    $price *= $multiplier;
    $breakdown[] = ['item' => ucfirst($vehicleType) . ' surcharge', 'amount' => $surcharge];
}

// Rideshare discount
if ($serviceType === 'rideshare') {
    $discount = $price * 0.3;
    $price -= $discount;
    $breakdown[] = ['item' => 'Rideshare discount (30%)', 'amount' => -$discount];
}

// Hourly pricing
if ($serviceType === 'hourly') {
    $hourlyRates = ['sedan' => 3500, 'van' => 5000, 'minibus' => 8000];
    $rate = $hourlyRates[$vehicleType] ?? 3500;
    $price = $rate;
    $breakdown = [['item' => 'Hourly rate (per hour)', 'amount' => $rate]];
}

$price = round($price, 2);

echo json_encode([
    'success' => true,
    'price' => $price,
    'formatted_price' => formatPrice($price),
    'currency' => CURRENCY,
    'breakdown' => $breakdown,
    'note' => 'Estimated fare. Final price may vary based on actual distance and conditions.'
]);
