<?php
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/mailer.php';
require_once __DIR__ . '/inc/csrf.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$token = $_GET['token'] ?? '';
$pdo = getDB();
$stmt = $pdo->prepare('SELECT * FROM password_resets WHERE token = ? AND expires_at >= NOW()');
$stmt->execute([$token]);
$row = $stmt->fetch();
$message = '';
if (!$row) {
    $message = 'Token inválido ou expirado.';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $row) {
  csrf_validate();
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';
    if ($password !== $password2) {
        $message = 'As senhas não coincidem.';
    } elseif (strlen($password) < 8) {
        $message = 'A password deve ter pelo menos 8 caracteres.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$hash, $row['user_id']]);
        $pdo->prepare('INSERT INTO logs (user_id,type,message) VALUES (?,?,?)')->execute([$row['user_id'],'password_reset','Password changed via reset link']);
        $pdo->prepare('DELETE FROM password_resets WHERE id = ?')->execute([$row['id']]);
        header('Location: login.php'); exit;
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
    <h2>Redefinir Senha</h2>
    <?php if($message): ?><div class="card" style="background:#ffefef;color:#900"><?php echo $message; ?></div><?php endif; ?>
    <?php if($row): ?>
    <form method="post">
      <?php echo csrf_input(); ?>
      <div class="form-row"><label>Nova Password</label><input type="password" name="password" required></div>
      <div class="form-row"><label>Confirmar Password</label><input type="password" name="password2" required></div>
      <div class="form-row"><button class="btn">Alterar Password</button></div>
    </form>
    <?php endif; ?>
  </div>
</main>
</body>
</html>
