-- Full schema and seeds for CyberCore (24-12-2025)
-- Use for fresh installs; for existing DBs, run migrations separately.

CREATE DATABASE IF NOT EXISTS cybercore CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE cybercore;

-- Users, tickets, logs, invoices
-- (from schema.sql, updated with company_name)
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  country VARCHAR(100),
  address VARCHAR(255),
  city VARCHAR(100),
  postal_code VARCHAR(20),
  phone VARCHAR(50),
  nif VARCHAR(20) NOT NULL,
  entity_type ENUM('Singular','Coletiva') NOT NULL DEFAULT 'Singular',
  company_name VARCHAR(255) DEFAULT NULL,
  password_hash VARCHAR(255) NOT NULL,
  receive_news TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS tickets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  subject VARCHAR(255) NOT NULL,
  message TEXT NOT NULL,
  status ENUM('open','pending','closed') DEFAULT 'open',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  type VARCHAR(100) NOT NULL,
  message TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS invoices (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  reference VARCHAR(100) NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  due_date DATE,
  status ENUM('unpaid','paid','overdue') DEFAULT 'unpaid',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Roles and domains
ALTER TABLE users
  ADD COLUMN IF NOT EXISTS role ENUM('Cliente','Suporte ao Cliente','Suporte Financeira','Suporte Técnica','Gestor') NOT NULL DEFAULT 'Cliente';

CREATE TABLE IF NOT EXISTS domains (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  domain VARCHAR(255) NOT NULL,
  type ENUM('Alojamento Web', 'Alojamento de Email', 'Domínios', 'Servidores Dedicados', 'Servidores VPS', 'Serviços de Manutenção de Websites', 'Desenvolvimento de Website', 'Gestão de Redes Sociais') NOT NULL DEFAULT 'Domínios',
  registered_on DATE NULL,
  expires_on DATE NULL,
  status ENUM('active','expired','pending') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Settings + seeds, departments, permissions, categories, taxes, payment methods
CREATE TABLE IF NOT EXISTS settings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(100) NOT NULL UNIQUE,
  setting_value LONGTEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_key (setting_key)
);

INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
('site_logo', ''),
('favicon', ''),
('login_background', ''),
('site_language', 'pt-PT'),
('site_timezone', 'Europe/Lisbon'),
('date_format', 'd/m/Y'),
('time_format', 'H:i'),
('week_start', 'Segunda'),
('weekend_days', 'Sábado,Domingo'),
('currency', 'EUR'),
('currency_symbol', '€'),
('currency_position', 'right'),
('decimal_separator', ','),
('decimal_precision', '2'),
('cron_url', ''),
('cron_last_run', ''),
('cron_interval_minutes', '10'),
('smtp_host', ''),
('smtp_port', '587'),
('smtp_user', ''),
('smtp_pass', ''),
('smtp_secure', 'tls'),
('smtp_from', 'no-reply@seudominio.com'),
('smtp_from_name', 'CyberCore');

INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
('company_name', ''),
('company_address', ''),
('company_phone', ''),
('company_email', ''),
('company_website', ''),
('company_nif', ''),
('company_logo', '');

INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
('maintenance_disable_login', '0'),
('maintenance_message', ''),
('maintenance_exception_roles', 'Gestor'),
('maintenance_hide_menus', '[]');

CREATE TABLE IF NOT EXISTS departments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT IGNORE INTO departments (name, active) VALUES
('Suporte ao Cliente', 1),
('Suporte Técnico', 1),
('Suporte Financeiro', 1);

CREATE TABLE IF NOT EXISTS department_permissions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  department_id INT NOT NULL,
  permission_key VARCHAR(150) NOT NULL,
  permission_value LONGTEXT,
  permission_scope JSON,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY dept_permission (department_id, permission_key),
  FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS client_permissions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  permission_key VARCHAR(100) NOT NULL UNIQUE,
  allowed TINYINT(1) DEFAULT 0
);

INSERT IGNORE INTO client_permissions (permission_key, allowed) VALUES
('disable_account_creation', 0),
('verify_email_before_login', 0),
('client_view_documents', 1),
('client_add_documents', 0);

CREATE TABLE IF NOT EXISTS service_categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS taxes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  rate DECIMAL(5,2) NOT NULL,
  active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS payment_methods (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  gateway VARCHAR(100) DEFAULT '',
  active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Password resets
CREATE TABLE IF NOT EXISTS password_resets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token VARCHAR(128) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Services tables
CREATE TABLE IF NOT EXISTS web_hosting (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  plan VARCHAR(100) NOT NULL,
  storage VARCHAR(50),
  bandwidth VARCHAR(50),
  status ENUM('active','inactive','pending') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS email_hosting (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  plan VARCHAR(100) NOT NULL,
  storage VARCHAR(50),
  status ENUM('active','inactive','pending') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS dedicated_servers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  cpu VARCHAR(100),
  ram VARCHAR(50),
  storage VARCHAR(100),
  status ENUM('active','inactive','pending') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS vps_servers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  cpu VARCHAR(100),
  ram VARCHAR(50),
  storage VARCHAR(100),
  status ENUM('active','inactive','pending') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS website_maintenance (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  plan VARCHAR(100) NOT NULL,
  status ENUM('active','inactive','pending') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS website_development (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  project_name VARCHAR(255) NOT NULL,
  status ENUM('active','inactive','pending') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS social_media_management (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  platforms TEXT,
  status ENUM('active','inactive','pending') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
