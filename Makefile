# pao_duro — atalhos de desenvolvimento
# Uso: make <comando>

.PHONY: up down build restart logs shell-api shell-web migrate fresh seed test

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
	docker compose exec db mysql -u pao_duro -psecret pao_duro

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