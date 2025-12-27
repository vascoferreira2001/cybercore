<?php
require_once __DIR__ . '/../menu_config.php';

function renderSidebar(array $menuItems, ?string $sidebarActive = null) {
  ob_start();
  ?>
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
      <div class="logo">
        <svg width="32" height="32" viewBox="0 0 32 32" fill="none" aria-hidden="true">
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
      <button class="sidebar-toggle" id="sidebarToggle" aria-label="Alternar menu lateral">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="3" y1="12" x2="21" y2="12"></line>
          <line x1="3" y1="6" x2="21" y2="6"></line>
          <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
      </button>
    </div>

    <nav class="sidebar-nav" aria-label="Navegação principal">
      <?php foreach ($menuItems as $item): ?>
        <?php if (isset($item['type']) && $item['type'] === 'divider'): ?>
          <div class="nav-divider"><?php echo htmlspecialchars($item['label']); ?></div>
        <?php else: ?>
          <a href="<?php echo htmlspecialchars($item['url']); ?>"
             class="nav-item <?php echo ($sidebarActive === $item['key']) ? 'active' : ''; ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
              <?php echo getMenuIcon($item['icon']); ?>
            </svg>
            <span><?php echo htmlspecialchars($item['label']); ?></span>
          </a>
        <?php endif; ?>
      <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">
      <a href="/logout.php" class="nav-item logout-btn">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
          <?php echo getMenuIcon('logout'); ?>
        </svg>
        <span>Sair</span>
      </a>
    </div>
  </aside>
  <?php
  return ob_get_clean();
}
?>
