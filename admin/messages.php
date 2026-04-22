<?php
$pageTitle = 'Contact Messages';
require_once 'includes/admin-header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $postAction = sanitize($_POST['action'] ?? '');
    if ($postAction === 'read' && $id) {
        dbExecute("UPDATE contact_messages SET is_read = 1 WHERE id = ?", [$id]);
        redirectWith('/admin/messages.php', 'success', 'Marked as read.');
    }
    if ($postAction === 'delete' && $id) {
        dbExecute("DELETE FROM contact_messages WHERE id = ?", [$id]);
        redirectWith('/admin/messages.php', 'success', 'Message deleted.');
    }
}

$messages = dbFetchAll("SELECT * FROM contact_messages ORDER BY is_read ASC, created_at DESC");
?>

<h1 class="page-title">Contact Messages</h1>

<div class="admin-card">
    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Subject</th><th>Message</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                <?php if (empty($messages)): ?>
                <tr><td colspan="8" class="text-center">No messages.</td></tr>
                <?php else: ?>
                <?php foreach ($messages as $m): ?>
                <tr class="<?php echo !$m['is_read'] ? 'row-unread' : ''; ?>">
                    <td><strong><?php echo sanitize($m['name']); ?></strong></td>
                    <td><?php echo sanitize($m['email']); ?></td>
                    <td><?php echo sanitize($m['phone']); ?></td>
                    <td><?php echo sanitize($m['subject']); ?></td>
                    <td class="message-cell"><?php echo sanitize(substr($m['message'], 0, 80)); ?></td>
                    <td><?php echo formatDate($m['created_at']); ?></td>
                    <td><?php echo $m['is_read'] ? '<span class="badge-success">Read</span>' : '<span class="badge-warning">New</span>'; ?></td>
                    <td>
                        <?php if (!$m['is_read']): ?>
                        <form method="POST" class="inline-form"><input type="hidden" name="action" value="read"><input type="hidden" name="id" value="<?php echo $m['id']; ?>"><button type="submit" class="btn btn-sm btn-outline">Mark Read</button></form>
                        <?php endif; ?>
                        <form method="POST" class="inline-form"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo $m['id']; ?>"><button type="submit" class="btn btn-sm btn-danger btn-delete">Delete</button></form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/admin-footer.php'; ?>
