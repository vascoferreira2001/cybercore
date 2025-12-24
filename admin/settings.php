<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/csrf.php';
require_once __DIR__ . '/../inc/settings.php';
require_once __DIR__ . '/../inc/mailer.php';

requireLogin();
$user = currentUser();

// Apenas Gestor pode aceder
if ($user['role'] !== 'Gestor') {
  http_response_code(403);
  echo 'Acesso negado.';
  exit;
}

$pdo = getDB();
$message = '';
$errors = [];
$shouldRedirect = false;

$generalDefaults = getGeneralSettingsDefaults();
$generalSettings = getGeneralSettings($pdo);
$cronUrl = getSetting($pdo, 'cron_url', '');
$cronLastRun = getSetting($pdo, 'cron_last_run', '');
$cronInterval = getSetting($pdo, 'cron_interval_minutes', $generalDefaults['cron_interval_minutes']);
$smtp = [
  'smtp_host' => getSetting($pdo, 'smtp_host', ''),
  'smtp_port' => getSetting($pdo, 'smtp_port', '587'),
  'smtp_user' => getSetting($pdo, 'smtp_user', ''),
  'smtp_pass' => getSetting($pdo, 'smtp_pass', ''),
  'smtp_secure' => getSetting($pdo, 'smtp_secure', 'tls'),
  'smtp_from' => getSetting($pdo, 'smtp_from', defined('MAIL_FROM') ? MAIL_FROM : 'no-reply@seudominio.com'),
  'smtp_from_name' => getSetting($pdo, 'smtp_from_name', defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'CyberCore'),
];

// Processar atualizações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_validate();

  // Garantir que os valores obrigatórios permanecem consistentes
  $posted = [
    'site_language' => $generalDefaults['site_language'],
    'site_timezone' => $generalDefaults['site_timezone'],
    'date_format' => $generalDefaults['date_format'],
    'time_format' => $generalDefaults['time_format'],
    'week_start' => $generalDefaults['week_start'],
    'weekend_days' => $generalDefaults['weekend_days'],
    'currency' => $generalDefaults['currency'],
    'currency_symbol' => $generalDefaults['currency_symbol'],
    'currency_position' => $generalDefaults['currency_position'],
    'decimal_separator' => $generalDefaults['decimal_separator'],
    'decimal_precision' => $generalDefaults['decimal_precision'],
  ];

  foreach ($posted as $key => $val) {
    setSetting($pdo, $key, $val);
  }
  $generalSettings = getGeneralSettings($pdo);
  $message .= 'Definições gerais atualizadas. ';
  $shouldRedirect = true;

  // Cron settings
  $cronUrlPost = trim($_POST['cron_url'] ?? $cronUrl);
  if ($cronUrlPost && !filter_var($cronUrlPost, FILTER_VALIDATE_URL)) {
    $errors[] = 'Cron URL inválida.';
  } else {
    setSetting($pdo, 'cron_url', $cronUrlPost);
    $cronUrl = $cronUrlPost;
  }
  setSetting($pdo, 'cron_interval_minutes', '10');
  $cronInterval = '10';

  // SMTP settings
  $smtpHost = trim($_POST['smtp_host'] ?? '');
  $smtpPort = trim($_POST['smtp_port'] ?? '');
  $smtpUser = trim($_POST['smtp_user'] ?? '');
  $smtpPass = trim($_POST['smtp_pass'] ?? '');
  $smtpSecure = trim($_POST['smtp_secure'] ?? 'tls');
  $smtpFrom = trim($_POST['smtp_from'] ?? '');
  $smtpFromName = trim($_POST['smtp_from_name'] ?? '');
  $testEmail = trim($_POST['test_email'] ?? '');

  if ($smtpPort !== '' && !ctype_digit($smtpPort)) {
    $errors[] = 'Porta SMTP deve ser numérica.';
  }
  if ($smtpSecure && !in_array($smtpSecure, ['tls', 'ssl', 'none'], true)) {
    $errors[] = 'Tipo de segurança SMTP inválido.';
  }
  if ($smtpFrom && !filter_var($smtpFrom, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Email de remetente inválido.';
  }
  if ($testEmail && !filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Email de teste inválido.';
  }

  if (empty($errors)) {
    $smtpToSave = [
      'smtp_host' => $smtpHost,
      'smtp_port' => $smtpPort ?: '587',
      'smtp_user' => $smtpUser,
      'smtp_pass' => $smtpPass,
      'smtp_secure' => $smtpSecure ?: 'tls',
      'smtp_from' => $smtpFrom ?: $smtp['smtp_from'],
      'smtp_from_name' => $smtpFromName ?: $smtp['smtp_from_name'],
    ];
    foreach ($smtpToSave as $k => $v) {
      setSetting($pdo, $k, $v);
    }
    $smtp = $smtpToSave;
    $message .= 'Configurações SMTP guardadas. ';
  }

  // Processar upload de imagens
  if (!empty($_FILES['site_logo']) && $_FILES['site_logo']['error'] === UPLOAD_ERR_OK) {
    $logoErrors = validateImageUpload($_FILES['site_logo'], 2000, ['jpg', 'jpeg', 'png']);
    if (empty($logoErrors)) {
      $oldLogo = getSetting($pdo, 'site_logo');
      $newLogo = saveUploadedFile($_FILES['site_logo']);
      if ($newLogo) {
        setSetting($pdo, 'site_logo', $newLogo);
        deleteOldFile($oldLogo);
        $message .= 'Logo atualizado com sucesso. ';
      }
    } else {
      $errors = array_merge($errors, $logoErrors);
    }
  }
    
  if (!empty($_FILES['favicon']) && $_FILES['favicon']['error'] === UPLOAD_ERR_OK) {
    $faviconErrors = validateImageUpload($_FILES['favicon'], 500, ['jpg', 'jpeg', 'png']);
    if (empty($faviconErrors)) {
      $oldFavicon = getSetting($pdo, 'favicon');
      $newFavicon = saveUploadedFile($_FILES['favicon']);
      if ($newFavicon) {
        setSetting($pdo, 'favicon', $newFavicon);
        deleteOldFile($oldFavicon);
        $message .= 'Favicon atualizado com sucesso. ';
      }
    } else {
      $errors = array_merge($errors, $faviconErrors);
    }
  }
    
  if (!empty($_FILES['login_background']) && $_FILES['login_background']['error'] === UPLOAD_ERR_OK) {
    $bgErrors = validateImageUpload($_FILES['login_background'], 5000, ['jpg', 'jpeg', 'png']);
    if (empty($bgErrors)) {
      $oldBg = getSetting($pdo, 'login_background');
      $newBg = saveUploadedFile($_FILES['login_background']);
      if ($newBg) {
        setSetting($pdo, 'login_background', $newBg);
        deleteOldFile($oldBg);
        $message .= 'Imagem de fundo atualizada com sucesso. ';
      }
    } else {
      $errors = array_merge($errors, $bgErrors);
    }
  }

  // Envio de email de teste
  if (isset($_POST['send_test']) && empty($errors)) {
    if (!$testEmail) {
      $errors[] = 'Indique um email para teste SMTP.';
    } else {
      $sent = sendMail($testEmail, 'Teste SMTP - CyberCore', '<p>Este é um email de teste SMTP enviado em ' . date('d/m/Y H:i') . '</p>', 'Teste SMTP');
      if ($sent) {
        $message .= 'Email de teste enviado para ' . htmlspecialchars($testEmail) . '. ';
      } else {
        $errors[] = 'Falha ao enviar email de teste. Verifique as configurações SMTP no servidor.';
      }
    }
    $shouldRedirect = false; // manter feedback na página
  }

  // Registar log e redirecionar se aplicável
  if ($message && empty($errors) && $shouldRedirect && !isset($_POST['send_test'])) {
    $pdo->prepare('INSERT INTO logs (user_id,type,message) VALUES (?,?,?)')->execute([$user['id'],'settings_update','Settings updated']);
    header('Location: settings.php?success=1');
    exit;
  }
}

// Carregar configurações atuais para exibição
$siteLogo = getSetting($pdo, 'site_logo');
$favicon = getSetting($pdo, 'favicon');
$loginBackground = getSetting($pdo, 'login_background');

// URLs públicas e caminhos para verificação
$siteLogoUrl = getAssetUrl($siteLogo);
$faviconUrl = getAssetUrl($favicon);
$loginBackgroundUrl = getAssetUrl($loginBackground);
$siteLogoPath = getAssetPath($siteLogo);
$faviconPath = getAssetPath($favicon);
$loginBackgroundPath = getAssetPath($loginBackground);
?>
<?php include __DIR__ . '/../inc/header.php'; ?>

<div class="card">
  <h2>Definições Gerais</h2>
  
  <?php if (!empty($message)): ?>
    <div style="background:#e8f5e9;color:#2e7d32;padding:12px;border-radius:4px;margin-bottom:16px">
      ✓ <?php echo htmlspecialchars($message); ?>
    </div>
  <?php elseif (isset($_GET['success'])): ?>
    <div style="background:#e8f5e9;color:#2e7d32;padding:12px;border-radius:4px;margin-bottom:16px">
      ✓ Configurações aplicadas com sucesso!
    </div>
  <?php endif; ?>
  
  <?php if (!empty($errors)): ?>
    <div style="background:#ffebee;color:#c62828;padding:12px;border-radius:4px;margin-bottom:16px">
      <?php foreach ($errors as $err): ?>
        ✗ <?php echo htmlspecialchars($err); ?><br>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  
  <form method="post" enctype="multipart/form-data">
    <?php echo csrf_input(); ?>
    
    <h3>Localização e Formatos</h3>
    <div class="form-row">
      <label>Idioma do Website</label>
      <input type="text" name="site_language" value="<?php echo htmlspecialchars($generalSettings['site_language']); ?>" readonly>
    </div>
    <div class="form-row">
      <label>Fuso Horário</label>
      <input type="text" name="site_timezone" value="<?php echo htmlspecialchars($generalSettings['site_timezone']); ?>" readonly>
    </div>
    <div class="form-row">
      <label>Formato da Data</label>
      <input type="text" name="date_format" value="<?php echo htmlspecialchars($generalSettings['date_format']); ?>" readonly>
    </div>
    <div class="form-row">
      <label>Formato da Hora</label>
      <input type="text" name="time_format" value="<?php echo htmlspecialchars($generalSettings['time_format']); ?>" readonly>
    </div>
    <div class="form-row">
      <label>Primeiro dia da semana</label>
      <input type="text" name="week_start" value="<?php echo htmlspecialchars($generalSettings['week_start']); ?>" readonly>
    </div>
    <div class="form-row">
      <label>Fins de semana</label>
      <input type="text" name="weekend_days" value="<?php echo htmlspecialchars($generalSettings['weekend_days']); ?>" readonly>
    </div>
    
    <h3>Moeda e Decimais</h3>
    <div class="form-row">
      <label>Moeda</label>
      <input type="text" name="currency" value="<?php echo htmlspecialchars($generalSettings['currency']); ?>" readonly>
    </div>
    <div class="form-row">
      <label>Símbolo</label>
      <input type="text" name="currency_symbol" value="<?php echo htmlspecialchars($generalSettings['currency_symbol']); ?>" readonly>
    </div>
    <div class="form-row">
      <label>Posição do símbolo</label>
      <input type="text" name="currency_position" value="Esquerda (<?php echo htmlspecialchars($generalSettings['currency_symbol']); ?>10,00)" readonly>
    </div>
    <div class="form-row">
      <label>Separador decimal</label>
      <input type="text" name="decimal_separator" value="Vírgula (,)" readonly>
    </div>
    <div class="form-row">
      <label>Nº casas decimais</label>
      <input type="number" min="2" max="2" name="decimal_precision" value="<?php echo htmlspecialchars($generalSettings['decimal_precision']); ?>" readonly>
    </div>
    
    <hr style="margin:24px 0;border:none;border-top:1px solid #ddd">
    <h3>Cron Job</h3>
    <p class="small">Executar a cada 10 minutos. Exemplo de entrada crontab:<br>*/10 * * * * curl -s "<?php echo htmlspecialchars($cronUrl ?: (SITE_URL . '/cron.php')); ?>"</p>
    <div class="form-row">
      <label>Link para Cron</label>
      <input type="url" name="cron_url" value="<?php echo htmlspecialchars($cronUrl); ?>" placeholder="https://seu-dominio.com/cron.php?token=xyz" required>
    </div>
    <div class="form-row">
      <label>Intervalo</label>
      <input type="text" value="<?php echo htmlspecialchars($cronInterval); ?> minutos" readonly>
    </div>
    <div class="form-row">
      <label>Última execução</label>
      <input type="text" value="<?php echo $cronLastRun ? htmlspecialchars($cronLastRun) : 'Ainda não executado'; ?>" readonly>
    </div>
    
    <hr style="margin:24px 0;border:none;border-top:1px solid #ddd">
    <h3>SMTP</h3>
    <div class="form-row">
      <label>Servidor SMTP</label>
      <input type="text" name="smtp_host" value="<?php echo htmlspecialchars($smtp['smtp_host']); ?>" placeholder="smtp.seudominio.com">
    </div>
    <div class="form-row">
      <label>Porta</label>
      <input type="number" min="1" max="65535" name="smtp_port" value="<?php echo htmlspecialchars($smtp['smtp_port']); ?>">
    </div>
    <div class="form-row">
      <label>Utilizador</label>
      <input type="text" name="smtp_user" value="<?php echo htmlspecialchars($smtp['smtp_user']); ?>">
    </div>
    <div class="form-row">
      <label>Password</label>
      <input type="password" name="smtp_pass" value="<?php echo htmlspecialchars($smtp['smtp_pass']); ?>">
    </div>
    <div class="form-row">
      <label>Segurança</label>
      <select name="smtp_secure">
        <option value="tls" <?php echo $smtp['smtp_secure']==='tls'?'selected':''; ?>>TLS</option>
        <option value="ssl" <?php echo $smtp['smtp_secure']==='ssl'?'selected':''; ?>>SSL</option>
        <option value="none" <?php echo $smtp['smtp_secure']==='none'?'selected':''; ?>>Sem encriptação</option>
      </select>
    </div>
    <div class="form-row">
      <label>Remetente (From)</label>
      <input type="email" name="smtp_from" value="<?php echo htmlspecialchars($smtp['smtp_from']); ?>" required>
    </div>
    <div class="form-row">
      <label>Nome do remetente</label>
      <input type="text" name="smtp_from_name" value="<?php echo htmlspecialchars($smtp['smtp_from_name']); ?>" required>
    </div>
    <div class="form-row">
      <label>Email para teste</label>
      <div style="display:flex;gap:8px;width:100%;align-items:center">
        <input type="email" name="test_email" value="" placeholder="email@teste.com" style="flex:1">
        <button type="submit" name="send_test" value="1" class="btn" style="background:#1976d2">Enviar Teste</button>
      </div>
    </div>
    
    <h3>Logo do Website</h3>
    <p class="small">Dimensões recomendadas: 150x60px. Formatos: JPG, JPEG, PNG. Máximo: 2MB.</p>
    
    <?php if ($siteLogo && file_exists($siteLogoPath)): ?>
      <div style="margin-bottom:12px">
        <img src="<?php echo htmlspecialchars($siteLogoUrl); ?>?v=<?php echo time(); ?>" alt="Logo" style="max-width:150px;max-height:60px;border:1px solid #ddd;padding:4px;border-radius:4px">
        <p class="small" style="margin:8px 0 0 0">Ficheiro atual: <?php echo htmlspecialchars(basename($siteLogo)); ?></p>
      </div>
    <?php endif; ?>
    
    <div class="form-row">
      <label>Carregar Logo</label>
      <input type="file" name="site_logo" accept="image/jpeg,image/png">
    </div>
    
    <hr style="margin:24px 0;border:none;border-top:1px solid #ddd">
    
    <h3>Favicon (32x32)</h3>
    <p class="small">Dimensões: 32x32px. Formatos: JPG, JPEG, PNG. Máximo: 500KB.</p>
    
    <?php if ($favicon && file_exists($faviconPath)): ?>
      <div style="margin-bottom:12px">
        <img src="<?php echo htmlspecialchars($faviconUrl); ?>?v=<?php echo time(); ?>" alt="Favicon" style="width:32px;height:32px;border:1px solid #ddd;padding:2px;border-radius:4px">
        <p class="small" style="margin:8px 0 0 0">Ficheiro atual: <?php echo htmlspecialchars(basename($favicon)); ?></p>
      </div>
    <?php endif; ?>
    
    <div class="form-row">
      <label>Carregar Favicon</label>
      <input type="file" name="favicon" accept="image/jpeg,image/png">
    </div>
    
    <hr style="margin:24px 0;border:none;border-top:1px solid #ddd">
    
    <h3>Imagem de Fundo da Página de Login</h3>
    <p class="small">Dimensões recomendadas: 1920x1080px. Formatos: JPG, JPEG, PNG. Máximo: 5MB.</p>
    
    <?php if ($loginBackground && file_exists($loginBackgroundPath)): ?>
      <div style="margin-bottom:12px">
        <img src="<?php echo htmlspecialchars($loginBackgroundUrl); ?>?v=<?php echo time(); ?>" alt="Background" style="max-width:200px;max-height:150px;border:1px solid #ddd;padding:4px;border-radius:4px;object-fit:cover">
        <p class="small" style="margin:8px 0 0 0">Ficheiro atual: <?php echo htmlspecialchars(basename($loginBackground)); ?></p>
      </div>
    <?php endif; ?>
    
    <div class="form-row">
      <label>Carregar Imagem de Fundo</label>
      <input type="file" name="login_background" accept="image/jpeg,image/png">
    </div>
    
    <hr style="margin:24px 0;border:none;border-top:1px solid #ddd">
    
    <div class="form-row">
      <button type="submit" class="btn">Guardar Configurações</button>
    </div>
  </form>
</div>

<?php include __DIR__ . '/../inc/footer.php'; ?>
