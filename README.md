# Farmacia Jombaca

Aplicacao web para gestao e operacao da Farmacia Jombaca, executada com PHP + MySQL em ambiente Docker.

## Visao geral

Este projeto foi preparado para funcionar em ambiente limpo (fresh clone), com:

- bootstrap automatico do schema da base de dados;
- criacao/garantia automatica de utilizador administrador padrao;
- stack completa orquestrada por `docker compose`.

## Stack tecnica

- PHP 8.2 + Apache
- MySQL 8.0
- Docker Compose

## Pre-requisitos

- Docker instalado e em execucao
- Docker Compose (comando `docker compose`)

## Arranque rapido (clone limpo)

```bash
git clone https://github.com/JethWeber/farmacia-jombaca.git
cd farmacia-jombaca
docker compose up --build -d
```

> Nota: no primeiro arranque, o MySQL pode demorar alguns minutos para inicializar totalmente e executar os scripts de bootstrap.

## Endpoints locais

- Aplicacao: [http://localhost:8080](http://localhost:8080)
- Login: [http://localhost:8080/login.php](http://localhost:8080/login.php)
- MySQL (host): `localhost:3306`

## Administrador padrao garantido

O sistema garante que a plataforma nunca fica sem administrador. O utilizador abaixo e criado/atualizado automaticamente:

- Nome: `Weber Admin`
- Email: `weber@admin.com`
- Telefone: `900000000`
- Senha: `weber@admin666`
- Perfil: `admin`

## Verificacao pos-arranque

### 1) Confirmar estado dos servicos

```bash
docker compose ps
```

### 2) Validar schema e admin padrao

```bash
docker compose exec db mysql -uroot -pjombacaRoot farmacia_jombaca -e "SHOW TABLES; SELECT id,nome_completo,email,telefone,role FROM usuarios WHERE email='weber@admin.com' OR telefone='900000000';"
```

## Operacao

### Parar os servicos

```bash
docker compose down
```

### Reiniciar os servicos

```bash
docker compose up -d
```

### Reset total da base (ambiente limpo)

```bash
docker compose down --volumes --remove-orphans
docker compose up --build -d
```

## Estrutura importante

- `docker-compose.yml`: definicao dos servicos `web` e `db`
- `docker/mysql/init/001_schema.sql`: schema inicial da base de dados
- `docker/mysql/init/002_admin_seed.sql`: seed idempotente do admin padrao
- `www/config/db.php`: conexao PDO e garantia de admin em runtime

## Troubleshooting

### Erro de conexao com MySQL no primeiro boot

Se aparecer `Connection refused` logo apos o `up`, aguarde alguns instantes e valide novamente. O primeiro bootstrap do MySQL pode ser mais lento.

### Confirmar logs dos servicos

```bash
docker compose logs --tail=120 db
docker compose logs --tail=120 web
```

