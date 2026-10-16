> [!NOTE]
> **Documento histórico.** Arquivado em 15/10/2026 (estava em `mobile/SOLUCAO_RAPIDA.md`). Pode conter credenciais, URLs e instruções que não valem mais. A organização atual da documentação está no [DOCUMENTACAO.md](../../../DOCUMENTACAO.md).

# 🚨 ERRO: Token Não Recebido - SOLUÇÃO RÁPIDA

## ⚡ Execute AGORA

### 1️⃣ Testar se Backend Está Funcionando

```powershell
# No PowerShell, execute:
.\testar-api.ps1
```

**Isso vai:**
- ✅ Verificar se API responde
- ✅ Criar um usuário de teste
- ✅ Tentar fazer login
- ✅ Mostrar se token está sendo retornado

---

### 2️⃣ Ver os Resultados

**Se mostrar**: `🎫 TOKEN ENCONTRADO`
- ✅ API está OK, problema é no app

**Se mostrar**: `❌ TOKEN NÃO ENCONTRADO`
- ❌ API não está retornando token (problema no backend)

---

### 3️⃣ Testar no App

**Use as credenciais de teste**:
- **E-mail**: `teste.api@email.com`
- **Senha**: `senha123`

---

## 🔧 Se Backend NÃO Retornar Token

O problema está no backend. Verifique:

```powershell
# Ver logs do backend
cd backend
docker-compose logs -f php
```

**Pode ser**:
- Controlador de Sessão não está retornando token
- Rota não está configurada
- Banco de dados sem dados

---

## 🔧 Se App Continuar com Erro

**Mesmo com API retornando token**, o problema pode ser:

### Solução A: URL Errada

```javascript
// Editar: mobile/src/constants/config.js

// Se estiver em emulador Android:
export const API_URL = 'http://10.0.2.2:8181/api';

// Se estiver em device físico, usar seu IP:
export const API_URL = 'http://192.168.X.X:8181/api';
```

**Descobrir seu IP**:
```powershell
ipconfig
# Procurar "IPv4 Address" na rede Wi-Fi
```

### Solução B: Limpar Cache

```powershell
cd mobile
npx expo start -c
```

---

## 📋 Checklist Rápido

Execute em ordem:

1. [ ] `.\testar-api.ps1` → API retorna token?
2. [ ] Backend está rodando? `docker-compose ps`
3. [ ] URL da API está correta? Verificar `config.js`
4. [ ] Cache limpo? `npx expo start -c`
5. [ ] Credenciais corretas? `teste.api@email.com` / `senha123`

---

## 🎯 O Código Já Está Corrigido

O código do mobile JÁ tem:
- ✅ Logs detalhados (veja no console)
- ✅ Suporte para múltiplos formatos de token
- ✅ Tratamento robusto de erros
- ✅ Fallback se usuário não vier

**O problema provavelmente é**:
1. Backend não está rodando
2. Backend não retorna token
3. URL da API está errada no app

---

## 🚀 EXECUTE AGORA

```powershell
# 1. Testar API
.\testar-api.ps1

# 2. Ver resultado e ajustar conforme necessário

# 3. Iniciar app com cache limpo
npx expo start -c

# 4. Olhar logs no console do Expo
```

---

## 📞 O Que Olhar nos Logs

No console do Expo, procure por:

```
✅ BOM:
🌐 API URL configurada: http://localhost:8181/api
🔐 Tentando login com: teste.api@email.com
📦 Resposta da API: { "token": "..." }
🎫 Token encontrado? true
✅ Token salvo no AsyncStorage

❌ PROBLEMA:
🔐 Tentando login com: teste.api@email.com
📦 Resposta da API: { ... }  ← SEM TOKEN
🎫 Token encontrado? false
❌ Token não encontrado na resposta
```

