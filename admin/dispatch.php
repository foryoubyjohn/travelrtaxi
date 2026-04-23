<?php
$pageTitle = 'Dispatch Console';
require_once 'includes/admin-header.php';

// ── Filters from GET ──────────────────────────────────────────
$filterDate    = sanitize($_GET['date']     ?? 'today');
$filterStatus  = sanitize($_GET['status']  ?? 'active');
$filterDriver  = (int)($_GET['driver']     ?? 0);
$filterVehicle = sanitize($_GET['vehicle'] ?? '');
$filterService = sanitize($_GET['service'] ?? '');
$filterSearch  = sanitize($_GET['search']  ?? '');
$filterDateFrom = sanitize($_GET['from']   ?? '');
$filterDateTo   = sanitize($_GET['to']     ?? '');

// ── Status groups ──────────────────────────────────────────────
$DISPATCH_STATUSES = [
    'pending'      => ['label' => 'Pending',         'color' => '#f59e0b', 'bg' => '#fffbeb'],
    'confirmed'    => ['label' => 'Confirmed',       'color' => '#3b82f6', 'bg' => '#eff6ff'],
    'assigned'     => ['label' => 'Assigned',        'color' => '#8b5cf6', 'bg' => '#f5f3ff'],
    'accepted'     => ['label' => 'Driver Accepted', 'color' => '#06b6d4', 'bg' => '#ecfeff'],
    'declined'     => ['label' => 'Declined',        'color' => '#6b7280', 'bg' => '#f9fafb'],
    'on_the_way'   => ['label' => 'On the Way',      'color' => '#f97316', 'bg' => '#fff7ed'],
    'arrived'      => ['label' => 'Arrived',         'color' => '#eab308', 'bg' => '#fefce8'],
    'trip_started' => ['label' => 'Trip Started',    'color' => '#10b981', 'bg' => '#ecfdf5'],
    'in_progress'  => ['label' => 'In Progress',     'color' => '#10b981', 'bg' => '#ecfdf5'],
    'completed'    => ['label' => 'Completed',       'color' => '#059669', 'bg' => '#f0fdf4'],
    'cancelled'    => ['label' => 'Cancelled',       'color' => '#ef4444', 'bg' => '#fef2f2'],
    'no_show'      => ['label' => 'No Show',         'color' => '#6b7280', 'bg' => '#f9fafb'],
];

// ── Build main booking query ────────────────────────────────────
$where  = [];
$params = [];

// Date filter
$today = date('Y-m-d');
if ($filterDate === 'today') {
    $where[] = "b.booking_date = ?";
    $params[] = $today;
} elseif ($filterDate === 'upcoming') {
    $where[] = "b.booking_date > ?";
    $params[] = $today;
} elseif ($filterDate === 'custom' && $filterDateFrom) {
    $where[] = "b.booking_date >= ?";
    $params[] = $filterDateFrom;
    if ($filterDateTo) {
        $where[] = "b.booking_date <= ?";
        $params[] = $filterDateTo;
    }
}

// Status filter
$activeStatuses = ['pending','confirmed','assigned','accepted','on_the_way','arrived','trip_started','in_progress'];
if ($filterStatus === 'active') {
    $placeholders = implode(',', array_fill(0, count($activeStatuses), '?'));
    $where[] = "b.status IN ($placeholders)";
    $params  = array_merge($params, $activeStatuses);
} elseif ($filterStatus === 'unassigned') {
    $where[] = "b.driver_id IS NULL AND b.status NOT IN ('completed','cancelled','no_show')";
} elseif ($filterStatus === 'completed') {
    $where[] = "b.status = 'completed'";
} elseif ($filterStatus === 'cancelled') {
    $where[] = "b.status IN ('cancelled','no_show')";
} elseif ($filterStatus === 'all') {
    // no status filter
} elseif (array_key_exists($filterStatus, $DISPATCH_STATUSES)) {
    $where[] = "b.status = ?";
    $params[] = $filterStatus;
}

// Driver filter
if ($filterDriver) {
    $where[] = "b.driver_id = ?";
    $params[] = $filterDriver;
}

// Vehicle type filter
if ($filterVehicle) {
    $where[] = "b.vehicle_type = ?";
    $params[] = $filterVehicle;
}

// Service type filter
if ($filterService) {
    $where[] = "b.service_type = ?";
    $params[] = $filterService;
}

// Search
if ($filterSearch) {
    $like = '%' . $filterSearch . '%';
    $where[] = "(b.booking_ref LIKE ? OR b.customer_name LIKE ? OR b.customer_phone LIKE ? OR b.pickup_location LIKE ? OR b.dropoff_location LIKE ?)";
    $params = array_merge($params, [$like, $like, $like, $like, $like]);
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$bookings = dbFetchAll(
    "SELECT b.*,
            v.name AS vehicle_name, v.plate_number,
            du.first_name AS drv_first, du.last_name AS drv_last, du.phone AS drv_phone,
            (SELECT COUNT(*) FROM dispatch_notes dn WHERE dn.booking_id = b.id) AS note_count
     FROM bookings b
     LEFT JOIN vehicles v  ON b.vehicle_id = v.id
     LEFT JOIN drivers  d  ON b.driver_id  = d.id
     LEFT JOIN users    du ON d.user_id    = du.id
     $whereSQL
     ORDER BY
       CASE b.status
         WHEN 'trip_started' THEN 1
         WHEN 'in_progress'  THEN 2
         WHEN 'on_the_way'   THEN 3
         WHEN 'arrived'      THEN 4
         WHEN 'accepted'     THEN 5
         WHEN 'assigned'     THEN 6
         WHEN 'confirmed'    THEN 7
         WHEN 'pending'      THEN 8
         WHEN 'declined'     THEN 9
         WHEN 'completed'    THEN 10
         WHEN 'cancelled'    THEN 11
         WHEN 'no_show'      THEN 12
         ELSE 13
       END,
       b.booking_date ASC, b.booking_time ASC",
    $params
);

// ── Operational widgets ──────────────────────────────────────────
$widgets = dbFetchOne(
    "SELECT
        SUM(CASE WHEN b.status IN ('trip_started','in_progress','on_the_way','arrived','accepted') THEN 1 ELSE 0 END) AS active_trips,
        SUM(CASE WHEN b.driver_id IS NULL AND b.status NOT IN ('completed','cancelled','no_show') THEN 1 ELSE 0 END) AS unassigned,
        SUM(CASE WHEN b.status = 'completed' AND b.booking_date = CURDATE() THEN 1 ELSE 0 END) AS completed_today,
        SUM(CASE WHEN b.status = 'completed' AND b.booking_date = CURDATE() THEN COALESCE(b.final_price, b.estimated_price, 0) ELSE 0 END) AS revenue_today
     FROM bookings b"
);

$driverStats = dbFetchOne(
    "SELECT
        SUM(CASE WHEN d.status = 'available' AND u.is_active = 1 THEN 1 ELSE 0 END) AS available_drivers,
        SUM(CASE WHEN d.status = 'on_trip'   AND u.is_active = 1 THEN 1 ELSE 0 END) AS drivers_on_trip,
        SUM(CASE WHEN d.status = 'offline'   AND u.is_active = 1 THEN 1 ELSE 0 END) AS drivers_offline
     FROM drivers d JOIN users u ON d.user_id = u.id"
);

// ── Drivers list for assign modal ────────────────────────────────
$allDrivers = dbFetchAll(
    "SELECT d.id, d.status, d.rating,
            u.first_name, u.last_name, u.phone,
            v.name AS vehicle_name, v.plate_number, v.type AS vtype, v.capacity
     FROM drivers d
     JOIN users u ON d.user_id = u.id
     LEFT JOIN vehicles v ON d.vehicle_id = v.id
     WHERE u.is_active = 1
     ORDER BY FIELD(d.status,'available','on_trip','offline'), u.first_name"
);

// ── Recent activity log ──────────────────────────────────────────
$activityLog = dbFetchAll(
    "SELECT dal.*, u.first_name, u.last_name, b.booking_ref
     FROM dispatcher_action_logs dal
     LEFT JOIN users u    ON dal.dispatcher_id = u.id
     LEFT JOIN bookings b ON dal.booking_id    = b.id
     ORDER BY dal.created_at DESC
     LIMIT 30"
);

// ── Group bookings by status ─────────────────────────────────────
$grouped = [];
foreach ($DISPATCH_STATUSES as $s => $meta) {
    $grouped[$s] = [];
}
foreach ($bookings as $b) {
    $s = $b['status'];
    if (!isset($grouped[$s])) $grouped[$s] = [];
    $grouped[$s][] = $b;
}

// ── Helpers ──────────────────────────────────────────────────────
$statusLabel = function(string $s) use ($DISPATCH_STATUSES): string {
    return $DISPATCH_STATUSES[$s]['label'] ?? ucfirst(str_replace('_', ' ', $s));
};
$statusColor = function(string $s) use ($DISPATCH_STATUSES): string {
    return $DISPATCH_STATUSES[$s]['color'] ?? '#6b7280';
};
$statusBg = function(string $s) use ($DISPATCH_STATUSES): string {
    return $DISPATCH_STATUSES[$s]['bg'] ?? '#f9fafb';
};

function waLink(string $phone, string $name, string $ref): string {
    $clean = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($clean) === 10) $clean = '1' . $clean;
    $msg = urlencode("Hi $name, this is Travelr Taxi regarding booking $ref.");
    return "https://wa.me/{$clean}?text={$msg}";
}
?>

<!-- Dispatch Top Bar -->
<div class="dispatch-topbar">
    <div class="dispatch-topbar-left">
        <h1 class="dispatch-title"><i class="fas fa-broadcast-tower"></i> Dispatch Console</h1>
        <span class="dispatch-live" id="liveIndicator"><span class="live-dot"></span> LIVE</span>
    </div>
    <div class="dispatch-topbar-right">
        <span class="dispatch-clock" id="dispatchClock"></span>
        <button class="btn btn-sm btn-outline" id="refreshBtn" onclick="location.reload()">
            <i class="fas fa-sync-alt"></i> Refresh
        </button>
        <span class="dispatch-autorefresh" id="autoRefreshLabel">Auto-refresh: <strong id="countdown">60</strong>s</span>
    </div>
</div>

<!-- Operational Widgets -->
<div class="dispatch-widgets">
    <div class="dw-card dw-active">
        <div class="dw-icon"><i class="fas fa-car-side"></i></div>
        <div class="dw-info">
            <div class="dw-value"><?php echo (int)($widgets['active_trips'] ?? 0); ?></div>
            <div class="dw-label">Active Trips</div>
        </div>
    </div>
    <div class="dw-card dw-unassigned">
        <div class="dw-icon"><i class="fas fa-exclamation-circle"></i></div>
        <div class="dw-info">
            <div class="dw-value"><?php echo (int)($widgets['unassigned'] ?? 0); ?></div>
            <div class="dw-label">Unassigned</div>
        </div>
    </div>
    <div class="dw-card dw-available">
        <div class="dw-icon"><i class="fas fa-user-check"></i></div>
        <div class="dw-info">
            <div class="dw-value"><?php echo (int)($driverStats['available_drivers'] ?? 0); ?></div>
            <div class="dw-label">Available Drivers</div>
        </div>
    </div>
    <div class="dw-card dw-on-trip">
        <div class="dw-icon"><i class="fas fa-road"></i></div>
        <div class="dw-info">
            <div class="dw-value"><?php echo (int)($driverStats['drivers_on_trip'] ?? 0); ?></div>
            <div class="dw-label">Drivers on Trip</div>
        </div>
    </div>
    <div class="dw-card dw-completed">
        <div class="dw-icon"><i class="fas fa-flag-checkered"></i></div>
        <div class="dw-info">
            <div class="dw-value"><?php echo (int)($widgets['completed_today'] ?? 0); ?></div>
            <div class="dw-label">Completed Today</div>
        </div>
    </div>
    <div class="dw-card dw-revenue">
        <div class="dw-icon"><i class="fas fa-dollar-sign"></i></div>
        <div class="dw-info">
            <div class="dw-value"><?php echo formatPrice($widgets['revenue_today'] ?? 0); ?></div>
            <div class="dw-label">Revenue Today</div>
        </div>
    </div>
</div>

<!-- Filter Bar -->
<form method="GET" action="/admin/dispatch.php" id="filterForm" class="dispatch-filters">
    <div class="df-row">
        <!-- Quick date -->
        <div class="df-group">
            <label>Date Range</label>
            <div class="df-btns">
                <?php foreach (['today' => 'Today', 'upcoming' => 'Upcoming', 'all' => 'All Dates', 'custom' => 'Custom'] as $v => $l): ?>
                <button type="submit" name="date" value="<?php echo $v; ?>"
                    class="df-btn <?php echo $filterDate === $v ? 'active' : ''; ?>"
                    onclick="syncFilter('date','<?php echo $v; ?>')"
                ><?php echo $l; ?></button>
                <?php endforeach; ?>
            </div>
        </div>
        <!-- Custom date range -->
        <div class="df-group df-custom-date <?php echo $filterDate === 'custom' ? '' : 'hidden'; ?>" id="customDateGroup">
            <label>From</label>
            <input type="date" name="from" value="<?php echo $filterDateFrom; ?>" class="df-input">
            <label>To</label>
            <input type="date" name="to"   value="<?php echo $filterDateTo; ?>"   class="df-input">
        </div>
        <!-- Status -->
        <div class="df-group">
            <label>Status</label>
            <div class="df-btns">
                <?php foreach (['active' => 'Active', 'unassigned' => 'Unassigned', 'completed' => 'Completed', 'cancelled' => 'Cancelled', 'all' => 'All'] as $v => $l): ?>
                <button type="submit" name="status" value="<?php echo $v; ?>"
                    class="df-btn <?php echo $filterStatus === $v ? 'active' : ''; ?>"
                    onclick="syncFilter('status','<?php echo $v; ?>')"
                ><?php echo $l; ?></button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="df-row df-row2">
        <!-- Driver filter -->
        <div class="df-group">
            <label>Driver</label>
            <select name="driver" class="df-select" onchange="this.form.submit()">
                <option value="">All Drivers</option>
                <?php foreach ($allDrivers as $d): ?>
                <option value="<?php echo $d['id']; ?>" <?php echo $filterDriver === $d['id'] ? 'selected' : ''; ?>>
                    <?php echo sanitize($d['first_name'] . ' ' . $d['last_name']); ?>
                    (<?php echo ucfirst($d['status']); ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <!-- Vehicle type -->
        <div class="df-group">
            <label>Vehicle Type</label>
            <select name="vehicle" class="df-select" onchange="this.form.submit()">
                <option value="">All Types</option>
                <?php foreach (['sedan' => 'Sedan', 'van' => 'Van', 'minibus' => 'Minibus'] as $v => $l): ?>
                <option value="<?php echo $v; ?>" <?php echo $filterVehicle === $v ? 'selected' : ''; ?>><?php echo $l; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <!-- Service type -->
        <div class="df-group">
            <label>Service</label>
            <select name="service" class="df-select" onchange="this.form.submit()">
                <option value="">All Services</option>
                <?php foreach (['standard' => 'Standard', 'airport' => 'Airport', 'tour' => 'Tour', 'hourly' => 'Hourly'] as $v => $l): ?>
                <option value="<?php echo $v; ?>" <?php echo $filterService === $v ? 'selected' : ''; ?>><?php echo $l; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <!-- Search -->
        <div class="df-group df-search">
            <label>Search</label>
            <div class="df-search-wrap">
                <input type="text" name="search" value="<?php echo $filterSearch; ?>"
                    placeholder="Ref, name, phone, location…" class="df-input df-search-input">
                <button type="submit" class="df-search-btn"><i class="fas fa-search"></i></button>
                <?php if ($filterSearch): ?>
                <a href="?" class="df-clear-btn" title="Clear search"><i class="fas fa-times"></i></a>
                <?php endif; ?>
            </div>
        </div>
        <!-- Hidden fields to preserve other filters -->
        <input type="hidden" name="date"    id="h_date"    value="<?php echo $filterDate; ?>">
        <input type="hidden" name="status"  id="h_status"  value="<?php echo $filterStatus; ?>">
    </div>
</form>

<!-- Results summary -->
<div class="dispatch-summary">
    <span class="ds-count"><strong><?php echo count($bookings); ?></strong> booking<?php echo count($bookings) !== 1 ? 's' : ''; ?> shown</span>
    <div class="ds-legend">
        <?php foreach ($DISPATCH_STATUSES as $s => $meta): ?>
        <?php $cnt = count($grouped[$s]); if (!$cnt) continue; ?>
        <span class="ds-badge" style="background:<?php echo $meta['color']; ?>">
            <?php echo $meta['label']; ?> <strong><?php echo $cnt; ?></strong>
        </span>
        <?php endforeach; ?>
    </div>
</div>

<!-- ── Dispatch Board ──────────────────────────────────────────── -->
<?php if (empty($bookings)): ?>
<div class="dispatch-empty">
    <i class="fas fa-inbox"></i>
    <p>No bookings match the current filters.</p>
    <a href="/admin/dispatch.php" class="btn btn-outline">Clear Filters</a>
</div>
<?php else: ?>

<?php foreach ($DISPATCH_STATUSES as $statusKey => $statusMeta):
    $rows = $grouped[$statusKey];
    if (empty($rows)) continue;
?>
<div class="dispatch-group" id="group-<?php echo $statusKey; ?>">
    <div class="dg-header" onclick="toggleGroup('<?php echo $statusKey; ?>')">
        <div class="dg-header-left">
            <span class="dg-dot" style="background:<?php echo $statusMeta['color']; ?>"></span>
            <span class="dg-title"><?php echo $statusMeta['label']; ?></span>
            <span class="dg-count"><?php echo count($rows); ?></span>
        </div>
        <i class="fas fa-chevron-down dg-chevron" id="chev-<?php echo $statusKey; ?>"></i>
    </div>

    <div class="dg-body" id="body-<?php echo $statusKey; ?>">
        <div class="table-responsive">
        <table class="dispatch-table">
            <thead>
                <tr>
                    <th style="width:110px">Booking Ref</th>
                    <th>Customer</th>
                    <th>Route</th>
                    <th style="width:120px">Date / Time</th>
                    <th style="width:80px">Service</th>
                    <th style="width:60px">Pax</th>
                    <th style="width:90px">Fare</th>
                    <th>Driver / Vehicle</th>
                    <th style="width:130px">Status</th>
                    <th style="width:200px">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $b):
                $sc      = $statusColor($b['status']);
                $drvName = ($b['drv_first'] ?? '') ? sanitize($b['drv_first'] . ' ' . $b['drv_last']) : null;
                $fare    = $b['final_price'] ?? $b['estimated_price'] ?? 0;
                $isToday = $b['booking_date'] === $today;
                $isPast  = $b['booking_date'] < $today;
            ?>
            <tr class="dr-row <?php echo $isPast && !in_array($b['status'],['completed','cancelled','no_show']) ? 'dr-overdue' : ''; ?>"
                style="border-left: 4px solid <?php echo $sc; ?>">
                <td>
                    <strong class="dr-ref"><?php echo sanitize($b['booking_ref']); ?></strong>
                    <?php if ($b['note_count'] > 0): ?>
                    <span class="dr-note-badge" title="<?php echo $b['note_count']; ?> note(s)">
                        <i class="fas fa-sticky-note"></i><?php echo $b['note_count']; ?>
                    </span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="dr-customer">
                        <strong><?php echo sanitize($b['customer_name']); ?></strong>
                        <div class="dr-contact-links">
                            <a href="tel:<?php echo sanitize($b['customer_phone']); ?>" class="dr-contact-btn dr-call" title="Call">
                                <i class="fas fa-phone"></i>
                            </a>
                            <a href="<?php echo waLink($b['customer_phone'], $b['customer_name'], $b['booking_ref']); ?>"
                               target="_blank" class="dr-contact-btn dr-wa" title="WhatsApp">
                                <i class="fab fa-whatsapp"></i>
                            </a>
                        </div>
                        <small class="dr-phone"><?php echo sanitize($b['customer_phone']); ?></small>
                    </div>
                </td>
                <td class="dr-route">
                    <div class="dr-route-from"><i class="fas fa-map-marker-alt dr-icon-pickup"></i><?php echo sanitize(substr($b['pickup_location'], 0, 30)); ?><?php echo strlen($b['pickup_location']) > 30 ? '…' : ''; ?></div>
                    <div class="dr-route-to"><i class="fas fa-flag-checkered dr-icon-drop"></i><?php echo sanitize(substr($b['dropoff_location'], 0, 30)); ?><?php echo strlen($b['dropoff_location']) > 30 ? '…' : ''; ?></div>
                </td>
                <td>
                    <div class="dr-datetime">
                        <span class="dr-date <?php echo $isToday ? 'dr-today' : ($isPast ? 'dr-past' : ''); ?>">
                            <?php echo $isToday ? 'TODAY' : formatDate($b['booking_date']); ?>
                        </span>
                        <span class="dr-time"><?php echo formatTime($b['booking_time']); ?></span>
                    </div>
                </td>
                <td>
                    <span class="dr-service dr-service-<?php echo $b['service_type']; ?>">
                        <?php echo ucfirst($b['service_type']); ?>
                    </span>
                    <div><small class="dr-vtype"><?php echo ucfirst($b['vehicle_type']); ?></small></div>
                </td>
                <td class="dr-pax"><i class="fas fa-user"></i> <?php echo $b['passengers']; ?></td>
                <td class="dr-fare">
                    <?php echo $fare > 0 ? '<strong>' . formatPrice($fare) . '</strong>' : '<span class="text-muted">TBD</span>'; ?>
                    <?php if ($b['final_price'] && $b['estimated_price'] && $b['final_price'] != $b['estimated_price']): ?>
                    <div><small class="dr-est">(est. <?php echo formatPrice($b['estimated_price']); ?>)</small></div>
                    <?php endif; ?>
                </td>
                <td class="dr-driver">
                    <?php if ($drvName): ?>
                    <div class="dr-driver-assigned">
                        <i class="fas fa-id-badge dr-icon-driver"></i>
                        <div>
                            <strong><?php echo $drvName; ?></strong>
                            <?php if ($b['drv_phone']): ?>
                            <div class="dr-contact-links">
                                <a href="tel:<?php echo sanitize($b['drv_phone']); ?>" class="dr-contact-btn dr-call" title="Call Driver"><i class="fas fa-phone"></i></a>
                                <a href="<?php echo waLink($b['drv_phone'], $drvName, $b['booking_ref']); ?>" target="_blank" class="dr-contact-btn dr-wa" title="WhatsApp Driver"><i class="fab fa-whatsapp"></i></a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($b['vehicle_name']): ?>
                    <small class="dr-vehicle"><?php echo sanitize($b['vehicle_name']); ?> · <?php echo sanitize($b['plate_number'] ?? ''); ?></small>
                    <?php endif; ?>
                    <?php else: ?>
                    <span class="dr-unassigned"><i class="fas fa-question-circle"></i> Unassigned</span>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="dr-status-badge" style="background:<?php echo $sc; ?>">
                        <?php echo $statusLabel($b['status']); ?>
                    </span>
                </td>
                <td class="dr-actions">
                    <!-- Assign / Reassign -->
                    <button class="da-btn da-assign" title="<?php echo $drvName ? 'Reassign Driver' : 'Assign Driver'; ?>"
                        onclick="openAssignModal(<?php echo $b['id']; ?>, '<?php echo sanitize($b['booking_ref']); ?>', <?php echo $b['driver_id'] ? $b['driver_id'] : 'null'; ?>)">
                        <i class="fas fa-user-plus"></i>
                    </button>
                    <!-- Update Status -->
                    <button class="da-btn da-status" title="Update Status"
                        onclick="openStatusModal(<?php echo $b['id']; ?>, '<?php echo sanitize($b['booking_ref']); ?>', '<?php echo $b['status']; ?>')">
                        <i class="fas fa-exchange-alt"></i>
                    </button>
                    <!-- Notes -->
                    <button class="da-btn da-notes <?php echo $b['note_count'] > 0 ? 'da-has-notes' : ''; ?>" title="Dispatcher Notes (<?php echo $b['note_count']; ?>)"
                        onclick="openNotesModal(<?php echo $b['id']; ?>, '<?php echo sanitize($b['booking_ref']); ?>')">
                        <i class="fas fa-sticky-note"></i>
                    </button>
                    <!-- View full detail -->
                    <a href="/admin/booking-detail.php?id=<?php echo $b['id']; ?>" class="da-btn da-view" title="View Full Detail" target="_blank">
                        <i class="fas fa-external-link-alt"></i>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<!-- ── Activity Log ──────────────────────────────────────────── -->
<div class="dispatch-section-header" style="margin-top:30px">
    <h2><i class="fas fa-history"></i> Dispatch Activity Log</h2>
    <span class="ds-count">Last 30 actions</span>
</div>
<div class="admin-card" style="padding:0;overflow:hidden">
    <?php if (empty($activityLog)): ?>
    <p style="padding:20px;color:#666;text-align:center">No dispatch activity recorded yet.</p>
    <?php else: ?>
    <table class="dispatch-log-table">
        <thead>
            <tr>
                <th style="width:160px">Time</th>
                <th style="width:130px">Dispatcher</th>
                <th style="width:110px">Booking</th>
                <th style="width:120px">Action</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($activityLog as $log):
            $actionColors = [
                'assigned'             => '#8b5cf6',
                'reassigned'           => '#f97316',
                'status_changed'       => '#3b82f6',
                'note_added'           => '#10b981',
                'driver_status_changed'=> '#06b6d4',
            ];
            $ac = $actionColors[$log['action_type']] ?? '#6b7280';
        ?>
        <tr>
            <td class="log-time"><?php echo date('M d, g:i A', strtotime($log['created_at'])); ?></td>
            <td><?php echo $log['first_name'] ? sanitize($log['first_name'] . ' ' . $log['last_name']) : '<em>System</em>'; ?></td>
            <td>
                <?php if ($log['booking_ref']): ?>
                <a href="/admin/booking-detail.php?id=<?php echo $log['booking_id']; ?>" class="log-ref"><?php echo sanitize($log['booking_ref']); ?></a>
                <?php else: ?>—<?php endif; ?>
            </td>
            <td><span class="log-action-badge" style="background:<?php echo $ac; ?>"><?php echo ucfirst(str_replace('_', ' ', $log['action_type'])); ?></span></td>
            <td class="log-desc"><?php echo sanitize($log['description'] ?? ''); ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- ═══════════════════════════════════════════════════════════
     MODALS
     ═══════════════════════════════════════════════════════ -->

<!-- Assign Driver Modal -->
<div class="dispatch-modal" id="assignModal" role="dialog" aria-modal="true">
    <div class="dm-backdrop" onclick="closeModal('assignModal')"></div>
    <div class="dm-panel dm-panel-lg">
        <div class="dm-header">
            <h3 id="assignModalTitle"><i class="fas fa-user-plus"></i> Assign Driver</h3>
            <button class="dm-close" onclick="closeModal('assignModal')">&times;</button>
        </div>
        <div class="dm-body">
            <p class="dm-booking-ref">Booking: <strong id="assignBookingRef"></strong></p>
            <input type="text" id="driverSearch" class="df-input" placeholder="Search driver name or plate…" oninput="filterDriverList(this.value)" style="margin-bottom:12px;width:100%">
            <div id="driverList" class="driver-list">
                <?php foreach ($allDrivers as $d):
                    $statusClr = $d['status'] === 'available' ? '#10b981' : ($d['status'] === 'on_trip' ? '#3b82f6' : '#6b7280');
                ?>
                <div class="dl-item" data-search="<?php echo strtolower(sanitize($d['first_name'] . ' ' . $d['last_name'] . ' ' . ($d['plate_number'] ?? ''))); ?>">
                    <div class="dl-info">
                        <div class="dl-name">
                            <strong><?php echo sanitize($d['first_name'] . ' ' . $d['last_name']); ?></strong>
                            <span class="dl-status-dot" style="background:<?php echo $statusClr; ?>" title="<?php echo ucfirst($d['status']); ?>"></span>
                            <span class="dl-status-text"><?php echo ucfirst(str_replace('_', ' ', $d['status'])); ?></span>
                        </div>
                        <div class="dl-vehicle">
                            <?php if ($d['vehicle_name']): ?>
                            <i class="fas fa-car"></i> <?php echo sanitize($d['vehicle_name']); ?>
                            &nbsp;·&nbsp; <?php echo sanitize($d['plate_number'] ?? ''); ?>
                            &nbsp;·&nbsp; Cap: <?php echo $d['capacity'] ?? '—'; ?>
                            <?php else: ?><em>No vehicle assigned</em><?php endif; ?>
                        </div>
                        <div class="dl-rating"><i class="fas fa-star" style="color:#FFD400"></i> <?php echo number_format($d['rating'] ?? 5, 1); ?></div>
                    </div>
                    <button class="btn btn-sm btn-primary dl-select-btn"
                        onclick="selectDriver(<?php echo $d['id']; ?>, '<?php echo sanitize($d['first_name'] . ' ' . $d['last_name']); ?>')">
                        Select
                    </button>
                </div>
                <?php endforeach; ?>
                <?php if (empty($allDrivers)): ?>
                <p style="color:#666;padding:20px;text-align:center">No active drivers found.</p>
                <?php endif; ?>
            </div>
            <div class="dm-note-group" style="margin-top:14px">
                <label>Assignment Note (optional)</label>
                <input type="text" id="assignNote" class="df-input" placeholder="e.g. Preferred driver requested by customer">
            </div>
        </div>
        <div class="dm-footer">
            <button class="btn btn-outline" onclick="closeModal('assignModal')">Cancel</button>
            <button class="btn btn-primary" id="confirmAssignBtn" disabled onclick="confirmAssign()">
                <i class="fas fa-check"></i> Confirm Assignment
            </button>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div class="dispatch-modal" id="statusModal" role="dialog" aria-modal="true">
    <div class="dm-backdrop" onclick="closeModal('statusModal')"></div>
    <div class="dm-panel">
        <div class="dm-header">
            <h3><i class="fas fa-exchange-alt"></i> Update Status</h3>
            <button class="dm-close" onclick="closeModal('statusModal')">&times;</button>
        </div>
        <div class="dm-body">
            <p class="dm-booking-ref">Booking: <strong id="statusBookingRef"></strong></p>
            <p>Current: <span id="currentStatusBadge"></span></p>
            <div class="status-grid">
                <?php foreach ($DISPATCH_STATUSES as $s => $meta): ?>
                <button class="sg-btn" data-status="<?php echo $s; ?>"
                    style="--sc:<?php echo $meta['color']; ?>"
                    onclick="selectStatus('<?php echo $s; ?>')">
                    <?php echo $meta['label']; ?>
                </button>
                <?php endforeach; ?>
            </div>
            <div class="dm-note-group" style="margin-top:14px">
                <label>Status Note (optional)</label>
                <input type="text" id="statusNote" class="df-input" placeholder="Reason or additional context…">
            </div>
        </div>
        <div class="dm-footer">
            <button class="btn btn-outline" onclick="closeModal('statusModal')">Cancel</button>
            <button class="btn btn-primary" id="confirmStatusBtn" disabled onclick="confirmStatus()">
                <i class="fas fa-check"></i> Update Status
            </button>
        </div>
    </div>
</div>

<!-- Notes Modal -->
<div class="dispatch-modal" id="notesModal" role="dialog" aria-modal="true">
    <div class="dm-backdrop" onclick="closeModal('notesModal')"></div>
    <div class="dm-panel">
        <div class="dm-header">
            <h3><i class="fas fa-sticky-note"></i> Dispatcher Notes</h3>
            <button class="dm-close" onclick="closeModal('notesModal')">&times;</button>
        </div>
        <div class="dm-body">
            <p class="dm-booking-ref">Booking: <strong id="notesBookingRef"></strong></p>
            <div id="notesList" class="notes-list"></div>
            <div class="dm-note-group" style="margin-top:14px">
                <label>Add Note</label>
                <textarea id="newNote" class="df-textarea" rows="3" placeholder="Enter dispatcher note…"></textarea>
            </div>
        </div>
        <div class="dm-footer">
            <button class="btn btn-outline" onclick="closeModal('notesModal')">Close</button>
            <button class="btn btn-primary" onclick="submitNote()">
                <i class="fas fa-save"></i> Save Note
            </button>
        </div>
    </div>
</div>

<!-- Toast notification -->
<div id="dispatchToast" class="dispatch-toast" role="status" aria-live="polite"></div>

<!-- ══ JavaScript ═══════════════════════════════════════════════ -->
<script>
// ── State ────────────────────────────────────────────────────
var _assignBookingId  = null;
var _assignSelectedId = null;
var _statusBookingId  = null;
var _selectedStatus   = null;
var _notesBookingId   = null;
var _countdown        = 60;
var _cdInterval       = null;

// ── Clock ───────────────────────────────────────────────────
function updateClock() {
    var now = new Date();
    document.getElementById('dispatchClock').textContent =
        now.toLocaleTimeString('en-US', {hour:'2-digit',minute:'2-digit',second:'2-digit'});
}
updateClock();
setInterval(updateClock, 1000);

// ── Auto-refresh countdown ──────────────────────────────────
function startCountdown() {
    _countdown = 60;
    _cdInterval = setInterval(function() {
        _countdown--;
        var el = document.getElementById('countdown');
        if (el) el.textContent = _countdown;
        if (_countdown <= 0) {
            clearInterval(_cdInterval);
            location.reload();
        }
    }, 1000);
}
startCountdown();

// ── Modal helpers ────────────────────────────────────────────
function openModal(id) {
    document.getElementById(id).classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeModal(id) {
    document.getElementById(id).classList.remove('open');
    document.body.style.overflow = '';
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        ['assignModal','statusModal','notesModal'].forEach(closeModal);
    }
});

// ── Toast ────────────────────────────────────────────────────
function showToast(msg, type) {
    var t = document.getElementById('dispatchToast');
    t.textContent = msg;
    t.className = 'dispatch-toast show dispatch-toast-' + (type || 'success');
    setTimeout(function() { t.className = 'dispatch-toast'; }, 4000);
}

// ── Group collapse ───────────────────────────────────────────
function toggleGroup(key) {
    var body = document.getElementById('body-' + key);
    var chev = document.getElementById('chev-' + key);
    if (!body) return;
    var open = body.style.display !== 'none';
    body.style.display = open ? 'none' : '';
    if (chev) chev.style.transform = open ? 'rotate(-90deg)' : '';
}

// ── Filter helpers ────────────────────────────────────────────
function syncFilter(name, val) {
    document.getElementById('h_' + name).value = val;
    if (val === 'custom') {
        document.getElementById('customDateGroup').classList.remove('hidden');
    } else if (name === 'date') {
        document.getElementById('customDateGroup').classList.add('hidden');
    }
}

// ── ASSIGN MODAL ────────────────────────────────────────────
function openAssignModal(bookingId, ref, currentDriverId) {
    _assignBookingId  = bookingId;
    _assignSelectedId = null;
    document.getElementById('assignBookingRef').textContent = ref;
    document.getElementById('assignNote').value = '';
    document.getElementById('driverSearch').value = '';
    document.getElementById('confirmAssignBtn').disabled = true;
    // Reset selection highlight
    document.querySelectorAll('.dl-item').forEach(function(el) {
        el.classList.remove('dl-selected');
    });
    filterDriverList('');
    openModal('assignModal');
}

function filterDriverList(q) {
    q = q.toLowerCase();
    document.querySelectorAll('.dl-item').forEach(function(el) {
        var s = el.dataset.search || '';
        el.style.display = (!q || s.includes(q)) ? '' : 'none';
    });
}

function selectDriver(driverId, driverName) {
    _assignSelectedId = driverId;
    document.querySelectorAll('.dl-item').forEach(function(el) {
        el.classList.remove('dl-selected');
    });
    // Find matching item and highlight
    document.querySelectorAll('.dl-item').forEach(function(el) {
        var btn = el.querySelector('.dl-select-btn');
        if (btn && btn.getAttribute('onclick').includes('(' + driverId + ',')) {
            el.classList.add('dl-selected');
        }
    });
    document.getElementById('confirmAssignBtn').disabled = false;
    document.getElementById('confirmAssignBtn').textContent = '✓ Assign ' + driverName;
}

function confirmAssign() {
    if (!_assignBookingId || !_assignSelectedId) return;
    var note = document.getElementById('assignNote').value;
    var btn  = document.getElementById('confirmAssignBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Assigning…';

    fetch('/admin/dispatch-action.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            action: 'assign_driver',
            booking_id: _assignBookingId,
            driver_id:  _assignSelectedId,
            note:       note
        })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        closeModal('assignModal');
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(function() { location.reload(); }, 1200);
        } else {
            showToast(data.error || 'Assignment failed.', 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check"></i> Confirm Assignment';
        }
    })
    .catch(function() {
        showToast('Network error. Please try again.', 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check"></i> Confirm Assignment';
    });
}

// ── STATUS MODAL ────────────────────────────────────────────
var _scMap = {}, _slMap = {};
<?php foreach ($DISPATCH_STATUSES as $sk => $sm): ?>
_scMap['<?php echo $sk; ?>'] = '<?php echo $sm['color']; ?>';
_slMap['<?php echo $sk; ?>'] = '<?php echo $sm['label']; ?>';
<?php endforeach; ?>

function openStatusModal(bookingId, ref, currentStatus) {
    _statusBookingId = bookingId;
    _selectedStatus  = null;
    document.getElementById('statusBookingRef').textContent = ref;
    var badge = document.getElementById('currentStatusBadge');
    badge.innerHTML = '<span class="dr-status-badge" style="background:' + (_scMap[currentStatus] || '#6b7280') + '">' + (_slMap[currentStatus] || currentStatus) + '</span>';
    document.getElementById('statusNote').value = '';
    document.getElementById('confirmStatusBtn').disabled = true;
    // Reset button highlights
    document.querySelectorAll('.sg-btn').forEach(function(b) { b.classList.remove('sg-selected'); });
    openModal('statusModal');
}

function selectStatus(status) {
    _selectedStatus = status;
    document.querySelectorAll('.sg-btn').forEach(function(b) {
        b.classList.toggle('sg-selected', b.dataset.status === status);
    });
    document.getElementById('confirmStatusBtn').disabled = false;
    document.getElementById('confirmStatusBtn').innerHTML =
        '<i class="fas fa-check"></i> Set to ' + (_slMap[status] || status);
}

function confirmStatus() {
    if (!_statusBookingId || !_selectedStatus) return;
    var note = document.getElementById('statusNote').value;
    var btn  = document.getElementById('confirmStatusBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating…';

    fetch('/admin/dispatch-action.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            action:     'update_status',
            booking_id: _statusBookingId,
            new_status: _selectedStatus,
            note:       note
        })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        closeModal('statusModal');
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(function() { location.reload(); }, 1200);
        } else {
            showToast(data.error || 'Update failed.', 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check"></i> Update Status';
        }
    })
    .catch(function() {
        showToast('Network error.', 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check"></i> Update Status';
    });
}

// ── NOTES MODAL ─────────────────────────────────────────────
function openNotesModal(bookingId, ref) {
    _notesBookingId = bookingId;
    document.getElementById('notesBookingRef').textContent = ref;
    document.getElementById('newNote').value = '';
    document.getElementById('notesList').innerHTML = '<p style="color:#666;font-size:.85rem"><i class="fas fa-spinner fa-spin"></i> Loading notes…</p>';
    openModal('notesModal');
    loadNotes(bookingId);
}

function loadNotes(bookingId) {
    fetch('/admin/dispatch-action.php?action=get_notes&id=' + bookingId)
    .then(function(r) { return r.json(); })
    .then(function(data) {
        var list = document.getElementById('notesList');
        if (!data.success || !data.notes.length) {
            list.innerHTML = '<p style="color:#888;font-size:.85rem;font-style:italic">No notes yet for this booking.</p>';
            return;
        }
        list.innerHTML = data.notes.map(function(n) {
            return '<div class="note-item">'
                + '<div class="note-meta">'
                + '<strong>' + htmlEscape(n.first_name + ' ' + n.last_name) + '</strong>'
                + '<span class="note-time">' + formatNoteTime(n.created_at) + '</span>'
                + '</div>'
                + '<div class="note-body">' + htmlEscape(n.note) + '</div>'
                + '</div>';
        }).join('');
    })
    .catch(function() {
        document.getElementById('notesList').innerHTML = '<p style="color:#ef4444">Failed to load notes.</p>';
    });
}

function submitNote() {
    var note = document.getElementById('newNote').value.trim();
    if (!note) { showToast('Note cannot be empty.', 'error'); return; }

    fetch('/admin/dispatch-action.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            action:     'add_note',
            booking_id: _notesBookingId,
            note:       note
        })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            document.getElementById('newNote').value = '';
            showToast('Note saved.', 'success');
            loadNotes(_notesBookingId);
        } else {
            showToast(data.error || 'Failed to save note.', 'error');
        }
    })
    .catch(function() { showToast('Network error.', 'error'); });
}

// ── Utility ──────────────────────────────────────────────────
function htmlEscape(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function formatNoteTime(ts) {
    if (!ts) return '';
    var d = new Date(ts.replace(' ','T'));
    return d.toLocaleDateString('en-US',{month:'short',day:'numeric'}) + ' '
         + d.toLocaleTimeString('en-US',{hour:'2-digit',minute:'2-digit'});
}
</script>

<?php require_once 'includes/admin-footer.php'; ?>
