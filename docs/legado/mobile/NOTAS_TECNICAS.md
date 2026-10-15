> [!NOTE]
> **Documento histórico.** Arquivado em 15/10/2026 (estava em `mobile/NOTAS_TECNICAS.md`). Pode conter credenciais, URLs e instruções que não valem mais. A organização atual da documentação está no [DOCUMENTACAO.md](../../../DOCUMENTACAO.md).

# ⚠️ NOTAS IMPORTANTES - BACKEND vs MOBILE

## 🔴 Problemas Identificados e Corrigidos

### 1. **Endpoint `/usuarios/me` Não Existe**
**Problema**: O backend não tem endpoint para retornar usuário autenticado  
**Workaround Atual**: 
```javascript
// Após login, salvamos o token e tentamos buscar /usuarios/me
// Se não existir, precisaremos adaptar
```

**Solução Recomendada para Backend**:
```php
// backend/src/Controllers/UsuarioController.php
public function me(Request $request, Response $response): Response
{
    $usuario = $request->getAttribute('usuario'); // Do JWT middleware
    return $response->withJson($usuario);
}

// backend/src/Routes/usuarios.php  
$app->get('/usuarios/me', 'UsuarioController:me');
```

---

### 2. **API Retorna Apenas Token no Login**

**Situação Atual da API**:
```json
// POST /sessao/login
{
  "token": "eyJhbGciOiJIUzI1..."
}
```

**O que Mobile Precisa**:
```json
{
  "token": "eyJhbGciOiJIUzI1...",
  "usuario": {
    "uuid": "...",
    "nome_completo": "...",
    "email": "...",
    ...
  }
}
```

**Solução Atual no Mobile**:
1. Faz login → Recebe token
2. Salva token
3. Faz GET /usuarios/{uuid} (se souber o UUID)
4. OU decodifica JWT para pegar UUID

---

### 3. **Cadastro Requer Estrutura Complexa**

**API Espera**:
```json
{
  "associacao": {
    "cnpj": "...",
    "razao_social": "...",
    "nome_fantasia": "...",
    "url_ata_associacao_pdf": "..."
  },
  "usuario": {
    "nome_completo": "...",
    "cpf": "...",
    "email": "...",
    "senha": "...",
    "apelido": "...",
    "data_de_nascimento": "..."
  }
}
```

**Problema**: Usuário comum não tem CNPJ/Razão Social  
**Solução Atual**: Mobile usa valores padrão:
```javascript
cnpj: '00000000000000',
razao_social: userData.nome,
nome_fantasia: userData.nome,
url_ata_associacao_pdf: 'https://exemplo.com/ata.pdf'
```

**Recomendação**: Backend deveria ter 2 endpoints:
- `/sessao/cadastro-associacao` → Para criar associação completa
- `/sessao/cadastro-usuario` → Para usuário simples

---

### 4. **Login Usa E-mail, Não CPF**

**Documentação da API diz**:
```
Campo: email (string) - Obrigatório
Campo: senha (string) - Obrigatório
```

**Mas o nome da entidade sugere**: CPF/CNPJ também deveria funcionar

**Solução no Mobile**: Usamos apenas e-mail por enquanto

---

## 📝 Checklist para Backend Melhorar

### Endpoints Necessários:
- [ ] `GET /usuarios/me` → Retorna usuário do token JWT
- [ ] `PUT /usuarios/me` → Atualiza próprio perfil
- [ ] `POST /sessao/cadastro-simples` → Cadastro sem associação

### Melhorias de Response:
- [ ] Login retornar `{token, usuario}` em vez de só `{token}`
- [ ] Erros com formato consistente: `{error: string, code: string}`
- [ ] Validações retornarem detalhes: `{errors: {campo: [mensagens]}}`

### Validações:
- [ ] CPF/CNPJ validados no backend
- [ ] E-mail único no banco
- [ ] Senhas com hash bcrypt
- [ ] Campos obrigatórios claros na doc

---

## 🔧 Configurações Críticas

### mobile/src/constants/config.js
```javascript
export const API_URL = Constants.expoConfig?.extra?.apiUrl || 'http://localhost:8181/api';

// ⚠️ IMPORTANTE:
// - localhost: Só funciona no emulador Android
// - 10.0.2.2: Emulador Android (alias para localhost)  
// - SEU_IP: Device físico (ex: 192.168.1.100:8181/api)
```

### Testar no Device Físico:
```bash
# 1. Descobrir seu IP local
ipconfig  # Windows
ifconfig  # Mac/Linux

# 2. Usar IP na config
export const API_URL = 'http://192.168.1.100:8181/api';

# 3. Backend deve aceitar conexões externas
# docker-compose.yml
ports:
  - "0.0.0.0:8181:80"  # Permite acesso externo
```

---

## 🐛 Problemas Conhecidos e Soluções

### AsyncStorage Warning
```
[AsyncStorage] Passing null/undefined as value is not supported
```

**Causa**: Tentar salvar token/user que é `undefined`  
**Solução Implementada**: Validação antes de salvar
```javascript
if (!token) {
  throw new Error('Token não recebido');
}
await AsyncStorage.setItem(CONFIG.tokenKey, token);
```

---

### Loop Infinito no AuthContext
**Causa**: `useEffect` sem dependências adequadas  
**Solução Implementada**:
```javascript
useEffect(() => {
  loadStorageData();
}, []); // Array vazio = executa uma vez
```

---

### Tela Piscando
**Causa**: Multiple rerenders por estado inconsistente  
**Solução Implementada**:
1. Loading flag para controlar renderização
2. Validação de consistência token/usuário
3. Limpeza automática de dados inválidos

---

## 📚 Documentação Importante

### Leia Antes de Modificar:
1. **API**: `docs/api/README.md`
2. **Banco**: `docs/db/README.md`  
3. **Mobile**: `mobile/README.md`

### Fluxo de Autenticação:
```
┌─────────────┐
│   CADASTRO  │
└──────┬──────┘
       │ POST /sessao/cadastro
       │ {associacao, usuario}
       ↓
┌─────────────────────────┐
│ Backend cria:           │
│ - Associação            │
│ - Usuário admin         │
│ - Primeira mensalidade  │
└──────┬──────────────────┘
       │ Retorna {associacao, usuario, mensalidade}
       ↓
┌─────────────┐
│ Redireciona │
│ para LOGIN  │
└──────┬──────┘
       │
       ↓
┌─────────────┐
│    LOGIN    │
└──────┬──────┘
       │ POST /sessao/login
       │ {email, senha}
       ↓
┌─────────────────────────┐
│ Backend retorna:        │
│ {token}                 │
└──────┬──────────────────┘
       │
       ↓
┌─────────────────────────┐
│ Mobile:                 │
│ 1. Salva token          │
│ 2. Busca dados usuário  │
│ 3. Salva no AsyncStorage│
│ 4. Atualiza Context     │
└──────┬──────────────────┘
       │
       ↓
┌─────────────┐
│    HOME     │
│ (Autenticado)│
└─────────────┘
```

---

## 🎯 Pontos de Atenção

### Para Desenvolvedores Frontend:
1. ✅ Sempre validar se token existe antes de requests
2. ✅ Tratar erro 401 → Fazer logout automático
3. ✅ Usar loading states em todas operações async
4. ✅ Limpar AsyncStorage em caso de erro
5. ✅ Não confiar em dados salvos localmente

### Para Desenvolvedores Backend:
1. ✅ Retornar dados do usuário no login
2. ✅ Criar endpoint `/usuarios/me`
3. ✅ Validar CPF no servidor
4. ✅ Mensagens de erro claras e consistentes
5. ✅ CORS configurado corretamente

---

## 🚀 Próximas Implementações

### Prioridade Alta:
- [ ] Implementar refresh token
- [ ] Endpoint `/usuarios/me`
- [ ] Melhorar mensagens de erro da API
- [ ] Validação de CPF server-side

### Prioridade Média:
- [ ] Recuperação de senha
- [ ] Verificação de e-mail
- [ ] Upload de documentos (ata, estatuto)
- [ ] Foto de perfil

### Prioridade Baixa:
- [ ] Login social (Google, Facebook)
- [ ] Autenticação biométrica
- [ ] Multi-idioma
- [ ] Modo escuro

---

## 💡 Dicas de Debug

### Ver o que está no AsyncStorage:
```javascript
import AsyncStorage from '@react-native-async-storage/async-storage';

// Verificar token
const token = await AsyncStorage.getItem('@hortas:token');
console.log('Token:', token);

// Verificar usuário  
const user = await AsyncStorage.getItem('@hortas:user');
console.log('User:', JSON.parse(user));

// Limpar tudo
await AsyncStorage.clear();
```

### Ver headers da requisição:
```javascript
// Adicione no api.js
api.interceptors.request.use(
  async (config) => {
    console.log('Request:', config.url);
    console.log('Headers:', config.headers);
    console.log('Data:', config.data);
    return config;
  }
);
```

### Ver response da API:
```javascript
api.interceptors.response.use(
  (response) => {
    console.log('Response:', response.data);
    return response;
  }
);
```

---

## ✅ Status Atual (29/10/2025)

| Funcionalidade | Status | Observações |
|---------------|--------|-------------|
| Cadastro | ✅ Funcionando | Usa valores padrão para associação |
| Login | ✅ Funcionando | Com e-mail |
| Logout | ✅ Funcionando | Limpa storage |
| Persistência | ✅ Funcionando | Mantém sessão |
| Refresh Token | ❌ Não implementado | Token expira sem aviso |
| Recuperar Senha | ❌ Não implementado | - |
| Profile Update | ⚠️ Parcial | Apenas local, não sincroniza |

---

## 🏁 Conclusão

O sistema de autenticação está **funcional para MVP**, mas precisa de melhorias no backend para estar completo. As principais limitações estão documentadas e há workarounds implementados.

**Recomendação**: Priorizar implementação de `/usuarios/me` e ajustar resposta do login no backend.
