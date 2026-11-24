-- Dados iniciais para avaliação. Importe depois do schema.sql.
-- Gestor:      gestor@avaliacao.com      / Gestor@2026
-- Funcionário: funcionario@avaliacao.com / Funcionario@2026
-- As datas são relativas ao dia da importação, para que o dashboard e o
-- relatório do mês atual já mostrem dados.

SET NAMES utf8mb4;

INSERT INTO `user` (name, email, password, role) VALUES
    ('Gestor de Avaliação', 'gestor@avaliacao.com',
     '$2y$10$MqNt88Z/1QbSHMj3M/ARgO31UhH5KjYpnlArqIpEUF0DB5dClS7CO', 'gestor'),
    ('Funcionário de Avaliação', 'funcionario@avaliacao.com',
     '$2y$10$rKPYnLVCTgP2IUIFQNHDSeMCrgqYsRiZNsmsPv1XLjh4ky0NMB/7m', 'funcionario');

SET @gestor = (SELECT id_user FROM `user` WHERE email = 'gestor@avaliacao.com');
SET @funcionario = (SELECT id_user FROM `user` WHERE email = 'funcionario@avaliacao.com');

INSERT INTO `client` (name, phone, email) VALUES
    ('Maria Souza', '(51) 99999-1111', 'maria@exemplo.com'),
    ('Oficina Bom Motor', '(51) 3333-2222', NULL),
    ('João Pereira', NULL, NULL),
    ('Escritório Contábil Lima', '(51) 3664-1020', 'contato@contabillima.com'),
    ('Clínica Sorriso', '(51) 3665-7788', 'adm@clinicasorriso.com');

SET @maria = (SELECT id_client FROM `client` WHERE name = 'Maria Souza');
SET @oficina = (SELECT id_client FROM `client` WHERE name = 'Oficina Bom Motor');
SET @joao = (SELECT id_client FROM `client` WHERE name = 'João Pereira');
SET @lima = (SELECT id_client FROM `client` WHERE name = 'Escritório Contábil Lima');
SET @clinica = (SELECT id_client FROM `client` WHERE name = 'Clínica Sorriso');

-- Serviços finalizados: a comissão segue as faixas do RF10.
INSERT INTO `service`
    (description, price, user_id, client_id, created_at, finished_at, commission_user, commission_rate, finished_by)
VALUES
    ('Formatação e backup de notebook', 350.00, @funcionario, @maria,
     NOW() - INTERVAL 6 DAY, NOW() - INTERVAL 5 DAY, 17.50, 5.00, @funcionario),
    ('Instalação de rede cabeada (8 pontos)', 2800.00, @funcionario, @lima,
     NOW() - INTERVAL 5 DAY, NOW() - INTERVAL 4 DAY, 280.00, 10.00, @gestor),
    ('Servidor de arquivos e rotina de backup', 12500.00, @gestor, @clinica,
     NOW() - INTERVAL 4 DAY, NOW() - INTERVAL 3 DAY, 2500.00, 20.00, @gestor);

-- Serviços pendentes.
INSERT INTO `service` (description, price, user_id, client_id, created_at) VALUES
    ('Troca de SSD e migração de sistema', 690.00, @funcionario, @oficina, NOW() - INTERVAL 3 DAY),
    ('Câmeras de segurança (4 câmeras)', 4300.00, @funcionario, @clinica, NOW() - INTERVAL 2 DAY),
    ('Remoção de vírus e atualização', 180.00, @gestor, @joao, NOW() - INTERVAL 1 DAY),
    ('Suporte remoto mensal', 900.00, @funcionario, @lima, NOW());
