<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/services.php';

$page_title = 'Meus Serviços | Área de Cliente';
$page_heading = 'Meus Serviços';
$active_menu = 'services';

// TODO: substituir por utilizador autenticado
$current_user_id = $_SESSION['user_id'] ?? 1;

$plans = [
    'starter' => ['name' => 'Starter', 'monthly_price' => 4.99],
    'business' => ['name' => 'Business', 'monthly_price' => 9.99],
    'pro' => ['name' => 'Pro', 'monthly_price' => 19.99],
];

$cycles = [
    'monthly' => 'Mensal',
    'yearly' => 'Anual',
];

$status_meta = [
    'provisioning' => ['label' => 'A configurar', 'class' => 'badge-warning'],
    'active' => ['label' => 'Ativo', 'class' => 'badge-success'],
    'pending' => ['label' => 'Pendente', 'class' => 'badge-info'],
    'suspended' => ['label' => 'Suspenso', 'class' => 'badge-error'],
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
            if ($action === 'order') {
                $domain = strtolower(trim($_POST['domain'] ?? ''));
                $plan = $_POST['plan'] ?? '';
                $cycle = $_POST['billing_cycle'] ?? 'monthly';

                if (strlen($domain) < 3 || strlen($domain) > 255 || !preg_match('/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)+$/i', $domain)) {
                    $form_errors['domain'] = 'Domínio inválido. Exemplo: exemplo.pt';
                }
                if (!isset($plans[$plan])) {
                    $form_errors['plan'] = 'Plano inválido.';
                }
                if (!in_array($cycle, array_keys($cycles), true)) {
                    $form_errors['billing_cycle'] = 'Ciclo de faturação inválido.';
                }

                if (empty($form_errors)) {
                    $base_price = $plans[$plan]['monthly_price'];
                    $price = $cycle === 'yearly' ? round($base_price * 12 * 0.9, 2) : $base_price;
                    $next_due = (new DateTime('now'))->modify($cycle === 'yearly' ? '+1 year' : '+1 month')->format('Y-m-d');

                    cybercore_services_create($current_user_id, [
                        'domain' => $domain,
                        'plan' => $plan,
                        'billing_cycle' => $cycle,
                        'status' => 'provisioning',
                        'price' => $price,
                        'currency' => 'EUR',
                        'next_due_date' => $next_due,
                    ]);

                    $flash_success = 'Serviço solicitado com sucesso! Iremos ativá-lo brevemente.';
                } else {
                    $flash_error = 'Existem erros no formulário. Verifique os campos.';
                }
            }

            if ($action === 'cancel') {
                $service_id = filter_var($_POST['service_id'] ?? null, FILTER_VALIDATE_INT);
                if (!$service_id) {
                    $flash_error = 'Serviço inválido.';
                } else {
                    $canceled = cybercore_services_cancel($current_user_id, $service_id);
                    $flash_success = $canceled ? 'Serviço cancelado com sucesso.' : 'Não foi possível cancelar este serviço.';
                }
            }
        } catch (PDOException $e) {
            if ((int) $e->getCode() === 23000) {
                $flash_error = 'Já existe um serviço com esse domínio para o seu utilizador.';
            } else {
                error_log('[services] ' . $e->getMessage());
                $flash_error = 'Ocorreu um erro ao processar o pedido. Tente novamente.';
            }
        } catch (Throwable $e) {
            error_log('[services] ' . $e->getMessage());
            $flash_error = 'Ocorreu um erro. Tente novamente.';
        }
    }
}

$services = cybercore_services_list($current_user_id);

require_once __DIR__ . '/includes/layout-top.php';
?>

<section class="panel" id="novo-servico">
  <div class="panel-head">
    <div>
      <p class="panel-kicker">Novo serviço</p>
      <h2>Encomendar alojamento</h2>
    </div>
    <div class="panel-actions">
      <a class="btn btn-ghost" href="#">Ver planos</a>
    </div>
  </div>

  <?php if ($flash_error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($flash_error); ?></div>
  <?php endif; ?>

  <?php if ($flash_success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($flash_success); ?></div>
  <?php endif; ?>

  <form method="POST" action="" class="form">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
    <input type="hidden" name="action" value="order">

    <div class="form-row">
      <div class="form-group">
        <label for="domain">Domínio</label>
        <input type="text" id="domain" name="domain" placeholder="exemplo.pt" value="<?php echo htmlspecialchars($_POST['domain'] ?? ''); ?>" required>
        <?php if (isset($form_errors['domain'])): ?><small class="form-help" style="color: var(--error);"><?php echo htmlspecialchars($form_errors['domain']); ?></small><?php endif; ?>
      </div>
      <div class="form-group">
        <label for="plan">Plano</label>
        <select id="plan" name="plan" required>
          <option value="" disabled <?php echo empty($_POST['plan']) ? 'selected' : ''; ?>>Selecione o plano</option>
          <?php foreach ($plans as $key => $plan): ?>
            <option value="<?php echo htmlspecialchars($key); ?>" <?php echo (($_POST['plan'] ?? '') === $key) ? 'selected' : ''; ?>><?php echo htmlspecialchars($plan['name']); ?> (<?php echo number_format($plan['monthly_price'], 2, ',', '.'); ?> €/mês)</option>
          <?php endforeach; ?>
        </select>
        <?php if (isset($form_errors['plan'])): ?><small class="form-help" style="color: var(--error);"><?php echo htmlspecialchars($form_errors['plan']); ?></small><?php endif; ?>
      </div>
      <div class="form-group">
        <label for="billing_cycle">Ciclo de faturação</label>
        <select id="billing_cycle" name="billing_cycle" required>
          <?php foreach ($cycles as $key => $label): ?>
            <option value="<?php echo htmlspecialchars($key); ?>" <?php echo (($_POST['billing_cycle'] ?? '') === $key) ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
          <?php endforeach; ?>
        </select>
        <?php if (isset($form_errors['billing_cycle'])): ?><small class="form-help" style="color: var(--error);"><?php echo htmlspecialchars($form_errors['billing_cycle']); ?></small><?php endif; ?>
      </div>
    </div>

    <button type="submit" class="btn btn-primary">Encomendar serviço</button>
  </form>
</section>

<section class="panel">
  <div class="panel-head">
    <div>
      <p class="panel-kicker">Serviços</p>
      <h2>Todos os serviços</h2>
    </div>
  </div>

  <div class="table-responsive">
    <?php if (empty($services)): ?>
      <p class="text-muted">Ainda não existem serviços. Encomende o primeiro acima.</p>
    <?php else: ?>
    <table class="table">
      <thead>
        <tr>
          <th>Serviço</th>
          <th>Plano</th>
          <th>Status</th>
          <th>Preço</th>
          <th>Próximo vencimento</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($services as $service): ?>
          <?php
            $planLabel = $plans[$service['plan']]['name'] ?? ucfirst($service['plan']);
            $statusKey = $service['status'];
            $statusInfo = $status_meta[$statusKey] ?? ['label' => ucfirst($statusKey), 'class' => 'badge-info'];
            $price = number_format((float) $service['price'], 2, ',', '.');
            $due = $service['next_due_date'] ? date('d M Y', strtotime($service['next_due_date'])) : '—';
          ?>
          <tr>
            <td><?php echo htmlspecialchars($service['domain']); ?></td>
            <td><?php echo htmlspecialchars($planLabel); ?> (<?php echo htmlspecialchars($cycles[$service['billing_cycle']] ?? $service['billing_cycle']); ?>)</td>
            <td><span class="badge <?php echo htmlspecialchars($statusInfo['class']); ?>"><?php echo htmlspecialchars($statusInfo['label']); ?></span></td>
            <td><?php echo $price; ?> €</td>
            <td><?php echo htmlspecialchars($due); ?></td>
            <td class="table-actions">
              <?php if ($statusKey !== 'canceled'): ?>
                <form method="POST" action="" style="display:inline">
                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                  <input type="hidden" name="action" value="cancel">
                  <input type="hidden" name="service_id" value="<?php echo (int) $service['id']; ?>">
                  <button type="submit" class="btn btn-ghost" onclick="return confirm('Confirmar cancelamento do serviço?');">Cancelar</button>
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
