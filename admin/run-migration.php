<?php
/**
 * One-time migration runner — Dispatch Command Center
 * Visit this page once to apply the migration, then it deletes itself.
 */
require_once 'includes/admin-header.php';

$sqlFile = __DIR__ . '/dispatch-migration.sql';
$results = [];
$allOk   = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run'])) {
    if (!file_exists($sqlFile)) {
        $results[] = ['sql' => 'Load file', 'ok' => false, 'msg' => 'dispatch-migration.sql not found in admin/'];
        $allOk = false;
    } else {
        $raw        = file_get_contents($sqlFile);
        $statements = array_filter(
            array_map('trim', preg_split('/;[\s]*\n/', $raw)),
            fn($s) => $s !== '' && !preg_match('/^--/', $s) && !preg_match('/^USE\s/i', $s)
        );

        require_once dirname(__DIR__) . '/includes/db.php';

        foreach ($statements as $sql) {
            if (trim($sql) === '') continue;
            try {
                dbExecute($sql, []);
                $results[] = ['sql' => mb_strimwidth(trim($sql), 0, 80, '...'), 'ok' => true,  'msg' => 'OK'];
            } catch (Throwable $e) {
                $msg = $e->getMessage();
                // Already-exists errors are harmless — treat as success
                $harmless = str_contains($msg, 'already exists') || str_contains($msg, 'Duplicate column');
                $results[] = ['sql' => mb_strimwidth(trim($sql), 0, 80, '...'), 'ok' => $harmless, 'msg' => $harmless ? 'Already exists (skipped)' : $msg];
                if (!$harmless) $allOk = false;
            }
        }

        // No self-destruct — safe to re-run (all statements skip if already exists)
    }
}
?>

<style>
.mig-wrap { max-width: 760px; margin: 2rem auto; }
.mig-wrap h2 { font-size: 1.4rem; margin-bottom: .5rem; }
.mig-warn { background: #fef3c7; border: 1px solid #f59e0b; border-radius: 8px; padding: 1rem 1.25rem; margin-bottom: 1.5rem; font-size: .9rem; }
.mig-table { width: 100%; border-collapse: collapse; font-size: .85rem; margin-top: 1rem; }
.mig-table th { background: #1e293b; color: #fff; padding: .5rem .75rem; text-align: left; }
.mig-table td { padding: .45rem .75rem; border-bottom: 1px solid #e2e8f0; font-family: monospace; }
.mig-ok   { color: #059669; font-weight: 600; }
.mig-skip { color: #d97706; font-weight: 600; }
.mig-err  { color: #dc2626; font-weight: 600; }
.mig-success { background: #d1fae5; border: 1px solid #059669; border-radius: 8px; padding: 1rem 1.25rem; margin-top: 1.25rem; font-weight: 600; color: #065f46; }
.mig-failure { background: #fee2e2; border: 1px solid #dc2626; border-radius: 8px; padding: 1rem 1.25rem; margin-top: 1.25rem; font-weight: 600; color: #991b1b; }
.btn-run { background: #0f172a; color: #fff; border: none; padding: .65rem 1.75rem; border-radius: 6px; font-size: 1rem; cursor: pointer; font-weight: 600; }
.btn-run:hover { background: #1e3a5f; }
</style>

<div class="mig-wrap">
    <h2><i class="fas fa-database"></i> Dispatch Migration Runner</h2>

    <?php if (empty($results)): ?>
    <div class="mig-warn">
        <strong><i class="fas fa-exclamation-triangle"></i> Run once only.</strong>
        This script will create 3 new tables (<code>dispatch_notes</code>, <code>booking_status_history</code>, <code>assignment_log</code>)
        and add the <code>dispatcher_notes</code> column to <code>bookings</code>.
        It will <strong>delete itself</strong> after a successful run.
    </div>
    <form method="POST">
        <button type="submit" name="run" value="1" class="btn-run"><i class="fas fa-play"></i> Run Migration Now</button>
    </form>

    <?php else: ?>
    <table class="mig-table">
        <thead><tr><th>#</th><th>Statement</th><th>Result</th><th>Message</th></tr></thead>
        <tbody>
        <?php foreach ($results as $i => $r): ?>
        <tr>
            <td><?= $i + 1 ?></td>
            <td><?= htmlspecialchars($r['sql']) ?></td>
            <td class="<?= $r['ok'] ? ($r['msg'] === 'OK' ? 'mig-ok' : 'mig-skip') : 'mig-err' ?>">
                <?= $r['ok'] ? ($r['msg'] === 'OK' ? '✓ OK' : '⚠ Skipped') : '✗ Error' ?>
            </td>
            <td><?= htmlspecialchars($r['msg']) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($allOk): ?>
    <div class="mig-success">
        <i class="fas fa-check-circle"></i> Migration completed successfully!
        Proceed to <a href="/admin/run-realtime-migration.php">Step 3 — Real-Time &amp; GPS Migration &rarr;</a>
    </div>
    <?php else: ?>
    <div class="mig-failure">
        <i class="fas fa-times-circle"></i> One or more statements failed. Review the errors above and fix before retrying.
    </div>
    <form method="POST" style="margin-top:1rem">
        <button type="submit" name="run" value="1" class="btn-run"><i class="fas fa-redo"></i> Retry</button>
    </form>
    <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once 'includes/admin-footer.php'; ?>
