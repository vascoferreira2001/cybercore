-- CyberCore - Complete Database Schema
-- Last updated: 27 December 2025
-- Optimized for hosting provider platform with services, billing, tickets, and user management

CREATE DATABASE IF NOT EXISTS cybercore CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cybercore;

-- ============================================================================
-- USERS & AUTHENTICATION
-- ============================================================================

CREATE TABLE IF NOT EXISTS `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `identifier` VARCHAR(50) NOT NULL COMMENT 'Unique identifier: CYC#00001',
  `email` VARCHAR(255) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(50) DEFAULT NULL,
  `nif` VARCHAR(20) NOT NULL COMMENT 'Tax ID (Portugal)',
  `entity_type` ENUM('Singular','Coletiva') NOT NULL DEFAULT 'Singular',
  `company_name` VARCHAR(255) DEFAULT NULL,
  `address` VARCHAR(255) DEFAULT NULL,
  `city` VARCHAR(100) DEFAULT NULL,
  `postal_code` VARCHAR(20) DEFAULT NULL,
  `country` VARCHAR(100) DEFAULT 'Portugal',
  `role` ENUM('Cliente','Suporte ao Cliente','Suporte Financeiro','Suporte Técnico','Gestor') NOT NULL DEFAULT 'Cliente',
  `email_verified` TINYINT(1) NOT NULL DEFAULT 0,
  `email_verification_token` VARCHAR(64) DEFAULT NULL,
  `email_verification_expires` DATETIME DEFAULT NULL,
  `receive_news` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_users_identifier` (`identifier`),
  UNIQUE KEY `uniq_users_email` (`email`),
  KEY `idx_users_role` (`role`),
  KEY `idx_users_email_verified` (`email_verified`),
  CONSTRAINT `chk_users_entity_company` CHECK ((`entity_type` = 'Singular') OR (`company_name` IS NOT NULL))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `token` VARCHAR(128) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `used` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_password_resets_token` (`token`),
  KEY `idx_password_resets_user` (`user_id`),
  KEY `idx_password_resets_expires` (`expires_at`),
  CONSTRAINT `fk_password_resets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_sessions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `session_token` VARCHAR(128) NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `user_agent` TEXT,
  `last_activity` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `expires_at` DATETIME NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_sessions_token` (`session_token`),
  KEY `idx_sessions_user` (`user_id`),
  KEY `idx_sessions_expires` (`expires_at`),
  CONSTRAINT `fk_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- SERVICES & HOSTING
-- ============================================================================

CREATE TABLE IF NOT EXISTS `services` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `domain` VARCHAR(255) NOT NULL,
  `plan` VARCHAR(50) NOT NULL,
  `billing_cycle` ENUM('monthly','yearly') NOT NULL DEFAULT 'monthly',
  `status` ENUM('provisioning','active','pending','suspended','canceled') NOT NULL DEFAULT 'provisioning',
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `currency` CHAR(3) NOT NULL DEFAULT 'EUR',
  `plesk_subscription_id` VARCHAR(100) DEFAULT NULL COMMENT 'Plesk API subscription ID',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `next_due_date` DATE DEFAULT NULL,
  `canceled_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_services_user_domain` (`user_id`, `domain`),
  KEY `idx_services_user` (`user_id`),
  KEY `idx_services_status` (`status`),
  KEY `idx_services_domain` (`domain`),
  KEY `idx_services_user_status` (`user_id`, `status`),
  KEY `idx_services_next_due` (`next_due_date`),
  CONSTRAINT `fk_services_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_services_price` CHECK (`price` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `domains` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `service_id` BIGINT UNSIGNED DEFAULT NULL,
  `domain` VARCHAR(255) NOT NULL,
  `type` ENUM('Alojamento Web','Alojamento de Email','Domínios','Servidores Dedicados','Servidores VPS','Serviços de Manutenção de Websites','Desenvolvimento de Website','Gestão de Redes Sociais') NOT NULL DEFAULT 'Domínios',
  `registered_on` DATE DEFAULT NULL,
  `expires_on` DATE DEFAULT NULL,
  `renewal_date` DATE DEFAULT NULL,
  `status` ENUM('active','expired','pending','suspended') DEFAULT 'active',
  `auto_renew` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_domains_domain` (`domain`),
  KEY `idx_domains_user` (`user_id`),
  KEY `idx_domains_service` (`service_id`),
  KEY `idx_domains_status` (`status`),
  KEY `idx_domains_renewal` (`renewal_date`),
  KEY `idx_domains_expires` (`expires_on`),
  CONSTRAINT `fk_domains_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_domains_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- BILLING & INVOICES
-- ============================================================================

CREATE TABLE IF NOT EXISTS `invoices` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `service_id` BIGINT UNSIGNED DEFAULT NULL,
  `number` VARCHAR(64) NOT NULL,
  `reference` VARCHAR(128) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Net amount',
  `vat_rate` DECIMAL(5,2) NOT NULL DEFAULT 23.00,
  `vat_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `currency` CHAR(3) NOT NULL DEFAULT 'EUR',
  `status` ENUM('draft','unpaid','paid','overdue','canceled') NOT NULL DEFAULT 'unpaid',
  `due_date` DATE NOT NULL,
  `issued_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `paid_at` DATETIME DEFAULT NULL,
  `pdf_path` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_invoices_number` (`number`),
  KEY `idx_invoices_user` (`user_id`),
  KEY `idx_invoices_service` (`service_id`),
  KEY `idx_invoices_status` (`status`),
  KEY `idx_invoices_due` (`due_date`),
  KEY `idx_invoices_user_status` (`user_id`, `status`),
  CONSTRAINT `fk_invoices_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_invoices_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_invoices_amount` CHECK (`amount` >= 0),
  CONSTRAINT `chk_invoices_vat_rate` CHECK (`vat_rate` >= 0 AND `vat_rate` <= 30),
  CONSTRAINT `chk_invoices_vat_amount` CHECK (`vat_amount` >= 0),
  CONSTRAINT `chk_invoices_total` CHECK (`total` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- SUPPORT TICKETS
-- ============================================================================

CREATE TABLE IF NOT EXISTS `tickets` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `assigned_to` BIGINT UNSIGNED DEFAULT NULL COMMENT 'Support staff assigned',
  `subject` VARCHAR(255) NOT NULL,
  `priority` ENUM('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
  `status` ENUM('open','customer-replied','answered','pending','closed') NOT NULL DEFAULT 'open',
  `department` VARCHAR(100) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tickets_user` (`user_id`),
  KEY `idx_tickets_assigned` (`assigned_to`),
  KEY `idx_tickets_status` (`status`),
  KEY `idx_tickets_priority` (`priority`),
  KEY `idx_tickets_user_status` (`user_id`, `status`),
  CONSTRAINT `fk_tickets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tickets_assigned` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ticket_messages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ticket_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED DEFAULT NULL,
  `is_admin` TINYINT(1) NOT NULL DEFAULT 0,
  `message` TEXT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ticket_messages_ticket` (`ticket_id`),
  KEY `idx_ticket_messages_user` (`user_id`),
  KEY `idx_ticket_messages_created` (`created_at`),
  CONSTRAINT `fk_ticket_messages_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ticket_messages_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- FISCAL & COMPLIANCE
-- ============================================================================

CREATE TABLE IF NOT EXISTS `fiscal_change_requests` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `old_nif` VARCHAR(20) NOT NULL,
  `new_nif` VARCHAR(20) NOT NULL,
  `old_entity_type` ENUM('Singular','Coletiva') NOT NULL,
  `new_entity_type` ENUM('Singular','Coletiva') NOT NULL,
  `old_company_name` VARCHAR(255) DEFAULT NULL,
  `new_company_name` VARCHAR(255) DEFAULT NULL,
  `reason` TEXT NOT NULL,
  `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `requested_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reviewed_by` BIGINT UNSIGNED DEFAULT NULL,
  `reviewed_at` DATETIME DEFAULT NULL,
  `decision_reason` VARCHAR(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_fiscal_user` (`user_id`),
  KEY `idx_fiscal_status` (`status`),
  KEY `idx_fiscal_reviewed` (`reviewed_by`),
  KEY `idx_fiscal_requested` (`requested_at`),
  CONSTRAINT `fk_fiscal_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fiscal_reviewed` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- NOTIFICATIONS & LOGS
-- ============================================================================

CREATE TABLE IF NOT EXISTS `notifications` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `type` ENUM('info','success','warning','error') DEFAULT 'info',
  `is_read` TINYINT(1) DEFAULT 0,
  `action_url` VARCHAR(500) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `read_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_notifications_user` (`user_id`),
  KEY `idx_notifications_read` (`is_read`),
  KEY `idx_notifications_created` (`created_at`),
  KEY `idx_notifications_user_read` (`user_id`, `is_read`),
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED DEFAULT NULL,
  `type` VARCHAR(100) NOT NULL,
  `message` TEXT NOT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_logs_user` (`user_id`),
  KEY `idx_logs_type` (`type`),
  KEY `idx_logs_created` (`created_at`),
  CONSTRAINT `fk_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- EMAIL TEMPLATES
-- ============================================================================

CREATE TABLE IF NOT EXISTS `email_templates` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `template_key` VARCHAR(100) NOT NULL,
  `template_name` VARCHAR(255) NOT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `body_html` LONGTEXT NOT NULL,
  `body_text` LONGTEXT,
  `variables` TEXT COMMENT 'JSON array of available variables',
  `is_system` TINYINT(1) DEFAULT 0 COMMENT 'System templates cannot be deleted',
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_email_templates_key` (`template_key`),
  KEY `idx_email_templates_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Email verification template
INSERT IGNORE INTO `email_templates` (`template_key`, `template_name`, `subject`, `body_html`, `body_text`, `variables`, `is_system`, `is_active`) VALUES 
('email_verification', 'Verificação de Email', 'Verifique o seu email - {{site_name}}', '<html><body style="font-family:Arial,sans-serif;background:#f4f5f7;padding:20px"><table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:8px;margin:0 auto"><tr><td style="padding:32px"><h1 style="color:#123659;font-size:24px">{{site_name}}</h1><p style="color:#5a6c7d;line-height:1.6">Olá {{user_name}},</p><p style="color:#5a6c7d;line-height:1.6">Por favor, verifique o seu email clicando no link abaixo:</p><p style="margin:24px 0"><a href="{{verification_link}}" style="background:#123659;color:#fff;padding:12px 24px;text-decoration:none;border-radius:4px;display:inline-block">Verificar Email</a></p><p style="color:#8896a6;font-size:13px">Link válido por 24 horas.</p></td></tr></table></body></html>', 'Olá {{user_name}}, Por favor verifique o seu email: {{verification_link}}', '["site_name","user_name","verification_link"]', 1, 1);

-- Password reset template
INSERT IGNORE INTO `email_templates` (`template_key`, `template_name`, `subject`, `body_html`, `body_text`, `variables`, `is_system`, `is_active`) VALUES 
('password_reset', 'Recuperação de Password', 'Recuperar password - {{site_name}}', '<html><body style="font-family:Arial,sans-serif;background:#f4f5f7;padding:20px"><table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:8px;margin:0 auto"><tr><td style="padding:32px"><h1 style="color:#123659;font-size:24px">{{site_name}}</h1><p style="color:#5a6c7d;line-height:1.6">Olá {{user_name}},</p><p style="color:#5a6c7d;line-height:1.6">Para redefinir a sua password, clique no link abaixo:</p><p style="margin:24px 0"><a href="{{reset_link}}" style="background:#123659;color:#fff;padding:12px 24px;text-decoration:none;border-radius:4px;display:inline-block">Redefinir Password</a></p><p style="color:#8896a6;font-size:13px">Link válido por 1 hora.</p></td></tr></table></body></html>', 'Olá {{user_name}}, Para redefinir a sua password: {{reset_link}}', '["site_name","user_name","reset_link"]', 1, 1);

-- Welcome email template
INSERT IGNORE INTO `email_templates` (`template_key`, `template_name`, `subject`, `body_html`, `body_text`, `variables`, `is_system`, `is_active`) VALUES 
('welcome_email', 'Bem-vindo', 'Bem-vindo ao {{site_name}}!', '<html><body style="font-family:Arial,sans-serif;background:#f4f5f7;padding:20px"><table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:8px;margin:0 auto"><tr><td style="padding:32px"><h1 style="color:#123659;font-size:24px">Bem-vindo ao {{site_name}}!</h1><p style="color:#5a6c7d;line-height:1.6">Olá {{user_name}},</p><p style="color:#5a6c7d;line-height:1.6">A sua conta foi ativada com sucesso. Pode agora aceder à sua área de cliente.</p><p style="margin:24px 0"><a href="{{dashboard_link}}" style="background:#32a852;color:#fff;padding:12px 24px;text-decoration:none;border-radius:4px;display:inline-block">Aceder ao Dashboard</a></p></td></tr></table></body></html>', 'Olá {{user_name}}, Bem-vindo ao {{site_name}}! Aceda ao dashboard: {{dashboard_link}}', '["site_name","user_name","dashboard_link"]', 1, 1);

-- ============================================================================
-- SETTINGS & CONFIGURATION
-- ============================================================================

CREATE TABLE IF NOT EXISTS `settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` LONGTEXT,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_settings_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default settings
INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES
('site_name', 'CyberCore'),
('site_language', 'pt-PT'),
('site_timezone', 'Europe/Lisbon'),
('currency', 'EUR'),
('currency_symbol', '€'),
('vat_rate', '23.00'),
('smtp_host', ''),
('smtp_port', '587'),
('smtp_user', ''),
('smtp_pass', ''),
('smtp_secure', 'tls'),
('smtp_from', 'no-reply@cybercore.pt'),
('smtp_from_name', 'CyberCore'),
('company_name', 'CyberCore'),
('company_nif', ''),
('company_address', ''),
('company_phone', ''),
('company_email', 'info@cybercore.pt');

-- ============================================================================
-- CHANGELOG
-- ============================================================================

CREATE TABLE IF NOT EXISTS `changelog` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `version` VARCHAR(20) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `release_date` DATETIME NOT NULL,
  `status` ENUM('pending','completed','failed') DEFAULT 'completed',
  `executed_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_changelog_version` (`version`),
  KEY `idx_changelog_release` (`release_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `changelog` (`version`, `title`, `description`, `release_date`) VALUES 
('1.0.0', 'Initial Release', 'Complete hosting management system with services, billing, support tickets', '2025-12-27 00:00:00');
