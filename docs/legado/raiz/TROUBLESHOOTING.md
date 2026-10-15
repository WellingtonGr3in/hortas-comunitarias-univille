> [!NOTE]
> **Documento histórico.** Arquivado em 15/10/2026 (estava em `TROUBLESHOOTING.md`). Pode conter credenciais, URLs e instruções que não valem mais. A organização atual da documentação está no [DOCUMENTACAO.md](../../../DOCUMENTACAO.md).

# 🚨 Solução para o Problema do Frontend

## Problema Identificado
O erro `vue-cli-service: not found` ocorre porque o Vue CLI Service não está instalado no container do frontend.

## 🛠️ Soluções Possíveis

### **Opção 1: Executar Frontend Localmente (Recomendado para desenvolvimento)**

Se você tem Node.js instalado localmente:

```bash
# 1. Navegar para a pasta do frontend
cd frontend

# 2. Instalar dependências
npm install

# 3. Instalar Vue CLI globalmente (se não tiver)
npm install -g @vue/cli @vue/cli-service

# 4. Iniciar servidor de desenvolvimento
npm run serve
```

O frontend estará disponível em: http://localhost:3000

### **Opção 2: Corrigir Container Docker**

Se preferir usar Docker:

```bash
# 1. Parar todos os containers
docker-compose down

# 2. Remover volumes órfãos
docker system prune -f

# 3. Reiniciar Docker Desktop (se estiver usando macOS)

# 4. Tentar novamente
make start
```

### **Opção 3: Híbrida (Backend no Docker, Frontend Local)**

Esta é uma abordagem comum no desenvolvimento:

```bash
# Backend e banco no Docker
docker-compose up -d mysql php nginx phpmyadmin redis

# Frontend local
cd frontend && npm install && npm run serve
```

## 🔧 URLs Após Correção

- **Frontend**: http://localhost:3000
- **Backend API**: http://localhost:8181/api  
- **phpMyAdmin**: http://localhost:8080
- **MySQL**: localhost:3306

## 🐛 Se o problema persistir

1. **Verificar se Docker Desktop está funcionando:**
   ```bash
   docker --version
   docker-compose --version
   ```

2. **Limpar completamente o ambiente:**
   ```bash
   make clean  # ou
   docker-compose down -v --rmi all
   ```

3. **Executar backend em modo híbrido:**
   ```bash
   # Apenas serviços essenciais
   docker-compose up -d mysql nginx php phpmyadmin
   
   # Frontend local
   cd frontend && npm run serve
   ```

## ✅ Verificação

Para testar se tudo está funcionando:

1. **Backend**: http://localhost:8181/api 
2. **Frontend**: http://localhost:3000
3. **phpMyAdmin**: http://localhost:8080
4. **Banco**: Conectar via phpMyAdmin ou cliente MySQL

---

**💡 Dica:** Para desenvolvimento, é comum executar o frontend localmente pois oferece melhor experiência com hot-reload e debug.
