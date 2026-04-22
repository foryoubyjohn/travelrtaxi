<?php
$pageTitle = 'Route Management';
require_once 'includes/admin-header.php';

$action = sanitize($_GET['action'] ?? 'list');
$editId = intval($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = sanitize($_POST['action'] ?? '');
    if ($postAction === 'add' || $postAction === 'edit') {
        $origin = sanitize($_POST['origin']);
        $destination = sanitize($_POST['destination']);
        $distance = floatval($_POST['distance_km']);
        $time = intval($_POST['estimated_time_min']);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($postAction === 'add') {
            dbInsert("INSERT INTO routes (origin, destination, distance_km, estimated_time_min, is_active) VALUES (?,?,?,?,?)",
                [$origin, $destination, $distance, $time, $isActive]);
            redirectWith('/admin/routes.php', 'success', 'Route added.');
        } else {
            $id = intval($_POST['id']);
            dbExecute("UPDATE routes SET origin=?, destination=?, distance_km=?, estimated_time_min=?, is_active=? WHERE id=?",
                [$origin, $destination, $distance, $time, $isActive, $id]);
            redirectWith('/admin/routes.php', 'success', 'Route updated.');
        }
    }
    if ($postAction === 'delete') {
        dbExecute("DELETE FROM routes WHERE id = ?", [intval($_POST['id'])]);
        redirectWith('/admin/routes.php', 'success', 'Route deleted.');
    }
}

$editRoute = ($action === 'edit' && $editId) ? dbFetchOne("SELECT * FROM routes WHERE id = ?", [$editId]) : null;
$routes = dbFetchAll("SELECT * FROM routes ORDER BY origin, destination");
?>

<div class="page-header-row">
    <h1 class="page-title">Route Management</h1>
    <a href="/admin/routes.php?action=add" class="btn btn-primary"><i class="fas fa-plus"></i> Add Route</a>
</div>

<?php if ($action === 'add' || ($action === 'edit' && $editRoute)): ?>
<div class="admin-card">
    <h3><?php echo $action === 'add' ? 'Add Route' : 'Edit Route'; ?></h3>
    <form method="POST">
        <input type="hidden" name="action" value="<?php echo $action; ?>">
        <?php if ($editRoute): ?><input type="hidden" name="id" value="<?php echo $editRoute['id']; ?>"><?php endif; ?>
        <div class="form-row">
            <div class="form-group half">
                <label>Origin *</label>
                <input type="text" name="origin" required value="<?php echo $editRoute ? sanitize($editRoute['origin']) : ''; ?>">
            </div>
            <div class="form-group half">
                <label>Destination *</label>
                <input type="text" name="destination" required value="<?php echo $editRoute ? sanitize($editRoute['destination']) : ''; ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group half">
                <label>Distance (KM)</label>
                <input type="number" name="distance_km" step="0.1" value="<?php echo $editRoute ? $editRoute['distance_km'] : ''; ?>">
            </div>
            <div class="form-group half">
                <label>Estimated Time (Minutes)</label>
                <input type="number" name="estimated_time_min" value="<?php echo $editRoute ? $editRoute['estimated_time_min'] : ''; ?>">
            </div>
        </div>
        <div class="form-group">
            <label><input type="checkbox" name="is_active" <?php echo (!$editRoute || $editRoute['is_active']) ? 'checked' : ''; ?>> Active</label>
        </div>
        <div class="form-buttons">
            <a href="/admin/routes.php" class="btn btn-outline">Cancel</a>
            <button type="submit" class="btn btn-primary">Save</button>
        </div>
    </form>
</div>
<?php endif; ?>

<div class="admin-card">
    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr><th>Origin</th><th>Destination</th><th>Distance</th><th>Est. Time</th><th>Active</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($routes as $r): ?>
                <tr>
                    <td><?php echo sanitize($r['origin']); ?></td>
                    <td><?php echo sanitize($r['destination']); ?></td>
                    <td><?php echo $r['distance_km']; ?> km</td>
                    <td><?php echo $r['estimated_time_min']; ?> min</td>
                    <td><?php echo $r['is_active'] ? '<i class="fas fa-check text-green"></i>' : '<i class="fas fa-times text-red"></i>'; ?></td>
                    <td>
                        <a href="?action=edit&id=<?php echo $r['id']; ?>" class="btn btn-sm btn-outline">Edit</a>
                        <form method="POST" class="inline-form"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo $r['id']; ?>"><button type="submit" class="btn btn-sm btn-danger btn-delete">Delete</button></form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/admin-footer.php'; ?>
