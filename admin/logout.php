<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
logoutUser();
header('Location: /admin/login.php');
exit;
