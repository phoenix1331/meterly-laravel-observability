.PHONY: help up down build shell migrate seed test logs fresh demo horizon-logs

help: ## show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "%-10s %s\n", $$1, $$2}'

up: ## start the stack in the background
	@docker compose up -d

down: ## stop the stack
	@docker compose down

build: ## rebuild the app image
	@docker compose build

shell: ## open a shell in the app container
	@docker compose exec app bash

migrate: ## run database migrations
	@docker compose exec app php artisan migrate

seed: ## seed the database
	@docker compose exec app php artisan db:seed

test: ## run the test suite
	@docker compose exec app php artisan test

logs: ## tail the app container logs
	@docker compose logs -f app

horizon-logs: ## tail the horizon queue worker logs
	@docker compose logs -f horizon

fresh: ## drop all tables and re-migrate with seed data
	@docker compose exec app php artisan migrate:fresh --seed

demo: ## bring up the stack, migrate, seed, and start the traffic simulator
	@docker compose up -d
	@docker compose exec app php artisan migrate --force
	@docker compose exec app php artisan db:seed
	@docker compose exec -d app php artisan app:simulate-traffic
	@echo "demo stack is up: http://localhost:8000"
	@echo "grafana: http://localhost:3000"
	@echo "traffic simulator running in the background (10 minutes, one scripted incident at 2 minutes)"
