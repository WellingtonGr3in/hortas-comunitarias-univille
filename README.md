[![backend-ci](https://github.com/lvcaspacifico/hortas-comunitarias-univille/actions/workflows/backend-ci.yml/badge.svg)](https://github.com/lvcaspacifico/hortas-comunitarias-univille/actions/workflows/backend-ci.yml) [![geracao-docs-api](https://github.com/lvcaspacifico/hortas-comunitarias-univille/actions/workflows/api-doc.yml/badge.svg?branch=main)](https://github.com/lvcaspacifico/hortas-comunitarias-univille/actions/workflows/api-doc.yml)

# 🌱 Hortas Comunitárias + Univille

Sistema de gestão de hortas comunitárias desenvolvido na disciplina de Vivências de Extensão V pelos graduandos dos cursos de Engenharia de Software e Sistemas de Informação da Univille.

Esse fork do [repositório original](https://github.com/thiagohome2/hortas-comunitarias-univille)🔗 constrói e entrega o MVP do backend da aplicação, disponibilizando todo o esquema de banco de dados, regras de negócio mínimas em uma API REST, bem como os frontends para desktop (aplicação web) e para mobile (aplicativo).

> [!CAUTION]
> Cabe a cada grupo atualizar esse e outros `README.md` relevantes a sua contribuição no projeto. O mesmo vale para as Issues. Não nos responsabilizamos por informações faltantes decorrentes do não preenchimento. A manutenção do repositório é da responsabilidade de todos.

> [!TIP]
> Não sabe por onde começar? O [DOCUMENTACAO.md](DOCUMENTACAO.md) lista todos os documentos do projeto, as credenciais de teste que funcionam e o endereço certo da API. O [Manual do Usuário](docs/MANUAL_USUARIO.md) explica o sistema para quem vai usá-lo.

## 🟩 Backend

> **Status**: MVP Completo e Funcional ✅ 

A documentação do banco de dados está [disponível aqui](https://github.com/lvcaspacifico/hortas-comunitarias-univille/blob/main/docs/db/README.md) 🔗.

A  documentação da API REST está [disponível aqui](https://lvcaspacifico.github.io/hortas-comunitarias-univille/) 🔗.

Conteúdo entregue/documentado nos links acima:

+ Esquema do banco de dados
+ Endpoints da API REST
+ Contexto/valores específicos aceitos de alguns campos
+ Relacionamentos entre entidades
+ Envio/retorno obrigatório de cada rota
+ Quais os dados retornados de acordo com cada cargo
+ Coleção de templates do Postman para testes automatizados ou manuais [ficam aqui](postman)

    
#### 🟢 Membros do grupo

- Lucas Pacífico | [@lvcaspacifico](https://github.com/lvcaspacifico)
- João Alencar | [@j-alencar](https://github.com/j-alencar)
- Marcelo Fiedler | [@MarceloAFiedler](https://github.com/MarceloAFiedler)
- Felipe Mourão | [@thekogami](https://github.com/thekogami)
- Marcos Will | [@Marcoswill0101](https://github.com/Marcoswill0101)

---

## 🟩 Frontend Desktop

#### 🟢 Membros do grupo

- Nathan Bergmann | [@NathanBergmann](https://github.com/NathanBergmann)
- Vitor Luis Cagneti | [@vitorluiscagneti](https://github.com/vitorluiscagneti)

Colaborações de outros grupos:

- Felipe Mourão | [@thekogami](https://github.com/thekogami)

## 🟩 Integração com Gateway de Pagamentos

> **Status**: NDA ❌

#### 🟢 Membros do grupo

- ⛓️‍💥 NDA

---

## 🟩 Frontend Mobile

> **Status**: MVP Completo e Funcional ✅ 

O aplicativo mobile foi desenvolvido em **React Native com Expo** e está 100% funcional, oferecendo:

### ✨ Funcionalidades Implementadas
- ✅ **Autenticação completa** (Login, Cadastro, Logout com JWT)
- ✅ **CRUD de Hortas** (Criar, Listar, Editar, Excluir, Detalhes)
- ✅ **CRUD de Canteiros** (Criar, Listar, Editar, Excluir, Detalhes)
- ✅ **Gerenciamento de Perfil** (Visualizar e editar dados do usuário)
- ✅ **Navegação por Tabs** (Home, Hortas, Canteiros, Perfil)
- ✅ **Validações** (CPF/CNPJ, E-mail, Senha, etc.)
- ✅ **Integração completa** com a API REST do backend

### 🚀 Como Executar
```bash
cd mobile
npm install
npm start
# Escanear QR code com Expo Go no celular
```

📖 **Documentação completa**: [`mobile/README.md`](mobile/README.md)  
⚡ **Guia rápido**: [`mobile/QUICKSTART.md`](mobile/QUICKSTART.md)

#### 🟢 Membros do grupo

- José Pedro | [@sejodrope](https://github.com/sejodrope)
- Artur Bogo | [@bogoartur](https://github.com/bogoartur)
- Paloma Teixeira | [@Fxxkittxr](https://github.com/Fxxkittxr)
- Fabiana Pauli | [@FabianaPauli](https://github.com/FabianaPauli)
- Arthur Francisco | [@arthurrfrancis](https://github.com/arthurrfrancis)
- Lucas | [@lucasanc20](https://github.com/lucasanc20)
- Henrique Marcelinho | [@riqueolvr](https://github.com/riqueolvr)

---

## 🌱 Detalhes técnicos do projeto

**Backend**
- **PHP 8.2** com **Slim Framework 4**
- **MySQL 8.0** para banco de dados
- **JWT** para autenticação
- **Nginx** como servidor web
- **Redis** para cache e sessões

**Frontend Desktop**
- **Vue.js 3** com Composition API
- **Vue Router 4** para roteamento
- **Vuex 4** para gerenciamento de estado
- **Bootstrap 5** para UI
- **Leaflet** para mapas

**Frontend Mobile**
- **React Native** 0.72.6
- **Expo** ~49.0.0 para desenvolvimento
- **React Navigation** 6.x para navegação
- **Axios** para requisições HTTP
- **AsyncStorage** para persistência local

**DevOps**
- **Docker** e **Docker Compose**
- **Nginx** como proxy reverso
- **phpMyAdmin** para administração do banco


```
hortas-comunitarias-univille/
├── backend/                 # API PHP com Slim Framework
├── frontend/               # Aplicação Vue.js
├── mobile/                 # App React Native ou Vue Native
├── docker/                 # Configurações Docker
├── docker-compose.yml      # Orquestração dos serviços
├── Makefile               # Comandos úteis
└── DEVELOPMENT.md         # Guia detalhado de desenvolvimento
```

## 🤔 Como rodar

```bash
# Clone o repositório
git clone <repo-url>
cd hortas-comunitarias-univille

# Configuração automática do ambiente
make setup

# Ou use o script diretamente
./setup.sh
```

🌐 URLs dos Serviços:

- **Frontend**: http://localhost:3000
- **Backend API**: http://localhost:8181
- **phpMyAdmin**: http://localhost:8080
- **Portainer**: http://localhost:9000
- **MySQL**: localhost:3306

## 📋 Comandos Úteis

```bash
make help           # Ver todos os comandos disponíveis
make start          # Iniciar todos os containers
make stop           # Parar todos os containers
make logs           # Ver logs dos serviços
make install        # Instalar dependências
make clean          # Limpar ambiente
```

Para mais detalhes, consulte o [DEVELOPMENT.md](DEVELOPMENT.md) ou o `README.md` de cada pasta do projeto.
