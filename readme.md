# muquirana — Ambiente de Desenvolvimento

## Estrutura do monorepo

```
muquirana/
├── api/                        # Laravel (backend)
│   ├── app/
│   ├── routes/api.php
│   ├── .env                    # criado a partir do .env.example
│   └── .env.example
├── web/                        # React + TypeScript (frontend)
│   ├── src/
│   └── .env                    # VITE_API_URL=http://localhost:8000/api
├── docker/
│   ├── nginx/
│   │   └── default.conf        # Nginx para o Laravel
│   └── php/
│       ├── Dockerfile          # PHP 8.2 + extensões + Composer
│       └── php-local.ini       # configurações de dev
├── docker-compose.yml
├── Makefile                    # atalhos úteis
└── README.md
```

---

## Portas locais

| Serviço     | Endereço                  |
|-------------|---------------------------|
| Laravel API | http://localhost:8000     |
| React Dev   | http://localhost:5173     |
| MySQL       | localhost:3306            |
| Redis       | localhost:6379            |

---

## Setup inicial (primeira vez)

```bash
# 1. Clonar o repositório
git clone git@github.com:seu-usuario/muquirana.git
cd muquirana

# 2. Rodar o setup completo (cria .env, build, migrate, key:generate)
make setup
```

Pronto. Sem instalar PHP, Node ou MySQL na máquina.

---

## Comandos do dia a dia

```bash
make up              # sobe todos os containers
make down            # derruba tudo
make logs            # acompanha logs em tempo real
make logs-api        # logs só do PHP/Nginx

make migrate         # roda migrations
make fresh           # apaga tudo e recria o banco (⚠️ destrói dados)
make shell-api       # terminal dentro do container PHP
make shell-db        # MySQL CLI

make artisan migrate:status   # qualquer comando artisan
make artisan make:controller Api/TransactionController
```

---

## Como o tráfego funciona localmente

```
Navegador
  │
  ├── http://localhost:5173  →  container web (Vite dev server)
  │                              React faz requisições para →
  │
  └── http://localhost:8000  →  container nginx
                                    └── PHP-FPM (container api)
                                            └── MySQL (container db)
                                            └── Redis (container redis)
```

O React e o Laravel **nunca compartilham a mesma porta** — exatamente como em produção, onde serão subdomínios separados (`app.` e `api.`).

---

## Diferenças entre local e produção

| | Docker local | Oracle Cloud |
|---|---|---|
| PHP | container `api` (porta 9000 interna) | `php8.2-fpm` nativo |
| MySQL | container `db` (host = `db`) | `127.0.0.1` |
| Redis | container `redis` (host = `redis`) | `127.0.0.1` |
| Nginx | container `nginx` | Nginx nativo |
| React | Vite dev server (HMR) | Arquivos estáticos compilados pelo CI |
| `.env` DB_HOST | `db` | `127.0.0.1` |

> O `.env` de produção fica no servidor e **não entra no repositório**.
> O GitHub Actions injeta só o `VITE_API_URL` via secret no build do React.

---

## Variáveis de ambiente

### api/.env (local — gerado pelo `make setup`)
Criado a partir de `api/.env.example`. Os hosts `db` e `redis`
são os nomes dos services do docker-compose.

### web/.env (local)
```env
VITE_API_URL=http://localhost:8000/api
```

### web (produção — via GitHub Actions secret)
```env
VITE_API_URL=https://api.seudominio.com/api
```