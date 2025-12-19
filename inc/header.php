<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
?>
<!doctype html>
<html lang="pt">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?php echo SITE_NAME; ?></title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="app">
  <aside class="sidebar">
    <div class="brand">CyberCore</div>
    <?php $cu = currentUser(); if($cu): ?>
      <div class="user-info" style="margin-bottom:12px;font-size:14px"><?php echo htmlspecialchars($cu['first_name'].' '.$cu['last_name']); ?> <div class="small">Role: <?php echo htmlspecialchars($cu['role']); ?></div></div>
    <?php endif; ?>
    <nav>
      <a href="dashboard.php">Página de Início</a>
      <?php if(!$cu || in_array($cu['role'], ['Cliente','Suporte ao Cliente','Suporte Técnica','Gestor'])): ?>
        <a href="support.php">Suporte</a>
      <?php endif; ?>
      <?php if(!$cu || in_array($cu['role'], ['Cliente','Gestor','Suporte ao Cliente','Suporte Técnica'])): ?>
        <a href="domains.php">Domínios</a>
      <?php endif; ?>
      <?php if(!$cu || in_array($cu['role'], ['Cliente','Gestor'])): ?>
      <?php endif; ?>
        <?php if(!$cu || in_array($cu['role'], ['Cliente','Suporte Financeira','Gestor'])): ?>
          <a href="finance.php">Financeiro</a>
        <?php endif; ?>
        <?php if(!$cu || in_array($cu['role'], ['Cliente','Suporte Financeira','Gestor'])): ?>
          <a href="logs.php">Logs</a>
        <?php endif; ?>
      <?php if($cu && $cu['role'] === 'Gestor'): ?>
        <a href="manage_users.php">Gestão de Utilizadores</a>
      <?php endif; ?>
    </nav>
    <div class="logout"><a href="logout.php">Logout</a></div>
  </aside>
  <main class="content">
