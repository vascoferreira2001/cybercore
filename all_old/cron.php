<?php
// Cron de manutenção básico. Opcionalmente proteger com CRON_TOKEN no ambiente.
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/settings.php';

header('Content-Type: text/plain; charset=utf-8');

// Proteção opcional por token (defina CRON_TOKEN no ambiente)
$envToken = getenv('CRON_TOKEN');
if ($envToken) {
    $token = $_GET['token'] ?? '';
    if (!hash_equals($envToken, $token)) {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
}

try {
    $pdo = getDB();
    
    // Aplicar timezone definido nas configurações
    $settings = applyGeneralSettings($pdo);

    // TODO: adicionar tarefas recorrentes aqui (ex: faturas, lembretes, etc.)

    // Registar última execução
    $now = date('d/m/Y H:i:s');
    setSetting($pdo, 'cron_last_run', $now);
    
    echo 'OK ' . $now . "\n";
    echo 'Timezone: ' . date_default_timezone_get() . "\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo 'ERROR: ' . $e->getMessage() . "\n";
    echo 'File: ' . $e->getFile() . ':' . $e->getLine() . "\n";
    echo 'Trace: ' . $e->getTraceAsString();
}
