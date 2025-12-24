-- Legacy: moved into consolidated schema.sql
USE cybercore;

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
