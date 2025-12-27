<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/billing.php';

$page_title = 'Faturas | Área de Cliente';
$page_heading = 'Faturas';
$active_menu = 'invoices';

// TODO: substituir por utilizador autenticado
$current_user_id = $_SESSION['user_id'] ?? 1;

$status_meta = [
  'draft' => ['label' => 'Rascunho', 'class' => 'badge-info'],
  'unpaid' => ['label' => 'Em aberto', 'class' => 'badge-warning'],
  'paid' => ['label' => 'Pago', 'class' => 'badge-success'],
  'overdue' => ['label' => 'Vencido', 'class' => 'badge-error'],
  'canceled' => ['label' => 'Cancelado', 'class' => 'badge-error'],
];

$flash_success = '';
$flash_error = '';
$form_errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  if (!csrf_validate($_POST['csrf_token'] ?? null)) {
    $flash_error = 'Token de segurança inválido. Atualize a página e tente novamente.';
  } else {
    try {
      if ($action === 'create') {
        $description = trim($_POST['description'] ?? '');
        $amount = (float) ($_POST['amount'] ?? 0);
        $vat_rate = (float) ($_POST['vat_rate'] ?? 23);
        $due_date = $_POST['due_date'] ?? '';
        $reference = trim($_POST['reference'] ?? '');

        if ($amount <= 0) {
          $form_errors['amount'] = 'Valor tem de ser superior a 0.';
        }
        if ($vat_rate < 0 || $vat_rate > 30) {
          $form_errors['vat_rate'] = 'IVA inválido (0-30%).';
        }
        if (!$due_date || !DateTime::createFromFormat('Y-m-d', $due_date)) {
          $form_errors['due_date'] = 'Data de vencimento inválida.';
        }
        if (empty($form_errors)) {
          cybercore_invoice_create($current_user_id, [
            'description' => $description ?: 'Serviço de alojamento',
            'amount' => $amount,
            'vat_rate' => $vat_rate,
            'due_date' => $due_date,
            'reference' => $reference ?: null,
            'status' => 'unpaid',
          ]);
          $flash_success = 'Fatura criada com sucesso.';
        } else {
          $flash_error = 'Existem erros no formulário. Verifique os campos.';
        }
      }

      if ($action === 'mark_paid') {
        $invoice_id = filter_var($_POST['invoice_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$invoice_id) {
          $flash_error = 'Fatura inválida.';
        } else {
          $ok = cybercore_invoice_update_status($current_user_id, $invoice_id, 'paid');
          $flash_success = $ok ? 'Fatura marcada como paga.' : 'Não foi possível atualizar a fatura.';
        }
      }

      if ($action === 'cancel_invoice') {
        $invoice_id = filter_var($_POST['invoice_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$invoice_id) {
          $flash_error = 'Fatura inválida.';
        } else {
          $ok = cybercore_invoice_update_status($current_user_id, $invoice_id, 'canceled');
          $flash_success = $ok ? 'Fatura cancelada.' : 'Não foi possível cancelar a fatura.';
        }
      }
    } catch (Throwable $e) {
      error_log('[invoices] ' . $e->getMessage());
      $flash_error = 'Ocorreu um erro ao processar o pedido.';
    }
  }
}

$invoices = cybercore_invoice_list($current_user_id);

require_once __DIR__ . '/includes/layout-top.php';
?>

<section class="panel">
  <div class="panel-head">
    <div>
      <p class="panel-kicker">Nova fatura</p>
      <h2>Criar fatura rápida</h2>
    </div>
  </div>

  <?php if ($flash_error): ?><div class="alert alert-error"><?php echo htmlspecialchars($flash_error); ?></div><?php endif; ?>
  <?php if ($flash_success): ?><div class="alert alert-success"><?php echo htmlspecialchars($flash_success); ?></div><?php endif; ?>

  <form method="POST" action="" class="form">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
    <input type="hidden" name="action" value="create">
    <div class="form-row">
      <div class="form-group">
        <label for="description">Descrição</label>
        <input type="text" id="description" name="description" value="<?php echo htmlspecialchars($_POST['description'] ?? ''); ?>" placeholder="Serviço de alojamento" required>
      </div>
      <div class="form-group">
        <label for="reference">Referência</label>
        <input type="text" id="reference" name="reference" value="<?php echo htmlspecialchars($_POST['reference'] ?? ''); ?>" placeholder="OPCIONAL-123">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label for="amount">Valor (s/ IVA)</label>
        <input type="number" step="0.01" min="0" id="amount" name="amount" value="<?php echo htmlspecialchars($_POST['amount'] ?? ''); ?>" required>
        <?php if (isset($form_errors['amount'])): ?><small class="form-help" style="color: var(--error); "><?php echo htmlspecialchars($form_errors['amount']); ?></small><?php endif; ?>
      </div>
      <div class="form-group">
        <label for="vat_rate">IVA (%)</label>
        <input type="number" step="0.01" min="0" max="30" id="vat_rate" name="vat_rate" value="<?php echo htmlspecialchars($_POST['vat_rate'] ?? '23'); ?>" required>
        <?php if (isset($form_errors['vat_rate'])): ?><small class="form-help" style="color: var(--error); "><?php echo htmlspecialchars($form_errors['vat_rate']); ?></small><?php endif; ?>
      </div>
      <div class="form-group">
        <label for="due_date">Data de vencimento</label>
        <input type="date" id="due_date" name="due_date" value="<?php echo htmlspecialchars($_POST['due_date'] ?? ''); ?>" required>
        <?php if (isset($form_errors['due_date'])): ?><small class="form-help" style="color: var(--error); "><?php echo htmlspecialchars($form_errors['due_date']); ?></small><?php endif; ?>
      </div>
    </div>
    <button type="submit" class="btn btn-primary">Gerar fatura</button>
  </form>
</section>

<section class="panel">
  <div class="panel-head">
    <div>
      <p class="panel-kicker">Faturação</p>
      <h2>Resumo de faturas</h2>
    </div>
  </div>

  <div class="table-responsive">
    <?php if (empty($invoices)): ?>
      <p class="text-muted">Ainda não existem faturas.</p>
    <?php else: ?>
    <table class="table">
      <thead>
        <tr>
          <th>Número</th>
          <th>Referência</th>
          <th>Emitida</th>
          <th>Vencimento</th>
          <th>Valor</th>
          <th>Status</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($invoices as $invoice): ?>
          <?php
            $statusKey = $invoice['status'];
            $statusInfo = $status_meta[$statusKey] ?? ['label' => ucfirst($statusKey), 'class' => 'badge-info'];
            $issued = date('d M Y', strtotime($invoice['issued_at']));
            $due = date('d M Y', strtotime($invoice['due_date']));
            $total = number_format((float) $invoice['total'], 2, ',', '.');
          ?>
          <tr>
            <td><?php echo htmlspecialchars($invoice['number']); ?></td>
            <td><?php echo htmlspecialchars($invoice['reference'] ?? '—'); ?></td>
            <td><?php echo htmlspecialchars($issued); ?></td>
            <td><?php echo htmlspecialchars($due); ?></td>
            <td><?php echo $total; ?> €</td>
            <td><span class="badge <?php echo htmlspecialchars($statusInfo['class']); ?>"><?php echo htmlspecialchars($statusInfo['label']); ?></span></td>
            <td class="table-actions">
              <a class="link" href="#">PDF</a>
              <?php if ($statusKey !== 'paid' && $statusKey !== 'canceled'): ?>
              <form method="POST" action="" style="display:inline">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                <input type="hidden" name="action" value="mark_paid">
                <input type="hidden" name="invoice_id" value="<?php echo (int) $invoice['id']; ?>">
                <button type="submit" class="btn btn-ghost">Marcar pago</button>
              </form>
              <form method="POST" action="" style="display:inline">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                <input type="hidden" name="action" value="cancel_invoice">
                <input type="hidden" name="invoice_id" value="<?php echo (int) $invoice['id']; ?>">
                <button type="submit" class="btn btn-ghost" onclick="return confirm('Confirmar cancelamento da fatura?');">Cancelar</button>
              </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</section>

<?php require_once __DIR__ . '/includes/layout-bottom.php'; ?>
