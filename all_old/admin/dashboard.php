<?php
/**
 * Admin Dashboard Entry Point
 * Redirects to unified dashboard with role-based content
 */
require_once __DIR__ . '/../inc/auth.php';
requireLogin();

// Ensure admin/manager roles only
checkRole(['Gestor', 'Suporte Financeiro', 'Suporte Técnico', 'Suporte ao Cliente']);

// Redirect to unified dashboard (it shows role-specific content)
header('Location: /dashboard.php');
exit;
