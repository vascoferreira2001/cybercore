<?php
define('DASHBOARD_LAYOUT', true);
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/dashboard_helper.php';

checkRole(['Gestor']);
$user = currentUser();
$GLOBALS['currentUser'] = $user;

$manifestPath = __DIR__ . '/../deploy/ftp-manifest.txt';
$results = [];

if (file_exists($manifestPath)) {
  $lines = file($manifestPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  foreach ($lines as $line) {
    [$rel, $hash] = array_pad(preg_split('/\s+/', trim($line), 2), 2, '');
    $abs = realpath(__DIR__ . '/../' . $rel);
    $exists = $abs && file_exists($abs);
    $serverHash = $exists ? md5_file($abs) : '';
    $match = $exists && ($serverHash === $hash);
    $results[] = [
      'path' => $rel,
      'expected' => $hash,
      'exists' => $exists,
      'serverHash' => $serverHash,
      'match' => $match
    ];
  }
} else {
  $results = null;
}

ob_start();
?>
<div class="card">
  <h2>Verificação de Deploy (FTP)</h2>
  <p style="color:#999">Compara os ficheiros no servidor com o manifesto local.</p>
  <?php if ($results === null): ?>
    <div style="padding:12px;background:#fff3cd;border:1px solid #ffeeba;border-radius:8px;color:#856404">
      Manifesto não encontrado em <code>/deploy/ftp-manifest.txt</code>.
      Faça upload do ficheiro de manifesto junto com os ficheiros alterados.
    </div>
  <?php else: ?>
    <div style="overflow-x:auto">
      <table class="table" style="width:100%;border-collapse:collapse">
        <thead>
          <tr style="background:#0b1222;border-bottom:1px solid #334155;color:#93c5fd">
            <th style="padding:10px;text-align:left">Ficheiro</th>
            <th style="padding:10px;text-align:left">Esperado (MD5)</th>
            <th style="padding:10px;text-align:left">Servidor (MD5)</th>
            <th style="padding:10px;text-align:center">Estado</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($results as $r): ?>
            <tr>
              <td style="padding:10px"><?php echo htmlspecialchars($r['path']); ?></td>
              <td style="padding:10px;font-family:monospace"><?php echo htmlspecialchars($r['expected']); ?></td>
              <td style="padding:10px;font-family:monospace"><?php echo htmlspecialchars($r['serverHash'] ?: '—'); ?></td>
              <td style="padding:10px;text-align:center">
                <?php if (!$r['exists']): ?>
                  <span class="badge badge-danger">Em falta</span>
                <?php elseif ($r['match']): ?>
                  <span class="badge badge-success">OK</span>
                <?php else: ?>
                  <span class="badge badge-warning">Difere</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
echo renderDashboardLayout('Verificação de Deploy', 'Comparação de hashes de ficheiros', $content, 'updates');
?>