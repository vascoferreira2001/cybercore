<?php
// Página descontinuada: redirecionar para Configurações > Equipa
require_once __DIR__ . '/../inc/auth.php';
requireLogin();
header('Location: /admin/settings.php?tab=equipa', true, 302);
exit;
?>
