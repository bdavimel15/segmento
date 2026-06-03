-- =========================================================
-- SQL LIMPO - MOTOR DE SEGMENTAÇÃO DE CLIENTES COM IA
-- Gerado para MySQL 5.7+
-- Objetivo: criar um banco de teste, tabelas principais mínimas e o motor novo.
-- IMPORTANTE: não apaga dados existentes. Usa CREATE TABLE IF NOT EXISTS.
-- =========================================================

CREATE DATABASE IF NOT EXISTS `segmentador_clientes`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `segmentador_clientes`;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =========================================================
-- 1) TABELAS BASE MÍNIMAS DO SISTEMA ATUAL
-- Só cria se ainda não existirem.
-- Se você já tiver essas tabelas no banco real, elas serão mantidas.
-- =========================================================

CREATE TABLE IF NOT EXISTS `contato_grupo` (
  `contato_grupo_id` INT(11) NOT NULL AUTO_INCREMENT,
  `cog_nome` VARCHAR(100) NOT NULL DEFAULT '',
  `cog_qtd` VARCHAR(50) DEFAULT NULL,
  `cog_sql` TEXT DEFAULT NULL,
  `cog_permitir_programar` ENUM('S','N') DEFAULT 'N',
  `cog_campanha` ENUM('S','N') DEFAULT 'S',
  `excluido` TIMESTAMP NULL DEFAULT NULL,
  `cadastrado` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `coq_atualizacao_contador` DATE DEFAULT NULL,
  PRIMARY KEY (`contato_grupo_id`),
  KEY `idx_contato_grupo_nome` (`cog_nome`),
  KEY `idx_contato_grupo_excluido` (`excluido`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cliente` (
  `cliente_id` INT(11) NOT NULL AUTO_INCREMENT,
  `cli_nome` VARCHAR(255) NOT NULL DEFAULT '',
  `cli_cpf` VARCHAR(20) DEFAULT NULL,
  `sexo_id` CHAR(1) DEFAULT NULL,
  `cli_telefone` VARCHAR(30) NOT NULL DEFAULT '',
  `cli_email` VARCHAR(100) DEFAULT NULL,
  `cli_data_nascimento` DATE DEFAULT NULL,
  `cli_qtd_pedidos` INT(11) NOT NULL DEFAULT 0,
  `cli_proxima_compra` DATETIME DEFAULT NULL,
  `cli_newsletter` ENUM('S','N') DEFAULT 'S',
  `cliente_origem_id` INT(11) DEFAULT NULL,
  `excluido` TIMESTAMP NULL DEFAULT NULL,
  `cadastrado` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`cliente_id`),
  KEY `idx_cliente_excluido` (`excluido`),
  KEY `idx_cliente_telefone` (`cli_telefone`),
  KEY `idx_cliente_cadastrado` (`cadastrado`),
  KEY `idx_cliente_qtd_pedidos` (`cli_qtd_pedidos`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `contato` (
  `contato_id` INT(11) NOT NULL AUTO_INCREMENT,
  `con_nome` VARCHAR(255) DEFAULT NULL,
  `con_celular` VARCHAR(255) NOT NULL DEFAULT '',
  `contato_origem_id` INT(11) DEFAULT 1,
  `excluido` TIMESTAMP NULL DEFAULT NULL,
  `cadastrado` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`contato_id`),
  UNIQUE KEY `unico_contato_celular` (`con_celular`),
  KEY `idx_contato_excluido` (`excluido`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `status` (
  `status_id` INT(11) NOT NULL AUTO_INCREMENT,
  `sta_nome` VARCHAR(50) NOT NULL DEFAULT '',
  `sta_confirmado` ENUM('S','N') DEFAULT 'S',
  `sta_ativo` ENUM('S','N') DEFAULT 'S',
  `excluido` TIMESTAMP NULL DEFAULT NULL,
  `cadastrado` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`status_id`),
  KEY `idx_status_confirmado` (`sta_confirmado`),
  KEY `idx_status_excluido` (`excluido`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `pedido` (
  `pedido_id` INT(11) NOT NULL AUTO_INCREMENT,
  `ped_numero` VARCHAR(20) DEFAULT NULL,
  `ped_data` DATETIME DEFAULT NULL,
  `cliente_id` INT(11) DEFAULT NULL,
  `estabelecimento_id` INT(11) DEFAULT NULL,
  `status_id` INT(11) DEFAULT NULL,
  `ped_valor_total` DECIMAL(10,2) DEFAULT NULL,
  `excluido` TIMESTAMP NULL DEFAULT NULL,
  `cadastrado` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`pedido_id`),
  KEY `idx_pedido_cliente` (`cliente_id`),
  KEY `idx_pedido_status` (`status_id`),
  KEY `idx_pedido_data` (`ped_data`),
  KEY `idx_pedido_excluido` (`excluido`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `pedido_status` (
  `pedido_status_id` INT(11) NOT NULL AUTO_INCREMENT,
  `pedido_id` INT(11) DEFAULT NULL,
  `status_id` INT(11) DEFAULT NULL,
  `excluido` TIMESTAMP NULL DEFAULT NULL,
  `cadastrado` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`pedido_status_id`),
  KEY `idx_pedido_status_pedido` (`pedido_id`),
  KEY `idx_pedido_status_status` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `pedido_item` (
  `pedido_item_id` INT(11) NOT NULL AUTO_INCREMENT,
  `produto_id` INT(11) DEFAULT NULL,
  `pedido_id` INT(11) DEFAULT NULL,
  `pei_quantidade` DECIMAL(10,3) DEFAULT NULL,
  `pei_valor_unitario` DECIMAL(10,2) DEFAULT NULL,
  `pei_valor_total` DECIMAL(10,2) DEFAULT NULL,
  `pei_descricao` VARCHAR(2000) DEFAULT '',
  `excluido` TIMESTAMP NULL DEFAULT NULL,
  `cadastrado` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`pedido_item_id`),
  KEY `idx_pedido_item_pedido` (`pedido_id`),
  KEY `idx_pedido_item_produto` (`produto_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cashback` (
  `cashback_id` INT(11) NOT NULL AUTO_INCREMENT,
  `cliente_id` INT(11) DEFAULT NULL,
  `pedido_id` INT(11) DEFAULT NULL,
  `cas_valor` DECIMAL(10,2) DEFAULT NULL,
  `cas_complemento` VARCHAR(255) DEFAULT NULL,
  `excluido` TIMESTAMP NULL DEFAULT NULL,
  `cadastrado` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`cashback_id`),
  KEY `idx_cashback_cliente` (`cliente_id`),
  KEY `idx_cashback_excluido` (`excluido`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cashback_configuracao` (
  `cashback_configuracao_id` INT(11) NOT NULL AUTO_INCREMENT,
  `cac_percentual` INT(2) DEFAULT NULL,
  `cac_ativo` ENUM('S','N') DEFAULT 'N',
  `cac_tempo_expiracao` INT(11) DEFAULT NULL,
  `cac_tempo_geracao` INT(11) DEFAULT 2,
  `excluido` DATETIME DEFAULT NULL,
  `cadastrado` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`cashback_configuracao_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `notificacao_programada_envio` (
  `notificacao_programada_envio_id` INT(11) NOT NULL AUTO_INCREMENT,
  `notificacao_programada_id` INT(11) DEFAULT NULL,
  `cliente_id` INT(11) DEFAULT NULL,
  `noe_numero` VARCHAR(20) DEFAULT NULL,
  `noe_mensagem` TEXT DEFAULT NULL,
  `noe_status` ENUM('Pendente','Falha','Sucesso') DEFAULT 'Pendente',
  `excluido` TIMESTAMP NULL DEFAULT NULL,
  `cadastrado` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`notificacao_programada_envio_id`),
  KEY `idx_notificacao_cliente` (`cliente_id`),
  KEY `idx_notificacao_cadastrado` (`cadastrado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `estabelecimento` (
  `estabelecimento_id` INT(11) NOT NULL AUTO_INCREMENT,
  `est_nome` VARCHAR(100) NOT NULL DEFAULT '',
  `est_ativo` ENUM('S','N') DEFAULT 'S',
  `excluido` TIMESTAMP NULL DEFAULT NULL,
  `cadastrado` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`estabelecimento_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- View base para campanhas. Recria para garantir consistência no banco de teste.
DROP VIEW IF EXISTS `view_cliente_contato`;
CREATE VIEW `view_cliente_contato` AS
SELECT
  c.cliente_id AS contato_id,
  c.cli_nome AS con_nome,
  c.cli_telefone AS con_celular,
  c.cli_email AS con_email,
  c.cliente_id AS cliente_id,
  c.cli_qtd_pedidos AS con_qtd_pedidos,
  CAST(CONCAT(YEAR(CURDATE()), '-', DATE_FORMAT(c.cli_data_nascimento, '%m-%d')) AS DATE) AS con_data_aniversario,
  c.cli_proxima_compra AS cli_proxima_compra,
  CAST(COALESCE(cb.total_cashback, 0) AS DECIMAL(10,2)) AS cli_cashback_disponivel
FROM cliente c
LEFT JOIN (
  SELECT cliente_id, SUM(cas_valor) AS total_cashback
  FROM cashback
  WHERE excluido IS NULL
  GROUP BY cliente_id
) cb ON cb.cliente_id = c.cliente_id
WHERE c.excluido IS NULL
  AND c.cli_telefone IS NOT NULL
  AND c.cli_telefone <> ''
  AND c.cli_newsletter = 'S';

-- =========================================================
-- 2) MOTOR NOVO DE SEGMENTAÇÃO
-- JSON = regra oficial.
-- SQL gerada = apenas preview/execução/auditoria.
-- =========================================================

CREATE TABLE IF NOT EXISTS `segmento_cliente` (
  `segmento_cliente_id` INT(11) NOT NULL AUTO_INCREMENT,
  `nome` VARCHAR(120) NOT NULL,
  `descricao` VARCHAR(255) DEFAULT NULL,

  `tipo` ENUM('dinamico','manual','importado','sql_legado') DEFAULT 'dinamico',
  `origem` ENUM('manual','ia','preset','legado') DEFAULT 'manual',

  `regra_json` JSON DEFAULT NULL,
  `resumo_humano` TEXT DEFAULT NULL,

  `status_validacao` ENUM('rascunho','pendente_validacao','validada','reprovada','inativa','erro') DEFAULT 'rascunho',
  `motivo_reprovacao` TEXT DEFAULT NULL,

  `limite` INT(11) DEFAULT 25,
  `ordenacao` ENUM('aleatoria','mais_recentes','mais_antigos','maior_valor','menor_valor','ultima_compra_desc','ultima_compra_asc') DEFAULT 'aleatoria',

  `permitir_email` ENUM('S','N') DEFAULT 'S',
  `permitir_sms` ENUM('S','N') DEFAULT 'S',
  `permitir_whatsapp` ENUM('S','N') DEFAULT 'S',
  `permitir_push` ENUM('S','N') DEFAULT 'S',

  `ultima_previa_qtd` INT(11) DEFAULT NULL,
  `ultima_previa_em` DATETIME DEFAULT NULL,

  `validado_por` INT(11) DEFAULT NULL,
  `validado_em` DATETIME DEFAULT NULL,

  `contato_grupo_id_legado` INT(11) DEFAULT NULL,

  `excluido` TIMESTAMP NULL DEFAULT NULL,
  `cadastrado` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`segmento_cliente_id`),
  KEY `idx_segmento_status` (`status_validacao`),
  KEY `idx_segmento_tipo_origem` (`tipo`,`origem`),
  KEY `idx_segmento_legado` (`contato_grupo_id_legado`),
  KEY `idx_segmento_excluido` (`excluido`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `segmento_cliente_execucao` (
  `segmento_cliente_execucao_id` INT(11) NOT NULL AUTO_INCREMENT,
  `segmento_cliente_id` INT(11) NOT NULL,

  `canal` ENUM('email','sms','whatsapp','push','preview','exportacao') DEFAULT 'preview',

  `regra_json_snapshot` JSON NOT NULL,
  `sql_gerada_snapshot` LONGTEXT DEFAULT NULL,

  `total_encontrado` INT(11) DEFAULT 0,
  `total_processado` INT(11) DEFAULT 0,
  `total_enviado` INT(11) DEFAULT 0,

  `status` ENUM('pendente','executando','concluida','erro','cancelada') DEFAULT 'pendente',
  `erro` TEXT DEFAULT NULL,

  `executado_por` INT(11) DEFAULT NULL,
  `executado_em` DATETIME DEFAULT NULL,

  `cadastrado` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`segmento_cliente_execucao_id`),
  KEY `idx_execucao_segmento` (`segmento_cliente_id`),
  KEY `idx_execucao_status` (`status`),
  KEY `idx_execucao_canal` (`canal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `segmento_cliente_campo` (
  `segmento_cliente_campo_id` INT(11) NOT NULL AUTO_INCREMENT,

  `chave` VARCHAR(80) NOT NULL,
  `label` VARCHAR(120) NOT NULL,
  `descricao` VARCHAR(255) DEFAULT NULL,

  `categoria` ENUM('cliente','contato','pedido','produto','cashback','carrinho','notificacao','endereco','sistema') NOT NULL,
  `tipo_valor` ENUM('string','number','date','datetime','boolean','money','select') NOT NULL,

  `origem_tabela` VARCHAR(120) DEFAULT NULL,
  `origem_coluna` VARCHAR(120) DEFAULT NULL,
  `expressao_sql` TEXT DEFAULT NULL,

  `operadores_json` JSON NOT NULL,

  `ativo` ENUM('S','N') DEFAULT 'S',
  `ordem` INT(11) DEFAULT 0,

  `cadastrado` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`segmento_cliente_campo_id`),
  UNIQUE KEY `uk_segmento_campo_chave` (`chave`),
  KEY `idx_campo_categoria` (`categoria`),
  KEY `idx_campo_ativo` (`ativo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `segmento_cliente_validacao` (
  `segmento_cliente_validacao_id` INT(11) NOT NULL AUTO_INCREMENT,
  `segmento_cliente_id` INT(11) NOT NULL,

  `status_anterior` VARCHAR(50) DEFAULT NULL,
  `status_novo` VARCHAR(50) NOT NULL,

  `regra_json_snapshot` JSON DEFAULT NULL,
  `resumo_humano_snapshot` TEXT DEFAULT NULL,

  `observacao` TEXT DEFAULT NULL,
  `validado_por` INT(11) DEFAULT NULL,

  `cadastrado` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`segmento_cliente_validacao_id`),
  KEY `idx_validacao_segmento` (`segmento_cliente_id`),
  KEY `idx_validacao_status` (`status_novo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `segmento_cliente_preset` (
  `segmento_cliente_preset_id` INT(11) NOT NULL AUTO_INCREMENT,

  `nome` VARCHAR(120) NOT NULL,
  `descricao` VARCHAR(255) DEFAULT NULL,
  `categoria` VARCHAR(80) DEFAULT NULL,

  `regra_json` JSON NOT NULL,
  `ativo` ENUM('S','N') DEFAULT 'S',

  `ordem` INT(11) DEFAULT 0,
  `cadastrado` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`segmento_cliente_preset_id`),
  UNIQUE KEY `uk_preset_nome` (`nome`),
  KEY `idx_preset_categoria` (`categoria`),
  KEY `idx_preset_ativo` (`ativo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- 3) CATÁLOGO DE CAMPOS PERMITIDOS
-- A IA só pode devolver campos que existam aqui.
-- =========================================================

INSERT INTO `segmento_cliente_campo`
(`chave`, `label`, `descricao`, `categoria`, `tipo_valor`, `origem_tabela`, `origem_coluna`, `expressao_sql`, `operadores_json`, `ativo`, `ordem`)
VALUES
('nome_cliente','Nome do cliente','Nome cadastrado do cliente','cliente','string','cliente','cli_nome','cliente.cli_nome',
 JSON_ARRAY('contains','not_contains','starts_with','ends_with','is_empty','is_not_empty'),'S',10),

('telefone_cliente','Telefone do cliente','Telefone/celular do cliente','cliente','string','cliente','cli_telefone','cliente.cli_telefone',
 JSON_ARRAY('contains','not_contains','starts_with','ends_with','is_empty','is_not_empty'),'S',20),

('email_cliente','E-mail do cliente','E-mail cadastrado do cliente','cliente','string','cliente','cli_email','cliente.cli_email',
 JSON_ARRAY('contains','not_contains','starts_with','ends_with','is_empty','is_not_empty'),'S',30),

('data_cadastro','Data de cadastro','Data em que o cliente foi cadastrado','cliente','datetime','cliente','cadastrado','cliente.cadastrado',
 JSON_ARRAY('today','equals_date','before_date','after_date','between_dates','last_x_days','more_than_x_days_ago','less_than_x_days_ago'),'S',40),

('aniversario','Aniversário','Aniversário calculado pelo mês e dia do nascimento','cliente','date','cliente','cli_data_nascimento','view_cliente_contato.con_data_aniversario',
 JSON_ARRAY('today','equals_date','next_x_days','between_dates'),'S',50),

('qtd_pedidos','Quantidade de pedidos','Quantidade total de pedidos gravada no cliente','pedido','number','cliente','cli_qtd_pedidos','cliente.cli_qtd_pedidos',
 JSON_ARRAY('equals','not_equals','greater_than','greater_or_equal','less_than','less_or_equal','between'),'S',60),

('qtd_pedidos_confirmados','Quantidade de pedidos confirmados','Quantidade de pedidos confirmados, considerando status.sta_confirmado = S e pedido.excluido IS NULL','pedido','number','pedido','pedido_id',
 '(SELECT COUNT(*) FROM pedido p INNER JOIN status s ON s.status_id = p.status_id WHERE p.cliente_id = cliente.cliente_id AND p.excluido IS NULL AND s.sta_confirmado = ''S'')',
 JSON_ARRAY('equals','not_equals','greater_than','greater_or_equal','less_than','less_or_equal','between'),'S',70),

('ultima_compra','Última compra','Data da última compra confirmada do cliente','pedido','datetime','pedido','ped_data',
 '(SELECT MAX(p.ped_data) FROM pedido p INNER JOIN status s ON s.status_id = p.status_id WHERE p.cliente_id = cliente.cliente_id AND p.excluido IS NULL AND s.sta_confirmado = ''S'')',
 JSON_ARRAY('equals_date','before_date','after_date','between_dates','last_x_days','exactly_x_days_ago','more_than_x_days_ago','less_than_x_days_ago','is_empty','is_not_empty'),'S',80),

('primeira_compra','Primeira compra','Data da primeira compra confirmada do cliente','pedido','datetime','pedido','ped_data',
 '(SELECT MIN(p.ped_data) FROM pedido p INNER JOIN status s ON s.status_id = p.status_id WHERE p.cliente_id = cliente.cliente_id AND p.excluido IS NULL AND s.sta_confirmado = ''S'')',
 JSON_ARRAY('equals_date','before_date','after_date','between_dates','last_x_days','more_than_x_days_ago','less_than_x_days_ago','is_empty','is_not_empty'),'S',90),

('valor_total_compras','Valor total comprado','Soma dos valores de pedidos confirmados','pedido','money','pedido','ped_valor_total',
 '(SELECT COALESCE(SUM(p.ped_valor_total),0) FROM pedido p INNER JOIN status s ON s.status_id = p.status_id WHERE p.cliente_id = cliente.cliente_id AND p.excluido IS NULL AND s.sta_confirmado = ''S'')',
 JSON_ARRAY('equals','not_equals','greater_than','greater_or_equal','less_than','less_or_equal','between'),'S',100),

('cashback_saldo','Saldo de cashback','Saldo total de cashback disponível do cliente','cashback','money','cashback','cas_valor',
 '(SELECT COALESCE(SUM(cas_valor),0) FROM cashback cb WHERE cb.cliente_id = cliente.cliente_id AND cb.excluido IS NULL)',
 JSON_ARRAY('equals','not_equals','greater_than','greater_or_equal','less_than','less_or_equal','between'),'S',110),

('proxima_compra','Próxima compra prevista','Data prevista para próxima compra do cliente','cliente','datetime','cliente','cli_proxima_compra','cliente.cli_proxima_compra',
 JSON_ARRAY('equals_date','before_date','after_date','between_dates','next_x_days','last_x_days','is_empty','is_not_empty'),'S',120),

('recebeu_notificacao_nos_ultimos_dias','Recebeu notificação recentemente','Verifica se o cliente recebeu notificação nos últimos X dias','notificacao','number','notificacao_programada_envio','cadastrado',
 '(SELECT COUNT(*) FROM notificacao_programada_envio npe WHERE npe.cliente_id = cliente.cliente_id AND npe.excluido IS NULL AND npe.cadastrado >= DATE_SUB(NOW(), INTERVAL ? DAY))',
 JSON_ARRAY('exists','not_exists'),'S',130),

('produto_comprado','Produto comprado','Verifica se o cliente comprou produto específico','produto','number','pedido_item','produto_id',
 '(SELECT COUNT(*) FROM pedido_item pi INNER JOIN pedido p ON p.pedido_id = pi.pedido_id INNER JOIN status s ON s.status_id = p.status_id WHERE p.cliente_id = cliente.cliente_id AND pi.excluido IS NULL AND p.excluido IS NULL AND s.sta_confirmado = ''S'' AND pi.produto_id = ?)',
 JSON_ARRAY('exists','not_exists'),'S',140)
ON DUPLICATE KEY UPDATE
  `label` = VALUES(`label`),
  `descricao` = VALUES(`descricao`),
  `categoria` = VALUES(`categoria`),
  `tipo_valor` = VALUES(`tipo_valor`),
  `origem_tabela` = VALUES(`origem_tabela`),
  `origem_coluna` = VALUES(`origem_coluna`),
  `expressao_sql` = VALUES(`expressao_sql`),
  `operadores_json` = VALUES(`operadores_json`),
  `ativo` = VALUES(`ativo`),
  `ordem` = VALUES(`ordem`),
  `atualizado` = CURRENT_TIMESTAMP;

-- =========================================================
-- 4) PRESETS INICIAIS
-- =========================================================

INSERT INTO `segmento_cliente_preset`
(`nome`, `descricao`, `categoria`, `regra_json`, `ativo`, `ordem`)
VALUES
('Aniversariantes do dia','Clientes que fazem aniversário hoje','Aniversário',
 JSON_OBJECT('version',1,'entity','cliente','logic','AND','conditions',JSON_ARRAY(JSON_OBJECT('field','aniversario','operator','today','value',NULL)),'limit',25,'order',JSON_OBJECT('field','random','direction','asc')),
 'S',10),

('Clientes sem compra há 30 dias','Clientes com última compra há mais de 30 dias','Recência',
 JSON_OBJECT('version',1,'entity','cliente','logic','AND','conditions',JSON_ARRAY(JSON_OBJECT('field','ultima_compra','operator','more_than_x_days_ago','value',30)),'limit',25,'order',JSON_OBJECT('field','random','direction','asc')),
 'S',20),

('Clientes com mais de 2 pedidos','Clientes com pelo menos 3 pedidos confirmados','Pedidos',
 JSON_OBJECT('version',1,'entity','cliente','logic','AND','conditions',JSON_ARRAY(JSON_OBJECT('field','qtd_pedidos_confirmados','operator','greater_than','value',2)),'limit',25,'order',JSON_OBJECT('field','random','direction','asc')),
 'S',30),

('Clientes com cashback','Clientes com saldo de cashback maior que zero','Cashback',
 JSON_OBJECT('version',1,'entity','cliente','logic','AND','conditions',JSON_ARRAY(JSON_OBJECT('field','cashback_saldo','operator','greater_than','value',0)),'limit',25,'order',JSON_OBJECT('field','random','direction','asc')),
 'S',40),

('Clientes cadastrados sem pedido','Clientes cadastrados e sem pedidos confirmados','Cadastro',
 JSON_OBJECT('version',1,'entity','cliente','logic','AND','conditions',JSON_ARRAY(JSON_OBJECT('field','qtd_pedidos_confirmados','operator','equals','value',0)),'limit',25,'order',JSON_OBJECT('field','mais_recentes','direction','desc')),
 'S',50)
ON DUPLICATE KEY UPDATE
  `descricao` = VALUES(`descricao`),
  `categoria` = VALUES(`categoria`),
  `regra_json` = VALUES(`regra_json`),
  `ativo` = VALUES(`ativo`),
  `ordem` = VALUES(`ordem`),
  `atualizado` = CURRENT_TIMESTAMP;

SET FOREIGN_KEY_CHECKS = 1;

-- Fim.
