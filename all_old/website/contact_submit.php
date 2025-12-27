<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: /website/contact.php');
  exit;
}

require_once __DIR__ . '/../inc/mailer.php';

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$message = trim($_POST['message'] ?? '');

$errors = [];
if ($name === '') $errors[] = 'Nome é obrigatório';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email inválido';
if ($subject === '') $errors[] = 'Assunto é obrigatório';
if ($message === '' || strlen($message) < 10) $errors[] = 'Mensagem deve ter pelo menos 10 caracteres';

if (!empty($errors)) {
  http_response_code(400);
  echo '<!doctype html><html lang="pt"><head><meta charset="utf-8"><title>Erro</title></head><body>';
  echo '<p>Corrija os seguintes erros:</p><ul>';
  foreach ($errors as $e) echo '<li>' . htmlspecialchars($e) . '</li>';
  echo '</ul><p><a href="/website/contact.php">Voltar</a></p></body></html>';
  exit;
}

$to = 'contact@cyberworld.pt';
$body = "Nova mensagem do website:\n\n"
      . "Nome: $name\n"
      . "Email: $email\n"
      . "Telefone: $phone\n"
      . "Assunto: $subject\n\n"
      . "Mensagem:\n$message\n";

$sent = sendEmail($to, 'Website Contact: ' . $subject, nl2br(htmlspecialchars($body)), $email);

if ($sent) {
  echo '<!doctype html><html lang="pt"><head><meta charset="utf-8"><title>Obrigado</title>';
  echo '<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700&display=swap" rel="stylesheet">';
  echo '<link rel="stylesheet" href="/assets/css/website.css">';
  echo '</head><body><div class="container" style="padding:40px">';
  echo '<h1>Obrigado!</h1><p>Recebemos a sua mensagem e iremos responder em breve.</p>';
  echo '<p><a class="btn primary" href="/website/index.php">Voltar ao início</a></p>';
  echo '</div></body></html>';
} else {
  http_response_code(500);
  echo '<!doctype html><html lang="pt"><head><meta charset="utf-8"><title>Erro</title></head><body>';
  echo '<h1>Ocorreu um erro ao enviar.</h1><p>Tente novamente mais tarde.</p>';
  echo '<p><a href="/website/contact.php">Voltar</a></p></body></html>';
}
