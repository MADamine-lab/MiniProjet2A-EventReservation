# 🎪 EventHub — Application Web de Gestion de Réservations d'Événements

> **Mini Projet FIA3-GL** — ISSAT Sousse | Technologies : Symfony 6.3 + JWT + Passkeys + Docker

---

## 📋 Table des matières

- [Description](#-description)
- [Technologies utilisées](#-technologies-utilisées)
- [Architecture du projet](#-architecture-du-projet)
- [Prérequis](#-prérequis)
- [Installation avec Docker](#-installation-avec-docker)
- [Installation manuelle](#-installation-manuelle)
- [Comptes de démonstration](#-comptes-de-démonstration)
- [Fonctionnalités](#-fonctionnalités)
- [API REST](#-api-rest)
- [JWT & Passkeys](#-jwt--passkeys)
- [Structure des fichiers](#-structure-des-fichiers)

---

## 📖 Description

**EventHub** est une application web complète de gestion de réservations d'événements développée avec Symfony 6.3.  
Elle permet :
- Aux **utilisateurs** de consulter des événements et effectuer des réservations en ligne
- Aux **administrateurs** de gérer les événements et réservations via un tableau de bord sécurisé
- Une **sécurité renforcée** via JWT (API REST) et Passkeys (WebAuthn/FIDO2)

---

## 🛠 Technologies utilisées

| Technologie | Version | Rôle |
|-------------|---------|------|
| PHP | 8.2 | Langage backend |
| Symfony | 6.3 | Framework PHP |
| Doctrine ORM | 2.x | ORM / Migrations |
| MySQL | 8.0 | Base de données |
| Lexik JWT Bundle | 2.x | Authentification API JWT |
| WebAuthn / Passkeys | FIDO2 | Auth sans mot de passe |
| Docker + Compose | latest | Conteneurisation |
| Nginx | alpine | Serveur web |
| Twig | 3.x | Moteur de templates |
| phpMyAdmin | latest | Interface BDD |

---



## ✅ Prérequis

### Avec Docker (recommandé)
- [Docker Desktop](https://www.docker.com/products/docker-desktop/) ≥ 24.x
- [Docker Compose](https://docs.docker.com/compose/) ≥ 2.x

### Sans Docker
- PHP ≥ 8.1 avec extensions : `pdo_mysql`, `mbstring`, `gd`, `zip`, `intl`
- Composer ≥ 2.x
- MySQL ≥ 8.0
- OpenSSL (pour les clés JWT)

---

## 🐳 Installation avec Docker


### 2. Configurer l'environnement

```bash
cp .env .env.local
# Modifier .env.local si nécessaire (les valeurs par défaut fonctionnent avec Docker)
```

### 3. Lancer Docker Compose

```bash
docker-compose up -d --build
```

> ⏳ La première fois, Docker télécharge les images et installe les dépendances (~3-5 min)

### 4. Vérifier que les conteneurs tournent

```bash
docker-compose ps
```

Vous devez voir 4 conteneurs : `php`, `nginx`, `db`, `phpmyadmin`

### 5. Exécuter les migrations

```bash
docker-compose exec php php bin/console doctrine:migrations:migrate --no-interaction
```

### 6. Générer les clés JWT

```bash
docker-compose exec php mkdir -p config/jwt
docker-compose exec php openssl genpkey -algorithm RSA \
  -out config/jwt/private.pem \
  -aes256 -pass pass:your_jwt_passphrase \
  -pkeyopt rsa_keygen_bits:4096
docker-compose exec php openssl pkey \
  -in config/jwt/private.pem \
  -out config/jwt/public.pem \
  -pubout -passin pass:your_jwt_passphrase
```

### 7. Vider le cache

```bash
docker-compose exec php php bin/console cache:clear
```

### 8. Accéder à l'application

| Service | URL |
|---------|-----|
| 🌐 Site public | http://localhost:8080 |
| 🔐 Admin | http://localhost:8080/admin/login |
| 📊 phpMyAdmin | http://localhost:8081 |
| 🔌 API REST | http://localhost:8080/api |

---

## 🔧 Installation manuelle

```bash
# 1. Installer les dépendances PHP
composer install

# 2. Configurer la base de données dans .env.local
DATABASE_URL="mysql://root:root@127.0.0.1:3306/event_reservation?serverVersion=8.0"

# 3. Créer la base de données
php bin/console doctrine:database:create

# 4. Lancer les migrations
php bin/console doctrine:migrations:migrate

# 5. Générer les clés JWT
mkdir -p config/jwt
openssl genpkey -algorithm RSA -out config/jwt/private.pem -aes256 -pass pass:your_jwt_passphrase -pkeyopt rsa_keygen_bits:4096
openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout -passin pass:your_jwt_passphrase

# 6. Lancer le serveur de développement
symfony server:start
# ou
php -S localhost:8000 -t public/
```

---

## 👤 Comptes de démonstration

| Rôle | Identifiant | Mot de passe | Accès |
|------|-------------|--------------|-------|
| **Administrateur** | `admin` | `password123` | `/admin/login` |
| **Utilisateur** | `user1` | `password123` | `/login` |

---

## ✨ Fonctionnalités

### Côté Utilisateur (`/`)
- ✅ Page d'accueil avec événements mis en avant
- ✅ Liste complète des événements (`/events`)
- ✅ Détail d'un événement avec description, date, lieu, places restantes
- ✅ Formulaire de réservation (nom, email, téléphone)
- ✅ Page de confirmation après réservation
- ✅ Inscription (`/register`) et Connexion (`/login`)
- ✅ Authentification par **Passkey** (WebAuthn/FIDO2)

### Côté Administrateur (`/admin`)
- ✅ Tableau de bord avec statistiques
- ✅ Liste de tous les événements
- ✅ **CRUD complet** sur les événements (créer, modifier, supprimer)
- ✅ Upload d'images pour les événements
- ✅ Consultation des réservations par événement
- ✅ Consultation de toutes les réservations
- ✅ Déconnexion sécurisée

---

## 🔌 API REST

Base URL : `http://localhost:8080/api`

### Authentification

```bash
# Obtenir un token JWT
POST /api/login
Content-Type: application/json
{"username": "admin", "password": "password123"}

# Réponse
{"token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."}
```

### Endpoints Événements

| Méthode | Endpoint | Auth | Description |
|---------|----------|------|-------------|
| GET | `/api/events` | Public | Liste des événements |
| GET | `/api/events/{id}` | Public | Détail d'un événement |
| POST | `/api/events` | ROLE_ADMIN | Créer un événement |
| PUT | `/api/events/{id}` | ROLE_ADMIN | Modifier un événement |
| DELETE | `/api/events/{id}` | ROLE_ADMIN | Supprimer un événement |

### Endpoints Réservations

| Méthode | Endpoint | Auth | Description |
|---------|----------|------|-------------|
| POST | `/api/reservations` | JWT | Créer une réservation |
| GET | `/api/reservations` | ROLE_ADMIN | Toutes les réservations |
| GET | `/api/reservations/event/{id}` | ROLE_ADMIN | Réservations par événement |

### Endpoints Passkeys (WebAuthn)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| POST | `/api/passkey/register/options` | Options d'enregistrement |
| POST | `/api/passkey/register/verify` | Vérification enregistrement |
| POST | `/api/passkey/authenticate/options` | Challenge d'authentification |
| POST | `/api/passkey/authenticate/verify` | Vérification authentification |

### Exemple d'utilisation avec curl

```bash
# 1. Se connecter et récupérer le token
TOKEN=$(curl -s -X POST http://localhost:8080/api/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"password123"}' | python3 -c "import sys,json; print(json.load(sys.stdin)['token'])")

# 2. Lister les événements
curl http://localhost:8080/api/events

# 3. Créer un événement (admin)
curl -X POST http://localhost:8080/api/events \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Mon événement",
    "description": "Description...",
    "date": "2026-07-15 10:00:00",
    "location": "Sousse, Tunisie",
    "seats": 50
  }'

# 4. Réserver une place
curl -X POST http://localhost:8080/api/reservations \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "event_id": 1,
    "name": "Mohamed Ben Ali",
    "email": "m.benali@email.com",
    "phone": "+216 20 123 456"
  }'
```

---

## 🔐 JWT & Passkeys

### JWT (JSON Web Token)

Le projet utilise **LexikJWTAuthenticationBundle** pour sécuriser l'API REST :

- **Login** : `POST /api/login` → retourne un token JWT signé avec RSA-4096
- **Durée de vie** : 3600 secondes (1 heure)
- **Usage** : Header `Authorization: Bearer <token>`
- Les clés RSA sont générées localement (voir installation)

### Passkeys (WebAuthn / FIDO2)

Le projet implémente le protocole **WebAuthn** pour une authentification sans mot de passe :

1. **Enregistrement** : L'utilisateur crée une clé cryptographique liée à son appareil
2. **Authentification** : L'utilisateur s'authentifie via biométrie (empreinte, Face ID) ou PIN
3. Aucun mot de passe transmis sur le réseau — **résistant au phishing**

> ⚠️ Les Passkeys nécessitent un navigateur moderne (Chrome 108+, Firefox 122+, Safari 16+) et HTTPS en production.

---

## 🌿 Branches GitHub

```
main          ← Code stable et fonctionnel (production-ready)
dev           ← Intégration et tests
feature/auth  ← Développement JWT + Passkeys
feature/admin ← Interface d'administration
feature/api   ← API REST
feature/front ← Interface utilisateur
```

---

## 🐳 Commandes Docker utiles

```bash
# Démarrer les conteneurs
docker-compose up -d

# Arrêter les conteneurs
docker-compose down

# Voir les logs
docker-compose logs -f php

# Accéder au conteneur PHP
docker-compose exec php bash

# Vider le cache Symfony
docker-compose exec php php bin/console cache:clear

# Créer une migration après modification d'entité
docker-compose exec php php bin/console make:migration

# Appliquer les migrations
docker-compose exec php php bin/console doctrine:migrations:migrate

# Créer un utilisateur admin via console
docker-compose exec php php bin/console app:create-admin
```

---

## 📝 Notes de développement

- Les images uploadées sont stockées dans `public/uploads/events/`
- En production, configurer `APP_ENV=prod` et regénérer les clés JWT avec une vraie passphrase
- Les Passkeys fonctionnent uniquement en HTTPS en production (localhost est autorisé en développement)
- phpMyAdmin accessible sur `http://localhost:8081` (user: `root`, pass: `root`)

---

## 👨‍💻 Auteur

**Étudiant FIA3-GL** — ISSAT Sousse  
Année universitaire 2025-2026

---

*Projet réalisé dans le cadre du cours de développement web — Département Informatique ISSAT Sousse*
