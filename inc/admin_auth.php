<?php
// Admin authentication and authorization helpers
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/bootstrap.php';

function cybercore_admin_roles(): array
{
    return ['Gestor', 'Suporte ao Cliente', 'Suporte Financeiro', 'Suporte Técnico'];
}

function cybercore_is_admin(): bool
{
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
        return false;
    }
    return in_array($_SESSION['user_role'], cybercore_admin_roles(), true);
}

function cybercore_require_admin(): void
{
    if (!cybercore_is_admin()) {
        header('Location: /client/login.php');
        exit;
    }
}

function cybercore_admin_can(string $action): bool
{
    $role = $_SESSION['user_role'] ?? '';
    
    // Gestor has full access
    if ($role === 'Gestor') {
        return true;
    }
    
    // Define permissions per role
    $permissions = [
        'Suporte ao Cliente' => ['view_tickets', 'reply_tickets', 'view_users'],
        'Suporte Financeiro' => ['view_invoices', 'edit_invoices', 'view_users', 'approve_fiscal'],
        'Suporte Técnico' => ['view_services', 'edit_services', 'view_tickets', 'reply_tickets'],
    ];
    
    return in_array($action, $permissions[$role] ?? [], true);
}

function cybercore_admin_get_current_user(): ?array
{
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    $pdo = cybercore_pdo();
    $stmt = $pdo->prepare('SELECT id, identifier, email, first_name, last_name, role FROM users WHERE id = :id');
    $stmt->execute(['id' => $_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}
