.PHONY: setup fresh dev seed index sync help infra horizon analytics-status analytics-aggregate analytics-report analytics-prune

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

setup: ## Full setup: install deps, start infra, migrate, seed, sync index, import
	composer install
	npm install
	cp -n .env.example .env || true
	php artisan key:generate --force
	docker compose up -d
	sleep 3
	php artisan migrate --force
	php artisan db:seed
	php artisan scout:sync-index-settings
	php artisan scout:import "App\Models\Product"
	npm run build

fresh: ## Reset everything: drop DB, re-migrate, re-seed, re-index
	php artisan migrate:fresh --force
	php artisan db:seed
	php artisan scout:sync-index-settings
	php artisan scout:import "App\Models\Product"

dev: ## Start development servers (Laravel + Vite + Horizon + Scheduler)
	composer run dev

seed: ## Seed the database with products
	php artisan db:seed --class=ProductSeeder

index: ## Import all products into Meilisearch
	php artisan scout:import "App\Models\Product"

sync: ## Sync Meilisearch index settings (filters, sort, etc.)
	php artisan scout:sync-index-settings

infra: ## Start all infrastructure (Postgres, Redis, Meilisearch)
	docker compose up -d

horizon: ## Start Laravel Horizon (queue workers)
	php artisan horizon

analytics-status: ## Show analytics system status and counts
	php artisan analytics:status

analytics-aggregate: ## Run analytics aggregation manually
	php artisan analytics:aggregate

analytics-report: ## Generate dashboard report manually
	php artisan analytics:report

analytics-prune: ## Prune old raw analytics data
	php artisan analytics:prune
