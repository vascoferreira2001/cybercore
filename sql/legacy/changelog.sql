-- Legacy: moved into consolidated schema.sql
CREATE TABLE IF NOT EXISTS changelog (
    id INT AUTO_INCREMENT PRIMARY KEY,
    version VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description LONGTEXT,
    release_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_version (version),
    INDEX idx_date (release_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO changelog (version, title, description, release_date) VALUES 
('1.0.0', 'Versão Inicial', 'Lançamento inicial do CyberCore com funcionalidades de autenticação, gestão de clientes e painel administrativo', NOW())
ON DUPLICATE KEY UPDATE version = version;
