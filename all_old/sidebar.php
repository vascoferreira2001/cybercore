<?php
require_once __DIR__ . '/inc/auth.php';
$cu = currentUser();
$role = $cu ? $cu['role'] : null;
$profileUrl = '/profile.php';
$current = $_SERVER['REQUEST_URI'] ?? '';

function navItem($href, $label, $icon = '', $active = false) {
    $cls = 'nav-item' . ($active ? ' active' : '');
    echo '<a href="' . htmlspecialchars($href) . '" class="' . $cls . '">';
    if ($icon) echo '<span class="icon">' . htmlspecialchars($icon) . '</span> ';
    echo htmlspecialchars($label) . '</a>';
}
?>
<nav class="sidebar-nav">
  <?php
  // Common items
  navItem('/dashboard.php', 'Painel', '📊', strpos($current, '/dashboard.php') !== false);

  if ($role === 'Gestor') {
      navItem('/admin/customers.php', 'Clientes', '👥', strpos($current, '/admin/customers.php') !== false);
      navItem('/admin/tasks.php', 'Tarefas', '✓', strpos($current, '/admin/tasks.php') !== false);
      navItem('/admin/services.php', 'Serviços', '🛠️', strpos($current, '/admin/services.php') !== false);
      navItem('/admin/reports.php', 'Relatórios', '📈', strpos($current, '/admin/reports.php') !== false);
      navItem('/admin/settings.php', 'Definições', '⚙️', strpos($current, '/admin/settings.php') !== false);
  }
  if (in_array($role, ['Suporte Técnico','Suporte Financeiro','Suporte ao Cliente','Gestor'])) {
      navItem('/admin/tickets.php', 'Tickets', '🎫', strpos($current, '/admin/tickets.php') !== false);
      navItem('/admin/live-chat.php', 'Live Chat', '💬', strpos($current, '/admin/live-chat.php') !== false);
  }
  if ($role === 'Suporte Financeiro' || $role === 'Gestor') {
      navItem('/admin/expenses.php', 'Despesas', '💰', strpos($current, '/admin/expenses.php') !== false);
      navItem('/admin/reports.php', 'Relatórios', '📈', strpos($current, '/admin/reports.php') !== false);
  }
  if ($role === 'Cliente' || !$role) {
      navItem('/support.php', 'Suporte', '🎧', strpos($current, '/support.php') !== false);
      navItem('/domains.php', 'Domínios', '🌐', strpos($current, '/domains.php') !== false);
      navItem('/finance.php', 'Financeiro', '💰', strpos($current, '/finance.php') !== false);
      navItem('/logs.php', 'Logs', '📋', strpos($current, '/logs.php') !== false);
  }
  ?>
  <div class="logout" style="margin-top:16px"><a href="/logout.php">Logout</a></div>
</nav>
