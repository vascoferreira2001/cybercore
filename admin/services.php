<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/admin_auth.php';
require_once __DIR__ . '/../inc/services.php';

cybercore_require_admin();

$page_title = 'Serviços | Admin Panel';
$page_heading = 'Gestão de Serviços';
$active_menu = 'services';

$flash_success = '';
$flash_error = '';

$pdo = cybercore_pdo();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $flash_error = 'Token de segurança inválido.';
    } else {
        $action = $_POST['action'] ?? '';
        $service_id = filter_var($_POST['service_id'] ?? null, FILTER_VALIDATE_INT);
        
        if ($action === 'activate' && $service_id) {
            $stmt = $pdo->prepare('UPDATE services SET status = "active" WHERE id = :id');
            $stmt->execute(['id' => $service_id]);
            $flash_success = 'Serviço ativado.';
        }
        
        if ($action === 'suspend' && $service_id) {
            $stmt = $pdo->prepare('UPDATE services SET status = "suspended" WHERE id = :id');
            $stmt->execute(['id' => $service_id]);
            $flash_success = 'Serviço suspenso.';
        }
    }
}

$services = $pdo->query('SELECT s.*, u.identifier, u.email, u.first_name, u.last_name FROM services s JOIN users u ON s.user_id = u.id ORDER BY s.created_at DESC')->fetchAll();

require_once __DIR__ . '/includes/layout-top.php';
?>

<?php if ($flash_error): ?><div class="alert alert-error"><?php echo htmlspecialchars($flash_error); ?></div><?php endif; ?>
<?php if ($flash_success): ?><div class="alert alert-success"><?php echo htmlspecialchars($flash_success); ?></div><?php endif; ?>

<section class="panel">
  <div class="panel-head">
    <div>
      <p class="panel-kicker">Serviços</p>
      <h2>Todos os serviços</h2>
    </div>
    <div class="panel-actions">
      <a class="btn btn-ghost" href="#">Filtros</a>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Cliente</th>
          <th>Domínio</th>
          <th>Plano</th>
          <th>Preço</th>
          <th>Status</th>
          <th>Criado</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($services as $service): ?>
          <tr>
            <td><?php echo htmlspecialchars($service['identifier']); ?> - <?php echo htmlspecialchars($service['first_name']); ?></td>
            <td><?php echo htmlspecialchars($service['domain']); ?></td>
            <td><?php echo htmlspecialchars($service['plan']); ?></td>
            <td><?php echo number_format((float) $service['price'], 2, ',', '.'); ?> €</td>
            <td>
              <?php
                $status_class = [
                  'active' => 'badge-success',
                  'provisioning' => 'badge-warning',
                  'suspended' => 'badge-error',
                  'canceled' => 'badge-error'
                ];
                $class = $status_class[$service['status']] ?? 'badge-info';
              ?>
              <span class="badge <?php echo $class; ?>"><?php echo htmlspecialchars($service['status']); ?></span>
            </td>
            <td><?php echo date('d/m/Y', strtotime($service['created_at'])); ?></td>
            <td class="table-actions">
              <?php if ($service['status'] === 'provisioning'): ?>
                <form method="POST" action="" style="display:inline">
                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                  <input type="hidden" name="action" value="activate">
                  <input type="hidden" name="service_id" value="<?php echo (int) $service['id']; ?>">
                  <button type="submit" class="btn btn-ghost">Ativar</button>
                </form>
              <?php endif; ?>
              <?php if ($service['status'] === 'active'): ?>
                <form method="POST" action="" style="display:inline">
                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                  <input type="hidden" name="action" value="suspend">
                  <input type="hidden" name="service_id" value="<?php echo (int) $service['id']; ?>">
                  <button type="submit" class="btn btn-ghost">Suspender</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<?php require_once __DIR__ . '/includes/layout-bottom.php'; ?>
