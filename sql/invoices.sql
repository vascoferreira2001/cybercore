-- Invoices table (Portugal VAT ready)
CREATE TABLE IF NOT EXISTS `invoices` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `number` VARCHAR(64) NOT NULL,
  `reference` VARCHAR(128) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00, -- net amount
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
  UNIQUE KEY `uniq_invoice_number` (`number`),
  KEY `idx_invoices_user` (`user_id`),
  KEY `idx_invoices_status` (`status`),
  KEY `idx_invoices_due` (`due_date`),
  KEY `idx_invoices_user_status` (`user_id`, `status`),
  CONSTRAINT `chk_invoices_amount` CHECK (`amount` >= 0),
  CONSTRAINT `chk_invoices_vat_rate` CHECK (`vat_rate` >= 0 AND `vat_rate` <= 30),
  CONSTRAINT `chk_invoices_vat_amount` CHECK (`vat_amount` >= 0),
  CONSTRAINT `chk_invoices_total` CHECK (`total` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
