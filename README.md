# 🎪 EventHub — Application Web de Gestion de Réservations d'Événements

> **Mini Projet ** — ISSAT Sousse | Symfony 6.4 + JWT + Passkeys + Docker + Email Verification

---

## 📋 Table des matières

- [Description](#-description)
- [Technologies utilisées](#-technologies-utilisées)
- [Architecture du projet](#-architecture-du-projet)
- [Prérequis](#-prérequis)
- [Installation avec Docker](#-installation-avec-docker)
- [Comptes de démonstration](#-comptes-de-démonstration)
- [Fonctionnalités](#-fonctionnalités)
- [Vérification Email](#-vérification-email)
- [API REST](#-api-rest)
- [JWT & Passkeys](#-jwt--passkeys)
- [Structure des fichiers](#-structure-des-fichiers)
- [Branches GitHub](#-branches-github)

---

## 📖 Description

**EventHub** est une application web complète de gestion de réservations d'événements développée avec Symfony 6.4.

Elle permet :
- Aux **utilisateurs** de consulter des événements et effectuer des réservations en ligne
- Aux **administrateurs** de gérer les événements et réservations via un tableau de bord sécurisé
- Une **sécurité renforcée** via JWT (API REST), Passkeys (WebAuthn/FIDO2) et vérification email

---

## 🛠 Technologies utilisées

| Technologie | Version | Rôle |
|-------------|---------|------|
| PHP | 8.2 | Langage backend |
| Symfony | 6.4 LTS | Framework PHP (MVC) |
| Doctrine ORM | 2.x | ORM / Migrations |
| MySQL | 8.0 | Base de données |
| Lexik JWT Bundle | 2.x | Authentification API JWT |
| Symfony Mailer | 6.4 | Envoi d'emails |
| Mailpit | latest | Serveur SMTP local (dev) |
| WebAuthn / Passkeys | FIDO2 | Auth sans mot de passe |
| Docker + Compose | latest | Conteneurisation |
| Nginx | alpine | Serveur web |
| Twig | 3.x | Moteur de templates |
| phpMyAdmin | latest | Interface BDD |

---

## 🏗 Architecture du projet

```
projet/
├── config/
│   ├── packages/
│   │   ├── security.yaml          # Firewalls, JWT, access_control
│   │   ├── doctrine.yaml          # Configuration ORM
│   │   ├── mailer.yaml            # Configuration SMTP Mailpit
│   │   └── lexik_jwt_authentication.yaml
│   ├── routes.yaml
│   └── services.yaml
├── docker/
│   ├── php/Dockerfile             # PHP-FPM 8.2
│   ├── nginx/default.conf         # Vhost Nginx
│   └── mysql/init.sql             # Script init BDD
├── migrations/
│   ├── Version20260223000001.php  # Création tables
│   ├── Version20260223000002.php  # Données de démo
│   └── Version20260223000003.php  # Champs email/vérification
├── src/
│   ├── Controller/
│   │   ├── Api/
│   │   │   ├── EventApiController.php
│   │   │   ├── ReservationApiController.php
│   │   │   └── PasskeyController.php      # WebAuthn
│   │   ├── AdminController.php            # CRUD admin
│   │   ├── EventController.php            # Pages publiques
│   │   └── SecurityController.php         # Login/Register/Verify
│   ├── Entity/
│   │   ├── User.php               # + email, isVerified, token
│   │   ├── Event.php
│   │   └── Reservation.php
│   ├── EventListener/
│   │   └── LoginListener.php      # Bloque comptes non vérifiés
│   ├── Form/
│   │   ├── EventType.php
│   │   ├── ReservationType.php
│   │   └── RegistrationType.php   # + champ email
│   ├── Repository/
│   │   ├── UserRepository.php
│   │   ├── EventRepository.php
│   │   └── ReservationRepository.php
│   └── Service/
│       └── EmailVerificationService.php   # Envoi email HTML
└── templates/
    ├── admin/                     # Dashboard, CRUD, réservations
    ├── event/                     # Home, liste, détail, réservation
    ├── security/
    │   ├── login.html.twig
    │   ├── register.html.twig
    │   ├── verify_pending.html.twig
    │   ├── email_not_verified.html.twig
    │   └── resend_verification.html.twig
    └── base.html.twig
```

---

## ✅ Prérequis

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) ≥ 24.x
- [Docker Compose](https://docs.docker.com/compose/) ≥ 2.x

---

## 🐳 Installation avec Docker

### 1. Cloner le dépôt

```bash
git clone https://github.com/MADamine-lab/MiniProjet2A-EventReservation.git
cd MiniProjet2A-EventReservation
```

### 2. Lancer Docker Compose

```bash
docker-compose up -d --build
```

### 3. Installer les dépendances PHP

```bash
docker-compose exec php bash
curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php
php /tmp/composer-setup.php --install-dir=/usr/bin --filename=composer
composer install --no-interaction
exit
```

### 4. Exécuter les migrations

```bash
docker-compose exec php php bin/console doctrine:migrations:migrate --no-interaction
```

### 5. Générer les clés JWT

```bash
docker-compose exec php bash -c "
mkdir -p config/jwt &&
openssl genpkey -algorithm RSA -out config/jwt/private.pem -aes256 -pass pass:your_jwt_passphrase -pkeyopt rsa_keygen_bits:4096 &&
openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout -passin pass:your_jwt_passphrase
"
```

### 6. Vider le cache

```bash
docker-compose exec php php bin/console cache:clear
```

### 7. Accéder à l'application

| Service | URL |
|---------|-----|
| 🌐 Site public | http://localhost:8080 |
| 🔐 Admin | http://localhost:8080/admin/login |
| 📊 phpMyAdmin | http://localhost:8081 |
| 📬 Mailpit (emails) | http://localhost:8025 |

---

## 👤 Comptes de démonstration

| Rôle | Identifiant | Mot de passe |
|------|-------------|--------------|
| **Administrateur** | `admin` | `admin123` |
| **Utilisateur** | `user1` | `admin123` |

> ⚠️ Les comptes de démo sont pré-vérifiés. Les nouveaux comptes nécessitent une vérification email.

---

## ✨ Fonctionnalités

### Côté Utilisateur
- ✅ Inscription avec **vérification email obligatoire**
- ✅ Connexion (login/password)
- ✅ Connexion via **Passkey** (WebAuthn/FIDO2)
- ✅ Page d'accueil avec événements mis en avant
- ✅ Liste et détail des événements
- ✅ Formulaire de réservation (nom, email, téléphone)
- ✅ Page de confirmation après réservation
- ✅ Renvoi du lien de vérification

### Côté Administrateur
- ✅ Tableau de bord avec statistiques
- ✅ **CRUD complet** sur les événements
- ✅ Upload d'images pour les événements
- ✅ Consultation des réservations par événement
- ✅ Consultation de toutes les réservations
- ✅ Déconnexion sécurisée

---

## 📧 Vérification Email

### Flux complet

```
Inscription
    ↓
Token généré (64 chars, valide 24h)
    ↓
Email HTML envoyé via Mailpit (SMTP local)
    ↓
Utilisateur clique sur le lien
    ↓
isVerified = true, token supprimé
    ↓
Connexion autorisée ✅
```

### Routes de vérification

| Route | Description |
|-------|-------------|
| `GET /register` | Formulaire inscription |
| `GET /verify-pending` | Page après inscription (lien direct en dev) |
| `GET /verify-email/{token}` | Activation du compte |
| `GET/POST /resend-verification` | Renvoi du lien |
| `GET /email-not-verified` | Page si connexion sans vérification |

### Voir les emails en développement

Tous les emails envoyés sont visibles sur **http://localhost:8025** (Mailpit).

---

## 🔌 API REST

Base URL : `http://localhost:8080/api`

### Authentification JWT

```bash
# Obtenir un token
curl -X POST http://localhost:8080/api/login \
  -H "Content-Type: application/json" \
  -d '{"username": "admin", "password": "admin123"}'

# Réponse
{"token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."}
```

### Endpoints

| Méthode | Endpoint | Auth | Description |
|---------|----------|------|-------------|
| POST | `/api/login` | Non | Obtenir token JWT |
| GET | `/api/events` | Non | Liste événements |
| GET | `/api/events/{id}` | Non | Détail événement |
| POST | `/api/events` | ROLE_ADMIN | Créer événement |
| PUT | `/api/events/{id}` | ROLE_ADMIN | Modifier événement |
| DELETE | `/api/events/{id}` | ROLE_ADMIN | Supprimer événement |
| POST | `/api/reservations` | JWT | Créer réservation |
| GET | `/api/reservations` | ROLE_ADMIN | Toutes réservations |
| POST | `/api/passkey/register/options` | Non | Options Passkey |
| POST | `/api/passkey/register/verify` | Non | Enregistrer Passkey |
| POST | `/api/passkey/authenticate/options` | Non | Challenge auth |
| POST | `/api/passkey/authenticate/verify` | Non | Vérifier Passkey |

---

## 🔐 JWT & Passkeys

### JWT (JSON Web Token)
- Login → token RSA-4096 signé, durée 3600s
- Header : `Authorization: Bearer <token>`
- Bundle : **LexikJWTAuthenticationBundle**

### Passkeys (WebAuthn / FIDO2)
- Authentification sans mot de passe
- Biométrie (empreinte, Face ID) ou PIN
- Résistant au phishing
- Requiert HTTPS en production (localhost OK en dev)

---

## 🐳 Commandes Docker utiles

```bash
# Démarrer
docker-compose up -d

# Arrêter
docker-compose down

# Logs PHP
docker-compose logs -f php

# Accéder au conteneur PHP
docker-compose exec php bash

# Vider le cache
docker-compose exec php php bin/console cache:clear

# Nouvelle migration
docker-compose exec php php bin/console make:migration

# Appliquer migrations
docker-compose exec php php bin/console doctrine:migrations:migrate

# Voir les routes
docker-compose exec php php bin/console debug:router
```

---

## 🌿 Branches GitHub

```
main              ← Code stable
dev               ← Intégration et tests
feature/auth      ← JWT + Passkeys + Email verification
feature/admin     ← Interface administration
feature/api       ← API REST
feature/front     ← Interface utilisateur
```

---

## 📝 Notes

- Emails visibles sur **http://localhost:8025** (Mailpit) en développement
- En production : remplacer `smtp://mailpit:1025` par un vrai SMTP (Gmail, Mailgun...)
- Les clés JWT sont dans `config/jwt/` (ne jamais committer)
- Images uploadées dans `public/uploads/events/`
- Mot de passe par défaut des comptes démo : `admin123`

---

## 👨‍💻 Auteur

**Dardouri Mohamed Amine** — ISSAT Sousse 
ING-A2-GL 
Année universitaire 2025-2026

---

*Projet réalisé dans le cadre du cours de développement web — Département Informatique ISSAT Sousse*
