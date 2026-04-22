<?php
$pageTitle = 'Fleet Management';
require_once 'includes/admin-header.php';

$action = sanitize($_GET['action'] ?? 'list');
$editId = intval($_GET['id'] ?? 0);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = sanitize($_POST['action'] ?? '');

    if ($postAction === 'add' || $postAction === 'edit') {
        $name = sanitize($_POST['name']);
        $type = sanitize($_POST['type']);
        $capacity = intval($_POST['capacity']);
        $plateNumber = sanitize($_POST['plate_number']);
        $color = sanitize($_POST['color']);
        $status = sanitize($_POST['status']);
        $notes = sanitize($_POST['notes'] ?? '');
        $image = null;

        // Handle image upload
        if (!empty($_FILES['image']['name'])) {
            $upload = uploadFile($_FILES['image'], 'uploads/');
            if ($upload['success']) {
                $image = $upload['filename'];
            }
        }

        if ($postAction === 'add') {
            dbInsert("INSERT INTO vehicles (name, type, capacity, plate_number, color, image, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                [$name, $type, $capacity, $plateNumber, $color, $image, $status, $notes]);
            redirectWith('/admin/fleet.php', 'success', 'Vehicle added successfully.');
        } else {
            $id = intval($_POST['id']);
            $sql = "UPDATE vehicles SET name=?, type=?, capacity=?, plate_number=?, color=?, status=?, notes=?";
            $params = [$name, $type, $capacity, $plateNumber, $color, $status, $notes];
            if ($image) {
                $sql .= ", image=?";
                $params[] = $image;
            }
            $sql .= " WHERE id=?";
            $params[] = $id;
            dbExecute($sql, $params);
            redirectWith('/admin/fleet.php', 'success', 'Vehicle updated successfully.');
        }
    }

    if ($postAction === 'delete') {
        $id = intval($_POST['id']);
        dbExecute("UPDATE vehicles SET status = 'retired' WHERE id = ?", [$id]);
        redirectWith('/admin/fleet.php', 'success', 'Vehicle retired successfully.');
    }
}

// Get vehicle for editing
$editVehicle = null;
if ($action === 'edit' && $editId) {
    $editVehicle = dbFetchOne("SELECT * FROM vehicles WHERE id = ?", [$editId]);
}

$vehicles = dbFetchAll("SELECT * FROM vehicles ORDER BY status, type, name");
?>

<div class="page-header-row">
    <h1 class="page-title">Fleet Management</h1>
    <a href="/admin/fleet.php?action=add" class="btn btn-primary"><i class="fas fa-plus"></i> Add Vehicle</a>
</div>

<?php if ($action === 'add' || ($action === 'edit' && $editVehicle)): ?>
<!-- Add/Edit Form -->
<div class="admin-card">
    <h3><?php echo $action === 'add' ? 'Add New Vehicle' : 'Edit Vehicle'; ?></h3>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="<?php echo $action; ?>">
        <?php if ($editVehicle): ?>
        <input type="hidden" name="id" value="<?php echo $editVehicle['id']; ?>">
        <?php endif; ?>

        <div class="form-row">
            <div class="form-group half">
                <label>Vehicle Name *</label>
                <input type="text" name="name" required value="<?php echo $editVehicle ? sanitize($editVehicle['name']) : ''; ?>" placeholder="e.g., Toyota Corolla 2023">
            </div>
            <div class="form-group half">
                <label>Type *</label>
                <select name="type" required>
                    <option value="sedan" <?php echo ($editVehicle && $editVehicle['type'] === 'sedan') ? 'selected' : ''; ?>>Sedan</option>
                    <option value="van" <?php echo ($editVehicle && $editVehicle['type'] === 'van') ? 'selected' : ''; ?>>7-Seater Van</option>
                    <option value="minibus" <?php echo ($editVehicle && $editVehicle['type'] === 'minibus') ? 'selected' : ''; ?>>Mini Bus</option>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group half">
                <label>Capacity *</label>
                <input type="number" name="capacity" required min="1" max="50" value="<?php echo $editVehicle ? $editVehicle['capacity'] : '4'; ?>">
            </div>
            <div class="form-group half">
                <label>Plate Number</label>
                <input type="text" name="plate_number" value="<?php echo $editVehicle ? sanitize($editVehicle['plate_number']) : ''; ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group half">
                <label>Color</label>
                <input type="text" name="color" value="<?php echo $editVehicle ? sanitize($editVehicle['color']) : ''; ?>">
            </div>
            <div class="form-group half">
                <label>Status</label>
                <select name="status">
                    <option value="active" <?php echo ($editVehicle && $editVehicle['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                    <option value="maintenance" <?php echo ($editVehicle && $editVehicle['status'] === 'maintenance') ? 'selected' : ''; ?>>Maintenance</option>
                    <option value="retired" <?php echo ($editVehicle && $editVehicle['status'] === 'retired') ? 'selected' : ''; ?>>Retired</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label>Vehicle Image</label>
            <input type="file" name="image" accept="image/*">
            <?php if ($editVehicle && $editVehicle['image']): ?>
            <p class="form-hint">Current: <?php echo sanitize($editVehicle['image']); ?></p>
            <?php endif; ?>
        </div>
        <div class="form-group">
            <label>Notes</label>
            <textarea name="notes" rows="2"><?php echo $editVehicle ? sanitize($editVehicle['notes']) : ''; ?></textarea>
        </div>
        <div class="form-buttons">
            <a href="/admin/fleet.php" class="btn btn-outline">Cancel</a>
            <button type="submit" class="btn btn-primary"><?php echo $action === 'add' ? 'Add Vehicle' : 'Update Vehicle'; ?></button>
        </div>
    </form>
</div>
<?php endif; ?>

<!-- Vehicle List -->
<div class="admin-card">
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Capacity</th>
                    <th>Plate</th>
                    <th>Color</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($vehicles as $v): ?>
                <tr>
                    <td>
                        <?php if ($v['image']): ?>
                        <img src="/uploads/<?php echo sanitize($v['image']); ?>" class="table-thumb" alt="">
                        <?php else: ?>
                        <div class="table-thumb-placeholder"><i class="fas fa-car"></i></div>
                        <?php endif; ?>
                    </td>
                    <td><strong><?php echo sanitize($v['name']); ?></strong></td>
                    <td><?php echo ucfirst($v['type']); ?></td>
                    <td><?php echo $v['capacity']; ?></td>
                    <td><?php echo sanitize($v['plate_number']); ?></td>
                    <td><?php echo sanitize($v['color']); ?></td>
                    <td><?php echo statusBadge($v['status']); ?></td>
                    <td>
                        <a href="/admin/fleet.php?action=edit&id=<?php echo $v['id']; ?>" class="btn btn-sm btn-outline">Edit</a>
                        <form method="POST" class="inline-form">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $v['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-danger btn-delete">Retire</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/admin-footer.php'; ?>
