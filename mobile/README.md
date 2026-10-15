# 📱 Hortas Comunitárias - Mobile App

Aplicativo mobile desenvolvido em React Native + Expo para o sistema de gestão de hortas comunitárias.

> ⚠️ **IMPORTANTE**: Correções críticas aplicadas em 29/10/2025. O histórico está em [RESUMO_CORRECOES.md](../docs/legado/mobile/RESUMO_CORRECOES.md).

## 📋 Funcionalidades MVP

- ✅ **Autenticação** (CORRIGIDO ✨)
  - Login com **e-mail** e senha
  - Cadastro de novos usuários (estrutura completa)
  - Persistência de sessão (JWT + AsyncStorage)
  - Logout com limpeza completa
  
- ✅ **Gerenciamento de Entidades (CRUD)**
  - Hortas
  - Canteiros
  - Usuários
  - Associações
  - Categorias Financeiras
  - Chaves
  - Permissões

- ✅ **Navegação**
  - Bottom tabs para navegação principal
  - Stack navigation para telas de detalhes

## 🚀 Início Rápido

### Pré-requisitos

- Node.js 18+
- npm ou yarn
- Expo CLI (`npm install -g expo-cli`)
- Expo Go app no celular (Android/iOS)
- **Backend rodando** em `http://localhost:8181/api`

### Instalação

```bash
# Navegar para a pasta mobile
cd mobile

# Instalar dependências
npm install

# Iniciar o servidor de desenvolvimento
npm start

# OU usar script do PowerShell (Windows)
.\iniciar.ps1
```

### Executar no dispositivo

1. Instale o **Expo Go** no seu celular ([Android](https://play.google.com/store/apps/details?id=host.exp.exponent) | [iOS](https://apps.apple.com/app/expo-go/id982107779))
2. Execute `npm start`
3. Escaneie o QR code com o Expo Go (Android) ou Camera (iOS)

### Executar em emulador

```bash
# Android
npm run android

# iOS (apenas macOS)
npm run ios
```

## 🏗️ Estrutura do Projeto

```
mobile/
├── src/
│   ├── components/       # Componentes reutilizáveis
│   │   ├── common/       # Botões, inputs, cards, etc
│   │   └── forms/        # Formulários específicos
│   ├── screens/          # Telas do app
│   │   ├── Auth/         # Login e Cadastro
│   │   ├── Home/         # Tela inicial
│   │   ├── Hortas/       # CRUD de Hortas
│   │   ├── Canteiros/    # CRUD de Canteiros
│   │   ├── Usuarios/     # CRUD de Usuários
│   │   └── Profile/      # Perfil do usuário
│   ├── navigation/       # Configuração de rotas
│   ├── services/         # Integração com API
│   │   ├── api.js        # Cliente HTTP (axios)
│   │   └── auth.js       # Serviços de autenticação
│   ├── contexts/         # Context API (estado global)
│   │   └── AuthContext.js
│   ├── utils/            # Funções utilitárias
│   │   ├── validators.js # Validação de CPF, email, etc
│   │   └── formatters.js # Formatação de dados
│   ├── constants/        # Constantes e configurações
│   │   ├── colors.js
│   │   └── config.js
│   └── App.js            # Componente raiz
├── assets/               # Imagens, fontes, ícones
├── app.json              # Configuração do Expo
└── package.json          # Dependências
```

## 🔧 Configuração da API

### 🌐 Produção (Padrão)

O app está **configurado automaticamente** para usar a API de produção no Railway:

```
https://hortas-comunitarias-univille-production.up.railway.app/api/v1
```

### 🛠️ Desenvolvimento Local

Para testar com backend local, altere o arquivo [`app.json`](./app.json):

```json
"extra": {
  "apiUrl": "http://192.168.0.22:8181/api/v1"
}
```

### 📋 Alternativas

1. **Método 1**: Edite [`app.json`](./app.json)
```json
"extra": {
  "apiUrl": "SEU_URL_AQUI/api/v1"
}
```

2. **Método 2**: O app detecta automaticamente:
   - **Desenvolvimento** (`__DEV__ = true`): `http://localhost:8181/api/v1`
   - **Produção** (`__DEV__ = false`): URL do Railway

## 📱 Testando no Celular

Para testar com o backend rodando no seu computador:

### Método 1: Usar ngrok (Recomendado)
```bash
# Instalar ngrok
npm install -g ngrok

# Expor a porta do backend
ngrok http 8181

# Copiar o URL gerado (ex: https://abc123.ngrok.io)
# Atualizar app.json com esse URL
```

### Método 2: Usar IP local
```bash
# Descobrir seu IP local
# Windows: ipconfig
# Mac/Linux: ifconfig

# Atualizar app.json
"apiUrl": "http://SEU_IP:8181/api"
```

## 🎨 Padrões de UI

- **Cores principais**: Verde (#4CAF50) para ações primárias
- **Componentes**: Reutilizáveis e modulares
- **Responsividade**: Adapta-se a diferentes tamanhos de tela
- **Feedback**: Loading states e mensagens de erro/sucesso

## 🔐 Autenticação

O app usa JWT para autenticação:

1. Login retorna token JWT
2. Token é salvo no AsyncStorage
3. Requisições incluem token no header `Authorization: Bearer TOKEN`
4. Token expira após 24h (configurável no backend)

## 🧪 Testes

```bash
# Executar testes
npm test

# Executar com coverage
npm run test:coverage
```

## 📦 Build para Produção

### Android (APK)
```bash
# Build APK
expo build:android -t apk

# Build AAB (Google Play)
expo build:android -t app-bundle
```

### iOS
```bash
# Build para App Store
expo build:ios
```

## 🐛 Troubleshooting

### ⚠️ Problemas Corrigidos (29/10/2025)

#### ✅ Erro AsyncStorage: "Passing null/undefined as value"
**Causa**: Token não estava sendo recebido corretamente  
**Solução**: Validação antes de salvar + tratamento de erro robusto  
**Status**: ✅ CORRIGIDO

#### ✅ Tela Piscando ao Abrir App  
**Causa**: Loop infinito no AuthContext  
**Solução**: Loading state + validação de consistência  
**Status**: ✅ CORRIGIDO

#### ✅ Login com CPF Não Funcionava
**Causa**: API usa e-mail, não CPF/CNPJ  
**Solução**: Interface alterada para usar e-mail  
**Status**: ✅ CORRIGIDO

#### ✅ Cadastro Retornava Erro 400
**Causa**: Estrutura de dados incompatível com API  
**Solução**: Payload ajustado para `{associacao, usuario}`  
**Status**: ✅ CORRIGIDO

📖 **Ver detalhes completos**: [RESUMO_CORRECOES.md](../docs/legado/mobile/RESUMO_CORRECOES.md)

---

### Problemas Comuns

#### Erro de conexão com API
- Verifique se o backend está rodando em `http://localhost:8181/api`
- Confirme o URL da API em `src/constants/config.js`
- Para testar com celular físico, use o IP local (ex: `http://192.168.1.100:8181/api`)

#### Cache de dependências
```bash
# Limpar cache do Expo
expo start -c

# Limpar cache do Metro
npm start -- --reset-cache
```

#### Problemas com AsyncStorage
```bash
# Reinstalar dependência
npm uninstall @react-native-async-storage/async-storage
npm install @react-native-async-storage/async-storage

# Limpar storage do app (reset completo)
# No código: await AsyncStorage.clear();
```

#### App não conecta com backend no device físico
```bash
# 1. Descobrir IP local
ipconfig  # Windows
ifconfig  # Mac/Linux

# 2. Atualizar src/constants/config.js
export const API_URL = 'http://SEU_IP:8181/api';

# 3. Backend deve aceitar conexões externas
# Verificar docker-compose.yml:
# ports: - "0.0.0.0:8181:80"
```

## 📚 Documentação de Referência

### Documentação do Projeto
- [Guia da documentação](../DOCUMENTACAO.md) ⭐ **LEIA PRIMEIRO**
- [Manual do Usuário](../docs/MANUAL_USUARIO.md)
- Histórico (arquivado): [Resumo das Correções](../docs/legado/mobile/RESUMO_CORRECOES.md), [Guia de Teste Rápido](../docs/legado/mobile/TESTE_RAPIDO.md), [Notas Técnicas](../docs/legado/mobile/NOTAS_TECNICAS.md)
- [API Backend](../docs/api/README.md)
- [Banco de Dados](../docs/db/README.md)

### Referências Externas
- [React Native](https://reactnative.dev/)
- [Expo](https://docs.expo.dev/)
- [React Navigation](https://reactnavigation.org/)
- [AsyncStorage](https://react-native-async-storage.github.io/async-storage/)

## 👥 Equipe de Desenvolvimento

- **Grupo do Pacífico**: Backend MVP + Documentação
- **Grupo Mobile**: Implementação do app mobile
- **Grupo Frontend Desktop**: Interface web
- **Grupo do Guidini**: Módulo de pagamentos

---
