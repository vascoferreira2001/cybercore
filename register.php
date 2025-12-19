<?php
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/csrf.php';
if (session_status() === PHP_SESSION_NONE) session_start();

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
    $password = $_POST['password'] ?? '';
    $receive_news = isset($_POST['receive_news']) ? 1 : 0;

    if ($first === '' || $last === '' || $email === '' || $nif === '') {
        $errors[] = 'Preencha os campos obrigatórios.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email inválido.';
    if (!preg_match('/^\d{4}-\d{3}$/', $postal)) $errors[] = 'Código postal deve ser no formato 1234-567.';
    if (!preg_match('/^\d{9}$/', $nif)) $errors[] = 'NIF inválido (9 dígitos).';
    if (strlen($password) < 8) $errors[] = 'A password deve ter pelo menos 8 caracteres.';

    if (empty($errors)) {
        $pdo = getDB();
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'Já existe um utilizador com esse email.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $role = 'Cliente';
            $ins = $pdo->prepare('INSERT INTO users (first_name,last_name,email,country,address,city,postal_code,phone,nif,entity_type,password_hash,role,receive_news) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)');
            $ins->execute([$first,$last,$email,$country,$address,$city,$postal,$phone,$nif,$entity,$hash,$role,$receive_news]);
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
  <link rel="stylesheet" href="/cybercore/css/style.css">
</head>
<body>
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
      <div class="form-row"><label>Palavra Passe</label><input type="password" id="password" name="password" required></div>
      <div class="form-row"><label><input type="checkbox" name="receive_news"> Desejo receber newsletters</label></div>
      <div class="form-row"><label><input type="checkbox" required> Declaro que li e aceito a Política de Privacidade</label></div>
      <div class="form-row"><label><input type="checkbox" required> Confirmo que tenho mais de 18 anos</label></div>
      <div class="form-row"><label><input type="checkbox" required> Aceito as Condições de Serviço</label></div>
      <div class="form-row small">Ao submeter confirmo a veracidade dos dados para efeitos de faturação.</div>
      <div class="form-row"><button class="btn">Criar Conta</button></div>
    </form>
    <div class="small">Já tem conta? <a href="/cybercore/login.php">Entrar</a></div>
  </div>
</main>
<script src="/cybercore/js/app.js"></script>
</body>
</html>
