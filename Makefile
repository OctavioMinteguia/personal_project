.PHONY: help install test test-coverage phpstan cs-fix cs-check docker-up docker-down clean

help: ## Show this help message
	@echo 'Usage: make [target]'
	@echo ''
	@echo 'Targets:'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  %-15s %s\n", $$1, $$2}' $(MAKEFILE_LIST)

install: ## Install dependencies
	composer install

test: ## Run tests
	./vendor/bin/phpunit

test-coverage: ## Run tests with coverage
	./vendor/bin/phpunit --coverage-html coverage

phpstan: ## Run PHPStan static analysis
	./vendor/bin/phpstan analyse

cs-check: ## Check code style
	./vendor/bin/phpcs

cs-fix: ## Fix code style issues
	./vendor/bin/phpcbf

docker-up: ## Start Docker containers
	docker-compose up -d

docker-down: ## Stop Docker containers
	docker-compose down

clean: ## Clean cache and logs
	rm -rf var/cache/*
	rm -rf var/log/*
	rm -rf coverage/

setup: install docker-up ## Setup project for development
	@echo "Project setup complete!"
	@echo "Run 'make test' to run tests"
	@echo "Run 'php -S localhost:8000 -t public' to start the server"


