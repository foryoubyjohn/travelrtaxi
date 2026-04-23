<?php
/**
 * Authentication & Permissions Functions
 */
require_once __DIR__ . '/db.php';

/**
 * Static fallback permissions map used when the role_permissions DB table
 * has not been created yet. Wildcard '*' means all permissions.
 */
const ROLE_PERMISSIONS = [
    'admin'      => ['*'],
    'dispatcher' => [
        'dispatch',
        'view_bookings', 'edit_bookings',
        'view_drivers',
        'view_fleet',
    ],
    'driver'     => [],
    'customer'   => [],
];

/**
 * Register a new user
 */
function registerUser($firstName, $lastName, $email, $phone, $password, $role = 'customer') {
    $hash = password_hash($password, PASSWORD_BCRYPT);
    try {
        $id = dbInsert(
            "INSERT INTO users (first_name, last_name, email, phone, password_hash, role) VALUES (?, ?, ?, ?, ?, ?)",
            [$firstName, $lastName, $email, $phone, $hash, $role]
        );
        return ['success' => true, 'user_id' => $id];
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            return ['success' => false, 'error' => 'Email already exists.'];
        }
        return ['success' => false, 'error' => 'Registration failed.'];
    }
}

/**
 * Login user
 */
function loginUser($email, $password) {
    $user = dbFetchOne("SELECT * FROM users WHERE email = ? AND is_active = 1", [$email]);
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id']          = $user['id'];
        $_SESSION['user_role']        = $user['role'];
        $_SESSION['user_name']        = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['user_email']       = $user['email'];
        unset($_SESSION['user_permissions']); // force permissions reload on next check
        return ['success' => true, 'user' => $user];
    }
    return ['success' => false, 'error' => 'Invalid email or password.'];
}

/**
 * Logout user
 */
function logoutUser() {
    session_unset();
    session_destroy();
}

/**
 * Load the current user's permissions (from session cache, DB, or static fallback).
 */
function loadUserPermissions() {
    if (!isLoggedIn()) return [];
    if (isset($_SESSION['user_permissions'])) return $_SESSION['user_permissions'];

    $role = $_SESSION['user_role'];

    try {
        $rows = dbFetchAll(
            "SELECT permission_name FROM role_permissions WHERE role = ?",
            [$role]
        );
        if (!empty($rows)) {
            $_SESSION['user_permissions'] = array_column($rows, 'permission_name');
            return $_SESSION['user_permissions'];
        }
    } catch (Exception $e) {
        // DB table not yet created — fall through to static map
    }

    $_SESSION['user_permissions'] = ROLE_PERMISSIONS[$role] ?? [];
    return $_SESSION['user_permissions'];
}

/**
 * Check whether the current user holds a named permission.
 */
function hasPermission($permission) {
    if (!isLoggedIn()) return false;
    $perms = loadUserPermissions();
    return in_array('*', $perms) || in_array($permission, $perms);
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Check if user is driver
 */
function isDriver() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'driver';
}

/**
 * Check if user is a dispatcher
 */
function isDispatcher() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'dispatcher';
}

/**
 * Check if user is a customer
 */
function isCustomer() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'customer';
}

/**
 * Check if user has dispatch access (admin or dispatcher)
 */
function canDispatch() {
    return isAdmin() || isDispatcher();
}

/**
 * Get current user
 */
function getCurrentUser() {
    if (!isLoggedIn()) return null;
    return dbFetchOne("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
}

/**
 * Require driver access
 */
function requireDriver() {
    if (!isLoggedIn() || !isDriver()) {
        header('Location: /driver/login.php');
        exit;
    }
}

/**
 * Require admin access.
 * Dispatchers (and other logged-in non-admins) are redirected to their
 * own allowed area rather than the login page to avoid confusing loops.
 */
function requireAdmin() {
    if (!isLoggedIn()) {
        header('Location: /admin/login.php');
        exit;
    }
    if (!isAdmin()) {
        if (isDispatcher()) {
            header('Location: /admin/dispatch.php');
        } elseif (isDriver()) {
            header('Location: /driver/');
        } else {
            header('Location: /login.php');
        }
        exit;
    }
}

/**
 * Require dispatch access (admin or dispatcher role)
 */
function requireDispatchAccess() {
    if (!isLoggedIn() || !canDispatch()) {
        header('Location: /admin/login.php');
        exit;
    }
}

/**
 * Require customer access — redirects other roles to their own area.
 */
function requireCustomer() {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
    if (!isCustomer()) {
        if (isAdmin())      { header('Location: /admin/');              exit; }
        if (isDispatcher()) { header('Location: /admin/dispatch.php');  exit; }
        if (isDriver())     { header('Location: /driver/');             exit; }
        header('Location: /login.php');
        exit;
    }
}

/**
 * Require login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}

/**
 * Generate CSRF token
 */
function generateCSRF() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRF($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
