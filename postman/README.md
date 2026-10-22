# Collections

As collections do Postman da API REST deste projeto moram aqui.

Elas usam os mesmos endpoints, mas, por questões de organização e ergonomia, são divididas em:

## `hortas-comunitarias`

Para geração de documentação estática. Também pode ser importada no GUI do Postman para enviar requests.

## `smoke-tests`

Estruturada sequencialmente, serve exclusivamente para testes de CI.

## `permissoes-por-perfil`

Testa a matriz de permissões do Manual do Usuário (HC-72): o que cada perfil (admin da plataforma, admin de associação, admin de horta e canteirista) pode e não pode fazer, inclusive acessar dados de outra associação ou horta. Roda na CI antes dos smoke tests e bloqueia o PR se falhar.

Não precisa de environment: a coleção entra como `admin@email.com` (criado pela migration `03`), cria duas associações, duas hortas e um usuário de cada perfil, e testa em cima desses dados. Pode rodar quantas vezes quiser no mesmo banco.

```
newman run postman/permissoes-por-perfil.postman_collection.json --env-var "BASE_URL=http://localhost:8181/api"
```

