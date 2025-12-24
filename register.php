<?php
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/csrf.php';
require_once __DIR__ . '/inc/settings.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$pdo = getDB();
$maintenanceDisabled = getSetting($pdo, 'maintenance_disable_login', '0') === '1';
$maintenanceMessage = trim(getSetting($pdo, 'maintenance_message', ''));

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
      $ins = $pdo->prepare('INSERT INTO users (first_name,last_name,email,country,address,city,postal_code,phone,nif,entity_type,company_name,password_hash,role,receive_news) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
      $ins->execute([$first,$last,$email,$country,$address,$city,$postal,$phone,$nif,$entity,$company,$hash,$role,$receive_news]);
      $userId = $pdo->lastInsertId();
      $pdo->prepare('INSERT INTO logs (user_id,type,message) VALUES (?,?,?)')->execute([$userId,'registration','User registered']);
      $_SESSION['user_id'] = $userId;
      header('Location: dashboard.php');
      exit;
    }
  }
}
?>
<!doctype html>
<html lang="pt">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Registo - CyberCore</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php if ($maintenanceDisabled): ?>
  <div class="maintenance-overlay" id="maintenanceOverlay">
    <div class="maintenance-modal">
      <strong>Modo de Manutenção</strong>
      <p style="margin:8px 0 0 0"><?php echo htmlspecialchars($maintenanceMessage ?: 'Criação de conta temporariamente desativada.'); ?></p>
    </div>
  </div>
  <style>
    .maintenance-overlay { position:fixed; inset:0; display:flex; align-items:center; justify-content:center; background:rgba(0,0,0,0.45); z-index:1000; }
    .maintenance-modal { background:#fff3cd; color:#856404; border:1px solid #ffeeba; padding:16px 18px; border-radius:8px; box-shadow:0 12px 30px rgba(0,0,0,0.25); max-width:520px; width:92%; text-align:center; }
  </style>
  <script>
    document.addEventListener('DOMContentLoaded', function(){
      document.querySelectorAll('form input, form button, form select').forEach(function(el){ el.setAttribute('disabled','disabled'); });
    });
  </script>
<?php endif; ?>
<main style="max-width:800px;margin:24px auto">
  <div class="card">
    <h2>Criação de Conta</h2>
    <?php if(!empty($errors)): ?><div class="card" style="background:#ffefef;color:#900"><?php echo implode('<br>',$errors); ?></div><?php endif; ?>
    <form method="post" id="registerForm">
      <?php echo csrf_input(); ?>
      <div class="form-row"><label>Nome (Obrigatório)</label><input type="text" name="first_name" required></div>
      <div class="form-row"><label>Apelido (Obrigatório)</label><input type="text" name="last_name" required></div>
      <div class="form-row"><label>Email (Obrigatório)</label><input type="email" name="email" required></div>
      <div class="form-row"><label>País</label><input type="text" name="country"></div>
      <div class="form-row"><label>Morada</label><input type="text" name="address"></div>
      <div class="form-row"><label>Cidade</label><input type="text" name="city"></div>
      <div class="form-row"><label>Código Postal (PT) 1234-567</label><input type="text" name="postal_code" pattern="\d{4}-\d{3}" required></div>
      <div class="form-row"><label>Telemóvel</label><input type="text" name="phone"></div>
      <div class="form-row"><label>NIF (Obrigatório)</label><input type="text" name="nif" pattern="\d{9}" required></div>
      <div class="form-row"><label>Tipo de Entidade</label>
        <select name="entity_type"><option>Singular</option><option>Coletiva</option></select>
      </div>
      <div class="form-row" id="companyRow" style="display:none"><label>Nome da Empresa (Obrigatório para Entidade Coletiva)</label><input type="text" name="company_name" id="company_name"></div>
      <div class="form-row"><label>Palavra Passe</label><input type="password" id="password" name="password" required></div>
      <div class="form-row"><label><input type="checkbox" name="receive_news"> Desejo receber newsletters</label></div>
      <div class="form-row"><label><input type="checkbox" required> Declaro que li e aceito a Política de Privacidade</label></div>
      <div class="form-row"><label><input type="checkbox" required> Confirmo que tenho mais de 18 anos</label></div>
      <div class="form-row"><label><input type="checkbox" required> Aceito as Condições de Serviço</label></div>
      <div class="form-row small">Ao submeter confirmo a veracidade dos dados para efeitos de faturação.</div>
      <div class="form-row"><button class="btn">Criar Conta</button></div>
    </form>
    <div class="small">Já tem conta? <a href="login.php">Entrar</a></div>
  </div>
</main>
<script src="js/app.js"></script>
<script>
  (function(){
    var select = document.querySelector('select[name="entity_type"]');
    var companyRow = document.getElementById('companyRow');
    var companyInput = document.getElementById('company_name');
    function syncCompanyField(){
      var isCollective = select && select.value === 'Coletiva';
      if (companyRow) companyRow.style.display = isCollective ? 'block' : 'none';
      if (companyInput) {
        if (isCollective) {
          companyInput.setAttribute('required','required');
        } else {
          companyInput.removeAttribute('required');
          companyInput.value = '';
        }
      }
    }
    if (select) {
      select.addEventListener('change', syncCompanyField);
      syncCompanyField();
    }
  })();
</script>
</body>
</html>
