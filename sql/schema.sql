-- Schema para CyberCore Área de Cliente (MySQL)

-- Último atualizado: 25 de Dezembro de 2025
-- Design System: Font=Source Sans 3, Cor Primária=#007dff
-- Segurança: Email UNIQUE, Identificador UNIQUE (CYC#00001), Password Hashed

CREATE DATABASE IF NOT EXISTS cybercore CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE cybercore;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
    identifier VARCHAR(50) NOT NULL UNIQUE COMMENT 'Identificador único formato CYC#00001',
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE COMMENT 'Email único - não pode haver duplicatas',
  country VARCHAR(100),
  address VARCHAR(255),
  city VARCHAR(100),
  postal_code VARCHAR(20),
  phone VARCHAR(50),
  nif VARCHAR(20) NOT NULL,
  entity_type ENUM('Singular','Coletiva') NOT NULL DEFAULT 'Singular',
  company_name VARCHAR(255) DEFAULT NULL,
  role ENUM('Cliente','Suporte ao Cliente','Suporte Financeira','Suporte Técnica','Gestor') NOT NULL DEFAULT 'Cliente',
  password_hash VARCHAR(255) NOT NULL,
  receive_news TINYINT(1) DEFAULT 0,
  email_verified TINYINT(1) DEFAULT 0,
  email_verification_token VARCHAR(64) DEFAULT NULL,
  email_verification_expires DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_identifier (identifier),
  KEY idx_email (email)
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

-- Tabela de domínios
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

-- Tabela de configurações e seeds
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

-- Estrutura de departamentos e permissões
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

-- Tabela para resets de password
CREATE TABLE IF NOT EXISTS password_resets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token VARCHAR(128) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabelas de serviços
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

-- Tabela de modelos de email
CREATE TABLE IF NOT EXISTS email_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_key VARCHAR(100) NOT NULL UNIQUE,
    template_name VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body_html LONGTEXT NOT NULL,
    body_text LONGTEXT,
    variables TEXT COMMENT 'JSON array of available variables',
    is_system TINYINT(1) DEFAULT 0 COMMENT 'System templates cannot be deleted',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_key (template_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Inserir templates de email pré-definidos
INSERT IGNORE INTO email_templates (template_key, template_name, subject, body_html, body_text, variables, is_system, is_active) VALUES 
('email_verification', 'Verificação de Email', 'Verifique o seu email - {{site_name}}', '<html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head><body style="margin:0;padding:0;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Helvetica,Arial,sans-serif;background:#f4f5f7"><table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f5f7;padding:40px 20px"><tr><td align="center"><table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:4px"><tr><td style="padding:32px 40px;border-bottom:1px solid #e8e9ed"><h1 style="margin:0;color:#123659;font-size:20px;font-weight:600">{{site_name}}</h1></td></tr><tr><td style="padding:40px"><p style="margin:0 0 16px 0;color:#1e2e3e;font-size:16px">Estimado/a {{user_name}},</p><p style="margin:0 0 24px 0;color:#5a6c7d;font-size:15px;line-height:1.6">Obrigado por ter escolhido o {{site_name}}! Para completar o seu registo, por favor verifique o seu endereço de email clicando no botão abaixo.</p><table cellpadding="0" cellspacing="0" style="margin:32px 0"><tr><td align="center" style="background:#123659;border-radius:3px"><a href="{{verification_link}}" style="display:inline-block;padding:14px 32px;color:#ffffff;text-decoration:none;font-size:15px;font-weight:500">Verificar o meu email</a></td></tr></table><p style="margin:24px 0 0 0;color:#8896a6;font-size:13px;line-height:1.6">Se o botão não funcionar, pode copiar e colar o seguinte link no seu navegador:</p><p style="margin:8px 0 0 0;color:#4a90e2;font-size:13px;word-break:break-all">{{verification_link}}</p><hr style="border:none;border-top:1px solid #e8e9ed;margin:32px 0"><p style="margin:0;color:#8896a6;font-size:13px;line-height:1.6">Este link é válido por 24 horas. Se não solicitou este registo, pode ignorar este email.</p></td></tr><tr><td style="padding:24px 40px;text-align:center;background:#f8f9fa;border-top:1px solid #e8e9ed"><p style="margin:0;color:#8896a6;font-size:12px">© {{current_year}} {{site_name}}. Todos os direitos reservados.</p></td></tr></table></td></tr></table></body></html>', 'Estimado/a {{user_name}}, Obrigado por ter escolhido o {{site_name}}! Para completar o seu registo, por favor verifique o seu endereço de email clicando no link abaixo: {{verification_link}} Este link é válido por 24 horas. Se não solicitou este registo, pode ignorar este email. © {{current_year}} {{site_name}}. Todos os direitos reservados.', '[\"site_name\", \"user_name\", \"verification_link\", \"current_year\"]', 1, 1);

INSERT IGNORE INTO email_templates (template_key, template_name, subject, body_html, body_text, variables, is_system, is_active) VALUES 
('password_reset', 'Recuperação de Password', 'Recuperar password - {{site_name}}', '<html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head><body style="margin:0;padding:0;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Helvetica,Arial,sans-serif;background:#f4f5f7"><table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f5f7;padding:40px 20px"><tr><td align="center"><table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:4px"><tr><td style="padding:32px 40px;border-bottom:1px solid #e8e9ed"><h1 style="margin:0;color:#123659;font-size:20px;font-weight:600">{{site_name}}</h1></td></tr><tr><td style="padding:40px"><p style="margin:0 0 16px 0;color:#1e2e3e;font-size:16px">Estimado/a {{user_name}},</p><p style="margin:0 0 24px 0;color:#5a6c7d;font-size:15px;line-height:1.6">Recebemos um pedido para redefinir a password da sua conta. Para criar uma nova password, clique no botão abaixo.</p><table cellpadding="0" cellspacing="0" style="margin:32px 0"><tr><td align="center" style="background:#123659;border-radius:3px"><a href="{{reset_link}}" style="display:inline-block;padding:14px 32px;color:#ffffff;text-decoration:none;font-size:15px;font-weight:500">Redefinir a minha password</a></td></tr></table><p style="margin:24px 0 0 0;color:#8896a6;font-size:13px;line-height:1.6">Se o botão não funcionar, pode copiar e colar o seguinte link no seu navegador:</p><p style="margin:8px 0 0 0;color:#4a90e2;font-size:13px;word-break:break-all">{{reset_link}}</p><hr style="border:none;border-top:1px solid #e8e9ed;margin:32px 0"><p style="margin:0;color:#8896a6;font-size:13px;line-height:1.6">Este link é válido por 1 hora. Se não solicitou esta alteração, pode ignorar este email - a sua password permanecerá inalterada.</p></td></tr><tr><td style="padding:24px 40px;text-align:center;background:#f8f9fa;border-top:1px solid #e8e9ed"><p style="margin:0;color:#8896a6;font-size:12px">© {{current_year}} {{site_name}}. Todos os direitos reservados.</p></td></tr></table></td></tr></table></body></html>', 'Estimado/a {{user_name}}, Recebemos um pedido para redefinir a password da sua conta. Para criar uma nova password, clique no link abaixo: {{reset_link}} Este link é válido por 1 hora. Se não solicitou esta alteração, pode ignorar este email - a sua password permanecerá inalterada. © {{current_year}} {{site_name}}. Todos os direitos reservados.', '[\"site_name\", \"user_name\", \"reset_link\", \"current_year\"]', 1, 1);

INSERT IGNORE INTO email_templates (template_key, template_name, subject, body_html, body_text, variables, is_system, is_active) VALUES 
('welcome_email', 'Bem-vindo ao {{site_name}}', 'Bem-vindo! A sua conta foi ativada', '<html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head><body style="margin:0;padding:0;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Helvetica,Arial,sans-serif;background:#f4f5f7"><table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f5f7;padding:40px 20px"><tr><td align="center"><table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:4px"><tr><td style="padding:32px 40px;border-bottom:1px solid #e8e9ed"><h1 style="margin:0;color:#123659;font-size:20px;font-weight:600">{{site_name}}</h1></td></tr><tr><td style="padding:40px"><p style="margin:0 0 16px 0;color:#1e2e3e;font-size:16px">Bem-vindo/a {{user_name}}!</p><p style="margin:0 0 24px 0;color:#5a6c7d;font-size:15px;line-height:1.6">O seu email foi verificado com sucesso e a sua conta está agora ativa. Pode agora aceder à sua área de cliente e começar a utilizar os nossos serviços.</p><p style="margin:0 0 24px 0;color:#5a6c7d;font-size:15px;line-height:1.6">Eis algumas funcionalidades que pode aceder:</p><ul style="margin:0 0 24px 0;padding-left:20px;color:#5a6c7d;font-size:15px;line-height:1.8"><li>Alojamento Web e Email</li><li>Suporte Técnico</li><li>Faturas e Pagamentos</li></ul><table cellpadding="0" cellspacing="0" style="margin:32px 0"><tr><td align="center" style="background:#32a852;border-radius:3px"><a href="{{dashboard_link}}" style="display:inline-block;padding:14px 32px;color:#ffffff;text-decoration:none;font-size:15px;font-weight:500">Aceder ao Dashboard</a></td></tr></table><p style="margin:24px 0 0 0;color:#5a6c7d;font-size:14px;line-height:1.6">Se tiver alguma questão, não hesite em contactar o nosso suporte.</p><p style="margin:8px 0 0 0;color:#5a6c7d;font-size:14px">A equipa do {{site_name}}</p></td></tr><tr><td style="padding:24px 40px;text-align:center;background:#f8f9fa;border-top:1px solid #e8e9ed"><p style="margin:0;color:#8896a6;font-size:12px">© {{current_year}} {{site_name}}. Todos os direitos reservados.</p></td></tr></table></td></tr></table></body></html>', 'Bem-vindo/a {{user_name}}! O seu email foi verificado com sucesso e a sua conta está agora ativa. Pode agora aceder à sua área de cliente: {{dashboard_link}} Se tiver alguma questão, não hesite em contactar o nosso suporte. A equipa do {{site_name}} © {{current_year}} {{site_name}}. Todos os direitos reservados.', '[\"site_name\", \"user_name\", \"dashboard_link\", \"current_year\"]', 1, 1);

-- Tabela de changelog de modificações
CREATE TABLE IF NOT EXISTS changelog (
  id INT AUTO_INCREMENT PRIMARY KEY,
  version VARCHAR(20),
  migration_file VARCHAR(255),
  status ENUM('pending','completed','failed') DEFAULT 'pending',
  executed_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
