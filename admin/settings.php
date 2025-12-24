<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/csrf.php';
require_once __DIR__ . '/../inc/settings.php';

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

// Processar upload de imagens
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();
    
    // Logo upload
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
    
    // Favicon upload
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
    
    // Background upload
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
    
    if ($message && empty($errors)) {
        $pdo->prepare('INSERT INTO logs (user_id,type,message) VALUES (?,?,?)')->execute([$user['id'],'settings_update','Settings updated']);
        // Recarregar página para aplicar novas configurações
        header('Location: settings.php?success=1');
        exit;
    }
}

// Carregar configurações atuais
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
      ✓ <?php echo htmlspecialchars($message); ?>Recarregando...
    </div>
    <script>setTimeout(() => location.reload(), 1500);</script>
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
