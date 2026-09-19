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
- login: `admin@email.com`
- senha: `admin`

para encerrar:

```powershell
powershell.exe -executionpolicy bypass -file .\scripts\stop-local.ps1
```

o banco fica salvo para o próximo uso.

---

> **TUDO SEMPRE DENTRO DA PASTA RAIZ DO PROJETO, PODE ABRIR USAR O TERMINAL DA PROPRIA IDE**
