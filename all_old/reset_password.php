<?php
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/mailer.php';
require_once __DIR__ . '/inc/csrf.php';
require_once __DIR__ . '/inc/settings.php';
require_once __DIR__ . '/inc/maintenance.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$token = $_GET['token'] ?? '';
$pdo = getDB();
$maintenanceDisabled = getSetting($pdo, 'maintenance_disable_login', '0') === '1';
$maintenanceMessage = trim(getSetting($pdo, 'maintenance_message', ''));

$row = null;
$message = '';

if ($maintenanceDisabled) {
  $message = $maintenanceMessage ?: 'A redefinição de senha está temporariamente indisponível devido a manutenção.';
} else {
  $stmt = $pdo->prepare('SELECT * FROM password_resets WHERE token = ? AND expires_at >= NOW()');
  $stmt->execute([$token]);
  $row = $stmt->fetch();
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
}
?>
<!doctype html>
<html lang="pt-PT">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Redefinir Password - CyberCore</title>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg: #0f172a;
      --panel: rgba(15, 23, 42, 0.92);
      --muted: #cdd5e1;
      --text: #e2e8f0;
      --accent: #2563eb;
      --accent-2: #38bdf8;
      --border: rgba(255, 255, 255, 0.08);
      --danger: #f97316;
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
      font-family: "Manrope", -apple-system, BlinkMacSystemFont, "Segoe UI", Helvetica, Arial, sans-serif;
      background: radial-gradient(circle at 20% 20%, rgba(56, 189, 248, 0.12), transparent 30%),
                  radial-gradient(circle at 80% 0%, rgba(37, 99, 235, 0.16), transparent 32%),
                  radial-gradient(circle at 40% 80%, rgba(26, 86, 219, 0.12), transparent 35%),
                  var(--bg);
      color: var(--text);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 28px 16px;
    }

    .card {
      width: 100%;
      max-width: 520px;
      background: var(--panel);
      border: 1px solid var(--border);
      border-radius: 18px;
      padding: 28px;
      box-shadow: 0 30px 90px rgba(0, 0, 0, 0.35);
      backdrop-filter: blur(14px);
    }

    h1 {
      font-size: 24px;
      font-weight: 700;
      color: #fff;
      margin-bottom: 8px;
    }

    p.subtitle {
      color: var(--muted);
      font-size: 14px;
      margin-bottom: 18px;
      line-height: 1.6;
    }

    .notice {
      border: 1px solid rgba(249, 115, 22, 0.35);
      background: rgba(249, 115, 22, 0.08);
      color: #fed7aa;
      border-radius: 12px;
      padding: 12px 14px;
      font-size: 13px;
      line-height: 1.5;
      margin-bottom: 14px;
    }

    form { display: grid; gap: 14px; }

    label {
      display: block;
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      color: var(--muted);
      margin-bottom: 6px;
      font-weight: 700;
    }

    .input {
      width: 100%;
      border-radius: 12px;
      border: 1px solid var(--border);
      background: rgba(255, 255, 255, 0.03);
      color: #fff;
      padding: 13px 14px;
      font-size: 15px;
      transition: border-color 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
    }

    .input::placeholder { color: #8ea0be; }
    .input:focus {
      outline: none;
      border-color: rgba(56, 189, 248, 0.65);
      box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.15);
      background: rgba(255, 255, 255, 0.05);
    }

    .btn {
      width: 100%;
      padding: 14px;
      border: none;
      border-radius: 12px;
      font-weight: 800;
      font-size: 15px;
      cursor: pointer;
      transition: transform 0.15s ease, box-shadow 0.15s ease, filter 0.15s ease;
      color: #fff;
      background: linear-gradient(120deg, #2563eb, #38bdf8);
      box-shadow: 0 12px 30px rgba(37, 99, 235, 0.35);
    }

    .btn:hover { transform: translateY(-1px); filter: brightness(1.05); }
    .btn:active { transform: translateY(1px); }

    .footer {
      margin-top: 12px;
      text-align: center;
      color: var(--muted);
      font-size: 12px;
    }

    .footer a { color: var(--accent-2); text-decoration: none; }
    .footer a:hover { text-decoration: underline; }
  </style>
</head>
<body>
<?php if ($maintenanceDisabled): ?>
  <?php renderMaintenanceModal($maintenanceMessage ?: 'A redefinição de senha está temporariamente desativada.', ['disable_form' => true]); ?>
<?php endif; ?>

<div class="card">
  <h1>Redefinir password</h1>
  <p class="subtitle">Defina uma nova password segura para recuperar o acesso à sua conta.</p>

  <?php if ($message): ?>
    <div class="notice"><?php echo htmlspecialchars($message); ?></div>
  <?php endif; ?>

  <?php if ($row): ?>
    <form method="post">
      <?php echo csrf_input(); ?>
      <div>
        <label for="password">Nova password</label>
        <input class="input" type="password" id="password" name="password" required placeholder="Mínimo 8 caracteres">
      </div>
      <div>
        <label for="password2">Confirmar password</label>
        <input class="input" type="password" id="password2" name="password2" required placeholder="Repita a password">
      </div>
      <button class="btn" type="submit">Alterar password</button>
    </form>
  <?php endif; ?>

  <div class="footer">Voltar para o <a href="login.php">login</a></div>
</div>

</body>
</html>
