<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/billing.php';

cybercore_require_admin();

$page_title = 'Faturas | Admin Panel';
$page_heading = 'Gestão de Faturas';
$active_menu = 'invoices';

$pdo = cybercore_pdo();
$invoices = $pdo->query('SELECT i.*, u.identifier, u.email, u.first_name, u.last_name FROM invoices i JOIN users u ON i.user_id = u.id ORDER BY i.issued_at DESC')->fetchAll();

require_once __DIR__ . '/includes/layout-top.php';
?>

<section class="panel">
  <div class="panel-head">
    <div>
      <p class="panel-kicker">Faturas</p>
      <h2>Todas as faturas</h2>
    </div>
    <div class="panel-actions">
      <a class="btn btn-ghost" href="#">Exportar</a>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Número</th>
          <th>Cliente</th>
          <th>Total</th>
          <th>Status</th>
          <th>Vencimento</th>
          <th>Emitida</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($invoices as $invoice): ?>
          <tr>
            <td><?php echo htmlspecialchars($invoice['number']); ?></td>
            <td><?php echo htmlspecialchars($invoice['identifier']); ?> - <?php echo htmlspecialchars($invoice['first_name']); ?></td>
            <td><?php echo number_format((float) $invoice['total'], 2, ',', '.'); ?> €</td>
            <td>
              <?php
                $status_class = [
                  'paid' => 'badge-success',
                  'unpaid' => 'badge-warning',
                  'overdue' => 'badge-error',
                  'canceled' => 'badge-error'
                ];
                $class = $status_class[$invoice['status']] ?? 'badge-info';
              ?>
              <span class="badge <?php echo $class; ?>"><?php echo htmlspecialchars($invoice['status']); ?></span>
            </td>
            <td><?php echo date('d/m/Y', strtotime($invoice['due_date'])); ?></td>
            <td><?php echo date('d/m/Y', strtotime($invoice['issued_at'])); ?></td>
            <td><a class="link" href="#">Ver</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<?php require_once __DIR__ . '/includes/layout-bottom.php'; ?>
