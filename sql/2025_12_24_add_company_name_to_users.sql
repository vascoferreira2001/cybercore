-- Adiciona coluna opcional para nome da empresa em contas coletivas
USE cybercore;

ALTER TABLE users
  ADD COLUMN company_name VARCHAR(255) NULL DEFAULT NULL AFTER entity_type;
