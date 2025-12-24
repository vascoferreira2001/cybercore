-- Tabela para guardar configurações do website
CREATE TABLE IF NOT EXISTS settings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(100) NOT NULL UNIQUE,
  setting_value LONGTEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_key (setting_key)
);

-- Inserir configurações por defeito
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

-- Configurações da Empresa
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
('company_name', ''),
('company_address', ''),
('company_phone', ''),
('company_email', ''),
('company_website', ''),
('company_nif', ''),
('company_logo', '');

-- Tabelas de permissões e estrutura de negócio
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
  resource VARCHAR(100) NOT NULL,
  can_view TINYINT(1) DEFAULT 1,
  can_edit TINYINT(1) DEFAULT 0,
  can_delete TINYINT(1) DEFAULT 0,
  can_operate TINYINT(1) DEFAULT 0,
  UNIQUE KEY dept_resource (department_id, resource),
  FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS client_permissions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  permission_key VARCHAR(100) NOT NULL UNIQUE,
  allowed TINYINT(1) DEFAULT 0
);

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
