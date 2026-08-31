-- Banco de dados usado para armazenar os serviços cadastrados e os funcionários que podem acessar o sistema.
CREATE DATABASE IF NOT EXISTS teste_titan
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE teste_titan;

-- Armazena os funcionários que poderão acessar o sistema.
CREATE TABLE IF NOT EXISTS `user` (
    id_user BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    update_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    ativo TINYINT(1) NOT NULL DEFAULT 1
);

-- Cada serviço pertence ao funcionário que realizou o cadastro.
CREATE TABLE IF NOT EXISTS `service` (
    id_service BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    description VARCHAR(255) NOT NULL,
    price DECIMAL(11, 2) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    update_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    -- Uma data preenchida indica que o serviço já foi finalizado.
    finished_at DATETIME NULL,
    commission_user DECIMAL(11, 2) NULL,
    user_id_user BIGINT UNSIGNED NOT NULL,

    CONSTRAINT fk_service_user
        FOREIGN KEY (user_id_user)
        REFERENCES `user` (id_user)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    -- Índices usados nas consultas e nos filtros do dashboard.
    INDEX idx_service_user (user_id_user),
    INDEX idx_service_created_at (created_at),
    INDEX idx_service_finished_at (finished_at)
);

-- Facilita a conferência das tabelas depois da importação.
SHOW TABLES;