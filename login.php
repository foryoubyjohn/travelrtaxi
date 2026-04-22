<?php
$pageTitle = 'Login';
require_once 'includes/header.php';

if (isLoggedIn()) {
    header('Location: /account.php');
    exit;
}

$error = '';
$tab = sanitize($_GET['tab'] ?? 'login');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formAction = sanitize($_POST['form_action'] ?? '');

    if ($formAction === 'login') {
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $result = loginUser($email, $password);
        if ($result['success']) {
            if ($result['user']['role'] === 'admin') {
                header('Location: /admin/');
            } else {
                header('Location: /account.php');
            }
            exit;
        }
        $error = $result['error'];
        $tab = 'login';
    }

    if ($formAction === 'register') {
        $firstName = sanitize($_POST['first_name'] ?? '');
        $lastName = sanitize($_POST['last_name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($firstName) || empty($email) || empty($password)) {
            $error = 'Please fill in all required fields.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } else {
            $result = registerUser($firstName, $lastName, $email, $phone, $password);
            if ($result['success']) {
                loginUser($email, $password);
                header('Location: /account.php');
                exit;
            }
            $error = $result['error'];
        }
        $tab = 'register';
    }
}
?>

<section class="page-hero">
    <div class="container">
        <h1>My Account</h1>
        <p>Login or create an account to manage your bookings</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="auth-container">
            <div class="auth-tabs">
                <button class="auth-tab <?php echo $tab === 'login' ? 'active' : ''; ?>" onclick="showTab('login')">Login</button>
                <button class="auth-tab <?php echo $tab === 'register' ? 'active' : ''; ?>" onclick="showTab('register')">Register</button>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Login Form -->
            <div class="auth-form" id="loginForm" style="display:<?php echo $tab === 'login' ? 'block' : 'none'; ?>">
                <form method="POST">
                    <input type="hidden" name="form_action" value="login">
                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Email</label>
                        <input type="email" name="email" required placeholder="your@email.com">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Password</label>
                        <input type="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block btn-lg">Login</button>
                </form>
            </div>

            <!-- Register Form -->
            <div class="auth-form" id="registerForm" style="display:<?php echo $tab === 'register' ? 'block' : 'none'; ?>">
                <form method="POST">
                    <input type="hidden" name="form_action" value="register">
                    <div class="form-row">
                        <div class="form-group half">
                            <label>First Name *</label>
                            <input type="text" name="first_name" required>
                        </div>
                        <div class="form-group half">
                            <label>Last Name</label>
                            <input type="text" name="last_name">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="tel" name="phone">
                    </div>
                    <div class="form-group">
                        <label>Password * (min 6 characters)</label>
                        <input type="password" name="password" required minlength="6">
                    </div>
                    <button type="submit" class="btn btn-primary btn-block btn-lg">Create Account</button>
                </form>
            </div>

            <p class="auth-note">Account is optional. You can <a href="/booking.php">book as a guest</a> anytime.</p>
        </div>
    </div>
</section>

<script>
function showTab(tab) {
    document.getElementById('loginForm').style.display = tab === 'login' ? 'block' : 'none';
    document.getElementById('registerForm').style.display = tab === 'register' ? 'block' : 'none';
    document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
    event.target.classList.add('active');
}
</script>

<?php require_once 'includes/footer.php'; ?>
