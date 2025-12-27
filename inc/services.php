<?php
// Hosting services backend helpers
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

function cybercore_services_allowed_statuses(): array
{
    return ['provisioning', 'active', 'pending', 'suspended', 'canceled'];
}

function cybercore_services_allowed_cycles(): array
{
    return ['monthly', 'yearly'];
}

function cybercore_services_list(int $userId): array
{
    $pdo = cybercore_pdo();
    $stmt = $pdo->prepare('SELECT * FROM services WHERE user_id = :user_id ORDER BY created_at DESC');
    $stmt->execute(['user_id' => $userId]);
    return $stmt->fetchAll();
}

function cybercore_services_get(int $userId, int $serviceId): ?array
{
    $pdo = cybercore_pdo();
    $stmt = $pdo->prepare('SELECT * FROM services WHERE user_id = :user_id AND id = :id LIMIT 1');
    $stmt->execute(['user_id' => $userId, 'id' => $serviceId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function cybercore_services_create(int $userId, array $data): int
{
    $allowedStatuses = cybercore_services_allowed_statuses();
    $allowedCycles = cybercore_services_allowed_cycles();

    $status = $data['status'] ?? 'provisioning';
    $cycle = $data['billing_cycle'] ?? 'monthly';

    if (!in_array($status, $allowedStatuses, true)) {
        throw new InvalidArgumentException('Estado de serviço inválido.');
    }
    if (!in_array($cycle, $allowedCycles, true)) {
        throw new InvalidArgumentException('Ciclo de faturação inválido.');
    }

    $pdo = cybercore_pdo();
    $stmt = $pdo->prepare('INSERT INTO services (user_id, domain, plan, billing_cycle, status, price, currency, next_due_date) VALUES (:user_id, :domain, :plan, :billing_cycle, :status, :price, :currency, :next_due_date)');

    $stmt->execute([
        'user_id' => $userId,
        'domain' => $data['domain'],
        'plan' => $data['plan'],
        'billing_cycle' => $cycle,
        'status' => $status,
        'price' => $data['price'],
        'currency' => $data['currency'] ?? 'EUR',
        'next_due_date' => $data['next_due_date'] ?? null,
    ]);

    return (int) $pdo->lastInsertId();
}

function cybercore_services_cancel(int $userId, int $serviceId): bool
{
    $pdo = cybercore_pdo();
    $stmt = $pdo->prepare('UPDATE services SET status = :status, canceled_at = NOW() WHERE user_id = :user_id AND id = :id AND status <> :status');
    $stmt->execute([
        'user_id' => $userId,
        'id' => $serviceId,
        'status' => 'canceled',
    ]);

    return $stmt->rowCount() > 0;
}

function cybercore_services_update_status(int $userId, int $serviceId, string $status): bool
{
    if (!in_array($status, cybercore_services_allowed_statuses(), true)) {
        throw new InvalidArgumentException('Estado de serviço inválido.');
    }

    $pdo = cybercore_pdo();
    $stmt = $pdo->prepare('UPDATE services SET status = :status WHERE user_id = :user_id AND id = :id');
    $stmt->execute([
        'user_id' => $userId,
        'id' => $serviceId,
        'status' => $status,
    ]);

    return $stmt->rowCount() > 0;
}
