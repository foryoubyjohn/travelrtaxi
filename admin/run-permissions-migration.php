<?php
/**
 * Migration Runner — Permissions, Tracking Token & Schema Fixes
 * Runs permissions_migration.sql from the project root.
 * Admin access required.
 */
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: /admin/login.php');
    exit;
}

$sqlFile = dirname(__DIR__) . '/permissions_migration.sql';
$results = [];
$allOk   = true;
$ran     = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run'])) {
    $ran = true;
    if (!file_exists($sqlFile)) {
        $results[] = ['sql' => 'Load file', 'ok' => false, 'msg' => 'permissions_migration.sql not found'];
        $allOk = false;
    } else {
        $raw   = file_get_contents($sqlFile);
        $chunks = preg_split('/;[ \t]*\n/', $raw);

        // For each chunk: strip leading blank lines and comment lines, then keep
        // whatever real SQL remains (filtering USE statements and empty results).
        $statements = [];
        foreach ($chunks as $chunk) {
            $lines = explode("\n", $chunk);
            while (!empty($lines)) {
                $first = ltrim($lines[0]);
                if ($first === '' || substr($first, 0, 2) === '--' || substr($first, 0, 2) === '/*') {
                    array_shift($lines);
                } else {
                    break;
                }
            }
            $cleaned = trim(implode("\n", $lines));
            if ($cleaned !== '' && !preg_match('/^USE\s/i', $cleaned)) {
                $statements[] = $cleaned;
            }
        }

        foreach ($statements as $sql) {
            if (trim($sql) === '') continue;
            try {
                dbExecute($sql, []);
                $results[] = ['sql' => mb_strimwidth(trim($sql), 0, 100, '...'), 'ok' => true, 'msg' => 'OK'];
            } catch (Throwable $e) {
                $msg      = $e->getMessage();
                // Treat "already exists" / duplicate as harmless
                $harmless = stripos($msg, 'already exists')       !== false
                         || stripos($msg, 'Duplicate column')      !== false
                         || stripos($msg, 'Duplicate column name') !== false
                         || stripos($msg, 'Duplicate entry')       !== false
                         || stripos($msg, 'Duplicate key name')    !== false
                         || stripos($msg, 'duplicate key')         !== false;
                $results[] = [
                    'sql' => mb_strimwidth(trim($sql), 0, 100, '...'),
                    'ok'  => $harmless,
                    'msg' => $harmless ? 'Already exists (skipped)' : $msg,
                ];
                if (!$harmless) $allOk = false;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Permissions & Schema Migration</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; background: #f3f4f6; color: #1a1a1a; padding: 40px 20px; }
        .wrap { max-width: 860px; margin: 0 auto; }
        h1 { font-size: 1.5rem; margin-bottom: 6px; }
        .sub { color: #6b7280; font-size: .9rem; margin-bottom: 24px; }
        .card { background: #fff; border-radius: 12px; padding: 28px; box-shadow: 0 1px 4px rgba(0,0,0,.08); margin-bottom: 20px; }
        .info { background: #eff6ff; border: 1px solid #3b82f6; border-radius: 8px; padding: 14px 18px; margin-bottom: 20px; font-size: .88rem; line-height: 1.7; }
        .info ul { margin: 6px 0 0 20px; }
        .info li { margin-bottom: 3px; }
        code { background: #e5e7eb; padding: 1px 5px; border-radius: 3px; font-size: .85em; }
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 12px 28px; background: #1a1a1a; color: #FFD400; border: none; border-radius: 8px; font-size: 1rem; font-weight: 700; cursor: pointer; transition: background .15s; }
        .btn:hover { background: #333; }
        table { width: 100%; border-collapse: collapse; font-size: .84rem; }
        th { background: #1e293b; color: #fff; padding: 8px 12px; text-align: left; }
        td { padding: 7px 12px; border-bottom: 1px solid #e5e7eb; font-family: monospace; word-break: break-all; }
        .ok   { color: #059669; font-weight: 700; font-family: sans-serif; white-space: nowrap; }
        .skip { color: #d97706; font-weight: 700; font-family: sans-serif; white-space: nowrap; }
        .err  { color: #dc2626; font-weight: 700; font-family: sans-serif; white-space: nowrap; }
        .banner-ok  { background: #d1fae5; border: 1px solid #059669; border-radius: 8px; padding: 16px 20px; margin-top: 20px; color: #065f46; font-weight: 700; font-size: .95rem; }
        .banner-err { background: #fee2e2; border: 1px solid #dc2626; border-radius: 8px; padding: 16px 20px; margin-top: 20px; color: #991b1b; font-weight: 700; font-size: .95rem; }
        .banner-ok a, .banner-err a { color: inherit; text-decoration: underline; }
        .back { display: inline-block; margin-top: 20px; color: #6b7280; font-size: .85rem; text-decoration: none; }
        .back:hover { color: #1a1a1a; }
    </style>
</head>
<body>
<div class="wrap">
    <h1><i class="fas fa-key"></i> Permissions &amp; Schema Migration</h1>
    <p class="sub">Applies the <code>permissions_migration.sql</code> file. Safe to re-run — existing tables and columns are skipped.</p>

    <div class="card">
        <?php if (!$ran): ?>
        <div class="info">
            <strong><i class="fas fa-info-circle"></i> What this migration does:</strong>
            <ul>
                <li>Adds <code>dispatcher</code> to <code>users.role</code> ENUM (if not already there)</li>
                <li>Adds <code>dispatcher</code> to <code>booking_status_history.changed_by_role</code> ENUM</li>
                <li>Reconciles <code>dispatch_notes</code> schema — ensures both <code>admin_id</code> and <code>dispatcher_id</code> columns exist</li>
                <li>Adds <code>tracking_token</code> and <code>tracking_enabled</code> columns to <code>bookings</code></li>
                <li>Creates <code>permissions</code> table with 21 named permissions</li>
                <li>Creates <code>role_permissions</code> table and seeds admin + dispatcher access</li>
            </ul>
        </div>
        <form method="POST" action="">
            <button type="submit" name="run" value="1" class="btn">
                <i class="fas fa-play"></i> Run Migration Now
            </button>
        </form>

        <?php else: ?>
        <table>
            <thead>
                <tr><th>#</th><th>Statement</th><th>Result</th><th>Message</th></tr>
            </thead>
            <tbody>
            <?php foreach ($results as $i => $r): ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td><?= htmlspecialchars($r['sql']) ?></td>
                <td class="<?= $r['ok'] ? ($r['msg'] === 'OK' ? 'ok' : 'skip') : 'err' ?>">
                    <?= $r['ok'] ? ($r['msg'] === 'OK' ? '✓ OK' : '⚠ Skipped') : '✗ Error' ?>
                </td>
                <td style="font-family:sans-serif; font-size:.82rem"><?= htmlspecialchars($r['msg']) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($allOk): ?>
        <div class="banner-ok">
            <i class="fas fa-check-circle"></i> Migration complete! All permissions and schema changes are live.
            &nbsp;&rarr;&nbsp; <a href="/admin/">Back to Admin Dashboard</a>
        </div>
        <?php else: ?>
        <div class="banner-err">
            <i class="fas fa-times-circle"></i> One or more errors occurred. Review the table above, then retry.
        </div>
        <form method="POST" action="" style="margin-top:16px">
            <button type="submit" name="run" value="1" class="btn"><i class="fas fa-redo"></i> Retry</button>
        </form>
        <?php endif; ?>
        <?php endif; ?>
    </div>

    <a href="/admin/" class="back"><i class="fas fa-arrow-left"></i> Back to Admin Dashboard</a>
</div>
</body>
</html>
