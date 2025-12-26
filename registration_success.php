<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['registration_success'])) {
  header('Location: register.php');
  exit;
}

$email = $_SESSION['registration_email'] ?? '';
unset($_SESSION['registration_success']);
unset($_SESSION['registration_email']);
?>
<!doctype html>
<html lang="pt">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Registo Concluído · CyberCore</title>
  <link rel="stylesheet" href="assets/css/auth/auth-modern.css">
  <link rel="stylesheet" href="assets/css/shared/design-system.css">
</head>
<body class="auth">
  <div class="shell single" role="main">
    <div class="hero" aria-hidden="true">
      <div class="hero-header">
        <div class="check" style="font-size: 24px;">✓</div>
        <div class="hero-title">Bem-vindo ao CyberCore</div>
      </div>
      <p class="hero-subtitle">A sua conta foi criada com sucesso! Enviámos um email de verificação para ativar a sua conta.</p>
      <ul class="hero-list">
        <li><span class="check">✓</span> Conta criada com sucesso</li>
        <li><span class="check">✓</span> Email de verificação enviado</li>
        <li><span class="check">✓</span> Link válido por 24 horas</li>
      </ul>
      <div class="hero-cta">
        <p>Verifique a sua caixa de entrada (e pasta de spam) e clique no link para ativar a conta.</p>
        <a class="ghost-btn" href="login.php">Ir para Login</a>
      </div>
    </div>
    <div class="panel">
      <div class="panel-header">
        <h1>Verificação de Email Necessária</h1>
        <p>Complete o registo verificando o seu email.</p>
      </div>

      <div class="alert alert-success" role="status">
        <strong>✓ Conta criada com sucesso!</strong><br>
        Enviámos um email de verificação para:
      </div>

      <div style="text-align:center;padding:16px;background:#f8fafc;border:1px solid #e5e7eb;border-radius:12px;margin:16px 0;">
        <strong style="font-size:16px;color:#2563eb;"><?php echo htmlspecialchars($email); ?></strong>
      </div>

      <div class="alert alert-warning" role="alert">
        <strong>⚠️ Importante:</strong><br>
        Só poderá fazer login após verificar o seu email. O link de verificação é válido por 24 horas.
      </div>

      <div class="section-title">Próximos passos</div>
      <ol style="color:#333;font-size:14px;line-height:1.8;margin:0 0 24px 20px;">
        <li>Verifique a sua caixa de entrada (e pasta de spam)</li>
        <li>Clique no link de verificação no email</li>
        <li>Faça login com as suas credenciais</li>
      </ol>

      <div class="cta">
        <a class="btn btn-primary" href="login.php">Continuar para Login</a>
      </div>

      <div class="footer">Não recebeu o email? Aguarde alguns minutos ou contacte suporte.</div>
    </div>
  </div>
</body>
</html>
