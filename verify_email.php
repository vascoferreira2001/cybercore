<?php
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/settings.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$pdo = getDB();
$message = '';
$error = '';
$token = $_GET['token'] ?? '';

if (!$token) {
  $error = 'Token de verificação inválido.';
} else {
  // Procurar utilizador com este token
  $stmt = $pdo->prepare('SELECT id, email, email_verification_expires FROM users WHERE email_verification_token = ? AND email_verified = 0');
  $stmt->execute([$token]);
  $user = $stmt->fetch();
  
  if (!$user) {
    $error = 'Token de verificação inválido ou já utilizado.';
  } elseif ($user['email_verification_expires'] && strtotime($user['email_verification_expires']) < time()) {
    $error = 'Token de verificação expirado. Por favor, registe-se novamente.';
  } else {
    // Verificar email
    $stmt = $pdo->prepare('UPDATE users SET email_verified = 1, email_verification_token = NULL, email_verification_expires = NULL WHERE id = ?');
    $stmt->execute([$user['id']]);
    
    // Log
    $pdo->prepare('INSERT INTO logs (user_id, type, message) VALUES (?, ?, ?)')->execute([
      $user['id'],
      'email_verified',
      'Email verificado com sucesso'
    ]);
    
    $message = 'Email verificado com sucesso! Pode agora fazer login.';
  }
}
?>
<!doctype html>
<html lang="pt">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Verificar Email - CyberCore</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<main style="max-width:480px;margin:40px auto">
  <div class="card">
    <h2>Verificação de Email</h2>
    <?php if($message): ?>
      <div class="card" style="background:#e8f5e9;color:#2e7d32;margin-bottom:16px">
        ✓ <?php echo htmlspecialchars($message); ?>
      </div>
      <div style="text-align:center;margin-top:20px">
        <a href="login.php" class="btn">Ir para Login</a>
      </div>
    <?php endif; ?>
    <?php if($error): ?>
      <div class="card" style="background:#ffefef;color:#900;margin-bottom:16px">
        ✗ <?php echo htmlspecialchars($error); ?>
      </div>
      <div style="text-align:center;margin-top:20px">
        <a href="register.php" class="btn">Registar Nova Conta</a>
      </div>
    <?php endif; ?>
  </div>
</main>
</body>
</html>
