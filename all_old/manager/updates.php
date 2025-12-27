<?php
define('DASHBOARD_LAYOUT', true);
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/dashboard_helper.php';

checkRole(['Cliente','Suporte ao Cliente','Suporte Financeiro','Suporte Técnico','Gestor']);
$user = currentUser();
$GLOBALS['currentUser'] = $user;
$pdo = getDB();

$content = '<div class="card">
  <h2>Histórico de Atualizações do Sistema</h2>
  <p style="color:#666;margin:16px 0">Aqui encontra um registo completo de todas as atualizações realizadas no CyberCore.</p>';
  
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
              <h3 style="margin:0 0 4px 0">v<?php echo htmlspecialchars($update['version']); ?> - <?php echo htmlspecialchars($update['title']); ?></h3>
              <small style="color:#999">Lançado em <?php echo date('d/m/Y \à\s H:i', strtotime($update['release_date'])); ?></small>
            </div>
          </div>
          <?php if ($update['description']): ?>
            <p style="margin:12px 0 0 0;color:#555;line-height:1.6"><?php echo nl2br(htmlspecialchars($update['description'])); ?></p>
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
