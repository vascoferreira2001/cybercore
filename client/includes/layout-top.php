<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../inc/bootstrap.php';

$page_title = $page_title ?? 'Área de Cliente | CyberCore';
$page_description = $page_description ?? 'Gestão de serviços CyberCore';
$extra_css = $extra_css ?? [];
$extra_css[] = '/assets/css/client-dashboard.css';
$active_menu = $active_menu ?? '';
$page_heading = $page_heading ?? 'Visão Geral';
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
<body class="client-body">
<div class="client-app">
  <aside class="client-sidebar">
    <div class="sidebar-brand">
      <a href="/client/dashboard.php" class="logo">Cyber<span>Core</span></a>
      <small>Alojamento & Cloud</small>
    </div>
    <nav class="sidebar-nav">
      <a class="nav-link <?php echo $active_menu === 'dashboard' ? 'active' : ''; ?>" href="/client/dashboard.php">Visão Geral</a>
      <a class="nav-link <?php echo $active_menu === 'services' ? 'active' : ''; ?>" href="/client/services.php">Meus Serviços</a>
      <a class="nav-link <?php echo $active_menu === 'invoices' ? 'active' : ''; ?>" href="/client/invoices.php">Faturas</a>
      <a class="nav-link <?php echo $active_menu === 'tickets' ? 'active' : ''; ?>" href="/client/tickets.php">Tickets de Suporte</a>
      <a class="nav-link <?php echo $active_menu === 'profile' ? 'active' : ''; ?>" href="/client/profile.php">Perfil</a>
    </nav>
    <div class="sidebar-foot">
      <span class="status-dot status-ok"></span>
      <span>Infraestrutura operacional</span>
    </div>
  </aside>

  <div class="client-shell">
    <header class="client-topbar">
      <div>
        <p class="topbar-kicker">Área de Cliente</p>
        <h1><?php echo htmlspecialchars($page_heading); ?></h1>
      </div>
      <div class="topbar-actions">
        <a class="btn btn-ghost" href="/client/tickets.php">Abrir ticket</a>
        <a class="btn btn-primary" href="/client/services.php">Novo serviço</a>
      </div>
    </header>

    <main class="client-main">
