<?php
require_once __DIR__ . '/../inc/db.php';

$pdo = getDB();

try {
  // Tentar adicionar a coluna
  $pdo->exec('ALTER TABLE users ADD COLUMN identifier VARCHAR(50) UNIQUE AFTER id');
  echo "✅ Coluna identifier adicionada com sucesso\n";
} catch (PDOException $e) {
  if (strpos($e->getMessage(), 'Duplicate column') !== false) {
    echo "✅ Coluna identifier já existe\n";
  } else {
    echo "❌ Erro ao adicionar coluna: " . $e->getMessage() . "\n";
  }
}

// Agora gerar identificadores para utilizadores que não têm
try {
  $stmt = $pdo->query("SELECT id FROM users WHERE identifier IS NULL ORDER BY id ASC");
  $users = $stmt->fetchAll();
  
  foreach ($users as $user) {
    $id = $user['id'];
    $identifier = 'CYC#' . str_pad($id, 5, '0', STR_PAD_LEFT);
    $pdo->prepare("UPDATE users SET identifier = ? WHERE id = ?")->execute([$identifier, $id]);
  }
  
  echo "✅ Identificadores criados para " . count($users) . " utilizadores\n";
} catch (Exception $e) {
  echo "❌ Erro ao gerar identificadores: " . $e->getMessage() . "\n";
}
?>
