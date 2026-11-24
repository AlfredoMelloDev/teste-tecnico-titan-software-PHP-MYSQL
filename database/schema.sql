-- Esquema v2 do Sistema Web para Gestão de Serviços e Comissões.
-- Instalação nova: crie (ou selecione) o banco no servidor e importe este arquivo.
-- Bancos criados com o esquema antigo devem usar database/migration_v2.sql.
-- Requer MySQL 8.0.16+ (ou MariaDB 10.5+) para as restrições CHECK.

SET NAMES utf8mb4;

-- Usuários que acessam o sistema. O perfil define as permissões (RF02).
CREATE TABLE IF NOT EXISTS `user` (
    id_user BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'funcionario',
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT chk_user_role CHECK (role IN ('gestor', 'funcionario')),
    CONSTRAINT chk_user_active CHECK (active IN (0, 1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Clientes atendidos. Não acessam o sistema; apenas são vinculados aos serviços (RF04).
CREATE TABLE IF NOT EXISTS `client` (
    id_client BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    phone VARCHAR(20) NULL,
    email VARCHAR(100) NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT chk_client_active CHECK (active IN (0, 1)),
    INDEX idx_client_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Serviços. A situação é derivada de finished_at: NULL = pendente.
CREATE TABLE IF NOT EXISTS `service` (
    id_service BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    description VARCHAR(255) NOT NULL,
    price DECIMAL(11, 2) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    -- Dados gravados juntos, na mesma transação, no momento da finalização.
    finished_at DATETIME NULL,
    commission_user DECIMAL(11, 2) NULL,
    -- Percentual aplicado: mantém o histórico explicável se as faixas mudarem.
    commission_rate DECIMAL(5, 2) NULL,
    -- Quem finalizou (gestor ou responsável), para auditoria.
    finished_by BIGINT UNSIGNED NULL,

    user_id BIGINT UNSIGNED NOT NULL,
    client_id BIGINT UNSIGNED NOT NULL,

    CONSTRAINT fk_service_user
        FOREIGN KEY (user_id) REFERENCES `user` (id_user)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_service_client
        FOREIGN KEY (client_id) REFERENCES `client` (id_client)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_service_finished_by
        FOREIGN KEY (finished_by) REFERENCES `user` (id_user)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT chk_service_price CHECK (price > 0),

    -- Um serviço finalizado sempre tem comissão; um pendente nunca tem.
    CONSTRAINT chk_service_finish CHECK (
        (finished_at IS NULL AND commission_user IS NULL AND commission_rate IS NULL)
        OR (finished_at IS NOT NULL AND commission_user IS NOT NULL AND commission_rate IS NOT NULL)
    ),

    INDEX idx_service_user (user_id),
    INDEX idx_service_client (client_id),
    INDEX idx_service_created_at (created_at),
    INDEX idx_service_finished_at (finished_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
