<?php
/**
 * Driver Panel - Footer Include
 * Bottom tab navigation (mobile app style)
 */
$currentPage = basename($_SERVER['PHP_SELF']);
?>
</main><!-- /.driver-main -->

<?php if ($currentPage !== 'login.php'): ?>
<!-- Bottom Tab Navigation -->
<nav class="driver-bottom-nav">
    <a href="/driver/" class="tab-item <?= $currentPage === 'index.php' ? 'active' : '' ?>">
        <i class="fas fa-th-large"></i>
        <span>Dashboard</span>
    </a>
    <a href="/driver/rides.php" class="tab-item <?= in_array($currentPage, ['rides.php','ride-detail.php']) ? 'active' : '' ?>">
        <i class="fas fa-route"></i>
        <span>Rides</span>
    </a>
    <a href="/driver/availability.php" class="tab-item <?= $currentPage === 'availability.php' ? 'active' : '' ?>">
        <i class="fas fa-signal"></i>
        <span>Status</span>
    </a>
    <a href="/driver/earnings.php" class="tab-item <?= $currentPage === 'earnings.php' ? 'active' : '' ?>">
        <i class="fas fa-wallet"></i>
        <span>Earnings</span>
    </a>
    <a href="/driver/profile.php" class="tab-item <?= $currentPage === 'profile.php' ? 'active' : '' ?>">
        <i class="fas fa-user-circle"></i>
        <span>Profile</span>
    </a>
</nav>
<?php endif; ?>

<!-- Real-time CSS -->
<link rel="stylesheet" href="/assets/css/realtime.css">

<!-- Driver Real-time JS -->
<script src="/assets/js/driver-realtime.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-dismiss flash messages
    const flash = document.getElementById('driverFlash');
    if (flash) {
        setTimeout(function() {
            flash.style.opacity = '0';
            flash.style.transform = 'translateY(-20px)';
            setTimeout(function() { flash.remove(); }, 400);
        }, 4000);
    }

    // Pull-to-refresh simulation
    let touchStart = 0;
    document.addEventListener('touchstart', function(e) {
        touchStart = e.touches[0].clientY;
    });
    document.addEventListener('touchend', function(e) {
        const touchEnd = e.changedTouches[0].clientY;
        if (touchEnd - touchStart > 150 && window.scrollY === 0) {
            location.reload();
        }
    });

    // Confirm actions
    document.querySelectorAll('[data-confirm]').forEach(function(el) {
        el.addEventListener('click', function(e) {
            if (!confirm(this.dataset.confirm)) {
                e.preventDefault();
            }
        });
    });

    // Initialize real-time module (replaces crude 30s page reload)
    var currentPage = '<?= basename($_SERVER["PHP_SELF"]) ?>';
    if (typeof DriverRealtime !== 'undefined') {
        // Dashboard: start polling + location toggle listener
        if (currentPage === 'index.php') {
            DriverRealtime.startDashboardPolling();

            // Location toggle handler
            var locToggle = document.getElementById('locationToggle');
            if (locToggle) {
                locToggle.addEventListener('change', function() {
                    if (this.checked) {
                        DriverRealtime.startLocationSharing();
                    } else {
                        DriverRealtime.stopLocationSharing();
                    }
                });
                // Auto-start if already enabled
                if (locToggle.checked) {
                    DriverRealtime.startLocationSharing();
                }
            }
        }

        // Ride detail: start ride polling + location sharing if active
        if (currentPage === 'ride-detail.php') {
            var bookingId = document.body.dataset.bookingId || '';
            var rideStatus = document.querySelector('.ride-status-banner');
            var currentStatus = rideStatus ? rideStatus.className.replace('ride-status-banner status-', '') : '';
            if (bookingId) {
                DriverRealtime.startRideDetailPolling(bookingId, currentStatus);
            }
        }
    }
});
</script>

</body>
</html>
