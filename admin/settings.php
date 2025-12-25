<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/csrf.php';
require_once __DIR__ . '/../inc/settings.php';
require_once __DIR__ . '/../inc/mailer.php';
require_once __DIR__ . '/../inc/email_templates.php';

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

$maintenance = [
  'disable_login' => getSetting($pdo, 'maintenance_disable_login', '0'),
  'message' => getSetting($pdo, 'maintenance_message', ''),
  'exception_roles' => getSetting($pdo, 'maintenance_exception_roles', 'Gestor')
];

// Função auxiliar para carregar permissão (definida no início para uso global)
function getDeptPermission($pdo, $deptId, $key) {
  try {
    // Tenta a nova estrutura primeiro
    $r = $pdo->prepare('SELECT permission_value, permission_scope FROM department_permissions WHERE department_id=? AND permission_key=?');
    $r->execute([$deptId, $key]);
    $row = $r->fetch();
    if ($row) {
      return ['value'=>$row['permission_value'], 'scope'=>json_decode($row['permission_scope']??'[]', true)];
    }
    return ['value'=>'', 'scope'=>[]];
  } catch (Exception $e) {
    error_log('getDeptPermission error: ' . $e->getMessage());
    return ['value'=>'', 'scope'=>[]];
  }
}

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

  // === MODELOS DE EMAIL ===
  if (isset($_POST['update_email_template'])) {
    $templateId = (int)$_POST['template_id'];
    $data = [
      'subject' => trim($_POST['subject'] ?? ''),
      'body_html' => $_POST['body_html'] ?? '',
      'is_active' => isset($_POST['is_active']) ? 1 : 0
    ];
    
    if (updateEmailTemplate($pdo, $templateId, $data)) {
      header('Location: settings.php?tab=modelos-email&success=1');
      exit;
    } else {
      header('Location: settings.php?tab=modelos-email&error=1');
      exit;
    }
  }

  if (isset($_POST['test_email_template'])) {
    $templateKey = $_POST['template_key'] ?? '';
    $testEmail = $_POST['test_email_address'] ?? '';
    
    if ($testEmail && filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
      $testVars = [
        'user_name' => 'Utilizador Teste',
        'verification_link' => SITE_URL . 'verify_email.php?token=TEST_TOKEN_12345',
        'reset_link' => SITE_URL . 'reset_password.php?token=TEST_TOKEN_67890',
        'dashboard_link' => SITE_URL . 'dashboard.php'
      ];
      
      $sent = sendTemplatedEmail($pdo, $templateKey, $testEmail, 'Utilizador Teste', $testVars);
      header('Location: settings.php?tab=modelos-email&test=' . ($sent ? '1' : '0'));
      exit;
    } else {
      header('Location: settings.php?tab=modelos-email&test=0');
      exit;
    }
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

  // === FUNÇÕES: Permissões Hierárquicas por departamento ===
  if (isset($_POST['save_hierarchical_permissions'])) {
    try {
      foreach ($_POST['perm'] ?? [] as $deptId => $perms) {
        foreach ($perms as $key => $value) {
          $deptId = intval($deptId);
          if ($deptId <= 0) continue;
          
          // Suportar checkboxes (sim/não) e radio buttons (múltiplas opções)
          if (is_array($value)) {
            // Se for array, provavelmente é um checkbox selecionado
            $permValue = 'sim';
          } else {
            $permValue = trim($value);
          }
          
          // Inserir ou atualizar a permissão
          $stmt = $pdo->prepare('INSERT INTO department_permissions (department_id, permission_key, permission_value, permission_scope) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE permission_value=VALUES(permission_value), permission_scope=VALUES(permission_scope)');
          $stmt->execute([$deptId, $key, $permValue, '[]']);
        }
      }
      header('Location: settings.php?tab=funcoes&success=1');
      exit;
    } catch (Exception $e) {
      error_log('save_hierarchical_permissions error: ' . $e->getMessage());
      $errors[] = 'Erro ao guardar permissões: ' . $e->getMessage();
    }
  }

  // === PERMISSÕES (compatibilidade com antiga) ===
  if (isset($_POST['save_dept_permissions'])) {
    foreach ($_POST['perm'] ?? [] as $deptId => $byRes) {
      foreach ($byRes as $res => $flags) {
        $stmt = $pdo->prepare('INSERT INTO department_permissions (department_id, permission_key, permission_value, permission_scope) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE permission_value=VALUES(permission_value), permission_scope=VALUES(permission_scope)');
        $permValue = json_encode(['can_view'=>isset($flags['view'])?1:0, 'can_edit'=>isset($flags['edit'])?1:0, 'can_delete'=>isset($flags['delete'])?1:0, 'can_operate'=>isset($flags['operate'])?1:0]);
        $stmt->execute([$deptId, $res, $permValue, '[]']);
      }
    }
    header('Location: settings.php?tab=funcoes&success=1');
    exit;
  }

  // === CLIENTE: Permissões ===
  if (isset($_POST['save_client_permissions'])) {
    $clientPermsKeys = [
      'view_invoices',
      'pay_invoices',
      'open_tickets',
      'manage_domains',
      'view_services',
      'edit_profile',
      'client_view_documents',
      'client_add_documents'
    ];
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

  // === MANUTENÇÃO ===
  if (isset($_POST['save_maintenance'])) {
    $disableLogin = isset($_POST['maintenance_disable_login']) ? '1' : '0';
    $messageText = trim($_POST['maintenance_message'] ?? '');
    $exceptionRoles = trim($_POST['maintenance_exception_roles'] ?? 'Gestor');

    setSetting($pdo, 'maintenance_disable_login', $disableLogin);
    setSetting($pdo, 'maintenance_message', $messageText);
    setSetting($pdo, 'maintenance_exception_roles', $exceptionRoles ?: 'Gestor');

    header('Location: settings.php?tab=manutencao&success=1');
    exit;
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
                      isset($_POST['add_method']) || isset($_POST['toggle_method']) || isset($_POST['delete_method']) ||
                      isset($_POST['save_maintenance']);

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
    <a href="settings.php?tab=manutencao" style="padding:12px 16px;cursor:pointer;border-bottom:3px solid <?php echo $activeTab==='manutencao'?'#1976d2':'transparent'; ?>;color:<?php echo $activeTab==='manutencao'?'#1976d2':'#666'; ?>;font-weight:<?php echo $activeTab==='manutencao'?'bold':'normal'; ?>;text-decoration:none">Manutenção</a>
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

  <!-- TAB: MANUTENÇÃO -->
  <?php if ($activeTab === 'manutencao'): ?>
    <form method="post">
      <?php echo csrf_input(); ?>
      <h3>Modo de Manutenção</h3>
      <p class="small" style="margin-bottom:12px">Controla a visibilidade de menus na área de cliente e o acesso ao login durante manutenção.</p>

      <div style="margin-bottom:16px;padding:12px;border:1px solid #ddd;border-radius:4px">
        <h4 style="margin-top:0">Login</h4>
        <label style="display:block;margin-bottom:8px"><input type="checkbox" name="maintenance_disable_login" <?php echo $maintenance['disable_login']==='1'?'checked':''; ?>> Desativar o Login (mostra aviso de manutenção; só exceções podem entrar)</label>
        <label style="display:block;margin-bottom:8px">Mensagem de manutenção</label>
        <textarea name="maintenance_message" rows="3" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px"><?php echo htmlspecialchars($maintenance['message']); ?></textarea>
        <p class="small" style="margin-top:6px">Texto exibido no aviso. Sugestão: informe janelas de manutenção e contactos.</p>
        <label style="display:block;margin-top:8px">Cargos/roles que podem entrar (separados por vírgula)</label>
        <input type="text" name="maintenance_exception_roles" value="<?php echo htmlspecialchars($maintenance['exception_roles']); ?>" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px">
        <p class="small" style="margin-top:6px">Ex.: Gestor, Suporte ao Cliente</p>
      </div>

      <div class="form-row" style="margin-top:12px">
        <button type="submit" name="save_maintenance" value="1" class="btn">Guardar Configurações de Manutenção</button>
      </div>
    </form>
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
  
  <!-- TAB: FUNÇÕES DA EQUIPA (Permissões Hierárquicas) -->
  <?php if ($activeTab === 'funcoes'): ?>
    <?php 
      $departments = $pdo->query('SELECT * FROM departments ORDER BY name')->fetchAll();
    ?>
    <form method="post">
      <?php echo csrf_input(); ?>
      <h3>Permissões por Departamento</h3>
      <p class="small" style="margin-bottom:16px">Configure as permissões detalhadas para cada departamento.</p>
      
      <?php foreach ($departments as $d): ?>
        <div style="margin-bottom:24px;padding:16px;background:#f9f9f9;border-radius:4px;border-left:4px solid #1976d2">
          <h4 style="margin-top:0"><?php echo htmlspecialchars($d['name']); ?></h4>
          
          <!-- Permissões de Administração -->
          <div style="margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid #ddd">
            <h5>Permissões de Administração</h5>
            <?php 
              $adminPerms = ['admin_settings', 'admin_add_members', 'admin_enable_members', 'admin_delete_members'];
              foreach ($adminPerms as $ap):
                $p = getDeptPermission($pdo, $d['id'], $ap);
                $checked = $p['value'] === 'sim' ? 'checked' : '';
            ?>
              <label style="display:block;margin-bottom:8px"><input type="checkbox" name="perm[<?php echo $d['id']; ?>][<?php echo $ap; ?>]" value="sim" <?php echo $checked; ?>> 
                <?php 
                  $labels = [
                    'admin_settings' => 'Pode gerir todos os tipos de configurações',
                    'admin_add_members' => 'Pode adicionar / convidar novos membros para a equipa',
                    'admin_enable_members' => 'Pode ativar ou desativar membros da equipa',
                    'admin_delete_members' => 'Pode excluir membros da equipa'
                  ];
                  echo htmlspecialchars($labels[$ap] ?? $ap);
                ?>
              </label>
            <?php endforeach; ?>
          </div>
          
          <!-- Definir permissões de membros da equipa -->
          <div style="margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid #ddd">
            <h5>Definir Permissões de Membros da Equipa</h5>
            <?php 
              $perm = getDeptPermission($pdo, $d['id'], 'members_visibility');
              $val = $perm['value'];
            ?>
            <label style="display:block;margin-bottom:6px"><input type="radio" name="perm[<?php echo $d['id']; ?>][members_visibility]" value="hide_list" <?php echo $val==='hide_list'?'checked':''; ?>> Esconder a lista de membros da equipa</label>
            <label style="display:block;margin-bottom:6px"><input type="radio" name="perm[<?php echo $d['id']; ?>][members_visibility]" value="hide_dropdowns" <?php echo $val==='hide_dropdowns'?'checked':''; ?>> Ocultar a lista de membros nos menus suspensos</label>
            <label style="display:block;margin-bottom:6px"><input type="radio" name="perm[<?php echo $d['id']; ?>][members_visibility]" value="" <?php echo empty($val)?'checked':''; ?>> Mostrar tudo</label>
            
            <div style="margin-top:12px;padding-top:12px;border-top:1px solid #eee">
              <?php 
                $perm2 = getDeptPermission($pdo, $d['id'], 'members_contact');
                $val2 = $perm2['value'];
              ?>
              <label style="display:block;margin-bottom:6px"><input type="radio" name="perm[<?php echo $d['id']; ?>][members_contact]" value="no" <?php echo $val2==='no'?'checked':''; ?>> Não pode ver dados de contacto</label>
              <label style="display:block;margin-bottom:6px"><input type="radio" name="perm[<?php echo $d['id']; ?>][members_contact]" value="yes" <?php echo $val2==='yes'?'checked':''; ?>> Pode ver os dados de contacto dos membros</label>
            </div>
            
            <div style="margin-top:12px;padding-top:12px;border-top:1px solid #eee">
              <?php 
                $perm3 = getDeptPermission($pdo, $d['id'], 'members_social');
                $val3 = $perm3['value'];
              ?>
              <label style="display:block;margin-bottom:6px"><input type="radio" name="perm[<?php echo $d['id']; ?>][members_social]" value="no" <?php echo $val3==='no'?'checked':''; ?>> Não pode ver links de redes sociais</label>
              <label style="display:block;margin-bottom:6px"><input type="radio" name="perm[<?php echo $d['id']; ?>][members_social]" value="yes" <?php echo $val3==='yes'?'checked':''; ?>> Pode ver os links de redes sociais</label>
            </div>
            
            <div style="margin-top:12px;padding-top:12px;border-top:1px solid #eee">
              <?php 
                $perm4 = getDeptPermission($pdo, $d['id'], 'members_update');
                $val4 = $perm4['value'];
              ?>
              <label style="display:block;margin-bottom:6px"><input type="radio" name="perm[<?php echo $d['id']; ?>][members_update]" value="no" <?php echo $val4==='no'?'checked':''; ?>> Não</label>
              <label style="display:block;margin-bottom:6px"><input type="radio" name="perm[<?php echo $d['id']; ?>][members_update]" value="all" <?php echo $val4==='all'?'checked':''; ?>> Sim, de todos os membros</label>
              <label style="display:block;margin-bottom:6px"><input type="radio" name="perm[<?php echo $d['id']; ?>][members_update]" value="specific" <?php echo $val4==='specific'?'checked':''; ?>> Sim, de membros ou equipas específicas</label>
            </div>
            
            <div style="margin-top:12px;padding-top:12px;border-top:1px solid #eee">
              <?php 
                $perm5 = getDeptPermission($pdo, $d['id'], 'members_notes');
                $checked5 = $perm5['value'] === 'sim' ? 'checked' : '';
              ?>
              <label><input type="checkbox" name="perm[<?php echo $d['id']; ?>][members_notes]" value="sim" <?php echo $checked5; ?>> Pode gerenciar as notas dos membros da equipa</label>
            </div>
          </div>
          
          <!-- Permissões de Mensagem -->
          <div style="margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid #ddd">
            <h5>Definir Permissões de Mensagem</h5>
            <?php 
              $perm = getDeptPermission($pdo, $d['id'], 'messaging');
              $val = $perm['value'];
            ?>
            <label style="display:block;margin-bottom:6px"><input type="radio" name="perm[<?php echo $d['id']; ?>][messaging]" value="no" <?php echo $val==='no'?'checked':''; ?>> Não é possível enviar qualquer mensagem</label>
            <label style="display:block;margin-bottom:6px"><input type="radio" name="perm[<?php echo $d['id']; ?>][messaging]" value="all" <?php echo $val==='all'?'checked':''; ?>> Pode enviar mensagens para todos</label>
            <label style="display:block;margin-bottom:6px"><input type="radio" name="perm[<?php echo $d['id']; ?>][messaging]" value="specific" <?php echo $val==='specific'?'checked':''; ?>> Pode enviar mensagens para membros ou equipes específicas</label>
          </div>
          
          <!-- Licenças -->
          <div style="margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid #ddd">
            <h5>Pode gerir Licenças de Membros da Equipa</h5>
            <?php 
              $perm = getDeptPermission($pdo, $d['id'], 'licenses');
              $val = $perm['value'];
            ?>
            <label style="display:block;margin-bottom:6px"><input type="radio" name="perm[<?php echo $d['id']; ?>][licenses]" value="no" <?php echo $val==='no'?'checked':''; ?>> Não</label>
            <label style="display:block;margin-bottom:6px"><input type="radio" name="perm[<?php echo $d['id']; ?>][licenses]" value="all" <?php echo $val==='all'?'checked':''; ?>> Sim, de todos os membros</label>
            <label style="display:block;margin-bottom:6px"><input type="radio" name="perm[<?php echo $d['id']; ?>][licenses]" value="specific" <?php echo $val==='specific'?'checked':''; ?>> Sim, de membros ou equipas específicas (menos o seu próprio)</label>
            <div style="margin-top:8px;padding-left:20px">
              <?php 
                $perm2 = getDeptPermission($pdo, $d['id'], 'licenses_delete');
                $checked2 = $perm2['value'] === 'sim' ? 'checked' : '';
              ?>
              <label><input type="checkbox" name="perm[<?php echo $d['id']; ?>][licenses_delete]" value="sim" <?php echo $checked2; ?>> Pode eliminar pedidos de licença</label>
            </div>
          </div>
          
          <!-- Horário de Trabalho -->
          <div style="margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid #ddd">
            <h5>Pode gerir o Painel de Horário de Trabalho da Equipa</h5>
            <?php 
              $perm = getDeptPermission($pdo, $d['id'], 'work_schedule');
              $val = $perm['value'];
            ?>
            <label style="display:block;margin-bottom:6px"><input type="radio" name="perm[<?php echo $d['id']; ?>][work_schedule]" value="no" <?php echo $val==='no'?'checked':''; ?>> Não</label>
            <label style="display:block;margin-bottom:6px"><input type="radio" name="perm[<?php echo $d['id']; ?>][work_schedule]" value="all" <?php echo $val==='all'?'checked':''; ?>> Sim, de todos os membros</label>
            <label style="display:block;margin-bottom:6px"><input type="radio" name="perm[<?php echo $d['id']; ?>][work_schedule]" value="specific" <?php echo $val==='specific'?'checked':''; ?>> Sim, de membros ou equipas específicas</label>
          </div>
          
          <!-- Acesso a Clientes -->
          <div style="margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid #ddd">
            <h5>Pode Aceder às Informações dos Clientes</h5>
            <?php 
              $perm = getDeptPermission($pdo, $d['id'], 'customers');
              $val = $perm['value'];
            ?>
            <label style="display:block;margin-bottom:6px"><input type="radio" name="perm[<?php echo $d['id']; ?>][customers]" value="no" <?php echo $val==='no'?'checked':''; ?>> Não</label>
            <label style="display:block;margin-bottom:6px"><input type="radio" name="perm[<?php echo $d['id']; ?>][customers]" value="all" <?php echo $val==='all'?'checked':''; ?>> Sim, todos os clientes</label>
            <label style="display:block;margin-bottom:6px"><input type="radio" name="perm[<?php echo $d['id']; ?>][customers]" value="own" <?php echo $val==='own'?'checked':''; ?>> Sim, apenas clientes próprios</label>
            <label style="display:block;margin-bottom:6px"><input type="radio" name="perm[<?php echo $d['id']; ?>][customers]" value="readonly" <?php echo $val==='readonly'?'checked':''; ?>> Somente leitura</label>
            <label style="display:block;margin-bottom:6px"><input type="radio" name="perm[<?php echo $d['id']; ?>][customers]" value="specific" <?php echo $val==='specific'?'checked':''; ?>> Sim, grupos de clientes específicos</label>
          </div>
          
          <!-- Acesso a Tickets -->
          <div style="margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid #ddd">
            <h5>Pode Aceder a Tickets</h5>
            <?php 
              $perm = getDeptPermission($pdo, $d['id'], 'tickets');
              $val = $perm['value'];
            ?>
            <label style="display:block;margin-bottom:6px"><input type="radio" name="perm[<?php echo $d['id']; ?>][tickets]" value="no" <?php echo $val==='no'?'checked':''; ?>> Não</label>
            <label style="display:block;margin-bottom:6px"><input type="radio" name="perm[<?php echo $d['id']; ?>][tickets]" value="all" <?php echo $val==='all'?'checked':''; ?>> Sim, todos os chamados</label>
            <label style="display:block;margin-bottom:6px"><input type="radio" name="perm[<?php echo $d['id']; ?>][tickets]" value="assigned" <?php echo $val==='assigned'?'checked':''; ?>> Sim, apenas tíquetes atribuídos</label>
            <label style="display:block;margin-bottom:6px"><input type="radio" name="perm[<?php echo $d['id']; ?>][tickets]" value="categories" <?php echo $val==='categories'?'checked':''; ?>> Sim, categorias específicas</label>
          </div>
          
          <!-- Ajuda e Banco de Conhecimentos -->
          <div style="margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid #ddd">
            <h5>Pode gerir as Secções Ajuda e Banco de Conhecimentos</h5>
            <?php 
              $perm = getDeptPermission($pdo, $d['id'], 'knowledge_base');
              $checked = $perm['value'] === 'sim' ? 'checked' : '';
            ?>
            <label><input type="checkbox" name="perm[<?php echo $d['id']; ?>][knowledge_base]" value="sim" <?php echo $checked; ?>> Sim</label>
          </div>
          
          <!-- Avisos de Pagamento -->
          <div style="margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid #ddd">
            <h5>Definir Permissões do Aviso de Pagamento</h5>
            <?php 
              $perm = getDeptPermission($pdo, $d['id'], 'payment_warnings');
              $val = $perm['value'];
            ?>
            <label style="display:block;margin-bottom:6px"><input type="radio" name="perm[<?php echo $d['id']; ?>][payment_warnings]" value="no" <?php echo $val==='no'?'checked':''; ?>> Não consigo acessar aos Avisos de Pagamento</label>
            <label style="display:block;margin-bottom:6px"><input type="radio" name="perm[<?php echo $d['id']; ?>][payment_warnings]" value="manage_all" <?php echo $val==='manage_all'?'checked':''; ?>> Pode gerenciar todos os Avisos de Pagamento</label>
            <label style="display:block;margin-bottom:6px"><input type="radio" name="perm[<?php echo $d['id']; ?>][payment_warnings]" value="view_all" <?php echo $val==='view_all'?'checked':''; ?>> Pode visualizar todos os Avisos de Pagamento</label>
            <label style="display:block;margin-bottom:6px"><input type="radio" name="perm[<?php echo $d['id']; ?>][payment_warnings]" value="manage_own_clients" <?php echo $val==='manage_own_clients'?'checked':''; ?>> Pode gerenciar dos próprios clientes</label>
            <label style="display:block;margin-bottom:6px"><input type="radio" name="perm[<?php echo $d['id']; ?>][payment_warnings]" value="manage_own_clients_no_delete" <?php echo $val==='manage_own_clients_no_delete'?'checked':''; ?>> Pode gerenciar dos próprios clientes (exceto excluir)</label>
            <label style="display:block;margin-bottom:6px"><input type="radio" name="perm[<?php echo $d['id']; ?>][payment_warnings]" value="view_own_clients" <?php echo $val==='view_own_clients'?'checked':''; ?>> Pode visualizar dos próprios clientes</label>
            <label style="display:block;margin-bottom:6px"><input type="radio" name="perm[<?php echo $d['id']; ?>][payment_warnings]" value="manage_created" <?php echo $val==='manage_created'?'checked':''; ?>> Pode gerenciar apenas criados por você</label>
            <label style="display:block;margin-bottom:6px"><input type="radio" name="perm[<?php echo $d['id']; ?>][payment_warnings]" value="manage_created_no_delete" <?php echo $val==='manage_created_no_delete'?'checked':''; ?>> Pode gerenciar apenas criados por você (exceto excluir)</label>
          </div>
          
          <!-- Orçamentos -->
          <div style="margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid #ddd">
            <h5>Pode Aceder aos Orçamentos</h5>
            <?php 
              $perm = getDeptPermission($pdo, $d['id'], 'quotes');
              $val = $perm['value'];
            ?>
            <label style="display:block;margin-bottom:6px"><input type="radio" name="perm[<?php echo $d['id']; ?>][quotes]" value="no" <?php echo $val==='no'?'checked':''; ?>> Não consigo acessar os orçamentos</label>
            <label style="display:block;margin-bottom:6px"><input type="radio" name="perm[<?php echo $d['id']; ?>][quotes]" value="manage_all" <?php echo $val==='manage_all'?'checked':''; ?>> Capaz de gerenciar todos os orçamentos</label>
            <label style="display:block;margin-bottom:6px"><input type="radio" name="perm[<?php echo $d['id']; ?>][quotes]" value="view_all" <?php echo $val==='view_all'?'checked':''; ?>> É possível visualizar todos os orçamentos</label>
            <label style="display:block;margin-bottom:6px"><input type="radio" name="perm[<?php echo $d['id']; ?>][quotes]" value="manage_created" <?php echo $val==='manage_created'?'checked':''; ?>> Só é possível gerenciar orçamentos criados por mim</label>
            <label style="display:block;margin-bottom:6px"><input type="radio" name="perm[<?php echo $d['id']; ?>][quotes]" value="manage_own_clients" <?php echo $val==='manage_own_clients'?'checked':''; ?>> Capaz de gerenciar orçamentos de seus próprios clientes</label>
          </div>
          
          <!-- Contratos -->
          <div style="margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid #ddd">
            <h5>Pode Acessar os Contratos</h5>
            <?php 
              $perm = getDeptPermission($pdo, $d['id'], 'contracts');
              $val = $perm['value'];
            ?>
            <label style="display:block;margin-bottom:6px"><input type="radio" name="perm[<?php echo $d['id']; ?>][contracts]" value="no" <?php echo $val==='no'?'checked':''; ?>> Não consigo acessar os contratos</label>
            <label style="display:block;margin-bottom:6px"><input type="radio" name="perm[<?php echo $d['id']; ?>][contracts]" value="manage_all" <?php echo $val==='manage_all'?'checked':''; ?>> Pode gerenciar todos os contratos</label>
            <label style="display:block;margin-bottom:6px"><input type="radio" name="perm[<?php echo $d['id']; ?>][contracts]" value="manage_own_clients" <?php echo $val==='manage_own_clients'?'checked':''; ?>> Pode gerenciar apenas contratos de clientes próprios</label>
            <label style="display:block;margin-bottom:6px"><input type="radio" name="perm[<?php echo $d['id']; ?>][contracts]" value="view_own_clients" <?php echo $val==='view_own_clients'?'checked':''; ?>> Só pode ver os contratos do próprio cliente</label>
          </div>
          
          <!-- Propostas -->
          <div style="margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid #ddd">
            <h5>Pode Acessar Propostas</h5>
            <?php 
              $perm = getDeptPermission($pdo, $d['id'], 'proposals');
              $val = $perm['value'];
            ?>
            <label style="display:block;margin-bottom:6px"><input type="radio" name="perm[<?php echo $d['id']; ?>][proposals]" value="no" <?php echo $val==='no'?'checked':''; ?>> Não consigo acessar as propostas</label>
            <label style="display:block;margin-bottom:6px"><input type="radio" name="perm[<?php echo $d['id']; ?>][proposals]" value="manage_all" <?php echo $val==='manage_all'?'checked':''; ?>> Capaz de gerenciar todas as propostas</label>
            <label style="display:block;margin-bottom:6px"><input type="radio" name="perm[<?php echo $d['id']; ?>][proposals]" value="view_all" <?php echo $val==='view_all'?'checked':''; ?>> É possível visualizar todas as propostas</label>
            <label style="display:block;margin-bottom:6px"><input type="radio" name="perm[<?php echo $d['id']; ?>][proposals]" value="manage_created" <?php echo $val==='manage_created'?'checked':''; ?>> Só pode gerir propostas criadas por si</label>
            <label style="display:block;margin-bottom:6px"><input type="radio" name="perm[<?php echo $d['id']; ?>][proposals]" value="manage_own_clients" <?php echo $val==='manage_own_clients'?'checked':''; ?>> Capaz de gerenciar propostas de clientes próprios</label>
            <label style="display:block;margin-bottom:6px"><input type="radio" name="perm[<?php echo $d['id']; ?>][proposals]" value="view_own_clients" <?php echo $val==='view_own_clients'?'checked':''; ?>> É possível visualizar as propostas dos próprios clientes</label>
            <label style="display:block;margin-bottom:6px"><input type="radio" name="perm[<?php echo $d['id']; ?>][proposals]" value="manage_prospects" <?php echo $val==='manage_prospects'?'checked':''; ?>> Capaz de gerenciar propostas de clientes em potencial</label>
          </div>
          
          <!-- Despesas -->
          <div style="margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid #ddd">
            <h5>Pode Aceder a Despesas</h5>
            <?php 
              $perm = getDeptPermission($pdo, $d['id'], 'expenses');
              $val = $perm['value'];
            ?>
            <label style="display:block;margin-bottom:6px"><input type="radio" name="perm[<?php echo $d['id']; ?>][expenses]" value="no" <?php echo $val==='no'?'checked':''; ?>> Não</label>
            <label style="display:block;margin-bottom:6px"><input type="radio" name="perm[<?php echo $d['id']; ?>][expenses]" value="all" <?php echo $val==='all'?'checked':''; ?>> Sim, todas as despesas</label>
            <label style="display:block;margin-bottom:6px"><input type="radio" name="perm[<?php echo $d['id']; ?>][expenses]" value="own" <?php echo $val==='own'?'checked':''; ?>> Pode gerenciar apenas despesas criadas por ele mesmo</label>
          </div>
          
          <!-- Avisos -->
          <div style="margin-bottom:20px">
            <h5>Pode Gerir Avisos</h5>
            <?php 
              $perm = getDeptPermission($pdo, $d['id'], 'alerts');
              $val = $perm['value'];
            ?>
            <label style="display:block;margin-bottom:6px"><input type="radio" name="perm[<?php echo $d['id']; ?>][alerts]" value="no" <?php echo $val==='no'?'checked':''; ?>> Não</label>
            <label style="display:block;margin-bottom:6px"><input type="radio" name="perm[<?php echo $d['id']; ?>][alerts]" value="yes" <?php echo $val==='yes'?'checked':''; ?>> Sim</label>
          </div>
        </div>
      <?php endforeach; ?>
      
      <div class="form-row" style="margin-top:20px">
        <button type="submit" name="save_hierarchical_permissions" value="1" class="btn">Guardar Permissões Hierárquicas</button>
      </div>
    </form>
  <?php endif; ?>
  
  <!-- TAB: PERMISSÕES DO CLIENTE -->
  <?php if ($activeTab === 'cliente'): ?>
    <?php 
      $clientPermsKeys = [
        'view_invoices','pay_invoices','open_tickets','manage_domains','view_services','edit_profile',
        'client_view_documents','client_add_documents'
      ];
      $rows = $pdo->query('SELECT * FROM client_permissions')->fetchAll();
      $permMap = [];
      foreach ($rows as $r) $permMap[$r['permission_key']] = (int)$r['allowed'];
    ?>
    <form method="post">
      <?php echo csrf_input(); ?>
      <h3>Permissões dos Clientes</h3>
      <p class="small" style="margin-bottom:12px">Defina o que os clientes podem fazer na área de cliente.</p>
      <p class="small" style="margin-bottom:12px;padding:8px;background:#e8f5e9;border-left:3px solid #4caf50"><strong>Nota:</strong> A verificação de email é obrigatória para todos os novos registos. O registo é desativado automaticamente quando o Modo de Manutenção está ativo.</p>

      <div style="margin-bottom:16px;padding:12px;border:1px solid #ddd;border-radius:4px">
        <h4 style="margin-top:0">Documentos</h4>
        <label style="display:block;margin-bottom:8px"><input type="checkbox" name="client_perm[client_view_documents]" <?php echo !empty($permMap['client_view_documents'])?'checked':''; ?>> O cliente pode ver documentos</label>
        <label style="display:block;margin-bottom:8px"><input type="checkbox" name="client_perm[client_add_documents]" <?php echo !empty($permMap['client_add_documents'])?'checked':''; ?>> O cliente pode adicionar documentos</label>
      </div>

      <div style="margin-bottom:16px;padding:12px;border:1px solid #ddd;border-radius:4px">
        <h4 style="margin-top:0">Acessos Gerais</h4>
        <label style="display:block;margin-bottom:8px"><input type="checkbox" name="client_perm[view_invoices]" <?php echo !empty($permMap['view_invoices'])?'checked':''; ?>> Ver faturas</label>
        <label style="display:block;margin-bottom:8px"><input type="checkbox" name="client_perm[pay_invoices]" <?php echo !empty($permMap['pay_invoices'])?'checked':''; ?>> Pagar faturas</label>
        <label style="display:block;margin-bottom:8px"><input type="checkbox" name="client_perm[open_tickets]" <?php echo !empty($permMap['open_tickets'])?'checked':''; ?>> Abrir tickets</label>
        <label style="display:block;margin-bottom:8px"><input type="checkbox" name="client_perm[manage_domains]" <?php echo !empty($permMap['manage_domains'])?'checked':''; ?>> Gerir domínios</label>
        <label style="display:block;margin-bottom:8px"><input type="checkbox" name="client_perm[view_services]" <?php echo !empty($permMap['view_services'])?'checked':''; ?>> Ver serviços</label>
        <label style="display:block;margin-bottom:8px"><input type="checkbox" name="client_perm[edit_profile]" <?php echo !empty($permMap['edit_profile'])?'checked':''; ?>> Editar perfil</label>
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
    <?php
      // Carregar todos os templates
      $templates = listEmailTemplates($pdo, true);
      $editingId = isset($_GET['edit']) ? (int)$_GET['edit'] : null;
      $editingTemplate = null;
      if ($editingId) {
        foreach ($templates as $t) {
          if ($t['id'] === $editingId) {
            $editingTemplate = $t;
            break;
          }
        }
      }
    ?>
    
    <!-- Mensagens de feedback -->
    <?php if (isset($_GET['test'])): ?>
      <?php if ($_GET['test'] === '1'): ?>
        <div style="background:#e8f5e9;color:#2e7d32;padding:12px;border-radius:4px;margin-bottom:16px">
          ✓ Email de teste enviado com sucesso!
        </div>
      <?php else: ?>
        <div style="background:#ffebee;color:#c62828;padding:12px;border-radius:4px;margin-bottom:16px">
          ✗ Erro ao enviar email de teste. Verifique as configurações SMTP.
        </div>
      <?php endif; ?>
    <?php endif; ?>
    
    <!-- Edição de Template -->
    <?php if ($editingTemplate): ?>
      <div style="background:#fff3cd;border:1px solid #ffc107;padding:16px;border-radius:4px;margin-bottom:20px">
        <h3 style="margin-top:0;color:#856404">
          ✏️ Editando: <?php echo htmlspecialchars($editingTemplate['template_name']); ?>
          <?php if ($editingTemplate['is_system']): ?>
            <span style="background:#17a2b8;color:#fff;padding:4px 8px;border-radius:3px;font-size:12px;margin-left:8px">SISTEMA</span>
          <?php endif; ?>
        </h3>
        
        <form method="post">
          <?php echo csrf_input(); ?>
          <input type="hidden" name="template_id" value="<?php echo $editingTemplate['id']; ?>">
          
          <div class="form-row">
            <label><strong>Assunto do Email</strong> <span style="color:red">*</span></label>
            <input type="text" name="subject" 
                   value="<?php echo htmlspecialchars($editingTemplate['subject']); ?>" 
                   required
                   placeholder="Ex: Bem-vindo ao {{site_name}}!">
            <small>Pode usar variáveis: {{site_name}}, {{user_name}}, {{current_year}}, etc.</small>
          </div>
          
          <div class="form-row">
            <label><strong>Corpo do Email (HTML)</strong> <span style="color:red">*</span></label>
            <textarea name="body_html" rows="20" required style="font-family:monospace;font-size:13px;line-height:1.6"><?php echo htmlspecialchars($editingTemplate['body_html']); ?></textarea>
            <small>
              <strong>Variáveis disponíveis:</strong> 
              <?php 
                $vars = json_decode($editingTemplate['variables'] ?? '[]', true);
                if (!empty($vars)) {
                  foreach ($vars as $v) {
                    echo '<code>{{' . htmlspecialchars($v) . '}}</code> ';
                  }
                }
              ?>
              + variáveis globais ({{site_name}}, {{current_year}}, {{site_url}})
            </small>
          </div>
          
          <div class="form-row">
            <label>
              <input type="checkbox" name="is_active" <?php echo $editingTemplate['is_active'] ? 'checked' : ''; ?>>
              <strong>Template ativo</strong> (apenas templates ativos são usados no envio automático)
            </label>
          </div>
          
          <div class="form-row" style="margin-top:20px">
            <button type="submit" name="update_email_template" value="1" class="btn">💾 Guardar Alterações</button>
            <a href="settings.php?tab=modelos" class="btn" style="background:#6c757d;margin-left:10px">✕ Cancelar</a>
            <button type="button" onclick="testEmailModal('<?php echo htmlspecialchars($editingTemplate['template_key']); ?>')" 
                    class="btn" style="background:#17a2b8;margin-left:10px">📧 Enviar Teste</button>
          </div>
        </form>
      </div>
    <?php endif; ?>
    
    <!-- Lista de Templates -->
    <h3>📧 Modelos de Email Disponíveis</h3>
    <p class="small" style="margin-bottom:20px">
      Personalize os emails automáticos enviados pelo sistema. Clique em "Editar" para alterar o assunto e o conteúdo HTML.
    </p>
    
    <?php if (empty($templates)): ?>
      <div style="padding:40px;background:#f5f5f5;border-radius:4px;text-align:center">
        <p style="color:#999">Nenhum modelo de email encontrado. Execute o schema.sql para criar os templates iniciais.</p>
      </div>
    <?php else: ?>
      <table style="width:100%;border-collapse:collapse;background:#fff">
        <thead>
          <tr style="background:#f8f9fa;border-bottom:2px solid #dee2e6">
            <th style="padding:12px;text-align:left;font-weight:600">Template</th>
            <th style="padding:12px;text-align:left;font-weight:600">Assunto</th>
            <th style="padding:12px;text-align:center;font-weight:600">Tipo</th>
            <th style="padding:12px;text-align:center;font-weight:600">Estado</th>
            <th style="padding:12px;text-align:center;font-weight:600">Atualizado</th>
            <th style="padding:12px;text-align:center;font-weight:600">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($templates as $t): ?>
            <tr style="border-bottom:1px solid #dee2e6;<?php echo $editingId === $t['id'] ? 'background:#fff9e6' : ''; ?>">
              <td style="padding:12px">
                <div style="font-weight:600;margin-bottom:4px"><?php echo htmlspecialchars($t['template_name']); ?></div>
                <code style="font-size:11px;background:#f4f4f4;padding:2px 6px;border-radius:3px"><?php echo htmlspecialchars($t['template_key']); ?></code>
              </td>
              <td style="padding:12px">
                <span style="color:#666;font-size:14px"><?php echo htmlspecialchars($t['subject']); ?></span>
              </td>
              <td style="padding:12px;text-align:center">
                <?php if ($t['is_system']): ?>
                  <span style="background:#17a2b8;color:#fff;padding:4px 10px;border-radius:12px;font-size:11px;font-weight:600">SISTEMA</span>
                <?php else: ?>
                  <span style="background:#28a745;color:#fff;padding:4px 10px;border-radius:12px;font-size:11px;font-weight:600">CUSTOM</span>
                <?php endif; ?>
              </td>
              <td style="padding:12px;text-align:center">
                <?php if ($t['is_active']): ?>
                  <span style="color:#28a745;font-weight:600">✓ Ativo</span>
                <?php else: ?>
                  <span style="color:#dc3545;font-weight:600">✗ Inativo</span>
                <?php endif; ?>
              </td>
              <td style="padding:12px;text-align:center;color:#666;font-size:13px">
                <?php echo date('d/m/Y', strtotime($t['updated_at'])); ?><br>
                <small><?php echo date('H:i', strtotime($t['updated_at'])); ?></small>
              </td>
              <td style="padding:12px;text-align:center">
                <a href="settings.php?tab=modelos&edit=<?php echo $t['id']; ?>" 
                   class="btn btn-sm" 
                   style="background:#007bff;padding:6px 12px;font-size:13px">✏️ Editar</a>
                <button onclick="testEmailModal('<?php echo htmlspecialchars($t['template_key']); ?>')" 
                        class="btn btn-sm" 
                        style="background:#17a2b8;padding:6px 12px;font-size:13px;margin-left:4px">📧 Teste</button>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
    
    <!-- Informação sobre Variáveis -->
    <div style="margin-top:30px;padding:20px;background:#f8f9fa;border-left:4px solid #007bff;border-radius:4px">
      <h4 style="margin-top:0;color:#007bff">📋 Guia de Variáveis</h4>
      
      <div style="margin-bottom:16px">
        <strong>Variáveis Globais (disponíveis em todos os templates):</strong>
        <ul style="margin:8px 0;padding-left:20px;line-height:1.8">
          <li><code>{{site_name}}</code> - Nome da empresa/site</li>
          <li><code>{{current_year}}</code> - Ano atual (ex: 2025)</li>
          <li><code>{{site_url}}</code> - URL base do site</li>
        </ul>
      </div>
      
      <div>
        <strong>Variáveis Específicas por Template:</strong>
        <ul style="margin:8px 0;padding-left:20px;line-height:1.8">
          <li><strong>email_verification:</strong> <code>{{user_name}}</code>, <code>{{verification_link}}</code></li>
          <li><strong>password_reset:</strong> <code>{{user_name}}</code>, <code>{{reset_link}}</code></li>
          <li><strong>welcome_email:</strong> <code>{{user_name}}</code>, <code>{{dashboard_link}}</code></li>
        </ul>
      </div>
      
      <p style="margin:16px 0 0 0;font-size:13px;color:#666">
        <strong>💡 Dica:</strong> Use um design responsivo e compatível com todos os clientes de email. 
        Os templates atuais seguem o estilo clean e profissional inspirado em grandes provedores.
      </p>
    </div>
    
    <!-- Modal de Teste de Email -->
    <div id="testEmailModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.6);z-index:9999;align-items:center;justify-content:center">
      <div style="background:#fff;padding:30px;border-radius:8px;max-width:500px;width:90%;box-shadow:0 4px 20px rgba(0,0,0,0.3)">
        <h3 style="margin-top:0">📧 Enviar Email de Teste</h3>
        <p style="color:#666;margin-bottom:20px">O email será enviado com valores de exemplo para as variáveis.</p>
        <form method="post">
          <?php echo csrf_input(); ?>
          <input type="hidden" name="template_key" id="test_template_key">
          <div class="form-row">
            <label><strong>Email de Destino</strong></label>
            <input type="email" name="test_email_address" required placeholder="seu-email@exemplo.com" style="width:100%">
          </div>
          <div class="form-row" style="margin-top:20px">
            <button type="submit" name="test_email_template" value="1" class="btn" style="background:#28a745">✓ Enviar Teste</button>
            <button type="button" onclick="closeTestModal()" class="btn" style="background:#6c757d;margin-left:10px">✕ Cancelar</button>
          </div>
        </form>
      </div>
    </div>
    
    <script>
    function testEmailModal(templateKey) {
      document.getElementById('test_template_key').value = templateKey;
      document.getElementById('testEmailModal').style.display = 'flex';
    }
    
    function closeTestModal() {
      document.getElementById('testEmailModal').style.display = 'none';
    }
    
    // Fechar modal ao clicar fora
    document.getElementById('testEmailModal').addEventListener('click', function(e) {
      if (e.target === this) {
        closeTestModal();
      }
    });
    
    // Atalho ESC para fechar
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        closeTestModal();
      }
    });
    </script>
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
