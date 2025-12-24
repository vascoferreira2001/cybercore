<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/settings.php';

// Prevenir cache para garantir que os dados sejam sempre atualizados
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Carregar logo e favicon
$pdo = getDB();
$siteLogo = getSetting($pdo, 'site_logo');
$favicon = getSetting($pdo, 'favicon');

// Construir URLs públicas baseadas no servidor
$siteLogoUrl = getAssetUrl($siteLogo);
$faviconUrl = getAssetUrl($favicon);
$siteLogoPath = getAssetPath($siteLogo);
$faviconPath = getAssetPath($favicon);

// Determinar se está em admin para ajustar URLs
$inAdmin = strpos($_SERVER['REQUEST_URI'], '/admin/') !== false;
$baseUrl = $inAdmin ? '/admin' : '';
$adminUrl = $inAdmin ? '' : '/admin';
?>
<!doctype html>
<html lang="pt">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?php echo SITE_NAME; ?></title>
  <link rel="stylesheet" href="<?php echo (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) ? '../css/style.css' : 'css/style.css'; ?>">
  <?php if ($faviconPath && file_exists($faviconPath)): ?>
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($faviconUrl); ?>?v=<?php echo time(); ?>">
  <?php endif; ?>
</head>
<body>
<div class="app">
  <aside class="sidebar">
    <div class="brand">
      <?php if ($siteLogoPath && file_exists($siteLogoPath)): ?>
        <img src="<?php echo htmlspecialchars($siteLogoUrl); ?>?v=<?php echo time(); ?>" alt="Logo" style="max-width:150px;height:auto">
      <?php else: ?>
        CyberCore
      <?php endif; ?>
    </div>
    <?php $cu = currentUser(); if($cu): 
      $cwc = 'CWC#' . str_pad($cu['id'], 4, '0', STR_PAD_LEFT);
    ?>
      <div class="user-info">
        <strong><?php echo htmlspecialchars($cu['first_name'].' '.$cu['last_name']); ?></strong><br>
        <span class="badge"><?php echo htmlspecialchars($cu['role']); ?></span>
      </div>
    <?php endif; ?>
    <nav class="sidebar-nav">
      <!-- Menu para: Gestor, Suporte ao Cliente, Suporte Técnica, Suporte Financeira -->
      <?php if(in_array($cu['role'], ['Gestor','Suporte ao Cliente','Suporte Técnica','Suporte Financeira'])): ?>

        <!-- Painel -->
        <a href="/admin/dashboard.php" class="nav-item">
          <span class="icon">📊</span> Painel
        </a>

        <!-- Clientes -->
        <a href="/admin/customers.php" class="nav-item">
          <span class="icon">👥</span> Clientes
        </a>

        <!-- Tarefas -->
        <a href="/admin/tasks.php" class="nav-item">
          <span class="icon">✓</span> Tarefas
        </a>

        <!-- Serviços (submenu) -->
        <div class="nav-group">
          <a href="#" class="nav-item submenu-toggle" data-submenu="services" onclick="toggleSubmenu(event, 'services')">
            <span class="icon">🛠️</span> Serviços <span class="arrow">▼</span>
          </a>
          <div id="services-submenu" class="submenu">
            <a href="/admin/services.php" class="nav-item-sub">Serviços</a>
            <a href="/admin/payment-warnings.php" class="nav-item-sub">Avisos de Pagamento</a>
            <a href="/admin/payments.php" class="nav-item-sub">Pagamentos</a>
            <a href="/admin/contracts.php" class="nav-item-sub">Contratos</a>
          </div>
        </div>

        <!-- Orçamentos -->
        <a href="/admin/quotes.php" class="nav-item">
          <span class="icon">📋</span> Orçamentos
        </a>

        <!-- Notas (Notas Privadas) -->
        <a href="/admin/notes.php" class="nav-item">
          <span class="icon">📝</span> Notas
        </a>

        <!-- Live Chat - Equipa -->
        <a href="/admin/live-chat.php" class="nav-item">
          <span class="icon">💬</span> Live Chat
        </a>

        <!-- Equipa (submenu) -->
        <div class="nav-group">
          <a href="#" class="nav-item submenu-toggle" data-submenu="team" onclick="toggleSubmenu(event, 'team')">
            <span class="icon">👔</span> Equipa <span class="arrow">▼</span>
          </a>
          <div id="team-submenu" class="submenu">
            <a href="/admin/team.php" class="nav-item-sub">Equipa</a>
            <a href="/admin/schedule.php" class="nav-item-sub">Horário de Trabalho</a>
            <a href="/admin/licenses.php" class="nav-item-sub">Licenças</a>
          </div>
        </div>

        <!-- Suporte ao Cliente (submenu) -->
        <div class="nav-group">
          <a href="#" class="nav-item submenu-toggle" data-submenu="customer-support" onclick="toggleSubmenu(event, 'customer-support')">
            <span class="icon">🎧</span> Suporte ao Cliente <span class="arrow">▼</span>
          </a>
          <div id="customer-support-submenu" class="submenu">
            <a href="/admin/tickets.php" class="nav-item-sub">Tickets</a>
            <a href="/admin/alerts.php" class="nav-item-sub">Avisos</a>
            <a href="/admin/knowledge-base.php" class="nav-item-sub">Bancos de Conhecimento</a>
            <a href="/admin/documents.php" class="nav-item-sub">Documentos</a>
          </div>
        </div>

        <!-- Suporte Financeiro (submenu) - Apenas Gestor e Suporte Financeira -->
        <?php if(in_array($cu['role'], ['Gestor','Suporte Financeira'])): ?>
        <div class="nav-group">
          <a href="#" class="nav-item submenu-toggle" data-submenu="finance" onclick="toggleSubmenu(event, 'finance')">
            <span class="icon">💰</span> Suporte Financeiro <span class="arrow">▼</span>
          </a>
          <div id="finance-submenu" class="submenu">
            <a href="/admin/expenses.php" class="nav-item-sub">Despesas</a>
            <a href="/admin/reports.php" class="nav-item-sub">Relatórios</a>
          </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <!-- Configuração (submenu) - Apenas Gestor -->
        <?php if($cu['role'] === 'Gestor'): ?>
        <div class="nav-group">
          <a href="#" class="nav-item submenu-toggle" data-submenu="settings" onclick="toggleSubmenu(event, 'settings')">
            <span class="icon">⚙️</span> Configuração <span class="arrow">▼</span>
          </a>
          <div id="settings-submenu" class="submenu">
            <a href="/admin/settings.php" class="nav-item-sub">Definições Gerais</a>
            <a href="/admin/manage_users.php" class="nav-item-sub">Gestão de Utilizadores</a>
            <a href="/admin/system-logs.php" class="nav-item-sub">Logs do Sistema</a>
          </div>
        </div>
        <?php endif; ?>

      <!-- Menu para Cliente (visão simples) -->
      <?php else: ?>
        <a href="/dashboard.php" class="nav-item">
          <span class="icon">📊</span> Painel
        </a>
        <a href="/support.php" class="nav-item">
          <span class="icon">🎧</span> Suporte
        </a>
        <a href="/domains.php" class="nav-item">
          <span class="icon">🌐</span> Domínios
        </a>
        <a href="/finance.php" class="nav-item">
          <span class="icon">💰</span> Financeiro
        </a>
        <a href="/logs.php" class="nav-item">
          <span class="icon">📋</span> Logs
        </a>
      <?php endif; ?>
    </nav>
    <div class="logout"><a href="/logout.php">Logout</a></div>
  </aside>
  <main class="content">
