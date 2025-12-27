<?php
function renderTopbar(array $user, int $unreadNotifications = 0, string $profileUrl = '/profile.php') {
  $fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: ($user['email'] ?? 'Utilizador');
  $userId = str_pad($user['id'] ?? 0, 5, '0', STR_PAD_LEFT);
  $initial = strtoupper(substr($fullName, 0, 1));

  ob_start();
  ?>
  <header class="topbar">
    <div class="topbar-left">
      <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Abrir menu">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="3" y1="12" x2="21" y2="12"></line>
          <line x1="3" y1="6" x2="21" y2="6"></line>
          <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
      </button>
      <div class="search-box">
        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
          <circle cx="11" cy="11" r="8"></circle>
          <path d="m21 21-4.35-4.35"></path>
        </svg>
        <input type="text" placeholder="Pesquisar...">
      </div>
    </div>

    <div class="topbar-right">
      <button class="notification-btn" aria-label="Notificações">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
          <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
        </svg>
        <?php if ($unreadNotifications > 0): ?>
        <span class="notification-badge"><?php echo (int)$unreadNotifications; ?></span>
        <?php endif; ?>
      </button>

      <a class="user-menu" href="<?php echo htmlspecialchars($profileUrl); ?>" aria-label="Abrir perfil do utilizador">
        <div class="user-info">
          <span class="user-name"><?php echo htmlspecialchars($fullName); ?></span>
          <span class="user-id">CYC#<?php echo htmlspecialchars($userId); ?></span>
        </div>
        <div class="user-avatar" title="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
          <?php echo htmlspecialchars($initial ?: 'U'); ?>
        </div>
      </a>
    </div>
  </header>
  <?php
  return ob_get_clean();
}
?>
