-- Usuários de teste, um de cada perfil, com senha 123456. SÓ PARA AMBIENTE LOCAL.
-- O nome tem _DEV_: o run-migrations.php só executa este arquivo quando APP_ENV=development.
--
--   plataforma@gmail.com   Administrador da plataforma
--   associacao@gmail.com   Administrador da "Associação Demonstração"
--   horta@gmail.com        Administrador da "Horta Demonstração"
--   canteirista@gmail.com  Canteirista da "Horta Demonstração", dono do canteiro D-01
--
-- UUIDs fixos e INSERT IGNORE: rodar de novo não duplica nada.

SET @senha = '$2y$10$ew4sLRw7Vg0YZFbQz2nup.M0l/GhCTf1jM6idLmH8ScH.wDoBoD0W'; -- 123456

INSERT IGNORE INTO enderecos (uuid, tipo_logradouro, logradouro, numero, bairro, cidade, estado, cep, excluido)
VALUES ('de300000-0000-4000-8000-000000000001', 'Rua', 'Paulo Malschitzki', '10', 'Zona Industrial Norte', 'Joinville', 'SC', '89219-710', 0);

INSERT IGNORE INTO associacoes (uuid, cnpj, razao_social, nome_fantasia, endereco_uuid, status_aprovacao, excluido, telefone_de_contato, email)
VALUES ('de300000-0000-4000-8000-000000000002', '11222333000181', 'Associação Demonstração', 'Associação Demonstração',
        'de300000-0000-4000-8000-000000000001', 1, 0, '(47) 3461-0000', 'associacao@gmail.com');

INSERT IGNORE INTO hortas (uuid, nome_da_horta, endereco_uuid, associacao_vinculada_uuid, percentual_taxa_associado, excluido, telefone_de_contato, nome_do_responsavel)
VALUES ('de300000-0000-4000-8000-000000000003', 'Horta Demonstração', 'de300000-0000-4000-8000-000000000001',
        'de300000-0000-4000-8000-000000000002', 10.00, 0, '(47) 3461-0001', 'Horta Teste');

INSERT IGNORE INTO usuarios (uuid, nome_completo, apelido, cpf, email, senha, data_de_nascimento, cargo_uuid, associacao_uuid, horta_uuid, status_de_acesso, excluido)
SELECT 'de300000-0000-4000-8000-000000000010', 'Plataforma Teste', 'Plataforma', '747.092.762-00', 'plataforma@gmail.com', @senha, '1990-01-01',
       c.uuid, NULL, NULL, 'ativo', 0
FROM cargos c WHERE c.slug = 'admin_plataforma';

INSERT IGNORE INTO usuarios (uuid, nome_completo, apelido, cpf, email, senha, data_de_nascimento, cargo_uuid, associacao_uuid, horta_uuid, status_de_acesso, excluido)
SELECT 'de300000-0000-4000-8000-000000000011', 'Associação Teste', 'Associação', '358.872.177-07', 'associacao@gmail.com', @senha, '1990-01-01',
       c.uuid, 'de300000-0000-4000-8000-000000000002', 'de300000-0000-4000-8000-000000000003', 'ativo', 0
FROM cargos c WHERE c.slug = 'admin_associacao_geral';

INSERT IGNORE INTO usuarios (uuid, nome_completo, apelido, cpf, email, senha, data_de_nascimento, cargo_uuid, associacao_uuid, horta_uuid, status_de_acesso, excluido)
SELECT 'de300000-0000-4000-8000-000000000012', 'Horta Teste', 'Horta', '504.056.663-81', 'horta@gmail.com', @senha, '1990-01-01',
       c.uuid, 'de300000-0000-4000-8000-000000000002', 'de300000-0000-4000-8000-000000000003', 'ativo', 0
FROM cargos c WHERE c.slug = 'admin_horta_geral';

INSERT IGNORE INTO usuarios (uuid, nome_completo, apelido, cpf, email, senha, data_de_nascimento, cargo_uuid, associacao_uuid, horta_uuid, status_de_acesso, excluido)
SELECT 'de300000-0000-4000-8000-000000000013', 'Canteirista Teste', 'Canteirista', '854.417.439-64', 'canteirista@gmail.com', @senha, '1990-01-01',
       c.uuid, 'de300000-0000-4000-8000-000000000002', 'de300000-0000-4000-8000-000000000003', 'ativo', 0
FROM cargos c WHERE c.slug = 'canteirista';

-- Cadastro de canteirista (telefone) e um canteiro dele, para as telas não ficarem vazias
INSERT IGNORE INTO canteiristas (uuid, horta_uuid, usuario_uuid, telefone, ativo, excluido)
VALUES ('de300000-0000-4000-8000-000000000020', 'de300000-0000-4000-8000-000000000003', 'de300000-0000-4000-8000-000000000013', '(47) 99999-0001', 1, 0);

INSERT IGNORE INTO canteiros (uuid, numero_identificador, status, localizacao, tamanho_m2, horta_uuid, excluido)
VALUES ('de300000-0000-4000-8000-000000000030', 'D-01', 'Ocupado', 'Perto da entrada', 12.00, 'de300000-0000-4000-8000-000000000003', 0);

INSERT IGNORE INTO canteiristas_canteiros (uuid, canteirista_uuid, canteiro_uuid, ativo, excluido)
VALUES ('de300000-0000-4000-8000-000000000031', 'de300000-0000-4000-8000-000000000020', 'de300000-0000-4000-8000-000000000030', 1, 0);
