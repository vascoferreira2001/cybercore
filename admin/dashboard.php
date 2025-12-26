<?php
// Redirect all admin dashboard requests to the unified main dashboard
require_once __DIR__ . '/../inc/auth.php';
requireLogin();
header('Location: /dashboard.php');
exit;
