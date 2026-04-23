<?php
/**
 * Migration Runner — Step 3: Real-Time & GPS Tracking
 * Standalone page, no admin layout dependency.
 */
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: /admin/login.php');
    exit;
}

$sqlFile = dirname(__DIR__) . '/realtime-migration.sql';
$results = [];
$allOk   = true;
$ran     = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run'])) {
    $ran = true;
    if (!file_exists($sqlFile)) {
        $results[] = ['sql' => 'Load file', 'ok' => false, 'msg' => 'realtime-migration.sql not found in project root'];
        $allOk = false;
    } else {
        $raw        = file_get_contents($sqlFile);
        $statements = array_filter(
            array_map('trim', preg_split('/;[\s]*\n/', $raw)),
            fn($s) => $s !== '' && !preg_match('/^--/', $s) && !preg_match('/^USE\s/i', $s)
        );
        foreach ($statements as $sql) {
            if (trim($sql) === '') continue;
            try {
                dbExecute($sql, []);
                $results[] = ['sql' => mb_strimwidth(trim($sql), 0, 90, '...'), 'ok' => true, 'msg' => 'OK'];
            } catch (Throwable $e) {
                $msg      = $e->getMessage();
                $harmless = str_contains($msg, 'already exists') || str_contains($msg, 'Duplicate column');
                $results[] = ['sql' => mb_strimwidth(trim($sql), 0, 90, '...'), 'ok' => $harmless, 'msg' => $harmless ? 'Already exists (skipped)' : $msg];
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
    <title>Step 3 — Real-Time Migration</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; background: #f3f4f6; color: #1a1a1a; padding: 40px 20px; }
        .wrap { max-width: 820px; margin: 0 auto; }
        h1 { font-size: 1.5rem; margin-bottom: 6px; }
        .sub { color: #6b7280; font-size: .9rem; margin-bottom: 24px; }
        .steps { display: flex; gap: 8px; margin-bottom: 28px; flex-wrap: wrap; }
        .step { padding: 6px 16px; border-radius: 20px; font-size: .8rem; font-weight: 700; }
        .step-active { background: #1a1a1a; color: #FFD400; }
        .step-done   { background: #d1fae5; color: #065f46; }
        .step-next   { background: #e5e7eb; color: #6b7280; }
        .card { background: #fff; border-radius: 12px; padding: 28px; box-shadow: 0 1px 4px rgba(0,0,0,.08); margin-bottom: 20px; }
        .warn { background: #fef3c7; border: 1px solid #f59e0b; border-radius: 8px; padding: 14px 18px; margin-bottom: 20px; font-size: .88rem; line-height: 1.6; }
        .warn ul { margin: 6px 0 0 20px; }
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 12px 28px; background: #1a1a1a; color: #FFD400; border: none; border-radius: 8px; font-size: 1rem; font-weight: 700; cursor: pointer; }
        .btn:hover { background: #333; }
        table { width: 100%; border-collapse: collapse; font-size: .84rem; }
        th { background: #1e293b; color: #fff; padding: 8px 12px; text-align: left; }
        td { padding: 7px 12px; border-bottom: 1px solid #e5e7eb; font-family: monospace; word-break: break-all; }
        .ok   { color: #059669; font-weight: 700; font-family: sans-serif; }
        .skip { color: #d97706; font-weight: 700; font-family: sans-serif; }
        .err  { color: #dc2626; font-weight: 700; font-family: sans-serif; }
        .banner-ok  { background: #d1fae5; border: 1px solid #059669; border-radius: 8px; padding: 16px 20px; margin-top: 20px; color: #065f46; font-weight: 700; font-size: .95rem; }
        .banner-err { background: #fee2e2; border: 1px solid #dc2626; border-radius: 8px; padding: 16px 20px; margin-top: 20px; color: #991b1b; font-weight: 700; font-size: .95rem; }
        .banner-ok a, .banner-err a { color: inherit; text-decoration: underline; margin: 0 4px; }
        .back { display: inline-block; margin-top: 16px; color: #6b7280; font-size: .85rem; }
    </style>
</head>
<body>
<div class="wrap">
    <h1><i class="fas fa-satellite-dish"></i> Database Migration Runner</h1>
    <p class="sub">Run these three steps in order to set up the Driver Panel and Real-Time features.</p>

    <div class="steps">
        <span class="step step-done">Step 1 · Driver Panel ✓</span>
        <span class="step step-done">Step 2 · Dispatch Console ✓</span>
        <span class="step step-active">Step 3 · Real-Time &amp; GPS</span>
    </div>

    <div class="card">
        <?php if (!$ran): ?>
        <div class="warn">
            <strong><i class="fas fa-exclamation-triangle"></i> What this adds:</strong>
            <ul>
                <li>Creates <code>driver_locations</code> table (GPS history)</li>
                <li>Creates <code>sync_checkpoints</code> table (change detection)</li>
                <li>Adds <code>location_sharing</code>, <code>last_latitude</code>, <code>last_longitude</code>, <code>last_location_at</code> to <code>drivers</code></li>
                <li>Adds <code>tracking_token</code>, <code>tracking_enabled</code> to <code>bookings</code></li>
                <li>Backfills tracking tokens for all existing bookings</li>
            </ul>
            Already-existing columns and tables are safely skipped.
        </div>
        <form method="POST" action="">
            <button type="submit" name="run" value="1" class="btn">
                <i class="fas fa-play"></i> Run Step 3 Now
            </button>
        </form>

        <?php else: ?>
        <table>
            <thead><tr><th>#</th><th>Statement</th><th>Result</th><th>Message</th></tr></thead>
            <tbody>
            <?php foreach ($results as $i => $r): ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td><?= htmlspecialchars($r['sql']) ?></td>
                <td class="<?= $r['ok'] ? ($r['msg'] === 'OK' ? 'ok' : 'skip') : 'err' ?>">
                    <?= $r['ok'] ? ($r['msg'] === 'OK' ? '✓ OK' : '⚠ Skipped') : '✗ Error' ?>
                </td>
                <td style="font-family:sans-serif"><?= htmlspecialchars($r['msg']) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($allOk): ?>
        <div class="banner-ok">
            <i class="fas fa-check-circle"></i> All 3 migrations complete! &nbsp;
            <a href="/driver/login.php">Test Driver Login &rarr;</a> &nbsp;|&nbsp;
            <a href="/admin/dispatch.php">Open Dispatch Center &rarr;</a>
        </div>
        <?php else: ?>
        <div class="banner-err">
            <i class="fas fa-times-circle"></i> One or more errors. Fix the issues above and retry.
        </div>
        <form method="POST" action="" style="margin-top:16px">
            <button type="submit" name="run" value="1" class="btn"><i class="fas fa-redo"></i> Retry</button>
        </form>
        <?php endif; ?>
        <?php endif; ?>
    </div>

    <a href="/admin/" class="back"><i class="fas fa-arrow-left"></i> Back to Admin</a>
</div>
</body>
</html>
