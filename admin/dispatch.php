<?php
/**
 * Dispatch Command Center
 * Central dispatch screen for managing all bookings, driver assignments, and trip status
 */
$pageTitle = 'Dispatch Center';
require_once 'includes/admin-header.php';

// ── Filters ──
$filter     = sanitize($_GET['filter'] ?? 'today');
$driverFilter = intval($_GET['driver'] ?? 0);
$vehicleFilter = sanitize($_GET['vehicle'] ?? '');
$searchQuery  = sanitize($_GET['q'] ?? '');

// ── Build query ──
$where  = [];
$params = [];

switch ($filter) {
    case 'today':
        $where[] = "b.booking_date = CURDATE()";
        break;
    case 'active':
        $where[] = "b.status IN ('assigned','accepted','on_the_way','arrived','trip_started','in_progress')";
        break;
    case 'completed':
        $where[] = "b.status = 'completed'";
        break;
    case 'unassigned':
        $where[] = "(b.driver_id IS NULL OR b.status IN ('pending','confirmed'))";
        break;
    case 'cancelled':
        $where[] = "b.status IN ('cancelled','no_show','declined')";
        break;
    case 'all':
    default:
        break;
}

if ($driverFilter > 0) {
    $where[] = "b.driver_id = ?";
    $params[] = $driverFilter;
}

if ($vehicleFilter && in_array($vehicleFilter, ['sedan','van','minibus'])) {
    $where[] = "b.vehicle_type = ?";
    $params[] = $vehicleFilter;
}

if ($searchQuery) {
    $where[] = "(b.booking_ref LIKE ? OR b.customer_name LIKE ? OR b.customer_phone LIKE ? OR b.pickup_location LIKE ? OR b.dropoff_location LIKE ?)";
    $like = "%{$searchQuery}%";
    $params = array_merge($params, [$like, $like, $like, $like, $like]);
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$bookings = dbFetchAll(
    "SELECT b.*,
            d_rec.id as driver_record_id,
            d_user.first_name as driver_first, d_user.last_name as driver_last, d_user.phone as driver_phone,
            d_rec.availability as driver_availability,
            d_rec.location_sharing, d_rec.last_latitude, d_rec.last_longitude, d_rec.last_location_at,
            v.name as vehicle_name, v.plate_number
     FROM bookings b
     LEFT JOIN drivers d_rec ON b.driver_id = d_rec.id
     LEFT JOIN users d_user ON d_rec.user_id = d_user.id
     LEFT JOIN vehicles v ON b.vehicle_id = v.id
     {$whereClause}
     ORDER BY
        FIELD(b.status, 'pending','confirmed','assigned','accepted','on_the_way','arrived','trip_started','in_progress','completed','no_show','declined','cancelled'),
        b.booking_date ASC, b.booking_time ASC",
    $params
);

// ── Stats ──
$statsActive = dbFetchOne("SELECT COUNT(*) as c FROM bookings WHERE status IN ('assigned','accepted','on_the_way','arrived','trip_started','in_progress')")['c'];
$statsUnassigned = dbFetchOne("SELECT COUNT(*) as c FROM bookings WHERE status IN ('pending','confirmed') AND driver_id IS NULL")['c'];
$statsAvailableDrivers = dbFetchOne("SELECT COUNT(*) as c FROM drivers WHERE status = 'available' AND availability = 'available'")['c'];
$statsRevenueToday = dbFetchOne("SELECT COALESCE(SUM(final_price),0) as r FROM bookings WHERE status = 'completed' AND DATE(trip_completed_at) = CURDATE()")['r'];
$statsTodayBookings = dbFetchOne("SELECT COUNT(*) as c FROM bookings WHERE booking_date = CURDATE()")['c'];
$statsCompletedToday = dbFetchOne("SELECT COUNT(*) as c FROM bookings WHERE status = 'completed' AND DATE(trip_completed_at) = CURDATE()")['c'];

// ── Available Drivers (for assignment panel) ──
$availableDrivers = dbFetchAll(
    "SELECT d.id, d.status, d.availability, d.total_trips, d.rating,
            d.location_sharing, d.last_latitude, d.last_longitude, d.last_location_at,
            u.first_name, u.last_name, u.phone,
            v.name as vehicle_name, v.type as vehicle_type, v.plate_number, v.capacity
     FROM drivers d
     JOIN users u ON d.user_id = u.id
     LEFT JOIN vehicles v ON d.vehicle_id = v.id
     WHERE u.is_active = 1
     ORDER BY
        FIELD(d.availability, 'available','unavailable','off_duty'),
        d.status ASC, u.first_name ASC"
);

// ── All Drivers (for filter dropdown) ──
$allDrivers = dbFetchAll(
    "SELECT d.id, u.first_name, u.last_name FROM drivers d JOIN users u ON d.user_id = u.id ORDER BY u.first_name"
);

// ── Group bookings by status ──
$statusGroups = [
    'unassigned' => ['label' => 'Unassigned', 'icon' => 'fa-exclamation-circle', 'color' => '#ef4444', 'statuses' => ['pending','confirmed']],
    'assigned'   => ['label' => 'Assigned',   'icon' => 'fa-user-check',         'color' => '#8b5cf6', 'statuses' => ['assigned','accepted']],
    'on_the_way' => ['label' => 'On the Way', 'icon' => 'fa-car-side',           'color' => '#3b82f6', 'statuses' => ['on_the_way']],
    'arrived'    => ['label' => 'Arrived',     'icon' => 'fa-map-marker-alt',     'color' => '#06b6d4', 'statuses' => ['arrived']],
    'in_progress'=> ['label' => 'In Progress', 'icon' => 'fa-road',              'color' => '#10b981', 'statuses' => ['trip_started','in_progress']],
    'completed'  => ['label' => 'Completed',   'icon' => 'fa-check-circle',       'color' => '#059669', 'statuses' => ['completed']],
    'cancelled'  => ['label' => 'Cancelled',   'icon' => 'fa-times-circle',       'color' => '#6b7280', 'statuses' => ['cancelled','no_show','declined']],
];

$grouped = [];
foreach ($statusGroups as $key => $group) {
    $grouped[$key] = array_filter($bookings, function($b) use ($group) {
        return in_array($b['status'], $group['statuses']);
    });
}
?>

<!-- Dispatch-specific CSS -->
<link rel="stylesheet" href="/assets/css/dispatch.css">
<link rel="stylesheet" href="/assets/css/realtime.css">

<div class="dispatch-header">
    <div class="dispatch-title-row">
        <h1 class="page-title"><i class="fas fa-satellite-dish"></i> Dispatch Command Center</h1>
        <div class="dispatch-actions-top">
            <button onclick="location.reload()" class="btn btn-sm btn-outline" title="Refresh"><i class="fas fa-sync-alt"></i> Refresh</button>
            <span class="sync-indicator" id="syncIndicator"><span class="sync-dot sync-dot-live"></span> Live</span>
            <span class="dispatch-time" id="dispatchClock"></span>
        </div>
    </div>
</div>

<!-- ── Stats Strip ── -->
<div class="dispatch-stats">
    <div class="dstat dstat-active">
        <div class="dstat-icon"><i class="fas fa-bolt"></i></div>
        <div class="dstat-info">
            <div class="dstat-number"><?= $statsActive ?></div>
            <div class="dstat-label">Active Trips</div>
        </div>
    </div>
    <div class="dstat dstat-unassigned">
        <div class="dstat-icon"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="dstat-info">
            <div class="dstat-number"><?= $statsUnassigned ?></div>
            <div class="dstat-label">Unassigned</div>
        </div>
    </div>
    <div class="dstat dstat-drivers">
        <div class="dstat-icon"><i class="fas fa-user-check"></i></div>
        <div class="dstat-info">
            <div class="dstat-number"><?= $statsAvailableDrivers ?></div>
            <div class="dstat-label">Available Drivers</div>
        </div>
    </div>
    <div class="dstat dstat-revenue">
        <div class="dstat-icon"><i class="fas fa-dollar-sign"></i></div>
        <div class="dstat-info">
            <div class="dstat-number"><?= formatPrice($statsRevenueToday) ?></div>
            <div class="dstat-label">Revenue Today</div>
        </div>
    </div>
    <div class="dstat dstat-today">
        <div class="dstat-icon"><i class="fas fa-calendar-day"></i></div>
        <div class="dstat-info">
            <div class="dstat-number"><?= $statsTodayBookings ?></div>
            <div class="dstat-label">Today's Bookings</div>
        </div>
    </div>
    <div class="dstat dstat-completed">
        <div class="dstat-icon"><i class="fas fa-flag-checkered"></i></div>
        <div class="dstat-info">
            <div class="dstat-number"><?= $statsCompletedToday ?></div>
            <div class="dstat-label">Completed Today</div>
        </div>
    </div>
</div>

<!-- ── Filters Bar ── -->
<div class="dispatch-filters">
    <div class="filter-group">
        <a href="?filter=today" class="dbtn <?= $filter === 'today' ? 'dbtn-active' : '' ?>"><i class="fas fa-calendar-day"></i> Today</a>
        <a href="?filter=active" class="dbtn <?= $filter === 'active' ? 'dbtn-active' : '' ?>"><i class="fas fa-bolt"></i> Active</a>
        <a href="?filter=unassigned" class="dbtn <?= $filter === 'unassigned' ? 'dbtn-active' : '' ?>"><i class="fas fa-exclamation-circle"></i> Unassigned</a>
        <a href="?filter=completed" class="dbtn <?= $filter === 'completed' ? 'dbtn-active' : '' ?>"><i class="fas fa-check-circle"></i> Completed</a>
        <a href="?filter=cancelled" class="dbtn <?= $filter === 'cancelled' ? 'dbtn-active' : '' ?>"><i class="fas fa-times-circle"></i> Cancelled</a>
        <a href="?filter=all" class="dbtn <?= $filter === 'all' ? 'dbtn-active' : '' ?>"><i class="fas fa-list"></i> All</a>
    </div>
    <div class="filter-group">
        <form method="GET" class="dispatch-filter-form">
            <input type="hidden" name="filter" value="<?= $filter ?>">
            <select name="driver" onchange="this.form.submit()" class="dselect">
                <option value="0">All Drivers</option>
                <?php foreach ($allDrivers as $d): ?>
                <option value="<?= $d['id'] ?>" <?= $driverFilter == $d['id'] ? 'selected' : '' ?>><?= sanitize($d['first_name'] . ' ' . $d['last_name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="vehicle" onchange="this.form.submit()" class="dselect">
                <option value="">All Vehicles</option>
                <option value="sedan" <?= $vehicleFilter === 'sedan' ? 'selected' : '' ?>>Sedan</option>
                <option value="van" <?= $vehicleFilter === 'van' ? 'selected' : '' ?>>Van</option>
                <option value="minibus" <?= $vehicleFilter === 'minibus' ? 'selected' : '' ?>>Minibus</option>
            </select>
        </form>
        <form method="GET" class="dispatch-search-form">
            <input type="hidden" name="filter" value="<?= $filter ?>">
            <input type="text" name="q" value="<?= $searchQuery ?>" placeholder="Search ref, name, phone, location..." class="dsearch">
            <button type="submit" class="dbtn dbtn-search"><i class="fas fa-search"></i></button>
        </form>
    </div>
</div>

<!-- ── Main Dispatch Grid ── -->
<div class="dispatch-layout">

    <!-- Left: Booking Columns -->
    <div class="dispatch-board">
        <?php foreach ($statusGroups as $key => $group):
            $items = $grouped[$key];
            $count = count($items);
            if ($count === 0 && in_array($key, ['completed','cancelled']) && $filter !== 'all' && $filter !== 'completed' && $filter !== 'cancelled') continue;
        ?>
        <div class="dispatch-column" data-status="<?= $key ?>">
            <div class="dcol-header" style="border-color: <?= $group['color'] ?>">
                <span class="dcol-title"><i class="fas <?= $group['icon'] ?>" style="color:<?= $group['color'] ?>"></i> <?= $group['label'] ?></span>
                <span class="dcol-count" style="background:<?= $group['color'] ?>"><?= $count ?></span>
            </div>
            <div class="dcol-body">
                <?php if ($count === 0): ?>
                <div class="dcol-empty">No bookings</div>
                <?php else: ?>
                <?php foreach ($items as $b): ?>
                <div class="dispatch-card dcard-<?= $b['status'] ?>" data-booking-id="<?= $b['id'] ?>">
                    <!-- Card Header -->
                    <div class="dcard-top">
                        <span class="dcard-ref"><?= sanitize($b['booking_ref']) ?></span>
                        <?= statusBadge($b['status']) ?>
                    </div>

                    <!-- Customer -->
                    <div class="dcard-customer">
                        <strong><?= sanitize($b['customer_name']) ?></strong>
                        <div class="dcard-contact">
                            <a href="tel:<?= sanitize($b['customer_phone']) ?>" class="dcard-btn-mini" title="Call Customer"><i class="fas fa-phone"></i></a>
                            <a href="https://wa.me/1<?= preg_replace('/[^0-9]/', '', $b['customer_phone']) ?>" target="_blank" class="dcard-btn-mini dcard-btn-wa" title="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                        </div>
                    </div>

                    <!-- Route -->
                    <div class="dcard-route">
                        <div class="dcard-route-point"><i class="fas fa-circle dcard-pickup-dot"></i> <?= sanitize(mb_strimwidth($b['pickup_location'], 0, 40, '...')) ?></div>
                        <div class="dcard-route-point"><i class="fas fa-map-marker-alt dcard-dropoff-dot"></i> <?= sanitize(mb_strimwidth($b['dropoff_location'], 0, 40, '...')) ?></div>
                    </div>

                    <!-- Meta -->
                    <div class="dcard-meta">
                        <span><i class="far fa-calendar"></i> <?= formatDate($b['booking_date']) ?></span>
                        <span><i class="far fa-clock"></i> <?= formatTime($b['booking_time']) ?></span>
                        <span><i class="fas fa-users"></i> <?= $b['passengers'] ?></span>
                        <span class="dcard-fare"><?= $b['estimated_price'] > 0 ? formatPrice($b['estimated_price']) : '-' ?></span>
                    </div>
                    <div class="dcard-meta">
                        <span><i class="fas fa-concierge-bell"></i> <?= ucfirst($b['service_type']) ?></span>
                        <span><i class="fas fa-car"></i> <?= ucfirst($b['vehicle_type']) ?></span>
                    </div>

                    <!-- Driver Info -->
                    <?php if ($b['driver_first']): ?>
                    <div class="dcard-driver">
                        <i class="fas fa-id-badge"></i>
                        <span><?= sanitize($b['driver_first'] . ' ' . $b['driver_last']) ?></span>
                        <?php if ($b['vehicle_name']): ?>
                        <span class="dcard-vehicle"><?= sanitize($b['vehicle_name']) ?> (<?= sanitize($b['plate_number'] ?? '') ?>)</span>
                        <?php endif; ?>
                        <div class="dcard-driver-actions">
                            <a href="tel:<?= sanitize($b['driver_phone']) ?>" class="dcard-btn-mini" title="Call Driver"><i class="fas fa-phone"></i></a>
                        </div>
                    </div>
                    <?php
                    // GPS location indicator
                    if ($b['location_sharing'] && $b['last_latitude']):
                        $locAge = time() - strtotime($b['last_location_at'] ?? '2000-01-01');
                        $isLive = ($locAge < 120);
                        $isStale = ($locAge >= 120 && $locAge < 600);
                        if ($isLive || $isStale):
                    ?>
                    <div class="dcard-location <?= $isLive ? 'dcard-loc-live' : 'dcard-loc-stale' ?>">
                        <span class="dcard-loc-dot"></span>
                        <?= $isLive ? 'Live' : 'Last: ' . date('g:i A', strtotime($b['last_location_at'])) ?>
                        <span class="dcard-loc-coords" title="<?= $b['last_latitude'] ?>, <?= $b['last_longitude'] ?>"><i class="fas fa-map-pin"></i></span>
                    </div>
                    <?php endif; endif; ?>
                    <?php endif; ?>

                    <!-- Dispatcher Notes Preview -->
                    <?php if ($b['dispatcher_notes']): ?>
                    <div class="dcard-note-preview"><i class="fas fa-sticky-note"></i> <?= sanitize(mb_strimwidth($b['dispatcher_notes'], 0, 60, '...')) ?></div>
                    <?php endif; ?>

                    <!-- Quick Actions -->
                    <div class="dcard-actions">
                        <?php if (!$b['driver_id'] || in_array($b['status'], ['pending','confirmed'])): ?>
                        <button class="dbtn dbtn-assign" onclick="openAssignModal(<?= $b['id'] ?>, '<?= sanitize($b['booking_ref']) ?>', '<?= sanitize($b['customer_name']) ?>', '<?= sanitize($b['vehicle_type']) ?>')"><i class="fas fa-user-plus"></i> Assign</button>
                        <?php elseif (in_array($b['status'], ['assigned','accepted'])): ?>
                        <button class="dbtn dbtn-reassign" onclick="openAssignModal(<?= $b['id'] ?>, '<?= sanitize($b['booking_ref']) ?>', '<?= sanitize($b['customer_name']) ?>', '<?= sanitize($b['vehicle_type']) ?>', <?= $b['driver_id'] ?>)"><i class="fas fa-exchange-alt"></i> Reassign</button>
                        <?php endif; ?>
                        <button class="dbtn dbtn-status" onclick="openStatusModal(<?= $b['id'] ?>, '<?= sanitize($b['booking_ref']) ?>', '<?= $b['status'] ?>')"><i class="fas fa-sync-alt"></i> Status</button>
                        <button class="dbtn dbtn-note" onclick="openNoteModal(<?= $b['id'] ?>, '<?= sanitize($b['booking_ref']) ?>')"><i class="fas fa-sticky-note"></i> Note</button>
                        <a href="/admin/booking-detail.php?id=<?= $b['id'] ?>" class="dbtn dbtn-detail"><i class="fas fa-expand"></i></a>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Right: Available Drivers Panel -->
    <div class="dispatch-drivers-panel">
        <div class="dpanel-header">
            <h3><i class="fas fa-users"></i> Drivers</h3>
            <span class="dpanel-count"><?= count($availableDrivers) ?> total</span>
        </div>
        <div class="dpanel-body">
            <?php foreach ($availableDrivers as $drv): ?>
            <div class="driver-row driver-<?= $drv['availability'] ?>">
                <div class="driver-row-left">
                    <div class="driver-avail-dot avail-<?= $drv['availability'] ?>"></div>
                    <div class="driver-row-info">
                        <strong><?= sanitize($drv['first_name'] . ' ' . $drv['last_name']) ?></strong>
                        <span class="driver-row-meta">
                            <?php if ($drv['vehicle_name']): ?>
                            <?= sanitize($drv['vehicle_name']) ?> &middot; <?= ucfirst($drv['vehicle_type']) ?> &middot; <?= $drv['capacity'] ?> seats
                            <?php else: ?>
                            No vehicle
                            <?php endif; ?>
                        </span>
                        <span class="driver-row-stats">
                            <i class="fas fa-star" style="color:#f59e0b"></i> <?= number_format($drv['rating'], 1) ?>
                            &middot; <?= $drv['total_trips'] ?> trips
                        </span>
                        <?php
                        if ($drv['location_sharing'] && $drv['last_latitude']):
                            $drvLocAge = time() - strtotime($drv['last_location_at'] ?? '2000-01-01');
                            $drvLive = ($drvLocAge < 120);
                            $drvStale = ($drvLocAge >= 120 && $drvLocAge < 600);
                            if ($drvLive || $drvStale):
                        ?>
                        <span class="driver-loc-badge <?= $drvLive ? 'driver-loc-live' : 'driver-loc-stale' ?>">
                            <span class="dloc-dot"></span>
                            <?= $drvLive ? 'Live' : 'Last: ' . date('g:i A', strtotime($drv['last_location_at'])) ?>
                        </span>
                        <?php endif; endif; ?>
                    </div>
                </div>
                <div class="driver-row-right">
                    <span class="driver-status-tag tag-<?= $drv['availability'] ?>"><?= ucfirst(str_replace('_', ' ', $drv['availability'])) ?></span>
                    <a href="tel:<?= sanitize($drv['phone']) ?>" class="dcard-btn-mini" title="Call"><i class="fas fa-phone"></i></a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     MODALS
     ═══════════════════════════════════════════════════════════ -->

<!-- Assign / Reassign Driver Modal -->
<div class="dispatch-modal" id="assignModal">
    <div class="dmodal-overlay" onclick="closeModal('assignModal')"></div>
    <div class="dmodal-content">
        <div class="dmodal-header">
            <h3><i class="fas fa-user-plus"></i> <span id="assignModalTitle">Assign Driver</span></h3>
            <button onclick="closeModal('assignModal')" class="dmodal-close">&times;</button>
        </div>
        <div class="dmodal-body">
            <div class="dmodal-booking-info" id="assignBookingInfo"></div>
            <form id="assignForm" onsubmit="return submitAssign(event)">
                <input type="hidden" name="booking_id" id="assignBookingId">
                <input type="hidden" name="old_driver_id" id="assignOldDriverId" value="">
                <div class="form-group">
                    <label><i class="fas fa-id-badge"></i> Select Driver</label>
                    <select name="driver_id" id="assignDriverSelect" class="dmodal-select" required>
                        <option value="">-- Choose Driver --</option>
                        <?php foreach ($availableDrivers as $drv): ?>
                        <option value="<?= $drv['id'] ?>"
                                data-vehicle="<?= $drv['vehicle_type'] ?>"
                                data-avail="<?= $drv['availability'] ?>"
                                data-status="<?= $drv['status'] ?>"
                                <?= $drv['availability'] !== 'available' ? 'class="driver-unavailable-opt"' : '' ?>>
                            <?= sanitize($drv['first_name'] . ' ' . $drv['last_name']) ?>
                            — <?= ucfirst($drv['vehicle_type'] ?? 'no vehicle') ?>
                            (<?= ucfirst($drv['availability']) ?>)
                            <?= $drv['status'] === 'on_trip' ? ' [ON TRIP]' : '' ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-comment"></i> Reason / Notes (optional)</label>
                    <input type="text" name="reason" placeholder="e.g. Closest driver, customer request..." class="dmodal-input">
                </div>
                <div class="dmodal-actions">
                    <button type="button" onclick="closeModal('assignModal')" class="dbtn dbtn-cancel">Cancel</button>
                    <button type="submit" class="dbtn dbtn-confirm" id="assignSubmitBtn"><i class="fas fa-check"></i> Assign Driver</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div class="dispatch-modal" id="statusModal">
    <div class="dmodal-overlay" onclick="closeModal('statusModal')"></div>
    <div class="dmodal-content">
        <div class="dmodal-header">
            <h3><i class="fas fa-sync-alt"></i> Update Status</h3>
            <button onclick="closeModal('statusModal')" class="dmodal-close">&times;</button>
        </div>
        <div class="dmodal-body">
            <div class="dmodal-booking-info" id="statusBookingInfo"></div>
            <form id="statusForm" onsubmit="return submitStatus(event)">
                <input type="hidden" name="booking_id" id="statusBookingId">
                <input type="hidden" name="old_status" id="statusOldStatus">
                <div class="form-group">
                    <label><i class="fas fa-flag"></i> New Status</label>
                    <select name="new_status" id="statusSelect" class="dmodal-select" required>
                        <option value="">-- Select Status --</option>
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="assigned">Assigned</option>
                        <option value="accepted">Accepted</option>
                        <option value="on_the_way">On the Way</option>
                        <option value="arrived">Arrived</option>
                        <option value="trip_started">Trip Started</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="no_show">No Show</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-comment"></i> Notes (optional)</label>
                    <input type="text" name="notes" placeholder="Reason for status change..." class="dmodal-input">
                </div>
                <div class="dmodal-actions">
                    <button type="button" onclick="closeModal('statusModal')" class="dbtn dbtn-cancel">Cancel</button>
                    <button type="submit" class="dbtn dbtn-confirm"><i class="fas fa-check"></i> Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Dispatcher Note Modal -->
<div class="dispatch-modal" id="noteModal">
    <div class="dmodal-overlay" onclick="closeModal('noteModal')"></div>
    <div class="dmodal-content">
        <div class="dmodal-header">
            <h3><i class="fas fa-sticky-note"></i> Dispatcher Note</h3>
            <button onclick="closeModal('noteModal')" class="dmodal-close">&times;</button>
        </div>
        <div class="dmodal-body">
            <div class="dmodal-booking-info" id="noteBookingInfo"></div>
            <div id="noteExistingNotes" class="dmodal-existing-notes"></div>
            <form id="noteForm" onsubmit="return submitNote(event)">
                <input type="hidden" name="booking_id" id="noteBookingId">
                <div class="form-group">
                    <label><i class="fas fa-pen"></i> Add Note</label>
                    <textarea name="note" id="noteText" rows="3" placeholder="Type dispatcher note..." class="dmodal-textarea" required></textarea>
                </div>
                <div class="form-group">
                    <label class="dmodal-checkbox">
                        <input type="checkbox" name="is_priority" value="1"> <i class="fas fa-exclamation-triangle" style="color:#f59e0b"></i> Mark as Priority
                    </label>
                </div>
                <div class="dmodal-actions">
                    <button type="button" onclick="closeModal('noteModal')" class="dbtn dbtn-cancel">Cancel</button>
                    <button type="submit" class="dbtn dbtn-confirm"><i class="fas fa-plus"></i> Add Note</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     JAVASCRIPT
     ═══════════════════════════════════════════════════════════ -->
<script>
// ── Clock ──
function updateClock() {
    const el = document.getElementById('dispatchClock');
    if (el) {
        const now = new Date();
        el.textContent = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    }
}
setInterval(updateClock, 1000);
updateClock();

// ── Real-time polling (replaces crude 60s page reload) ──
// Initialized at bottom of script

// ── Modal Helpers ──
function openModal(id) {
    document.getElementById(id).classList.add('dmodal-open');
    document.body.style.overflow = 'hidden';
}
function closeModal(id) {
    document.getElementById(id).classList.remove('dmodal-open');
    document.body.style.overflow = '';
}

// ── Assign Modal ──
function openAssignModal(bookingId, ref, customer, vehicleType, currentDriverId) {
    document.getElementById('assignBookingId').value = bookingId;
    document.getElementById('assignOldDriverId').value = currentDriverId || '';
    document.getElementById('assignBookingInfo').innerHTML =
        '<strong>' + ref + '</strong> — ' + customer + ' <span class="dcard-meta-tag">' + vehicleType + '</span>';
    document.getElementById('assignModalTitle').textContent = currentDriverId ? 'Reassign Driver' : 'Assign Driver';
    document.getElementById('assignSubmitBtn').innerHTML = currentDriverId
        ? '<i class="fas fa-exchange-alt"></i> Reassign Driver'
        : '<i class="fas fa-check"></i> Assign Driver';
    document.getElementById('assignDriverSelect').value = '';
    openModal('assignModal');
}

// ── Status Modal ──
function openStatusModal(bookingId, ref, currentStatus) {
    document.getElementById('statusBookingId').value = bookingId;
    document.getElementById('statusOldStatus').value = currentStatus;
    document.getElementById('statusBookingInfo').innerHTML =
        '<strong>' + ref + '</strong> — Current: <span class="status-badge" style="background:var(--status-color,#6b7280)">' + currentStatus.replace('_', ' ') + '</span>';
    document.getElementById('statusSelect').value = '';
    openModal('statusModal');
}

// ── Note Modal ──
function openNoteModal(bookingId, ref) {
    document.getElementById('noteBookingId').value = bookingId;
    document.getElementById('noteBookingInfo').innerHTML = '<strong>' + ref + '</strong>';
    document.getElementById('noteText').value = '';
    document.getElementById('noteExistingNotes').innerHTML = '<div class="dmodal-loading">Loading notes...</div>';
    openModal('noteModal');

    // Fetch existing notes
    fetch('/admin/api/dispatch.php?action=get_notes&booking_id=' + bookingId)
        .then(r => r.json())
        .then(data => {
            const container = document.getElementById('noteExistingNotes');
            if (data.notes && data.notes.length > 0) {
                let html = '<div class="existing-notes-list">';
                data.notes.forEach(n => {
                    html += '<div class="enote' + (n.is_priority ? ' enote-priority' : '') + '">'
                        + '<div class="enote-text">' + (n.is_priority ? '<i class="fas fa-exclamation-triangle"></i> ' : '') + escapeHtml(n.note) + '</div>'
                        + '<div class="enote-meta">' + escapeHtml(n.admin_name) + ' — ' + n.created_at + '</div>'
                        + '</div>';
                });
                html += '</div>';
                container.innerHTML = html;
            } else {
                container.innerHTML = '<div class="enote-empty">No notes yet</div>';
            }
        })
        .catch(() => {
            document.getElementById('noteExistingNotes').innerHTML = '<div class="enote-empty">Could not load notes</div>';
        });
}

// ── Form Submissions (AJAX) ──
function submitAssign(e) {
    e.preventDefault();
    const form = document.getElementById('assignForm');
    const data = new FormData(form);
    data.append('action', 'assign_driver');
    data.append('assigned_by', '<?= $_SESSION['user_id'] ?>');

    fetch('/admin/api/dispatch.php', { method: 'POST', body: data })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                closeModal('assignModal');
                showToast(res.message, 'success');
                setTimeout(() => location.reload(), 800);
            } else {
                showToast(res.message || 'Error assigning driver', 'error');
            }
        })
        .catch(() => showToast('Network error', 'error'));
    return false;
}

function submitStatus(e) {
    e.preventDefault();
    const form = document.getElementById('statusForm');
    const data = new FormData(form);
    data.append('action', 'update_status');
    data.append('changed_by', '<?= $_SESSION['user_id'] ?>');

    fetch('/admin/api/dispatch.php', { method: 'POST', body: data })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                closeModal('statusModal');
                showToast(res.message, 'success');
                setTimeout(() => location.reload(), 800);
            } else {
                showToast(res.message || 'Error updating status', 'error');
            }
        })
        .catch(() => showToast('Network error', 'error'));
    return false;
}

function submitNote(e) {
    e.preventDefault();
    const form = document.getElementById('noteForm');
    const data = new FormData(form);
    data.append('action', 'add_note');
    data.append('admin_id', '<?= $_SESSION['user_id'] ?>');

    fetch('/admin/api/dispatch.php', { method: 'POST', body: data })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                closeModal('noteModal');
                showToast(res.message, 'success');
                setTimeout(() => location.reload(), 800);
            } else {
                showToast(res.message || 'Error adding note', 'error');
            }
        })
        .catch(() => showToast('Network error', 'error'));
    return false;
}

// ── Toast Notification ──
function showToast(message, type) {
    const toast = document.createElement('div');
    toast.className = 'dispatch-toast dtoast-' + type;
    toast.innerHTML = '<i class="fas fa-' + (type === 'success' ? 'check-circle' : 'exclamation-circle') + '"></i> ' + message;
    document.body.appendChild(toast);
    setTimeout(() => toast.classList.add('dtoast-show'), 10);
    setTimeout(() => { toast.classList.remove('dtoast-show'); setTimeout(() => toast.remove(), 300); }, 3000);
}

// ── Escape HTML ──
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<!-- Real-time Polling Module -->
<script src="/assets/js/dispatch-realtime.js"></script>
<script>
// Initialize dispatch real-time polling
document.addEventListener('DOMContentLoaded', function() {
    DispatchRealtime.init('<?= $filter ?>');

    // Pause polling when modals are open
    var origOpenModal = window.openModal;
    window.openModal = function(id) {
        DispatchRealtime.pause();
        origOpenModal(id);
    };
    var origCloseModal = window.closeModal;
    window.closeModal = function(id) {
        origCloseModal(id);
        DispatchRealtime.resume();
    };
});
</script>

<?php require_once 'includes/admin-footer.php'; ?>
