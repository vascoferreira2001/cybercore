<?php
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/db.php';
requireLogin();
$user = currentUser();
$pdo = getDB();
if (!$user) { header('Location: logout.php'); exit; }

// Gerar CWC ID
$cwc = 'CWC#' . str_pad($user['id'], 4, '0', STR_PAD_LEFT);

// Resumos e métricas conforme role
$metrics = [];
if ($user['role'] === 'Gestor') {
  $metrics['users_total'] = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
  $metrics['domains_total'] = $pdo->query('SELECT COUNT(*) FROM domains')->fetchColumn();
  $metrics['invoices_unpaid'] = $pdo->query("SELECT COUNT(*) FROM invoices WHERE status = 'unpaid'")->fetchColumn();
  $metrics['tickets_open'] = $pdo->query("SELECT COUNT(*) FROM tickets WHERE status = 'open'")->fetchColumn();
} elseif ($user['role'] === 'Suporte Financeira') {
  $metrics['invoices_total'] = $pdo->query('SELECT COUNT(*) FROM invoices')->fetchColumn();
  $metrics['invoices_unpaid'] = $pdo->query("SELECT COUNT(*) FROM invoices WHERE status = 'unpaid'")->fetchColumn();
} elseif (in_array($user['role'], ['Suporte ao Cliente','Suporte Técnica'])) {
  $metrics['tickets_open'] = $pdo->query("SELECT COUNT(*) FROM tickets WHERE status = 'open'")->fetchColumn();
  $metrics['domains_total'] = $pdo->query('SELECT COUNT(*) FROM domains')->fetchColumn();
} else { // Cliente
  $stmt = $pdo->prepare('SELECT COUNT(*) FROM domains WHERE user_id = ? AND status = "active"'); $stmt->execute([$user['id']]); $metrics['my_services_active'] = $stmt->fetchColumn();
  $stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE user_id = ? AND status = 'open'"); $stmt->execute([$user['id']]); $metrics['my_tickets_active'] = $stmt->fetchColumn();
  $stmt = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE user_id = ? AND status != 'paid' AND due_date < NOW()"); $stmt->execute([$user['id']]); $metrics['overdue_invoices'] = $stmt->fetchColumn();
  $stmt = $pdo->prepare("SELECT SUM(amount) FROM invoices WHERE user_id = ? AND status != 'paid' AND due_date < NOW()"); $stmt->execute([$user['id']]); $metrics['overdue_amount'] = $stmt->fetchColumn() ?: 0;
}

?>
<?php include __DIR__ . '/inc/header.php'; ?>

<div class="card">
  <h2>Dashboard</h2>
  <p><strong>Cliente:</strong> <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></p>
  <p><strong>ID Cliente:</strong> <?php echo $cwc; ?></p>
  <p class="small">Bem-vindo. O painel abaixo mostra apenas o que é relevante para o seu papel.</p>
</div>

<?php if ($user['role'] === 'Gestor'): ?>
  <div class="card">
    <h3>Visão Geral (Gestor)</h3>
    <p>Utilizadores: <?php echo $metrics['users_total']; ?></p>
    <p>Domínios: <?php echo $metrics['domains_total']; ?></p>
    <p>Faturas por pagar: <?php echo $metrics['invoices_unpaid']; ?></p>
    <p>Tickets abertos: <?php echo $metrics['tickets_open']; ?></p>
    <p class="small">Utilize o menu para aceder às áreas detalhadas.</p>
  </div>
<?php elseif ($user['role'] === 'Contabilista'): ?>
  <div class="card">
    <h3>Financeiro (Contabilista)</h3>
    <p>Total de faturas: <?php echo $metrics['invoices_total']; ?></p>
    <p>Faturas por pagar: <?php echo $metrics['invoices_unpaid']; ?></p>
  </div>
<?php elseif ($user['role'] === 'Suporte'): ?>
  <div class="card">
    <h3>Suporte</h3>
    <p>Tickets abertos: <?php echo $metrics['tickets_open']; ?></p>
    <p>Domínios total: <?php echo $metrics['domains_total']; ?></p>
    <p class="small">Aceda a Suporte para gerir tickets.</p>
  </div>
<?php else: ?>
  <div class="widgets">
    <div class="widget">
      <h4>Projetos Ativos</h4>
      <p><?php echo $metrics['my_services_active']; ?></p>
    </div>
    <div class="widget">
      <h4>Tickets Abertos</h4>
      <p><?php echo $metrics['my_tickets_active']; ?></p>
    </div>
    <div class="widget">
      <h4>Faturas em Atraso</h4>
      <p><?php echo $metrics['overdue_invoices']; ?></p>
    </div>
    <div class="widget">
      <h4>Valor em Atraso</h4>
      <p><?php echo number_format($metrics['overdue_amount'], 2); ?> €</p>
    </div>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/inc/footer.php'; ?>

