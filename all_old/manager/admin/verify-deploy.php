<?php
require_once __DIR__ . '/../../inc/auth.php';
require_once __DIR__ . '/../../inc/permissions.php';
require_once __DIR__ . '/../../inc/dashboard_helper.php';

checkRole(['Gestor']);
$user = currentUser();
$GLOBALS['currentUser'] = $user;
requirePermission('is_super_admin', $user);

$manifestPath = __DIR__ . '/../../deploy/ftp-manifest.txt';
$root = realpath(__DIR__ . '/../../');
$results = [ 'ok' => [], 'missing' => [], 'mismatch' => [] ];
$manifestCount = 0;
$error = null;

if (!file_exists($manifestPath)) {
  $error = 'Manifesto não encontrado em deploy/ftp-manifest.txt';
} else {
  $lines = file($manifestPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  $manifestCount = count($lines);
  foreach ($lines as $line) {
    // Format: "<md5> <path>"
    $parts = preg_split('/\s+/', $line, 2);
    if (count($parts) !== 2) continue;
    [$expected, $relPath] = $parts;
    $absPath = $root . '/' . $relPath;
    if (!file_exists($absPath)) {
      $results['missing'][] = $relPath;
      continue;
    }
    $actual = md5_file($absPath);
    if (strtolower($actual) !== strtolower($expected)) {
      $results['mismatch'][] = [ 'path' => $relPath, 'expected' => $expected, 'actual' => $actual ];
    } else {
      $results['ok'][] = $relPath;
    }
  }
}

$summary = '<div class="card">'
  . '<h2>Verificação de Deploy</h2>'
  . '<p>Manifesto: <strong>' . htmlspecialchars(basename($manifestPath)) . '</strong> (' . $manifestCount . ' ficheiros)</p>';
if ($error) {
  $summary .= '<p style="color:#f87171">Erro: ' . htmlspecialchars($error) . '</p>';
} else {
  $summary .= '<ul>'
    . '<li>OK: ' . count($results['ok']) . '</li>'
    . '<li>Em falta: ' . count($results['missing']) . '</li>'
    . '<li>Hash diferente: ' . count($results['mismatch']) . '</li>'
    . '</ul>';
}
$summary .= '</div>';

$detail = '<div class="card">';
if (!$error) {
  if (!empty($results['missing'])) {
    $detail .= '<h3>Ficheiros em falta</h3><ul>';
    foreach ($results['missing'] as $p) {
      $detail .= '<li>' . htmlspecialchars($p) . '</li>';
    }
    $detail .= '</ul>';
  }
  if (!empty($results['mismatch'])) {
    $detail .= '<h3>Ficheiros com hash diferente</h3><table style="width:100%"><thead><tr><th>Ficheiro</th><th>Esperado</th><th>Atual</th></tr></thead><tbody>';
    foreach ($results['mismatch'] as $m) {
      $detail .= '<tr><td>' . htmlspecialchars($m['path']) . '</td><td>' . htmlspecialchars($m['expected']) . '</td><td>' . htmlspecialchars($m['actual']) . '</td></tr>';
    }
    $detail .= '</tbody></table>';
  }
  if (empty($results['missing']) && empty($results['mismatch'])) {
    $detail .= '<p>Tudo em conformidade. ✅</p>';
  }
}
$detail .= '</div>';

$content = $summary . $detail;
echo renderDashboardLayout('Verificar Deploy', 'Compara manifestos e hashes no servidor', $content, 'updates');
