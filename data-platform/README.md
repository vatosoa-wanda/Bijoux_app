# Data Platform — Bijoux App

Module d'analytique ajouté à l'application de gestion de bijoux artisanaux. Il complète l'application transactionnelle existante (Laravel + React + PostgreSQL) par une couche **data engineering** : extraction, chargement dans un entrepôt cloud (BigQuery), et agrégation en indicateurs métier.

> Ce README s'adresse à toute personne qui reprend ce module sans contexte préalable : il doit permettre de comprendre l'architecture, relancer le pipeline, et savoir où chercher en cas de problème.

Le projet est réparti en **deux dossiers** à la racine du dépôt :
```
Bijoux_app/
├── data-platform/     # le code du pipeline (ce README)
└── airflow/           # l'orchestrateur qui exécute ce code selon un planning
                        # → voir airflow/README.md pour tout ce qui concerne Airflow/Docker
```
`data-platform/` peut être exécuté seul, à la main, sans Airflow — c'est ce que documente ce fichier. Pour l'automatisation planifiée (DAG, Docker, dépannage Airflow), voir [`airflow/README.md`](../airflow/README.md).

---

## 1. Pourquoi ce module existe

L'application principale (PostgreSQL) est optimisée pour des opérations **transactionnelles** : créer une commande, enregistrer un mouvement de stock, valider un contrôle qualité — une ligne à la fois. Elle n'est pas faite pour calculer des tendances sur plusieurs mois ou croiser plusieurs tables sur de gros volumes sans ralentir l'application en production.

Ce module ajoute donc une couche séparée, dédiée à l'**analyse** :

```
PostgreSQL (transactionnel)  →  BigQuery (analytique)  →  BI / dashboard
```

---

## 2. Architecture

```
┌──────────────────────────────┐
│   Application existante      │
│  React → Laravel → PostgreSQL│
└───────────────┬───────────────┘
                │  extraction (batch)
                ▼
        ┌───────────────┐
        │  extract.py    │  PostgreSQL → staging/*.parquet
        └───────┬───────┘
                ▼
        ┌───────────────┐
        │   load.py      │  staging/*.parquet → BigQuery (tables raw_*)
        └───────┬───────┘
                ▼
        ┌───────────────┐
        │ transform.py   │  raw_* → tables de faits + vue_dashboard
        └───────┬───────┘
                ▼
        BI (Looker Studio, Metabase...) ← lit vue_dashboard
```

`extract.py`, `load.py`, `transform.py` sont des scripts Python **autonomes**, testables et exécutables seuls (section 7). Ils sont enchaînés soit manuellement via `pipeline.py`, soit automatiquement par Airflow (voir `airflow/README.md`) — dans les deux cas, c'est exactement le même code qui tourne.

**Principe clé : ELT, pas ETL.** On charge les données brutes dans BigQuery (`load.py`) *avant* de les transformer (`transform.py`), plutôt que l'inverse. BigQuery est très rapide en SQL sur de gros volumes, donc c'est plus simple et plus robuste de lui laisser faire les agrégations.

---

## 3. Stack technique & choix

| Brique | Outil | Pourquoi |
|---|---|---|
| Langage | Python 3.11+ | écosystème data standard |
| Connexion PostgreSQL | SQLAlchemy + psycopg2-binary | léger, suffisant pour ce volume |
| Manipulation de données | pandas | standard pour un projet de cette taille |
| Entrepôt de données | BigQuery (Google Cloud) | 1 To de requêtes + 10 Go de stockage gratuits par mois, gestion serverless |
| Chargement | `google-cloud-bigquery` (SDK officiel) | direct, pas de connecteur tiers payant |
| Format d'échange | Parquet (zone `staging/`) | plus compact qu'un CSV, types préservés (dates, décimaux) |
| Orchestration | **Apache Airflow** (Docker) | détails complets dans `airflow/README.md` |

---

## 4. Prérequis

- Python 3.11 ou plus récent
- PostgreSQL déjà rempli avec le schéma de l'application (`base_de_donnees.sql`)
- Un compte Google Cloud avec facturation activée (le free tier suffit très largement)
- Google Cloud CLI installé (`gcloud`, `bq`)
- Docker Desktop **uniquement si vous voulez utiliser Airflow** — voir `airflow/README.md`

---

## 5. Installation

### 5.1 Environnement Python
```bash
cd data-platform
python3 -m venv venv-etl
source venv-etl/bin/activate        # Windows : venv-etl\Scripts\activate
pip install -r requirements.txt
```

### 5.2 Configuration Google Cloud (à faire une seule fois)
```bash
gcloud auth login
gcloud config set project <votre-projet-id>
gcloud services enable bigquery.googleapis.com

gcloud iam service-accounts create bijoux-etl \
  --display-name="Service account ETL bijoux"

gcloud projects add-iam-policy-binding <votre-projet-id> \
  --member="serviceAccount:bijoux-etl@<votre-projet-id>.iam.gserviceaccount.com" \
  --role="roles/bigquery.dataEditor"

gcloud projects add-iam-policy-binding <votre-projet-id> \
  --member="serviceAccount:bijoux-etl@<votre-projet-id>.iam.gserviceaccount.com" \
  --role="roles/bigquery.jobUser"

gcloud iam service-accounts keys create bijoux-etl-key.json \
  --iam-account=bijoux-etl@<votre-projet-id>.iam.gserviceaccount.com
```
⚠️ `bijoux-etl-key.json` et `.env` contiennent des secrets : ils sont dans `.gitignore`, ne jamais les commit.

> Cette même clé (`bijoux-etl-key.json`) devra être copiée dans `airflow/dags/` si vous utilisez Airflow — voir `airflow/README.md`.

### 5.3 Fichier `.env`
Copier `.env.example` vers `.env` et remplir :
```env
PGHOST=localhost
PGPORT=5432
PGDATABASE=bijoux_db
PGUSER=postgres
PGPASSWORD=xxxxx

GOOGLE_APPLICATION_CREDENTIALS=./bijoux-etl-key.json
GCP_PROJECT_ID=bijoux-data-platform
BQ_DATASET=analytics_bijoux
```

---

## 6. Structure du dossier

```
data-platform/
├── .env                      # secrets (non commité)
├── .env.example              # gabarit à copier
├── bijoux-etl-key.json       # clé de service account (non commité)
├── requirements.txt
├── extract.py                # Étape 1 : ingestion PostgreSQL → staging/
├── load.py                   # Étape 2 : staging/ → BigQuery (tables raw_*)
├── transform.sql             # Requêtes d'agrégation (lues par transform.py)
├── transform.py              # Étape 3 : exécute transform.sql dans BigQuery
├── pipeline.py                # Orchestration manuelle des 3 étapes (hors Airflow)
├── run_pipeline.bat           # Lanceur pour un test rapide / ancien Planificateur Windows
├── staging/                   # fichiers .parquet intermédiaires (généré, non commité)
├── logs/                      # logs d'exécution (généré, non commité)
└── tests/
    └── test_pipeline.py
```

> ⚠️ `extract.py`, `load.py`, `transform.py`, `transform.sql` existent aussi en copie dans `airflow/dags/`, pour qu'Airflow puisse les exécuter (Docker ne voit pas ce dossier par défaut). **Toute modification de la logique du pipeline doit être répercutée dans les deux dossiers.** Limite connue, voir section 12 et la roadmap de `airflow/README.md`.

---

## 7. Comment lancer

### Usage recommandé : via Airflow (planification automatique quotidienne)
Voir `airflow/README.md` — interface web sur [http://localhost:8080](http://localhost:8080).

### Usage manuel (développement, debug rapide, test d'une seule étape)

Tout le pipeline d'un coup :
```bash
python pipeline.py
```

Étape par étape :
```bash
python extract.py                                   # toutes les tables
python extract.py --tables mouvement_stock          # une seule table

python load.py                                       # tout charger
python load.py --tables mouvement_stock produit_fini # un sous-ensemble

python transform.py                                  # toutes les agrégations, dans l'ordre
python transform.py --queries fact_stock_mensuel      # une seule requête
```

### Planification sans Airflow (alternative simple, si Docker n'est pas disponible)
- **Windows** : `schtasks /create /tn "BijouxDataPipeline" /tr "...\run_pipeline.bat" /sc daily /st 02:00`
- **Linux / macOS** : cron `0 2 * * * /chemin/venv-etl/bin/python /chemin/pipeline.py >> /chemin/logs/pipeline.log 2>&1`

Détail des flags : voir section 9.

---

## 8. Documentation des tables BigQuery (dataset `analytics_bijoux`)

### Zone brute (`raw_*`) — copie fidèle de PostgreSQL, écrasée à chaque run

| Table | Colonnes clés | Grain (1 ligne =) | Source PostgreSQL | Fréquence |
|---|---|---|---|---|
| `raw_mouvement_stock` | `id_mouvement`, `id_matiere`, `sens`, `quantite`, `prix_total`, `date_mouvement` | un mouvement de stock | `mouvement_stock` + `matiere_premiere` + `type_mouvement` | quotidienne (WRITE_TRUNCATE) |
| `raw_ordre_fabrication` | `id_of`, `id_bijou`, `statut_code`, `quantite_prevue/realisee/rejetee` | un ordre de fabrication | `ordre_fabrication` + `bijou` + `statut_production` | quotidienne |
| `raw_controle_qualite` | `id_controle`, `id_of`, `quantite_controlee/rejetee`, `defaut_code` | **un (contrôle, défaut)** — ⚠️ un contrôle avec 3 défauts donne 3 lignes | `controle_qualite` + `defaut_constate` + `type_defaut` (LEFT JOIN) | quotidienne |
| `raw_produit_fini` | `id_produit_fini`, `id_bijou`, `cout_revient`, `prix_vente`, `statut` | un produit fini | `produit_fini` | quotidienne |
| `raw_bijou` | `id_bijou`, `id_collection`, `reference`, `nom` | un modèle de bijou | `bijou` | quotidienne |
| `raw_collection` | `id_collection`, `nom`, `saison`, `annee` | une collection | `collection` | quotidienne |

> **Point d'attention sur `raw_controle_qualite`** : son grain n'est pas "un contrôle" mais "un (contrôle, défaut)" à cause du `LEFT JOIN` fait dans `extract.py`. Ne jamais sommer `quantite_controlee`/`quantite_rejetee` directement dessus sans dédupliquer d'abord sur `id_controle` (voir `fact_qualite_mensuelle` dans `transform.sql` pour l'exemple correct).

### Zone agrégée (`fact_*` et vue) — indicateurs métier prêts pour le BI

| Table / Vue | Type | Colonnes | Grain | Calculée à partir de | Fréquence |
|---|---|---|---|---|---|
| `fact_stock_mensuel` | TABLE | `mois`, `id_matiere`, `total_entrees`, `total_sorties` | un mois × une matière | `raw_mouvement_stock` | recalculée à chaque run |
| `fact_qualite_mensuelle` | TABLE | `mois`, `id_bijou`, `total_controle`, `total_rejete`, `taux_rejet_pct` | un mois × un bijou | `raw_controle_qualite` (dédupliqué) + `raw_ordre_fabrication` | idem |
| `fact_defauts_mensuels` | TABLE | `mois`, `defaut_code`, `defaut_libelle`, `total_defauts` | un mois × un type de défaut | `raw_controle_qualite` (grain défaut, non dédupliqué) | idem |
| `fact_rentabilite_collection` | TABLE | `mois`, `id_collection`, `collection_nom`, `cout_moyen`, `prix_vente_moyen`, `marge_moyenne`, `marge_pct` | un mois × une collection | `raw_produit_fini` + `raw_bijou` + `raw_collection` | idem |
| `vue_dashboard` | **VIEW** | `mois` + indicateurs clés des 3 tables de faits | un mois | les 4 tables `fact_*` | **pas de fréquence propre** : recalculée à chaque lecture |

C'est la table/vue `vue_dashboard` que tout outil de BI (Looker Studio, Metabase...) doit lire en priorité.

---

## 9. Commandes à connaître

### Python / environnement
```bash
python3 -m venv venv-etl              # créer l'environnement virtuel
source venv-etl/bin/activate          # l'activer (Windows : venv-etl\Scripts\activate)
pip install -r requirements.txt       # installer les dépendances
pip freeze > requirements.txt         # regénérer la liste après un nouvel install
```

### Google Cloud CLI (`gcloud`)
```bash
gcloud auth login
gcloud config set project <id>
gcloud config get-value project
gcloud services enable bigquery.googleapis.com
```

### BigQuery CLI (`bq`)
```bash
bq ls
bq ls analytics_bijoux
bq show analytics_bijoux.raw_mouvement_stock
bq query --use_legacy_sql=false "SELECT * FROM analytics_bijoux.vue_dashboard"
bq rm -r -f -d <projet>:analytics_bijoux
```

### Planification (Windows `schtasks`)
```cmd
schtasks /create /tn "BijouxDataPipeline" /tr "...\run_pipeline.bat" /sc daily /st 02:00
schtasks /run /tn "BijouxDataPipeline"              REM lancer immédiatement
schtasks /query /tn "BijouxDataPipeline" /v /fo LIST REM voir le dernier résultat (0 = succès)
schtasks /delete /tn "BijouxDataPipeline" /f        REM supprimer la tâche
```

> Commandes Docker/Airflow (`docker compose`, `airflow-cli`...) : voir `airflow/README.md`.

---

## 10. Vocabulaire à connaître (glossaire rapide)

| Terme | Définition courte |
|---|---|
| **OLTP** | base optimisée pour des transactions unitaires (PostgreSQL ici) |
| **OLAP** | base optimisée pour l'analyse de gros volumes (BigQuery ici) |
| **ELT** | Extract → Load (brut) → Transform *dans* l'entrepôt (approche retenue ici) |
| **Idempotence** | relancer le pipeline plusieurs fois de suite donne le même résultat (`WRITE_TRUNCATE`) |
| **Grain** | ce que représente une ligne d'une table — se tromper de grain fausse silencieusement les agrégations |
| **Fan-out** | multiplication du nombre de lignes causée par une jointure 1-vers-N |
| **Dataset (BigQuery)** | conteneur logique qui regroupe des tables liées |
| **Batch** | traitement par lots à intervalle régulier, par opposition au streaming temps réel |

> Glossaire Airflow (DAG, Task, Scheduler, Executor, Catchup...) : voir `airflow/README.md`.

---

> Problèmes spécifiques à Airflow/Docker : voir `airflow/README.md`.

---

## 12. Limites connues (assumées pour un projet portfolio)

- Extraction **batch quotidienne**, pas de temps réel — non nécessaire pour ce cas d'usage
- Pas d'ingestion incrémentale (CDC) : chaque run réextrait tout, acceptable au volume actuel
- Pas de tests de qualité de données automatisés (ex : Great Expectations) — validation manuelle actuellement
- Code du pipeline dupliqué entre `data-platform/` et `airflow/dags/` (voir section 6)

---

## Roadmap v2

- **CDC (Change Data Capture)** via Debezium pour ne synchroniser que les lignes modifiées, au lieu de tout réextraire
- **dbt** pour versionner les transformations SQL de `transform.sql` : tests de données intégrés, documentation auto-générée, lineage visuel
- **Tests de qualité automatisés** (Great Expectations ou équivalent) : ex. vérifier que `taux_rejet_pct` reste entre 0 et 100
- **BI branché en continu** : Looker Studio connecté directement à `vue_dashboard` pour un dashboard public consultable par toute l'équipe
- **Gouvernance** : data catalog, contrôle d'accès par rôle sur le dataset

> Roadmap orchestration/infra (secrets, alerting, dossier de code partagé...) : voir `airflow/README.md`.
