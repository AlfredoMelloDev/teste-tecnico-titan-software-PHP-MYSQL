-- Migração de um banco criado com o esquema antigo (teste técnico) para o esquema v2.
-- Execute uma única vez, com o banco selecionado. Faça uma cópia de segurança antes.
-- Requer MySQL 8.0.16+ (ou MariaDB 10.5+).

SET NAMES utf8mb4;

-- 1) Usuários: nomenclatura padronizada e perfil.
ALTER TABLE `user`
    RENAME COLUMN ativo TO active,
    RENAME COLUMN update_at TO updated_at;

ALTER TABLE `user`
    ADD COLUMN role VARCHAR(20) NOT NULL DEFAULT 'funcionario' AFTER password,
    ADD CONSTRAINT chk_user_role CHECK (role IN ('gestor', 'funcionario'));

-- O primeiro usuário cadastrado passa a ser o gestor, para que sempre exista um.
UPDATE `user` SET role = 'gestor' ORDER BY id_user LIMIT 1;

-- 2) Clientes.
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

-- Os serviços antigos não tinham cliente: recebem um cliente genérico, já inativo.
INSERT INTO `client` (name, active) VALUES ('Cliente não informado (base anterior)', 0);
SET @legacy_client = LAST_INSERT_ID();

-- 3) Serviços: nomenclatura, cliente e dados de auditoria da finalização.
ALTER TABLE `service` DROP FOREIGN KEY fk_service_user;

ALTER TABLE `service`
    RENAME COLUMN update_at TO updated_at,
    RENAME COLUMN user_id_user TO user_id;

ALTER TABLE `service`
    ADD COLUMN commission_rate DECIMAL(5, 2) NULL AFTER commission_user,
    ADD COLUMN finished_by BIGINT UNSIGNED NULL AFTER commission_rate,
    ADD COLUMN client_id BIGINT UNSIGNED NULL AFTER user_id;

UPDATE `service` SET client_id = @legacy_client;

-- Percentual dos serviços já finalizados, deduzido das faixas do RF10.
UPDATE `service`
SET commission_rate = CASE
        WHEN price > 10000.00 THEN 20.00
        WHEN price > 1000.00 THEN 10.00
        ELSE 5.00
    END
WHERE finished_at IS NOT NULL;

ALTER TABLE `service`
    MODIFY client_id BIGINT UNSIGNED NOT NULL,
    ADD CONSTRAINT fk_service_user
        FOREIGN KEY (user_id) REFERENCES `user` (id_user)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    ADD CONSTRAINT fk_service_client
        FOREIGN KEY (client_id) REFERENCES `client` (id_client)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    ADD CONSTRAINT fk_service_finished_by
        FOREIGN KEY (finished_by) REFERENCES `user` (id_user)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    ADD CONSTRAINT chk_service_price CHECK (price > 0),
    ADD CONSTRAINT chk_service_finish CHECK (
        (finished_at IS NULL AND commission_user IS NULL AND commission_rate IS NULL)
        OR (finished_at IS NOT NULL AND commission_user IS NOT NULL AND commission_rate IS NOT NULL)
    ),
    ADD INDEX idx_service_client (client_id);
