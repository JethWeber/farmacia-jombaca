# Farmácia Jombaca

Aplicação web em PHP e MySQL para gestão da farmácia, pensada para **clone limpo** do repositório e arranque com Docker.

## Pré-requisitos

- Docker e plugin **Docker Compose** (`docker compose`)

## Arranque rápido

```bash
git clone https://github.com/JethWeber/farmacia-jombaca.git
cd farmacia-jombaca
cp .env.example .env   # opcional: credenciais alinhadas com o MySQL
docker compose up --build -d
```

Aguarde o MySQL concluir o primeiro arranque (pode levar 1–3 minutos). Depois:

- Site: [http://localhost:8080](http://localhost:8080)
- Login: [http://localhost:8080/login.php](http://localhost:8080/login.php)

### Primeiro administrador

Na **primeira criação** do volume da base de dados, o script `docker/mysql/init/002_admin_seed.sql` cria um único utilizador de equipa interna (não há seed de produtos nem clientes de demonstração):

| Campo    | Valor            |
|----------|------------------|
| Telefone | `900000000`      |
| Senha    | `weber@admin666` |
| Perfil   | Admin principal  |

Altere a palavra-passe após o primeiro acesso (Gestão de utilizadores, como admin principal).

### Migrações automáticas (ALTER / tabelas novas)

Em **cada pedido HTTP** que carrega `www/config/db.php`, executam-se migrações idempotentes (colunas em falta, tabelas auxiliares como `vendas`, `pedidos_recuperacao`, etc.). Não é necessário correr scripts SQL manuais após o clone, além do bootstrap inicial do MySQL em `docker/mysql/init/`.

## Variáveis de ambiente

- Ficheiro **`.env.example`**: modelo. Copie para **`.env`** na raiz do projeto se quiser personalizar credenciais.
- O serviço **web** recebe `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`.
- O serviço **db** usa `MYSQL_*` e os mesmos nomes de base/utilizador se definir `DB_*` no `.env` (substituição do Compose).

## Perfis no painel

| Perfil              | Acesso |
|---------------------|--------|
| **Admin principal** | Painel completo: utilizadores, categorias, filiais, produtos, financeiro, etc. |
| **Admin secundário**| Painel sem funções exclusivas do principal (ex.: gestão de utilizadores). |
| **Funcionário**     | Painel dedicado: validade (vencidos + 3 meses), consulta de stock/produtos, vendas, relatório dia/mês, estado das reservas. **Não** cadastra produtos nem outros administradores. |

O login com perfil **funcionário** abre `dashboard_funcionario.php`.

## Uploads de imagens

- Pastas versionadas com `.gitkeep` em `www/uploads/...`.
- No arranque do contentor **web**, o *entrypoint* cria as pastas e ajusta permissões para o Apache gravar ficheiros enviados.
- Limite de upload PHP: **16 MB** (ver `php/Dockerfile`).
- No site público, imagens em falta ou caminhos inválidos usam um **placeholder** (`assets/img/placeholder-produto.svg`) e fundo em degradê nas grelhas de produtos.

## Comandos úteis

```bash
docker compose ps
docker compose logs --tail=80 web
docker compose logs --tail=80 db
docker compose down
docker compose down --volumes   # apaga dados da BD no volume
```

## Estrutura relevante

| Caminho | Descrição |
|---------|-----------|
| `docker-compose.yml` | Serviços `web` e `db` |
| `docker/mysql/init/001_schema.sql` | Schema inicial |
| `docker/mysql/init/002_admin_seed.sql` | Apenas o admin padrão (idempotente) |
| `php/Dockerfile` | PHP 8.2 Apache + limites de upload |
| `php/docker-entrypoint.sh` | Permissões das pastas `uploads` |
| `www/config/db.php` | PDO + migrações em runtime |
| `www/config/imagem_helper.php` | URLs seguras para imagens de produto |

## Licença / uso

Projeto académico / demonstração — adapte credenciais e segurança antes de produção.
