<?php
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/mailer.php';
require_once __DIR__ . '/inc/csrf.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_validate();
    $email = $_POST['email'] ?? '';
    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($u = $stmt->fetch()) {
      $token = bin2hex(random_bytes(24));
      $expires = date('Y-m-d H:i:s', time() + 3600);
      $pdo->prepare('INSERT INTO password_resets (user_id,token,expires_at) VALUES (?,?,?)')->execute([$u['id'],$token,$expires]);
      $resetLink = sprintf('%s/reset_password.php?token=%s', rtrim('http://localhost/cybercore','/'), $token);
      $html = "<p>Pedido de redefinição de senha recebido. Clique no link abaixo para redefinir a sua senha (válido 1 hora):</p><p><a href=\"$resetLink\">Redefinir Senha</a></p>";
      $sent = sendMail($email, 'Redefinição de Senha - CyberCore', $html);
      $pdo->prepare('INSERT INTO logs (user_id,type,message) VALUES (?,?,?)')->execute([$u['id'],'password_reset','Password reset requested, email sent: '.($sent? 'yes':'no')]);
      $message = 'Se o email existir no sistema, recebeu um link para redefinir a senha.';
    } else {
      $message = 'Se o email existir no sistema, recebeu um link para redefinir a senha.';
    }
}
?>
<!doctype html>
<html lang="pt">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Redefinir Senha - CyberCore</title>
  <link rel="stylesheet" href="/cybercore/css/style.css">
</head>
<body>
<main style="max-width:480px;margin:40px auto">
  <div class="card">
    <h2>Redefinição de Senha</h2>
    <?php if($message): ?><div class="card" style="background:#eef;color:#033"><?php echo $message; ?></div><?php endif; ?>
    <form method="post">
      <?php echo csrf_input(); ?>
      <div class="form-row"><label>Email</label><input type="email" name="email" required></div>
      <div class="form-row"><button class="btn">Pedir redefinição</button></div>
    </form>
  </div>
</main>
</body>
</html>
