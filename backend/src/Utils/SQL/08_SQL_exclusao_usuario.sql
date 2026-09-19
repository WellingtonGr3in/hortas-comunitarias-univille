-- O valor 0 significa que o usuário NÃO é responsável pela conta.
ALTER TABLE usuarios DROP CHECK chk_nao_excluir_responsavel;
ALTER TABLE usuarios ADD CONSTRAINT chk_nao_excluir_responsavel
CHECK (excluido = 0 OR responsavel_da_conta IS NULL OR responsavel_da_conta = 0);
