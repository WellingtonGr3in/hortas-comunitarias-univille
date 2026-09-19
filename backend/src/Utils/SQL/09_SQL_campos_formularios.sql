-- Campos já enviados pelas telas e declarados nos Models, ausentes do schema.
ALTER TABLE associacoes
    ADD COLUMN descricao TEXT NULL,
    ADD COLUMN telefone_de_contato VARCHAR(20) NULL,
    ADD COLUMN email VARCHAR(255) NULL,
    ADD COLUMN endereco_texto TEXT NULL;
ALTER TABLE hortas
    ADD COLUMN telefone_de_contato VARCHAR(20) NULL,
    ADD COLUMN nome_do_responsavel VARCHAR(255) NULL;
