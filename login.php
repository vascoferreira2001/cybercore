<?php
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/csrf.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_validate();
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT id,password_hash FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $u = $stmt->fetch();
    if ($u && password_verify($password, $u['password_hash'])) {
      $_SESSION['user_id'] = $u['id'];
      // Store role in session for quick checks
      $_SESSION['role'] = $u['role'] ?? 'Cliente';
      $pdo->prepare('INSERT INTO logs (user_id,type,message) VALUES (?,?,?)')->execute([$u['id'],'login','User logged in']);
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Credenciais inválidas.';
    }
}
?>
<!doctype html>
<html lang="pt">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Login - CyberCore</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<main style="max-width:480px;margin:40px auto">
  <div class="card">
    <h2>Login</h2>
    <?php if($error): ?><div class="card" style="background:#ffefef;color:#900"><?php echo $error; ?></div><?php endif; ?>
    <form method="post">
      <?php echo csrf_input(); ?>
      <div class="form-row"><label>Email</label><input type="email" name="email" required></div>
      <div class="form-row"><label>Senha</label><input type="password" name="password" required></div>
      <div class="form-row"><button class="btn">Entrar</button></div>
    </form>
    <div class="small"><a href="forgot_password.php">Esqueci a senha</a> · <a href="register.php">Criar conta</a></div>
  </div>
</main>
</body>
</html>
