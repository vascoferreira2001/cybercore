<?php
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/db.php';
// Script para criar utilizadores de teste com diferentes roles.
// Execute uma vez via browser ou CLI: php sample_users.php

$pdo = getDB();
$users = [
  ['first'=>'Cliente','last'=>'Teste','email'=>'cliente@example.test','role'=>'Cliente'],
  ['first'=>'SuporteCliente','last'=>'Teste','email'=>'suporte_cliente@example.test','role'=>'Suporte ao Cliente'],
  ['first'=>'SuporteFinanceiro','last'=>'Teste','email'=>'suporte_financeiro@example.test','role'=>'Suporte Financeiro'],
  ['first'=>'SuporteTecnico','last'=>'Teste','email'=>'suporte_tecnico@example.test','role'=>'Suporte Técnico'],
  ['first'=>'Gestor','last'=>'Teste','email'=>'gestor@example.test','role'=>'Gestor'],
];
$pwd = 'Password123!';
foreach($users as $u){
  $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?'); $stmt->execute([$u['email']]);
  if ($stmt->fetch()) continue;
  $hash = password_hash($pwd, PASSWORD_DEFAULT);
  // Marcar utilizadores de teste como verificados para permitir login imediato
  $ins = $pdo->prepare('INSERT INTO users (first_name,last_name,email,password_hash,role,email_verified) VALUES (?,?,?,?,?,1)');
  $ins->execute([$u['first'],$u['last'],$u['email'],$hash,$u['role']]);
}
echo "Sample users created (password: $pwd)";
