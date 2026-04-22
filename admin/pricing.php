<?php
$pageTitle = 'Pricing Management';
require_once 'includes/admin-header.php';

$action = sanitize($_GET['action'] ?? 'list');
$editId = intval($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = sanitize($_POST['action'] ?? '');
    if ($postAction === 'add' || $postAction === 'edit') {
        $name = sanitize($_POST['name']);
        $type = sanitize($_POST['type']);
        $routeId = !empty($_POST['route_id']) ? intval($_POST['route_id']) : null;
        $baseFare = floatval($_POST['base_fare'] ?? 0);
        $perKm = floatval($_POST['per_km_rate'] ?? 0);
        $perMin = floatval($_POST['per_minute_rate'] ?? 0);
        $perHour = floatval($_POST['per_hour_rate'] ?? 0);
        $flatPrice = floatval($_POST['flat_price'] ?? 0);
        $vehicleType = sanitize($_POST['vehicle_type']);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($postAction === 'add') {
            dbInsert("INSERT INTO pricing_rules (name, type, route_id, base_fare, per_km_rate, per_minute_rate, per_hour_rate, flat_price, vehicle_type, is_active) VALUES (?,?,?,?,?,?,?,?,?,?)",
                [$name, $type, $routeId, $baseFare, $perKm, $perMin, $perHour, $flatPrice, $vehicleType, $isActive]);
            redirectWith('/admin/pricing.php', 'success', 'Pricing rule added.');
        } else {
            $id = intval($_POST['id']);
            dbExecute("UPDATE pricing_rules SET name=?, type=?, route_id=?, base_fare=?, per_km_rate=?, per_minute_rate=?, per_hour_rate=?, flat_price=?, vehicle_type=?, is_active=? WHERE id=?",
                [$name, $type, $routeId, $baseFare, $perKm, $perMin, $perHour, $flatPrice, $vehicleType, $isActive, $id]);
            redirectWith('/admin/pricing.php', 'success', 'Pricing rule updated.');
        }
    }
    if ($postAction === 'delete') {
        dbExecute("DELETE FROM pricing_rules WHERE id = ?", [intval($_POST['id'])]);
        redirectWith('/admin/pricing.php', 'success', 'Pricing rule deleted.');
    }
}

$editRule = null;
if ($action === 'edit' && $editId) {
    $editRule = dbFetchOne("SELECT * FROM pricing_rules WHERE id = ?", [$editId]);
}

$rules = dbFetchAll("SELECT pr.*, r.origin, r.destination FROM pricing_rules pr LEFT JOIN routes r ON pr.route_id = r.id ORDER BY pr.type, pr.name");
$routes = dbFetchAll("SELECT * FROM routes WHERE is_active = 1 ORDER BY origin");
?>

<div class="page-header-row">
    <h1 class="page-title">Pricing Management</h1>
    <a href="/admin/pricing.php?action=add" class="btn btn-primary"><i class="fas fa-plus"></i> Add Pricing Rule</a>
</div>

<?php if ($action === 'add' || ($action === 'edit' && $editRule)): ?>
<div class="admin-card">
    <h3><?php echo $action === 'add' ? 'Add Pricing Rule' : 'Edit Pricing Rule'; ?></h3>
    <form method="POST">
        <input type="hidden" name="action" value="<?php echo $action; ?>">
        <?php if ($editRule): ?><input type="hidden" name="id" value="<?php echo $editRule['id']; ?>"><?php endif; ?>

        <div class="form-row">
            <div class="form-group half">
                <label>Rule Name *</label>
                <input type="text" name="name" required value="<?php echo $editRule ? sanitize($editRule['name']) : ''; ?>">
            </div>
            <div class="form-group half">
                <label>Type *</label>
                <select name="type" required>
                    <option value="flat" <?php echo ($editRule && $editRule['type'] === 'flat') ? 'selected' : ''; ?>>Flat Rate</option>
                    <option value="distance" <?php echo ($editRule && $editRule['type'] === 'distance') ? 'selected' : ''; ?>>Distance-Based</option>
                    <option value="rideshare" <?php echo ($editRule && $editRule['type'] === 'rideshare') ? 'selected' : ''; ?>>Rideshare</option>
                    <option value="hourly" <?php echo ($editRule && $editRule['type'] === 'hourly') ? 'selected' : ''; ?>>Hourly</option>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group half">
                <label>Route (for flat rates)</label>
                <select name="route_id">
                    <option value="">-- None --</option>
                    <?php foreach ($routes as $r): ?>
                    <option value="<?php echo $r['id']; ?>" <?php echo ($editRule && $editRule['route_id'] == $r['id']) ? 'selected' : ''; ?>><?php echo sanitize($r['origin'] . ' → ' . $r['destination']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group half">
                <label>Vehicle Type</label>
                <select name="vehicle_type">
                    <option value="all" <?php echo ($editRule && $editRule['vehicle_type'] === 'all') ? 'selected' : ''; ?>>All</option>
                    <option value="sedan" <?php echo ($editRule && $editRule['vehicle_type'] === 'sedan') ? 'selected' : ''; ?>>Sedan</option>
                    <option value="van" <?php echo ($editRule && $editRule['vehicle_type'] === 'van') ? 'selected' : ''; ?>>Van</option>
                    <option value="minibus" <?php echo ($editRule && $editRule['vehicle_type'] === 'minibus') ? 'selected' : ''; ?>>Mini Bus</option>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group half">
                <label>Flat Price</label>
                <input type="number" name="flat_price" step="0.01" value="<?php echo $editRule ? $editRule['flat_price'] : '0'; ?>">
            </div>
            <div class="form-group half">
                <label>Base Fare</label>
                <input type="number" name="base_fare" step="0.01" value="<?php echo $editRule ? $editRule['base_fare'] : '0'; ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group third">
                <label>Per KM Rate</label>
                <input type="number" name="per_km_rate" step="0.01" value="<?php echo $editRule ? $editRule['per_km_rate'] : '0'; ?>">
            </div>
            <div class="form-group third">
                <label>Per Minute Rate</label>
                <input type="number" name="per_minute_rate" step="0.01" value="<?php echo $editRule ? $editRule['per_minute_rate'] : '0'; ?>">
            </div>
            <div class="form-group third">
                <label>Per Hour Rate</label>
                <input type="number" name="per_hour_rate" step="0.01" value="<?php echo $editRule ? $editRule['per_hour_rate'] : '0'; ?>">
            </div>
        </div>
        <div class="form-group">
            <label><input type="checkbox" name="is_active" <?php echo (!$editRule || $editRule['is_active']) ? 'checked' : ''; ?>> Active</label>
        </div>
        <div class="form-buttons">
            <a href="/admin/pricing.php" class="btn btn-outline">Cancel</a>
            <button type="submit" class="btn btn-primary">Save</button>
        </div>
    </form>
</div>
<?php endif; ?>

<div class="admin-card">
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Route</th>
                    <th>Flat Price</th>
                    <th>Base Fare</th>
                    <th>Per KM</th>
                    <th>Per Min</th>
                    <th>Per Hour</th>
                    <th>Vehicle</th>
                    <th>Active</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rules as $r): ?>
                <tr>
                    <td><strong><?php echo sanitize($r['name']); ?></strong></td>
                    <td><?php echo ucfirst($r['type']); ?></td>
                    <td><?php echo $r['origin'] ? sanitize($r['origin'] . ' → ' . $r['destination']) : '-'; ?></td>
                    <td><?php echo $r['flat_price'] > 0 ? formatPrice($r['flat_price']) : '-'; ?></td>
                    <td><?php echo $r['base_fare'] > 0 ? formatPrice($r['base_fare']) : '-'; ?></td>
                    <td><?php echo $r['per_km_rate'] > 0 ? formatPrice($r['per_km_rate']) : '-'; ?></td>
                    <td><?php echo $r['per_minute_rate'] > 0 ? formatPrice($r['per_minute_rate']) : '-'; ?></td>
                    <td><?php echo $r['per_hour_rate'] > 0 ? formatPrice($r['per_hour_rate']) : '-'; ?></td>
                    <td><?php echo ucfirst($r['vehicle_type']); ?></td>
                    <td><?php echo $r['is_active'] ? '<i class="fas fa-check text-green"></i>' : '<i class="fas fa-times text-red"></i>'; ?></td>
                    <td>
                        <a href="?action=edit&id=<?php echo $r['id']; ?>" class="btn btn-sm btn-outline">Edit</a>
                        <form method="POST" class="inline-form">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-danger btn-delete">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/admin-footer.php'; ?>
