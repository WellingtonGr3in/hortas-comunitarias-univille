-- Canteiristas criados pela tela antes desta correção ficaram com o usuário sem horta e sem associação:
-- não apareciam nas mensalidades da associação e entravam no sistema com o token sem escopo.
-- Preenche só o que está vazio, a partir da horta do cadastro de canteirista.
UPDATE usuarios u
JOIN canteiristas c ON c.usuario_uuid = u.uuid AND c.excluido = 0
JOIN hortas h ON h.uuid = c.horta_uuid
SET u.horta_uuid = COALESCE(u.horta_uuid, h.uuid),
    u.associacao_uuid = COALESCE(u.associacao_uuid, h.associacao_vinculada_uuid)
WHERE u.horta_uuid IS NULL OR u.associacao_uuid IS NULL;
