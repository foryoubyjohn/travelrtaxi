<?php
$pageTitle = 'Review Management';
require_once 'includes/admin-header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = sanitize($_POST['action'] ?? '');
    $id = intval($_POST['id'] ?? 0);
    if ($postAction === 'approve' && $id) {
        dbExecute("UPDATE testimonials SET is_approved = 1 WHERE id = ?", [$id]);
        redirectWith('/admin/testimonials.php', 'success', 'Review approved.');
    }
    if ($postAction === 'reject' && $id) {
        dbExecute("UPDATE testimonials SET is_approved = 0 WHERE id = ?", [$id]);
        redirectWith('/admin/testimonials.php', 'success', 'Review hidden.');
    }
    if ($postAction === 'delete' && $id) {
        dbExecute("DELETE FROM testimonials WHERE id = ?", [$id]);
        redirectWith('/admin/testimonials.php', 'success', 'Review deleted.');
    }
}

$reviews = dbFetchAll("SELECT * FROM testimonials ORDER BY is_approved ASC, created_at DESC");
?>

<h1 class="page-title">Review Management</h1>

<div class="admin-card">
    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr><th>Customer</th><th>Location</th><th>Rating</th><th>Message</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($reviews as $r): ?>
                <tr class="<?php echo !$r['is_approved'] ? 'row-pending' : ''; ?>">
                    <td><strong><?php echo sanitize($r['customer_name']); ?></strong></td>
                    <td><?php echo sanitize($r['location']); ?></td>
                    <td><?php for ($i = 0; $i < $r['rating']; $i++) echo '<i class="fas fa-star" style="color:#f59e0b"></i>'; ?></td>
                    <td class="message-cell"><?php echo sanitize(substr($r['message'], 0, 100)); ?><?php echo strlen($r['message']) > 100 ? '...' : ''; ?></td>
                    <td><?php echo formatDate($r['created_at']); ?></td>
                    <td><?php echo $r['is_approved'] ? '<span class="badge-success">Approved</span>' : '<span class="badge-warning">Pending</span>'; ?></td>
                    <td>
                        <?php if (!$r['is_approved']): ?>
                        <form method="POST" class="inline-form"><input type="hidden" name="action" value="approve"><input type="hidden" name="id" value="<?php echo $r['id']; ?>"><button type="submit" class="btn btn-sm btn-success">Approve</button></form>
                        <?php else: ?>
                        <form method="POST" class="inline-form"><input type="hidden" name="action" value="reject"><input type="hidden" name="id" value="<?php echo $r['id']; ?>"><button type="submit" class="btn btn-sm btn-outline">Hide</button></form>
                        <?php endif; ?>
                        <form method="POST" class="inline-form"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo $r['id']; ?>"><button type="submit" class="btn btn-sm btn-danger btn-delete">Delete</button></form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/admin-footer.php'; ?>
