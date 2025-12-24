<?php
// Script de migração da base de dados para produção
// Execute via browser ou CLI: php migrate.php

require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/db.php';

$pdo = getDB();
$dbName = defined('DB_NAME') ? DB_NAME : null;

try {
    // Nota: em MySQL, DDL pode fazer commits implícitos; usamos try/catch e mensagens claras.
    // Executar schema.sql
    $schema = file_get_contents(__DIR__ . '/sql/schema.sql');
    $pdo->exec($schema);
    echo "Schema importado com sucesso.\n";

    // Garantir coluna role em users apenas se não existir
    if ($dbName) {
        $check = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'users' AND COLUMN_NAME = 'role'");
        $check->execute([$dbName]);
        $hasRole = (int)$check->fetchColumn() > 0;
    } else {
        $hasRole = false; // Se não conseguirmos saber o schema, tentamos adicionar e tratamos erro.
    }
    if (!$hasRole) {
        $pdo->exec("ALTER TABLE users\n  ADD COLUMN role ENUM('Cliente','Suporte ao Cliente','Suporte Financeira','Suporte Técnica','Gestor') NOT NULL DEFAULT 'Cliente'");
        echo "Coluna role adicionada em users.\n";
    } else {
        echo "Coluna role já existe em users — a saltar.\n";
    }

    // Criar domains se necessário
    $pdo->exec("CREATE TABLE IF NOT EXISTS domains (\n  id INT AUTO_INCREMENT PRIMARY KEY,\n  user_id INT NOT NULL,\n  domain VARCHAR(255) NOT NULL,\n  type ENUM('Alojamento Web', 'Alojamento de Email', 'Domínios', 'Servidores Dedicados', 'Servidores VPS', 'Serviços de Manutenção de Websites', 'Desenvolvimento de Website', 'Gestão de Redes Sociais') NOT NULL DEFAULT 'Domínios',\n  registered_on DATE NULL,\n  expires_on DATE NULL,\n  status ENUM('active','expired','pending') DEFAULT 'active',\n  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE\n)");
    echo "Tabela domains verificada/criada.\n";

    // Executar password_resets.sql (idempotente)
    $resets = file_get_contents(__DIR__ . '/sql/password_resets.sql');
    $pdo->exec($resets);
    echo "Password resets importado com sucesso.\n";

    echo "Migração concluída!\n";
} catch (PDOException $e) {
    echo "Erro na migração: " . $e->getMessage() . "\n";
}
?>