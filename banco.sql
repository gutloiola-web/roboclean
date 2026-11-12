-- ============================================================================
-- PROJETO ESCOLAR: RoboClean - Tecnologia a serviço do meio ambiente
-- EVENTO: Feira de Ciências e Inovação "Síntese" (2026)
-- BANCO DE DADOS: MySQL / MariaDB (Compatível com XAMPP, Laragon e Hospedagens)
-- ============================================================================

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

CREATE DATABASE IF NOT EXISTS `roboclean_db`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `roboclean_db`;

-- ----------------------------------------------------------------------------
-- 1. Tabela de Membros da Equipe
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `membros` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(100) NOT NULL,
  `funcao` VARCHAR(150) NOT NULL,
  `foto` VARCHAR(255) DEFAULT NULL,
  `ordem` INT NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dados iniciais da equipe (Artur e Breno já cadastrados + espaço para os demais)
INSERT INTO `membros` (`id`, `nome`, `funcao`, `foto`, `ordem`) VALUES
(1, 'Artur', 'Construção LEGO SPIKE & Engenharia do Robô', 'assets/img/membro_artur.svg', 1),
(2, 'Breno', 'Programação, Controle Xbox & Sensores', 'assets/img/membro_breno.svg', 2),
(3, 'Integrante 3 (Editar no Admin)', 'Pesquisa Ambiental & Documentação', 'assets/img/membro_placeholder.svg', 3),
(4, 'Integrante 4 (Editar no Admin)', 'Testes Práticos & Apresentação Síntese', 'assets/img/membro_placeholder.svg', 4)
ON DUPLICATE KEY UPDATE
  `nome` = VALUES(`nome`),
  `funcao` = VALUES(`funcao`);

-- Para adicionar mais integrantes pelo SQL (ou use o painel em /admin):
-- INSERT INTO `membros` (`nome`, `funcao`, `foto`, `ordem`) VALUES
-- ('Nome do Colega', 'Função no Projeto', NULL, 5);

-- ----------------------------------------------------------------------------
-- 2. Tabela da Galeria (Fotos e Vídeos)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `galeria` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `titulo` VARCHAR(150) NOT NULL,
  `arquivo` VARCHAR(255) NOT NULL,
  `tipo` ENUM('foto', 'video') NOT NULL DEFAULT 'foto',
  `data` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Itens iniciais da galeria com fotos de robôs feitos em LEGO SPIKE (editáveis pelo /admin)
INSERT INTO `galeria` (`id`, `titulo`, `arquivo`, `tipo`, `data`) VALUES
(1, 'Robô Catador de Lixo construído com LEGO Education SPIKE', 'assets/img/spike_galeria_1.jpg', 'foto', NOW()),
(2, 'Controle de Xbox conectado via Bluetooth ao Hub LEGO SPIKE', 'assets/img/spike_galeria_2.jpg', 'foto', NOW()),
(3, 'Garra/Pá Coletora e Sensor de Distância em Testes Práticos', 'assets/img/spike_galeria_3.jpg', 'foto', NOW())
ON DUPLICATE KEY UPDATE
  `titulo` = VALUES(`titulo`),
  `arquivo` = VALUES(`arquivo`);

-- ----------------------------------------------------------------------------
-- 3. Tabela do Mural de Mensagens (Aprovação no Painel Admin)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `mensagens` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(80) NOT NULL,
  `texto` VARCHAR(500) NOT NULL,
  `aprovada` TINYINT(1) NOT NULL DEFAULT 0,
  `data` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mensagens iniciais aprovadas para recepcionar os visitantes da feira Síntese
INSERT INTO `mensagens` (`id`, `nome`, `texto`, `aprovada`, `data`) VALUES
(1, 'Equipe RoboClean', 'Bem-vindos à nossa apresentação na feira Síntese! Deixe seu comentário ou pergunta sobre o nosso robô catador de lixo.', 1, NOW()),
(2, 'Coordenação Pedagógica', 'Parabéns à equipe pela iniciativa de unir a robótica educacional LEGO SPIKE com a preservação do meio ambiente!', 1, NOW())
ON DUPLICATE KEY UPDATE
  `nome` = VALUES(`nome`),
  `texto` = VALUES(`texto`);

-- ----------------------------------------------------------------------------
-- 4. Tabela de Contador de Visitas
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `visitas` (
  `id` INT PRIMARY KEY,
  `total` INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `visitas` (`id`, `total`) VALUES (1, 1)
ON DUPLICATE KEY UPDATE `total` = `total`;

-- ----------------------------------------------------------------------------
-- 5. Tabela de Administradores
-- Usuário padrão: admin
-- Senha padrão:   roboclean2026
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `usuario` VARCHAR(60) NOT NULL UNIQUE,
  `senha_hash` VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `admins` (`id`, `usuario`, `senha_hash`) VALUES
(1, 'admin', '$2y$12$w8BhOQXDaEw2RXlYzTsIl.crNe6gjPAnrZtWKAiAeKtWHsb42yAHO')
ON DUPLICATE KEY UPDATE
  `usuario` = VALUES(`usuario`);

