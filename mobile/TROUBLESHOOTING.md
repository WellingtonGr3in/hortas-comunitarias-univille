# 🚨 GUIA DE SOLUÇÃO - Tela Branca no Expo

## ✅ Solução Rápida (Siga na ordem)

### **Passo 1: Garantir que ambos estão na mesma WiFi**
- PC e celular devem estar conectados à **MESMA REDE WiFi**
- Se estiver em rede corporativa/universidade, pode ter bloqueios

### **Passo 2: Usar Modo TUNNEL (Recomendado)**

1. **Abra o Explorador de Arquivos**
2. Navegue até: `mobile` (pasta do projeto)
3. **Clique duas vezes** no arquivo **`iniciar.bat`**
4. Aguarde o QR code aparecer
5. Escaneie com Expo Go
6. **IMPORTANTE**: No modo tunnel, pode demorar **2-3 minutos** para carregar na primeira vez!

### **Passo 3: Se ainda não funcionar, limpar cache**

No terminal (dentro da pasta `mobile`):

```powershell
# Adicionar Node ao PATH primeiro
$env:Path = "C:\Program Files\nodejs;" + $env:Path

# Limpar cache e reinstalar
Remove-Item -Recurse -Force node_modules
npm install --legacy-peer-deps

# Iniciar com cache limpo
npm start -- --clear
```

### **Passo 4: Verificar erros no Expo Go**

Se o app abrir mas mostrar **tela vermelha** com erro:
- **Tire um print da mensagem de erro**
- A mensagem vai dizer exatamente o que está errado

---

## 🔧 Problemas Comuns

### 1️⃣ "Network response timed out"
**Causa**: PC e celular não estão se comunicando  
**Solução**: 
- Use o modo `--tunnel`: `npm start -- --tunnel`
- Ou desabilite firewall temporariamente

### 2️⃣ "Uncaught Error: ..."
**Causa**: Algum arquivo JavaScript tem erro  
**Solução**: 
- Tire um print do erro completo
- Me mostre para eu corrigir

### 3️⃣ "Cannot connect to Metro"
**Causa**: Servidor Expo não está rodando  
**Solução**: 
- Reinicie o servidor
- Use `npm start -- --reset-cache`

### 4️⃣ Tela branca infinita (sem erro)
**Causa**: App travou na inicialização  
**Solução**:
- Feche completamente o Expo Go
- Force-stop o app nas configurações do Android
- Limpe o cache do Expo Go
- Escaneie o QR code novamente

---

## 🎯 Melhor Método (Mais Confiável)

### Usando Túnel com @expo/ngrok

```powershell
# No terminal, dentro da pasta mobile:
$env:Path = "C:\Program Files\nodejs;" + $env:Path

# Instalar ngrok global
npm install -g @expo/ngrok

# Iniciar com tunnel
npm start -- --tunnel
```

O modo tunnel **sempre funciona**, mesmo em redes complicadas, mas é mais lento (2-3 min para carregar).

---

## 📱 Checklist de Verificação

Antes de escanear o QR code, confirme:

- [ ] Node.js instalado (verificar: `node --version`)
- [ ] Dentro da pasta `mobile`
- [ ] `node_modules` existe (se não: `npm install --legacy-peer-deps`)
- [ ] Servidor Expo rodando (deve mostrar QR code)
- [ ] Expo Go instalado no celular
- [ ] Celular e PC na mesma WiFi (ou usando `--tunnel`)
- [ ] Aguardou pelo menos 2-3 minutos na primeira vez

---

## 🆘 Se NADA funcionar

Execute isto e me mande o resultado completo:

```powershell
cd mobile
$env:Path = "C:\Program Files\nodejs;" + $env:Path
npm start -- --tunnel > log.txt 2>&1
```

Aguarde o QR code, escaneie, aguarde 3 minutos, e depois:
- Me mostre o conteúdo de `log.txt`
- Tire print da tela do celular (mesmo que branca)
- Diga se apareceu algum erro vermelho

---

## 🎉 Quando Funcionar

Você deve ver a **tela de LOGIN** com:
- Campo CPF/CNPJ
- Campo Senha  
- Botão "Entrar"
- Link "Cadastrar"

Se viu isso, funcionou! 🚀

---

**Próximo passo**: Configurar a API backend com ngrok (veja [DICAS.md](../docs/legado/mobile/DICAS.md))
