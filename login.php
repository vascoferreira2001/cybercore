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
$siteLogo = getSetting($pdo, 'site_logo');

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
?>
<!doctype html>
<html lang="pt">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Login - CyberCore</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    html, body {
      height: 100%;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "Helvetica Neue", Helvetica, Arial, sans-serif;
      font-size: 14px;
      color: #333333;
      background: #fafafa;
    }

    body {
      display: flex;
      flex-direction: column;
    }

    .auth-container {
      display: flex;
      flex: 1;
    }

    .auth-column {
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      padding: 40px 20px;
    }

    .auth-column.existing {
      background: #ffffff;
      border-right: 1px solid #eeeeee;
    }

    .auth-column.new {
      background: #fafafa;
    }

    .auth-box {
      width: 100%;
      max-width: 350px;
    }

    .auth-logo {
      text-align: center;
      margin-bottom: 30px;
    }

    .auth-logo img {
      height: 40px;
      width: auto;
      object-fit: contain;
    }

    .auth-logo svg {
      height: 40px;
      width: auto;
    }

    .auth-box h2 {
      font-size: 16px;
      font-weight: 600;
      color: #000000;
      margin-bottom: 20px;
      text-align: left;
    }

    .form-group {
      margin-bottom: 16px;
    }

    .form-group label {
      display: block;
      font-size: 13px;
      color: #333333;
      margin-bottom: 6px;
      font-weight: 500;
    }

    .form-group input,
    .form-group select {
      width: 100%;
      padding: 10px 12px;
      border: 1px solid #d9d9d9;
      border-radius: 2px;
      font-size: 13px;
      color: #333333;
      font-family: inherit;
      transition: border-color 0.2s, box-shadow 0.2s;
      background: #ffffff;
    }

    .form-group input::placeholder {
      color: #999999;
    }

    .form-group input:focus,
    .form-group select:focus {
      outline: none;
      border-color: #ff6600;
      box-shadow: 0 0 0 1px rgba(255, 102, 0, 0.2);
    }

    .form-group input:disabled {
      background: #f5f5f5;
      cursor: not-allowed;
      color: #999999;
    }

    .form-actions {
      margin-top: 20px;
    }

    .btn-login {
      width: 100%;
      padding: 10px;
      background: #000000;
      color: #ffffff;
      border: none;
      border-radius: 2px;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.2s;
    }

    .btn-login:hover {
      background: #333333;
    }

    .btn-register {
      width: 100%;
      padding: 10px;
      background: #ff6600;
      color: #ffffff;
      border: none;
      border-radius: 2px;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.2s;
    }

    .btn-register:hover {
      background: #e55a00;
    }

    .form-footer {
      margin-top: 14px;
      text-align: center;
      font-size: 12px;
    }

    .form-footer a {
      color: #0066cc;
      text-decoration: none;
    }

    .form-footer a:hover {
      text-decoration: underline;
    }

    .error-message {
      background: #fff4f0;
      border: 1px solid #ffcccc;
      color: #cc3300;
      padding: 10px 12px;
      border-radius: 2px;
      margin-bottom: 16px;
      font-size: 12px;
      line-height: 1.5;
    }

    .checkbox-group {
      margin-top: 14px;
      padding-top: 14px;
      border-top: 1px solid #eeeeee;
    }

    .checkbox-item {
      display: flex;
      align-items: flex-start;
      margin-bottom: 8px;
    }

    .checkbox-item input[type="checkbox"] {
      width: auto;
      height: 14px;
      margin-right: 6px;
      margin-top: 1px;
      cursor: pointer;
    }

    .checkbox-item label {
      margin: 0;
      font-size: 12px;
      color: #666666;
      cursor: pointer;
      line-height: 1.4;
    }

    .checkbox-item a {
      color: #0066cc;
      text-decoration: none;
    }

    .checkbox-item a:hover {
      text-decoration: underline;
    }

    .benefits {
      font-size: 12px;
      color: #666666;
      line-height: 1.6;
      margin-top: 16px;
    }

    .benefits strong {
      display: block;
      color: #000000;
      margin-bottom: 6px;
    }

    @media (max-width: 768px) {
      .auth-container {
        flex-direction: column;
      }

      .auth-column.existing {
        border-right: none;
        border-bottom: 1px solid #eeeeee;
      }

      .auth-column {
        padding: 30px 20px;
      }
    }
  </style>
</head>
<body>
<?php if ($maintenanceDisabled): ?>
  <?php renderMaintenanceModal($maintenanceMessage ?: 'O login está temporariamente desativado para clientes.', ['disable_form' => false]); ?>
<?php endif; ?>

<div class="auth-container">
  <div class="auth-column existing">
    <div class="auth-box">
      <div class="auth-logo">
        <?php if ($siteLogo && getAssetPath($siteLogo) && file_exists(getAssetPath($siteLogo))): ?>
          <img src="<?php echo htmlspecialchars(getAssetUrl($siteLogo)); ?>?v=<?php echo time(); ?>" alt="Logo">
        <?php else: ?>
          <svg width="100" height="40" viewBox="0 0 100 40" fill="none">
            <text x="0" y="28" font-family="Arial, sans-serif" font-size="20" font-weight="700" fill="#000000">CyberCore</text>
          </svg>
        <?php endif; ?>
      </div>

      <h2>Já sou cliente</h2>

      <?php if($error): ?>
        <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <form method="post">
        <?php echo csrf_input(); ?>

        <div class="form-group">
          <label>Identificador ou endereço de e-mail</label>
          <input type="email" name="email" required autofocus value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        </div>

        <div class="form-group">
          <label>Palavra-passe</label>
          <input type="password" name="password" required>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn-login">Aceder</button>
        </div>

        <div class="form-footer">
          <a href="forgot_password.php">Perdeu o identificador ou a palavra-passe?</a>
        </div>
      </form>
    </div>
  </div>

  <div class="auth-column new">
    <div class="auth-box">
      <h2>Sou novo</h2>

      <form action="register.php" method="get">
        <div class="form-group">
          <label>Nome</label>
          <input type="text" placeholder="Seu primeiro nome" disabled>
        </div>

        <div class="form-group">
          <label>Sobrenome</label>
          <input type="text" placeholder="Seu sobrenome" disabled>
        </div>

        <div class="form-group">
          <label>E-mail</label>
          <input type="email" placeholder="seu@email.com" disabled>
        </div>

        <div class="form-group">
          <label>Palavra-passe</label>
          <input type="password" placeholder="••••••••" disabled>
        </div>

        <div class="checkbox-group">
          <div class="checkbox-item">
            <input type="checkbox" disabled>
            <label>Aceito os <a href="#">Termos e condições</a></label>
          </div>
          <div class="checkbox-item">
            <label>
              <input type="checkbox" disabled style="margin-right: 6px;">
              Aceito receber e-mails relativos às novidades e ofertas comerciais
            </label>
          </div>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn-register">Criar uma conta</button>
        </div>
      </form>

      <div class="benefits">
        <strong>Benefícios:</strong>
        ✓ Sem taxas de configuração<br>
        ✓ Suporte 24/7 em português<br>
        ✓ Controlo total dos seus serviços
      </div>
    </div>
  </div>
</div>

</body>
</html>
