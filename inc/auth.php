<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// Session cookie hardening
if (session_status() === PHP_SESSION_NONE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
    } else {
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_secure', $secure ? '1' : '0');
        ini_set('session.cookie_samesite', 'Strict');
    }
    session_start();
}

function currentUser()
{
    if (empty($_SESSION['user_id'])) return null;
    static $user = null;
    if ($user === null) {
        $pdo = getDB();
        $stmt = $pdo->prepare('SELECT id,first_name,last_name,email,role FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
    }
    return $user;
}

function requireLogin()
{
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function requireRole($roles)
{
    $user = currentUser();
    if (!$user) {
        header('Location: login.php');
        exit;
    }
    $allowed = is_array($roles) ? $roles : [$roles];
    if (!in_array($user['role'], $allowed)) {
        http_response_code(403);
        echo 'Acesso negado.';
        exit;
    }
}

function isRole($role)
{
    $u = currentUser();
    return $u && $u['role'] === $role;
}

// Role-based access helper
// Usage: checkRole(['Cliente','Gestor']);
// - Redirects to login if not authenticated
// - Redirects to no_access.php if role not allowed
// - Returns true when access granted
function checkRole($allowedRoles)
{
    if (empty($_SESSION['user_id'])) {
        header('Location: /login.php');
        exit;
    }
    $user = currentUser();
    if (!$user) {
        header('Location: /login.php');
        exit;
    }
    $allowed = is_array($allowedRoles) ? $allowedRoles : [$allowedRoles];
    if (!in_array($user['role'], $allowed)) {
        header('Location: /no_access.php');
        exit;
    }
    return true;
}
