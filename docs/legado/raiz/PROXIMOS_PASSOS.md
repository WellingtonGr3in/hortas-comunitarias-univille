> [!NOTE]
> **Documento histórico.** Arquivado em 15/10/2026 (estava em `PROXIMOS_PASSOS.md`). Pode conter credenciais, URLs e instruções que não valem mais. A organização atual da documentação está no [DOCUMENTACAO.md](../../../DOCUMENTACAO.md).

# 🚀 Próximos Passos - Deploy Mobile

## ✅ Status Atual

- ✅ Design system criado
- ✅ Componentes base criados (Badge, SummaryCard)
- ✅ Módulo Pagamentos implementado
- ✅ Commit realizado
- ⏳ Instalando @react-native-picker/picker

---

## 📋 Checklist de Deploy

### 1. ⏳ Instalação de Dependências (EM ANDAMENTO)

```bash
cd mobile
npx expo install @react-native-picker/picker
```

**Status**: Executando agora...

### 2. 🧪 Testar App Localmente

```bash
cd mobile
npx expo start --clear
```

**O que testar**:
- [ ] App inicia sem erros
- [ ] Login funciona
- [ ] Tab "Pagamentos" aparece (ícone de dinheiro)
- [ ] Tela de Pagamentos abre
- [ ] Cards de resumo aparecem (Total Pago, Total de Pagamentos)
- [ ] Botão + (FAB) funciona
- [ ] Formulário de novo pagamento abre
- [ ] Consegue selecionar Carteirista no dropdown
- [ ] Consegue salvar pagamento
- [ ] Badge de forma aparece (PIX azul, Dinheiro verde)

### 3. 🌐 Testar Integração com Produção

**URL da API**: `https://hortas-comunitarias-univille-production.up.railway.app/api/v1`

**Como testar**:
1. Faça login no app
2. Vá em Pagamentos
3. Adicione um pagamento de teste
4. Verifique se salvou (refresh na lista)
5. Edite o pagamento
6. Delete o pagamento

**Credenciais de teste** (se disponíveis):
- Email: `hortas_comunitarias@univille.br`
- Senha: `senha12345`

### 4. 🏗️ Build APK

#### Opção A: Build com EAS (Recomendado)

```bash
# Instalar EAS CLI (se ainda não tem)
npm install -g eas-cli

# Login no Expo
eas login

# Configurar projeto (primeira vez)
cd mobile
eas build:configure

# Build APK para testes
npx eas build --platform android --profile preview

# Ou Build AAB para Google Play
npx eas build --platform android --profile production
```

#### Opção B: Build Local (requer Android Studio)

```bash
cd mobile
npx expo run:android
```

### 5. 📱 Distribuir APK

Após o build, você receberá um link para download do APK.

**Compartilhar com testadores**:
1. Baixe o APK
2. Envie via WhatsApp, email, etc
3. Instale no dispositivo Android
4. Teste todas as funcionalidades

### 6. 🧪 Testes Finais

**Checklist de testes no dispositivo real**:

#### Autenticação
- [ ] Login funciona
- [ ] Logout funciona
- [ ] Token persiste (fechar e abrir app)

#### Hortas
- [ ] Listar hortas
- [ ] Ver detalhes
- [ ] Criar nova horta
- [ ] Editar horta
- [ ] Deletar horta

#### Canteiros
- [ ] Listar canteiros
- [ ] Ver detalhes
- [ ] Criar canteiro
- [ ] Editar canteiro
- [ ] Deletar canteiro

#### **Pagamentos (NOVO)** 💰
- [ ] Listar pagamentos
- [ ] Cards de resumo corretos (Total Pago, Total de Pagamentos)
- [ ] Criar pagamento
- [ ] Selecionar carteirista funciona
- [ ] Badges de forma aparecem (PIX azul, Dinheiro verde)
- [ ] Editar pagamento
- [ ] Deletar pagamento
- [ ] Pull-to-refresh funciona

#### Perfil
- [ ] Ver perfil
- [ ] Editar perfil

#### Geral
- [ ] Navegação entre tabs funciona
- [ ] Pull-to-refresh funciona em todas as listas
- [ ] Loading states aparecem
- [ ] Empty states aparecem quando vazio
- [ ] Mensagens de erro são claras
- [ ] App não crasha

---

## 🐛 Troubleshooting

### Erro: "Picker is not defined" ou similar

**Solução**:
```bash
cd mobile
npx expo install @react-native-picker/picker
npx expo start --clear
```

### App não conecta com API

**Verificar**:
1. URL no `app.json` está correta?
2. API está online? Teste: `curl https://hortas-comunitarias-univille-production.up.railway.app/api/v1`
3. Firewall bloqueando?

### Build falha

**Soluções**:
```bash
# Limpar cache
cd mobile
rm -rf node_modules
npm install
npx expo start --clear

# Se persistir, deletar package-lock.json
rm package-lock.json
npm install
```

### Erro de dependências

```bash
cd mobile
npm install
npx expo install --fix
```

---

## 📊 Estatísticas de Implementação

### Módulo Pagamentos
- **Linhas de código**: ~600 linhas
- **Arquivos criados**: 2 telas + 2 services + 1 navigator
- **Tempo de implementação**: ~4h
- **Cobertura**: CRUD 100% completo

### Design System
- **Linhas de código**: ~200 linhas
- **Arquivos criados**: 3 (colors, typography, spacing)
- **Sincronização**: 100% com frontend Bootstrap 5

### Componentes Base
- **Badge**: ~150 linhas
- **SummaryCard**: ~100 linhas
- **Variantes**: 10+ variantes de badges

### Total
- **Arquivos novos**: 13
- **Arquivos modificados**: 3
- **Linhas de código**: ~2000 linhas
- **Commit**: eef89ef

---

## 🎯 Roadmap Futuro (Fase 2)

### Dependentes 
- Service de dependentes ✅ (já criado)
- DependentesListScreen
- DependenteFormScreen
- Avatar component
- Máscara de CPF

### Notificações 
- Service de notificações
- NotificacoesListScreen (dupla view)
- NotificacaoFormScreen
- Badges de prioridade

### Melhorias UX
- DatePicker nativo
- Máscaras de input (telefone, CPF)
- Toast/Snackbar
- Animações

---

## 📞 Suporte

Se encontrar problemas:

1. **Verifique a documentação**:
   - [IMPLEMENTACOES_MOBILE_FASE1.md](IMPLEMENTACOES_MOBILE_FASE1.md)
   - [mobile/INSTALL_DEPENDENCIES.md](../mobile/INSTALL_DEPENDENCIES.md)

2. **Logs úteis**:
   ```bash
   # Ver logs do Expo
   npx expo start

   # Ver logs do build
   eas build:list
   ```

3. **Contato**:
   - Desenvolvedor: José Pedro [@sejodrope](https://github.com/sejodrope)
   - Projeto: Hortas Comunitárias - Univille

---

**Boa sorte com o deploy!** 🚀
