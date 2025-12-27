<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/tickets.php';

$page_title = 'Tickets de Suporte | Área de Cliente';
$page_heading = 'Tickets de Suporte';
$active_menu = 'tickets';

// TODO: substituir por utilizador autenticado real
$current_user_id = $_SESSION['user_id'] ?? 1;

$status_meta = [
  'open' => ['label' => 'Aberto', 'class' => 'badge-info'],
  'customer-replied' => ['label' => 'Cliente respondeu', 'class' => 'badge-warning'],
  'answered' => ['label' => 'Respondido', 'class' => 'badge-success'],
  'pending' => ['label' => 'Pendente', 'class' => 'badge-warning'],
  'closed' => ['label' => 'Fechado', 'class' => 'badge-error'],
];

$priority_labels = [
  'low' => 'Baixa',
  'normal' => 'Normal',
  'high' => 'Alta',
  'urgent' => 'Urgente',
];

$flash_success = '';
$flash_error = '';
$form_errors = [];

$ticket_id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$view_ticket = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  if (!csrf_validate($_POST['csrf_token'] ?? null)) {
    $flash_error = 'Token de segurança inválido. Atualize a página e tente novamente.';
  } else {
    try {
      if ($action === 'open') {
        $subject = trim($_POST['subject'] ?? '');
        $priority = $_POST['priority'] ?? 'normal';
        $message = trim($_POST['message'] ?? '');

        if (strlen($subject) < 5) $form_errors['subject'] = 'Assunto muito curto.';
        if (strlen($message) < 5) $form_errors['message'] = 'Mensagem muito curta.';
        if (!in_array($priority, cybercore_ticket_priorities(), true)) $form_errors['priority'] = 'Prioridade inválida.';

        if (empty($form_errors)) {
          $ticket_id = cybercore_ticket_create($current_user_id, [
            'subject' => $subject,
            'priority' => $priority,
            'message' => $message,
          ]);
          $flash_success = 'Ticket criado com sucesso.';
          // Placeholder for notification
          // cybercore_ticket_notify($userEmail, 'Novo ticket', $subject);
          $view_ticket = cybercore_ticket_get($current_user_id, $ticket_id);
        } else {
          $flash_error = 'Existem erros no formulário.';
        }
      }

      if ($action === 'reply') {
        $ticket_id = (int) ($_POST['ticket_id'] ?? 0);
        $message = trim($_POST['message'] ?? '');
        if ($ticket_id && strlen($message) >= 3) {
          cybercore_ticket_reply($ticket_id, $current_user_id, $message, false);
          $flash_success = 'Resposta enviada.';
          $view_ticket = cybercore_ticket_get($current_user_id, $ticket_id);
        } else {
          $flash_error = 'Mensagem muito curta.';
        }
      }

      if ($action === 'close') {
        $ticket_id = (int) ($_POST['ticket_id'] ?? 0);
        if ($ticket_id) {
          cybercore_ticket_update_status($ticket_id, 'closed');
          $flash_success = 'Ticket fechado.';
          $view_ticket = cybercore_ticket_get($current_user_id, $ticket_id);
        }
      }
    } catch (Throwable $e) {
      error_log('[tickets] ' . $e->getMessage());
      $flash_error = 'Ocorreu um erro ao processar o pedido.';
    }
  }
}

if ($ticket_id && !$view_ticket) {
  $view_ticket = cybercore_ticket_get($current_user_id, $ticket_id);
}

$tickets = cybercore_ticket_list($current_user_id);

require_once __DIR__ . '/includes/layout-top.php';
?>

<?php if ($view_ticket): ?>
<section class="panel">
  <div class="panel-head">
    <div>
      <p class="panel-kicker">Ticket #<?php echo (int) $view_ticket['id']; ?></p>
      <h2><?php echo htmlspecialchars($view_ticket['subject']); ?></h2>
      <p class="text-muted">Prioridade: <?php echo htmlspecialchars($priority_labels[$view_ticket['priority']] ?? $view_ticket['priority']); ?></p>
    </div>
    <div class="panel-actions">
      <span class="badge <?php echo htmlspecialchars(($status_meta[$view_ticket['status']]['class'] ?? 'badge-info')); ?>"><?php echo htmlspecialchars($status_meta[$view_ticket['status']]['label'] ?? $view_ticket['status']); ?></span>
    </div>
  </div>

  <?php if ($flash_error): ?><div class="alert alert-error"><?php echo htmlspecialchars($flash_error); ?></div><?php endif; ?>
  <?php if ($flash_success): ?><div class="alert alert-success"><?php echo htmlspecialchars($flash_success); ?></div><?php endif; ?>

  <div class="list list-divided">
    <?php foreach ($view_ticket['messages'] as $msg): ?>
      <div class="list-item">
        <div>
          <p class="list-title"><?php echo $msg['is_admin'] ? 'Suporte' : 'Você'; ?> · <?php echo date('d M Y H:i', strtotime($msg['created_at'])); ?></p>
          <p class="list-meta" style="white-space: pre-wrap;"><?php echo htmlspecialchars($msg['message']); ?></p>
        </div>
        <span class="badge <?php echo $msg['is_admin'] ? 'badge-info' : 'badge-success'; ?>"><?php echo $msg['is_admin'] ? 'Admin' : 'Cliente'; ?></span>
      </div>
    <?php endforeach; ?>
  </div>

  <?php if ($view_ticket['status'] !== 'closed'): ?>
  <form method="POST" action="" class="form" style="margin-top:1rem;">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
    <input type="hidden" name="action" value="reply">
    <input type="hidden" name="ticket_id" value="<?php echo (int) $view_ticket['id']; ?>">
    <div class="form-group">
      <label for="reply">Responder</label>
      <textarea id="reply" name="message" rows="4" required placeholder="Escreva a sua resposta"></textarea>
    </div>
    <div class="panel-actions">
      <button type="submit" class="btn btn-primary">Enviar resposta</button>
      <form method="POST" action="" style="display:inline">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
        <input type="hidden" name="action" value="close">
        <input type="hidden" name="ticket_id" value="<?php echo (int) $view_ticket['id']; ?>">
        <button type="submit" class="btn btn-ghost" onclick="return confirm('Fechar este ticket?');">Fechar</button>
      </form>
    </div>
  </form>
  <?php endif; ?>
</section>

<section class="panel">
  <a class="link" href="/client/tickets.php">← Voltar aos tickets</a>
</section>

<?php else: ?>

<section class="panel">
  <div class="panel-head">
    <div>
      <p class="panel-kicker">Novo ticket</p>
      <h2>Abrir ticket de suporte</h2>
    </div>
  </div>

  <?php if ($flash_error): ?><div class="alert alert-error"><?php echo htmlspecialchars($flash_error); ?></div><?php endif; ?>
  <?php if ($flash_success): ?><div class="alert alert-success"><?php echo htmlspecialchars($flash_success); ?></div><?php endif; ?>

  <form method="POST" action="" class="form">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
    <input type="hidden" name="action" value="open">
    <div class="form-row">
      <div class="form-group">
        <label for="subject">Assunto</label>
        <input type="text" id="subject" name="subject" value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>" required>
        <?php if (isset($form_errors['subject'])): ?><small class="form-help" style="color: var(--error); "><?php echo htmlspecialchars($form_errors['subject']); ?></small><?php endif; ?>
      </div>
      <div class="form-group">
        <label for="priority">Prioridade</label>
        <select id="priority" name="priority">
          <?php foreach (cybercore_ticket_priorities() as $p): ?>
            <option value="<?php echo $p; ?>" <?php echo (($_POST['priority'] ?? 'normal') === $p) ? 'selected' : ''; ?>><?php echo htmlspecialchars($priority_labels[$p]); ?></option>
          <?php endforeach; ?>
        </select>
        <?php if (isset($form_errors['priority'])): ?><small class="form-help" style="color: var(--error); "><?php echo htmlspecialchars($form_errors['priority']); ?></small><?php endif; ?>
      </div>
    </div>
    <div class="form-group">
      <label for="message">Mensagem</label>
      <textarea id="message" name="message" rows="4" required placeholder="Descreva o problema ou pedido com detalhe."><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
      <?php if (isset($form_errors['message'])): ?><small class="form-help" style="color: var(--error); "><?php echo htmlspecialchars($form_errors['message']); ?></small><?php endif; ?>
    </div>
    <button type="submit" class="btn btn-primary">Enviar ticket</button>
  </form>
</section>

<section class="panel">
  <div class="panel-head">
    <div>
      <p class="panel-kicker">Suporte</p>
      <h2>Tickets recentes</h2>
    </div>
  </div>

  <div class="table-responsive">
    <?php if (empty($tickets)): ?>
      <p class="text-muted">Ainda não existem tickets.</p>
    <?php else: ?>
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
        <?php foreach ($tickets as $t): ?>
          <tr>
            <td><?php echo (int) $t['id']; ?></td>
            <td><?php echo htmlspecialchars($t['subject']); ?></td>
            <td><?php echo htmlspecialchars($priority_labels[$t['priority']] ?? $t['priority']); ?></td>
            <td><span class="badge <?php echo htmlspecialchars($status_meta[$t['status']]['class'] ?? 'badge-info'); ?>"><?php echo htmlspecialchars($status_meta[$t['status']]['label'] ?? $t['status']); ?></span></td>
            <td><?php echo htmlspecialchars(date('d M Y H:i', strtotime($t['updated_at']))); ?></td>
            <td><a class="link" href="/client/tickets.php?id=<?php echo (int) $t['id']; ?>">Ver</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</section>

<?php endif; ?>

<?php require_once __DIR__ . '/includes/layout-bottom.php'; ?>
