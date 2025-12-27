<?php
/**
 * CyberCore Dashboard Layout Helper
 * Gera estrutura consistente de sidebar + topbar + main content
 */

require_once __DIR__ . '/menu_config.php';
require_once __DIR__ . '/layouts/sidebar.php';
require_once __DIR__ . '/layouts/topbar.php';

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
  <link rel="stylesheet" href="/assets/css/modern.css">
</head>
<body>

  <!-- ========== SIDEBAR ========== -->
  <?php echo renderSidebar($menuItems, $sidebarActive); ?>

  <!-- ========== MAIN CONTENT ========== -->
  <div class="main-wrapper">
    <!-- Top Navigation Bar -->
    <?php echo renderTopbar($user, $GLOBALS['unreadNotifications'] ?? 0, '/profile.php'); ?>

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

  <script>
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('sidebarToggle');
    const mobileBtn = document.getElementById('mobileMenuBtn');

    const toggleSidebar = () => sidebar?.classList.toggle('open');
    toggleBtn?.addEventListener('click', toggleSidebar);
    mobileBtn?.addEventListener('click', toggleSidebar);
  </script>

</body>
</html>
  <?php
  return ob_get_clean();
}
