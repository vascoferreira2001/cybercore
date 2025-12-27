<?php
// Plesk REST API integration helpers
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/bootstrap.php';

/**
 * Build base headers using secure API key storage (.env: PLESK_API_KEY) and base URL (.env: PLESK_API_URL)
 */
function cybercore_plesk_base(): array
{
    $baseUrl = rtrim(cybercore_env('PLESK_API_URL', ''), '/');
    $apiKey = cybercore_env('PLESK_API_KEY', '');

    if (!$baseUrl || !$apiKey) {
        throw new RuntimeException('Plesk API URL ou chave não configurada. Defina PLESK_API_URL e PLESK_API_KEY no .env.');
    }

    return [
        'base_url' => $baseUrl,
        'headers' => [
            'Content-Type: application/json',
            'Accept: application/json',
            // Plesk aceita Authorization: Bearer ou X-API-Key; usamos Authorization por compatibilidade
            'Authorization: Bearer ' . $apiKey,
        ],
    ];
}

/**
 * Low-level HTTP requester for Plesk API
 */
function cybercore_plesk_request(string $method, string $path, ?array $payload = null): array
{
    $config = cybercore_plesk_base();
    $url = $config['base_url'] . '/' . ltrim($path, '/');

    $ch = curl_init($url);
    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_HTTPHEADER => $config['headers'],
        CURLOPT_TIMEOUT => 20,
    ];

    if ($payload !== null) {
        $options[CURLOPT_POSTFIELDS] = json_encode($payload, JSON_UNESCAPED_SLASHES);
    }

    // Ensure SSL verification is on; configure CA bundle at OS level if needed
    $options[CURLOPT_SSL_VERIFYPEER] = true;
    $options[CURLOPT_SSL_VERIFYHOST] = 2;

    curl_setopt_array($ch, $options);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        throw new RuntimeException('Erro de ligação ao Plesk: ' . $err);
    }

    $data = json_decode($response, true);
    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        throw new RuntimeException('Resposta inválida do Plesk (JSON): ' . json_last_error_msg());
    }

    if ($httpCode >= 400) {
        $message = $data['message'] ?? $data['error'] ?? 'Erro ' . $httpCode;
        throw new RuntimeException('Plesk API erro ' . $httpCode . ': ' . $message);
    }

    return $data ?? [];
}

/**
 * Create a hosting account (customer + subscription) using a service plan.
 * $customer = [ 'email' => '', 'name' => '', 'password' => '' ]
 * $subscription = [ 'domain' => '', 'service_plan' => '', 'ip_address' => '' ]
 */
function cybercore_plesk_create_hosting_account(array $customer, array $subscription): array
{
    // 1) Create / get customer
    $customerPayload = [
        'email' => $customer['email'],
        'name' => $customer['name'] ?? $customer['email'],
        'password' => $customer['password'],
    ];
    $client = cybercore_plesk_request('POST', '/api/v2/clients', $customerPayload);
    $clientId = $client['id'] ?? null;
    if (!$clientId) {
        throw new RuntimeException('Não foi possível obter ID do cliente criado.');
    }

    // 2) Create subscription (hosting account)
    $subscriptionPayload = [
        'name' => $subscription['domain'],
        'owner' => ['id' => $clientId],
        'servicePlan' => ['name' => $subscription['service_plan']],
        'hosting' => [
            'ftpLogin' => $customer['email'],
            'ftpPassword' => $customer['password'],
        ],
    ];
    if (!empty($subscription['ip_address'])) {
        $subscriptionPayload['ipAddresses'] = [$subscription['ip_address']];
    }

    $sub = cybercore_plesk_request('POST', '/api/v2/subscriptions', $subscriptionPayload);
    return [
        'client' => $client,
        'subscription' => $sub,
    ];
}

/**
 * Suspend a hosting account (subscription)
 */
function cybercore_plesk_suspend_account(string $subscriptionId): bool
{
    cybercore_plesk_request('POST', '/api/v2/subscriptions/' . urlencode($subscriptionId) . '/status', [
        'status' => 'suspended',
    ]);
    return true;
}

/**
 * Delete a hosting account (subscription)
 */
function cybercore_plesk_delete_account(string $subscriptionId): bool
{
    cybercore_plesk_request('DELETE', '/api/v2/subscriptions/' . urlencode($subscriptionId));
    return true;
}

/**
 * Assign/add a domain to an existing subscription (as additional site)
 */
function cybercore_plesk_assign_domain(string $subscriptionId, string $domain): array
{
    $payload = [
        'name' => $domain,
        'subscription' => ['id' => $subscriptionId],
        'hosting_type' => 'virtual',
    ];
    return cybercore_plesk_request('POST', '/api/v2/domains', $payload);
}
