<?php
/**
 * Driver Panel - Profile
 */
$pageTitle = 'Profile';
require_once __DIR__ . '/includes/driver-header.php';

$driverId = $driverRecord['id'];
?>

<div class="driver-content">

    <!-- Profile Header -->
    <div class="profile-header">
        <div class="profile-avatar">
            <i class="fas fa-user-circle"></i>
        </div>
        <div class="profile-name"><?= sanitize($driverUser['first_name'] . ' ' . $driverUser['last_name']) ?></div>
        <div class="profile-role">Driver</div>
        <div class="profile-rating">
            <i class="fas fa-star"></i> <?= number_format($driverRecord['rating'], 1) ?>
            &middot; <?= intval($driverRecord['total_trips']) ?> trips
        </div>
    </div>

    <!-- Info Card -->
    <div class="detail-card">
        <div class="detail-card-title"><i class="fas fa-id-card"></i> Details</div>
        <div class="detail-list">
            <div class="detail-row">
                <span class="detail-label">Email</span>
                <span class="detail-value"><?= sanitize($driverUser['email']) ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Phone</span>
                <span class="detail-value"><?= sanitize($driverUser['phone'] ?? 'N/A') ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">License #</span>
                <span class="detail-value"><?= sanitize($driverRecord['license_number'] ?? 'N/A') ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">License Expiry</span>
                <span class="detail-value"><?= $driverRecord['license_expiry'] ? formatDate($driverRecord['license_expiry']) : 'N/A' ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Vehicle</span>
                <span class="detail-value"><?= sanitize($driverRecord['vehicle_name'] ?? 'Not assigned') ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Plate #</span>
                <span class="detail-value"><?= sanitize($driverRecord['plate_number'] ?? 'N/A') ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Member Since</span>
                <span class="detail-value"><?= formatDate($driverUser['created_at']) ?></span>
            </div>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="detail-card">
        <div class="detail-card-title"><i class="fas fa-link"></i> Quick Links</div>
        <a href="/driver/earnings.php" class="profile-link">
            <i class="fas fa-wallet"></i> View Earnings
            <i class="fas fa-chevron-right"></i>
        </a>
        <a href="/driver/availability.php" class="profile-link">
            <i class="fas fa-signal"></i> Change Availability
            <i class="fas fa-chevron-right"></i>
        </a>
        <a href="/driver/rides.php?filter=completed" class="profile-link">
            <i class="fas fa-history"></i> Ride History
            <i class="fas fa-chevron-right"></i>
        </a>
    </div>

    <!-- Logout -->
    <a href="/driver/logout.php" class="btn-driver-danger btn-block" style="margin-top:20px;">
        <i class="fas fa-sign-out-alt"></i> Sign Out
    </a>

</div>

<?php require_once __DIR__ . '/includes/driver-footer.php'; ?>
