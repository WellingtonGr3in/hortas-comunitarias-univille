-- Liga o pagamento à mensalidade que ele quita (decisão do time: opção A, pagamentos ganha mensalidade_uuid).
-- Alinhar com o DBA no HC-12 (Unificar o schema do banco).
--
-- mensalidade_tipo diz em qual tabela está a mensalidade_uuid, porque existem duas:
-- mensalidades_da_associacao (canteirista paga à associação) e mensalidades_da_plataforma
-- (associação paga à plataforma). Por ser polimórfica, mensalidade_uuid não tem FK; o
-- PagamentoService garante que ela existe.
--
-- carteirista_uuid passa a aceitar NULL: o pagamento de uma mensalidade da plataforma não tem
-- canteirista. Pagamentos antigos (avulsos, sem mensalidade) continuam válidos.
ALTER TABLE pagamentos
    MODIFY COLUMN carteirista_uuid CHAR(36) NULL,
    ADD COLUMN mensalidade_uuid CHAR(36) NULL AFTER carteirista_uuid,
    ADD COLUMN mensalidade_tipo ENUM('associacao', 'plataforma') NULL AFTER mensalidade_uuid,
    ADD KEY idx_pagamentos_mensalidade (mensalidade_tipo, mensalidade_uuid);
