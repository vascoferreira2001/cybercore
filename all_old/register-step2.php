<?php
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/csrf.php';
require_once __DIR__ . '/inc/settings.php';
require_once __DIR__ . '/inc/mailer.php';
require_once __DIR__ . '/inc/email_templates.php';
require_once __DIR__ . '/inc/debug.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// Fluxo legado: direcionar utilizadores para o formulário único
header('Location: register.php');
exit;

