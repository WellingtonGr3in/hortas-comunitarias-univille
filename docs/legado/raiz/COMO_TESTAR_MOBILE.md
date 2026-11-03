> [!NOTE]
> **Documento histórico.** Arquivado em 15/10/2026 (estava em `COMO_TESTAR_MOBILE.md`). Pode conter credenciais, URLs e instruções que não valem mais. A organização atual da documentação está no [DOCUMENTACAO.md](../../../DOCUMENTACAO.md).

# 🚀 COMO INICIAR O APP MOBILE

## ✅ Pré-requisitos
1. Backend rodando (já está!)
2. Node.js instalado (você precisa instalar)

## 📥 Instalar Node.js (SE NÃO TIVER)

### Opção 1: Winget (Windows 11)
```powershell
winget install OpenJS.NodeJS.LTS
```

### Opção 2: Download Manual
1. Acesse: https://nodejs.org/
2. Baixe a versão LTS
3. Instale normalmente

### Verificar instalação:
```powershell
node --version
npm --version
```

## 🎯 Iniciar o App Mobile

### Passo 1: Abrir PowerShell
```powershell
cd "C:\Users\José Pedro\OneDrive\Documents\Univille\VIVÊNCIAS\hortas-comunitarias-univille\mobile"
```

### Passo 2: Instalar dependências (primeira vez)
```powershell
npm install --legacy-peer-deps
```

### Passo 3: Iniciar o servidor Expo
```powershell
npx expo start
```

**OU use o script:**
```powershell
.\iniciar.ps1
```

## 📱 Testar no Celular

### Android:
1. Instale o app **Expo Go** da Play Store
2. Abra o Expo Go
3. Escaneie o QR code que aparece no terminal

### iOS:
1. Instale o app **Expo Go** da App Store
2. Abra o Expo Go
3. Escaneie o QR code que aparece no terminal

## ⚠️ IMPORTANTE: CPF e CNPJ Válidos

O backend **VALIDA** CPF e CNPJ! Use números válidos:

### CPFs Válidos para Teste:
- `12345678909` (123.456.789-09)
- `11144477735` (111.444.777-35)
- `22233344455` (222.333.444-55)

### CNPJs Válidos para Teste:
- `11222333000181` (11.222.333/0001-81)
- `12345678000195` (12.345.678/0001-95)
- `00000000000191` (00.000.000/0001-91)

## 🧪 Testando no App

### 1. Tela de Cadastro:
**Campos Obrigatórios:**
- Nome Completo: "Maria Silva Santos"
- Apelido: "Maria"
- Email: "maria@teste.com"
- Senha: "senha123" (mínimo 6 caracteres)
- CPF: `12345678909` ← **USE CPF VÁLIDO!**
- Data de Nascimento: "15/05/1995"
- CNPJ da Associação: `11222333000181` ← **USE CNPJ VÁLIDO!**
- Razão Social: "Associação Teste Ltda"
- Nome Fantasia: "Associação Teste"

### 2. Tela de Login:
Use o usuário que você cadastrou:
- Email: "maria@teste.com"
- Senha: "senha123"

**OU** use o que já existe no backend:
- Email: "maria.nova@email.com"
- Senha: "senha123"

## ✅ O que Deve Acontecer

### Após Cadastro Bem-Sucedido:
- ✅ Receber token JWT
- ✅ Usuário logado automaticamente
- ✅ Navegar para tela principal do app
- ✅ Sem erro "Token não recebido"

### Após Login Bem-Sucedido:
- ✅ Receber token JWT
- ✅ Dados do usuário carregados
- ✅ Navegar para tela principal do app

## ❌ Erros Comuns

### "npm: not recognized"
**Solução:** Instale o Node.js

### "Token não recebido da API"
**Solução:** 
1. Verifique se o backend está rodando: `cd backend && docker-compose ps`
2. Teste a API: `cd backend && .\testar-backend.ps1`

### "CPF inválido" ou "CNPJ inválido"
**Solução:** Use CPF/CNPJ válidos listados acima

### "Usuário já existe"
**Solução:** Use email diferente ou faça login com o existente

## 📊 Status Atual

- ✅ Backend funcionando 100%
- ✅ Login retornando token
- ✅ Cadastro retornando token  
- ✅ URLs da API corrigidas (`/api/v1`)
- ✅ Endpoints corrigidos (`/sessoes` com S)
- ✅ Validação de CPF/CNPJ ativa

## 🔍 Debug

Se algo não funcionar:

### 1. Ver logs do backend:
```powershell
cd backend
docker-compose logs --tail=50 php
```

### 2. Ver logs do app mobile:
Olhe no terminal onde o Expo está rodando

### 3. Testar API diretamente:
```powershell
cd backend
.\testar-backend.ps1
```

**Tudo pronto para testar! 🎉**
