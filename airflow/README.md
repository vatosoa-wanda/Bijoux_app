# Orchestration Airflow — Bijoux App

Ce dossier contient l'orchestrateur qui exécute automatiquement, chaque jour, le pipeline défini dans [`../data-platform/`](../data-platform/README.md) : extraction PostgreSQL → chargement BigQuery → agrégation SQL.

> Ce README suppose que vous avez déjà lu `../README.md` (vue d'ensemble du projet, documentation des tables BigQuery). Il se concentre uniquement sur ce qui est spécifique à Airflow/Docker.

---

## 0. Pourquoi Airflow plutôt que script + cron

Le projet utilisait initialement `pipeline.py` planifié via `schtasks`/`cron`. Airflow le remplace pour ces raisons :

| Besoin | Avec script + cron | Avec Airflow |
|---|---|---|
| Voir si le run de cette nuit a réussi | ouvrir `logs/pipeline.log` à la main | interface web avec statut coloré par tâche |
| Relancer juste l'étape qui a échoué | tout relancer depuis le début | relancer uniquement la tâche en échec depuis l'UI |
| Réessayer automatiquement en cas d'erreur réseau passagère | rien avant le lendemain 2h | retries configurables (2 essais, 5 min d'écart) |
| Voir la dépendance entre étapes | implicite dans le code Python | visible sous forme de graphe (DAG) |
| Rejouer une date passée (backfill) | impossible sans modifier le code | commande dédiée |

`pipeline.py` et `run_pipeline.bat` restent dans `data-platform/` pour un lancement manuel rapide (debug, test d'une modif de `transform.sql`) sans avoir à démarrer tout Airflow.

---

## 1. Concepts Airflow à connaître

| Terme | Définition courte |
|---|---|
| **DAG** (Directed Acyclic Graph) | la définition d'un pipeline — quelles tâches, dans quel ordre, à quelle fréquence |
| **Task** | une étape du DAG |
| **Operator** | le type de tâche ; `PythonOperator` exécute une fonction Python (celui utilisé ici) |
| **Scheduler** | processus Airflow qui déclenche les DAGs selon leur planning |
| **Executor** | comment les tâches s'exécutent physiquement (`CeleryExecutor` ici, distribue sur des workers) |
| **`schedule`** | fréquence du DAG en syntaxe cron (`"0 2 * * *"` = tous les jours à 2h) |
| **`catchup`** | si `True`, rejoue tous les runs "manqués" depuis `start_date` au premier démarrage — **`False`** ici |
| **Retries** | nombre de nouvelles tentatives automatiques en cas d'échec |
| **Backfill** | rejouer le DAG pour une plage de dates passées, à la demande |

---

## 2. Prérequis

- **Docker Desktop**, avec le backend **WSL2** activé (Airflow n'est pas supporté nativement sous Windows)
- Le service account Google Cloud déjà créé (`../README.md`, section 5.2) — sa clé JSON sera copiée ici

---

## 3. Installation

### 3.1 Préparer les dossiers
```cmd
cd airflow
mkdir dags logs plugins config
```

### 3.2 Récupérer le fichier officiel (si pas déjà fait)
```powershell
Invoke-WebRequest -Uri "https://airflow.apache.org/docs/apache-airflow/stable/docker-compose.yaml" -OutFile "docker-compose.yaml"
```

### 3.3 Créer l'image Docker personnalisée

`requirements-airflow.txt` :
```
psycopg2-binary
SQLAlchemy
pandas
pyarrow
python-dotenv
google-cloud-bigquery
db-dtypes
```

`Dockerfile` :
```dockerfile
# apache/airflow:3.3.1 doit correspondre à la version par défaut
# (AIRFLOW_IMAGE_NAME) indiquée en haut de docker-compose.yaml
FROM apache/airflow:3.3.1

COPY requirements-airflow.txt /requirements.txt
RUN pip install --no-cache-dir -r /requirements.txt
```

> Pourquoi pas `_PIP_ADDITIONAL_REQUIREMENTS` ? Cette variable réinstalle les paquets à chaque redémarrage de conteneur — lent, et dépendant d'internet à chaque `docker compose up`. Construire l'image une fois est la bonne pratique, même en développement.

Dans `docker-compose.yaml`, section `x-airflow-common` : commenter `image:`, activer `build:` :
```yaml
x-airflow-common:
  &airflow-common
  # image: ${AIRFLOW_IMAGE_NAME:-apache/airflow:3.3.1}
  build:
    context: .
    dockerfile: Dockerfile
```

### 3.4 Ajouter les variables du pipeline dans `docker-compose.yaml`

Dans le bloc `environment` (sous `&airflow-common-env`), ajouter :
```yaml
    PGHOST: ${PGHOST}
    PGPORT: ${PGPORT}
    PGDATABASE: ${PGDATABASE}
    PGUSER: ${PGUSER}
    PGPASSWORD: ${PGPASSWORD}
    GOOGLE_APPLICATION_CREDENTIALS: /opt/airflow/dags/bijoux-etl-key.json
    GCP_PROJECT_ID: ${GCP_PROJECT_ID}
    BQ_DATASET: ${BQ_DATASET}
```
> `GOOGLE_APPLICATION_CREDENTIALS` est écrit en dur (pas `${...}`) : c'est un chemin fixe **à l'intérieur du conteneur**, toujours le même, contrairement aux autres valeurs qui varient selon la machine.

### 3.5 Générer la clé de chiffrement Airflow (obligatoire)
```bash
python -c "from cryptography.fernet import Fernet; print(Fernet.generate_key().decode())"
```
Cette clé chiffre les données sensibles stockées par Airflow en base (mots de passe de Connections, etc.) — Airflow refuse de démarrer si elle est vide.

### 3.6 Créer `airflow/.env`
```env
AIRFLOW_UID=50000

FERNET_KEY=colle_ici_la_cle_generee

_AIRFLOW_WWW_USER_USERNAME=airflow
_AIRFLOW_WWW_USER_PASSWORD=airflow

_PIP_ADDITIONAL_REQUIREMENTS=

PGHOST=host.docker.internal
PGPORT=5432
PGDATABASE=bijoux_db
PGUSER=postgres
PGPASSWORD=xxxxx

GCP_PROJECT_ID=bijoux-data-platform
BQ_DATASET=analytics_bijoux
```
⚠️ **`PGHOST=host.docker.internal`, pas `localhost`** : Airflow tourne dans un conteneur ; `localhost` pointerait vers le conteneur lui-même, pas vers PostgreSQL installé sur la machine hôte.

### 3.7 Copier le code du pipeline dans `dags/`
Copier depuis `data-platform/` : `extract.py`, `load.py`, `transform.py`, `transform.sql`, ainsi que `bijoux-etl-key.json` (la clé de service account).

Créer en plus (spécifiques à Airflow, n'existent pas dans `data-platform/`) : `airflow_tasks.py` et `bijoux_pipeline_dag.py` — détaillés section 5.

### 3.8 Construire et démarrer
```cmd
docker compose build
docker compose up airflow-init
docker compose up -d
```
Interface web : [http://localhost:8080](http://localhost:8080), identifiants par défaut `airflow` / `airflow`.

---

## 4. Structure du dossier

```
airflow/
├── .env                        # secrets Airflow + PG/GCP (non commité)
├── .env.example
├── .gitignore
├── docker-compose.yaml         # définition des services (officiel, adapté)
├── Dockerfile                  # image personnalisée avec les deps du pipeline
├── requirements-airflow.txt
├── dags/
│   ├── extract.py              # copie de data-platform/extract.py
│   ├── load.py                 # copie de data-platform/load.py
│   ├── transform.py            # copie de data-platform/transform.py
│   ├── transform.sql           # copie de data-platform/transform.sql
│   ├── airflow_tasks.py        # pont entre le code existant et Airflow
│   ├── bijoux_pipeline_dag.py  # définition du DAG
│   ├── bijoux-etl-key.json     # clé de service account (non commité)
│   └── staging/                # généré au runtime (non commité)
├── logs/                       # généré par Airflow (non commité)
├── plugins/                    # vide dans ce projet
└── config/                     # généré par airflow-init (non commité)
```

---

## 5. Le DAG `bijoux_pipeline`

### 5.1 Les 3 tâches
```
extract_postgresql  →  load_bigquery  →  transform_aggregate
```
Chaque tâche est un `PythonOperator` qui appelle une fonction de `airflow_tasks.py`.

### 5.2 Pourquoi `airflow_tasks.py` existe (point technique important)

**Airflow ne regarde jamais la valeur de retour d'une tâche pour savoir si elle a réussi** — seulement si une exception a été levée. Or `run_extraction()`/`run_load()` (dans `extract.py`/`load.py`) renvoient un `dict` avec des `-1` en cas d'échec partiel, et `run_transform()` renvoie un `bool`. Sans adaptation, une extraction en échec apparaîtrait "verte" dans l'UI.

`airflow_tasks.py` fait le pont : il appelle ces fonctions **sans les modifier**, puis lève une `RuntimeError` si un échec est détecté :
```python
from extract import run_extraction
from load import run_load
from transform import run_transform


def extract_task() -> None:
    results = run_extraction()
    failed = [name for name, count in results.items() if count < 0]
    if failed:
        raise RuntimeError(f"Échec de l'extraction pour : {', '.join(failed)}")


def load_task() -> None:
    results = run_load()
    failed = [name for name, count in results.items() if count < 0]
    if failed:
        raise RuntimeError(f"Échec du chargement pour : {', '.join(failed)}")


def transform_task() -> None:
    if not run_transform():
        raise RuntimeError("Échec de l'agrégation SQL")
```

### 5.3 Le fichier du DAG (`bijoux_pipeline_dag.py`)
```python
from datetime import datetime, timedelta
from airflow import DAG
from airflow.operators.python import PythonOperator
from airflow_tasks import extract_task, load_task, transform_task

default_args = {
    "owner": "wanda",
    "retries": 2,
    "retry_delay": timedelta(minutes=5),
}

with DAG(
    dag_id="bijoux_pipeline",
    default_args=default_args,
    schedule="0 2 * * *",
    start_date=datetime(2026, 9, 1),
    catchup=False,
    tags=["bijoux", "data-platform"],
) as dag:
    extract = PythonOperator(task_id="extract_postgresql", python_callable=extract_task)
    load = PythonOperator(task_id="load_bigquery", python_callable=load_task)
    transform = PythonOperator(task_id="transform_aggregate", python_callable=transform_task)

    extract >> load >> transform
```

### 5.4 Paramètres clés

| Paramètre | Valeur | Pourquoi |
|---|---|---|
| `schedule` | `"0 2 * * *"` | tous les jours à 2h — identique à l'ancien cron/schtasks |
| `catchup` | `False` | ne pas rejouer tous les jours "manqués" depuis `start_date` au premier démarrage |
| `retries` | `2` | réessaie automatiquement en cas d'échec (ex : coupure réseau passagère) |
| `retry_delay` | `5 minutes` | délai entre deux tentatives |

---

## 6. Lancer et tester

### 6.1 Activer le DAG
Nouveau DAG = en pause par défaut (`DAGS_ARE_PAUSED_AT_CREATION: 'true'`). Cliquer sur le toggle à gauche de `bijoux_pipeline` dans l'UI pour l'activer — sinon il ne se déclenchera jamais tout seul.

### 6.2 Déclencher manuellement
- **UI** : icône ▶️ à droite de la ligne du DAG → **Déclencher** (garder "Exécution unique", valeurs par défaut)
- **CLI** : `docker compose --profile debug run --rm airflow-cli airflow dags trigger bijoux_pipeline`

### 6.3 Suivre l'exécution
Cliquer sur `bijoux_pipeline` → onglet **Grid**/**Graph** → cliquer sur une tâche → **Logs**.

### 6.4 Mettre en pause
Le toggle arrête uniquement le **déclenchement automatique futur** :
- un run déjà en cours va à son terme
- l'historique reste consultable
- un déclenchement manuel (▶️) reste possible même en pause
- les conteneurs Docker continuent de tourner (la pause n'arrête aucun service)

### 6.5 Décommissionner l'ancienne planification
Une fois Airflow validé comme fiable :
```cmd
schtasks /delete /tn "BijouxDataPipeline" /f
```

---

## 7. Commandes à connaître

> Le service s'appelle `airflow-cli` (sous le profil `debug`), pas `airflow-webserver` — Airflow 3.x a renommé ce rôle en `airflow-apiserver`, dédié à l'UI/API.

```cmd
REM Démarrer / arrêter
docker compose up -d
docker compose down            REM garde le volume PostgreSQL interne d'Airflow
docker compose stop            REM suspendre sans supprimer les conteneurs
docker compose start

REM Reconstruire après modif du Dockerfile/requirements-airflow.txt
docker compose build

REM Logs d'un service en direct
docker compose logs -f airflow-scheduler

REM Commandes airflow ponctuelles (via le service dédié)
docker compose --profile debug run --rm airflow-cli airflow dags list
docker compose --profile debug run --rm airflow-cli airflow dags list-import-errors
docker compose --profile debug run --rm airflow-cli airflow dags trigger bijoux_pipeline
docker compose --profile debug run --rm airflow-cli airflow dags test bijoux_pipeline 2026-09-06
docker compose --profile debug run --rm airflow-cli airflow tasks list bijoux_pipeline
```

---

## 8. Problèmes rencontrés pendant la migration (et solutions)

| Problème | Symptôme | Cause | Solution |
|---|---|---|---|
| Airflow refuse de démarrer | erreur au lancement de `airflow-init` liée à la clé de chiffrement | `FERNET_KEY` vide dans `.env` | Générer une clé (section 3.5) |
| `extract.py` échoue dans le conteneur avec erreur de connexion PostgreSQL | `OperationalError`, alors que ça marchait en local | `PGHOST=localhost` pointe vers le conteneur lui-même | `PGHOST=host.docker.internal` |
| `Variables d'environnement manquantes` pour `GOOGLE_APPLICATION_CREDENTIALS` | tâches `extract_postgresql`/`load_bigquery` échouent immédiatement | cette variable ne peut pas passer par `.env` (chemin fixe côté conteneur) | ajoutée en dur dans `docker-compose.yaml` |
| `no such service: airflow-webserver` | commande `docker compose exec airflow-webserver ...` échoue | Airflow 3.x a renommé ce rôle en `airflow-apiserver`, et fournit un service dédié `airflow-cli` pour les commandes | utiliser `docker compose --profile debug run --rm airflow-cli ...` |
| `bijoux_pipeline` n'apparaît pas dans l'UI | rien dans la liste des DAGs, aucune erreur visible | `bijoux_pipeline_dag.py` n'avait pas encore été créé — seul `airflow_tasks.py` existait | créer le fichier, attendre ~30s le scan du `dag-processor` |
| Dépendances Python réinstallées à chaque redémarrage de conteneur | démarrage lent, dépendant d'internet | usage de `_PIP_ADDITIONAL_REQUIREMENTS` | `Dockerfile` personnalisé + `docker compose build` |

---

## 9. Limites connues

- Code du pipeline dupliqué entre `data-platform/` et `airflow/dags/` (copies, pas de dossier partagé) — à revoir si le projet grossit
- Secrets injectés via variables d'environnement Docker Compose plutôt que via Airflow Connections/Secrets Backend
- Pas d'alerting (email/Slack) configuré sur échec de tâche

---

## 10. Roadmap v2

- **Secrets via Airflow Connections/Secrets Backend** plutôt que des variables d'environnement en clair
- **Alerting Slack/email** sur échec de tâche (`on_failure_callback`)
- **Registre d'images** (ex: Artifact Registry) plutôt qu'un build local à chaque déploiement
- **Dossier de code partagé** entre `data-platform/` et `airflow/dags/` (au lieu de deux copies à synchroniser manuellement)
- **Google Cloud Composer** (Airflow managé) si le projet devait un jour tourner hors d'une machine locale