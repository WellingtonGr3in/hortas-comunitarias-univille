-- Inventário de insumos e ferramentas.
-- Cada item pertence a uma associação OU a uma horta, nunca às duas (mesma regra de categorias_financeiras).
-- A quantidade do item só muda por movimentação; cada movimentação guarda o saldo antes e depois.
-- Sem CHARSET/COLLATE explícito para herdar a configuração do banco, como as tabelas da 00.
CREATE TABLE IF NOT EXISTS itens_do_inventario (
    uuid CHAR(36) NOT NULL,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    tipo ENUM('insumo', 'ferramenta') NOT NULL,
    unidade_de_medida VARCHAR(20) NOT NULL,
    quantidade DECIMAL(12,3) NOT NULL DEFAULT 0,
    quantidade_minima DECIMAL(12,3),
    associacao_uuid CHAR(36),
    horta_uuid CHAR(36),
    excluido TINYINT DEFAULT 0,
    usuario_criador_uuid CHAR(36) NOT NULL,
    data_de_criacao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usuario_alterador_uuid CHAR(36),
    data_de_ultima_alteracao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (uuid),
    KEY idx_itens_do_inventario_associacao (associacao_uuid),
    KEY idx_itens_do_inventario_horta (horta_uuid),
    CONSTRAINT chk_item_associacao_xor_horta CHECK ((associacao_uuid IS NULL) <> (horta_uuid IS NULL)),
    CONSTRAINT chk_item_quantidade_nao_negativa CHECK (quantidade >= 0),
    CONSTRAINT fk_item_inventario_associacao FOREIGN KEY (associacao_uuid) REFERENCES associacoes (uuid),
    CONSTRAINT fk_item_inventario_horta FOREIGN KEY (horta_uuid) REFERENCES hortas (uuid),
    CONSTRAINT fk_item_inventario_criador FOREIGN KEY (usuario_criador_uuid) REFERENCES usuarios (uuid),
    CONSTRAINT fk_item_inventario_alterador FOREIGN KEY (usuario_alterador_uuid) REFERENCES usuarios (uuid)
) ENGINE=InnoDB;

-- Movimentações não são editadas nem excluídas: um erro se corrige com uma nova movimentação (ajuste).
CREATE TABLE IF NOT EXISTS movimentacoes_do_inventario (
    uuid CHAR(36) NOT NULL,
    item_uuid CHAR(36) NOT NULL,
    tipo ENUM('entrada', 'saida', 'ajuste') NOT NULL,
    quantidade DECIMAL(12,3) NOT NULL,
    quantidade_anterior DECIMAL(12,3) NOT NULL,
    quantidade_posterior DECIMAL(12,3) NOT NULL,
    observacao TEXT,
    usuario_criador_uuid CHAR(36) NOT NULL,
    data_de_criacao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (uuid),
    KEY idx_movimentacoes_do_inventario_item (item_uuid, data_de_criacao),
    CONSTRAINT fk_movimentacao_inventario_item FOREIGN KEY (item_uuid) REFERENCES itens_do_inventario (uuid),
    CONSTRAINT fk_movimentacao_inventario_criador FOREIGN KEY (usuario_criador_uuid) REFERENCES usuarios (uuid)
) ENGINE=InnoDB;

-- Permissões (módulos 22 e 23 do enum Modulos; 19 a 21 já são usados no seed sem constar no enum).
INSERT INTO permissoes (uuid, slug, tipo, descricao, modulo, excluido, usuario_criador_uuid, data_de_criacao, usuario_alterador_uuid, data_de_ultima_alteracao) VALUES
(UUID(), 'itens_do_inventario_get', 0, 'Listar itens do inventário', 22, 0, NULL, NOW(), NULL, NOW()),
(UUID(), 'itens_do_inventario_get_uuid', 0, 'Buscar item do inventário por UUID', 22, 0, NULL, NOW(), NULL, NOW()),
(UUID(), 'itens_do_inventario_get_associacao', 0, 'Listar itens do inventário por associação', 22, 0, NULL, NOW(), NULL, NOW()),
(UUID(), 'itens_do_inventario_get_horta', 0, 'Listar itens do inventário por horta', 22, 0, NULL, NOW(), NULL, NOW()),
(UUID(), 'itens_do_inventario_post', 1, 'Criar item do inventário', 22, 0, NULL, NOW(), NULL, NOW()),
(UUID(), 'itens_do_inventario_put', 2, 'Editar item do inventário', 22, 0, NULL, NOW(), NULL, NOW()),
(UUID(), 'itens_do_inventario_delete', 3, 'Deletar item do inventário', 22, 0, NULL, NOW(), NULL, NOW()),
(UUID(), 'movimentacoes_do_inventario_get', 0, 'Listar movimentações do inventário', 23, 0, NULL, NOW(), NULL, NOW()),
(UUID(), 'movimentacoes_do_inventario_get_uuid', 0, 'Buscar movimentação do inventário por UUID', 23, 0, NULL, NOW(), NULL, NOW()),
(UUID(), 'movimentacoes_do_inventario_get_item', 0, 'Listar movimentações de um item do inventário', 23, 0, NULL, NOW(), NULL, NOW()),
(UUID(), 'movimentacoes_do_inventario_post', 1, 'Registrar movimentação do inventário', 23, 0, NULL, NOW(), NULL, NOW());

-- Admin da plataforma e admin da associação: tudo do inventário.
INSERT INTO permissoes_de_cargo (uuid, cargo_uuid, permissao_uuid, excluido, usuario_criador_uuid, data_de_criacao)
SELECT UUID(), c.uuid, p.uuid, 0, NULL, NOW()
FROM cargos c CROSS JOIN permissoes p
WHERE c.slug IN ('admin_plataforma', 'admin_associacao_geral')
AND (p.slug LIKE 'itens\_do\_inventario\_%' OR p.slug LIKE 'movimentacoes\_do\_inventario\_%');

-- Admin da horta: tudo, menos a listagem por associação.
INSERT INTO permissoes_de_cargo (uuid, cargo_uuid, permissao_uuid, excluido, usuario_criador_uuid, data_de_criacao)
SELECT UUID(), c.uuid, p.uuid, 0, NULL, NOW()
FROM cargos c CROSS JOIN permissoes p
WHERE c.slug = 'admin_horta_geral'
AND (p.slug LIKE 'itens\_do\_inventario\_%' OR p.slug LIKE 'movimentacoes\_do\_inventario\_%')
AND p.slug <> 'itens_do_inventario_get_associacao';
