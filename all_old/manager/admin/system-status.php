<?php
require_once __DIR__ . '/../../inc/auth.php';
require_once __DIR__ . '/../../inc/permissions.php';
require_once __DIR__ . '/../../inc/dashboard_helper.php';
require_once __DIR__ . '/../../inc/db.php';

checkRole(['Gestor']);
$user = currentUser();
$GLOBALS['currentUser'] = $user;
requirePermission('is_super_admin', $user);

$checks = [
  'php_version' => PHP_VERSION,
  'extensions' => [
    'pdo_mysql' => extension_loaded('pdo_mysql'),
    'openssl' => extension_loaded('openssl'),
  ],
  'db' => [ 'connected' => false, 'error' => null ],
  'paths' => [
    'root' => realpath(__DIR__ . '/../../'),
    'assets_writable' => false,
    'uploads_writable' => false,
  ],
  'session' => [ 'active' => session_status() === PHP_SESSION_ACTIVE ]
];

// DB check
try {
  $pdo = getDB();
  $stmt = $pdo->query('SELECT 1');
  $checks['db']['connected'] = ($stmt && $stmt->fetchColumn() == 1);
} catch (Throwable $e) {
  $checks['db']['error'] = $e->getMessage();
}

// Writable dirs
$assetsDir = __DIR__ . '/../../assets';
$uploadsDir = __DIR__ . '/../../assets/uploads';
$checks['paths']['assets_writable'] = is_writable($assetsDir);
$checks['paths']['uploads_writable'] = is_writable($uploadsDir);

$ok = function($b){ return $b ? '✅' : '❌'; };

$content = '<div class="card">'
  . '<h2>Estado do Sistema</h2>'
  . '<table style="width:100%">'
  . '<tr><th>PHP</th><td>' . htmlspecialchars($checks['php_version']) . '</td></tr>'
  . '<tr><th>Extensão pdo_mysql</th><td>' . $ok($checks['extensions']['pdo_mysql']) . '</td></tr>'
  . '<tr><th>Extensão openssl</th><td>' . $ok($checks['extensions']['openssl']) . '</td></tr>'
  . '<tr><th>Base de Dados</th><td>' . ($checks['db']['connected'] ? '✅ Conectado' : '❌ ' . htmlspecialchars($checks['db']['error'] ?? 'Falha')) . '</td></tr>'
  . '<tr><th>Assets writable</th><td>' . $ok($checks['paths']['assets_writable']) . '</td></tr>'
  . '<tr><th>Uploads writable</th><td>' . $ok($checks['paths']['uploads_writable']) . '</td></tr>'
  . '<tr><th>Sessão ativa</th><td>' . $ok($checks['session']['active']) . '</td></tr>'
  . '</table>'
  . '</div>';

echo renderDashboardLayout('Estado do Sistema', 'Verifica ambiente e ligações', $content, 'updates');
