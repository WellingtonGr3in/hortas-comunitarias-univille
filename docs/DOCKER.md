# Rodando com Docker

Sobe o sistema completo (banco MySQL, backend PHP e frontend Vue) com um comando, em qualquer PC com Docker.

| Serviço | Endereço |
|---|---|
| Sistema | http://localhost:3000 (login `plataforma@gmail.com`, `associacao@gmail.com`, `horta@gmail.com` ou `canteirista@gmail.com`, senha `123456`) |
| API | http://localhost:8181/api/v1 |
| MySQL | `localhost:3307` (banco `hortas`, usuário `hortas`) |
| phpMyAdmin (opcional) | http://localhost:8080 |

## 1. Instalar o Docker Desktop (Windows)

Precisa de Windows 10/11 64 bits com virtualização ligada na BIOS. O instalador pede permissão de administrador.

1. Instale o WSL 2 (uma vez só), num PowerShell **como administrador**, e reinicie o PC:

   ```powershell
   wsl --install --no-distribution
   ```

2. Baixe o instalador em https://www.docker.com/products/docker-desktop/ (Windows, AMD64).

3. Para instalar **no disco D:** (programa e imagens), rode no PowerShell como administrador, na pasta onde está o instalador:

   ```powershell
   & ".\Docker Desktop Installer.exe" install --accept-license --installation-dir=D:\Docker\DockerDesktop --wsl-default-data-root=D:\Docker\wsl
   ```

   Sem essas opções, ele instala tudo no C:. O WSL em si fica no C:, mas ocupa pouco; o que cresce (imagens, containers e o banco) vai para `D:\Docker\wsl`.

4. Abra o Docker Desktop e espere aparecer "Engine running".

Se o Docker já estava instalado no C:, dá para mover só os dados: Docker Desktop → Settings → Resources → Advanced → **Disk image location** → `D:\Docker\wsl`.

## 2. Subir o sistema

Na raiz do projeto:

```powershell
docker compose up -d --build
```

A primeira vez baixa as imagens e instala as dependências (alguns minutos). O backend aplica as migrations sozinho antes de iniciar. Depois, acesse http://localhost:3000.

Para acompanhar os logs:

```powershell
docker compose logs -f backend
```

## 3. Comandos do dia a dia

| O que fazer | Comando |
|---|---|
| Parar tudo (banco continua salvo) | `docker compose down` |
| Subir de novo | `docker compose up -d` |
| Atualizar depois de um `git pull` | `docker compose up -d --build` |
| Ver o que está rodando | `docker compose ps` |
| Abrir o phpMyAdmin | `docker compose --profile ferramentas up -d` |
| **Apagar o banco** e começar do zero | `docker compose down -v` |

O código vai **dentro** das imagens: depois de mudar arquivos, rode `docker compose up -d --build` para ver a mudança. Para desenvolver com recarregamento automático, use o `iniciar.bat` (ver `COMO_RODAR.md`).

## Senhas e segredos

As senhas do `docker-compose.yml` são só para uso local. Para trocar, crie um arquivo `.env` na raiz (ele já é ignorado pelo git):

```env
JWT_SECRET=um-segredo-grande-e-aleatorio
DB_PASSWORD=outra-senha
DB_ROOT_PASSWORD=mais-uma-senha
```

Trocar `DB_PASSWORD` depois que o banco já foi criado não muda a senha dentro dele: nesse caso, rode `docker compose down -v` para recriar o banco.

## Problemas comuns

- **"port is already allocated"**: outro programa usa a porta 3000, 8181 ou 3307. O `iniciar.bat` usa as mesmas portas; pare ele antes (`scripts\stop-local.ps1`).
- **O backend reinicia sem parar**: veja `docker compose logs backend`. Normalmente é erro numa migration.
- **"Docker Desktop is unable to start" / WSL**: confira se a virtualização está ligada na BIOS e se `wsl --status` mostra a versão 2.
