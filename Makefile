# Docker containers
PHP_CONTAINER = symfony_5
DB_CONTAINER = database_symfony_5
MERCURE_CONTAINER = mercure_symfo_5
ES_CONTAINER = elasticsearch_symfo_5

# Executables (local)
DOCKER = docker
DOCKER_COMP = docker-compose
DOCKER_EXEC = docker exec
PHP_EXEC = $(DOCKER_EXEC) $(PHP_CONTAINER)

# Executables
PHP      = $(PHP_EXEC) php
COMPOSER = $(PHP_EXEC) composer
SYMFONY  = $(PHP_EXEC) bin/console

# Misc
.DEFAULT_GOAL = help
.PHONY        = help build up start down logs sh composer vendor sf cc

## —— 🎵 🐳 The Symfony Docker Makefile 🐳 🎵 ——————————————————————————————————
help: ## Outputs this help screen
	@grep -E '(^[a-zA-Z0-9_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}{printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'

## —— Docker 🐳 ————————————————————————————————————————————————————————————————
build: ## Builds the Docker images
	@$(DOCKER_COMP) build --pull --no-cache

init: down init-network up install-vendors check-db-container db-init db-fixtures yarn-i yarn-dev ## Build and start the containers

reset: down up check-db-container db-init install-vendors ## Reset the project by installing or updating the php/js vendors

init-network: ## Create project docker network if not exists
	@$(DOCKER) network create symfony_5 || echo "Don't worry! We can continue..."

up: ## Start the docker's containers
	@$(DOCKER_COMP) up -d --build

down: ## Stop the docker hub
	@$(DOCKER_COMP) down --remove-orphans

logs: ## Show live logs
	@$(DOCKER_COMP) logs --tail=0 --follow

php: ## Enter PHP container as root
	@echo "Entering PHP container..."
	$(DOCKER_EXEC) -it $(PHP_CONTAINER) /bin/bash

mercure: ## Enter Mercure container as root
	@echo "Entering Mercure container..."
	$(DOCKER_EXEC) -it $(MERCURE_CONTAINER) /bin/sh

elastic: ## Enter Elasticsearch container as root
	@echo "Entering Elasticsearch container..."
	$(DOCKER_EXEC) -it $(ES_CONTAINER) /bin/bash

install-vendors:
	@$(PHP_EXEC) /usr/local/bin/install-vendors.sh

## —— Composer 🧙 ——————————————————————————————————————————————————————————————
composer: ## Run composer, pass the parameter "c=" to run a given command, example: make composer c='req symfony/orm-pack'
	@$(eval c ?=)
	@$(COMPOSER) $(c)

vendor: ## Install vendors according to the current composer.lock file
vendor: c=install --prefer-dist --no-dev --no-progress --no-scripts --no-interaction
vendor: composer

# —— Database 🗄 ——————————————————————————————————————————————————————————————
check-db-container: ## Check db container is up
	@$(DOCKER) info > /dev/null 2>&1 # Docker is up
	$(DOCKER) inspect --format "{{json .State.Status }}" $(DB_CONTAINER) # Db container is running
	sleep 10 # Waiting until Db container is 100% ready.

db-init: db-clear-meta db-migrate db-fixtures ## install database schema and run doctrine fixtures

db-reset: db-clear-meta db-migrate ## check database schema

db-migrate: ## Execute doctrine migrations
	$(SYMFONY) doctrine:migrations:migrate --no-interaction -vv

db-clear-meta: ## Clear doctrine metadata cache
	$(SYMFONY) doctrine:cache:clear-metadata

db-fixtures:
	$(SYMFONY) doctrine:fixtures:load --no-interaction -vv

## —— Symfony 🎵 ———————————————————————————————————————————————————————————————
sf: ## List all Symfony commands or pass the parameter "c=" to run a given command, example: make sf c=about
	@$(eval c ?=)
	@$(SYMFONY) $(c)

cc: c=c:c ## Clear the cache
cc: sf

cs-fix: ## run php cs-fix
	@$(PHP_EXEC) vendor/bin/php-cs-fixer fix -v

## —— assets  ———————————————————————————————————————————————————————————————
yarn-i: ## Install assets dependencies
	@$(PHP_EXEC) yarn install

yarn-watch: ## Build assets and watch changes
	@$(PHP_EXEC) yarn watch

yarn-dev: ## Build assets
	@$(PHP_EXEC) yarn dev
