.PHONY: help up down restart logs shell composer npm artisan migrate seed queue test clean

help: ## Mostra questo messaggio di aiuto
	@echo "Comandi disponibili:"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2}'

up: ## Avvia i container Docker
	docker-compose up -d

down: ## Ferma i container Docker
	docker-compose down

restart: ## Riavvia i container Docker
	docker-compose restart

logs: ## Mostra i log dei container
	docker-compose logs -f

shell: ## Accede al container app
	docker-compose exec app bash

composer: ## Installa le dipendenze Composer
	docker-compose exec app composer install

npm: ## Installa le dipendenze NPM
	docker-compose exec app npm install

artisan: ## Esegue un comando Artisan (usa: make artisan CMD="migrate")
	docker-compose exec app php artisan $(CMD)

migrate: ## Esegue le migrazioni
	docker-compose exec app php artisan migrate

migrate-fresh: ## Resetta il database e esegue le migrazioni
	docker-compose exec app php artisan migrate:fresh

seed: ## Esegue i seeders
	docker-compose exec app php artisan db:seed

queue: ## Avvia il queue worker nel container app
	docker-compose exec -d app php artisan queue:work --tries=3 --timeout=3600

queue-listen: ## Avvia il queue listener
	docker-compose exec app php artisan queue:listen

test: ## Esegue i test
	docker-compose exec app php artisan test

setup: ## Setup iniziale completo
	./docker/setup.sh

clean: ## Pulisce cache e log
	docker-compose exec app php artisan cache:clear
	docker-compose exec app php artisan config:clear
	docker-compose exec app php artisan route:clear
	docker-compose exec app php artisan view:clear

permissions: ## Fixa i permessi
	docker-compose exec app chmod -R 775 storage bootstrap/cache
	docker-compose exec app chown -R www-data:www-data storage bootstrap/cache

rebuild: ## Ricostruisce i container
	docker-compose build --no-cache
	docker-compose up -d

reset: ## Reset completo (ATTENZIONE: elimina tutti i dati)
	docker-compose down -v
	docker-compose build --no-cache
	docker-compose up -d
	./docker/setup.sh
