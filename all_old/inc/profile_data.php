<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

$response = [
    'success' => false,
    'user' => null,
];

try {
    if (empty($_SESSION['user_id'])) {
        echo json_encode($response);
        exit;
    }

    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT id, first_name, last_name, email, country, address, city, postal_code, phone, nif, entity_type, company_name, role FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $u = $stmt->fetch();

    if (!$u) {
        echo json_encode($response);
        exit;
    }

    $fullName = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
    $response['user'] = [
        'id' => (int)$u['id'],
        'role' => $u['role'] ?? 'Cliente',
        'personal' => [
            'fullName' => $fullName,
            'email' => $u['email'] ?? '',
            'phone' => $u['phone'] ?? '',
            'address' => $u['address'] ?? '',
            'city' => $u['city'] ?? '',
            'postalCode' => $u['postal_code'] ?? '',
            'country' => $u['country'] ?? ''
        ],
        'fiscal' => [
            'entityType' => $u['entity_type'] ?? '',
            'companyName' => $u['company_name'] ?? '',
            'taxId' => $u['nif'] ?? '',
            'locked' => true
        ]
    ];
    $response['success'] = true;
} catch (Throwable $e) {
    error_log('profile_data error: ' . $e->getMessage());
}

echo json_encode($response);
