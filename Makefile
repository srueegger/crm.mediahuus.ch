.PHONY: up down install migrate seed test fmt clean

# DDEV shortcuts
up: ## Start DDEV containers and install dependencies
	ddev start
	ddev composer install
	@echo "✅ Project started! Run 'make launch' to open in browser"

down: ## Stop DDEV containers
	ddev stop

install: ## Install dependencies
	ddev composer install

launch: ## Open project in browser
	ddev launch

# Database (future)
migrate: ## Run database migrations
	ddev composer migrate

seed: ## Seed database with test data
	ddev composer seed

# Code quality (future)
test: ## Run tests
	ddev composer test

fmt: ## Format code
	ddev composer phpcs

check: ## Check code quality
	ddev composer phpstan

# Utilities
clean: ## Clean cache and logs
	rm -rf logs/*.log
	rm -rf cache/*
	rm -rf tmp/*

logs: ## Follow application logs
	ddev logs -f

ssh: ## SSH into DDEV container
	ddev ssh

help: ## Show this help message
	@echo 'Usage: make [target]'
	@echo ''
	@echo 'Targets:'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  %-15s %s\n", $$1, $$2}' $(MAKEFILE_LIST)