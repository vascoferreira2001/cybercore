<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../inc/bootstrap.php';
require_once __DIR__ . '/../../inc/admin_auth.php';

cybercore_require_admin();

$current_admin = cybercore_admin_get_current_user();
$page_title = $page_title ?? 'Admin Panel | CyberCore';
$page_description = $page_description ?? 'Gestão da plataforma CyberCore';
$extra_css = $extra_css ?? [];
$extra_css[] = '/assets/css/admin-panel.css';
$active_menu = $active_menu ?? '';
$page_heading = $page_heading ?? 'Dashboard';
?>
<!doctype html>
<html lang="pt">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo htmlspecialchars($page_title); ?></title>
  <meta name="description" content="<?php echo htmlspecialchars($page_description); ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/base.css">
  <link rel="stylesheet" href="/assets/css/layout.css">
  <?php foreach ($extra_css as $css): ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($css); ?>">
  <?php endforeach; ?>
</head>
<body class="admin-body">
<div class="admin-app">
  <aside class="admin-sidebar">
    <div class="sidebar-brand">
      <a href="/admin/dashboard.php" class="logo">Cyber<span>Core</span></a>
      <small>Admin Panel</small>
    </div>
    <nav class="sidebar-nav">
      <a class="nav-link <?php echo $active_menu === 'dashboard' ? 'active' : ''; ?>" href="/admin/dashboard.php">📊 Dashboard</a>
      <a class="nav-link <?php echo $active_menu === 'users' ? 'active' : ''; ?>" href="/admin/users.php">👥 Utilizadores</a>
      <a class="nav-link <?php echo $active_menu === 'services' ? 'active' : ''; ?>" href="/admin/services.php">🌐 Serviços</a>
      <a class="nav-link <?php echo $active_menu === 'invoices' ? 'active' : ''; ?>" href="/admin/invoices.php">💰 Faturas</a>
      <a class="nav-link <?php echo $active_menu === 'tickets' ? 'active' : ''; ?>" href="/admin/tickets.php">🎫 Tickets</a>
    </nav>
    <div class="sidebar-foot">
      <span class="admin-role"><?php echo htmlspecialchars($current_admin['role'] ?? 'Admin'); ?></span>
      <a href="/client/logout.php" class="link-small">Sair</a>
    </div>
  </aside>

  <div class="admin-shell">
    <header class="admin-topbar">
      <div>
        <p class="topbar-kicker">Painel de Administração</p>
        <h1><?php echo htmlspecialchars($page_heading); ?></h1>
      </div>
      <div class="topbar-actions">
        <span class="admin-user">👤 <?php echo htmlspecialchars($current_admin['first_name'] ?? 'Admin'); ?></span>
      </div>
    </header>

    <main class="admin-main">
