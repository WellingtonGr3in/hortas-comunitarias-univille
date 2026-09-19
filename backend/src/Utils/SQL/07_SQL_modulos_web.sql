-- Criação da tabela dependentes
CREATE TABLE IF NOT EXISTS `dependentes` (
    `uuid` CHAR(36) NOT NULL PRIMARY KEY,
    `nome` VARCHAR(255) NOT NULL,
    `cpf` VARCHAR(14) NOT NULL,
    `idade` INT NOT NULL,
    `ativo` TINYINT(1) NOT NULL DEFAULT 1,
    `carteirista_uuid` CHAR(36) NULL,
    `excluido` TINYINT(1) NOT NULL DEFAULT 0,
    `usuario_criador_uuid` CHAR(36) NULL,
    `usuario_alterador_uuid` CHAR(36) NULL,
    `data_de_criacao` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `data_de_ultima_alteracao` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_excluido` (`excluido`),
    INDEX `idx_carteirista` (`carteirista_uuid`),
    INDEX `idx_usuario_criador` (`usuario_criador_uuid`),
    INDEX `idx_usuario_alterador` (`usuario_alterador_uuid`),
    CONSTRAINT `fk_dependente_carteirista` FOREIGN KEY (`carteirista_uuid`) REFERENCES `canteiristas` (`uuid`) ON DELETE SET NULL,
    CONSTRAINT `fk_dependente_criador` FOREIGN KEY (`usuario_criador_uuid`) REFERENCES `usuarios` (`uuid`) ON DELETE SET NULL,
    CONSTRAINT `fk_dependente_alterador` FOREIGN KEY (`usuario_alterador_uuid`) REFERENCES `usuarios` (`uuid`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `pagamentos` (
  `uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `carteirista_uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `forma_pagamento` enum('dinheiro','pix') COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_pagamento` date NOT NULL,
  `observacao` text COLLATE utf8mb4_unicode_ci,
  `excluido` tinyint NOT NULL DEFAULT '0',
  `usuario_criador_uuid` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_de_criacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `usuario_alterador_uuid` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_de_ultima_alteracao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`uuid`),
  KEY `idx_carteirista` (`carteirista_uuid`),
  KEY `idx_excluido` (`excluido`),
  CONSTRAINT `pagamentos_ibfk_1` FOREIGN KEY (`carteirista_uuid`) REFERENCES `canteiristas` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabela de pagamentos dos carteiristas';


CREATE TABLE IF NOT EXISTS `notificacoes` (
  `uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo` enum('aviso_geral','aviso_canteirista','evento_horta') COLLATE utf8mb4_unicode_ci NOT NULL,
  `titulo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mensagem` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `carteirista_uuid` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'UUID do carteirista específico (apenas para tipo aviso_canteirista)',
  `horta_uuid` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'UUID da horta relacionada ao evento',
  `data_evento` datetime DEFAULT NULL COMMENT 'Data do evento (apenas para tipo evento_horta)',
  `data_inicio` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Data de início da exibição',
  `data_fim` datetime DEFAULT NULL COMMENT 'Data de fim da exibição (NULL = sem prazo)',
  `ativa` tinyint NOT NULL DEFAULT '1' COMMENT '1 = ativa, 0 = inativa',
  `prioridade` enum('baixa','media','alta') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'media',
  `excluido` tinyint NOT NULL DEFAULT '0',
  `usuario_criador_uuid` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_de_criacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `usuario_alterador_uuid` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_de_ultima_alteracao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`uuid`),
  KEY `idx_tipo` (`tipo`),
  KEY `idx_ativa` (`ativa`),
  KEY `idx_excluido` (`excluido`),
  KEY `idx_carteirista` (`carteirista_uuid`),
  KEY `idx_horta` (`horta_uuid`),
  KEY `idx_data_inicio` (`data_inicio`),
  KEY `idx_data_fim` (`data_fim`),
  CONSTRAINT `notificacoes_ibfk_1` FOREIGN KEY (`carteirista_uuid`) REFERENCES `canteiristas` (`uuid`),
  CONSTRAINT `notificacoes_ibfk_2` FOREIGN KEY (`horta_uuid`) REFERENCES `hortas` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabela de notificações e avisos para canteiristas';
