<?php
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/csrf.php';
require_once __DIR__ . '/inc/settings.php';
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
    $stmt = $pdo->prepare('SELECT id, role, password_hash FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $u = $stmt->fetch();
    if ($u && password_verify($password, $u['password_hash'])) {
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
  <div class="maintenance-overlay" id="maintenanceOverlay">
    <div class="maintenance-modal">
      <strong>Modo de Manutenção</strong>
      <p style="margin:8px 0 0 0"><?php echo htmlspecialchars($maintenanceMessage ?: 'O login está temporariamente desativado para clientes.'); ?></p>
      <p class="small" style="margin:8px 0 0 0">Se for da equipa autorizada (ex.: <?php echo htmlspecialchars(implode(', ', $maintenanceExceptions)); ?>), pode fechar este aviso e iniciar sessão.</p>
      <button type="button" class="btn" style="margin-top:12px" onclick="document.getElementById('maintenanceOverlay').style.display='none';">Continuar</button>
    </div>
  </div>
  <style>
    .maintenance-overlay { position:fixed; inset:0; display:flex; align-items:center; justify-content:center; background:rgba(0,0,0,0.45); z-index:1000; }
    .maintenance-modal { background:#fff3cd; color:#856404; border:1px solid #ffeeba; padding:16px 18px; border-radius:8px; box-shadow:0 12px 30px rgba(0,0,0,0.25); max-width:520px; width:92%; }
  </style>
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
