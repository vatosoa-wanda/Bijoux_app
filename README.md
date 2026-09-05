# 💎 PolyBijoux — Application de gestion de production et vente de bijoux artisanaux

Application web de gestion métier pour un créateur de bijoux en pâte polymère : suivi des stocks, production, contrôle qualité et statistiques.

## 📋 Sommaire

- [Stack technique](#-stack-technique)
- [Architecture du projet](#-architecture-du-projet)
- [Fonctionnalités implémentées](#-fonctionnalités-implémentées)
- [Répartition de la logique métier](#-répartition-de-la-logique-métier)
- [Installation](#-installation)
- [Lancer les tests](#-lancer-les-tests)
- [Endpoints API](#-endpoints-api)
- [Captures d'écran](#-captures-décran)
- [Roadmap / fonctionnalités non traitées](#-roadmap--fonctionnalités-non-traitées)

## 🛠️ Stack technique

| Couche | Technologie |
|---|---|
| Backend | Laravel 12, PHP 8.2 |
| Frontend | React 19 (Vite), React Router, Tailwind CSS v4, Recharts |
| Base de données | PostgreSQL (types ENUM natifs, triggers et fonctions PL/pgSQL) |
| Auth API | Laravel Sanctum |
| Tests | PHPUnit (Feature tests) |

**Pourquoi PostgreSQL et pas MySQL ?** Le projet exploite volontairement des fonctionnalités avancées propres à PostgreSQL — types `ENUM` natifs, fonctions `PL/pgSQL`, triggers en cascade et vues agrégées — pour déplacer un maximum de logique métier critique (intégrité des stocks, calculs de coût, agrégations de reporting) au niveau de la base de données plutôt que dans le code applicatif.

## 🏗️ Architecture du projet

```
Bijoux_app/
├── backend/              # API Laravel
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/Api/
│   │   │   ├── Requests/
│   │   │   └── Resources/
│   │   └── Models/
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/
│   ├── routes/api.php
│   └── tests/Feature/
└── frontend/             # SPA React
    └── src/
        ├── api/          # Client axios
        ├── components/
        │   ├── layout/   # Sidebar, Header, Layout
        │   ├── ui/       # Modal, Badge
        │   └── qualite/  # Onglets Contrôle qualité / Statistiques
        └── pages/        # 6 pages principales
```

## ✅ Fonctionnalités implémentées

Le projet se concentre sur 3 fonctionnalités phares, choisies pour démontrer des compétences distinctes plutôt que de couvrir superficiellement l'ensemble du cahier des charges initial.

### 1. Gestion des stocks avec intégrité transactionnelle

- CRUD des matières premières (catégorie, unité, seuil d'alerte, prix unitaire).
- Enregistrement des mouvements de stock (entrées/sorties) : un **trigger PostgreSQL** (`tr_mouvement_stock_apply` / `fn_appliquer_mouvement_stock`) met à jour automatiquement `quantite_stock` et **bloque toute sortie qui rendrait le stock négatif**.
- Vue SQL `vue_stock_alertes` exposant les matières sous leur seuil, utilisée par le Dashboard.
- Page *Matières premières* (tableau + modal) et page *Stock & mouvements* (historique + formulaire de saisie).

### 2. Production (ordres de fabrication) + calcul automatique du coût de revient

- Fiches bijoux avec nomenclature (`composition_bijou`) : quantité de chaque matière nécessaire par bijou.
- Fonction PL/pgSQL `fn_cout_revient(id_bijou)` : coût matières + main-d'œuvre (temps × taux horaire paramétrable) + charges indirectes forfaitaires.
- Fonction `fn_verifier_disponibilite(id_bijou, quantite)` : vérifie le stock avant d'autoriser le lancement d'un ordre de fabrication.
- Trigger `tr_of_cloture` / `fn_cloturer_of` : à la clôture d'un OF, consomme automatiquement les matières premières (réutilise le trigger de stock de la Fonctionnalité 1) et crée l'entrée `produit_fini` correspondante avec son coût de revient calculé.
- Pages *Fiches bijoux* (formulaire + éditeur de nomenclature) et *Production* (liste des OF, workflow de statut simplifié à 3 étapes : En attente → En cours → Terminé).

### 3. Contrôle qualité + reporting

- Enregistrement des contrôles qualité avec défauts constatés associés.
- Trigger `tr_controle_qualite_rejet` / `fn_incrementer_rejet` : incrémente automatiquement `quantite_rejetee` sur l'ordre de fabrication concerné.
- Vues agrégées `vue_taux_rejet_par_bijou`, `vue_defauts_par_type` et `vue_kpi_dashboard` — tout le reporting est calculé côté base, sans logique d'agrégation en PHP.
- Page *Contrôle qualité* (checklist de défauts + décision) et onglet *Statistiques* (graphiques Recharts : taux de rejet par bijou, répartition des défauts).
- Dashboard alimenté par les vraies données (`GET /api/dashboard/kpi`).

## 🧩 Répartition de la logique métier

| Où | Quoi |
|---|---|
| **PostgreSQL** (triggers, fonctions, vues) | Mise à jour du stock, blocage stock négatif, calcul du coût de revient, consommation automatique des matières à la clôture d'un OF, incrémentation des rejets, toutes les agrégations de reporting |
| **Laravel** | Validation des entrées (FormRequests), orchestration des transactions (`DB::transaction`), formatage JSON (API Resources), gestion des erreurs remontées par PostgreSQL |
| **React** | Formulaires, affichage, appels API, état d'interface — aucun calcul métier |

Cette répartition limite les allers-retours entre PHP et la base et réduit le risque d'incohérence de données : le stock, par exemple, ne peut être modifié que via un mouvement, jamais directement.

## 🚀 Installation

### Prérequis

- PHP ≥ 8.2 avec l'extension `pdo_pgsql` activée
- Composer
- Node.js ≥ 18
- PostgreSQL ≥ 14

### Backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
```

Configurer PostgreSQL dans `.env` :
```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=bijoux_db
DB_USERNAME=postgres
DB_PASSWORD=votre_mot_de_passe
```

Créer la base puis lancer les migrations et les seeders :
```bash
psql -U postgres -c "CREATE DATABASE bijoux_db;"
php artisan migrate:fresh --seed --drop-types --drop-views
php artisan serve
```
> `--drop-types --drop-views` est nécessaire tant que le projet est en développement actif : PostgreSQL ne supprime pas automatiquement les types `ENUM` ni les vues avec un simple `migrate:fresh`.

L'API est disponible sur `http://127.0.0.1:8000/api`.

### Frontend

```bash
cd frontend
npm install
```

Créer `frontend/.env` :
```
VITE_API_URL=http://localhost:8000/api
```

```bash
npm run dev
```

L'application est disponible sur `http://localhost:5173`.

## 🧪 Lancer les tests

Le projet utilise une base PostgreSQL séparée pour les tests (`.env.testing`), afin que les triggers PL/pgSQL fonctionnent (ils ne sont pas supportés par SQLite).

```bash
psql -U postgres -c "CREATE DATABASE bijoux_db_test;"
php artisan key:generate --env=testing
php artisan test
```

**13 tests Feature** couvrent les règles métier critiques :
- `StockTriggerTest` (4 tests) : mise à jour du stock, blocage stock insuffisant, vue des alertes.
- `OrdreFabricationTest` (5 tests) : vérification de disponibilité, clôture d'OF avec décrémentation du stock, calcul du coût de revient.
- `ControleQualiteTest` (4 tests) : incrémentation des rejets, validation de cohérence, vues de statistiques, KPI dashboard.

## 📡 Endpoints API

| Méthode | Route | Description |
|---|---|---|
| GET/POST/PUT/DELETE | `/api/matieres` | CRUD matières premières |
| GET | `/api/matieres/alertes` | Matières sous leur seuil d'alerte |
| GET/POST | `/api/mouvements` | Historique / création de mouvements de stock |
| GET/POST/PUT/DELETE | `/api/bijoux` | CRUD fiches bijoux |
| GET | `/api/bijoux/{id}/cout-revient` | Coût de revient calculé |
| POST/DELETE | `/api/bijoux/{id}/compositions` | Gestion de la nomenclature |
| GET/POST | `/api/ordres-fabrication` | Liste / création d'OF |
| PATCH | `/api/ordres-fabrication/{id}/statut` | Changement de statut (déclenche la clôture) |
| GET/POST | `/api/controles-qualite` | Liste / création de contrôles qualité |
| GET | `/api/statistiques/taux-rejet` | Taux de rejet par bijou |
| GET | `/api/statistiques/defauts-par-type` | Répartition des défauts |
| GET | `/api/dashboard/kpi` | Indicateurs clés du tableau de bord |
| GET | `/api/categories-matiere`, `/api/unites-mesure`, `/api/types-bijou`, `/api/statuts-production`, `/api/types-defaut` | Listes de référence pour les formulaires |

## 📸 Captures d'écran

- Tableau de bord
- Fiches bijoux (nomenclature)
- Production (workflow de statut)
- Contrôle qualité et statistiques

## 🗺️ Roadmap / fonctionnalités non traitées

Hors périmètre de ce sprint de 2 jours, envisagées comme évolutions futures :

- **Commandes clients** : gestion des commandes personnalisées et lien avec la production (tables `client`, `commande`, `ligne_commande` déjà présentes dans le schéma SQL initial mais non exploitées).
- **Fournisseurs** : gestion des approvisionnements et historique d'achats (`fournisseur`, `fournisseur_matiere`).
- **Capacité de production en simulation multi-bijoux** : calcul du nombre maximal de bijoux réalisables toutes références confondues selon le stock disponible.
- **Paramètres avancés** : interface de configuration du taux horaire, des marges et des seuils (actuellement modifiables uniquement en base via la table `parametre`).
- **Export Excel/CSV** et **impression d'étiquettes/bons de fabrication**.
- **Authentification complète** avec gestion des rôles (actuellement hors périmètre, Sanctum installé mais non exploité pour de l'authentification utilisateur).

## 👤 Auteur

Projet réalisé par Wanda — étudiant(e) en Licence Informatique, dans le cadre d'un projet personnel de portfolio.