<?php
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/csrf.php';
require_once __DIR__ . '/inc/settings.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$siteLogo = getSetting(getDB(), 'site_logo');
$errors = [];
$step1Data = ['first_name' => '', 'last_name' => '', 'email' => '', 'password' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_validate();
  
  $first = trim($_POST['first_name'] ?? '');
  $last = trim($_POST['last_name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';

  if (!$first) $errors[] = 'Nome é obrigatório.';
  if (!$last) $errors[] = 'Sobrenome é obrigatório.';
  if (!$email) $errors[] = 'Email é obrigatório.';
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email inválido.';
  if (strlen($password) < 8) $errors[] = 'A password deve ter pelo menos 8 caracteres.';

  if (empty($errors)) {
    // Guardar dados em session para próximo passo
    $_SESSION['register_step1'] = [
      'first_name' => $first,
      'last_name' => $last,
      'email' => $email,
      'password' => $password
    ];
    header('Location: register-step2.php');
    exit;
  } else {
    $step1Data = ['first_name' => $first, 'last_name' => $last, 'email' => $email, 'password' => ''];
  }
}
?>
<!doctype html>
<html lang="pt">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Criar Conta - Passo 1 - CyberCore</title>
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
      justify-content: center;
      align-items: center;
      padding: 20px;
    }

    .register-container {
      width: 100%;
      max-width: 400px;
      background: #ffffff;
      padding: 40px;
      border-radius: 2px;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .register-logo {
      text-align: center;
      margin-bottom: 30px;
    }

    .register-logo img {
      height: 40px;
      width: auto;
      object-fit: contain;
    }

    .register-logo svg {
      height: 40px;
      width: auto;
    }

    .register-header h1 {
      font-size: 20px;
      font-weight: 600;
      color: #000000;
      margin-bottom: 8px;
    }

    .register-header p {
      font-size: 12px;
      color: #666666;
      margin: 0;
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

    .form-group input {
      width: 100%;
      padding: 10px 12px;
      border: 1px solid #d9d9d9;
      border-radius: 2px;
      font-size: 13px;
      color: #333333;
      font-family: inherit;
      transition: border-color 0.2s, box-shadow 0.2s;
    }

    .form-group input:focus {
      outline: none;
      border-color: #ff6600;
      box-shadow: 0 0 0 1px rgba(255, 102, 0, 0.2);
    }

    .form-actions {
      margin-top: 24px;
    }

    .btn {
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

    .btn:hover {
      background: #e55a00;
    }

    .error-message {
      background: #fff4f0;
      border: 1px solid #ffcccc;
      color: #cc3300;
      padding: 10px 12px;
      border-radius: 2px;
      margin-bottom: 16px;
      font-size: 12px;
      line-height: 1.6;
    }

    .error-message ul {
      margin: 0;
      padding-left: 16px;
    }

    .error-message li {
      margin-bottom: 4px;
    }

    .form-footer {
      text-align: center;
      margin-top: 16px;
      font-size: 12px;
    }

    .form-footer a {
      color: #0066cc;
      text-decoration: none;
    }

    .form-footer a:hover {
      text-decoration: underline;
    }

    .step-indicator {
      text-align: center;
      font-size: 11px;
      color: #999999;
      margin-bottom: 24px;
      padding-bottom: 16px;
      border-bottom: 1px solid #eeeeee;
    }
  </style>
</head>
<body>

<div class="register-container">
  <div class="register-logo">
    <?php if ($siteLogo && getAssetPath($siteLogo) && file_exists(getAssetPath($siteLogo))): ?>
      <img src="<?php echo htmlspecialchars(getAssetUrl($siteLogo)); ?>?v=<?php echo time(); ?>" alt="Logo">
    <?php else: ?>
      <svg width="100" height="40" viewBox="0 0 100 40" fill="none">
        <text x="0" y="28" font-family="Arial, sans-serif" font-size="20" font-weight="700" fill="#000000">CyberCore</text>
      </svg>
    <?php endif; ?>
  </div>

  <div class="step-indicator">
    Passo 1 de 2 - Informações Básicas
  </div>

  <div class="register-header">
    <h1>Criar Conta</h1>
    <p>Comece pelo essencial</p>
  </div>

  <?php if (!empty($errors)): ?>
    <div class="error-message">
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
      <label>Nome <span style="color: #ff6600;">*</span></label>
      <input type="text" name="first_name" required value="<?php echo htmlspecialchars($step1Data['first_name']); ?>">
    </div>

    <div class="form-group">
      <label>Sobrenome <span style="color: #ff6600;">*</span></label>
      <input type="text" name="last_name" required value="<?php echo htmlspecialchars($step1Data['last_name']); ?>">
    </div>

    <div class="form-group">
      <label>E-mail <span style="color: #ff6600;">*</span></label>
      <input type="email" name="email" required value="<?php echo htmlspecialchars($step1Data['email']); ?>">
    </div>

    <div class="form-group">
      <label>Palavra-passe <span style="color: #ff6600;">*</span></label>
      <input type="password" name="password" required minlength="8">
      <div style="font-size: 11px; color: #999999; margin-top: 4px;">Mínimo 8 caracteres</div>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn">Continuar</button>
    </div>
  </form>

  <div class="form-footer">
    Já tem conta? <a href="login.php">Faça login</a>
  </div>
</div>

</body>
</html>
