<?php
$pageTitle = 'Site Settings';
require_once 'includes/admin-header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = ['site_name','tagline','phone','whatsapp','email','currency','currency_symbol','address','facebook','instagram','twitter'];
    foreach ($fields as $key) {
        $value = sanitize($_POST[$key] ?? '');
        dbExecute("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?", [$key, $value, $value]);
    }
    redirectWith('/admin/settings.php', 'success', 'Settings updated successfully.');
}

$settings = [];
$rows = dbFetchAll("SELECT * FROM settings");
foreach ($rows as $row) { $settings[$row['setting_key']] = $row['setting_value']; }
?>

<h1 class="page-title">Site Settings</h1>

<div class="admin-card">
    <form method="POST">
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

<?php require_once 'includes/admin-footer.php'; ?>
