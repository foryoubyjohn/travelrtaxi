<?php
$pageTitle = 'Admin Login';
require_once 'includes/admin-header.php';

if (isAdmin()) {
    header('Location: /admin/');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $result = loginUser($email, $password);
    if ($result['success'] && $result['user']['role'] === 'admin') {
        header('Location: /admin/');
        exit;
    } else {
        $error = 'Invalid credentials or insufficient permissions.';
        if ($result['success']) {
            logoutUser();
            session_start();
        }
    }
}
?>
<div class="admin-login-page">
    <div class="admin-login-card">
        <img src="/assets/images/logo.jpeg" alt="Logo" class="login-logo">
        <h1>Admin Login</h1>
        <p>Sign in to manage Travelr Taxi</p>
        <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST" class="login-form">
            <div class="form-group">
                <label><i class="fas fa-envelope"></i> Email</label>
                <input type="email" name="email" required placeholder="admin@travelrtaxi.com" autofocus>
            </div>
            <div class="form-group">
                <label><i class="fas fa-lock"></i> Password</label>
                <input type="password" name="password" required placeholder="Enter password">
            </div>
            <button type="submit" class="btn btn-primary btn-block btn-lg">Sign In</button>
        </form>
        <p class="login-hint">Default: admin@travelrtaxi.com / admin123</p>
    </div>
</div>
</body>
</html>
