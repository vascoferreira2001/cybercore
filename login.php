<?php
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/csrf.php';
require_once __DIR__ . '/inc/settings.php';
require_once __DIR__ . '/inc/maintenance.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$pdo = getDB();
$maintenanceDisabled = getSetting($pdo, 'maintenance_disable_login', '0') === '1';
$maintenanceMessage = trim(getSetting($pdo, 'maintenance_message', ''));
$maintenanceExceptionsRaw = getSetting($pdo, 'maintenance_exception_roles', 'Gestor');
$maintenanceExceptions = array_filter(array_map('trim', explode(',', $maintenanceExceptionsRaw)));
if (empty($maintenanceExceptions)) {
  $maintenanceExceptions = ['Gestor'];
}

$error = '';
// Basic rate limiting: max 5 attempts per 10 minutes per session
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_validate();
  $now = time();
  $attempts = $_SESSION['login_attempts'] ?? [];
  // keep only recent attempts
  $attempts = array_filter($attempts, function($t) use ($now){ return $t > ($now - 600); });
  if (count($attempts) >= 5) {
    $error = 'Demasiadas tentativas. Tente novamente mais tarde.';
  } else {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $stmt = $pdo->prepare('SELECT id, role, password_hash, email_verified FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $u = $stmt->fetch();
    if ($u && password_verify($password, $u['password_hash'])) {
      // Verificar se o email foi verificado (exceto para staff)
      $isStaff = in_array($u['role'], ['Gestor', 'Suporte ao Cliente', 'Suporte Técnica', 'Suporte Financeira'], true);
      if (!$isStaff && !$u['email_verified']) {
        $error = 'Por favor, verifique o seu email antes de fazer login. Verifique a sua caixa de entrada e pasta de spam.';
        $pdo->prepare('INSERT INTO logs (user_id,type,message) VALUES (?,?,?)')->execute([$u['id'],'login_blocked','Login bloqueado - email não verificado']);
      } else {
        $isException = in_array($u['role'], $maintenanceExceptions, true);
        if ($maintenanceDisabled && !$isException) {
          $error = $maintenanceMessage ?: 'Login temporariamente desativado devido a manutenção.';
          $pdo->prepare('INSERT INTO logs (user_id,type,message) VALUES (?,?,?)')->execute([$u['id'],'login_blocked','Login bloqueado por manutenção']);
        } else {
          session_regenerate_id(true);
          $_SESSION['user_id'] = $u['id'];
          $_SESSION['role'] = $u['role'];
          $_SESSION['login_attempts'] = []; // reset attempts on success
          $pdo->prepare('INSERT INTO logs (user_id,type,message) VALUES (?,?,?)')->execute([$u['id'],'login','User logged in']);
          header('Location: dashboard.php');
          exit;
        }
      }
    } else {
      $attempts[] = $now;
      $_SESSION['login_attempts'] = $attempts;
      $error = 'Credenciais inválidas.';
    }
  }
}

// Carregar background
$loginBackground = getSetting($pdo, 'login_background');
$backgroundUrl = getAssetUrl($loginBackground);
$backgroundPath = getAssetPath($loginBackground);
$backgroundStyle = '';
if ($loginBackground && file_exists($backgroundPath)) {
  $backgroundStyle = 'background-image:url(' . htmlspecialchars($backgroundUrl) . '?v=' . time() . ');background-size:cover;background-position:center;background-attachment:fixed';
}
?>
<!doctype html>
<html lang="pt">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Login - CyberCore</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body style="<?php echo $backgroundStyle; ?>">
<?php if ($maintenanceDisabled): ?>
  <?php renderMaintenanceModal($maintenanceMessage ?: 'O login está temporariamente desativado para clientes.', ['disable_form' => false]); ?>
<?php endif; ?>
<main style="max-width:480px;margin:40px auto">
  <div class="card">
    <h2>Login</h2>
    <?php if($error): ?><div class="card" style="background:#ffefef;color:#900"><?php echo $error; ?></div><?php endif; ?>
    <form method="post">
      <?php echo csrf_input(); ?>
      <div class="form-row"><label>Email</label><input type="email" name="email" required></div>
      <div class="form-row"><label>Senha</label><input type="password" name="password" required></div>
      <div class="form-row"><button class="btn">Entrar</button></div>
    </form>
    <div class="small"><a href="forgot_password.php">Esqueci a senha</a> · <a href="register.php">Criar conta</a></div>
  </div>
</main>
</body>
</html>
