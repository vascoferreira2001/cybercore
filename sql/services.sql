-- Hosting services table
CREATE TABLE IF NOT EXISTS `services` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `domain` VARCHAR(255) NOT NULL,
  `plan` VARCHAR(50) NOT NULL,
  `billing_cycle` ENUM('monthly','yearly') NOT NULL DEFAULT 'monthly',
  `status` ENUM('provisioning','active','pending','suspended','canceled') NOT NULL DEFAULT 'provisioning',
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `currency` CHAR(3) NOT NULL DEFAULT 'EUR',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `next_due_date` DATE DEFAULT NULL,
  `canceled_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_domain` (`user_id`, `domain`),
  KEY `idx_services_user` (`user_id`),
  KEY `idx_services_status` (`status`),
  KEY `idx_services_domain` (`domain`),
  KEY `idx_services_user_status` (`user_id`, `status`),
  CONSTRAINT `chk_services_price_nonnegative` CHECK (`price` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
