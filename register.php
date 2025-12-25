<?php
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/csrf.php';
require_once __DIR__ . '/inc/settings.php';
require_once __DIR__ . '/inc/maintenance.php';
require_once __DIR__ . '/inc/debug.php';
require_once __DIR__ . '/inc/mailer.php';
require_once __DIR__ . '/inc/email_templates.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$pdo = getDB();
$maintenanceDisabled = getSetting($pdo, 'maintenance_disable_login', '0') === '1';
$maintenanceMessage = trim(getSetting($pdo, 'maintenance_message', ''));

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    csrf_validate();
    $first = trim($_POST['first_name'] ?? '');
    $last = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $country = $_POST['country'] ?? '';
    $address = $_POST['address'] ?? '';
    $city = $_POST['city'] ?? '';
    $postal = $_POST['postal_code'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $nif = preg_replace('/\D/','',($_POST['nif'] ?? ''));
    $entity = $_POST['entity_type'] ?? 'Singular';
    $company = trim($_POST['company_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $receive_news = isset($_POST['receive_news']) ? 1 : 0;

    if ($maintenanceDisabled) {
      $errors[] = $maintenanceMessage ?: 'Criação de conta temporariamente desativada devido a manutenção.';
    }

    if ($first === '' || $last === '' || $email === '' || $nif === '') {
      $errors[] = 'Preencha os campos obrigatórios.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email inválido.';
    if (!preg_match('/^\d{4}-\d{3}$/', $postal)) $errors[] = 'Código postal deve ser no formato 1234-567.';
    if (!preg_match('/^\d{9}$/', $nif)) $errors[] = 'NIF inválido (9 dígitos).';
    if ($entity === 'Coletiva' && $company === '') $errors[] = 'Nome da empresa é obrigatório para Entidade Coletiva.';
    if (strlen($password) < 8) $errors[] = 'A password deve ter pelo menos 8 caracteres.';

    if (empty($errors)) {
      $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
      $stmt->execute([$email]);
      if ($stmt->fetch()) {
        $errors[] = 'Já existe um utilizador com esse email.';
      } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $role = 'Cliente';
        
        // Gerar token de verificação de email
        $verificationToken = bin2hex(random_bytes(32));
        $verificationExpires = date('Y-m-d H:i:s', time() + 86400); // 24 horas

        // Obter colunas existentes na tabela para compatibilidade com esquemas antigos
        $colsStmt = $pdo->query('SHOW COLUMNS FROM users');
        $existingCols = array_map(function($r){ return $r['Field']; }, $colsStmt->fetchAll());

        $dataMap = [
          'first_name'    => $first,
          'last_name'     => $last,
          'email'         => $email,
          'country'       => $country,
          'address'       => $address,
          'city'          => $city,
          'postal_code'   => $postal,
          'phone'         => $phone,
          'nif'           => $nif,
          'entity_type'   => $entity,
          'company_name'  => $company,
          'password_hash' => $hash,
          'role'          => $role,
          'receive_news'  => $receive_news,
          'email_verified' => 0,
          'email_verification_token' => $verificationToken,
          'email_verification_expires' => $verificationExpires,
        ];

        $insertCols = [];
        $insertVals = [];
        foreach ($dataMap as $col => $val) {
          if (in_array($col, $existingCols, true)) {
            $insertCols[] = $col;
            $insertVals[] = $val;
          }
        }

        if (empty($insertCols)) {
          throw new RuntimeException('Nenhuma coluna válida encontrada para inserção na tabela users.');
        }

        $placeholders = implode(',', array_fill(0, count($insertCols), '?'));
        $sql = 'INSERT INTO users (' . implode(',', $insertCols) . ') VALUES (' . $placeholders . ')';
        $ins = $pdo->prepare($sql);
        $ins->execute($insertVals);

        $userId = $pdo->lastInsertId();
        $pdo->prepare('INSERT INTO logs (user_id,type,message) VALUES (?,?,?)')->execute([$userId,'registration','User registered - email verification pending']);
        
        // Enviar email de verificação usando template
        $verificationLink = rtrim(SITE_URL, '/') . '/verify_email.php?token=' . $verificationToken;
        
        $emailSent = sendTemplatedEmail($pdo, 'email_verification', $email, $first, [
          'user_name' => $first,
          'verification_link' => $verificationLink
        ]);
        
        if (!$emailSent) {
          logError('Falha ao enviar email de verificação', ['user_id' => $userId, 'email' => $email]);
        }
        
        // Redirecionar para página de sucesso sem login automático
        $_SESSION['registration_success'] = true;
        $_SESSION['registration_email'] = $email;
        header('Location: registration_success.php');
        exit;
      }
    }
  } catch (Throwable $e) {
    logError('Erro no registo: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
    $errors[] = 'Ocorreu um erro ao processar o seu registo. Tente novamente mais tarde.';
  }
}
?>
<!doctype html>
<html lang="pt">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Criar Conta - CyberCore</title>
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
    }

    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif;
      color: var(--text-dark);
      background: var(--bg-light);
    }

    .register-logo {
      text-align: center;
      padding: 20px 0;
      margin-bottom: 0;
    }

    .register-container {
      min-height: 100vh;
      padding: 20px;
      padding-top: 20px;
    }

    .register-content {
      max-width: 500px;
      margin: 0 auto;
      background: #ffffff;
      border-radius: 3px;
      box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
    }

    .register-header {
      padding: 24px 32px;
      border-bottom: 1px solid var(--border);
    }

    .register-header h1 {
      margin: 0;
      font-size: 20px;
      font-weight: 600;
      color: var(--primary);
    }

    .register-body {
      padding: 32px;
    }

    .form-group {
      margin-bottom: 18px;
    }

    .form-group.form-row-2 {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
    }

    .form-group.form-row-2 > * {
      margin-bottom: 0;
    }

    .form-group label {
      display: block;
      font-size: 13px;
      font-weight: 500;
      color: var(--text-dark);
      margin-bottom: 6px;
      text-transform: capitalize;
    }

    .form-group label .required {
      color: var(--error);
    }

    .form-group input,
    .form-group select {
      width: 100%;
      padding: 10px 12px;
      border: 1px solid var(--border);
      border-radius: 3px;
      font-size: 13px;
      color: var(--text-dark);
      font-family: inherit;
      transition: border-color 0.2s, box-shadow 0.2s;
      background: #ffffff;
    }

    .form-group input::placeholder,
    .form-group select::placeholder {
      color: #b0b8c1;
    }

    .form-group input:focus,
    .form-group select:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 2px rgba(18, 54, 89, 0.08);
    }

    .form-group.checkbox-group {
      margin-top: 20px;
      padding-top: 20px;
      border-top: 1px solid var(--border);
    }

    .checkbox-item {
      display: flex;
      align-items: flex-start;
      margin-bottom: 10px;
    }

    .checkbox-item input[type="checkbox"] {
      width: auto;
      height: 16px;
      margin-right: 8px;
      margin-top: 2px;
      cursor: pointer;
      accent-color: var(--primary);
    }

    .checkbox-item label {
      margin: 0;
      font-size: 12px;
      line-height: 1.5;
      cursor: pointer;
      color: var(--text-gray);
    }

    .checkbox-item a {
      color: var(--primary);
      text-decoration: none;
    }

    .checkbox-item a:hover {
      text-decoration: underline;
    }

    .form-actions {
      margin-top: 24px;
    }

    .btn-primary {
      width: 100%;
      padding: 10px;
      background: var(--success);
      color: #ffffff;
      border: none;
      border-radius: 3px;
      font-size: 14px;
      font-weight: 500;
      cursor: pointer;
      transition: background 0.2s;
    }

    .btn-primary:hover {
      background: #2a9d47;
    }

    .btn-primary:active {
      transform: translateY(1px);
    }

    .login-link {
      text-align: center;
      margin-top: 16px;
      font-size: 12px;
      color: var(--text-gray);
    }

    .login-link a {
      color: var(--primary);
      text-decoration: none;
      font-weight: 500;
    }

    .login-link a:hover {
      text-decoration: underline;
    }

    .error-message {
      background: #ffefef;
      border: 1px solid #ffcdd2;
      color: var(--error);
      padding: 11px 13px;
      border-radius: 3px;
      margin-bottom: 20px;
      font-size: 12px;
      line-height: 1.6;
    }

    .info-text {
      font-size: 11px;
      color: var(--text-gray);
      margin-top: 12px;
      line-height: 1.5;
      background: #f9f9f9;
      padding: 10px 12px;
      border-radius: 3px;
      border-left: 3px solid var(--border);
    }

    @media (max-width: 640px) {
      .register-header,
      .register-body {
        padding: 20px;
      }

      .form-group.form-row-2 {
        grid-template-columns: 1fr;
        gap: 0;
      }
    }
  </style>
</head>
<body>
<?php if ($maintenanceDisabled): ?>
  <?php renderMaintenanceModal($maintenanceMessage ?: 'Criação de conta temporariamente desativada.', ['disable_form' => true]); ?>
<?php endif; ?>

<div class="register-logo">
  <svg width="100" height="35" viewBox="0 0 100 35" fill="none">
    <text x="0" y="24" font-family="Arial, sans-serif" font-size="20" font-weight="600" fill="#123659">CyberCore</text>
  </svg>
</div>

<div class="register-container">
  <div class="register-content">
    <div class="register-header">
      <h1>Criar Conta</h1>
    </div>

    <div class="register-body">
      <?php if(!empty($errors)): ?>
        <div class="error-message">
          <?php echo implode('<br>', $errors); ?>
        </div>
      <?php endif; ?>

      <form method="post" id="registerForm">
        <?php echo csrf_input(); ?>

        <!-- Identificação -->
        <div class="form-group form-row-2">
          <div>
            <label>Nome <span class="required">*</span></label>
            <input type="text" name="first_name" required value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" placeholder="">
          </div>
          <div>
            <label>Sobrenome <span class="required">*</span></label>
            <input type="text" name="last_name" required value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" placeholder="">
          </div>
        </div>

        <div class="form-group">
          <label>E-mail <span class="required">*</span></label>
          <input type="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" placeholder="">
        </div>

        <div class="form-group">
          <label>NIF <span class="required">*</span></label>
          <input type="text" name="nif" pattern="\d{9}" placeholder="123456789" required value="<?php echo htmlspecialchars($_POST['nif'] ?? ''); ?>">
        </div>

        <!-- Morada -->
        <div class="form-group">
          <label>País</label>
          <input type="text" name="country" value="<?php echo htmlspecialchars($_POST['country'] ?? ''); ?>" placeholder="">
        </div>

        <div class="form-group">
          <label>Morada</label>
          <input type="text" name="address" value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>" placeholder="">
        </div>

        <div class="form-group form-row-2">
          <div>
            <label>Cidade</label>
            <input type="text" name="city" value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>" placeholder="">
          </div>
          <div>
            <label>Código Postal <span class="required">*</span></label>
            <input type="text" name="postal_code" pattern="\d{4}-\d{3}" placeholder="1234-567" required value="<?php echo htmlspecialchars($_POST['postal_code'] ?? ''); ?>">
          </div>
        </div>

        <div class="form-group">
          <label>Telemóvel</label>
          <input type="text" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" placeholder="">
        </div>

        <!-- Tipo de Entidade -->
        <div class="form-group">
          <label>Tipo de Entidade <span class="required">*</span></label>
          <select name="entity_type" required onchange="syncCompanyField()">
            <option value="Singular" <?php echo ($_POST['entity_type'] ?? 'Singular') === 'Singular' ? 'selected' : ''; ?>>Pessoa Singular</option>
            <option value="Coletiva" <?php echo ($_POST['entity_type'] ?? '') === 'Coletiva' ? 'selected' : ''; ?>>Pessoa Coletiva</option>
          </select>
        </div>

        <div class="form-group" id="companyRow" style="display: none;">
          <label>Nome da Empresa <span class="required">*</span></label>
          <input type="text" name="company_name" id="company_name" value="<?php echo htmlspecialchars($_POST['company_name'] ?? ''); ?>" placeholder="">
        </div>

        <!-- Segurança -->
        <div class="form-group">
          <label>Palavra-passe <span class="required">*</span></label>
          <input type="password" id="password" name="password" required minlength="8" placeholder="">
        </div>

        <!-- Consentimentos -->
        <div class="form-group checkbox-group">
          <div class="checkbox-item">
            <input type="checkbox" id="terms" name="terms" required>
            <label for="terms">
              Aceito os <a href="#" target="_blank">Termos e condições</a> <span class="required">*</span>
            </label>
          </div>

          <div class="checkbox-item">
            <input type="checkbox" id="newsletter" name="receive_news">
            <label for="newsletter">
              Aceito receber e-mails relativos às novidades e ofertas comerciais
            </label>
          </div>
        </div>

        <div class="info-text">
          A OVH SAS é responsável pelo tratamento dos seus dados pessoais. Confirmo a veracidade dos dados para efeitos de faturação.
        </div>

        <div class="form-actions">
          <button type="submit" class="btn-primary">Criar uma conta</button>
        </div>

        <div class="login-link">
          Já tem conta? <a href="login.php">Faça login</a>
        </div>
      </form>
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
