<?php
$pageTitle = 'Pricing';
$pageDesc = 'Transparent and affordable pricing for all our taxi and tour services. View our rates for standard rides, airport transfers, and tours.';
require_once 'includes/header.php';

$flatRates = dbFetchAll("SELECT pr.*, r.origin, r.destination FROM pricing_rules pr LEFT JOIN routes r ON pr.route_id = r.id WHERE pr.type = 'flat' AND pr.is_active = 1 ORDER BY pr.flat_price");
$distanceRates = dbFetchAll("SELECT * FROM pricing_rules WHERE type IN ('distance','rideshare') AND is_active = 1");
$hourlyRates = dbFetchAll("SELECT * FROM pricing_rules WHERE type = 'hourly' AND is_active = 1");
?>

<section class="page-hero">
    <div class="container">
        <h1>Our Pricing</h1>
        <p>Transparent, affordable rates — no hidden charges</p>
    </div>
</section>

<section class="section">
    <div class="container">

        <!-- Pricing Intro -->
        <div class="pricing-intro">
            <p>At Travelr Taxi, we believe in transparent pricing. Our rates are competitive and fair, with no hidden fees or surprise charges. Choose the pricing model that works best for your trip.</p>
        </div>

        <!-- Flat Rate Routes -->
        <div class="pricing-section">
            <h2><i class="fas fa-route"></i> Flat Rate Routes</h2>
            <p class="pricing-desc">Fixed prices for popular routes — know your fare before you ride.</p>
            <div class="pricing-table-wrap">
                <table class="pricing-table">
                    <thead>
                        <tr>
                            <th>Route</th>
                            <th>Vehicle Type</th>
                            <th>Price</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($flatRates as $rate): ?>
                        <tr>
                            <td>
                                <strong><?php echo sanitize($rate['origin']); ?></strong>
                                <i class="fas fa-arrow-right"></i>
                                <strong><?php echo sanitize($rate['destination']); ?></strong>
                            </td>
                            <td><?php echo ucfirst($rate['vehicle_type']); ?></td>
                            <td class="price-cell"><?php echo formatPrice($rate['flat_price']); ?></td>
                            <td><a href="/booking.php" class="btn btn-primary btn-sm">Book</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Distance-Based Pricing -->
        <div class="pricing-section">
            <h2><i class="fas fa-road"></i> Distance-Based Pricing</h2>
            <p class="pricing-desc">For routes not listed above, pricing is calculated based on distance.</p>
            <div class="pricing-cards-row">
                <?php foreach ($distanceRates as $rate): ?>
                <div class="pricing-card">
                    <h3><?php echo sanitize($rate['name']); ?></h3>
                    <div class="pricing-details">
                        <div class="pricing-detail-item">
                            <span>Base Fare</span>
                            <strong><?php echo formatPrice($rate['base_fare']); ?></strong>
                        </div>
                        <div class="pricing-detail-item">
                            <span>Per KM</span>
                            <strong><?php echo formatPrice($rate['per_km_rate']); ?></strong>
                        </div>
                        <?php if ($rate['per_minute_rate'] > 0): ?>
                        <div class="pricing-detail-item">
                            <span>Per Minute</span>
                            <strong><?php echo formatPrice($rate['per_minute_rate']); ?></strong>
                        </div>
                        <?php endif; ?>
                    </div>
                    <a href="/booking.php" class="btn btn-primary btn-block">Get Quote</a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Hourly Rates -->
        <?php if (!empty($hourlyRates)): ?>
        <div class="pricing-section">
            <h2><i class="fas fa-clock"></i> Hourly Rates</h2>
            <p class="pricing-desc">For tours, chauffeur service, and extended bookings.</p>
            <div class="pricing-cards-row">
                <?php foreach ($hourlyRates as $rate): ?>
                <div class="pricing-card featured">
                    <h3><?php echo sanitize($rate['name']); ?></h3>
                    <div class="pricing-big-price"><?php echo formatPrice($rate['per_hour_rate']); ?><span>/hour</span></div>
                    <ul class="pricing-features">
                        <li><i class="fas fa-check"></i> Dedicated driver</li>
                        <li><i class="fas fa-check"></i> Flexible itinerary</li>
                        <li><i class="fas fa-check"></i> All vehicle types available</li>
                        <li><i class="fas fa-check"></i> Minimum 1 hour</li>
                    </ul>
                    <a href="/booking.php?service=hourly" class="btn btn-primary btn-block">Book Hourly</a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Price Calculator -->
        <div class="pricing-section">
            <h2><i class="fas fa-calculator"></i> Fare Estimator</h2>
            <p class="pricing-desc">Get an instant estimate for your trip.</p>
            <div class="fare-calculator">
                <form id="fareCalculator" class="calculator-form">
                    <div class="form-row">
                        <div class="form-group half">
                            <label>Select Route (Optional)</label>
                            <select name="route_id" id="calcRoute">
                                <option value="">Custom Route</option>
                                <?php
                                $routes = dbFetchAll("SELECT * FROM routes WHERE is_active = 1");
                                foreach ($routes as $r):
                                ?>
                                <option value="<?php echo $r['id']; ?>" data-distance="<?php echo $r['distance_km']; ?>" data-time="<?php echo $r['estimated_time_min']; ?>">
                                    <?php echo sanitize($r['origin']); ?> → <?php echo sanitize($r['destination']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group half">
                            <label>Vehicle Type</label>
                            <select name="vehicle_type" id="calcVehicle">
                                <option value="sedan">Sedan</option>
                                <option value="van">7-Seater Van</option>
                                <option value="minibus">Mini Bus</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row" id="customDistanceRow">
                        <div class="form-group half">
                            <label>Distance (KM)</label>
                            <input type="number" name="distance" id="calcDistance" min="1" placeholder="Enter distance in KM">
                        </div>
                        <div class="form-group half">
                            <label>Estimated Time (Minutes)</label>
                            <input type="number" name="duration" id="calcDuration" min="1" placeholder="Estimated travel time">
                        </div>
                    </div>
                    <button type="button" onclick="calculateFare()" class="btn btn-primary btn-lg"><i class="fas fa-calculator"></i> Calculate Fare</button>
                </form>
                <div id="fareResult" class="fare-result" style="display:none;">
                    <h3>Estimated Fare</h3>
                    <div class="fare-amount" id="fareAmount"></div>
                    <p class="fare-note" id="fareNote"></p>
                    <a href="/booking.php" class="btn btn-primary">Book This Trip</a>
                </div>
            </div>
        </div>

    </div>
</section>

<script>
function calculateFare() {
    const routeSelect = document.getElementById('calcRoute');
    const selected = routeSelect.options[routeSelect.selectedIndex];
    const vehicle = document.getElementById('calcVehicle').value;
    let distance = parseFloat(document.getElementById('calcDistance').value) || 0;
    let duration = parseFloat(document.getElementById('calcDuration').value) || 0;

    if (selected.value) {
        distance = parseFloat(selected.dataset.distance) || distance;
        duration = parseFloat(selected.dataset.time) || duration;
    }

    if (distance <= 0) {
        alert('Please enter a distance or select a route.');
        return;
    }

    // Client-side estimation (mirrors server logic)
    let baseFare = 500;
    let perKm = 120;
    let price = baseFare + (perKm * distance);

    if (vehicle === 'van') price *= 1.4;
    if (vehicle === 'minibus') price *= 2.0;

    document.getElementById('fareAmount').textContent = 'J$' + price.toFixed(2);
    document.getElementById('fareNote').textContent = 'Estimated fare for ' + distance + ' km. Final price may vary.';
    document.getElementById('fareResult').style.display = 'block';
    document.getElementById('fareResult').scrollIntoView({behavior: 'smooth'});
}

document.getElementById('calcRoute').addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    if (selected.value) {
        document.getElementById('calcDistance').value = selected.dataset.distance;
        document.getElementById('calcDuration').value = selected.dataset.time;
    } else {
        document.getElementById('calcDistance').value = '';
        document.getElementById('calcDuration').value = '';
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
