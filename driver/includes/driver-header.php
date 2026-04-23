<?php
/**
 * Driver Panel - Header Include
 * Mobile-first app-like shell
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

// Gate: require driver login (except login page)
$currentPage = basename($_SERVER['PHP_SELF']);
if ($currentPage !== 'login.php') {
    if (!isLoggedIn() || !isDriver()) {
        header('Location: /driver/login.php');
        exit;
    }
    // Load driver record
    $driverUser = getCurrentUser();
    $driverRecord = dbFetchOne(
        "SELECT d.*, v.name AS vehicle_name, v.plate_number, v.type AS vehicle_type
         FROM drivers d
         LEFT JOIN vehicles v ON d.vehicle_id = v.id
         WHERE d.user_id = ?",
        [$driverUser['id']]
    );
    if (!$driverRecord) {
        logoutUser();
        header('Location: /driver/login.php');
        exit;
    }
}

$flash = getFlash();
$pageTitle = $pageTitle ?? 'Driver Panel';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#1a1a1a">
    <title><?= sanitize($pageTitle) ?> | Travelr Driver</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800&family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/driver.css">
</head>
<body class="driver-body">

<!-- Top App Bar -->
<header class="driver-topbar">
    <div class="topbar-left">
        <img src="/assets/images/logo.jpeg" alt="Travelr" class="driver-logo">
        <span class="topbar-title"><?= sanitize($pageTitle) ?></span>
    </div>
    <?php if ($currentPage !== 'login.php'): ?>
    <div class="topbar-right">
        <span class="driver-name-badge"><?= sanitize($driverUser['first_name']) ?></span>
    </div>
    <?php endif; ?>
</header>

<!-- Flash Messages -->
<?php if ($flash): ?>
<div class="driver-alert driver-alert-<?= $flash['type'] ?>" id="driverFlash">
    <?= sanitize($flash['message']) ?>
    <button class="alert-dismiss" onclick="this.parentElement.remove()">&times;</button>
</div>
<?php endif; ?>

<!-- Main Content Area -->
<main class="driver-main">
