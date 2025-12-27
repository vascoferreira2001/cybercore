<?php
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/mailer.php';
require_once __DIR__ . '/inc/csrf.php';
require_once __DIR__ . '/inc/settings.php';
require_once __DIR__ . '/inc/maintenance.php';
require_once __DIR__ . '/inc/auth_theme.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$pdo = getDB();
$maintenanceDisabled = getSetting($pdo, 'maintenance_disable_login', '0') === '1';
$maintenanceMessage = trim(getSetting($pdo, 'maintenance_message', ''));
$theme = loadAuthTheme($pdo);

$message = $maintenanceDisabled ? ($maintenanceMessage ?: 'Recuperação de senha temporariamente indisponível devido a manutenção.') : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_validate();

  if ($maintenanceDisabled) {
    $message = $maintenanceMessage ?: 'Recuperação de senha temporariamente indisponível devido a manutenção.';
  } else {
    $email = trim($_POST['email'] ?? '');
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);

    if ($user = $stmt->fetch()) {
      $token = bin2hex(random_bytes(24));
      $expires = date('Y-m-d H:i:s', time() + 3600);
      $pdo->prepare('INSERT INTO password_resets (user_id,token,expires_at) VALUES (?,?,?)')->execute([$user['id'], $token, $expires]);

      $resetLink = sprintf('%s/reset_password.php?token=%s', rtrim(SITE_URL, '/'), $token);
      $html = '<p>Pedido de redefinição de senha recebido. Clique no link abaixo para redefinir a sua senha (válido 1 hora):</p>' .
              '<p><a href="' . $resetLink . '">Redefinir Senha</a></p>';
      $sent = sendMail($email, 'Redefinição de Senha - CyberCore', $html, strip_tags($html));

      $pdo->prepare('INSERT INTO logs (user_id,type,message) VALUES (?,?,?)')->execute([$user['id'], 'password_reset', 'Password reset requested, email sent: ' . ($sent ? 'yes' : 'no')]);
    }

    $message = 'Se o email existir no sistema, recebeu um link para redefinir a senha.';
  }
}
?>
<!doctype html>
<html lang="pt-PT">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Recuperar Acesso - CyberCore</title>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/auth/auth-modern.css">
</head>
<body class="auth" <?php echo $theme['backgroundStyle'] ? 'style="' . $theme['backgroundStyle'] . '"' : ''; ?>>
<?php if ($maintenanceDisabled): ?>
  <?php renderMaintenanceModal($maintenanceMessage ?: 'A recuperação de senha está temporariamente desativada.', ['disable_form' => true]); ?>
<?php endif; ?>

<div class="shell single">
  <div class="panel">
    <div class="panel-header">
      <div class="hero-header">
        <div class="hero-logo">
          <?php renderAuthLogo($theme['logoUrl']); ?>
        </div>
      </div>
      <h1>Recuperar acesso</h1>
      <p>Enviamos um link de reset para o seu email.</p>
    </div>

    <?php if (!empty($message)): ?>
      <div class="notice"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <form method="post">
      <?php echo csrf_input(); ?>
      <div>
        <label for="email">Email</label>
        <input class="input" type="email" id="email" name="email" required placeholder="ex: nome@dominio.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
      </div>

      <div class="cta">
        <button class="btn btn-primary" type="submit">Enviar link</button>
        <div class="divider">ou</div>
        <a class="btn btn-secondary" href="login.php">Voltar ao login</a>
      </div>
    </form>

    <div class="footer">
      © 2025 CyberCore · <a href="#">Privacidade</a> · <a href="#">Termos</a> · <a href="#">Contacto</a>
    </div>
  </div>
</div>

</body>
</html>
