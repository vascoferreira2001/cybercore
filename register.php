<?php
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/csrf.php';
require_once __DIR__ . '/inc/settings.php';
require_once __DIR__ . '/inc/maintenance.php';
require_once __DIR__ . '/inc/debug.php';
require_once __DIR__ . '/inc/mailer.php';
require_once __DIR__ . '/inc/email_templates.php';
require_once __DIR__ . '/inc/auth_theme.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$pdo = getDB();
$maintenanceDisabled = getSetting($pdo, 'maintenance_disable_login', '0') === '1';
$maintenanceMessage = trim(getSetting($pdo, 'maintenance_message', ''));
$theme = loadAuthTheme($pdo);

$errors = [];
$receive_news = isset($_POST['receive_news']) ? 1 : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_validate();

  $first = trim($_POST['first_name'] ?? '');
  $last = trim($_POST['last_name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';
  $nif = trim($_POST['nif'] ?? '');
  $country = trim($_POST['country'] ?? '');
  $address = trim($_POST['address'] ?? '');
  $city = trim($_POST['city'] ?? '');
  $postal = trim($_POST['postal_code'] ?? '');
  $phone = trim($_POST['phone'] ?? '');
  $entityType = $_POST['entity_type'] ?? 'Singular';
  $company = trim($_POST['company_name'] ?? '');
  $termsAccepted = isset($_POST['terms']);

  if ($maintenanceDisabled) {
    $errors[] = 'Criação de conta temporariamente desativada.';
  }
  if ($first === '') $errors[] = 'Nome é obrigatório.';
  if ($last === '') $errors[] = 'Sobrenome é obrigatório.';
  if ($email === '') {
    $errors[] = 'Email é obrigatório.';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Email inválido.';
  }
  if (strlen($password) < 8) $errors[] = 'A password deve ter pelo menos 8 caracteres.';
  if (!preg_match('/^\d{9}$/', $nif)) $errors[] = 'NIF deve ter 9 dígitos.';
  if ($postal && !preg_match('/^\d{4}-\d{3}$/', $postal)) $errors[] = 'Código postal inválido.';
  if (!in_array($entityType, ['Singular', 'Coletiva'], true)) $errors[] = 'Tipo de entidade inválido.';
  if ($entityType === 'Coletiva' && $company === '') $errors[] = 'Nome da empresa é obrigatório para entidade coletiva.';
  if (!$termsAccepted) $errors[] = 'Tem de aceitar os termos e condições.';

  $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
  $stmt->execute([$email]);
  if ($stmt->fetch()) $errors[] = 'Já existe uma conta com este email.';

  if (empty($errors)) {
    try {
      $lastIdentifier = $pdo->query("SELECT identifier FROM users WHERE identifier LIKE 'CYC#%' ORDER BY id DESC LIMIT 1")->fetchColumn();
      $nextNumber = 1;
      if ($lastIdentifier && preg_match('/CYC#(\d+)/', $lastIdentifier, $m)) {
        $nextNumber = (int)$m[1] + 1;
      }
      $identifier = sprintf('CYC#%05d', $nextNumber);

      $passwordHash = password_hash($password, PASSWORD_DEFAULT);
      $token = bin2hex(random_bytes(32));
      $expires = date('Y-m-d H:i:s', time() + 86400);

      $insert = $pdo->prepare('INSERT INTO users (identifier, first_name, last_name, email, country, address, city, postal_code, phone, nif, entity_type, company_name, password_hash, receive_news, email_verification_token, email_verification_expires) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
      $insert->execute([$identifier, $first, $last, $email, $country, $address, $city, $postal, $phone, $nif, $entityType, $company ?: null, $passwordHash, $receive_news, $token, $expires]);
      $userId = $pdo->lastInsertId();

      $pdo->prepare('INSERT INTO logs (user_id, type, message) VALUES (?, ?, ?)')->execute([$userId, 'register', 'User registered']);

      $verificationLink = rtrim(SITE_URL, '/') . '/verify_email.php?token=' . urlencode($token);
      $fullName = trim($first . ' ' . $last);
      $body = '<p>Olá ' . htmlspecialchars($fullName) . ',</p>' .
              '<p>Obrigado por criar a sua conta na CyberCore. Clique no botão abaixo para verificar o seu email e ativar a conta:</p>' .
              '<p><a href="' . $verificationLink . '" style="display:inline-block;padding:12px 18px;background:#6a5acd;color:#fff;text-decoration:none;border-radius:8px;font-weight:600;">Verificar email</a></p>' .
              '<p>Se não solicitou esta conta, ignore este email.</p>' .
              '<p>— Equipa CyberCore</p>';
      sendMail($email, 'Verifique o seu email', $body, strip_tags($body));

      $_SESSION['registration_success'] = true;
      $_SESSION['registration_email'] = $email;
      header('Location: registration_success.php');
      exit;
    } catch (Throwable $e) {
      error_log('Erro ao registar utilizador: ' . $e->getMessage());
      $errors[] = 'Ocorreu um erro ao criar a conta. Tente novamente.';
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
  <title>Criar Conta - CyberCore</title>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/auth-modern.css">
</head>
<body class="auth" <?php echo $theme['backgroundStyle'] ? 'style="' . $theme['backgroundStyle'] . '"' : ''; ?>>
<?php if ($maintenanceDisabled): ?>
  <?php renderMaintenanceModal($maintenanceMessage ?: 'Criação de conta temporariamente desativada.', ['disable_form' => true]); ?>
<?php endif; ?>

<div class="shell single">
  <div class="panel">
    <div class="panel-header">
      <div class="hero-header">
        <div class="hero-logo">
          <?php renderAuthLogo($theme['logoUrl']); ?>
        </div>
      </div>
      <h1>Criar conta</h1>
      <p>Use email ou identificador para aceder ao painel.</p>
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

    <form method="post" id="registerForm">
      <?php echo csrf_input(); ?>

      <div class="form-grid">
        <div>
          <label>Nome <span class="required">*</span></label>
          <input class="input" type="text" name="first_name" required value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" placeholder="O seu nome">
        </div>
        <div>
          <label>Sobrenome <span class="required">*</span></label>
          <input class="input" type="text" name="last_name" required value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" placeholder="O seu sobrenome">
        </div>
        <div>
          <label>E-mail <span class="required">*</span></label>
          <input class="input" type="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" placeholder="ex: nome@dominio.com">
        </div>
        <div>
          <label>Palavra-passe <span class="required">*</span></label>
          <input class="input" type="password" id="password" name="password" required minlength="8" placeholder="Mínimo 8 caracteres">
        </div>
        <div>
          <label>NIF <span class="required">*</span></label>
          <input class="input" type="text" name="nif" pattern="\d{9}" placeholder="123456789" required value="<?php echo htmlspecialchars($_POST['nif'] ?? ''); ?>">
        </div>
        <div>
          <label>País</label>
          <input class="input" type="text" name="country" value="<?php echo htmlspecialchars($_POST['country'] ?? ''); ?>" placeholder="">
        </div>
        <div>
          <label>Telemóvel</label>
          <input class="input" type="text" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" placeholder="">
        </div>
        <div>
          <label>Morada</label>
          <input class="input" type="text" name="address" value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>" placeholder="">
        </div>
        <div>
          <label>Cidade</label>
          <input class="input" type="text" name="city" value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>" placeholder="">
        </div>
        <div>
          <label>Código Postal <span class="required">*</span></label>
          <input class="input" type="text" name="postal_code" pattern="\d{4}-\d{3}" placeholder="1234-567" required value="<?php echo htmlspecialchars($_POST['postal_code'] ?? ''); ?>">
        </div>
        <div>
          <label>Tipo de Entidade <span class="required">*</span></label>
          <select class="input" name="entity_type" required onchange="syncCompanyField()">
            <option value="Singular" <?php echo ($_POST['entity_type'] ?? 'Singular') === 'Singular' ? 'selected' : ''; ?>>Pessoa Singular</option>
            <option value="Coletiva" <?php echo ($_POST['entity_type'] ?? '') === 'Coletiva' ? 'selected' : ''; ?>>Pessoa Coletiva</option>
          </select>
        </div>
        <div id="companyRow" class="full" style="display: none;">
          <label>Nome da Empresa <span class="required">*</span></label>
          <input class="input" type="text" name="company_name" id="company_name" value="<?php echo htmlspecialchars($_POST['company_name'] ?? ''); ?>" placeholder="">
        </div>
      </div>

      <div class="form-group checkbox-group">
        <div class="checkbox-item">
          <input type="checkbox" id="terms" name="terms" required <?php echo isset($_POST['terms']) ? 'checked' : ''; ?>>
          <label for="terms">
            Aceito os <a href="#" target="_blank">Termos e condições</a> <span class="required">*</span>
          </label>
        </div>

        <div class="checkbox-item">
          <input type="checkbox" id="newsletter" name="receive_news" <?php echo $receive_news ? 'checked' : ''; ?>>
          <label for="newsletter">
            Aceito receber e-mails relativos às novidades e ofertas comerciais
          </label>
        </div>
      </div>

      <div class="info-text">
        A CyberCore é responsável pelo tratamento dos seus dados pessoais. Confirmo a veracidade dos dados para efeitos de faturação.
      </div>

      <div class="cta">
        <button type="submit" class="btn btn-primary">Criar conta</button>
        <div class="divider">ou</div>
        <a class="btn btn-secondary" href="login.php">Já tenho conta</a>
      </div>
    </form>

    <div class="footer">
      © 2025 CyberCore · <a href="#">Privacidade</a> · <a href="#">Termos</a> · <a href="#">Contacto</a>
    </div>
  </div>
</div>

<script>
  function syncCompanyField() {
    var entityType = document.querySelector('select[name="entity_type"]').value;
    var companyRow = document.getElementById('companyRow');
    var companyInput = document.getElementById('company_name');

    if (entityType === 'Coletiva') {
      companyRow.style.display = 'block';
      companyInput.setAttribute('required', 'required');
    } else {
      companyRow.style.display = 'none';
      companyInput.removeAttribute('required');
      companyInput.value = '';
    }
  }

  syncCompanyField();
</script>
</body>
</html>
