<?php
require_once __DIR__ . '/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

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
        header('Location: /cybercore/login.php');
        exit;
    }
}

function requireRole($roles)
{
    $user = currentUser();
    if (!$user) {
        header('Location: /cybercore/login.php');
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
