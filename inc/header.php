<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/permissions.php';

// Prevenir cache para garantir que os dados sejam sempre atualizados
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Carregar logo e favicon - versão simplificada sem dependências
$siteLogoUrl = '';
$siteLogoPath = '';
$faviconUrl = '';
$faviconPath = '';

try {
  if (isset($pdo) || function_exists('getDB')) {
    if (!isset($pdo)) $pdo = getDB();
    if ($pdo) {
      $siteLogo = getSetting($pdo, 'site_logo', '');
      $favicon = getSetting($pdo, 'favicon', '');
      
      if ($siteLogo) {
        $siteLogoUrl = (strpos($siteLogo, '/') === 0) ? $siteLogo : '/' . $siteLogo;
        $siteLogoPath = $_SERVER['DOCUMENT_ROOT'] . $siteLogoUrl;
      }
      if ($favicon) {
        $faviconUrl = (strpos($favicon, '/') === 0) ? $favicon : '/' . $favicon;
        $faviconPath = $_SERVER['DOCUMENT_ROOT'] . $faviconUrl;
      }
    }
  }
} catch (Exception $e) {
  // Silenciosamente falha - logo e favicon vazios
}
?>
<!doctype html>
<html lang="pt">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?php echo SITE_NAME; ?></title>
  <link rel="stylesheet" href="<?php echo (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) ? '../assets/css/shared/style.css' : 'assets/css/shared/style.css'; ?>">
  <?php $base = (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) ? '../' : ''; ?>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?php echo $base; ?>assets/css/shared/design-system.css">
  <link rel="stylesheet" href="<?php echo $base; ?>assets/css/auth/auth-modern.css">
  <?php if ($faviconPath && file_exists($faviconPath)): ?>
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($faviconUrl); ?>?v=<?php echo time(); ?>">
  <?php endif; ?>
</head>
<?php $useDashboard = (defined('DASHBOARD_LAYOUT') && DASHBOARD_LAYOUT === true); ?>
<?php $isAdminContext = (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false); ?>
<?php $profileUrl = $isAdminContext ? '../profile.php' : '/profile.php'; ?>
<?php $isProfilePage = (strpos($_SERVER['REQUEST_URI'], 'profile.php') !== false); ?>
<body class="<?php echo $useDashboard ? 'dashboard' : ''; ?>">
<?php if ($useDashboard): ?>
<div class="dashboard-app">
  <aside class="dashboard-sidebar">
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
      <!-- Menu para: Gestor, Suporte ao Cliente, Suporte Técnico, Suporte Financeiro -->
      <?php if($cu && in_array($cu['role'], ['Gestor','Suporte ao Cliente','Suporte Técnico','Suporte Financeiro'])): ?>

        <!-- Painel -->
        <a href="/dashboard.php" class="nav-item">
          <span class="icon">📊</span> Painel
        </a>

        <!-- Perfil -->
        <a href="<?php echo $profileUrl; ?>" class="nav-item<?php echo $isProfilePage ? ' active' : ''; ?>">
          <span class="icon">👤</span> Perfil
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
          <a href="#" class="nav-item submenu-toggle" data-submenu="services">
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
          <a href="#" class="nav-item submenu-toggle" data-submenu="team">
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
          <a href="#" class="nav-item submenu-toggle" data-submenu="customer-support">
            <span class="icon">🎧</span> Suporte ao Cliente <span class="arrow">▼</span>
          </a>
          <div id="customer-support-submenu" class="submenu">
            <a href="/admin/tickets.php" class="nav-item-sub">Tickets</a>
            <a href="/admin/alerts.php" class="nav-item-sub">Avisos</a>
            <a href="/admin/knowledge-base.php" class="nav-item-sub">Bancos de Conhecimento</a>
            <a href="/admin/documents.php" class="nav-item-sub">Documentos</a>
          </div>
        </div>

        <!-- Suporte Financeiro (submenu) - Apenas Gestor e Suporte Financeiro -->
        <?php if($cu && in_array($cu['role'], ['Gestor','Suporte Financeiro'])): ?>
        <div class="nav-group">
          <a href="#" class="nav-item submenu-toggle" data-submenu="finance">
            <span class="icon">💰</span> Suporte Financeiro <span class="arrow">▼</span>
          </a>
          <div id="finance-submenu" class="submenu">
            <a href="/admin/expenses.php" class="nav-item-sub">Despesas</a>
            <a href="/admin/reports.php" class="nav-item-sub">Relatórios</a>
          </div>
        </div>
        <?php endif; ?>

        <!-- Configuração (submenu) - Apenas Gestor -->
        <?php if($cu && $cu['role'] === 'Gestor'): ?>
        <div class="nav-group">
          <a href="#" class="nav-item submenu-toggle" data-submenu="settings">
            <span class="icon">⚙️</span> Configuração <span class="arrow">▼</span>
          </a>
          <div id="settings-submenu" class="submenu">
            <a href="/admin/settings.php" class="nav-item-sub">Definições Gerais</a>
            <a href="/admin/system-logs.php" class="nav-item-sub">Logs do Sistema</a>
          </div>
        </div>
        <?php endif; ?>

      <!-- Menu para Cliente (visão simples) -->
      <?php else: ?>
        <a href="/dashboard.php" class="nav-item">
          <span class="icon">📊</span> Painel
        </a>
        <a href="<?php echo $profileUrl; ?>" class="nav-item<?php echo $isProfilePage ? ' active' : ''; ?>">
          <span class="icon">👤</span> Perfil
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
  <main class="dashboard-content">
    <?php
      // Notificações simples (contagem por papel)
      $notifCount = 0;
      try {
        if (!isset($pdo)) { $pdo = getDB(); }
        $cu = currentUser();
        if ($cu && $cu['role'] === 'Cliente') {
          // Tickets abertos + faturas em atraso (se existir tabela)
          try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE user_id = ? AND status = 'open'");
            $stmt->execute([$cu['id']]);
            $notifCount += (int)$stmt->fetchColumn();
          } catch (Throwable $e) {}
          try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE user_id = ? AND status != 'paid' AND due_date < NOW()");
            $stmt->execute([$cu['id']]);
            $notifCount += (int)$stmt->fetchColumn();
          } catch (Throwable $e) {}
        } else if ($cu) {
          // Admin/support: tickets abertos
          try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM tickets WHERE status = 'open'");
            $notifCount += (int)$stmt->fetchColumn();
          } catch (Throwable $e) {}
        }
      } catch (Throwable $e) { $notifCount = 0; }
    ?>
    <div class="dashboard-topbar">
      <div class="topbar-left">
        <span class="topbar-title">CyberCore</span>
      </div>
      <div class="topbar-right">
        <form class="topbar-search" action="/search.php" method="get">
          <input type="text" name="q" placeholder="Pesquisar…">
          <button type="submit" class="icon-btn" aria-label="Pesquisar">🔎</button>
        </form>
        <button class="icon-btn bell" title="Notificações" type="button">
          🔔
          <?php if ($notifCount > 0): ?>
            <span class="badge"><?php echo (int)$notifCount; ?></span>
          <?php endif; ?>
        </button>
        <?php if ($cu): ?>
          <a class="user-chip" href="<?php echo $profileUrl; ?>" aria-label="Abrir perfil do utilizador">
            <span class="avatar"><?php echo strtoupper(substr($cu['first_name'],0,1)); ?></span>
            <span class="name"><?php 
              $displayName = !empty($cu['company_name']) ? $cu['company_name'] : ($cu['first_name'].' '.$cu['last_name']);
              echo htmlspecialchars($displayName);
            ?></span>
          </a>
        <?php endif; ?>
      </div>
    </div>
<?php endif; ?>
