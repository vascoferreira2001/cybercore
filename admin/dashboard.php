<?php
$page_title = 'Dashboard | Admin Panel';
$page_heading = 'Visão Geral';
$active_menu = 'dashboard';
require_once __DIR__ . '/includes/layout-top.php';

// Get stats
$pdo = cybercore_pdo();

$total_users = $pdo->query('SELECT COUNT(*) FROM users WHERE role = "Cliente"')->fetchColumn();
$total_services = $pdo->query('SELECT COUNT(*) FROM services')->fetchColumn();
$active_services = $pdo->query('SELECT COUNT(*) FROM services WHERE status = "active"')->fetchColumn();
$total_invoices = $pdo->query('SELECT COUNT(*) FROM invoices')->fetchColumn();
$unpaid_invoices = $pdo->query('SELECT COUNT(*) FROM invoices WHERE status = "unpaid"')->fetchColumn();
$open_tickets = $pdo->query('SELECT COUNT(*) FROM tickets WHERE status IN ("open", "customer-replied")')->fetchColumn();

$recent_users = $pdo->query('SELECT id, identifier, email, first_name, last_name, created_at FROM users ORDER BY created_at DESC LIMIT 5')->fetchAll();
$recent_tickets = $pdo->query('SELECT id, subject, status, priority, created_at FROM tickets ORDER BY created_at DESC LIMIT 5')->fetchAll();
?>

<section class="grid-4">
  <div class="card metric">
    <div class="metric-label">Total Clientes</div>
    <div class="metric-value"><?php echo (int) $total_users; ?></div>
  </div>
  <div class="card metric">
    <div class="metric-label">Serviços Ativos</div>
    <div class="metric-value"><?php echo (int) $active_services; ?> / <?php echo (int) $total_services; ?></div>
  </div>
  <div class="card metric">
    <div class="metric-label">Faturas em Aberto</div>
    <div class="metric-value"><?php echo (int) $unpaid_invoices; ?> / <?php echo (int) $total_invoices; ?></div>
  </div>
  <div class="card metric">
    <div class="metric-label">Tickets Abertos</div>
    <div class="metric-value"><?php echo (int) $open_tickets; ?></div>
  </div>
</section>

<div class="grid-2">
  <section class="panel">
    <div class="panel-head">
      <div>
        <p class="panel-kicker">Utilizadores</p>
        <h2>Registos recentes</h2>
      </div>
      <a class="btn btn-ghost" href="/admin/users.php">Ver todos</a>
    </div>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nome</th>
            <th>Email</th>
            <th>Registado</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recent_users as $user): ?>
            <tr>
              <td><?php echo htmlspecialchars($user['identifier']); ?></td>
              <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
              <td><?php echo htmlspecialchars($user['email']); ?></td>
              <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

  <section class="panel">
    <div class="panel-head">
      <div>
        <p class="panel-kicker">Suporte</p>
        <h2>Tickets recentes</h2>
      </div>
      <a class="btn btn-ghost" href="/admin/tickets.php">Ver todos</a>
    </div>
    <div class="list list-divided">
      <?php foreach ($recent_tickets as $ticket): ?>
        <div class="list-item">
          <div>
            <p class="list-title">#<?php echo (int) $ticket['id']; ?> - <?php echo htmlspecialchars($ticket['subject']); ?></p>
            <p class="list-meta"><?php echo date('d/m/Y H:i', strtotime($ticket['created_at'])); ?></p>
          </div>
          <span class="badge badge-<?php echo $ticket['status'] === 'open' ? 'warning' : 'info'; ?>"><?php echo htmlspecialchars($ticket['status']); ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
</div>

<?php require_once __DIR__ . '/includes/layout-bottom.php'; ?>
