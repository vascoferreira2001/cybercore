<?php
// Script de migração da base de dados para produção
// Execute via browser ou CLI: php migrate.php

require_once __DIR__ . '/inc/db.php';

$pdo = getDB();

try {
    // Executar schema.sql
    $schema = file_get_contents(__DIR__ . '/sql/schema.sql');
    $pdo->exec($schema);
    echo "Schema importado com sucesso.\n";

    // Executar roles_and_domains.sql
    $roles = file_get_contents(__DIR__ . '/sql/roles_and_domains.sql');
    $pdo->exec($roles);
    echo "Roles and domains importado com sucesso.\n";

    // Executar password_resets.sql
    $resets = file_get_contents(__DIR__ . '/sql/password_resets.sql');
    $pdo->exec($resets);
    echo "Password resets importado com sucesso.\n";

    echo "Migração concluída!\n";
} catch (PDOException $e) {
    echo "Erro na migração: " . $e->getMessage() . "\n";
}
?>