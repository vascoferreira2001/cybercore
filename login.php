<?php
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/csrf.php';
require_once __DIR__ . '/inc/settings.php';
require_once __DIR__ . '/inc/debug.php';
require_once __DIR__ . '/inc/auth_theme.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if (isset($_SESSION['user_id'])) {
  header('Location: dashboard.php');
  exit;
}

$pdo = getDB();
$theme = loadAuthTheme($pdo);
$errors = [];
$maxAttempts = 5;
$lockoutTime = 600;
$cacheAvailable = function_exists('apcu_fetch') && function_exists('apcu_store') && function_exists('apcu_exists') && function_exists('apcu_delete') && filter_var(ini_get('apc.enabled'), FILTER_VALIDATE_BOOLEAN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_validate();
  
  $emailOrId = $_POST['email'] ?? '';
  $password = $_POST['password'] ?? '';
  
  $clientIP = $_SERVER['REMOTE_ADDR'];
  $key = 'login_attempts_' . md5($clientIP . $emailOrId);
  $lockKey = 'login_lockout_' . md5($clientIP . $emailOrId);
  
  if ($cacheAvailable && apcu_exists($lockKey)) {
    $errors[] = 'Conta bloqueada temporariamente. Tente novamente em 10 minutos.';
  } elseif ($cacheAvailable && apcu_exists($key) && apcu_fetch($key) >= $maxAttempts) {
    $errors[] = 'Demasiadas tentativas falhadas. Conta bloqueada por 10 minutos.';
    apcu_store($lockKey, true, $lockoutTime);
  } else {
    $attempts = $cacheAvailable ? (apcu_fetch($key) ?? 0) : 0;
    
    if (empty($errors)) {
      if (strpos($emailOrId, 'CYC#') === 0) {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE identifier = ?');
        $stmt->execute([$emailOrId]);
      } else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$emailOrId]);
      }
      $user = $stmt->fetch();
      
      if ($user && password_verify($password, $user['password_hash'])) {
        if ($cacheAvailable) apcu_delete($key);
        
        if ($user['email_verified'] == 0 && $user['role'] === 'Cliente') {
          $_SESSION['pending_email_verification'] = $user['email'];
          header('Location: verify_email.php?step=resend');
          exit;
        }
        
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['login_time'] = time();
        
        $pdo->prepare('INSERT INTO logs (user_id,type,message) VALUES (?,?,?)')->execute([$user['id'],'login','User logged in']);
        
        header('Location: dashboard.php');
        exit;
      } else {
        $errors[] = 'Email ou palavra-passe incorretos.';
        if ($cacheAvailable) apcu_store($key, $attempts + 1, 600);
      }
    }
  }
}
?>
<!doctype html>
<html lang="pt-PT">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Login - CyberCore</title>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/auth/auth-modern.css">
</head>
<body class="auth" <?php echo $theme['backgroundStyle'] ? 'style="' . $theme['backgroundStyle'] . '"' : ''; ?>>

<div class="shell">
  <div class="hero">
    <div>
      <div class="hero-header">
        <div class="hero-logo"><?php renderAuthLogo($theme['logoUrl']); ?></div>
      </div>

      <p style="text-transform: uppercase; letter-spacing: 0.12em; font-size: 11px; color: var(--accent-2); font-weight: 700;">Aceda ao seu painel</p>
      <h1 class="hero-title">Bem-vindo(a) à CyberCore</h1>
      <p class="hero-subtitle">Faça login com o email ou o seu identificador para gerir os seus serviços, faturação e pedir suporte num só Painel.</p>

      <ul class="hero-list">
        <li><span class="check">✓</span> Login seguro com email ou CYC#ID</li>
        <li><span class="check">✓</span> Autenticação rápida com sessão renovada</li>
        <li><span class="check">✓</span> Monitorização de segurança e atividade</li>
      </ul>
    </div>
  </div>

  <div class="panel">
    <div class="panel-header">
      <h1>Entrar</h1>
      <p>Use o seu email ou identificador para continuar.</p>
    </div>

    <?php if (!empty($errors)): ?>
      <div class="error-box">
        <ul>
          <?php foreach ($errors as $error): ?>
            <li><?php echo htmlspecialchars($error); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post">
      <?php echo csrf_input(); ?>
      <div>
        <label for="email">Email ou identificador</label>
        <input class="input" type="text" id="email" name="email" required placeholder="ex: nome@dominio.com ou CYC#00001" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
      </div>
      <div>
        <label for="password">Palavra-passe</label>
        <input class="input" type="password" id="password" name="password" required placeholder="••••••••">
      </div>

      <div class="form-footer">
        <span></span>
        <a class="link" href="forgot_password.php">Esqueceu a password?</a>
      </div>

      <div class="cta">
        <button class="btn btn-primary" type="submit">Entrar</button>
        <div class="divider">ou</div>
        <a class="btn btn-primary" href="register.php" style="text-align:center;">Criar conta</a>
      </div>
    </form>

    <div class="footer">
      © 2025 CyberCore · <a href="#">Privacidade</a> · <a href="#">Termos</a> · <a href="#">Contacto</a>
    </div>
  </div>
</div>

</body>
</html>
