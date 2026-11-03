# como rodar

## SO RODAR O **iniciar.bat**

---

## OU

**requisitos:** windows, powershell e internet no primeiro uso.

na raiz do projeto, rode:

```powershell
.\scripts\start-local.ps1
```

se aparecer erro de permissão, use:

```powershell
powershell.exe -executionpolicy bypass -file .\scripts\start-local.ps1
```

no primeiro uso ele baixa e configura tudo. depois, acesse:

- url: <http://localhost:3000>
- senha de todos os usuários de teste: `123456`
  - `plataforma@gmail.com`: administrador da plataforma
  - `associacao@gmail.com`: administrador da associação demonstração
  - `horta@gmail.com`: administrador da horta demonstração
  - `canteirista@gmail.com`: canteirista da horta demonstração

para encerrar:

```powershell
.\scripts\stop-local.ps1
```
OU
```powershell
powershell.exe -executionpolicy bypass -file .\scripts\stop-local.ps1
```
o banco fica salvo para o próximo uso.

---

> **TUDO SEMPRE DENTRO DA PASTA RAIZ DO PROJETO, PODE ABRIR USAR O TERMINAL DA PROPRIA IDE**
