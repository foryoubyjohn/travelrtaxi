<?php
/**
 * Authentication Functions
 */
require_once __DIR__ . '/db.php';

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
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['user_email'] = $user['email'];
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
 * Get current user
 */
function getCurrentUser() {
    if (!isLoggedIn()) return null;
    return dbFetchOne("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
}

/**
 * Require admin access
 */
function requireAdmin() {
    if (!isLoggedIn() || !isAdmin()) {
        header('Location: /admin/login.php');
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
