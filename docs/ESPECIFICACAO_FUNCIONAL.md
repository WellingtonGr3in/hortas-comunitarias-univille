# Especificação funcional: Inventário e Portal do Canteirista

Atualizado em 06/11/2026, conferido contra o código da `main` (commit `cf4d4fd`).

Este documento descreve o que cada módulo precisa fazer, com campos, regras e permissões. Onde o sistema já faz alguma coisa, a regra foi escrita a partir do código e indica o arquivo de onde veio. Onde ainda falta algo, está marcado como **pendente**.

Cargos citados:

- `admin_plataforma`: administrador da plataforma
- `admin_associacao_geral`: administrador de associação
- `admin_horta_geral`: administrador de horta
- `canteirista`: canteirista

## Módulo 1: Inventário

Controle de insumos e ferramentas de uma associação ou de uma horta. O módulo já está implementado: banco em `backend/src/Utils/SQL/11_SQL_inventario.sql`, API em `/api/v1/itens-do-inventario` e `/api/v1/movimentacoes-do-inventario`, e telas em `/inventario` e `/inventario/:id` (entregues em 15/10/2026).

### Item do inventário

**Campos**

- **Nome:** texto obrigatório, de 1 a 100 caracteres.
- **Descrição:** texto opcional.
- **Tipo:** obrigatório, `insumo` ou `ferramenta`.
- **Unidade de medida:** texto obrigatório, de 1 a 20 caracteres. A tela sugere kg, g, litro, ml, unidade, pacote, saco e metro.
- **Quantidade atual:** número com até 3 casas decimais, nunca negativo. Começa em 0 se não for informada.
- **Quantidade mínima:** número opcional, nunca negativo.
- **Dono do item:** uma associação **ou** uma horta.
- **Auditoria:** quem criou, quando criou, quem alterou por último, quando alterou e se foi excluído.

**Regras**

- O item pertence a uma associação ou a uma horta, nunca às duas e nunca a nenhuma. O banco garante isso com a restrição `chk_item_associacao_xor_horta`.
- O dono do item não muda depois do cadastro. Para trocar, é preciso cadastrar outro item.
- A quantidade não é editada diretamente. Ela só muda por movimentação. Se alguém tentar editar a quantidade, o sistema recusa com a mensagem "A quantidade não é editada diretamente: registre uma movimentação".
- Se o item for cadastrado com quantidade maior que zero, o sistema cria junto uma movimentação de entrada com essa quantidade. Assim o histórico bate com o saldo desde o primeiro dia.
- Quando a quantidade atual fica abaixo da quantidade mínima, o item aparece como **Estoque baixo** na lista e no detalhe. A lista tem o filtro **Só estoque baixo**.
- Excluir um item não apaga o registro. Ele fica marcado como excluído (exclusão lógica) e o histórico de movimentações continua guardado.

**Permissões**

- `admin_plataforma`: vê, cadastra, edita e exclui itens de qualquer associação ou horta.
- `admin_associacao_geral`: vê, cadastra, edita e exclui itens da própria associação e das hortas ligadas a ela.
- `admin_horta_geral`: vê, cadastra, edita e exclui itens só da própria horta. Não tem a listagem por associação.
- `canteirista`: sem acesso.

### Movimentação do inventário

**Campos**

- **Item:** obrigatório.
- **Tipo:** obrigatório, `entrada`, `saida` ou `ajuste`.
- **Quantidade:** número com até 3 casas decimais.
- **Saldo anterior e saldo posterior:** preenchidos pelo sistema.
- **Observação:** texto opcional, por exemplo "compra", "uso no canteiro 3" ou "contagem mensal".
- **Quem registrou e quando:** preenchidos pelo sistema com o usuário logado e a data e hora.

**Regras**

- **Entrada:** soma a quantidade ao saldo. A quantidade precisa ser maior que zero.
- **Saída:** subtrai a quantidade do saldo. A quantidade precisa ser maior que zero.
- **Ajuste:** é uma contagem física. A quantidade informada passa a ser o novo saldo e pode ser zero.
- **Saída maior que o estoque:** o sistema recusa a movimentação, não altera nada e mostra a mensagem "Saída de {quantidade} {unidade} maior que o estoque atual de {item} ({saldo} {unidade})." Por exemplo: "Saída de 5 kg maior que o estoque atual de Adubo (3 kg)."
- A movimentação e a atualização do saldo acontecem juntas, numa transação. Se uma falhar, nenhuma é gravada.
- Movimentação não se edita nem se exclui: não existe rota para isso. Para corrigir um erro, registre um ajuste.
- O detalhe do item mostra o **Histórico de movimentações** com data, tipo, quantidade, saldo e observação.

**Permissões**

- Quem pode ver um item também pode movimentá-lo. Valem as mesmas regras de cargo do item.
- `canteirista`: sem acesso.

### Pontos em aberto

- **Excluir item com saldo:** hoje dá para excluir um item que ainda tem quantidade. Falta decidir se o sistema deve exigir zerar o saldo antes.
- **Canteirista registrando uso de insumo:** hoje só os administradores movimentam o estoque. Se o canteirista precisar registrar o que usou, isso é uma regra nova.

## Módulo 2: Portal do Canteirista

**Situação atual.** No site, o canteirista usa as mesmas telas dos administradores, com outros nomes no menu ("Meus canteiros", "Meus pagamentos", "Avisos") e com os botões de cadastrar, editar e excluir escondidos. Os dados já vêm filtrados pelo backend, e o canteirista só recebe o que é dele. O aplicativo de celular tem telas próprias (Início, Hortas, Canteiros, Pagamentos e Perfil), mas não tem Dependentes nem Avisos.

Cada tela abaixo lista os campos, o endpoint que já fornece os dados (conferido em `backend/src/Routes` e nas permissões do cargo em `13_SQL_permissoes_por_perfil.sql`) e a situação.

### Meus Canteiros

**Campos**

- Número do canteiro (código da placa)
- Tamanho em m²
- Status: Disponível, Ocupado ou Em preparo
- Localização dentro da horta
- O que está plantado
- Data da última colheita
- Nome da horta
- **Resumo no topo:** total de canteiros, área total em m², canteiros ativos e área média

**Endpoints**

- `GET /canteiros`: lista os canteiros do usuário logado, já com a horta. O filtro está em `CanteiroService::queryCanteirosDoUsuario`.
- `GET /canteiros/{uuid}`: detalhe de um canteiro. Recusa canteiro que não é do usuário.
- `GET /canteiros/summary/meus-canteiros`: dados do resumo do topo.

**Regras e permissões**

- O canteirista só vê os próprios canteiros e não edita nada.
- Permissões: `canteiros_get` e `canteiros_get_uuid`.

**Situação:** backend pronto. No site, a tela é a administrativa reaproveitada (`/canteiros`). Falta uma tela própria.

### Meu Financeiro

**Campos**

- **Mensalidades:**
  - Mês de referência
  - Valor
  - Vencimento
  - Situação: Paga, Pendente ou Atrasada
  - Dias em atraso
  - Data do pagamento
  - Recibo, quando houver
- **Pagamentos:**
  - Data
  - Valor
  - Forma: PIX ou Dinheiro
  - Mensalidade a que se refere
  - Observação

**Endpoints**

- `GET /mensalidades-da-associacao/usuario/{uuid}`: mensalidades do canteirista. Permissão `mensalidades_da_associacao_get_usuario`.
- `GET /pagamentos`: pagamentos do usuário logado (`PagamentoService`, `findAllDoUsuario`).

**Regras e permissões**

- O canteirista só consulta. Não registra pagamento nem altera mensalidade.

**Situação:** backend pronto. No site, "Meus pagamentos" mostra só os pagamentos. A tela de mensalidades (`/mensalidades-associacao`) é bloqueada para o canteirista, então ele não vê o que está pendente ou atrasado. Falta incluir as mensalidades nesta tela. **Pendente.**

### Meus Dependentes

**Campos**

- Nome
- Idade
- Ativo ou inativo
- CPF, mostrado só em parte (por exemplo `***.456.789-**`), seguindo o princípio do menor privilégio da Sprint 3

**Endpoints**

- `GET /dependentes`: lista só os dependentes do usuário logado (`DependenteService`).
- `GET /dependentes/{uuid}`: detalhe. Recusa dependente de outra pessoa.

**Regras e permissões**

- O canteirista só consulta. Quem cadastra e altera é o administrador da horta.
- O dependente não entra no sistema: o cargo `dependente` foi desativado.

**Situação:** backend pronto e tela no site (`/dependentes`). O CPF hoje aparece inteiro, e mascarar é **pendente**. No aplicativo, essa tela não existe.

### Notificações

**Campos**

- Título
- Mensagem
- Tipo: aviso geral, evento da horta ou aviso para um canteirista
- Prioridade: baixa, média ou alta
- Data do evento
- Lida ou não lida

**Endpoints**

- `GET /notificacoes/ativas`: só avisos em vigência, ou seja, ativos, com data de início já passada e data de fim vazia ou futura. Vêm do mais novo para o mais antigo (`NotificacaoRepository`).
- `POST /notificacoes/{id}/marcar-lida`: marca um aviso como lido.
- `POST /notificacoes/marcar-todas-lidas`: marca todos como lidos.

**Regras e permissões**

- A leitura é registrada por usuário: um aviso geral lido por uma pessoa continua não lido para as outras.
- O canteirista não cria nem edita avisos.
- Permissões: `notificacoes_get` e `notificacoes_marcar_lida`.

**Situação:** pronto no site, no menu "Avisos". No aplicativo, essa tela não existe.

### Minha Associação e Minha Horta

**Campos**

- **Horta:** nome, endereço, telefone de contato e nome do responsável.
- **Associação:** nome fantasia, razão social, telefone, e-mail e endereço.

**Endpoints**

- `GET /hortas/{uuid}`: horta do usuário. Recusa outra horta (`HortaService`).
- `GET /associacoes/{uuid}`: associação do usuário. Recusa associação fora do escopo (`AssociacaoService`).

**Regras e permissões**

- O canteirista só consulta.
- Permissões: `hortas_get_uuid` e `associacoes_get_uuid`.

**Situação:** backend pronto e tela no aplicativo (aba Hortas). No site, não há esta tela no menu do canteirista. **Pendente.**

### Endpoints para confirmar com o backend

Conferi no código, mas vale confirmar com quem mantém a API antes de marcar como pronto:

1. `GET /canteiros/summary/meus-canteiros` devolve só os canteiros do usuário logado?
2. `GET /mensalidades-da-associacao/usuario/{uuid}` recusa quando o `{uuid}` é de outro canteirista?
3. `GET /associacoes/{uuid}` funciona para o canteirista? O `AssociacaoService` não tem um caso específico para esse cargo.
4. A posição na fila de espera (`GET /fila-de-usuarios/usuario/{uuid}`) deve aparecer no portal? Hoje o serviço só mostra a fila da horta em que o usuário já está, então quem espera vaga em outra horta não vê a própria posição.
