-- Permissões por perfil conforme o Manual do Usuário (HC-72).
-- 1. Cria as permissões das rotas que estavam fora do mapa (dependentes, pagamentos, notificações e outras).
-- 2. Refaz as permissões dos cargos admin_associacao_geral, admin_horta_geral e canteirista.
-- 3. Desativa o cargo dependente: dependente não entra no sistema, só fica cadastrado ligado ao canteirista.
-- O escopo (própria associação, própria horta, só os meus dados) é aplicado nos Services.

-- ================= NOVAS PERMISSÕES =================
INSERT IGNORE INTO permissoes (uuid, slug, tipo, descricao, modulo, excluido, usuario_criador_uuid, data_de_criacao, usuario_alterador_uuid, data_de_ultima_alteracao) VALUES
(UUID(), 'dependentes_get', 0, 'Listar dependentes', 24, 0, NULL, NOW(), NULL, NOW()),
(UUID(), 'dependentes_get_uuid', 0, 'Buscar dependente por UUID', 24, 0, NULL, NOW(), NULL, NOW()),
(UUID(), 'dependentes_post', 1, 'Criar dependente', 24, 0, NULL, NOW(), NULL, NOW()),
(UUID(), 'dependentes_put', 2, 'Editar dependente', 24, 0, NULL, NOW(), NULL, NOW()),
(UUID(), 'dependentes_delete', 3, 'Deletar dependente', 24, 0, NULL, NOW(), NULL, NOW()),
(UUID(), 'pagamentos_get', 0, 'Listar pagamentos', 25, 0, NULL, NOW(), NULL, NOW()),
(UUID(), 'pagamentos_get_uuid', 0, 'Buscar pagamento por UUID', 25, 0, NULL, NOW(), NULL, NOW()),
(UUID(), 'pagamentos_post', 1, 'Registrar pagamento', 25, 0, NULL, NOW(), NULL, NOW()),
(UUID(), 'pagamentos_put', 2, 'Editar pagamento', 25, 0, NULL, NOW(), NULL, NOW()),
(UUID(), 'pagamentos_delete', 3, 'Deletar pagamento', 25, 0, NULL, NOW(), NULL, NOW()),
(UUID(), 'notificacoes_get', 0, 'Ler notificações', 26, 0, NULL, NOW(), NULL, NOW()),
(UUID(), 'notificacoes_marcar_lida', 2, 'Marcar notificações como lidas', 26, 0, NULL, NOW(), NULL, NOW()),
(UUID(), 'notificacoes_post', 1, 'Criar notificação', 26, 0, NULL, NOW(), NULL, NOW()),
(UUID(), 'notificacoes_put', 2, 'Editar notificação', 26, 0, NULL, NOW(), NULL, NOW()),
(UUID(), 'notificacoes_delete', 3, 'Deletar notificação', 26, 0, NULL, NOW(), NULL, NOW()),
(UUID(), 'mensalidades_da_associacao_get_associacao', 0, 'Listar mensalidades da associação por associação', 14, 0, NULL, NOW(), NULL, NOW());

-- ================= ADMIN DA PLATAFORMA (e cargo dummy dos dados de teste): tudo =================
INSERT IGNORE INTO permissoes_de_cargo (uuid, cargo_uuid, permissao_uuid, excluido, usuario_criador_uuid, data_de_criacao)
SELECT UUID(), c.uuid, p.uuid, 0, NULL, NOW()
FROM cargos c CROSS JOIN permissoes p
WHERE c.slug IN ('admin_plataforma', 'dummy')
AND p.slug NOT IN ('sessoes_login', 'sessoes_cadastro');

-- ================= REFAZ OS DEMAIS CARGOS =================
DELETE pc FROM permissoes_de_cargo pc
JOIN cargos c ON c.uuid = pc.cargo_uuid
WHERE c.slug IN ('admin_associacao_geral', 'admin_horta_geral', 'canteirista', 'dependente');

-- ADMIN DA ASSOCIAÇÃO: cuida das hortas, associados e financeiro da própria associação
INSERT INTO permissoes_de_cargo (uuid, cargo_uuid, permissao_uuid, excluido, usuario_criador_uuid, data_de_criacao)
SELECT UUID(), c.uuid, p.uuid, 0, NULL, NOW()
FROM cargos c CROSS JOIN permissoes p
WHERE c.slug = 'admin_associacao_geral'
AND (p.slug IN (
    -- Associações (somente a própria, leitura)
    'associacoes_get', 'associacoes_get_uuid',
    -- Usuários
    'usuarios_get', 'usuarios_get_uuid', 'usuarios_post', 'usuarios_put', 'usuarios_delete',
    -- Hortas
    'hortas_get', 'hortas_get_uuid', 'hortas_post', 'hortas_put', 'hortas_delete',
    -- Endereços
    'enderecos_get', 'enderecos_get_uuid', 'enderecos_post', 'enderecos_put', 'enderecos_delete',
    -- Canteiros
    'canteiros_get', 'canteiros_get_uuid', 'canteiros_post', 'canteiros_put', 'canteiros_delete',
    -- Canteiros e Usuários
    'canteiros_e_usuarios_get', 'canteiros_e_usuarios_get_uuid', 'canteiros_e_usuarios_post', 'canteiros_e_usuarios_put', 'canteiros_e_usuarios_delete',
    -- Canteiristas
    'canteiristas_get', 'canteiristas_get_uuid', 'canteiristas_post', 'canteiristas_put', 'canteiristas_delete',
    'canteiristas_canteiros_get', 'canteiristas_canteiros_get_uuid', 'canteiristas_canteiros_post', 'canteiristas_canteiros_put', 'canteiristas_canteiros_delete',
    -- Dependentes
    'dependentes_get', 'dependentes_get_uuid', 'dependentes_post', 'dependentes_put', 'dependentes_delete',
    -- Pagamentos
    'pagamentos_get', 'pagamentos_get_uuid', 'pagamentos_post', 'pagamentos_put', 'pagamentos_delete',
    -- Notificações
    'notificacoes_get', 'notificacoes_marcar_lida', 'notificacoes_post', 'notificacoes_put', 'notificacoes_delete',
    -- Cargos (somente leitura)
    'cargos_get', 'cargos_get_uuid',
    -- Permissões
    'permissoes_de_cargo_get', 'permissoes_de_cargo_get_uuid', 'permissoes_de_cargo_get_cargo',
    'permissoes_de_excecao_get', 'permissoes_de_excecao_get_uuid', 'permissoes_de_excecao_post', 'permissoes_de_excecao_put', 'permissoes_de_excecao_delete',
    'permissoes_do_usuario_get',
    -- Categorias Financeiras
    'categorias_financeiras_get', 'categorias_financeiras_get_uuid', 'categorias_financeiras_get_associacao', 'categorias_financeiras_get_horta',
    'categorias_financeiras_post', 'categorias_financeiras_put', 'categorias_financeiras_delete',
    -- Financeiro da Horta
    'financeiro_da_horta_get', 'financeiro_da_horta_get_uuid', 'financeiro_da_horta_get_horta',
    'financeiro_da_horta_post', 'financeiro_da_horta_put', 'financeiro_da_horta_delete',
    -- Financeiro da Associação
    'financeiro_da_associacao_get', 'financeiro_da_associacao_get_uuid', 'financeiro_da_associacao_get_associacao',
    'financeiro_da_associacao_post', 'financeiro_da_associacao_put', 'financeiro_da_associacao_delete',
    -- Mensalidades da Plataforma (somente leitura)
    'mensalidades_da_plataforma_get', 'mensalidades_da_plataforma_get_uuid',
    -- Mensalidades da Associação
    'mensalidades_da_associacao_get', 'mensalidades_da_associacao_get_uuid', 'mensalidades_da_associacao_get_associacao', 'mensalidades_da_associacao_get_usuario',
    'mensalidades_da_associacao_post', 'mensalidades_da_associacao_put', 'mensalidades_da_associacao_delete',
    -- Planos (somente leitura)
    'planos_get', 'planos_get_uuid', 'planos_get_usuario',
    -- Chaves
    'chaves_get', 'chaves_get_uuid', 'chaves_post', 'chaves_put', 'chaves_delete',
    -- Fila de Usuários
    'fila_de_usuarios_get', 'fila_de_usuarios_get_uuid', 'fila_de_usuarios_get_horta', 'fila_de_usuarios_get_usuario',
    'fila_de_usuarios_post', 'fila_de_usuarios_put', 'fila_de_usuarios_delete'
)
-- Inventário (completo)
OR p.slug LIKE 'itens\_do\_inventario\_%' OR p.slug LIKE 'movimentacoes\_do\_inventario\_%');

-- ADMIN DA HORTA: cuida dos canteiros, canteiristas, dependentes, pagamentos e avisos da própria horta
INSERT INTO permissoes_de_cargo (uuid, cargo_uuid, permissao_uuid, excluido, usuario_criador_uuid, data_de_criacao)
SELECT UUID(), c.uuid, p.uuid, 0, NULL, NOW()
FROM cargos c CROSS JOIN permissoes p
WHERE c.slug = 'admin_horta_geral'
AND (p.slug IN (
    -- Associações (somente a da sua horta, leitura)
    'associacoes_get', 'associacoes_get_uuid',
    -- Usuários
    'usuarios_get', 'usuarios_get_uuid', 'usuarios_post', 'usuarios_put', 'usuarios_delete',
    -- Hortas (ver e editar a própria)
    'hortas_get', 'hortas_get_uuid', 'hortas_put',
    -- Endereços
    'enderecos_get', 'enderecos_get_uuid', 'enderecos_post', 'enderecos_put', 'enderecos_delete',
    -- Canteiros
    'canteiros_get', 'canteiros_get_uuid', 'canteiros_post', 'canteiros_put', 'canteiros_delete',
    -- Canteiros e Usuários
    'canteiros_e_usuarios_get', 'canteiros_e_usuarios_get_uuid', 'canteiros_e_usuarios_post', 'canteiros_e_usuarios_put', 'canteiros_e_usuarios_delete',
    -- Canteiristas
    'canteiristas_get', 'canteiristas_get_uuid', 'canteiristas_post', 'canteiristas_put', 'canteiristas_delete',
    'canteiristas_canteiros_get', 'canteiristas_canteiros_get_uuid', 'canteiristas_canteiros_post', 'canteiristas_canteiros_put', 'canteiristas_canteiros_delete',
    -- Dependentes
    'dependentes_get', 'dependentes_get_uuid', 'dependentes_post', 'dependentes_put', 'dependentes_delete',
    -- Pagamentos
    'pagamentos_get', 'pagamentos_get_uuid', 'pagamentos_post', 'pagamentos_put', 'pagamentos_delete',
    -- Notificações
    'notificacoes_get', 'notificacoes_marcar_lida', 'notificacoes_post', 'notificacoes_put', 'notificacoes_delete',
    -- Cargos (somente leitura)
    'cargos_get', 'cargos_get_uuid',
    -- Permissões do usuário
    'permissoes_do_usuario_get',
    -- Categorias Financeiras
    'categorias_financeiras_get', 'categorias_financeiras_get_uuid', 'categorias_financeiras_get_horta',
    'categorias_financeiras_post', 'categorias_financeiras_put', 'categorias_financeiras_delete',
    -- Financeiro da Horta
    'financeiro_da_horta_get', 'financeiro_da_horta_get_uuid', 'financeiro_da_horta_get_horta',
    'financeiro_da_horta_post', 'financeiro_da_horta_put', 'financeiro_da_horta_delete',
    -- Financeiro da Associação (somente leitura)
    'financeiro_da_associacao_get', 'financeiro_da_associacao_get_uuid',
    -- Chaves
    'chaves_get', 'chaves_get_uuid', 'chaves_post', 'chaves_put', 'chaves_delete',
    -- Fila de Usuários
    'fila_de_usuarios_get', 'fila_de_usuarios_get_uuid', 'fila_de_usuarios_get_horta', 'fila_de_usuarios_get_usuario',
    'fila_de_usuarios_post', 'fila_de_usuarios_put', 'fila_de_usuarios_delete'
)
-- Inventário (tudo, menos a listagem por associação)
OR ((p.slug LIKE 'itens\_do\_inventario\_%' OR p.slug LIKE 'movimentacoes\_do\_inventario\_%')
    AND p.slug <> 'itens_do_inventario_get_associacao'));

-- CANTEIRISTA: só consulta os próprios dados e edita o próprio telefone e e-mail
INSERT INTO permissoes_de_cargo (uuid, cargo_uuid, permissao_uuid, excluido, usuario_criador_uuid, data_de_criacao)
SELECT UUID(), c.uuid, p.uuid, 0, NULL, NOW()
FROM cargos c CROSS JOIN permissoes p
WHERE c.slug = 'canteirista'
AND p.slug IN (
    -- Sua associação e sua horta
    'associacoes_get', 'associacoes_get_uuid',
    'hortas_get', 'hortas_get_uuid',
    -- Seus canteiros
    'canteiros_get', 'canteiros_get_uuid',
    'canteiros_e_usuarios_get', 'canteiros_e_usuarios_get_uuid',
    -- Seu cadastro de canteirista
    'canteiristas_get', 'canteiristas_get_uuid',
    -- Seus dependentes
    'dependentes_get', 'dependentes_get_uuid',
    -- Seus pagamentos e mensalidades
    'pagamentos_get', 'pagamentos_get_uuid',
    'mensalidades_da_associacao_get', 'mensalidades_da_associacao_get_uuid', 'mensalidades_da_associacao_get_usuario',
    -- Seus avisos
    'notificacoes_get', 'notificacoes_marcar_lida',
    -- Seu perfil (o Service limita a edição a telefone e e-mail)
    'usuarios_get_uuid', 'usuarios_put',
    'permissoes_do_usuario_get',
    -- Sua posição na fila de espera
    'fila_de_usuarios_get_usuario'
);

-- ================= CARGO DEPENDENTE DESATIVADO =================
UPDATE cargos SET excluido = 1, descricao = 'Desativado: dependente não entra no sistema.' WHERE slug = 'dependente';

UPDATE usuarios u
JOIN cargos c ON c.uuid = u.cargo_uuid
SET u.status_de_acesso = 'bloqueado',
    u.data_bloqueio_acesso = NOW(),
    u.motivo_bloqueio_acesso = 'Cargo dependente desativado: dependente não entra no sistema.'
WHERE c.slug = 'dependente';

-- Descrições dos cargos de acordo com o escopo real
UPDATE cargos SET descricao = 'Gerencia a própria associação: hortas, associados e financeiro.' WHERE slug = 'admin_associacao_geral';
UPDATE cargos SET descricao = 'Gerencia a própria horta: canteiros, canteiristas, dependentes e pagamentos.' WHERE slug = 'admin_horta_geral';
UPDATE cargos SET descricao = 'Planta em um ou mais canteiros e consulta os próprios dados.' WHERE slug = 'canteirista';
