<?php
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/csrf.php';
require_once __DIR__ . '/inc/settings.php';
require_once __DIR__ . '/inc/mailer.php';
require_once __DIR__ . '/inc/email_templates.php';
require_once __DIR__ . '/inc/debug.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// Se não tem dados do passo 1, redireciona
if (!isset($_SESSION['register_step1'])) {
  header('Location: register-step1.php');
  exit;
}

$step1 = $_SESSION['register_step1'];
$pdo = getDB();
$siteLogo = getSetting($pdo, 'site_logo');
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_validate();
  
  $country = $_POST['country'] ?? '';
  $address = $_POST['address'] ?? '';
  $city = $_POST['city'] ?? '';
  $postal = $_POST['postal_code'] ?? '';
  $phone = $_POST['phone'] ?? '';
  $nif = preg_replace('/\D/', '', ($_POST['nif'] ?? ''));
  $entity = $_POST['entity_type'] ?? 'Singular';
  $company = trim($_POST['company_name'] ?? '');
  $terms = isset($_POST['terms']) ? true : false;

  if (!$terms) $errors[] = 'Deve aceitar os Termos e Condições.';
  if (!preg_match('/^\d{4}-\d{3}$/', $postal)) $errors[] = 'Código postal deve ser no formato 1234-567.';
  if (!preg_match('/^\d{9}$/', $nif)) $errors[] = 'NIF inválido (9 dígitos).';
  if ($entity === 'Coletiva' && $company === '') $errors[] = 'Nome da empresa é obrigatório.';

  if (empty($errors)) {
    try {
      // Verificar se email já existe
      $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
      $stmt->execute([$step1['email']]);
      if ($stmt->fetch()) {
        $errors[] = 'Já existe um utilizador com esse email.';
      } else {
        // Inserir utilizador
        $hash = password_hash($step1['password'], PASSWORD_DEFAULT);
        $verificationToken = bin2hex(random_bytes(32));
        $verificationExpires = date('Y-m-d H:i:s', time() + 86400);

        $colsStmt = $pdo->query('SHOW COLUMNS FROM users');
        $existingCols = array_map(function($r){ return $r['Field']; }, $colsStmt->fetchAll());

        $dataMap = [
          'first_name'    => $step1['first_name'],
          'last_name'     => $step1['last_name'],
          'email'         => $step1['email'],
          'country'       => $country,
          'address'       => $address,
          'city'          => $city,
          'postal_code'   => $postal,
          'phone'         => $phone,
          'nif'           => $nif,
          'entity_type'   => $entity,
          'company_name'  => $company,
          'password_hash' => $hash,
          'role'          => 'Cliente',
          'receive_news'  => isset($_POST['receive_news']) ? 1 : 0,
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

        $placeholders = implode(',', array_fill(0, count($insertCols), '?'));
        $sql = 'INSERT INTO users (' . implode(',', $insertCols) . ') VALUES (' . $placeholders . ')';
        $ins = $pdo->prepare($sql);
        $ins->execute($insertVals);
        $userId = $pdo->lastInsertId();

        $pdo->prepare('INSERT INTO logs (user_id,type,message) VALUES (?,?,?)')->execute([$userId,'registration','User registered - email verification pending']);

        // Enviar email de verificação
        $verificationLink = rtrim(SITE_URL, '/') . '/verify_email.php?token=' . $verificationToken;
        sendTemplatedEmail($pdo, 'email_verification', $step1['email'], $step1['first_name'], [
          'user_name' => $step1['first_name'],
          'verification_link' => $verificationLink
        ]);

        // Limpar session
        unset($_SESSION['register_step1']);
        
        $_SESSION['registration_success'] = true;
        $_SESSION['registration_email'] = $step1['email'];
        header('Location: registration_success.php');
        exit;
      }
    } catch (Throwable $e) {
      logError('Erro no registo passo 2: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
      $errors[] = 'Ocorreu um erro ao processar o seu registo. Tente novamente mais tarde.';
    }
  }
}
?>
<!doctype html>
<html lang="pt">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Criar Conta - Passo 2 - CyberCore</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    html, body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "Helvetica Neue", Helvetica, Arial, sans-serif;
      font-size: 14px;
      color: #333333;
      background: #fafafa;
    }

    body {
      padding: 20px;
    }

    .register-container {
      max-width: 500px;
      margin: 0 auto;
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

    .step-indicator {
      text-align: center;
      font-size: 11px;
      color: #999999;
      margin-bottom: 24px;
      padding-bottom: 16px;
      border-bottom: 1px solid #eeeeee;
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
      margin: 0 0 16px 0;
    }

    .form-section-title {
      font-size: 12px;
      font-weight: 600;
      color: #666666;
      text-transform: uppercase;
      margin: 20px 0 12px 0;
      padding-top: 16px;
      border-top: 1px solid #eeeeee;
    }

    .form-section-title:first-of-type {
      border-top: none;
      padding-top: 0;
      margin-top: 0;
    }

    .form-row-2 {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
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
      transition: border-color 0.2s;
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
    }

    .checkbox-group {
      margin-top: 16px;
      padding-top: 16px;
      border-top: 1px solid #eeeeee;
    }

    .checkbox-item {
      display: flex;
      align-items: flex-start;
      margin-bottom: 10px;
    }

    .checkbox-item input[type="checkbox"] {
      width: auto;
      margin-right: 6px;
      margin-top: 2px;
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

    .info-box {
      background: #f5f5f5;
      border-left: 3px solid #eeeeee;
      padding: 10px 12px;
      margin-top: 16px;
      border-radius: 2px;
      font-size: 11px;
      color: #666666;
      line-height: 1.5;
    }

    .form-actions {
      display: flex;
      gap: 12px;
      margin-top: 24px;
    }

    .btn {
      flex: 1;
      padding: 10px;
      border: none;
      border-radius: 2px;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.2s;
    }

    .btn-primary {
      background: #ff6600;
      color: #ffffff;
    }

    .btn-primary:hover {
      background: #e55a00;
    }

    .btn-back {
      background: #ffffff;
      color: #333333;
      border: 1px solid #d9d9d9;
    }

    .btn-back:hover {
      background: #f5f5f5;
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

    @media (max-width: 640px) {
      .form-row-2 {
        grid-template-columns: 1fr;
      }

      .form-actions {
        flex-direction: column;
      }
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
    Passo 2 de 2 - Informações Adicionais
  </div>

  <div class="register-header">
    <h1>Completar Registro</h1>
    <p>Preencha os dados restantes</p>
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

    <!-- Dados já preenchidos (readonly) -->
    <div class="form-section-title">Informações do Passo 1</div>
    <div class="form-row-2">
      <div class="form-group">
        <label>Nome</label>
        <input type="text" value="<?php echo htmlspecialchars($step1['first_name']); ?>" disabled>
      </div>
      <div class="form-group">
        <label>Sobrenome</label>
        <input type="text" value="<?php echo htmlspecialchars($step1['last_name']); ?>" disabled>
      </div>
    </div>
    <div class="form-group">
      <label>E-mail</label>
      <input type="email" value="<?php echo htmlspecialchars($step1['email']); ?>" disabled>
    </div>

    <!-- Dados adicionais -->
    <div class="form-section-title">Morada</div>
    <div class="form-group">
      <label>País</label>
      <input type="text" name="country" placeholder="">
    </div>
    <div class="form-group">
      <label>Morada</label>
      <input type="text" name="address" placeholder="">
    </div>
    <div class="form-row-2">
      <div class="form-group">
        <label>Cidade</label>
        <input type="text" name="city" placeholder="">
      </div>
      <div class="form-group">
        <label>Código Postal <span style="color: #ff6600;">*</span></label>
        <input type="text" name="postal_code" pattern="\d{4}-\d{3}" placeholder="1234-567" required>
      </div>
    </div>
    <div class="form-group">
      <label>Telemóvel</label>
      <input type="text" name="phone" placeholder="">
    </div>

    <!-- Identificação Fiscal -->
    <div class="form-section-title">Identificação</div>
    <div class="form-group">
      <label>NIF <span style="color: #ff6600;">*</span></label>
      <input type="text" name="nif" pattern="\d{9}" placeholder="123456789" required>
    </div>

    <!-- Tipo de Entidade -->
    <div class="form-section-title">Tipo de Conta</div>
    <div class="form-group">
      <label>Tipo de Entidade <span style="color: #ff6600;">*</span></label>
      <select name="entity_type" onchange="syncCompanyField()" required>
        <option value="Singular">Pessoa Singular</option>
        <option value="Coletiva">Pessoa Coletiva</option>
      </select>
    </div>

    <div class="form-group" id="companyRow" style="display: none;">
      <label>Nome da Empresa <span style="color: #ff6600;">*</span></label>
      <input type="text" name="company_name" id="company_name">
    </div>

    <!-- Consentimentos -->
    <div class="checkbox-group">
      <div class="checkbox-item">
        <input type="checkbox" id="terms" name="terms" required>
        <label for="terms">
          Aceito os <a href="#" target="_blank">Termos e Condições</a> <span style="color: #ff6600;">*</span>
        </label>
      </div>

      <div class="checkbox-item">
        <input type="checkbox" id="newsletter" name="receive_news">
        <label for="newsletter">
          Aceito receber e-mails relativos às novidades e ofertas comerciais
        </label>
      </div>
    </div>

    <div class="info-box">
      Ao submeter este formulário, confirmo a veracidade dos dados para efeitos de faturação. Os dados serão tratados de acordo com a legislação de proteção de dados.
    </div>

    <div class="form-actions">
      <button type="button" class="btn btn-back" onclick="history.back()">← Voltar</button>
      <button type="submit" class="btn btn-primary">Criar Conta</button>
    </div>
  </form>
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
