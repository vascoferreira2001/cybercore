<?php
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/csrf.php';
require_once __DIR__ . '/inc/settings.php';
require_once __DIR__ . '/inc/debug.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if (isset($_SESSION['user_id'])) {
  header('Location: dashboard.php');
  exit;
}

$pdo = getDB();
$siteLogo = getSetting($pdo, 'site_logo');
$loginBg = getSetting($pdo, 'login_background');
$bgUrl = ($loginBg && getAssetPath($loginBg) && file_exists(getAssetPath($loginBg))) ? htmlspecialchars(getAssetUrl($loginBg)) : '';
$backgroundStyle = $bgUrl ? 'background: url(' . $bgUrl . ') center/cover no-repeat fixed, #0f172a;' : '';
$errors = [];
$maxAttempts = 5;
$lockoutTime = 600;
$cacheAvailable = function_exists('apcu_fetch') && function_exists('apcu_store') && function_exists('apcu_exists') && function_exists('apcu_delete') && filter_var(ini_get('apc.enabled'), FILTER_VALIDATE_BOOLEAN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_validate();
  
  $emailOrId = $_POST['email'] ?? '';
  $password = $_POST['password'] ?? '';
  
  $clientIP = $_SERVER['REMOTE_ADDR'];
  $key = 'login_attempts_' . md5($clientIP . $emailOrId);
  $lockKey = 'login_lockout_' . md5($clientIP . $emailOrId);
  
  if ($cacheAvailable && apcu_exists($lockKey)) {
    $errors[] = 'Conta bloqueada temporariamente. Tente novamente em 10 minutos.';
  } elseif ($cacheAvailable && apcu_exists($key) && apcu_fetch($key) >= $maxAttempts) {
    $errors[] = 'Demasiadas tentativas falhadas. Conta bloqueada por 10 minutos.';
    apcu_store($lockKey, true, $lockoutTime);
  } else {
    $attempts = $cacheAvailable ? (apcu_fetch($key) ?? 0) : 0;
    
    if (empty($errors)) {
      // Permitir login com email OU identificador (CYC#...)
      if (strpos($emailOrId, 'CYC#') === 0) {
        // Login com identificador
        $stmt = $pdo->prepare('SELECT * FROM users WHERE identifier = ?');
        $stmt->execute([$emailOrId]);
      } else {
        // Login com email
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$emailOrId]);
      }
      $user = $stmt->fetch();
      
      if ($user && password_verify($password, $user['password_hash'])) {
        if ($cacheAvailable) apcu_delete($key);
        
        if ($user['email_verified'] == 0 && $user['role'] === 'Cliente') {
          $_SESSION['pending_email_verification'] = $user['email'];
          header('Location: verify_email.php?step=resend');
          exit;
        }
        
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['login_time'] = time();
        
        $pdo->prepare('INSERT INTO logs (user_id,type,message) VALUES (?,?,?)')->execute([$user['id'],'login','User logged in']);
        
        header('Location: dashboard.php');
        exit;
      } else {
        $errors[] = 'Email ou palavra-passe incorretos.';
        if ($cacheAvailable) apcu_store($key, $attempts + 1, 600);
      }
    }
  }
}
?><!doctype html>
<html lang="pt-PT">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Login - CyberCore</title>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg: #0f172a;
      --panel: #0b1224;
      --muted: #cdd5e1;
      --text: #e2e8f0;
      --accent: #2563eb;
      --accent-2: #38bdf8;
      --border: rgba(255, 255, 255, 0.08);
      --danger: #f97316;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: "Manrope", -apple-system, BlinkMacSystemFont, "Segoe UI", Helvetica, Arial, sans-serif;
      background: radial-gradient(circle at 20% 20%, rgba(56, 189, 248, 0.12), transparent 30%),
                  radial-gradient(circle at 80% 0%, rgba(37, 99, 235, 0.16), transparent 32%),
                  radial-gradient(circle at 40% 80%, rgba(26, 86, 219, 0.12), transparent 35%),
                  var(--bg);
      color: var(--text);
      min-height: 100vh;
      padding: 32px 18px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .shell {
      width: 100%;
      max-width: 1080px;
      display: grid;
      grid-template-columns: 1.1fr 0.9fr;
      gap: 28px;
      background: rgba(11, 18, 36, 0.92);
      border: 1px solid var(--border);
      border-radius: 18px;
      box-shadow: 0 30px 90px rgba(0, 0, 0, 0.35);
      overflow: hidden;
      backdrop-filter: blur(14px);
    }

    .hero {
      padding: 34px;
      background: linear-gradient(145deg, rgba(37, 99, 235, 0.12), rgba(11, 18, 36, 0.85));
      border-right: 1px solid var(--border);
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }

    .hero-header {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 24px;
    }

    .hero-logo svg { height: 40px; width: auto; }

    .hero-title {
      font-size: 30px;
      font-weight: 700;
      margin-bottom: 12px;
      color: #fff;
    }

    .hero-subtitle {
      font-size: 15px;
      color: var(--muted);
      line-height: 1.7;
      max-width: 520px;
    }

    .hero-list {
      margin-top: 28px;
      list-style: none;
      display: grid;
      gap: 14px;
    }

    .hero-list li {
      display: flex;
      gap: 12px;
      align-items: center;
      color: var(--text);
      font-weight: 500;
    }

    .check {
      width: 28px;
      height: 28px;
      border-radius: 50%;
      background: rgba(37, 99, 235, 0.18);
      display: grid;
      place-items: center;
      color: var(--accent-2);
      font-weight: 700;
      border: 1px solid rgba(37, 99, 235, 0.35);
      box-shadow: 0 10px 30px rgba(37, 99, 235, 0.18);
    }

    .hero-cta {
      margin-top: auto;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 14px;
      padding: 18px;
      border: 1px solid var(--border);
      border-radius: 14px;
      background: rgba(255, 255, 255, 0.02);
    }

    .hero-cta p {
      color: var(--muted);
      font-size: 13px;
      line-height: 1.6;
    }

    .ghost-btn {
      color: #fff;
      text-decoration: none;
      font-weight: 700;
      padding: 12px 18px;
      border-radius: 12px;
      border: 1px solid rgba(255, 255, 255, 0.18);
      background: linear-gradient(120deg, rgba(37, 99, 235, 0.65), rgba(56, 189, 248, 0.5));
      box-shadow: 0 10px 30px rgba(37, 99, 235, 0.32);
      transition: transform 0.15s ease, box-shadow 0.15s ease;
    }

    .ghost-btn:hover { transform: translateY(-2px); box-shadow: 0 15px 36px rgba(37, 99, 235, 0.42); }

    .panel {
      padding: 34px;
      display: flex;
      flex-direction: column;
      gap: 16px;
      background: rgba(15, 23, 42, 0.92);
    }

    .panel-header h1 {
      font-size: 24px;
      font-weight: 700;
      color: #fff;
    }

    .panel-header p {
      color: var(--muted);
      margin-top: 6px;
      font-size: 14px;
    }

    .error-box {
      border: 1px solid rgba(249, 115, 22, 0.35);
      background: rgba(249, 115, 22, 0.08);
      color: #fed7aa;
      border-radius: 12px;
      padding: 12px 14px;
      font-size: 13px;
      line-height: 1.6;
    }

    .error-box ul { padding-left: 18px; margin: 0; }
    .error-box li { margin-bottom: 6px; }

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

    .form-footer {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 10px;
      font-size: 13px;
    }

    .link {
      color: var(--accent-2);
      text-decoration: none;
      font-weight: 600;
    }

    .link:hover { text-decoration: underline; }

    .cta {
      margin-top: 6px;
      display: grid;
      gap: 10px;
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
    }

    .btn-primary {
      background: linear-gradient(120deg, #2563eb, #38bdf8);
      box-shadow: 0 12px 30px rgba(37, 99, 235, 0.35);
    }

    .btn-primary:hover { transform: translateY(-1px); filter: brightness(1.05); }
    .btn-primary:active { transform: translateY(1px); }

    .btn-secondary {
      background: rgba(255, 255, 255, 0.06);
      border: 1px solid var(--border);
      color: #cbd5e1;
    }

    .divider {
      margin: 8px 0 4px;
      color: var(--muted);
      font-size: 12px;
      text-align: center;
    }

    .footer {
      text-align: center;
      color: var(--muted);
      font-size: 12px;
      margin-top: 18px;
    }

    .footer a { color: var(--accent-2); text-decoration: none; }
    .footer a:hover { text-decoration: underline; }

    @media (max-width: 900px) {
      .shell { grid-template-columns: 1fr; }
      .hero { border-right: none; border-bottom: 1px solid var(--border); }
      body { padding: 22px 14px; }
    }
  </style>
</head>
<body <?php echo $backgroundStyle ? 'style="' . $backgroundStyle . '"' : ''; ?>>

<div class="shell">
  <div class="hero">
    <div>
      <div class="hero-header">
        <div class="hero-logo">
          <?php if ($siteLogo && getAssetPath($siteLogo) && file_exists(getAssetPath($siteLogo))): ?>
            <img src="<?php echo htmlspecialchars(getAssetUrl($siteLogo)); ?>?v=<?php echo time(); ?>" alt="Logo" style="height:40px; width:auto;">
          <?php else: ?>
            <svg viewBox="0 0 200 50" fill="none" xmlns="http://www.w3.org/2000/svg">
              <text x="10" y="35" font-family="Manrope, sans-serif" font-size="28" font-weight="700" fill="#fff">CyberCore</text>
            </svg>
          <?php endif; ?>
        </div>
      </div>

      <div>
        <p style="text-transform: uppercase; letter-spacing: 0.12em; font-size: 11px; color: var(--accent-2); font-weight: 700;">Aceda ao seu painel</p>
        <h1 class="hero-title">Bem-vindo(a) à CyberCore</h1>
        <p class="hero-subtitle">Faça login com o email ou o seu identificador para gerir os seus serviços, faturação e pedir suporte num só Painel.</p>

        <ul class="hero-list">
          <li><span class="check">✓</span> Login seguro com email ou CYC#ID</li>
          <li><span class="check">✓</span> Autenticação rápida com sessão renovada</li>
          <li><span class="check">✓</span> Monitorização de segurança e atividade</li>
        </ul>
      </div>
    </div>

  </div>

  <div class="panel">
    <div class="panel-header">
      <h1>Entrar</h1>
      <p>Use o seu email ou identificador para continuar.</p>
    </div>

    <?php if (!empty($errors)): ?>
      <div class="error-box">
        <ul>
          <?php foreach ($errors as $error): ?>
            <li><?php echo htmlspecialchars($error); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post">
      <?php echo csrf_input(); ?>
      <div>
        <label for="email">Email ou identificador</label>
        <input class="input" type="text" id="email" name="email" required placeholder="ex: nome@dominio.com ou CYC#00001" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
      </div>
      <div>
        <label for="password">Palavra-passe</label>
        <input class="input" type="password" id="password" name="password" required placeholder="••••••••">
      </div>

      <div class="form-footer">
        <span></span>
        <a class="link" href="forgot_password.php">Esqueceu a password?</a>
      </div>

      <div class="cta">
        <button class="btn btn-primary" type="submit">Entrar</button>
        <div class="divider">ou</div>
        <a class="btn btn-primary" href="register.php" style="text-align:center;">Criar conta</a>
      </div>
    </form>

    <div class="footer">
      © 2025 CyberCore · <a href="#">Privacidade</a> · <a href="#">Termos</a> · <a href="#">Contacto</a>
    </div>
  </div>
</div>

</body>
</html>
