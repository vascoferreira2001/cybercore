<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/admin_auth.php';

cybercore_require_admin();

$page_title = 'Utilizadores | Admin Panel';
$page_heading = 'Gestão de Utilizadores';
$active_menu = 'users';

$flash_success = '';
$flash_error = '';

$pdo = cybercore_pdo();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $flash_error = 'Token de segurança inválido.';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'verify_email') {
            $user_id = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
            if ($user_id) {
                $stmt = $pdo->prepare('UPDATE users SET email_verified = 1, email_verification_token = NULL WHERE id = :id');
                $stmt->execute(['id' => $user_id]);
                $flash_success = 'Email verificado manualmente.';
            }
        }
    }
}

$users = $pdo->query('SELECT id, identifier, email, first_name, last_name, role, email_verified, created_at FROM users ORDER BY created_at DESC')->fetchAll();

require_once __DIR__ . '/includes/layout-top.php';
?>

<?php if ($flash_error): ?><div class="alert alert-error"><?php echo htmlspecialchars($flash_error); ?></div><?php endif; ?>
<?php if ($flash_success): ?><div class="alert alert-success"><?php echo htmlspecialchars($flash_success); ?></div><?php endif; ?>

<section class="panel">
  <div class="panel-head">
    <div>
      <p class="panel-kicker">Utilizadores</p>
      <h2>Todos os utilizadores</h2>
    </div>
    <div class="panel-actions">
      <a class="btn btn-ghost" href="#">Exportar</a>
      <a class="btn btn-primary" href="#">Novo utilizador</a>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nome</th>
          <th>Email</th>
          <th>Role</th>
          <th>Verificado</th>
          <th>Registado</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $user): ?>
          <tr>
            <td><?php echo htmlspecialchars($user['identifier']); ?></td>
            <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
            <td><?php echo htmlspecialchars($user['email']); ?></td>
            <td><span class="badge badge-info"><?php echo htmlspecialchars($user['role']); ?></span></td>
            <td>
              <?php if ($user['email_verified']): ?>
                <span class="badge badge-success">✓</span>
              <?php else: ?>
                <form method="POST" action="" style="display:inline">
                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                  <input type="hidden" name="action" value="verify_email">
                  <input type="hidden" name="user_id" value="<?php echo (int) $user['id']; ?>">
                  <button type="submit" class="badge badge-warning" style="border:none;cursor:pointer">Verificar</button>
                </form>
              <?php endif; ?>
            </td>
            <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
            <td><a class="link" href="#">Editar</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<?php require_once __DIR__ . '/includes/layout-bottom.php'; ?>
