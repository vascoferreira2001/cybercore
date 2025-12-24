-- ⚠️ NÃO IMPORTAR DIRETAMENTE - FICHEIRO LEGACY ⚠️
-- Este ficheiro é apenas para referência.
-- Use sql/schema.sql para instalação limpa.

-- Legacy: moved into consolidated schema.sql
USE cybercore;

CREATE TABLE IF NOT EXISTS password_resets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token VARCHAR(128) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
