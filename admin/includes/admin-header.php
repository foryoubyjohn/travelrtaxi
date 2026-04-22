<?php
require_once dirname(__DIR__, 2) . '/includes/config.php';
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/includes/helpers.php';

// Require admin access (except login page)
$adminPage = basename($_SERVER['PHP_SELF'], '.php');
if ($adminPage !== 'login') {
    requireAdmin();
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' | ' : ''; ?>Admin - <?php echo SITE_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&family=Open+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="admin-body">

<?php if ($adminPage !== 'login'): ?>
<!-- Admin Sidebar -->
<aside class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-header">
        <img src="/assets/images/logo.jpeg" alt="Logo" class="sidebar-logo">
        <h3>Admin Panel</h3>
    </div>
    <nav class="sidebar-nav">
        <a href="/admin/" class="<?php echo $adminPage === 'index' ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="/admin/bookings.php" class="<?php echo $adminPage === 'bookings' ? 'active' : ''; ?>"><i class="fas fa-calendar-check"></i> Bookings</a>
        <a href="/admin/drivers.php" class="<?php echo $adminPage === 'drivers' ? 'active' : ''; ?>"><i class="fas fa-id-card"></i> Drivers</a>
        <a href="/admin/fleet.php" class="<?php echo $adminPage === 'fleet' ? 'active' : ''; ?>"><i class="fas fa-car"></i> Fleet</a>
        <a href="/admin/pricing.php" class="<?php echo $adminPage === 'pricing' ? 'active' : ''; ?>"><i class="fas fa-dollar-sign"></i> Pricing</a>
        <a href="/admin/routes.php" class="<?php echo $adminPage === 'routes' ? 'active' : ''; ?>"><i class="fas fa-route"></i> Routes</a>
        <a href="/admin/customers.php" class="<?php echo $adminPage === 'customers' ? 'active' : ''; ?>"><i class="fas fa-users"></i> Customers</a>
        <a href="/admin/testimonials.php" class="<?php echo $adminPage === 'testimonials' ? 'active' : ''; ?>"><i class="fas fa-star"></i> Reviews</a>
        <a href="/admin/messages.php" class="<?php echo $adminPage === 'messages' ? 'active' : ''; ?>"><i class="fas fa-envelope"></i> Messages</a>
        <a href="/admin/settings.php" class="<?php echo $adminPage === 'settings' ? 'active' : ''; ?>"><i class="fas fa-cog"></i> Settings</a>
        <div class="sidebar-divider"></div>
        <a href="/" target="_blank"><i class="fas fa-external-link-alt"></i> View Site</a>
        <a href="/admin/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>
</aside>

<!-- Admin Main -->
<main class="admin-main">
    <!-- Admin Top Bar -->
    <header class="admin-topbar">
        <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
        <div class="topbar-right">
            <span class="admin-user"><i class="fas fa-user-shield"></i> <?php echo sanitize($_SESSION['user_name']); ?></span>
        </div>
    </header>

    <div class="admin-content">
        <?php if ($flash): ?>
        <div class="alert alert-<?php echo $flash['type']; ?>">
            <?php echo sanitize($flash['message']); ?>
            <button onclick="this.parentElement.remove()" class="alert-close">&times;</button>
        </div>
        <?php endif; ?>
<?php endif; ?>
