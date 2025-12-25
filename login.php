<?php
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/csrf.php';
require_once __DIR__ . '/inc/settings.php';
require_once __DIR__ . '/inc/debug.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if (isset($_SESSION['user_id'])) {
  header('Location: dashboard.php');
  exit;
}

$pdo = getDB();
$siteLogo = getSetting($pdo, 'site_logo');
$errors = [];
$maxAttempts = 5;
$lockoutTime = 600;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_validate();
  
  $emailOrId = $_POST['email'] ?? '';
  $password = $_POST['password'] ?? '';
  
  $clientIP = $_SERVER['REMOTE_ADDR'];
  $key = 'login_attempts_' . md5($clientIP . $emailOrId);
  $lockKey = 'login_lockout_' . md5($clientIP . $emailOrId);
  
  if (apcu_exists($lockKey)) {
    $errors[] = 'Conta bloqueada temporariamente. Tente novamente em 10 minutos.';
  } elseif (apcu_exists($key) && apcu_fetch($key) >= $maxAttempts) {
    $errors[] = 'Demasiadas tentativas falhadas. Conta bloqueada por 10 minutos.';
    apcu_store($lockKey, true, $lockoutTime);
  } else {
    $attempts = apcu_fetch($key) ?? 0;
    
    if (empty($errors)) {
      // Permitir login com email OU identificador (CYC#...)
      if (strpos($emailOrId, 'CYC#') === 0) {
        // Login com identificador
        $stmt = $pdo->prepare('SELECT * FROM users WHERE identifier = ?');
        $stmt->execute([$emailOrId]);
      } else {
        // Login com email
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$emailOrId]);
      }
      $user = $stmt->fetch();
      
      if ($user && password_verify($password, $user['password_hash'])) {
        apcu_delete($key);
        
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
        apcu_store($key, $attempts + 1, 600);
      }
    }
  }
}
?><!doctype html>
<html lang="pt-PT">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Login - CyberCore</title>
  <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    html, body {
      font-family: "Source Sans 3", -apple-system, BlinkMacSystemFont, "Segoe UI", Helvetica, Arial, sans-serif;
      background: #fff;
      color: #333;
      line-height: 1.6;
      min-height: 100vh;
    }

    body {
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }

    .login-wrapper {
      width: 100%;
      max-width: 900px;
    }

    .login-header {
      text-align: center;
      margin-bottom: 50px;
    }

    .login-logo {
      height: 50px;
      margin-bottom: 30px;
    }

    .login-logo img {
      height: 50px;
      width: auto;
      object-fit: contain;
    }

    .login-logo svg {
      height: 50px;
      width: auto;
    }

    .login-title {
      font-size: 32px;
      font-weight: 600;
      color: #000;
      margin-bottom: 10px;
    }

    .login-subtitle {
      font-size: 14px;
      color: #666;
    }

    .login-container {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 60px;
      margin-top: 40px;
    }

    .login-column {
      display: flex;
      flex-direction: column;
    }

    .login-section-title {
      font-size: 16px;
      font-weight: 600;
      color: #000;
      margin-bottom: 20px;
    }

    .login-section-subtitle {
      font-size: 13px;
      color: #666;
      margin-bottom: 20px;
      line-height: 1.5;
    }

    .form-group {
      margin-bottom: 16px;
    }

    .form-group label {
      display: block;
      font-size: 12px;
      font-weight: 500;
      color: #333;
      margin-bottom: 6px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .form-group input {
      width: 100%;
      padding: 12px;
      font-size: 14px;
      border: 1px solid #ddd;
      border-radius: 2px;
      font-family: "Source Sans 3", sans-serif;
      transition: all 0.2s ease;
    }

    .form-group input:focus {
      outline: none;
      border-color: #007dff;
      box-shadow: 0 0 0 3px rgba(0, 125, 255, 0.1);
    }

    .form-group input::placeholder {
      color: #999;
    }

    .form-actions {
      display: flex;
      gap: 10px;
      margin-top: 24px;
      margin-bottom: 20px;
    }

    .btn {
      flex: 1;
      padding: 12px 24px;
      font-size: 14px;
      font-weight: 600;
      border: none;
      border-radius: 2px;
      cursor: pointer;
      transition: all 0.2s ease;
      text-decoration: none;
      text-align: center;
      display: inline-block;
      font-family: "Source Sans 3", sans-serif;
    }

    .btn-primary {
      background: #007dff;
      color: #fff;
    }

    .btn-primary:hover {
      background: #0066cc;
      box-shadow: 0 2px 8px rgba(0, 125, 255, 0.3);
    }

    .btn-primary:active {
      background: #0052a3;
    }

    .btn-secondary {
      background: #f5f5f5;
      color: #333;
      border: 1px solid #ddd;
    }

    .btn-secondary:hover {
      background: #efefef;
    }

    .forgot-password {
      text-align: right;
    }

    .forgot-password a {
      font-size: 12px;
      color: #007dff;
      text-decoration: none;
      transition: color 0.2s;
    }

    .forgot-password a:hover {
      color: #0066cc;
      text-decoration: underline;
    }

    .error-box {
      background: #fff3cd;
      border: 1px solid #ffc107;
      color: #856404;
      padding: 12px 16px;
      border-radius: 2px;
      margin-bottom: 20px;
      font-size: 13px;
      line-height: 1.5;
    }

    .error-box ul {
      margin: 0;
      padding-left: 20px;
    }

    .error-box li {
      margin-bottom: 4px;
    }

    .divider {
      text-align: center;
      margin: 30px 0;
      position: relative;
    }

    .divider::before {
      content: '';
      position: absolute;
      left: 0;
      top: 50%;
      width: 100%;
      height: 1px;
      background: #eee;
    }

    .divider span {
      background: #fff;
      padding: 0 10px;
      position: relative;
      color: #666;
      font-size: 12px;
    }

    .register-info {
      background: #f9f9f9;
      padding: 24px;
      border-radius: 2px;
      border: 1px solid #f0f0f0;
    }

    .register-info-title {
      font-size: 16px;
      font-weight: 600;
      color: #000;
      margin-bottom: 12px;
    }

    .register-info-text {
      font-size: 13px;
      color: #666;
      line-height: 1.6;
      margin-bottom: 16px;
    }

    .register-benefits {
      list-style: none;
      margin-bottom: 16px;
    }

    .register-benefits li {
      font-size: 13px;
      color: #666;
      padding-left: 20px;
      position: relative;
      margin-bottom: 8px;
    }

    .register-benefits li::before {
      content: '✓';
      position: absolute;
      left: 0;
      color: #007dff;
      font-weight: bold;
    }

    .register-button {
      width: 100%;
      padding: 12px 24px;
      background: #007dff;
      color: #fff;
      border: none;
      border-radius: 2px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      font-family: "Source Sans 3", sans-serif;
      transition: all 0.2s ease;
      text-decoration: none;
      display: block;
      text-align: center;
    }

    .register-button:hover {
      background: #0066cc;
      box-shadow: 0 2px 8px rgba(0, 125, 255, 0.3);
    }

    .footer-text {
      text-align: center;
      font-size: 11px;
      color: #999;
      margin-top: 30px;
      padding-top: 20px;
      border-top: 1px solid #f0f0f0;
    }

    .footer-text a {
      color: #007dff;
      text-decoration: none;
    }

    .footer-text a:hover {
      text-decoration: underline;
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
      .login-container {
        grid-template-columns: 1fr;
        gap: 40px;
      }

      .login-title {
        font-size: 24px;
      }

      .form-actions {
        flex-direction: column;
      }

      .btn {
        width: 100%;
      }

      .forgot-password {
        text-align: left;
        margin-top: 10px;
      }

      body {
        padding: 20px;
      }

      .login-wrapper {
        max-width: 100%;
      }
    }
  </style>
</head>
<body>

<div class="login-wrapper">
  <div class="login-header">
    <div class="login-logo">
      <?php if ($siteLogo && getAssetPath($siteLogo) && file_exists(getAssetPath($siteLogo))): ?>
        <img src="<?php echo htmlspecialchars(getAssetUrl($siteLogo)); ?>?v=<?php echo time(); ?>" alt="Logo">
      <?php else: ?>
        <svg viewBox="0 0 200 50" fill="none" xmlns="http://www.w3.org/2000/svg">
          <text x="10" y="35" font-family="Source Sans 3, sans-serif" font-size="28" font-weight="600" fill="#000">CyberCore</text>
        </svg>
      <?php endif; ?>
    </div>
    <h1 class="login-title">Bem-vindo</h1>
    <p class="login-subtitle">Aceda à sua conta CyberCore</p>
  </div>

  <div class="login-container">
    <!-- Coluna 1: Login -->
    <div class="login-column">
      <h2 class="login-section-title">Já tem conta?</h2>
      <p class="login-section-subtitle">Faça login com o seu email e password para aceder à sua conta.</p>

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

        <div class="form-group">
          <label for="email">Email ou Identificador</label>
          <input type="text" id="email" name="email" required placeholder="seu@email.com ou CYC#12345">
        </div>

        <div class="form-group">
          <label for="password">Palavra-passe</label>
          <input type="password" id="password" name="password" required placeholder="••••••••">
        </div>

        <div class="forgot-password">
          <a href="forgot_password.php">Esqueceu a palavra-passe?</a>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Entrar</button>
        </div>
      </form>
    </div>

    <!-- Coluna 2: Registo -->
    <div class="login-column">
      <div class="register-info">
        <h2 class="register-info-title">Novo cliente?</h2>
        <p class="register-info-text">Crie uma conta para aceder a todos os serviços da CyberCore.</p>
        
        <ul class="register-benefits">
          <li>Gestão de domínios e alojamento</li>
          <li>Suporte técnico 24/7</li>
          <li>Faturação centralizada</li>
          <li>Painel de controlo completo</li>
        </ul>

          <a href="register.php" class="register-button">Criar conta</a>

        <div class="divider">
          <span>ou</span>
        </div>

        <p style="font-size: 11px; color: #999; text-align: center;">
          Já tem acesso? Recupere a sua conta usando o botão de recuperação de password.
        </p>
      </div>
    </div>
  </div>

  <div class="footer-text">
    <p>
      © 2025 CyberCore. Todos os direitos reservados. | 
      <a href="#">Privacidade</a> | 
      <a href="#">Termos de Serviço</a> | 
      <a href="#">Contacte-nos</a>
    </p>
  </div>
</div>

</body>
</html>
