<?php
define('DASHBOARD_LAYOUT', true);
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/dashboard_helper.php';

checkRole(['Cliente','Suporte ao Cliente','Suporte Financeiro','Suporte Técnico','Gestor']);
$user = currentUser();
$GLOBALS['currentUser'] = $user;
$pdo = getDB();

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$results = [];

try {
  if ($q !== '') {
    try {
      $stmt = $pdo->prepare("SELECT id, subject AS title, 'ticket' AS type FROM tickets WHERE subject LIKE ? LIMIT 10");
      $stmt->execute(['%'.$q.'%']);
      $results = array_merge($results, $stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (Throwable $e) {}
    try {
      $stmt = $pdo->prepare("SELECT id, domain AS title, 'domain' AS type FROM domains WHERE domain LIKE ? LIMIT 10");
      $stmt->execute(['%'.$q.'%']);
      $results = array_merge($results, $stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (Throwable $e) {}
  }
} catch (Throwable $e) {}

$content = '<div class="panel">';
if ($q === ''): 
  $content .= '<div class="card">Introduza palavras-chave na barra acima.</div>';
else: 
  if (empty($results)): 
    $content .= '<div class="card">Sem resultados.</div>';
  else: 
    $content .= '<div class="metrics-grid">';
    foreach ($results as $r): 
      $content .= '<div class="metric-card">
        <div class="metric-title">' . htmlspecialchars(strtoupper($r['type'])) . '</div>
        <div class="metric-value" style="font-size:18px;font-weight:600;">
          ' . htmlspecialchars($r['title']) . '
        </div>
      </div>';
    endforeach;
    $content .= '</div>';
  endif;
endif;
$content .= '</div>';

echo renderDashboardLayout('Pesquisar', 'Resultados para: ' . htmlspecialchars($q), $content, 'search');
?>
