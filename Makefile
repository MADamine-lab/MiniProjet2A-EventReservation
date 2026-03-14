.PHONY: help up down build bash migrate cache jwt

help: ## Affiche l'aide
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

up: ## Démarre les conteneurs Docker
	docker-compose up -d

down: ## Arrête les conteneurs
	docker-compose down

build: ## Build et démarre les conteneurs
	docker-compose up -d --build

bash: ## Accède au conteneur PHP
	docker-compose exec php bash

migrate: ## Exécute les migrations Doctrine
	docker-compose exec php php bin/console doctrine:migrations:migrate --no-interaction

cache: ## Vide le cache Symfony
	docker-compose exec php php bin/console cache:clear

jwt: ## Génère les clés JWT RSA
	docker-compose exec php mkdir -p config/jwt
	docker-compose exec php openssl genpkey -algorithm RSA -out config/jwt/private.pem -aes256 -pass pass:your_jwt_passphrase -pkeyopt rsa_keygen_bits:4096
	docker-compose exec php openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout -passin pass:your_jwt_passphrase
	@echo "✅ Clés JWT générées dans config/jwt/"

install: build migrate jwt cache ## Installation complète (build + migrate + jwt + cache)
	@echo "✅ Installation terminée !"
	@echo "🌐 Site : http://localhost:8080"
	@echo "🔐 Admin : http://localhost:8080/admin/login"
	@echo "📊 phpMyAdmin : http://localhost:8081"

logs: ## Affiche les logs PHP
	docker-compose logs -f php

status: ## Statut des conteneurs
	docker-compose ps

reset-db: ## Recrée la base de données (⚠ supprime les données)
	docker-compose exec php php bin/console doctrine:database:drop --force --if-exists
	docker-compose exec php php bin/console doctrine:database:create
	docker-compose exec php php bin/console doctrine:migrations:migrate --no-interaction
