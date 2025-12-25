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
  <style>
    :root {
      --primary: #123659;
      --primary-light: #1e4a7a;
      --bg-light: #f4f5f7;
      --text-dark: #1e2e3e;
      --text-gray: #5a6c7d;
      --border: #e8e9ed;
      --success: #32a852;
      --error: #c41c3b;
    }

    * {
      box-sizing: border-box;
    }

    html, body {
      margin: 0;
      padding: 0;
      background: #ffffff;
    }

    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif;
      color: var(--text-dark);
    }

    .login-logo {
      text-align: center;
      padding: 20px 0;
      margin-bottom: 20px;
    }

    .login-container {
      display: flex;
      flex-direction: column;
      min-height: 100vh;
      background: var(--bg-light);
    }

    .login-content {
      display: flex;
      flex: 1;
      background: var(--bg-light);
    }

    .login-section {
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      padding: 40px 20px;
    }

    .login-section.login-existing {
      background: #ffffff;
      border-right: 1px solid var(--border);
    }

    .login-section.login-new {
      background: var(--bg-light);
    }

    .login-box {
      width: 100%;
      max-width: 340px;
    }

    .login-box h2 {
      font-size: 18px;
      font-weight: 600;
      color: var(--primary);
      margin: 0 0 24px 0;
      text-align: left;
    }

    .form-group {
      margin-bottom: 18px;
    }

    .form-group label {
      display: block;
      font-size: 13px;
      color: var(--text-dark);
      margin-bottom: 6px;
      font-weight: 500;
      text-transform: capitalize;
    }

    .form-group input,
    .form-group select {
      width: 100%;
      padding: 11px 12px;
      border: 1px solid var(--border);
      border-radius: 3px;
      font-size: 14px;
      color: var(--text-dark);
      font-family: inherit;
      transition: border-color 0.2s, box-shadow 0.2s;
      background: #ffffff;
    }

    .form-group input::placeholder {
      color: #b0b8c1;
    }

    .form-group input:focus,
    .form-group select:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 2px rgba(18, 54, 89, 0.08);
    }

    .form-group input:disabled {
      background: #f9f9f9;
      cursor: not-allowed;
      color: #b0b8c1;
    }

    .form-actions {
      margin-top: 20px;
    }

    .btn-primary {
      width: 100%;
      padding: 11px;
      background: var(--primary);
      color: #ffffff;
      border: none;
      border-radius: 3px;
      font-size: 14px;
      font-weight: 500;
      cursor: pointer;
      transition: background 0.2s;
    }

    .btn-primary:hover {
      background: var(--primary-light);
    }

    .btn-primary:active {
      transform: translateY(1px);
    }

    .login-footer {
      margin-top: 16px;
      text-align: center;
      font-size: 13px;
    }

    .link-section {
      margin-top: 12px;
      text-align: center;
      font-size: 12px;
    }

    .link-section a {
      color: var(--primary);
      text-decoration: none;
      font-weight: 500;
    }

    .link-section a:hover {
      text-decoration: underline;
    }

    .error-message {
      background: #ffefef;
      border: 1px solid #ffcdd2;
      color: var(--error);
      padding: 11px 13px;
      border-radius: 3px;
      margin-bottom: 16px;
      font-size: 12px;
      line-height: 1.5;
    }

    .checkbox-consent {
      margin-bottom: 16px;
      padding-top: 16px;
      border-top: 1px solid var(--border);
    }

    .checkbox-consent a {
      color: var(--primary);
      text-decoration: none;
    }

    .checkbox-consent a:hover {
      text-decoration: underline;
    }

    @media (max-width: 768px) {
      .login-content {
        flex-direction: column;
      }

      .login-section.login-existing {
        border-right: none;
        border-bottom: 1px solid var(--border);
      }

      .login-section.login-existing,
      .login-section.login-new {
        padding: 32px 20px;
      }

      .login-logo {
        position: absolute;
        top: 20px;
        left: 20px;
      }

      .login-container {
        padding-top: 60px;
      }
    }
  </style>
</head>
<body>
<?php if ($maintenanceDisabled): ?>
  <?php renderMaintenanceModal($maintenanceMessage ?: 'O login está temporariamente desativado para clientes.', ['disable_form' => false]); ?>
<?php endif; ?>

<div class="login-container">
  <!-- Logo no topo -->
  <div class="login-logo">
    <svg width="120" height="40" viewBox="0 0 120 40" fill="none">
      <text x="0" y="28" font-family="Arial, sans-serif" font-size="24" font-weight="600" fill="#123659">CyberCore</text>
    </svg>
  </div>

  <div class="login-content">
    <div class="login-section login-existing">
      <div class="login-box">
        <h2>Já sou cliente</h2>
        
        <?php if($error): ?>
          <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="post">
          <?php echo csrf_input(); ?>
          
          <div class="form-group">
            <label>Identificador ou endereço de e-mail</label>
            <input type="email" name="email" required autofocus value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" placeholder="">
          </div>

          <div class="form-group">
            <label>Palavra-passe</label>
            <input type="password" name="password" required placeholder="">
          </div>

          <div class="form-actions">
            <button type="submit" class="btn-primary">Aceder</button>
          </div>

          <div class="link-section">
            <a href="forgot_password.php">Perdeu o identificador ou a palavra-passe?</a>
          </div>
        </form>
      </div>
    </div>

    <div class="login-section login-new">
      <div class="login-box">
        <h2>Sou novo na CyberCore</h2>
        
        <form action="register.php" method="get" style="margin-bottom: 0;">
          <div class="form-group">
            <label>Nome</label>
            <input type="text" placeholder="Seu primeiro nome" disabled style="background: #f9f9f9; cursor: not-allowed;">
          </div>

          <div class="form-group">
            <label>Sobrenome</label>
            <input type="text" placeholder="Seu sobrenome" disabled style="background: #f9f9f9; cursor: not-allowed;">
          </div>

          <div class="form-group">
            <label>E-mail</label>
            <input type="email" placeholder="seu@email.com" disabled style="background: #f9f9f9; cursor: not-allowed;">
          </div>

          <div class="form-group">
            <label>Palavra-passe</label>
            <input type="password" placeholder="••••••••" disabled style="background: #f9f9f9; cursor: not-allowed;">
          </div>

          <div class="checkbox-consent">
            <label style="display: flex; align-items: flex-start; gap: 8px; margin-bottom: 12px;">
              <input type="checkbox" disabled style="margin-top: 2px; cursor: not-allowed;">
              <span style="font-size: 12px; color: var(--text-gray);">Aceito os <a href="#" style="color: var(--primary); text-decoration: none;">Termos e condições</a></span>
            </label>
            <label style="display: flex; align-items: flex-start; gap: 8px;">
              <input type="checkbox" disabled style="margin-top: 2px; cursor: not-allowed;">
              <span style="font-size: 12px; color: var(--text-gray);">Aceito receber e-mails relativos às novidades e ofertas comerciais</span>
            </label>
          </div>

          <button type="submit" class="btn-primary" style="background: var(--success);">Criar uma conta</button>
        </form>
      </div>
    </div>
  </div>
</div>

</body>
</html>
