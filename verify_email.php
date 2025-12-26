<?php
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/settings.php';
require_once __DIR__ . '/inc/email_templates.php';
require_once __DIR__ . '/inc/csrf.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$pdo = getDB();
$state = ['success' => '', 'error' => '', 'notice' => ''];

// Reenvio de verificação
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'resend') {
  csrf_validate();
  $email = trim($_POST['email'] ?? '');
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $state['error'] = 'Por favor, introduza um email válido.';
  } else {
    $stmt = $pdo->prepare('SELECT id, name, email_verified FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Mensagem genérica por segurança
    $state['notice'] = 'Se existir uma conta com esse email, enviámos um novo link de verificação.';

    if ($user && (int)$user['email_verified'] === 0) {
      // Rate limit simples via logs (10 minutos)
      $last = $pdo->prepare("SELECT created_at FROM logs WHERE user_id = ? AND type = 'email_verification_resent' ORDER BY created_at DESC LIMIT 1");
      $last->execute([$user['id']]);
      $row = $last->fetch(PDO::FETCH_ASSOC);
      $tooSoon = $row && (strtotime($row['created_at']) > time() - 600);

      if ($tooSoon) {
        $state['error'] = 'Aguarde alguns minutos antes de pedir novo email.';
      } else {
        // Gerar novo token de 24h
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 24 * 3600);
        $upd = $pdo->prepare('UPDATE users SET email_verification_token = ?, email_verification_expires = ? WHERE id = ?');
        $upd->execute([$token, $expires, $user['id']]);

        // Enviar email via template
        $baseUrl = rtrim(SITE_URL, '/');
        $link = $baseUrl . '/verify_email.php?token=' . urlencode($token);
        sendTemplatedEmail($pdo, 'email_verification', $email, $user['name'] ?? '', [
          'user_name' => $user['name'] ?? 'Utilizador',
          'verification_link' => $link
        ]);

        // Log
        $pdo->prepare('INSERT INTO logs (user_id, type, message) VALUES (?, ?, ?)')->execute([
          $user['id'], 'email_verification_resent', 'Email de verificação reenviado']
        );
      }
    }
  }
}

// Verificação por token
$token = $_GET['token'] ?? '';
if ($token) {
  $stmt = $pdo->prepare('SELECT id, email, email_verification_expires FROM users WHERE email_verification_token = ? AND email_verified = 0');
  $stmt->execute([$token]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$user) {
    $state['error'] = 'Token de verificação inválido ou já utilizado.';
  } elseif ($user['email_verification_expires'] && strtotime($user['email_verification_expires']) < time()) {
    $state['error'] = 'Token de verificação expirado. Pode pedir novo email abaixo.';
    // Log expirado
    $pdo->prepare('INSERT INTO logs (user_id, type, message) VALUES (?, ?, ?)')->execute([
      $user['id'] ?? null, 'email_verification_expired', 'Token expirado']
    );
  } else {
    // Marcar como verificado
    $upd = $pdo->prepare('UPDATE users SET email_verified = 1, email_verification_token = NULL, email_verification_expires = NULL WHERE id = ?');
    $upd->execute([$user['id']]);
    $pdo->prepare('INSERT INTO logs (user_id, type, message) VALUES (?, ?, ?)')->execute([
      $user['id'], 'email_verified', 'Email verificado com sucesso']
    );
    $state['success'] = 'Email verificado com sucesso! Pode agora fazer login.';
  }
}
?>
<!doctype html>
<html lang="pt">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Verificação de Email · CyberCore</title>
  <link rel="stylesheet" href="assets/css/auth/auth-modern.css">
  <link rel="stylesheet" href="assets/css/shared/design-system.css">
</head>
<body class="auth">
  <div class="shell" role="main">
    <div class="hero" aria-hidden="true">
      <div class="hero-header">
        <div class="check">✓</div>
        <div class="hero-title">Verificação de Email</div>
      </div>
      <p class="hero-subtitle">Confirme o seu endereço de email para ativar a sua conta e começar a usar a plataforma.</p>
      <ul class="hero-list">
        <li><span class="check">✓</span> Link válido por 24 horas</li>
        <li><span class="check">✓</span> Necessário para clientes</li>
        <li><span class="check">✓</span> Ajuda a proteger a sua conta</li>
      </ul>
    </div>
    <div class="panel">
      <div class="panel-header">
        <h1>Estado da Verificação</h1>
        <p>Use o link do email ou reenvie um novo.</p>
      </div>

      <?php if (!empty($state['success'])): ?>
        <div class="alert alert-success" role="alert">
          <?php echo htmlspecialchars($state['success']); ?>
        </div>
        <div class="cta">
          <a class="btn btn-primary" href="login.php">Continuar para Login</a>
        </div>
      <?php endif; ?>

      <?php if (!empty($state['error'])): ?>
        <div class="alert alert-danger" role="alert">
          <?php echo htmlspecialchars($state['error']); ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($state['notice'])): ?>
        <div class="alert alert-info" role="status">
          <?php echo htmlspecialchars($state['notice']); ?>
        </div>
      <?php endif; ?>

      <div class="section-title">Reenviar verificação de email</div>
      <form method="post" class="form-grid" novalidate>
        <?php echo csrf_input(); ?>
        <input type="hidden" name="action" value="resend">
        <div class="full">
          <label for="email">Email da sua conta</label>
          <input class="input" id="email" name="email" type="email" placeholder="email@dominio.com" required aria-required="true">
        </div>
        <div class="form-footer">
          <button class="btn btn-primary" type="submit">Reenviar Email</button>
          <a class="link" href="register.php">Registar nova conta</a>
        </div>
      </form>

      <div class="footer">Precisa de ajuda? Contacte suporte.</div>
    </div>
  </div>
</body>
</html>
