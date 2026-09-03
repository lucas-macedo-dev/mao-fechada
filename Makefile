# mao_fechada — atalhos de desenvolvimento
# Uso: make <comando>

.PHONY: up down build restart logs shell-api shell-web shell-redis migrate fresh seed test pint pint-fix stan redis-keys redis-get redis-flush redis-info redis-monitor redis-dbsize

# ─── Docker ───────────────────────────────────────────────────────
up:
	docker compose up -d

down:
	docker compose down

build:
	docker compose build --no-cache

restart:
	docker compose restart

logs:
	docker compose logs -f

logs-api:
	docker compose logs -f api nginx

logs-web:
	docker compose logs -f web

# ─── Shells ───────────────────────────────────────────────────────
shell-api:
	docker compose exec api sh

shell-web:
	docker compose exec web sh

shell-db:
	docker compose exec db mysql -u mao_fechada -psecret mao_fechada

shell-redis:
	docker compose exec redis redis-cli

# ─── Laravel ──────────────────────────────────────────────────────
install:
	docker compose exec api composer install

migrate:
	docker compose exec api php artisan migrate

fresh:
	docker compose exec api php artisan migrate:fresh --seed

seed:
	docker compose exec api php artisan db:seed

artisan:
	docker compose exec api php artisan $(filter-out $@,$(MAKECMDGOALS))

cache-clear:
	docker compose exec api php artisan optimize:clear

# ─── Redis ────────────────────────────────────────────────────────
# Uso:
#   make redis-keys              -> lista as chaves do db de cache (db 1)
#   make redis-keys DB=0         -> lista as chaves do db padrão (filas/sessões)
#   make redis-get KEY=algo      -> mostra o valor de uma chave (db 1 por padrão)
#   make redis-flush DB=1        -> apaga TODAS as chaves de um db (exige DB=)
#   make redis-dbsize            -> conta as chaves de cada db (0 e 1)
redis-keys:
	docker compose exec redis redis-cli -n $(if $(DB),$(DB),1) keys '*'

redis-get:
	docker compose exec redis redis-cli -n $(if $(DB),$(DB),1) get '$(KEY)'

redis-flush:
	@if [ -z "$(DB)" ]; then echo "Uso: make redis-flush DB=<0|1>"; exit 1; fi
	docker compose exec redis redis-cli -n $(DB) flushdb

redis-info:
	docker compose exec redis redis-cli info

redis-monitor:
	docker compose exec redis redis-cli monitor

redis-dbsize:
	@echo "db0 (default — filas/sessões):" && docker compose exec redis redis-cli -n 0 dbsize
	@echo "db1 (cache):" && docker compose exec redis redis-cli -n 1 dbsize

# ─── Qualidade de código ──────────────────────────────────────────
# Uso:
#   make pint              -> checa (dry-run) o projeto inteiro
#   make pint FILES=path   -> checa (dry-run) arquivos/pastas específicos
#   make pint-fix          -> corrige o projeto inteiro
#   make pint-fix FILES=path -> corrige arquivos/pastas específicos
#   make stan               -> analisa o projeto inteiro
#   make stan FILES=path    -> analisa arquivos/pastas específicos
pint:
	docker compose exec api ./vendor/bin/pint --test $(FILES)

pint-fix:
	docker compose exec api ./vendor/bin/pint $(FILES)

stan:
	docker compose exec api ./vendor/bin/phpstan analyse $(FILES)

# ─── NPM ───────────────────────────────────────────────────────

compile	:
	docker compose exec web npm run build

# ─── Setup inicial ────────────────────────────────────────────────
setup:
	cp api/.env.example api/.env
	docker compose build
	docker compose up -d
	sleep 5
	docker compose exec api composer install
	docker compose exec api php artisan key:generate
	docker compose exec api php artisan migrate
	@echo ""
	@echo "✅ Ambiente pronto!"
	@echo "   API:   http://localhost:8000"
	@echo "   React: http://localhost:5173"