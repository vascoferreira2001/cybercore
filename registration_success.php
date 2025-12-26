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
  <title>Registo Concluído - CyberCore</title>
  <link rel="stylesheet" href="assets/css/shared/style.css">
</head>
<body>
<main style="max-width:600px;margin:40px auto">
  <div class="card">
    <div style="text-align:center;margin-bottom:20px">
      <div style="font-size:64px;color:#4CAF50">✓</div>
    </div>
    <h2 style="text-align:center">Registo Concluído!</h2>
    <div class="card" style="background:#e8f5e9;color:#2e7d32;margin:20px 0">
      <p style="margin:0"><strong>A sua conta foi criada com sucesso!</strong></p>
    </div>
    <p>Enviámos um email de verificação para:</p>
    <p style="text-align:center;font-weight:bold;font-size:16px;color:#0b84a5"><?php echo htmlspecialchars($email); ?></p>
    <p>Por favor, verifique a sua caixa de entrada (e pasta de spam) e clique no link de verificação para ativar a sua conta.</p>
    <div style="background:#fff3cd;border:1px solid #ffc107;padding:12px;border-radius:4px;margin:20px 0">
      <p style="margin:0;font-size:14px"><strong>⚠️ Importante:</strong> Só poderá fazer login após verificar o seu email. O link de verificação é válido por 24 horas.</p>
    </div>
    <div style="text-align:center;margin-top:24px">
      <a href="login.php" class="btn">Ir para Login</a>
    </div>
  </div>
</main>
</body>
</html>
