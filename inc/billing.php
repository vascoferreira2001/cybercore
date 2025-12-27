<?php
// Billing and invoices helpers
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

function cybercore_invoice_allowed_statuses(): array
{
    return ['draft', 'unpaid', 'paid', 'overdue', 'canceled'];
}

function cybercore_invoice_generate_number(int $userId): string
{
    // Simple unique pattern: INV-YYYYMMDD-USERID-RAND
    $date = (new DateTime('now', new DateTimeZone(cybercore_env('APP_TZ', 'Europe/Lisbon'))))->format('Ymd');
    $rand = random_int(1000, 9999);
    return sprintf('INV-%s-%d-%d', $date, $userId, $rand);
}

function cybercore_invoice_create(int $userId, array $data): int
{
    $status = $data['status'] ?? 'unpaid';
    if (!in_array($status, cybercore_invoice_allowed_statuses(), true)) {
        throw new InvalidArgumentException('Estado de fatura inválido.');
    }

    $amount = (float) ($data['amount'] ?? 0);
    $vatRate = (float) ($data['vat_rate'] ?? 23.0);
    $vatAmount = round($amount * ($vatRate / 100), 2);
    $total = round($amount + $vatAmount, 2);

    $pdo = cybercore_pdo();
    $stmt = $pdo->prepare('INSERT INTO invoices (user_id, number, reference, description, amount, vat_rate, vat_amount, total, currency, status, due_date, pdf_path) VALUES (:user_id, :number, :reference, :description, :amount, :vat_rate, :vat_amount, :total, :currency, :status, :due_date, :pdf_path)');

    $stmt->execute([
        'user_id' => $userId,
        'number' => $data['number'] ?? cybercore_invoice_generate_number($userId),
        'reference' => $data['reference'] ?? null,
        'description' => $data['description'] ?? null,
        'amount' => $amount,
        'vat_rate' => $vatRate,
        'vat_amount' => $vatAmount,
        'total' => $total,
        'currency' => $data['currency'] ?? 'EUR',
        'status' => $status,
        'due_date' => $data['due_date'],
        'pdf_path' => $data['pdf_path'] ?? null,
    ]);

    return (int) $pdo->lastInsertId();
}

function cybercore_invoice_list(int $userId): array
{
    $pdo = cybercore_pdo();
    $stmt = $pdo->prepare('SELECT * FROM invoices WHERE user_id = :user_id ORDER BY issued_at DESC');
    $stmt->execute(['user_id' => $userId]);
    return $stmt->fetchAll();
}

function cybercore_invoice_get(int $userId, int $invoiceId): ?array
{
    $pdo = cybercore_pdo();
    $stmt = $pdo->prepare('SELECT * FROM invoices WHERE user_id = :user_id AND id = :id LIMIT 1');
    $stmt->execute(['user_id' => $userId, 'id' => $invoiceId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function cybercore_invoice_update_status(int $userId, int $invoiceId, string $status): bool
{
    if (!in_array($status, cybercore_invoice_allowed_statuses(), true)) {
        throw new InvalidArgumentException('Estado de fatura inválido.');
    }

    $pdo = cybercore_pdo();
    $stmt = $pdo->prepare('UPDATE invoices SET status = :status, paid_at = CASE WHEN :status_pay = "paid" THEN NOW() ELSE paid_at END WHERE user_id = :user_id AND id = :id');
    $stmt->execute([
        'status' => $status,
        'status_pay' => $status,
        'user_id' => $userId,
        'id' => $invoiceId,
    ]);

    return $stmt->rowCount() > 0;
}
