<?php
/**
 * Helper Functions
 */

/**
 * Sanitize input
 */
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone
 */
function isValidPhone($phone) {
    return preg_match('/^[\d\-\+\(\)\s]{7,20}$/', $phone);
}

/**
 * Generate booking reference
 */
function generateBookingRef() {
    return 'TT-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
}

/**
 * Format currency
 */
function formatPrice($amount) {
    return CURRENCY_SYMBOL . number_format($amount, 2);
}

/**
 * Format date
 */
function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

/**
 * Format time
 */
function formatTime($time) {
    return date('g:i A', strtotime($time));
}

/**
 * Get status badge HTML
 */
function statusBadge($status) {
    $colors = [
        // Booking statuses (original)
        'pending'         => '#f59e0b',
        'confirmed'       => '#3b82f6',
        'assigned'        => '#8b5cf6',
        'in_progress'     => '#10b981',
        'completed'       => '#059669',
        'cancelled'       => '#ef4444',
        // Dispatch / driver lifecycle statuses
        'accepted'        => '#06b6d4',
        'declined'        => '#6b7280',
        'driver_accepted' => '#06b6d4',
        'on_the_way'      => '#f97316',
        'arrived'         => '#eab308',
        'trip_started'    => '#10b981',
        'no_show'         => '#6b7280',
        // Vehicle statuses
        'active'          => '#10b981',
        'maintenance'     => '#f59e0b',
        'retired'         => '#6b7280',
        // Driver statuses
        'available'       => '#10b981',
        'on_trip'         => '#3b82f6',
        'offline'         => '#6b7280',
        // Payment statuses
        'paid'            => '#10b981',
        'unpaid'          => '#f59e0b',
        'refunded'        => '#8b5cf6',
        // User roles
        'dispatcher'      => '#06b6d4',
    ];
    $color = $colors[$status] ?? '#6b7280';
    $label = ucfirst(str_replace('_', ' ', $status));
    return '<span class="status-badge" style="background:' . $color . '">' . $label . '</span>';
}

/**
 * Redirect with message
 */
function redirectWith($url, $type, $message) {
    $_SESSION['flash_type'] = $type;
    $_SESSION['flash_message'] = $message;
    header("Location: $url");
    exit;
}

/**
 * Get flash message
 */
function getFlash() {
    if (isset($_SESSION['flash_message'])) {
        $flash = [
            'type' => $_SESSION['flash_type'],
            'message' => $_SESSION['flash_message']
        ];
        unset($_SESSION['flash_type'], $_SESSION['flash_message']);
        return $flash;
    }
    return null;
}

/**
 * Handle file upload
 */
function uploadFile($file, $directory = 'uploads/', $allowedTypes = ['image/jpeg', 'image/png', 'image/webp']) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Upload error.'];
    }
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'error' => 'Invalid file type.'];
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'error' => 'File too large (max 5MB).'];
    }
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('img_') . '.' . $ext;
    $path = ROOT_PATH . $directory . $filename;
    if (move_uploaded_file($file['tmp_name'], $path)) {
        return ['success' => true, 'filename' => $filename, 'path' => $directory . $filename];
    }
    return ['success' => false, 'error' => 'Failed to save file.'];
}

/**
 * Get WhatsApp link
 */
function getWhatsAppLink($message = '') {
    $phone = str_replace(['-', ' ', '(', ')'], '', PHONE_WHATSAPP);
    if (empty($message)) {
        $message = WHATSAPP_MESSAGE;
    }
    return 'https://wa.me/1' . $phone . '?text=' . urlencode($message);
}

/**
 * Get call link
 */
function getCallLink() {
    $phone = str_replace(['-', ' ', '(', ')'], '', PHONE_PRIMARY);
    return 'tel:+1' . $phone;
}

/**
 * Calculate price estimate
 */
function calculatePrice($serviceType, $distanceKm = 0, $durationMin = 0, $vehicleType = 'sedan', $routeId = null) {
    // Try flat rate first for specific route
    if ($routeId) {
        $rule = dbFetchOne(
            "SELECT * FROM pricing_rules WHERE type = 'flat' AND route_id = ? AND (vehicle_type = ? OR vehicle_type = 'all') AND is_active = 1 LIMIT 1",
            [$routeId, $vehicleType]
        );
        if ($rule) {
            return ['price' => $rule['flat_price'], 'rule' => $rule['name'], 'type' => 'flat'];
        }
    }

    // Hourly rate for tours
    if ($serviceType === 'hourly' || $serviceType === 'tour') {
        $rule = dbFetchOne(
            "SELECT * FROM pricing_rules WHERE type = 'hourly' AND is_active = 1 LIMIT 1"
        );
        if ($rule) {
            $hours = max(1, ceil($durationMin / 60));
            return ['price' => $rule['per_hour_rate'] * $hours, 'rule' => $rule['name'], 'type' => 'hourly'];
        }
    }

    // Distance-based
    if ($distanceKm > 0) {
        $type = ($serviceType === 'standard') ? 'distance' : 'rideshare';
        $rule = dbFetchOne(
            "SELECT * FROM pricing_rules WHERE type = ? AND (vehicle_type = ? OR vehicle_type = 'all') AND is_active = 1 LIMIT 1",
            [$type, $vehicleType]
        );
        if (!$rule) {
            $rule = dbFetchOne(
                "SELECT * FROM pricing_rules WHERE type = 'distance' AND is_active = 1 LIMIT 1"
            );
        }
        if ($rule) {
            $price = $rule['base_fare'] + ($rule['per_km_rate'] * $distanceKm);
            if ($rule['per_minute_rate'] > 0 && $durationMin > 0) {
                $price += $rule['per_minute_rate'] * $durationMin;
            }
            return ['price' => $price, 'rule' => $rule['name'], 'type' => $rule['type']];
        }
    }

    return ['price' => 0, 'rule' => 'Contact for quote', 'type' => 'custom'];
}
