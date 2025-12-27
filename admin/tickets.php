<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/tickets.php';

cybercore_require_admin();

$page_title = 'Tickets | Admin Panel';
$page_heading = 'Gestão de Tickets';
$active_menu = 'tickets';

$current_admin_id = $_SESSION['user_id'] ?? null;

$flash_success = '';
$flash_error = '';

$pdo = cybercore_pdo();

$ticket_id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$view_ticket = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $flash_error = 'Token de segurança inválido.';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'reply') {
            $ticket_id = (int) ($_POST['ticket_id'] ?? 0);
            $message = trim($_POST['message'] ?? '');
            if ($ticket_id && strlen($message) >= 3) {
                cybercore_ticket_reply($ticket_id, $current_admin_id, $message, true);
                $flash_success = 'Resposta enviada ao cliente.';
                $view_ticket = cybercore_ticket_get(0, $ticket_id, true);
            }
        }
        
        if ($action === 'close') {
            $ticket_id = (int) ($_POST['ticket_id'] ?? 0);
            if ($ticket_id) {
                cybercore_ticket_update_status($ticket_id, 'closed');
                $flash_success = 'Ticket fechado.';
                $view_ticket = cybercore_ticket_get(0, $ticket_id, true);
            }
        }
    }
}

if ($ticket_id && !$view_ticket) {
    $view_ticket = cybercore_ticket_get(0, $ticket_id, true);
}

$tickets = cybercore_ticket_list(0, true);

require_once __DIR__ . '/includes/layout-top.php';
?>

<?php if ($view_ticket): ?>
<section class="panel">
  <div class="panel-head">
    <div>
      <p class="panel-kicker">Ticket #<?php echo (int) $view_ticket['id']; ?></p>
      <h2><?php echo htmlspecialchars($view_ticket['subject']); ?></h2>
      <p class="text-muted">Prioridade: <?php echo htmlspecialchars($view_ticket['priority']); ?></p>
    </div>
    <span class="badge badge-<?php echo $view_ticket['status'] === 'closed' ? 'error' : 'warning'; ?>"><?php echo htmlspecialchars($view_ticket['status']); ?></span>
  </div>

  <?php if ($flash_error): ?><div class="alert alert-error"><?php echo htmlspecialchars($flash_error); ?></div><?php endif; ?>
  <?php if ($flash_success): ?><div class="alert alert-success"><?php echo htmlspecialchars($flash_success); ?></div><?php endif; ?>

  <div class="list list-divided">
    <?php foreach ($view_ticket['messages'] as $msg): ?>
      <div class="list-item">
        <div>
          <p class="list-title"><?php echo $msg['is_admin'] ? '🛠️ Suporte' : '👤 Cliente'; ?> · <?php echo date('d M Y H:i', strtotime($msg['created_at'])); ?></p>
          <p class="list-meta" style="white-space: pre-wrap;"><?php echo htmlspecialchars($msg['message']); ?></p>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <?php if ($view_ticket['status'] !== 'closed'): ?>
  <form method="POST" action="" class="form" style="margin-top:1rem;">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
    <input type="hidden" name="action" value="reply">
    <input type="hidden" name="ticket_id" value="<?php echo (int) $view_ticket['id']; ?>">
    <div class="form-group">
      <label for="reply">Responder ao cliente</label>
      <textarea id="reply" name="message" rows="4" required placeholder="Escreva a sua resposta"></textarea>
    </div>
    <div class="panel-actions">
      <button type="submit" class="btn btn-primary">Enviar resposta</button>
      <form method="POST" action="" style="display:inline">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
        <input type="hidden" name="action" value="close">
        <input type="hidden" name="ticket_id" value="<?php echo (int) $view_ticket['id']; ?>">
        <button type="submit" class="btn btn-ghost">Fechar ticket</button>
      </form>
    </div>
  </form>
  <?php endif; ?>
</section>

<section class="panel">
  <a class="link" href="/admin/tickets.php">← Voltar aos tickets</a>
</section>

<?php else: ?>

<section class="panel">
  <div class="panel-head">
    <div>
      <p class="panel-kicker">Tickets</p>
      <h2>Todos os tickets</h2>
    </div>
    <div class="panel-actions">
      <a class="btn btn-ghost" href="#">Filtros</a>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>#</th>
          <th>Assunto</th>
          <th>Prioridade</th>
          <th>Status</th>
          <th>Atualizado</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($tickets as $ticket): ?>
          <tr>
            <td><?php echo (int) $ticket['id']; ?></td>
            <td><?php echo htmlspecialchars($ticket['subject']); ?></td>
            <td>
              <?php
                $priority_class = ['low' => 'badge-info', 'normal' => 'badge-info', 'high' => 'badge-warning', 'urgent' => 'badge-error'];
                $class = $priority_class[$ticket['priority']] ?? 'badge-info';
              ?>
              <span class="badge <?php echo $class; ?>"><?php echo htmlspecialchars($ticket['priority']); ?></span>
            </td>
            <td>
              <?php
                $status_class = ['open' => 'badge-warning', 'customer-replied' => 'badge-warning', 'answered' => 'badge-success', 'closed' => 'badge-error'];
                $class = $status_class[$ticket['status']] ?? 'badge-info';
              ?>
              <span class="badge <?php echo $class; ?>"><?php echo htmlspecialchars($ticket['status']); ?></span>
            </td>
            <td><?php echo date('d/m/Y H:i', strtotime($ticket['updated_at'])); ?></td>
            <td><a class="link" href="/admin/tickets.php?id=<?php echo (int) $ticket['id']; ?>">Ver</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<?php endif; ?>

<?php require_once __DIR__ . '/includes/layout-bottom.php'; ?>
