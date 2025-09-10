-- Script SQL para limpar bloqueios de login
-- Execute este script diretamente no banco de dados

-- Limpa a tabela login_lockouts (remove todos os bloqueios ativos)
TRUNCATE TABLE login_lockouts;

-- Limpa a tabela login_attempts (opcional, mas ajuda a começar do zero)
TRUNCATE TABLE login_attempts;

-- Limpa a tabela failed_logins (opcional, mas ajuda a começar do zero)
TRUNCATE TABLE failed_logins;
