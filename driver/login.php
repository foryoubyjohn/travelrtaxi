<?php
/**
 * Driver Panel - Login Page
 */
$pageTitle = 'Driver Login';
require_once __DIR__ . '/includes/driver-header.php';

// Already logged in as driver? Go to dashboard
if (isLoggedIn() && isDriver()) {
    header('Location: /driver/');
    exit;
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter your email and password.';
    } else {
        $result = loginUser($email, $password);
        if ($result['success']) {
            if ($result['user']['role'] === 'driver') {
                header('Location: /driver/');
                exit;
            } else {
                logoutUser();
                session_start();
                $error = 'This login is for drivers only.';
            }
        } else {
            $error = $result['error'];
        }
    }
}
?>

<div class="driver-login-page">
    <div class="driver-login-card">
        <img src="/assets/images/logo.jpeg" alt="Travelr Taxi" class="login-logo">
        <h1>Driver Login</h1>
        <p class="login-subtitle">Sign in to your driver account</p>

        <?php if (!empty($error)): ?>
        <div class="driver-alert driver-alert-error"><?= sanitize($error) ?></div>
        <?php endif; ?>

        <form method="POST" class="login-form">
            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> Email</label>
                <input type="email" id="email" name="email" placeholder="driver@travelrtaxi.com"
                       value="<?= sanitize($email ?? '') ?>" required autofocus>
            </div>
            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>
            <button type="submit" class="btn-driver-primary btn-block">
                <i class="fas fa-sign-in-alt"></i> SIGN IN
            </button>
        </form>

        <p class="login-hint">
            <i class="fas fa-info-circle"></i>
            Contact admin if you need access or forgot your password.
        </p>
        <a href="/" class="login-back"><i class="fas fa-arrow-left"></i> Back to main site</a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/driver-footer.php'; ?>
