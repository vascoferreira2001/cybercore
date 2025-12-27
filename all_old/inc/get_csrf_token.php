<?php
require_once __DIR__ . '/csrf.php';

header('Content-Type: application/json');

try {
    echo json_encode([
        'success' => true,
        'token' => csrf_token(),
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to generate token'
    ]);
}
