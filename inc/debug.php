<?php
// Helper para debug de erros
function enableErrorReporting() {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

function logError($message, $context = []) {
    $logFile = __DIR__ . '/../logs/errors.log';
    if (!is_dir(dirname($logFile))) {
        mkdir(dirname($logFile), 0755, true);
    }
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $message";
    if (!empty($context)) {
        $logEntry .= " | Context: " . json_encode($context);
    }
    $logEntry .= "\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    error_log($logEntry);
}

// Set error handler para capturar erros
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    logError("PHP Error ($errno): $errstr in $errfile:$errline");
    return false;
});

// Set exception handler
set_exception_handler(function($exception) {
    logError("Exception: " . $exception->getMessage(), [
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => $exception->getTraceAsString()
    ]);
});
?>
