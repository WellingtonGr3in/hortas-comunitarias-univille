> [!NOTE]
> **Documento histórico.** Arquivado em 15/10/2026 (estava em `mobile/ERRO_TOKEN.md`). Pode conter credenciais, URLs e instruções que não valem mais. A organização atual da documentação está no [DOCUMENTACAO.md](../../../DOCUMENTACAO.md).

# 🚨 TROUBLESHOOTING - Erro "Token Não Recebido"

## ❌ Erro Relatado
```
"Token não recebido da API"
```

## 🔍 Diagnóstico Rápido

### 1️⃣ Verificar se Backend Está Rodando

```powershell
# No PowerShell
Invoke-RestMethod -Uri "http://localhost:8181/api" -Method Get
```

**Resultado Esperado**: Deve retornar algo, sem erro de conexão

**Se der erro**: Backend não está rodando ou não está acessível

---

### 2️⃣ Testar Endpoint de Login Manualmente

```powershell
# Testar login via PowerShell
$body = @{
    email = "seu@email.com"
    senha = "suasenha"
} | ConvertTo-Json

Invoke-RestMethod -Uri "http://localhost:8181/api/sessao/login" `
    -Method Post `
    -Body $body `
    -ContentType "application/json"
```

**O que você DEVE ver**:
```json
{
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
}
```

**Se não vier `token`**: API está com problema

---

### 3️⃣ Verificar Logs do Mobile

Após tentar login, procure no console do Expo:

```
✅ BOM:
🔐 Tentando login com: seu@email.com
📦 Resposta da API: { "token": "..." }
🎫 Token encontrado? true
✅ Token salvo no AsyncStorage

❌ RUIM:
📦 Resposta da API: { "error": "..." }
🎫 Token encontrado? false
❌ Token não encontrado na resposta
```

---

## 🛠️ Soluções

### Solução 1: Backend Não Está Rodando

```powershell
# Navegar para pasta do backend
cd backend

# Iniciar com Docker
docker-compose up -d

# OU se usando PHP direto
php -S localhost:8181 -t public
```

**Testar**: Abra http://localhost:8181/api no navegador

---

### Solução 2: URL da API Está Errada

```javascript
// Editar: mobile/src/constants/config.js

// Para emulador Android
export const API_URL = 'http://10.0.2.2:8181/api';

// Para device físico (substituir IP)
export const API_URL = 'http://192.168.1.100:8181/api';

// Para iOS Simulator
export const API_URL = 'http://localhost:8181/api';
```

**Descobrir seu IP**:
```powershell
ipconfig
# Procurar por "IPv4 Address" na rede Wi-Fi
```

---

### Solução 3: API Retorna Formato Diferente

O código já foi ajustado para aceitar múltiplos formatos:
- `response.data.token`
- `response.data.data.token`
- `response.data.jwt`
- `response.data.access_token`

Mas se a API retornar em outro formato, edite:

```javascript
// mobile/src/services/auth.service.js

const token = response.data?.token || 
              response.data?.SEU_CAMPO_AQUI;  // Adicione aqui
```

---

### Solução 4: CORS Bloqueando Requisição

Se o erro for de CORS:

```php
// backend/public/index.php (adicionar no início)

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
```

---

### Solução 5: Limpar Cache e Reiniciar

```powershell
# 1. Parar Expo
Ctrl+C

# 2. Limpar cache
npx expo start -c

# 3. Limpar AsyncStorage (no código temporariamente)
# Em App.js:
import AsyncStorage from '@react-native-async-storage/async-storage';
AsyncStorage.clear();
```

---

## 📊 Checklist de Diagnóstico

Execute em ordem:

- [ ] **Backend está rodando?**
  ```powershell
  docker ps
  # OU
  netstat -an | findstr "8181"
  ```

- [ ] **API responde?**
  ```powershell
  Invoke-RestMethod -Uri "http://localhost:8181/api" -Method Get
  ```

- [ ] **Login manual funciona?**
  ```powershell
  # Teste com PowerShell (código acima)
  ```

- [ ] **Console do mobile mostra logs?**
  ```
  Procurar: 🔐 Tentando login...
  ```

- [ ] **Token aparece nos logs?**
  ```
  Procurar: 🎫 Token encontrado? true
  ```

---

## 🔬 Debug Avançado

### Ver TODAS as requisições:

```javascript
// mobile/src/services/api.js
// (já está implementado)

api.interceptors.request.use(config => {
  console.log('📤 REQUEST:', config.url);
  console.log('📦 DATA:', config.data);
  console.log('🔑 HEADERS:', config.headers);
  return config;
});

api.interceptors.response.use(
  response => {
    console.log('✅ RESPONSE:', response.status);
    console.log('📦 DATA:', response.data);
    return response;
  },
  error => {
    console.log('❌ ERROR:', error.message);
    console.log('📦 RESPONSE:', error.response?.data);
    return Promise.reject(error);
  }
);
```

### Testar API com Postman/Insomnia:

```
POST http://localhost:8181/api/sessao/login
Content-Type: application/json

{
  "email": "teste@email.com",
  "senha": "senha123"
}
```

**Deve retornar**:
```json
{
  "token": "eyJhbGc..."
}
```

---

## 🎯 Causa Mais Comum

**90% das vezes é um destes**:

1. ✅ **Backend não está rodando** → `docker-compose up -d`
2. ✅ **URL errada** → Trocar para `10.0.2.2` (Android) ou IP local
3. ✅ **Usuário não existe** → Fazer cadastro primeiro
4. ✅ **E-mail/senha errados** → Verificar credenciais

---

## 📞 Se Nada Funcionar

### 1. Testar backend isoladamente:

```powershell
# Criar usuário de teste direto no banco
# OU
# Usar Postman para fazer cadastro + login
```

### 2. Verificar logs do backend:

```powershell
cd backend
docker-compose logs -f php
# Ou
docker-compose logs -f nginx
```

### 3. Testar com usuário de teste fixo:

```javascript
// TEMPORÁRIO para debug
// mobile/src/services/auth.service.js

export const login = async (email, senha) => {
  // Simular login funcionando
  const fakeToken = 'fake-token-para-teste';
  const fakeUser = { email, nome: 'Teste' };
  
  await AsyncStorage.setItem(CONFIG.tokenKey, fakeToken);
  await AsyncStorage.setItem(CONFIG.userKey, JSON.stringify(fakeUser));
  
  return { token: fakeToken, usuario: fakeUser };
};
```

**Isso testa se o problema é na API ou no app**

---

## ✅ Solução Aplicada no Código

O código JÁ foi atualizado com:

1. ✅ Logs detalhados de debug
2. ✅ Suporte para múltiplos formatos de resposta
3. ✅ Tratamento de erro robusto
4. ✅ Fallback se `/usuarios/me` não existir
5. ✅ Limpeza automática em caso de erro

**Agora execute e veja os logs!**

---

## 🚀 Próximo Passo

```powershell
# 1. Verificar backend
cd backend
docker-compose up -d
docker-compose ps

# 2. Testar API manualmente
Invoke-RestMethod -Uri "http://localhost:8181/api" -Method Get

# 3. Reiniciar mobile com cache limpo
cd mobile
npx expo start -c

# 4. Ver logs no console enquanto tenta login
```

**IMPORTANTE**: Leia os logs no console do Expo. Eles vão te dizer exatamente o que está errado! 🔍
