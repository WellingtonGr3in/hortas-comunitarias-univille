# Documentação do projeto

Este arquivo diz onde está cada documento do projeto e qual deles vale hoje. O repositório juntou documentação de três turmas diferentes, e muita coisa se contradizia: credenciais, endereços da API e até arquivos citados que nunca existiram. Em 15/10/2026 os documentos antigos foram separados em `docs/legado/`.

Regra geral: se um documento atual e um documento do legado discordarem, vale o atual. Se o atual também estiver errado, vale o código, e o documento deve ser corrigido.

## Documentos atuais

| Documento | Para que serve |
| --- | --- |
| [README.md](README.md) | Apresentação do projeto, grupos e tecnologias |
| [COMO_RODAR.md](COMO_RODAR.md) | Jeito mais rápido de rodar o sistema no Windows (`iniciar.bat` ou `scripts/start-local.ps1`) |
| [DEVELOPMENT.md](DEVELOPMENT.md) | Ambiente com Docker e comandos do `Makefile` |
| [PORTAINER.md](PORTAINER.md) | Painel web para gerenciar os containers Docker |
| [backend/README.md](backend/README.md) | Como rodar e configurar a API (com e sem Docker) |
| [docs/api/README.md](docs/api/README.md) | Rotas da API REST, regras de negócio e permissões por cargo |
| [docs/db/README.md](docs/db/README.md) | Esquema do banco de dados |
| [docs/MANUAL_USUARIO.md](docs/MANUAL_USUARIO.md) | Manual de uso por perfil e glossário, para quem não é de TI |
| [frontend/README.md](frontend/README.md) | Como rodar e configurar o site (Vue.js) |
| [frontend/INTEGRACAO.md](frontend/INTEGRACAO.md) | Como o site conversa com a API |
| [frontend/EXEMPLOS.md](frontend/EXEMPLOS.md) | Exemplos de código para chamar a API no site |
| [mobile/README.md](mobile/README.md) | Visão geral do aplicativo (React Native + Expo) |
| [mobile/QUICKSTART.md](mobile/QUICKSTART.md) | Como rodar o aplicativo no celular |
| [mobile/DEPLOY.md](mobile/DEPLOY.md) | Como gerar o APK e publicar o aplicativo |
| [mobile/TROUBLESHOOTING.md](mobile/TROUBLESHOOTING.md) | Solução para tela branca e erros de conexão no Expo |
| [postman/README.md](postman/README.md) | Coleções do Postman para testar a API |

## Acesso de teste

Os três conjuntos de credenciais que apareciam na documentação antiga foram conferidos contra os scripts SQL do backend (`backend/src/Utils/SQL`). As senhas foram checadas contra os hashes gravados nesses scripts, não com o sistema rodando.

| Usuário | Senha | Perfil | Criado por |
| --- | --- | --- | --- |
| `admin@email.com` | `admin` | Administrador da plataforma | `03_SQL_criar_admin.sql` |
| `hortas_comunitarias@univille.br` | `senha12345` | Administrador da plataforma | `01_SQL_seed_dados_iniciais.sql` |
| `admin_assoc_1@example.com` | `senha12345` | Administrador de associação | `02_SQL_seed_dados_teste.sql` |
| `admin_horta_1@example.com` | `senha12345` | Administrador de horta | `02_SQL_seed_dados_teste.sql` |
| `canteirista_1@example.com` | `senha12345` | Canteirista | `02_SQL_seed_dados_teste.sql` |

Os scripts rodam automaticamente pelo `backend/run-migrations.php`, que o `scripts/start-local.ps1` chama. Existem também versões `_2` dos usuários de teste (por exemplo `canteirista_2@example.com`), com a mesma senha.

Credenciais que **não** valem mais:

| Onde aparecia | Usuário | Motivo |
| --- | --- | --- |
| `CREDENCIAIS.md` e `update-admin.sql` | `admin@admin.com` | Os dois arquivos foram removidos no PR #1 (14/10/2026). Um banco novo não tem esse usuário |
| `API_CORRIGIDA.md`, `COMO_TESTAR_MOBILE.md`, `CORRECOES_MOBILE.md` (legado) | `maria.nova@email.com` e similares, senha `senha123` | Contas criadas à mão no computador de quem escreveu o documento. Não existem nos scripts |

## Endereço da API

| Ambiente | Endereço |
| --- | --- |
| Local (Docker ou `start-local.ps1`) | `http://localhost:8181/api/v1` |
| Local, backend rodando com `php -S` (ver `backend/README.md`) | `http://localhost:8000/api/v1` |
| Celular na mesma rede | `http://<IP do computador>:8181/api/v1` |
| Produção | `https://hortas-comunitarias-univille-production.up.railway.app/api/v1` |

O `/v1` é obrigatório: as rotas ficam no grupo `/api/v1` em `backend/src/Routes/IndexRoutes.php`. Endereços sem `/v1`, ou com IPs fixos como `192.168.0.22`, aparecem só no legado.

## Legado

A pasta [docs/legado](docs/legado/README.md) guarda 20 documentos de turmas anteriores: relatórios de correção, guias de teste de uma fase específica, listas de próximos passos já superadas. Eles foram movidos e não apagados, porque ainda servem como histórico de decisões. Cada um tem um aviso no topo dizendo de onde veio.

Dois deles ainda têm conteúdo aproveitável: `docs/legado/raiz/CHECKLIST_TESTE.md`, com casos de teste do aplicativo, e `docs/legado/raiz/SUGESTOES_MELHORIAS_FUTURAS.md`, com ideias de funcionalidades.

## Como manter

- Documento novo vai para `docs/` e entra na tabela de documentos atuais deste sumário.
- Documento que deixou de valer vai para `docs/legado/`, com o aviso de histórico no topo. Não apague.
- Credencial, porta ou endereço de API só se documenta depois de conferir no código ou nos scripts SQL.
