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
('login_background', '');
