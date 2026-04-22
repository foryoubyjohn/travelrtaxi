<?php
$pageTitle = 'Our Fleet';
$pageDesc = 'View our fleet of sedans, vans, and mini buses. Modern, well-maintained vehicles for your comfort.';
require_once 'includes/header.php';

$filter = isset($_GET['type']) ? sanitize($_GET['type']) : 'all';
$sql = "SELECT * FROM vehicles WHERE status = 'active'";
$params = [];
if ($filter !== 'all' && in_array($filter, ['sedan', 'van', 'minibus'])) {
    $sql .= " AND type = ?";
    $params[] = $filter;
}
$sql .= " ORDER BY type, name";
$vehicles = dbFetchAll($sql, $params);
?>

<section class="page-hero">
    <div class="container">
        <h1>Our Fleet</h1>
        <p>Modern, well-maintained vehicles for every journey</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <!-- Fleet Stats -->
        <div class="fleet-stats-bar">
            <div class="fleet-stat">
                <i class="fas fa-car"></i>
                <strong>60+</strong> Sedans
            </div>
            <div class="fleet-stat">
                <i class="fas fa-shuttle-van"></i>
                <strong>30+</strong> 7-Seater Vans
            </div>
            <div class="fleet-stat">
                <i class="fas fa-bus"></i>
                <strong>10+</strong> Mini Buses
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="fleet-filters">
            <a href="/fleet.php" class="filter-btn <?php echo $filter === 'all' ? 'active' : ''; ?>">All Vehicles</a>
            <a href="/fleet.php?type=sedan" class="filter-btn <?php echo $filter === 'sedan' ? 'active' : ''; ?>">Sedans</a>
            <a href="/fleet.php?type=van" class="filter-btn <?php echo $filter === 'van' ? 'active' : ''; ?>">7-Seater Vans</a>
            <a href="/fleet.php?type=minibus" class="filter-btn <?php echo $filter === 'minibus' ? 'active' : ''; ?>">Mini Buses</a>
        </div>

        <!-- Vehicle Grid -->
        <div class="fleet-grid">
            <?php if (empty($vehicles)): ?>
                <p class="text-center">No vehicles found.</p>
            <?php else: ?>
                <?php foreach ($vehicles as $v): ?>
                <div class="vehicle-card">
                    <div class="vehicle-image">
                        <?php if ($v['image']): ?>
                            <img src="/uploads/<?php echo sanitize($v['image']); ?>" alt="<?php echo sanitize($v['name']); ?>">
                        <?php else: ?>
                            <div class="vehicle-placeholder">
                                <?php
                                $icon = 'fa-car';
                                if ($v['type'] === 'van') $icon = 'fa-shuttle-van';
                                if ($v['type'] === 'minibus') $icon = 'fa-bus';
                                ?>
                                <i class="fas <?php echo $icon; ?>"></i>
                            </div>
                        <?php endif; ?>
                        <span class="vehicle-type-badge"><?php echo ucfirst($v['type']); ?></span>
                    </div>
                    <div class="vehicle-info">
                        <h3><?php echo sanitize($v['name']); ?></h3>
                        <div class="vehicle-details">
                            <span><i class="fas fa-users"></i> <?php echo $v['capacity']; ?> Passengers</span>
                            <span><i class="fas fa-palette"></i> <?php echo sanitize($v['color']); ?></span>
                        </div>
                        <a href="/booking.php?vehicle_type=<?php echo $v['type']; ?>" class="btn btn-primary btn-sm">Book This Type</a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="cta-banner">
    <div class="container">
        <div class="cta-content">
            <h2>Need a Specific Vehicle?</h2>
            <p>Contact us for special requests or large group transportation.</p>
            <div class="cta-buttons">
                <a href="/booking.php" class="btn btn-primary btn-lg"><i class="fas fa-car"></i> Book Now</a>
                <a href="<?php echo getWhatsAppLink(); ?>" class="btn btn-whatsapp btn-lg" target="_blank"><i class="fab fa-whatsapp"></i> WhatsApp</a>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
