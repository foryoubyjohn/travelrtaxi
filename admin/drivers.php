<?php
$pageTitle = 'Driver Management';
require_once 'includes/admin-header.php';

$action = sanitize($_GET['action'] ?? 'list');
$editId = intval($_GET['id'] ?? 0);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = sanitize($_POST['action'] ?? '');

    if ($postAction === 'add') {
        $firstName = sanitize($_POST['first_name']);
        $lastName = sanitize($_POST['last_name']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $password = $_POST['password'] ?? 'driver123';
        $license = sanitize($_POST['license_number']);
        $licenseExpiry = sanitize($_POST['license_expiry']);
        $vehicleId = !empty($_POST['vehicle_id']) ? intval($_POST['vehicle_id']) : null;

        $result = registerUser($firstName, $lastName, $email, $phone, $password, 'driver');
        if ($result['success']) {
            dbInsert("INSERT INTO drivers (user_id, license_number, license_expiry, vehicle_id) VALUES (?, ?, ?, ?)",
                [$result['user_id'], $license, $licenseExpiry, $vehicleId]);
            redirectWith('/admin/drivers.php', 'success', 'Driver added successfully.');
        } else {
            redirectWith('/admin/drivers.php?action=add', 'error', $result['error']);
        }
    }

    if ($postAction === 'edit') {
        $driverId = intval($_POST['id']);
        $license = sanitize($_POST['license_number']);
        $licenseExpiry = sanitize($_POST['license_expiry']);
        $vehicleId = !empty($_POST['vehicle_id']) ? intval($_POST['vehicle_id']) : null;
        $status = sanitize($_POST['status']);

        dbExecute("UPDATE drivers SET license_number=?, license_expiry=?, vehicle_id=?, status=? WHERE id=?",
            [$license, $licenseExpiry, $vehicleId, $status, $driverId]);

        // Update user info
        $driver = dbFetchOne("SELECT user_id FROM drivers WHERE id = ?", [$driverId]);
        if ($driver) {
            $firstName = sanitize($_POST['first_name']);
            $lastName = sanitize($_POST['last_name']);
            $phone = sanitize($_POST['phone']);
            dbExecute("UPDATE users SET first_name=?, last_name=?, phone=? WHERE id=?",
                [$firstName, $lastName, $phone, $driver['user_id']]);
        }
        redirectWith('/admin/drivers.php', 'success', 'Driver updated successfully.');
    }

    if ($postAction === 'delete') {
        $driverId = intval($_POST['id']);
        $driver = dbFetchOne("SELECT user_id FROM drivers WHERE id = ?", [$driverId]);
        if ($driver) {
            dbExecute("UPDATE users SET is_active = 0 WHERE id = ?", [$driver['user_id']]);
        }
        redirectWith('/admin/drivers.php', 'success', 'Driver deactivated.');
    }
}

// Get driver for editing
$editDriver = null;
if ($action === 'edit' && $editId) {
    $editDriver = dbFetchOne("SELECT d.*, u.first_name, u.last_name, u.email, u.phone FROM drivers d JOIN users u ON d.user_id = u.id WHERE d.id = ?", [$editId]);
}

$drivers = dbFetchAll("SELECT d.*, u.first_name, u.last_name, u.email, u.phone, u.is_active, v.name as vehicle_name FROM drivers d JOIN users u ON d.user_id = u.id LEFT JOIN vehicles v ON d.vehicle_id = v.id ORDER BY u.first_name");
$vehicles = dbFetchAll("SELECT * FROM vehicles WHERE status = 'active' ORDER BY name");
?>

<div class="page-header-row">
    <h1 class="page-title">Driver Management</h1>
    <a href="/admin/drivers.php?action=add" class="btn btn-primary"><i class="fas fa-user-plus"></i> Add Driver</a>
</div>

<?php if ($action === 'add' || ($action === 'edit' && $editDriver)): ?>
<div class="admin-card">
    <h3><?php echo $action === 'add' ? 'Add New Driver' : 'Edit Driver'; ?></h3>
    <form method="POST">
        <input type="hidden" name="action" value="<?php echo $action; ?>">
        <?php if ($editDriver): ?>
        <input type="hidden" name="id" value="<?php echo $editDriver['id']; ?>">
        <?php endif; ?>

        <div class="form-row">
            <div class="form-group half">
                <label>First Name *</label>
                <input type="text" name="first_name" required value="<?php echo $editDriver ? sanitize($editDriver['first_name']) : ''; ?>">
            </div>
            <div class="form-group half">
                <label>Last Name *</label>
                <input type="text" name="last_name" required value="<?php echo $editDriver ? sanitize($editDriver['last_name']) : ''; ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group half">
                <label>Email *</label>
                <input type="email" name="email" required value="<?php echo $editDriver ? sanitize($editDriver['email']) : ''; ?>" <?php echo $editDriver ? 'readonly' : ''; ?>>
            </div>
            <div class="form-group half">
                <label>Phone *</label>
                <input type="tel" name="phone" required value="<?php echo $editDriver ? sanitize($editDriver['phone']) : ''; ?>">
            </div>
        </div>
        <?php if ($action === 'add'): ?>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" value="driver123" placeholder="Default: driver123">
        </div>
        <?php endif; ?>
        <div class="form-row">
            <div class="form-group half">
                <label>License Number</label>
                <input type="text" name="license_number" value="<?php echo $editDriver ? sanitize($editDriver['license_number']) : ''; ?>">
            </div>
            <div class="form-group half">
                <label>License Expiry</label>
                <input type="date" name="license_expiry" value="<?php echo $editDriver ? $editDriver['license_expiry'] : ''; ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group half">
                <label>Assigned Vehicle</label>
                <select name="vehicle_id">
                    <option value="">-- None --</option>
                    <?php foreach ($vehicles as $v): ?>
                    <option value="<?php echo $v['id']; ?>" <?php echo ($editDriver && $editDriver['vehicle_id'] == $v['id']) ? 'selected' : ''; ?>><?php echo sanitize($v['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($editDriver): ?>
            <div class="form-group half">
                <label>Status</label>
                <select name="status">
                    <option value="available" <?php echo $editDriver['status'] === 'available' ? 'selected' : ''; ?>>Available</option>
                    <option value="on_trip" <?php echo $editDriver['status'] === 'on_trip' ? 'selected' : ''; ?>>On Trip</option>
                    <option value="offline" <?php echo $editDriver['status'] === 'offline' ? 'selected' : ''; ?>>Offline</option>
                </select>
            </div>
            <?php endif; ?>
        </div>
        <div class="form-buttons">
            <a href="/admin/drivers.php" class="btn btn-outline">Cancel</a>
            <button type="submit" class="btn btn-primary"><?php echo $action === 'add' ? 'Add Driver' : 'Update Driver'; ?></button>
        </div>
    </form>
</div>
<?php endif; ?>

<!-- Driver List -->
<div class="admin-card">
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>License</th>
                    <th>Vehicle</th>
                    <th>Status</th>
                    <th>Rating</th>
                    <th>Trips</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($drivers as $d): ?>
                <tr class="<?php echo !$d['is_active'] ? 'row-inactive' : ''; ?>">
                    <td><strong><?php echo sanitize($d['first_name'] . ' ' . $d['last_name']); ?></strong></td>
                    <td><?php echo sanitize($d['email']); ?></td>
                    <td><?php echo sanitize($d['phone']); ?></td>
                    <td><?php echo sanitize($d['license_number']); ?></td>
                    <td><?php echo $d['vehicle_name'] ? sanitize($d['vehicle_name']) : '-'; ?></td>
                    <td><?php echo statusBadge($d['status']); ?></td>
                    <td><i class="fas fa-star" style="color:#f59e0b"></i> <?php echo number_format($d['rating'], 1); ?></td>
                    <td><?php echo $d['total_trips']; ?></td>
                    <td>
                        <a href="/admin/drivers.php?action=edit&id=<?php echo $d['id']; ?>" class="btn btn-sm btn-outline">Edit</a>
                        <form method="POST" class="inline-form">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $d['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-danger btn-delete">Deactivate</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/admin-footer.php'; ?>
