-- ⚠️ NÃO IMPORTAR DIRETAMENTE - FICHEIRO LEGACY ⚠️
-- Este ficheiro é apenas para referência.
-- Use sql/schema.sql para instalação limpa.
-- Importar este ficheiro causará erro #1060 (coluna duplicada).

-- Legacy: moved into consolidated schema.sql
USE cybercore;

ALTER TABLE users
  ADD COLUMN role ENUM('Cliente','Suporte ao Cliente','Suporte Financeira','Suporte Técnica','Gestor') NOT NULL DEFAULT 'Cliente';

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
