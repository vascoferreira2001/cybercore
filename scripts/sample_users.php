<?php
require_once __DIR__ . '/inc/db.php';
// Script para criar utilizadores de teste com diferentes roles.
// Execute uma vez via browser ou CLI: php sample_users.php

$pdo = getDB();
$users = [
  ['first'=>'Cliente','last'=>'Teste','email'=>'cliente@example.test','role'=>'Cliente'],
  ['first'=>'SuporteCliente','last'=>'Teste','email'=>'suporte_cliente@example.test','role'=>'Suporte ao Cliente'],
  ['first'=>'SuporteFinance','last'=>'Teste','email'=>'suporte_finance@example.test','role'=>'Suporte Financeira'],
  ['first'=>'SuporteTecnica','last'=>'Teste','email'=>'suporte_tecnica@example.test','role'=>'Suporte Técnica'],
  ['first'=>'Gestor','last'=>'Teste','email'=>'gestor@example.test','role'=>'Gestor'],
];
$pwd = 'Password123!';
foreach($users as $u){
  $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?'); $stmt->execute([$u['email']]);
  if ($stmt->fetch()) continue;
  $hash = password_hash($pwd, PASSWORD_DEFAULT);
  $ins = $pdo->prepare('INSERT INTO users (first_name,last_name,email,password_hash,role) VALUES (?,?,?,?,?)');
  $ins->execute([$u['first'],$u['last'],$u['email'],$hash,$u['role']]);
}
echo "Sample users created (password: $pwd)";
