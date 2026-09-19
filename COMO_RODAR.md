# como rodar

requisitos: windows, powershell e internet no primeiro uso.

na raiz do projeto, rode:

```powershell
.\scripts\start-local.ps1
```

no primeiro uso ele baixa e configura tudo. depois, acesse:

- sistema: <http://localhost:3000>
- login: `admin@email.com`
- senha: `admin`

para encerrar:

```powershell
.\scripts\stop-local.ps1
```

o banco fica salvo para o próximo uso.
