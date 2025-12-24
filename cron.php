<?php
// Cron de manutenção básico. Opcionalmente proteger com CRON_TOKEN no ambiente.
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
    applyGeneralSettings($pdo);

    // TODO: adicionar tarefas recorrentes aqui (ex: faturas, lembretes, etc.)

    // Registar última execução
    setSetting($pdo, 'cron_last_run', date('Y-m-d H:i:s'));
    echo 'OK ' . date('Y-m-d H:i:s');
} catch (Throwable $e) {
    http_response_code(500);
    echo 'ERROR: ' . $e->getMessage();
}
