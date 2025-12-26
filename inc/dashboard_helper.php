<?php
/**
 * CyberCore Dashboard Layout Helper
 * Gera estrutura consistente de sidebar + topbar + main content
 */

require_once __DIR__ . '/menu_config.php';

function renderDashboardLayout($pageTitle, $pageSubtitle, $content, $sidebarActive = null) {
  // Obter usuário atual
  $user = isset($GLOBALS['currentUser']) ? $GLOBALS['currentUser'] : null;
  
  if (!$user) {
    // Fallback para sessão
    require_once __DIR__ . '/auth.php';
    $user = currentUser();
  }
  
  if (!$user) {
    header('Location: /login.php');
    exit;
  }
  
  // Obter menu items baseado no cargo
  $menuItems = getMenuItemsByRole($user['role']);
  
  ob_start();
  ?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($pageTitle); ?> - CyberCore</title>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/pages/dashboard-modern.css">
</head>
<body>

  <!-- ========== SIDEBAR ========== -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
      <div class="logo">
        <svg width="32" height="32" viewBox="0 0 32 32" fill="none">
          <rect width="32" height="32" rx="8" fill="url(#gradient1)"/>
          <path d="M16 8L22 12V20L16 24L10 20V12L16 8Z" stroke="white" stroke-width="2" fill="none"/>
          <defs>
            <linearGradient id="gradient1" x1="0" y1="0" x2="32" y2="32">
              <stop offset="0%" stop-color="#007dff"/>
              <stop offset="100%" stop-color="#0052cc"/>
            </linearGradient>
          </defs>
        </svg>
        <span class="logo-text">CyberCore</span>
      </div>
      <button class="sidebar-toggle" id="sidebarToggle">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="3" y1="12" x2="21" y2="12"></line>
          <line x1="3" y1="6" x2="21" y2="6"></line>
          <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
      </button>
    </div>

    <nav class="sidebar-nav">
      <?php foreach ($menuItems as $item): ?>
        <?php if (isset($item['type']) && $item['type'] === 'divider'): ?>
          <div class="nav-divider"><?php echo htmlspecialchars($item['label']); ?></div>
        <?php else: ?>
          <a href="<?php echo htmlspecialchars($item['url']); ?>" 
             class="nav-item <?php echo ($sidebarActive === $item['key']) ? 'active' : ''; ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <?php echo getMenuIcon($item['icon']); ?>
            </svg>
            <span><?php echo htmlspecialchars($item['label']); ?></span>
          </a>
        <?php endif; ?>
      <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">
      <a href="/logout.php" class="nav-item logout-btn">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <?php echo getMenuIcon('logout'); ?>
        </svg>
        <span>Sair</span>
      </a>
    </div>
  </aside>

  <!-- ========== MAIN CONTENT ========== -->
  <div class="main-wrapper">
    <!-- Top Navigation Bar -->
    <header class="topbar">
      <div class="topbar-left">
        <button class="mobile-menu-btn" id="mobileMenuBtn">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="3" y1="12" x2="21" y2="12"></line>
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <line x1="3" y1="18" x2="21" y2="18"></line>
          </svg>
        </button>
        <div class="search-box">
          <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"></circle>
            <path d="m21 21-4.35-4.35"></path>
          </svg>
          <input type="text" placeholder="Pesquisar...">
        </div>
      </div>

      <div class="topbar-right">
        <a class="user-menu" href="/profile.php" aria-label="Abrir perfil do utilizador">
          <div class="user-info">
            <span class="user-name"><?php echo htmlspecialchars(isset($GLOBALS['currentUser']) ? (trim($GLOBALS['currentUser']['first_name'] . ' ' . $GLOBALS['currentUser']['last_name']) ?: $GLOBALS['currentUser']['email']) : 'Utilizador'); ?></span>
            <span class="user-id">CYC#<?php echo isset($GLOBALS['currentUser']) ? str_pad($GLOBALS['currentUser']['id'], 5, '0', STR_PAD_LEFT) : '00000'; ?></span>
          </div>
          <div class="user-avatar" title="<?php echo htmlspecialchars(isset($GLOBALS['currentUser']) ? $GLOBALS['currentUser']['email'] : ''); ?>">
            <?php echo isset($GLOBALS['currentUser']) ? strtoupper(substr($GLOBALS['currentUser']['first_name'], 0, 1)) : 'U'; ?>
          </div>
        </a>
      </div>
    </header>

    <!-- Main Content -->
    <main class="dashboard-content">
      <div class="dashboard-header">
        <div>
          <h1 class="page-title"><?php echo htmlspecialchars($pageTitle); ?></h1>
          <p class="page-subtitle"><?php echo htmlspecialchars($pageSubtitle); ?></p>
        </div>
      </div>

      <?php echo $content; ?>
    </main>

    <!-- Footer -->
    <footer class="dashboard-footer" role="contentinfo">
      <p>CyberCore © 2025 • Segurança e performance primeiro</p>
    </footer>
  </div>

</body>
</html>
  <?php
  return ob_get_clean();
}
