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

<!-- Driver JS -->
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

    // Auto-refresh dashboard every 30 seconds
    if (document.body.dataset.autoRefresh === 'true') {
        setInterval(function() {
            location.reload();
        }, 30000);
    }
});
</script>

</body>
</html>
