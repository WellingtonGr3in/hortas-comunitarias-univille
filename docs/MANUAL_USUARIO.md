# Manual do Usuário — Hortas Comunitárias

Atualizado em 06/11/2026

Este manual mostra, em passo a passo, as tarefas mais comuns de cada perfil do sistema Hortas Comunitárias. Ele foi escrito para quem cuida de uma horta no dia a dia, não para quem trabalha com tecnologia.

## Antes de começar

O sistema tem duas formas de acesso: o **site**, usado pelos administradores, e o **aplicativo de celular**, pensado para o canteirista. Cada pessoa entra com o próprio e-mail e senha, e o sistema mostra só o que aquele perfil pode ver ou mudar.

**Criar uma conta para a sua associação**

Se a sua associação ainda não usa o sistema, você mesmo pode criar a conta:

1. Na tela de entrada, clique em **Criar conta**.
2. Em **Associação**, preencha razão social, nome fantasia e CNPJ.
3. Em **Administrador**, preencha os seus dados: nome completo, como prefere ser chamado, CPF, data de nascimento, e-mail e senha (duas vezes).
4. Clique em **Criar conta**. O sistema já entra com o seu e-mail e você passa a ser o administrador dessa associação.

**Como entrar no site**

1. Abra o endereço do sistema no navegador.
2. Digite seu e-mail e sua senha.
3. Clique em **Entrar**. Você vai para a tela inicial, com o menu no topo. O menu muda conforme o seu perfil:
   - **Administrador da plataforma:** Início, Associações, Hortas, Canteiros, Canteiristas, Usuários, Pagamentos, Planos, Mensalidades, Mensalidades da associação, Dependentes, Inventário, Notificações e Perfil.
   - **Administrador de associação:** os mesmos itens, menos Planos.
   - **Administrador de horta:** Início, Associações, Hortas, Canteiros, Canteiristas, Usuários, Pagamentos, Dependentes, Inventário, Notificações e Perfil.
   - **Canteirista:** Início, Meus canteiros, Meus pagamentos, Dependentes, Avisos e Perfil.
4. Para sair, use o botão de sair no canto superior direito.

Cada perfil só enxerga os dados da sua parte: o administrador de associação vê a própria associação, o administrador de horta vê a própria horta e o canteirista vê só os próprios dados. Se você tentar abrir uma página que não é do seu perfil, o sistema volta para a tela inicial e mostra um aviso.

Em **Perfil** você confere seus dados e corrige seu e-mail. O canteirista também pode corrigir o telefone.

Em **Notificações**, os avisos que você ainda não leu aparecem com um ponto azul. Para limpar todos de uma vez, clique em **Marcar todas como lidas**.

| Perfil | O que faz no sistema |
| --- | --- |
| Administrador da plataforma | Cuida das associações cadastradas e do financeiro da plataforma |
| Administrador de associação | Cuida das hortas da associação, dos associados, do financeiro e do inventário da associação |
| Administrador de horta | Cuida dos canteiros, canteiristas, dependentes, do financeiro e do inventário da horta |
| Canteirista | Consulta seus pagamentos, sua associação, seus canteiros e seus dependentes |

## Administrador da plataforma

Você cuida do sistema como um todo: quais associações usam a plataforma e se a mensalidade da plataforma está em dia.

**1. Cadastrar uma nova associação**

1. No menu, clique em **Associações**.
2. Clique em **Nova Associação**.
3. Preencha nome, descrição, endereço, telefone e e-mail da associação.
4. Clique em **Salvar**. A associação aparece na lista.

**2. Conferir e aprovar uma associação**

1. Em **Associações**, procure a associação na lista.
2. Abra a associação e confira os dados e os documentos enviados (estatuto e ata).
3. Se estiver tudo certo, marque a associação como aprovada e salve.

**3. Corrigir os dados de uma associação**

1. Em **Associações**, clique em **Editar** na associação.
2. Mude o que estiver errado, como telefone ou e-mail.
3. Clique em **Salvar**.

**4. Acompanhar a mensalidade da plataforma**

1. No menu, clique em **Mensalidades**.
2. A lista mostra, para cada pessoa, o plano, o valor, o vencimento e a situação: **Paga**, **Pendente** ou **Atrasada**.
3. Para ver só quem deve, escolha **Pendentes** ou **Atrasadas** no filtro de status.
4. Quando alguém pagar, clique em **Registrar pagamento** na linha daquela mensalidade.
5. Confira o valor, informe a data, escolha **PIX** ou **Dinheiro**, escreva uma observação se quiser e clique em **Registrar pagamento**. A mensalidade passa a aparecer como **Paga** e o pagamento entra em **Pagamentos**.

Para cobrar uma associação, clique em **Nova mensalidade**, escolha a associação, o valor e o vencimento e salve. Ela começa como pendente.

**5. Mandar um aviso para todos**

1. No menu, clique em **Notificações** e depois em **Nova Notificação**.
2. Em tipo, escolha **Aviso Geral**.
3. Escreva um título curto e a mensagem.
4. Escolha a prioridade (baixa, média ou alta) e a data de início.
5. Clique em **Salvar**. O aviso aparece para os usuários.

**6. Cadastrar um plano**

O plano define quanto cada associação paga pelo uso da plataforma. Só o administrador da plataforma vê esta tela.

1. No menu, clique em **Planos** e depois em **Novo plano**.
2. Preencha o código, o nome, um apelido sem espaços em **Slug** (por exemplo `plano_bronze`), o valor em reais e, se quiser, uma descrição.
3. Clique em **Salvar**. Para mudar um plano depois, clique em **Editar** na linha dele.

## Administrador de associação

Você cuida das hortas que pertencem à sua associação, das pessoas associadas e do dinheiro da associação.

**1. Cadastrar uma nova horta**

1. No menu, clique em **Hortas** e depois em **Nova Horta**.
2. Em **Associação**, escolha a sua associação.
3. Preencha nome da horta, localização, telefone e o nome do responsável.
4. Clique em **Salvar**.

**2. Atualizar os dados de uma horta**

1. Em **Hortas**, clique em **Editar** na horta.
2. Mude o que for preciso, por exemplo o responsável ou o telefone.
3. Clique em **Salvar**.

**3. Cadastrar um associado (canteirista)**

1. No menu, clique em **Canteiristas**.
2. Clique para criar um novo e escolha a horta onde a pessoa vai plantar.
3. Preencha nome, CPF, e-mail, data de nascimento e telefone.
4. Crie uma senha inicial de pelo menos 6 caracteres e passe para a pessoa.
5. Clique em **Salvar**. Com esse e-mail e senha a pessoa já entra no aplicativo.

**4. Registrar o pagamento de uma mensalidade**

1. No menu, clique em **Pagamentos** e depois em **Novo Pagamento**.
2. Escolha quem pagou.
3. Informe o valor, a forma de pagamento (dinheiro ou PIX) e a data.
4. Se quiser, escreva uma observação, como "mês de outubro".
5. Clique em **Salvar**.

**5. Ver quem está em dia**

1. Em **Pagamentos**, olhe a lista de pagamentos.
2. Os pagamentos quitados aparecem como **Pago**.
3. Quem ainda não pagou aparece sem essa marcação. Use **Filtros** para achar uma pessoa ou um período.

**6. Cobrar a mensalidade de um canteirista**

1. No menu, clique em **Mensalidades da associação** e depois em **Nova mensalidade**.
2. Escolha a associação e o canteirista, informe o valor e o vencimento e salve. A mensalidade começa como pendente.
3. Para ver quem deve, use o filtro de status: **Pendentes** ou **Atrasadas**.
4. Quando o canteirista pagar, clique em **Marcar como paga** na linha da mensalidade.

**7. Consultar os usuários**

1. No menu, clique em **Usuários**. No topo aparecem o total de usuários, quantos há de cada cargo e quantos estão ativos.
2. Para achar alguém, digite nome, e-mail ou CPF na busca, ou filtre por cargo, associação ou horta.
3. Esta tela só mostra. Para mudar os dados de um canteirista, use **Canteiristas**.

O inventário funciona igual para o administrador de associação e o de horta. Veja **Controlar o inventário**, na parte do administrador de horta.

## Administrador de horta

Você cuida do dia a dia de uma horta: os canteiros, quem planta em cada um, os dependentes e os pagamentos.

**1. Cadastrar um canteiro**

1. No menu, clique em **Canteiros** e depois em **+ Novo Canteiro**.
2. Preencha o número do canteiro (o código que fica na placa, por exemplo "A-12") e o tamanho em metros quadrados.
3. Escreva onde ele fica dentro da horta e escolha a horta.
4. Em **Status**, escolha **Disponível**, **Ocupado** ou **Em Preparo**.
5. Clique em **Salvar**.

**2. Entregar um canteiro para um canteirista**

1. Em **Canteiros**, clique em **Editar** no canteiro.
2. Em **Canteirista Proprietário**, escolha a pessoa.
3. Mude o status para **Ocupado**.
4. Se quiser, preencha o que está plantado e a data da última colheita.
5. Clique em **Salvar**.

**3. Cadastrar um dependente**

1. No menu, clique em **Dependentes** e depois em **Cadastrar Dependente**.
2. Preencha nome, CPF e idade.
3. Em **Canteirista (Responsável)**, escolha o canteirista de quem a pessoa depende.
4. Deixe marcado **Ativo** e clique em **Salvar**.

**4. Registrar um pagamento da horta**

1. No menu, clique em **Pagamentos** e depois em **Novo Pagamento**.
2. Escolha o canteirista, informe o valor, a forma (dinheiro ou PIX) e a data.
3. Clique em **Salvar**. O total pago no topo da tela é atualizado.

**5. Avisar sobre um evento na horta**

1. Em **Notificações**, clique em **Nova Notificação**.
2. Em tipo, escolha **Evento da Horta** (ou **Aviso para Canteirista Específico** para falar com uma pessoa só).
3. Escolha a horta, escreva título e mensagem e informe a data do evento.
4. Clique em **Salvar**.

**6. Liberar um canteiro**

1. Em **Canteiros**, clique em **Editar** no canteiro.
2. Em **Canteirista Proprietário**, escolha **Sem proprietário**.
3. Mude o status para **Disponível** ou **Em Preparo** e clique em **Salvar**.

**7. Controlar o inventário**

O inventário guarda os insumos (adubo, sementes, substrato) e as ferramentas da horta ou da associação. Cada item pertence a uma horta ou a uma associação, nunca às duas.

Para cadastrar um item:

1. No menu, clique em **Inventário** e depois em **+ Novo item**.
2. Preencha o nome, escolha o tipo (**Insumo** ou **Ferramenta**) e a unidade de medida (kg, litro, unidade, saco...).
3. Em **Pertence a**, escolha a horta ou a associação. Isso não muda depois.
4. Informe a quantidade que existe hoje e, se quiser, uma **Quantidade mínima**. Quando o estoque ficar abaixo dela, o item aparece como **Estoque baixo**.
5. Clique em **Salvar**.

Depois do cadastro, a quantidade só muda por movimentação:

1. Na lista, clique em **Movimentar** no item.
2. Escolha o tipo:
   - **Entrada:** chegou mais (uma compra ou doação). Soma ao estoque.
   - **Saída:** foi usado ou emprestado. Diminui o estoque. O sistema não deixa tirar mais do que existe.
   - **Ajuste:** você contou e o número é diferente. Informe o total contado, que passa a ser o novo estoque.
3. Escreva uma observação, por exemplo "uso no canteiro 3", e salve.

Para ver o que aconteceu com um item, clique no nome dele: aparece o **Histórico de movimentações**, com data, tipo, quantidade e quem registrou. Para achar o que está acabando, marque **Só estoque baixo** na lista.

## Canteirista

Você planta em um ou mais canteiros. No sistema você consulta suas informações e só pode mudar o seu e-mail e o seu telefone; o resto quem cadastra e muda é o administrador da sua horta.

**1. Entrar no aplicativo**

1. Abra o aplicativo **Hortas Comunitárias** no celular.
2. Digite o e-mail e a senha que o administrador da horta passou para você.
3. Toque em **Entrar**. Na parte de baixo da tela ficam as abas Início, Hortas, Canteiros, Pagamentos e Perfil.

**2. Ver seus canteiros**

1. Toque na aba **Canteiros**.
2. Toque em um canteiro para ver o número, o tamanho, a horta e o que está plantado.

**3. Conferir seus pagamentos**

1. Toque na aba **Pagamentos**.
2. Veja a lista das mensalidades, com valor, data e forma de pagamento.
3. Se algum pagamento que você fez não aparecer, fale com o administrador da sua horta.

**4. Ver sua horta e sua associação**

1. Toque na aba **Hortas** e depois na sua horta para ver endereço e responsável.
2. Na tela da horta você também vê a associação a que ela pertence.

**5. Atualizar seu telefone ou e-mail**

1. Toque na aba **Perfil** e depois em **Editar**.
2. Corrija o que mudou.
3. Toque em **Salvar Alterações**.

**6. Ver seus dependentes**

Por enquanto os dependentes aparecem só no site. Entre no site com o mesmo e-mail e senha e clique em **Dependentes** no menu. Para incluir ou tirar alguém, peça ao administrador da horta.

**Para sair:** vá em **Perfil** e toque em **Sair**.

## Glossário

| Termo | O que significa |
| --- | --- |
| Associação | Grupo organizado, com CNPJ, que reúne pessoas e cuida de uma ou mais hortas. Os associados pagam uma mensalidade para a associação. |
| Canteirista | Pessoa que planta em um canteiro da horta. |
| Canteiro | Pedaço de terra da horta, com número e tamanho em metros quadrados, entregue a um canteirista. Pode estar disponível, ocupado ou em preparo. |
| Chave | Cópia da chave do portão ou do depósito da horta. O sistema registra qual chave existe e se ela está disponível ou emprestada. |
| Dependente | Pessoa ligada a um canteirista, como um filho ou cônjuge, que pode ajudar no canteiro dele. O dependente não entra no sistema: quem cadastra e consulta é o administrador da horta e o próprio canteirista. |
| Fila de espera | Lista, em ordem de chegada, de pessoas que querem um canteiro numa horta onde não há canteiro livre. Hoje o sistema só guarda a ordem; quem chama a próxima pessoa quando um canteiro vaga é o administrador da horta. |
| Horta | Terreno onde ficam os canteiros. Toda horta pertence a uma associação e tem um responsável. |
| Insumo | Material que é gasto no uso, como adubo, semente ou substrato. Fica no inventário junto com as ferramentas. |
| Inventário | Lista dos insumos e ferramentas de uma horta ou de uma associação, com a quantidade de cada um e o histórico de entradas e saídas. |
| Mensalidade | Valor pago todo mês. O associado paga a mensalidade da associação, e a mensalidade da plataforma é o valor cobrado pelo uso do sistema. Cada uma tem data de vencimento e aparece como "Paga" quando é quitada. |
| Plano | Pacote que define quanto uma associação paga por mês para usar a plataforma. |
