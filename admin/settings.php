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
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'geral';

$generalDefaults = getGeneralSettingsDefaults();
$generalSettings = getGeneralSettings($pdo);
$cronDefaultUrl = rtrim(SITE_URL, '/') . '/cron.php';
$cronUrl = getSetting($pdo, 'cron_url', $cronDefaultUrl);
if (!$cronUrl) {
    $cronUrl = $cronDefaultUrl;
}
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

  // Envio de email de teste (processo dedicado + redirect PRG)
  if (isset($_POST['send_test'])) {
    $testEmail = trim($_POST['test_email'] ?? '');
    if (!$testEmail || !filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
      header('Location: settings.php?tab=email&smtp_test=1&ok=0');
      exit;
    }
    $sent = sendMail($testEmail, 'Teste SMTP - CyberCore', '<p>Este é um email de teste SMTP enviado em ' . date('d/m/Y H:i') . '</p>', 'Teste SMTP');
    header('Location: settings.php?tab=email&smtp_test=1&ok=' . ($sent ? '1' : '0'));
    exit;
  }

  // Eliminar imagens existentes (logo, favicon, fundo)
  if (isset($_POST['delete_logo'])) {
    $oldLogo = getSetting($pdo, 'site_logo');
    if ($oldLogo) {
      deleteOldFile($oldLogo);
      setSetting($pdo, 'site_logo', '');
      $message .= 'Logo removido com sucesso. ';
    } else {
      $errors[] = 'Não existe logo para remover.';
    }
  }
  if (isset($_POST['delete_favicon'])) {
    $oldFavicon = getSetting($pdo, 'favicon');
    if ($oldFavicon) {
      deleteOldFile($oldFavicon);
      setSetting($pdo, 'favicon', '');
      $message .= 'Favicon removido com sucesso. ';
    } else {
      $errors[] = 'Não existe favicon para remover.';
    }
  }
  if (isset($_POST['delete_login_background'])) {
    $oldBg = getSetting($pdo, 'login_background');
    if ($oldBg) {
      deleteOldFile($oldBg);
      setSetting($pdo, 'login_background', '');
      $message .= 'Imagem de fundo removida com sucesso. ';
    } else {
      $errors[] = 'Não existe imagem de fundo para remover.';
    }
  }

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
  if (!$cronUrlPost) {
    $cronUrlPost = $cronDefaultUrl;
  }
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

  if ($smtpPort !== '' && !ctype_digit($smtpPort)) {
    $errors[] = 'Porta SMTP deve ser numérica.';
  }
  if ($smtpSecure && !in_array($smtpSecure, ['tls', 'ssl', 'none'], true)) {
    $errors[] = 'Tipo de segurança SMTP inválido.';
  }
  if ($smtpFrom && !filter_var($smtpFrom, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Email de remetente inválido.';
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

  // Registar log e redirecionar se aplicável
  if ($message && empty($errors) && $shouldRedirect && !isset($_POST['send_test'])) {
    $pdo->prepare('INSERT INTO logs (user_id,type,message) VALUES (?,?,?)')->execute([$user['id'],'settings_update','Settings updated']);
    header('Location: settings.php?tab=' . $activeTab . '&success=1');
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
  <h2>Configurações do Sistema</h2>
  
  <!-- Abas de navegação -->
  <div style="display:flex;gap:8px;border-bottom:2px solid #ddd;margin-bottom:24px;flex-wrap:wrap">
    <a href="settings.php?tab=geral" style="padding:12px 16px;cursor:pointer;border-bottom:3px solid <?php echo $activeTab==='geral'?'#1976d2':'transparent'; ?>;color:<?php echo $activeTab==='geral'?'#1976d2':'#666'; ?>;font-weight:<?php echo $activeTab==='geral'?'bold':'normal'; ?>;text-decoration:none">Geral</a>
    <a href="settings.php?tab=localizacao" style="padding:12px 16px;cursor:pointer;border-bottom:3px solid <?php echo $activeTab==='localizacao'?'#1976d2':'transparent'; ?>;color:<?php echo $activeTab==='localizacao'?'#1976d2':'#666'; ?>;font-weight:<?php echo $activeTab==='localizacao'?'bold':'normal'; ?>;text-decoration:none">Localização e Formatos</a>
    <a href="settings.php?tab=email" style="padding:12px 16px;cursor:pointer;border-bottom:3px solid <?php echo $activeTab==='email'?'#1976d2':'transparent'; ?>;color:<?php echo $activeTab==='email'?'#1976d2':'#666'; ?>;font-weight:<?php echo $activeTab==='email'?'bold':'normal'; ?>;text-decoration:none">Configuração de Email</a>
    <a href="settings.php?tab=cron" style="padding:12px 16px;cursor:pointer;border-bottom:3px solid <?php echo $activeTab==='cron'?'#1976d2':'transparent'; ?>;color:<?php echo $activeTab==='cron'?'#1976d2':'#666'; ?>;font-weight:<?php echo $activeTab==='cron'?'bold':'normal'; ?>;text-decoration:none">Cron Job</a>
    <a href="settings.php?tab=modelos" style="padding:12px 16px;cursor:pointer;border-bottom:3px solid <?php echo $activeTab==='modelos'?'#1976d2':'transparent'; ?>;color:<?php echo $activeTab==='modelos'?'#1976d2':'#666'; ?>;font-weight:<?php echo $activeTab==='modelos'?'bold':'normal'; ?>;text-decoration:none">Modelos de Email</a>
    <a href="settings.php?tab=notificacoes" style="padding:12px 16px;cursor:pointer;border-bottom:3px solid <?php echo $activeTab==='notificacoes'?'#1976d2':'transparent'; ?>;color:<?php echo $activeTab==='notificacoes'?'#1976d2':'#666'; ?>;font-weight:<?php echo $activeTab==='notificacoes'?'bold':'normal'; ?>;text-decoration:none">Notificações</a>
    <a href="settings.php?tab=integracao" style="padding:12px 16px;cursor:pointer;border-bottom:3px solid <?php echo $activeTab==='integracao'?'#1976d2':'transparent'; ?>;color:<?php echo $activeTab==='integracao'?'#1976d2':'#666'; ?>;font-weight:<?php echo $activeTab==='integracao'?'bold':'normal'; ?>;text-decoration:none">Integração</a>
  </div>
  
  <!-- Mensagens -->
  <?php if (!empty($message)): ?>
    <div style="background:#e8f5e9;color:#2e7d32;padding:12px;border-radius:4px;margin-bottom:16px">
      ✓ <?php echo htmlspecialchars($message); ?>
    </div>
  <?php elseif (isset($_GET['success'])): ?>
    <div style="background:#e8f5e9;color:#2e7d32;padding:12px;border-radius:4px;margin-bottom:16px">
      ✓ Configurações aplicadas com sucesso!
    </div>
  <?php elseif (isset($_GET['smtp_test'])): ?>
    <div style="background:<?php echo (isset($_GET['ok']) && $_GET['ok']==='1') ? '#e8f5e9' : '#ffebee'; ?>;color:<?php echo (isset($_GET['ok']) && $_GET['ok']==='1') ? '#2e7d32' : '#c62828'; ?>;padding:12px;border-radius:4px;margin-bottom:16px">
      <?php echo (isset($_GET['ok']) && $_GET['ok']==='1') ? '✓ Email de teste enviado com sucesso.' : '✗ Falha ao enviar email de teste.'; ?>
    </div>
  <?php endif; ?>
  
  <?php if (!empty($errors)): ?>
    <div style="background:#ffebee;color:#c62828;padding:12px;border-radius:4px;margin-bottom:16px">
      <?php foreach ($errors as $err): ?>
        ✗ <?php echo htmlspecialchars($err); ?><br>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  
  <!-- TAB: GERAL -->
  <?php if ($activeTab === 'geral'): ?>
    <form method="post" enctype="multipart/form-data">
      <?php echo csrf_input(); ?>
      
      <h3>Logo do Website</h3>
      <p class="small">Dimensões recomendadas: 150x60px. Formatos: JPG, JPEG, PNG. Máximo: 2MB.</p>
      
      <?php if ($siteLogo && file_exists($siteLogoPath)): ?>
        <div style="margin-bottom:12px">
          <img src="<?php echo htmlspecialchars($siteLogoUrl); ?>?v=<?php echo time(); ?>" alt="Logo" style="max-width:150px;max-height:60px;border:1px solid #ddd;padding:4px;border-radius:4px">
          <p class="small" style="margin:8px 0 0 0">Ficheiro atual: <?php echo htmlspecialchars(basename($siteLogo)); ?></p>
          <div style="margin-top:8px">
            <button type="submit" name="delete_logo" value="1" class="btn" style="background:#c62828" onclick="return confirm('Tem a certeza que deseja eliminar o logo? Esta ação não pode ser desfeita.')">Eliminar Logo</button>
          </div>
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
          <div style="margin-top:8px">
            <button type="submit" name="delete_favicon" value="1" class="btn" style="background:#c62828" onclick="return confirm('Tem a certeza que deseja eliminar o favicon?')">Eliminar Favicon</button>
          </div>
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
          <div style="margin-top:8px">
            <button type="submit" name="delete_login_background" value="1" class="btn" style="background:#c62828" onclick="return confirm('Tem a certeza que deseja eliminar a imagem de fundo da página de login?')">Eliminar Imagem</button>
          </div>
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
  <?php endif; ?>
  
  <!-- TAB: LOCALIZAÇÃO E FORMATOS -->
  <?php if ($activeTab === 'localizacao'): ?>
    <form method="post">
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
      
      <hr style="margin:24px 0;border:none;border-top:1px solid #ddd">
      
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
        <input type="text" name="currency_position" value="Direita (10,00<?php echo htmlspecialchars($generalSettings['currency_symbol']); ?>)" readonly>
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
      
      <div class="form-row">
        <button type="submit" class="btn">Guardar Configurações</button>
      </div>
    </form>
  <?php endif; ?>
  
  <!-- TAB: EMAIL -->
  <?php if ($activeTab === 'email'): ?>
    <form method="post">
      <?php echo csrf_input(); ?>
      
      <h3>Configuração SMTP</h3>
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
      
      <hr style="margin:24px 0;border:none;border-top:1px solid #ddd">
      
      <div class="form-row">
        <button type="submit" class="btn">Guardar Configurações SMTP</button>
      </div>
    </form>
    
    <!-- Formulário separado para teste de SMTP -->
    <div style="margin-top:32px;padding-top:24px;border-top:1px solid #ddd">
      <h3>Teste de SMTP</h3>
      <form method="post">
        <?php echo csrf_input(); ?>
        <div class="form-row">
          <label>Email para teste</label>
          <div style="display:flex;gap:8px;width:100%;align-items:center">
            <input type="email" name="test_email" value="" placeholder="email@teste.com" style="flex:1" required>
            <button type="submit" name="send_test" value="1" class="btn" style="background:#1976d2">Enviar Teste</button>
          </div>
        </div>
      </form>
    </div>
  <?php endif; ?>
  
  <!-- TAB: CRON JOB -->
  <?php if ($activeTab === 'cron'): ?>
    <form method="post">
      <?php echo csrf_input(); ?>
      
      <h3>Configuração de Cron Job</h3>
      <p class="small">Executar a cada 10 minutos. Exemplo de entrada crontab:<br><code>*/10 * * * * curl -s \"<?php echo htmlspecialchars($cronUrl ?: $cronDefaultUrl); ?>\"</code></p>
      
      <div class="form-row">
        <label>Link para Cron</label>
        <input type="url" name="cron_url" value="<?php echo htmlspecialchars($cronUrl ?: $cronDefaultUrl); ?>" placeholder="<?php echo htmlspecialchars($cronDefaultUrl); ?>" required>
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
      
      <div class="form-row">
        <button type="submit" class="btn">Guardar Configurações Cron</button>
      </div>
    </form>
  <?php endif; ?>
  
  <!-- TAB: MODELOS DE EMAIL -->
  <?php if ($activeTab === 'modelos'): ?>
    <div style="padding:20px;background:#f5f5f5;border-radius:4px;text-align:center">
      <p style="color:#666;font-style:italic">Gestão de modelos de email em desenvolvimento...</p>
      <p class="small">Aqui poderá criar e personalizar modelos de email para diferentes eventos do sistema (confirmações, alertas, notificações, etc.).</p>
    </div>
  <?php endif; ?>
  
  <!-- TAB: NOTIFICAÇÕES -->
  <?php if ($activeTab === 'notificacoes'): ?>
    <div style="padding:20px;background:#f5f5f5;border-radius:4px;text-align:center">
      <p style="color:#666;font-style:italic">Gestão de notificações em desenvolvimento...</p>
      <p class="small">Aqui poderá configurar as notificações do sistema (email, SMS, webhook, etc.).</p>
    </div>
  <?php endif; ?>
  
  <!-- TAB: INTEGRAÇÃO -->
  <?php if ($activeTab === 'integracao'): ?>
    <div style="padding:20px;background:#f5f5f5;border-radius:4px;text-align:center">
      <p style="color:#666;font-style:italic">Integrações em desenvolvimento...</p>
      <p class="small">Aqui poderá integrar com sistemas externos como cPanel, Plesk, Gateways de Pagamento, etc.</p>
    </div>
  <?php endif; ?>
  
</div>

<?php include __DIR__ . '/../inc/footer.php'; ?>
