<?php
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/mailer.php';
require_once __DIR__ . '/inc/csrf.php';
require_once __DIR__ . '/inc/settings.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$token = $_GET['token'] ?? '';
$pdo = getDB();
$maintenanceDisabled = getSetting($pdo, 'maintenance_disable_login', '0') === '1';
$maintenanceMessage = trim(getSetting($pdo, 'maintenance_message', ''));

$row = null;
$message = '';

if ($maintenanceDisabled) {
  $message = $maintenanceMessage ?: 'A redefinição de senha está temporariamente indisponível devido a manutenção.';
} else {
  $stmt = $pdo->prepare('SELECT * FROM password_resets WHERE token = ? AND expires_at >= NOW()');
  $stmt->execute([$token]);
  $row = $stmt->fetch();
  if (!$row) {
    $message = 'Token inválido ou expirado.';
  }
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && $row) {
    csrf_validate();
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';
    if ($password !== $password2) {
      $message = 'As senhas não coincidem.';
    } elseif (strlen($password) < 8) {
      $message = 'A password deve ter pelo menos 8 caracteres.';
    } else {
      $hash = password_hash($password, PASSWORD_DEFAULT);
      $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$hash, $row['user_id']]);
      $pdo->prepare('INSERT INTO logs (user_id,type,message) VALUES (?,?,?)')->execute([$row['user_id'],'password_reset','Password changed via reset link']);
      $pdo->prepare('DELETE FROM password_resets WHERE id = ?')->execute([$row['id']]);
      header('Location: login.php'); exit;
    }
  }
}
?>
<!doctype html>
<html lang="pt">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Redefinir Senha - CyberCore</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php if ($maintenanceDisabled): ?>
  <div class="maintenance-overlay" id="maintenanceOverlay">
    <div class="maintenance-modal">
      <strong>Modo de Manutenção</strong>
      <p style="margin:8px 0 0 0"><?php echo htmlspecialchars($maintenanceMessage ?: 'A redefinição de senha está temporariamente desativada.'); ?></p>
    </div>
  </div>
  <style>
    .maintenance-overlay { position:fixed; inset:0; display:flex; align-items:center; justify-content:center; background:rgba(0,0,0,0.45); z-index:1000; }
    .maintenance-modal { background:#fff3cd; color:#856404; border:1px solid #ffeeba; padding:16px 18px; border-radius:8px; box-shadow:0 12px 30px rgba(0,0,0,0.25); max-width:520px; width:92%; text-align:center; }
  </style>
  <script>
    document.addEventListener('DOMContentLoaded', function(){
      document.querySelectorAll('form input, form button').forEach(function(el){ el.setAttribute('disabled','disabled'); });
    });
  </script>
<?php endif; ?>
<main style="max-width:480px;margin:40px auto">
  <div class="card">
    <h2>Redefinir Senha</h2>
    <?php if($message): ?><div class="card" style="background:#ffefef;color:#900"><?php echo $message; ?></div><?php endif; ?>
    <?php if($row): ?>
    <form method="post">
      <?php echo csrf_input(); ?>
      <div class="form-row"><label>Nova Password</label><input type="password" name="password" required></div>
      <div class="form-row"><label>Confirmar Password</label><input type="password" name="password2" required></div>
      <div class="form-row"><button class="btn">Alterar Password</button></div>
    </form>
    <?php endif; ?>
  </div>
</main>
</body>
</html>
