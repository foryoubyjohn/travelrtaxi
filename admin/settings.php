<?php
require_once 'includes/admin-header.php';

$pageTitle = 'Site Settings';

// Handle site settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_action'])) {

    if ($_POST['form_action'] === 'site_settings') {
        $fields = ['site_name','tagline','phone','whatsapp','email','currency','currency_symbol','address','facebook','instagram','twitter'];
        foreach ($fields as $key) {
            $value = sanitize($_POST[$key] ?? '');
            dbExecute("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?", [$key, $value, $value]);
        }
        redirectWith('/admin/settings.php', 'success', 'Settings updated successfully.');
    }

    if ($_POST['form_action'] === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword     = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $user = dbFetchOne("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);

        if (!password_verify($currentPassword, $user['password_hash'])) {
            redirectWith('/admin/settings.php', 'error', 'Current password is incorrect.');
        } elseif (strlen($newPassword) < 8) {
            redirectWith('/admin/settings.php', 'error', 'New password must be at least 8 characters.');
        } elseif ($newPassword !== $confirmPassword) {
            redirectWith('/admin/settings.php', 'error', 'New passwords do not match.');
        } else {
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            dbExecute("UPDATE users SET password_hash = ? WHERE id = ?", [$hash, $_SESSION['user_id']]);
            redirectWith('/admin/settings.php', 'success', 'Password changed successfully.');
        }
    }
}

$settings = [];
$rows = dbFetchAll("SELECT * FROM settings");
foreach ($rows as $row) { $settings[$row['setting_key']] = $row['setting_value']; }

$currentUser = dbFetchOne("SELECT first_name, last_name, email FROM users WHERE id = ?", [$_SESSION['user_id']]);
?>

<h1 class="page-title">Site Settings</h1>

<!-- Site Settings -->
<div class="admin-card">
    <div class="card-header"><h2>General Settings</h2></div>
    <form method="POST">
        <input type="hidden" name="form_action" value="site_settings">

        <h3>General</h3>
        <div class="form-row">
            <div class="form-group half">
                <label>Site Name</label>
                <input type="text" name="site_name" value="<?php echo sanitize($settings['site_name'] ?? ''); ?>">
            </div>
            <div class="form-group half">
                <label>Tagline</label>
                <input type="text" name="tagline" value="<?php echo sanitize($settings['tagline'] ?? ''); ?>">
            </div>
        </div>

        <h3>Contact</h3>
        <div class="form-row">
            <div class="form-group half">
                <label>Phone</label>
                <input type="text" name="phone" value="<?php echo sanitize($settings['phone'] ?? ''); ?>">
            </div>
            <div class="form-group half">
                <label>WhatsApp</label>
                <input type="text" name="whatsapp" value="<?php echo sanitize($settings['whatsapp'] ?? ''); ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group half">
                <label>Email</label>
                <input type="email" name="email" value="<?php echo sanitize($settings['email'] ?? ''); ?>">
            </div>
            <div class="form-group half">
                <label>Address</label>
                <input type="text" name="address" value="<?php echo sanitize($settings['address'] ?? ''); ?>">
            </div>
        </div>

        <h3>Currency</h3>
        <div class="form-row">
            <div class="form-group half">
                <label>Currency Code</label>
                <input type="text" name="currency" value="<?php echo sanitize($settings['currency'] ?? 'JMD'); ?>">
            </div>
            <div class="form-group half">
                <label>Currency Symbol</label>
                <input type="text" name="currency_symbol" value="<?php echo sanitize($settings['currency_symbol'] ?? '$'); ?>">
            </div>
        </div>

        <h3>Social Media</h3>
        <div class="form-row">
            <div class="form-group third">
                <label>Facebook URL</label>
                <input type="url" name="facebook" value="<?php echo sanitize($settings['facebook'] ?? ''); ?>">
            </div>
            <div class="form-group third">
                <label>Instagram URL</label>
                <input type="url" name="instagram" value="<?php echo sanitize($settings['instagram'] ?? ''); ?>">
            </div>
            <div class="form-group third">
                <label>Twitter URL</label>
                <input type="url" name="twitter" value="<?php echo sanitize($settings['twitter'] ?? ''); ?>">
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-lg">Save Settings</button>
    </form>
</div>

<!-- Change Password -->
<div class="admin-card" style="margin-top:2rem;">
    <div class="card-header">
        <h2><i class="fas fa-lock"></i> Change Password</h2>
    </div>
    <p style="margin-bottom:1.5rem;color:#666;">
        Logged in as <strong><?php echo sanitize($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?></strong>
        (<?php echo sanitize($currentUser['email']); ?>)
    </p>
    <form method="POST" style="max-width:480px;">
        <input type="hidden" name="form_action" value="change_password">
        <div class="form-group">
            <label><i class="fas fa-lock"></i> Current Password</label>
            <input type="password" name="current_password" required placeholder="Enter your current password">
        </div>
        <div class="form-group">
            <label><i class="fas fa-key"></i> New Password</label>
            <input type="password" name="new_password" required placeholder="Min. 8 characters" minlength="8">
        </div>
        <div class="form-group">
            <label><i class="fas fa-check"></i> Confirm New Password</label>
            <input type="password" name="confirm_password" required placeholder="Repeat new password">
        </div>
        <button type="submit" class="btn btn-primary">Update Password</button>
    </form>
</div>

<?php require_once 'includes/admin-footer.php'; ?>
