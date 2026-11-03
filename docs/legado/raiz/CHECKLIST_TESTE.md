> [!NOTE]
> **Documento histórico.** Arquivado em 15/10/2026 (estava em `CHECKLIST_TESTE.md`). Pode conter credenciais, URLs e instruções que não valem mais. A organização atual da documentação está no [DOCUMENTACAO.md](../../../DOCUMENTACAO.md).
> Os casos de teste daqui ainda servem de base para um plano de testes novo.

# ✅ Checklist de Testes - App Mobile

## 🎯 Objetivo
Validar que todas as implementações estão funcionando corretamente antes do build de produção.

---

## 📦 1. Verificação de Instalação

### Dependências
- [x] @react-native-picker/picker@2.9.0 instalado
- [x] @react-native-community/datetimepicker@8.2.0 instalado
- [x] Todas as dependências resolvidas

### Arquivos
- [x] Design system criado (colors, typography, spacing)
- [x] Componentes base criados (Badge, SummaryCard)
- [x] Módulo Pagamentos completo (services, screens, navigator)
- [x] Navegação atualizada (MainTabNavigator)

---

## 🚀 2. Iniciar o App

```bash
cd mobile
npx expo start --clear
```

### Verificações Iniciais
- [ ] App inicia sem erros de compilação
- [ ] Nenhum erro de "module not found"
- [ ] Metro bundler carrega completamente
- [ ] QR code aparece

### ⚠️ Warnings Esperados (IGNORAR)
```
npm warn ERESOLVE overriding peer dependency
npm warn Conflicting peer dependency: eslint@8.57.1
```
**Estes warnings são normais e não afetam o app!**

---

## 📱 3. Testar no Dispositivo

### 3.1 Login/Autenticação
- [ ] Tela de login aparece
- [ ] Consegue fazer login com credenciais válidas
- [ ] Token é salvo corretamente
- [ ] Navega para tela principal após login
- [ ] Logout funciona
- [ ] Login persiste (fechar e abrir app)

**Credenciais de teste**:
- Email: `hortas_comunitarias@univille.br`
- Senha: `senha12345`

### 3.2 Navegação
- [ ] Bottom tab navigation aparece
- [ ] 5 tabs visíveis: Início, Hortas, Canteiros, **Pagamentos**, Perfil
- [ ] Ícone de Pagamentos (cash) aparece corretamente
- [ ] Consegue navegar entre todas as tabs
- [ ] Navegação não crasha

### 3.3 Módulo Hortas (existente)
- [ ] Lista de hortas carrega
- [ ] Pull-to-refresh funciona
- [ ] Consegue ver detalhes de uma horta
- [ ] Consegue criar nova horta
- [ ] Consegue editar horta
- [ ] Consegue deletar horta
- [ ] Empty state aparece quando vazio

### 3.4 Módulo Canteiros (existente)
- [ ] Lista de canteiros carrega
- [ ] Pull-to-refresh funciona
- [ ] Consegue ver detalhes de um canteiro
- [ ] Consegue criar novo canteiro
- [ ] Consegue editar canteiro
- [ ] Consegue deletar canteiro
- [ ] Empty state aparece quando vazio

### 3.5 **Módulo Pagamentos (NOVO)** 💰

#### Tela de Lista
- [ ] Tab "Pagamentos" abre sem erros
- [ ] Cards de resumo aparecem:
  - [ ] "Total Pago" com valor formatado (R$)
  - [ ] "Pagamentos" com count total
- [ ] Lista de pagamentos carrega
- [ ] Pull-to-refresh funciona
- [ ] Empty state aparece se vazio
- [ ] Botão FAB (+) aparece no canto inferior direito

#### Cada Item da Lista
- [ ] Nome do carteirista aparece
- [ ] Telefone do carteirista aparece (ou "-" se vazio)
- [ ] Valor em verde (R$ X,XX)
- [ ] Data formatada corretamente
- [ ] Badge de forma de pagamento aparece:
  - [ ] "PIX" com fundo azul claro (#e3f2fd)
  - [ ] "Dinheiro" com fundo verde claro (#f1f8e9)
- [ ] Observação aparece (se houver)
- [ ] Botões Editar (azul) e Excluir (vermelho) aparecem

#### Formulário de Novo Pagamento
- [ ] Botão FAB (+) abre o formulário
- [ ] Título "Novo Pagamento" aparece
- [ ] Todos os campos aparecem:
  - [ ] Carteirista (dropdown/picker)
  - [ ] Valor (input numérico)
  - [ ] Forma de Pagamento (dropdown: Dinheiro/PIX)
  - [ ] Data do Pagamento (input texto YYYY-MM-DD)
  - [ ] Observação (textarea)
- [ ] **Dropdown de Carteirista funciona** (crítico!)
  - [ ] Abre ao clicar
  - [ ] Lista de carteiristas aparece
  - [ ] Consegue selecionar um carteirista
  - [ ] Valor selecionado aparece
- [ ] Validações funcionam:
  - [ ] Erro se não selecionar carteirista
  - [ ] Erro se valor <= 0
  - [ ] Erro se não selecionar forma
  - [ ] Erro se não informar data
- [ ] Botão "Cadastrar" salva pagamento
- [ ] Loading aparece durante salvamento
- [ ] Mensagem de sucesso aparece
- [ ] Retorna para lista após salvar
- [ ] Novo pagamento aparece na lista

#### Editar Pagamento
- [ ] Botão "Editar" abre formulário
- [ ] Título "Editar Pagamento" aparece
- [ ] Campos preenchidos com dados existentes
- [ ] Consegue alterar valores
- [ ] Botão "Atualizar" salva alterações
- [ ] Retorna para lista após salvar
- [ ] Alterações aparecem na lista

#### Deletar Pagamento
- [ ] Botão "Excluir" abre confirmação
- [ ] Dialog "Tem certeza?" aparece
- [ ] "Cancelar" não deleta
- [ ] "Excluir" remove pagamento
- [ ] Mensagem de sucesso aparece
- [ ] Pagamento some da lista

### 3.6 Módulo Perfil (existente)
- [ ] Perfil do usuário carrega
- [ ] Dados aparecem corretamente
- [ ] Consegue editar perfil
- [ ] Consegue fazer logout

---

## 🌐 4. Integração com Backend de Produção

### Verificar Conexão
- [ ] App conecta com Railway:
  ```
  https://hortas-comunitarias-univille-production.up.railway.app/api/v1
  ```
- [ ] Login funciona com API de produção
- [ ] Dados são salvos no banco de produção
- [ ] Dados persistem após reload

### Testar CRUD Completo em Produção
#### Pagamentos
1. [ ] **Criar**: Adicionar pagamento de teste
2. [ ] **Read**: Pagamento aparece na lista
3. [ ] **Update**: Editar pagamento
4. [ ] **Delete**: Deletar pagamento
5. [ ] Verificar se não há erros de CORS
6. [ ] Verificar se autenticação (JWT) funciona

#### Outros Módulos
- [ ] Hortas funcionam em produção
- [ ] Canteiros funcionam em produção

---

## 🎨 5. Validação Visual (Design System)

### Cores
- [ ] Verde principal (#28a745) aparece corretamente
- [ ] Badges de PIX são azul claro
- [ ] Badges de Dinheiro são verde claro
- [ ] Botões primários são verdes
- [ ] Botões de editar são azuis (#2563eb)
- [ ] Botões de deletar são vermelhos (#e74c3c)

### Tipografia
- [ ] Fontes estão legíveis
- [ ] Tamanhos proporcionais (H1 > H2 > body)
- [ ] Pesos corretos (títulos em bold, textos normais)

### Espaçamentos
- [ ] Margens e paddings consistentes
- [ ] Cards têm espaçamento adequado
- [ ] Não há elementos colados

### Componentes
- [ ] Badges têm border radius arredondado
- [ ] Cards têm shadow sutil
- [ ] Botões têm feedback visual (pressão)
- [ ] Loading spinner aparece centralizado

---

## ⚠️ 6. Testes de Erro

### Conexão
- [ ] Desligar WiFi: app mostra mensagem de erro apropriada
- [ ] Religar WiFi: app reconecta
- [ ] Backend offline: mensagem de erro clara

### Validações
- [ ] Formulário vazio: mostra erros
- [ ] Valores inválidos: mostra erros específicos
- [ ] Token expirado: redireciona para login

### Edge Cases
- [ ] Lista vazia: empty state aparece
- [ ] Muito texto: trunca corretamente
- [ ] Muitos itens: scroll funciona

---

## 🐛 7. Bugs Conhecidos a Verificar

### Potenciais Problemas
- [ ] Picker não abre no Android → Instalar @react-native-picker/picker@2.9.0 ✅
- [ ] Cores diferentes do frontend → Verificar se #28a745 está sendo usado
- [ ] Data não formata → Verificar formatters.js
- [ ] Badge não aparece → Verificar import correto

### Se Encontrar Bugs
1. Anotar: Tela + Ação + Erro
2. Verificar console do Metro bundler
3. Verificar logs do dispositivo
4. Tirar screenshot se visual

---

## ✅ 8. Critérios de Aprovação

### Para Considerar PRONTO para Build
- [ ] **Todas** as funcionalidades existentes funcionam (Hortas, Canteiros, Perfil)
- [ ] **Módulo Pagamentos** funciona 100%:
  - [ ] Lista carrega
  - [ ] Criar funciona
  - [ ] Editar funciona
  - [ ] Deletar funciona
  - [ ] Picker funciona
  - [ ] Badges aparecem
- [ ] App não crasha em nenhum fluxo
- [ ] Integração com produção funciona
- [ ] Design system aplicado corretamente

### Opcional (Nice to Have)
- [ ] Animações suaves
- [ ] Feedback de loading em todas as ações
- [ ] Mensagens de erro específicas

---

## 📊 Resultado Final

### Resumo de Testes
- Total de checkboxes: **~120**
- Críticos (bloqueadores): **~40**
- Importantes: **~50**
- Nice to have: **~30**

### Status
- [ ] ✅ **APROVADO** - Pronto para build
- [ ] ⚠️ **APROVADO COM RESSALVAS** - Build com bugs menores conhecidos
- [ ] ❌ **REPROVADO** - Precisa correções antes do build

### Bugs Encontrados
```
1. [Tela] [Ação] [Erro]
2. [Tela] [Ação] [Erro]
...
```

### Próximos Passos Após Aprovação
1. Fazer commit do package.json atualizado
2. Gerar build APK: `npx eas build --platform android --profile preview`
3. Testar APK em dispositivo físico
4. Deploy final

---

**Boa sorte nos testes!** 🚀
