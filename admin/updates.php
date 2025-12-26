<?php
define('DASHBOARD_LAYOUT', true);
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/csrf.php';
require_once __DIR__ . '/../inc/dashboard_helper.php';
require_once __DIR__ . '/../inc/settings.php';

checkRole(['Gestor','Suporte ao Cliente','Suporte Técnica','Suporte Financeira']);
$user = currentUser();
$GLOBALS['currentUser'] = $user;

$pdo = getDB();
$message = '';
$errors = [];

// Processar nova atualização (apenas Gestor)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user['role'] === 'Gestor') {
    csrf_validate();
    
    $version = $_POST['version'] ?? '';
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    
    if (empty($version)) {
        $errors[] = 'Versão é obrigatória.';
    } elseif (empty($title)) {
        $errors[] = 'Título é obrigatório.';
    } else {
        try {
            $stmt = $pdo->prepare('INSERT INTO changelog (version, title, description, release_date) VALUES (?, ?, ?, NOW())');
            $stmt->execute([$version, $title, $description]);
            
            // Atualizar versão atual
            setSetting($pdo, 'app_version', $version);
            
            $pdo->prepare('INSERT INTO logs (user_id,type,message) VALUES (?,?,?)')->execute([$user['id'],'update_release','Released version ' . $version]);
            
            $message = 'Versão ' . htmlspecialchars($version) . ' lançada com sucesso!';
        } catch (PDOException $e) {
            $errors[] = 'Erro ao registar atualização: ' . $e->getMessage();
        }
    }
}

// Construir conteúdo
ob_start();
?>
<div class="card">
  <h2>⬆️ Gerenciamento de Atualizações</h2>
  
  <?php if (!empty($message)): ?>
    <div style="background:#e8f5e9;color:#2e7d32;padding:12px;border-radius:4px;margin-bottom:16px">
      ✓ <?php echo htmlspecialchars($message); ?>
    </div>
  <?php endif; ?>
  
  <?php if (!empty($errors)): ?>
    <div style="background:#ffebee;color:#c62828;padding:12px;border-radius:4px;margin-bottom:16px">
      <?php foreach ($errors as $err): ?>
        ✗ <?php echo htmlspecialchars($err); ?><br>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  
  <?php if ($user['role'] === 'Gestor'): ?>
    <div style="background:#f5f5f5;padding:16px;border-radius:4px;margin-bottom:24px">
      <h3>Registar Nova Atualização</h3>
      <form method="post">
        <?php echo csrf_input(); ?>
        
        <div class="form-row">
          <label>Versão (ex: 1.0.1)</label>
          <input type="text" name="version" placeholder="1.0.1" required pattern="^\d+\.\d+\.\d+$">
        </div>
        
        <div class="form-row">
          <label>Título da Atualização</label>
          <input type="text" name="title" placeholder="Correções de segurança" required>
        </div>
        
        <div class="form-row">
          <label>Descrição (detalhes das mudanças)</label>
          <textarea name="description" rows="5" placeholder="- Corrigido bug na autenticação&#10;- Melhorado desempenho do dashboard&#10;- Nova funcionalidade de reportes"></textarea>
        </div>
        
        <div class="form-row">
          <button type="submit" class="btn">Registar Atualização</button>
        </div>
      </form>
    </div>
  <?php endif; ?>
  
  <h3>Histórico de Atualizações</h3>
  
  <?php
  try {
    $stmt = $pdo->query('SELECT version, title, description, release_date FROM changelog ORDER BY release_date DESC LIMIT 50');
    $updates = $stmt->fetchAll();
    
    if (!empty($updates)):
      foreach ($updates as $update):
  ?>
        <div style="border:1px solid #e0e0e0;border-radius:4px;padding:16px;margin-bottom:12px;background:#f9f9f9">
          <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:8px">
            <div>
              <h4 style="margin:0 0 4px 0">v<?php echo htmlspecialchars($update['version']); ?> - <?php echo htmlspecialchars($update['title']); ?></h4>
              <small style="color:#999">Lançado em <?php echo date('d/m/Y \à\s H:i', strtotime($update['release_date'])); ?></small>
            </div>
          </div>
          <?php if ($update['description']): ?>
            <p style="margin:12px 0 0 0;color:#555;line-height:1.6;white-space:pre-wrap"><?php echo htmlspecialchars($update['description']); ?></p>
          <?php endif; ?>
        </div>
  <?php
      endforeach;
    else:
  ?>
      <div style="background:#ffffcc;color:#666;padding:12px;border-radius:4px">
        ℹ️ Nenhuma atualização registada ainda.
      </div>
  <?php
    endif;
  } catch (PDOException $e) {
    error_log('Changelog error: ' . $e->getMessage());
  ?>
    <div style="background:#ffebee;color:#c62828;padding:12px;border-radius:4px">
      ⚠️ Erro ao carregar histórico de atualizações.
    </div>
  <?php
  }
  ?>
</div>
<?php
$content = ob_get_clean();
echo renderDashboardLayout('Atualizações', 'Gerenciamento de versões e changelog', $content, 'updates');
?>
