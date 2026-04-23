<?php
/**
 * Driver Panel - Logout
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
logoutUser();
header('Location: /driver/login.php');
exit;
