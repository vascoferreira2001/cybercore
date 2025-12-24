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

  // === EQUIPA: Departamentos ===
  if (isset($_POST['add_department'])) {
    $name = trim($_POST['department_name'] ?? '');
    if ($name !== '') {
      $stmt = $pdo->prepare('INSERT IGNORE INTO departments (name, active) VALUES (?, 1)');
      $stmt->execute([$name]);
      header('Location: settings.php?tab=equipa&success=1');
      exit;
    }
  }
  if (isset($_POST['toggle_department'])) {
    $id = intval($_POST['department_id'] ?? 0);
    $newActive = intval($_POST['new_active'] ?? 1);
    if ($id > 0) {
      $stmt = $pdo->prepare('UPDATE departments SET active = ? WHERE id = ?');
      $stmt->execute([$newActive, $id]);
      header('Location: settings.php?tab=equipa&success=1');
      exit;
    }
  }
  if (isset($_POST['delete_department'])) {
    $id = intval($_POST['department_id'] ?? 0);
    if ($id > 0) {
      $pdo->prepare('DELETE FROM department_permissions WHERE department_id = ?')->execute([$id]);
      $pdo->prepare('DELETE FROM departments WHERE id = ?')->execute([$id]);
      header('Location: settings.php?tab=equipa&success=1');
      exit;
    }
  }

  // === FUNÇÕES: Permissões por departamento ===
  if (isset($_POST['save_dept_permissions'])) {
    foreach ($_POST['perm'] ?? [] as $deptId => $byRes) {
      foreach ($byRes as $res => $flags) {
        $stmt = $pdo->prepare('INSERT INTO department_permissions (department_id, resource, can_view, can_edit, can_delete, can_operate) VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE can_view=VALUES(can_view), can_edit=VALUES(can_edit), can_delete=VALUES(can_delete), can_operate=VALUES(can_operate)');
        $stmt->execute([$deptId, $res, isset($flags['view'])?1:0, isset($flags['edit'])?1:0, isset($flags['delete'])?1:0, isset($flags['operate'])?1:0]);
      }
    }
    header('Location: settings.php?tab=funcoes&success=1');
    exit;
  }

  // === CLIENTE: Permissões ===
  if (isset($_POST['save_client_permissions'])) {
    $clientPermsKeys = ['view_invoices','pay_invoices','open_tickets','manage_domains','view_services','edit_profile'];
    foreach ($clientPermsKeys as $k) {
      $allowed = isset($_POST['client_perm'][$k]) ? 1 : 0;
      $stmt = $pdo->prepare('INSERT INTO client_permissions (permission_key, allowed) VALUES (?, ?) ON DUPLICATE KEY UPDATE allowed = VALUES(allowed)');
      $stmt->execute([$k, $allowed]);
    }
    header('Location: settings.php?tab=cliente&success=1');
    exit;
  }

  // === EMPRESA: Dados da empresa ===
  if (isset($_POST['save_company'])) {
    $errors = [];
    $fields = ['company_name','company_address','company_phone','company_email','company_website','company_nif'];
    foreach ($fields as $f) {
      setSetting($pdo, $f, trim($_POST[$f] ?? ''));
    }
    // Upload logo da empresa
    if (!empty($_FILES['company_logo']) && $_FILES['company_logo']['error'] === UPLOAD_ERR_OK) {
      $logoErrors = validateImageUpload($_FILES['company_logo'], 3000, ['jpg','jpeg','png']);
      if (empty($logoErrors)) {
        $old = getSetting($pdo, 'company_logo');
        $new = saveUploadedFile($_FILES['company_logo']);
        if ($new) {
          setSetting($pdo, 'company_logo', $new);
          deleteOldFile($old);
        }
      } else {
        $errors = array_merge($errors, $logoErrors);
      }
    }
    if (!empty($errors)) {
      $message = implode(' ', $errors);
      // Não redirecionar, mostrar erro
    } else {
      header('Location: settings.php?tab=empresa&success=1');
      exit;
    }
  }

  // === CATEGORIAS: Adicionar/Toggle/Remover ===
  if (isset($_POST['add_category'])) {
    $name = trim($_POST['category_name'] ?? '');
    if ($name !== '') {
      $stmt = $pdo->prepare('INSERT IGNORE INTO service_categories (name, active) VALUES (?, 1)');
      $stmt->execute([$name]);
      header('Location: settings.php?tab=categorias&success=1');
      exit;
    }
  }
  if (isset($_POST['toggle_category'])) {
    $id = intval($_POST['category_id'] ?? 0);
    $newActive = intval($_POST['new_active'] ?? 1);
    if ($id > 0) {
      $pdo->prepare('UPDATE service_categories SET active = ? WHERE id = ?')->execute([$newActive, $id]);
      header('Location: settings.php?tab=categorias&success=1');
      exit;
    }
  }
  if (isset($_POST['delete_category'])) {
    $id = intval($_POST['category_id'] ?? 0);
    if ($id > 0) {
      $pdo->prepare('DELETE FROM service_categories WHERE id = ?')->execute([$id]);
      header('Location: settings.php?tab=categorias&success=1');
      exit;
    }
  }

  // === IMPOSTOS: Adicionar/Toggle/Remover ===
  if (isset($_POST['add_tax'])) {
    $name = trim($_POST['tax_name'] ?? '');
    $rate = trim($_POST['tax_rate'] ?? '');
    if ($name !== '' && is_numeric($rate)) {
      $stmt = $pdo->prepare('INSERT INTO taxes (name, rate, active) VALUES (?, ?, 1)');
      $stmt->execute([$name, $rate]);
      header('Location: settings.php?tab=impostos&success=1');
      exit;
    }
  }
  if (isset($_POST['toggle_tax'])) {
    $id = intval($_POST['tax_id'] ?? 0);
    $newActive = intval($_POST['new_active'] ?? 1);
    if ($id > 0) {
      $pdo->prepare('UPDATE taxes SET active = ? WHERE id = ?')->execute([$newActive, $id]);
      header('Location: settings.php?tab=impostos&success=1');
      exit;
    }
  }
  if (isset($_POST['delete_tax'])) {
    $id = intval($_POST['tax_id'] ?? 0);
    if ($id > 0) {
      $pdo->prepare('DELETE FROM taxes WHERE id = ?')->execute([$id]);
      header('Location: settings.php?tab=impostos&success=1');
      exit;
    }
  }

  // === MÉTODOS DE PAGAMENTO: Adicionar/Toggle/Remover ===
  if (isset($_POST['add_method'])) {
    $name = trim($_POST['method_name'] ?? '');
    $gateway = trim($_POST['method_gateway'] ?? '');
    if ($name !== '') {
      $stmt = $pdo->prepare('INSERT INTO payment_methods (name, gateway, active) VALUES (?, ?, 1)');
      $stmt->execute([$name, $gateway]);
      header('Location: settings.php?tab=pagamentos&success=1');
      exit;
    }
  }
  if (isset($_POST['toggle_method'])) {
    $id = intval($_POST['method_id'] ?? 0);
    $newActive = intval($_POST['new_active'] ?? 1);
    if ($id > 0) {
      $pdo->prepare('UPDATE payment_methods SET active = ? WHERE id = ?')->execute([$newActive, $id]);
      header('Location: settings.php?tab=pagamentos&success=1');
      exit;
    }
  }
  if (isset($_POST['delete_method'])) {
    $id = intval($_POST['method_id'] ?? 0);
    if ($id > 0) {
      $pdo->prepare('DELETE FROM payment_methods WHERE id = ?')->execute([$id]);
      header('Location: settings.php?tab=pagamentos&success=1');
      exit;
    }
  }

  // === ABAS GERAL/LOCALIZAÇÃO/EMAIL/CRON: só processar se não for ação específica das novas abas ===
  
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

  // Apenas processar configurações gerais/localization/email/cron se não houver ação específica
  $isSpecificAction = isset($_POST['add_department']) || isset($_POST['toggle_department']) || isset($_POST['delete_department']) ||
                      isset($_POST['save_dept_permissions']) || isset($_POST['save_client_permissions']) || isset($_POST['save_company']) ||
                      isset($_POST['add_category']) || isset($_POST['toggle_category']) || isset($_POST['delete_category']) ||
                      isset($_POST['add_tax']) || isset($_POST['toggle_tax']) || isset($_POST['delete_tax']) ||
                      isset($_POST['add_method']) || isset($_POST['toggle_method']) || isset($_POST['delete_method']);

  if (!$isSpecificAction) {
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
    <a href="settings.php?tab=equipa" style="padding:12px 16px;cursor:pointer;border-bottom:3px solid <?php echo $activeTab==='equipa'?'#1976d2':'transparent'; ?>;color:<?php echo $activeTab==='equipa'?'#1976d2':'#666'; ?>;font-weight:<?php echo $activeTab==='equipa'?'bold':'normal'; ?>;text-decoration:none">Equipa</a>
    <a href="settings.php?tab=funcoes" style="padding:12px 16px;cursor:pointer;border-bottom:3px solid <?php echo $activeTab==='funcoes'?'#1976d2':'transparent'; ?>;color:<?php echo $activeTab==='funcoes'?'#1976d2':'#666'; ?>;font-weight:<?php echo $activeTab==='funcoes'?'bold':'normal'; ?>;text-decoration:none">Funções da Equipa</a>
    <a href="settings.php?tab=cliente" style="padding:12px 16px;cursor:pointer;border-bottom:3px solid <?php echo $activeTab==='cliente'?'#1976d2':'transparent'; ?>;color:<?php echo $activeTab==='cliente'?'#1976d2':'#666'; ?>;font-weight:<?php echo $activeTab==='cliente'?'bold':'normal'; ?>;text-decoration:none">Permissões do Cliente</a>
    <a href="settings.php?tab=empresa" style="padding:12px 16px;cursor:pointer;border-bottom:3px solid <?php echo $activeTab==='empresa'?'#1976d2':'transparent'; ?>;color:<?php echo $activeTab==='empresa'?'#1976d2':'#666'; ?>;font-weight:<?php echo $activeTab==='empresa'?'bold':'normal'; ?>;text-decoration:none">Empresa</a>
    <a href="settings.php?tab=categorias" style="padding:12px 16px;cursor:pointer;border-bottom:3px solid <?php echo $activeTab==='categorias'?'#1976d2':'transparent'; ?>;color:<?php echo $activeTab==='categorias'?'#1976d2':'#666'; ?>;font-weight:<?php echo $activeTab==='categorias'?'bold':'normal'; ?>;text-decoration:none">Categorias de Serviços</a>
    <a href="settings.php?tab=impostos" style="padding:12px 16px;cursor:pointer;border-bottom:3px solid <?php echo $activeTab==='impostos'?'#1976d2':'transparent'; ?>;color:<?php echo $activeTab==='impostos'?'#1976d2':'#666'; ?>;font-weight:<?php echo $activeTab==='impostos'?'bold':'normal'; ?>;text-decoration:none">Impostos</a>
    <a href="settings.php?tab=pagamentos" style="padding:12px 16px;cursor:pointer;border-bottom:3px solid <?php echo $activeTab==='pagamentos'?'#1976d2':'transparent'; ?>;color:<?php echo $activeTab==='pagamentos'?'#1976d2':'#666'; ?>;font-weight:<?php echo $activeTab==='pagamentos'?'bold':'normal'; ?>;text-decoration:none">Métodos de Pagamento</a>
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
  
  <!-- TAB: EQUIPA (Departamentos) -->
  <?php if ($activeTab === 'equipa'): ?>
    <?php 
      $departments = $pdo->query('SELECT * FROM departments ORDER BY name')->fetchAll();
    ?>
    <form method="post">
      <?php echo csrf_input(); ?>
      <h3>Departamentos</h3>
      <p class="small">Criar e gerir departamentos da equipa. Por defeito: Suporte ao Cliente, Suporte Técnico, Suporte Financeiro.</p>
      <div class="form-row">
        <label>Novo departamento</label>
        <div style="display:flex;gap:8px;width:100%;align-items:center">
          <input type="text" name="department_name" placeholder="Nome do Departamento" style="flex:1" required>
          <button type="submit" name="add_department" value="1" class="btn">Adicionar</button>
        </div>
      </div>
    </form>
    <div style="margin-top:16px">
      <table style="width:100%;border-collapse:collapse">
        <thead>
          <tr style="background:#f7f7f7">
            <th style="text-align:left;padding:8px;border:1px solid #ddd">Departamento</th>
            <th style="text-align:left;padding:8px;border:1px solid #ddd">Estado</th>
            <th style="text-align:left;padding:8px;border:1px solid #ddd">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($departments as $d): ?>
            <tr>
              <td style="padding:8px;border:1px solid #ddd"><?php echo htmlspecialchars($d['name']); ?></td>
              <td style="padding:8px;border:1px solid #ddd"><?php echo $d['active'] ? 'Ativo' : 'Inativo'; ?></td>
              <td style="padding:8px;border:1px solid #ddd">
                <form method="post" style="display:inline-block;margin-right:6px">
                  <?php echo csrf_input(); ?>
                  <input type="hidden" name="department_id" value="<?php echo (int)$d['id']; ?>">
                  <input type="hidden" name="new_active" value="<?php echo $d['active']?0:1; ?>">
                  <button type="submit" name="toggle_department" value="1" class="btn" style="background:#1976d2">
                    <?php echo $d['active'] ? 'Desativar' : 'Ativar'; ?>
                  </button>
                </form>
                <form method="post" style="display:inline-block" onsubmit="return confirm('Eliminar este departamento?');">
                  <?php echo csrf_input(); ?>
                  <input type="hidden" name="department_id" value="<?php echo (int)$d['id']; ?>">
                  <button type="submit" name="delete_department" value="1" class="btn" style="background:#c62828">Remover</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
  
  <!-- TAB: FUNÇÕES DA EQUIPA (Permissões por departamento) -->
  <?php if ($activeTab === 'funcoes'): ?>
    <?php 
      $departments = $pdo->query('SELECT * FROM departments ORDER BY name')->fetchAll();
      $resources = ['dashboard','tickets','customers','services','finance','settings','reports'];
      // Carregar permissões atuais
      $perms = [];
      $rows = $pdo->query('SELECT * FROM department_permissions')->fetchAll();
      foreach ($rows as $r) {
        $perms[$r['department_id']][$r['resource']] = $r;
      }
    ?>
    <form method="post">
      <?php echo csrf_input(); ?>
      <h3>Permissões por Departamento</h3>
      <div style="overflow:auto">
        <table style="width:100%;border-collapse:collapse">
          <thead>
            <tr style="background:#f7f7f7">
              <th style="padding:8px;border:1px solid #ddd">Departamento</th>
              <?php foreach ($resources as $res): ?>
                <th style="padding:8px;border:1px solid #ddd"><?php echo htmlspecialchars($res); ?></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($departments as $d): ?>
              <tr>
                <td style="padding:8px;border:1px solid #ddd;white-space:nowrap"><?php echo htmlspecialchars($d['name']); ?></td>
                <?php foreach ($resources as $res): 
                  $p = $perms[$d['id']][$res] ?? ['can_view'=>1,'can_edit'=>0,'can_delete'=>0,'can_operate'=>0];
                ?>
                  <td style="padding:8px;border:1px solid #ddd">
                    <label style="display:inline-block;margin-right:6px"><input type="checkbox" name="perm[<?php echo $d['id']; ?>][<?php echo $res; ?>][view]" <?php echo $p['can_view']?'checked':''; ?>> Ver</label>
                    <label style="display:inline-block;margin-right:6px"><input type="checkbox" name="perm[<?php echo $d['id']; ?>][<?php echo $res; ?>][edit]" <?php echo $p['can_edit']?'checked':''; ?>> Editar</label>
                    <label style="display:inline-block;margin-right:6px"><input type="checkbox" name="perm[<?php echo $d['id']; ?>][<?php echo $res; ?>][delete]" <?php echo $p['can_delete']?'checked':''; ?>> Remover</label>
                    <label style="display:inline-block"><input type="checkbox" name="perm[<?php echo $d['id']; ?>][<?php echo $res; ?>][operate]" <?php echo $p['can_operate']?'checked':''; ?>> Operar</label>
                  </td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="form-row" style="margin-top:12px">
        <button type="submit" name="save_dept_permissions" value="1" class="btn">Guardar Permissões</button>
      </div>
    </form>
  <?php endif; ?>
  
  <!-- TAB: PERMISSÕES DO CLIENTE -->
  <?php if ($activeTab === 'cliente'): ?>
    <?php 
      $clientPermsKeys = ['view_invoices','pay_invoices','open_tickets','manage_domains','view_services','edit_profile'];
      $rows = $pdo->query('SELECT * FROM client_permissions')->fetchAll();
      $permMap = [];
      foreach ($rows as $r) $permMap[$r['permission_key']] = (int)$r['allowed'];
    ?>
    <form method="post">
      <?php echo csrf_input(); ?>
      <h3>Permissões dos Clientes</h3>
      <div class="form-row">
        <?php foreach ($clientPermsKeys as $k): ?>
          <label style="display:block;margin-bottom:8px"><input type="checkbox" name="client_perm[<?php echo $k; ?>]" <?php echo !empty($permMap[$k])?'checked':''; ?>> <?php echo htmlspecialchars(str_replace('_',' ', ucfirst($k))); ?></label>
        <?php endforeach; ?>
      </div>
      <div class="form-row" style="margin-top:12px">
        <button type="submit" name="save_client_permissions" value="1" class="btn">Guardar Permissões</button>
      </div>
    </form>
  <?php endif; ?>
  
  <!-- TAB: EMPRESA -->
  <?php if ($activeTab === 'empresa'): ?>
    <?php 
      $company = [
        'company_name' => getSetting($pdo, 'company_name'),
        'company_address' => getSetting($pdo, 'company_address'),
        'company_phone' => getSetting($pdo, 'company_phone'),
        'company_email' => getSetting($pdo, 'company_email'),
        'company_website' => getSetting($pdo, 'company_website'),
        'company_nif' => getSetting($pdo, 'company_nif'),
        'company_logo' => getSetting($pdo, 'company_logo'),
      ];
      $companyLogoUrl = getAssetUrl($company['company_logo']);
      $companyLogoPath = getAssetPath($company['company_logo']);
    ?>
    <form method="post" enctype="multipart/form-data">
      <?php echo csrf_input(); ?>
      <div class="form-row"><label>Nome da Empresa</label><input type="text" name="company_name" value="<?php echo htmlspecialchars($company['company_name']); ?>" required></div>
      <div class="form-row"><label>Morada</label><input type="text" name="company_address" value="<?php echo htmlspecialchars($company['company_address']); ?>"></div>
      <div class="form-row"><label>Telefone/Telemóvel</label><input type="text" name="company_phone" value="<?php echo htmlspecialchars($company['company_phone']); ?>"></div>
      <div class="form-row"><label>Email</label><input type="email" name="company_email" value="<?php echo htmlspecialchars($company['company_email']); ?>"></div>
      <div class="form-row"><label>Website</label><input type="url" name="company_website" value="<?php echo htmlspecialchars($company['company_website']); ?>"></div>
      <div class="form-row"><label>NIF</label><input type="text" name="company_nif" value="<?php echo htmlspecialchars($company['company_nif']); ?>"></div>
      <?php if ($company['company_logo'] && file_exists($companyLogoPath)): ?>
        <div style="margin-bottom:12px">
          <img src="<?php echo htmlspecialchars($companyLogoUrl); ?>?v=<?php echo time(); ?>" alt="Logo Empresa" style="max-width:180px;border:1px solid #ddd;padding:4px;border-radius:4px">
          <p class="small" style="margin:8px 0 0 0">Ficheiro atual: <?php echo htmlspecialchars(basename($company['company_logo'])); ?></p>
        </div>
      <?php endif; ?>
      <div class="form-row"><label>Logo da Empresa</label><input type="file" name="company_logo" accept="image/jpeg,image/png"></div>
      <div class="form-row" style="margin-top:12px"><button type="submit" name="save_company" value="1" class="btn">Guardar Dados da Empresa</button></div>
    </form>
  <?php endif; ?>
  
  <!-- TAB: CATEGORIAS DE SERVIÇOS -->
  <?php if ($activeTab === 'categorias'): ?>
    <?php 
      $cats = $pdo->query('SELECT * FROM service_categories ORDER BY name')->fetchAll();
    ?>
    <form method="post">
      <?php echo csrf_input(); ?>
      <h3>Categorias de Serviços</h3>
      <div class="form-row">
        <label>Nova categoria</label>
        <div style="display:flex;gap:8px;width:100%;align-items:center">
          <input type="text" name="category_name" placeholder="Nome da Categoria" style="flex:1" required>
          <button type="submit" name="add_category" value="1" class="btn">Adicionar</button>
        </div>
      </div>
    </form>
    <div style="margin-top:16px">
      <table style="width:100%;border-collapse:collapse">
        <thead>
          <tr style="background:#f7f7f7"><th style="padding:8px;border:1px solid #ddd">Categoria</th><th style="padding:8px;border:1px solid #ddd">Estado</th><th style="padding:8px;border:1px solid #ddd">Ações</th></tr>
        </thead>
        <tbody>
          <?php foreach ($cats as $c): ?>
            <tr>
              <td style="padding:8px;border:1px solid #ddd"><?php echo htmlspecialchars($c['name']); ?></td>
              <td style="padding:8px;border:1px solid #ddd"><?php echo $c['active']?'Ativa':'Inativa'; ?></td>
              <td style="padding:8px;border:1px solid #ddd">
                <form method="post" style="display:inline-block;margin-right:6px">
                  <?php echo csrf_input(); ?>
                  <input type="hidden" name="category_id" value="<?php echo (int)$c['id']; ?>">
                  <input type="hidden" name="new_active" value="<?php echo $c['active']?0:1; ?>">
                  <button type="submit" name="toggle_category" value="1" class="btn" style="background:#1976d2">
                    <?php echo $c['active'] ? 'Desativar' : 'Ativar'; ?>
                  </button>
                </form>
                <form method="post" style="display:inline-block" onsubmit="return confirm('Eliminar esta categoria?');">
                  <?php echo csrf_input(); ?>
                  <input type="hidden" name="category_id" value="<?php echo (int)$c['id']; ?>">
                  <button type="submit" name="delete_category" value="1" class="btn" style="background:#c62828">Remover</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
  
  <!-- TAB: IMPOSTOS -->
  <?php if ($activeTab === 'impostos'): ?>
    <?php 
      $taxes = $pdo->query('SELECT * FROM taxes ORDER BY name')->fetchAll();
    ?>
    <form method="post">
      <?php echo csrf_input(); ?>
      <h3>Impostos</h3>
      <div class="form-row">
        <label>Nome do imposto</label>
        <input type="text" name="tax_name" placeholder="Ex.: IVA PT" required>
      </div>
      <div class="form-row">
        <label>Taxa (%)</label>
        <input type="number" step="0.01" min="0" max="100" name="tax_rate" placeholder="Ex.: 23" required>
      </div>
      <div class="form-row" style="margin-top:12px">
        <button type="submit" name="add_tax" value="1" class="btn">Adicionar Imposto</button>
      </div>
    </form>
    <div style="margin-top:16px">
      <table style="width:100%;border-collapse:collapse">
        <thead>
          <tr style="background:#f7f7f7"><th style="padding:8px;border:1px solid #ddd">Imposto</th><th style="padding:8px;border:1px solid #ddd">Taxa (%)</th><th style="padding:8px;border:1px solid #ddd">Estado</th><th style="padding:8px;border:1px solid #ddd">Ações</th></tr>
        </thead>
        <tbody>
          <?php foreach ($taxes as $t): ?>
            <tr>
              <td style="padding:8px;border:1px solid #ddd"><?php echo htmlspecialchars($t['name']); ?></td>
              <td style="padding:8px;border:1px solid #ddd"><?php echo htmlspecialchars($t['rate']); ?></td>
              <td style="padding:8px;border:1px solid #ddd"><?php echo $t['active']?'Ativo':'Inativo'; ?></td>
              <td style="padding:8px;border:1px solid #ddd">
                <form method="post" style="display:inline-block;margin-right:6px">
                  <?php echo csrf_input(); ?>
                  <input type="hidden" name="tax_id" value="<?php echo (int)$t['id']; ?>">
                  <input type="hidden" name="new_active" value="<?php echo $t['active']?0:1; ?>">
                  <button type="submit" name="toggle_tax" value="1" class="btn" style="background:#1976d2">
                    <?php echo $t['active'] ? 'Desativar' : 'Ativar'; ?>
                  </button>
                </form>
                <form method="post" style="display:inline-block" onsubmit="return confirm('Eliminar este imposto?');">
                  <?php echo csrf_input(); ?>
                  <input type="hidden" name="tax_id" value="<?php echo (int)$t['id']; ?>">
                  <button type="submit" name="delete_tax" value="1" class="btn" style="background:#c62828">Remover</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
  
  <!-- TAB: MÉTODOS DE PAGAMENTO -->
  <?php if ($activeTab === 'pagamentos'): ?>
    <?php 
      $methods = $pdo->query('SELECT * FROM payment_methods ORDER BY name')->fetchAll();
    ?>
    <form method="post">
      <?php echo csrf_input(); ?>
      <h3>Métodos de Pagamento</h3>
      <div class="form-row"><label>Nome</label><input type="text" name="method_name" placeholder="Ex.: MB Way" required></div>
      <div class="form-row"><label>Gateway</label><input type="text" name="method_gateway" placeholder="Ex.: ifthenpay"></div>
      <div class="form-row" style="margin-top:12px"><button type="submit" name="add_method" value="1" class="btn">Adicionar Método</button></div>
    </form>
    <div style="margin-top:16px">
      <table style="width:100%;border-collapse:collapse">
        <thead><tr style="background:#f7f7f7"><th style="padding:8px;border:1px solid #ddd">Método</th><th style="padding:8px;border:1px solid #ddd">Gateway</th><th style="padding:8px;border:1px solid #ddd">Estado</th><th style="padding:8px;border:1px solid #ddd">Ações</th></tr></thead>
        <tbody>
          <?php foreach ($methods as $m): ?>
            <tr>
              <td style="padding:8px;border:1px solid #ddd"><?php echo htmlspecialchars($m['name']); ?></td>
              <td style="padding:8px;border:1px solid #ddd"><?php echo htmlspecialchars($m['gateway']); ?></td>
              <td style="padding:8px;border:1px solid #ddd"><?php echo $m['active']?'Ativo':'Inativo'; ?></td>
              <td style="padding:8px;border:1px solid #ddd">
                <form method="post" style="display:inline-block;margin-right:6px">
                  <?php echo csrf_input(); ?>
                  <input type="hidden" name="method_id" value="<?php echo (int)$m['id']; ?>">
                  <input type="hidden" name="new_active" value="<?php echo $m['active']?0:1; ?>">
                  <button type="submit" name="toggle_method" value="1" class="btn" style="background:#1976d2">
                    <?php echo $m['active'] ? 'Desativar' : 'Ativar'; ?>
                  </button>
                </form>
                <form method="post" style="display:inline-block" onsubmit="return confirm('Eliminar este método?');">
                  <?php echo csrf_input(); ?>
                  <input type="hidden" name="method_id" value="<?php echo (int)$m['id']; ?>">
                  <button type="submit" name="delete_method" value="1" class="btn" style="background:#c62828">Remover</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
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
