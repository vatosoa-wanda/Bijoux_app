"""
load.py — Étape Chargement du pipeline data bijoux.

Rôle : lire les fichiers .parquet déposés en staging par extract.py, créer le
dataset BigQuery s'il n'existe pas, puis charger chaque fichier dans sa table
BigQuery correspondante (préfixe `raw_`) en mode WRITE_TRUNCATE.

WRITE_TRUNCATE = on remplace tout le contenu de la table à chaque run.
C'est le choix le plus simple pour être idempotent (relancer 2 fois le
pipeline ne duplique rien) tant qu'on ne fait pas d'ingestion incrémentale.

Usage :
    python load.py                # charge toutes les tables extraites
    python load.py --tables mouvement_stock produit_fini
"""

from __future__ import annotations

import argparse
import logging
import os
import sys
from dataclasses import dataclass
from pathlib import Path

import pandas as pd
from dotenv import load_dotenv
from google.cloud import bigquery

# ----------------------------------------------------------------------------
# Configuration
# ----------------------------------------------------------------------------

STAGING_DIR = Path(__file__).parent / "staging"
DATASET_LOCATION = "EU"

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s | %(levelname)-8s | %(message)s",
    datefmt="%Y-%m-%d %H:%M:%S",
)
logger = logging.getLogger("load")


@dataclass(frozen=True)
class LoadSpec:
    """Associe un fichier de staging à sa table BigQuery de destination."""
    staging_file: str   # nom du fichier .parquet (sans extension)
    bq_table: str        # nom de la table dans le dataset BigQuery


# Un raw_<table> par fichier produit par extract.py. Garder les mêmes noms
# entre les deux scripts (staging_file == nom donné par extract.py) évite
# toute confusion.
LOAD_SPECS: list[LoadSpec] = [
    LoadSpec("mouvement_stock", "raw_mouvement_stock"),
    LoadSpec("ordre_fabrication", "raw_ordre_fabrication"),
    LoadSpec("controle_qualite", "raw_controle_qualite"),
    LoadSpec("produit_fini", "raw_produit_fini"),
    LoadSpec("bijou", "raw_bijou"),
    LoadSpec("collection", "raw_collection"),
]


# ----------------------------------------------------------------------------
# Logique de chargement
# ----------------------------------------------------------------------------

def get_client() -> bigquery.Client:
    """Construit le client BigQuery à partir des variables d'environnement."""
    load_dotenv()
    required = ["GCP_PROJECT_ID", "BQ_DATASET", "GOOGLE_APPLICATION_CREDENTIALS"]
    missing = [var for var in required if not os.getenv(var)]
    if missing:
        raise RuntimeError(
            f"Variables d'environnement manquantes dans .env : {', '.join(missing)}"
        )
    # GOOGLE_APPLICATION_CREDENTIALS est lu automatiquement par la lib Google
    # (variable standard), il suffit qu'elle soit dans l'environnement.
    return bigquery.Client(project=os.getenv("GCP_PROJECT_ID"))


def ensure_dataset(client: bigquery.Client, dataset_name: str) -> str:
    """Crée le dataset s'il n'existe pas déjà. Retourne son id complet."""
    dataset_id = f"{client.project}.{dataset_name}"
    dataset = bigquery.Dataset(dataset_id)
    dataset.location = DATASET_LOCATION
    client.create_dataset(dataset, exists_ok=True)
    logger.info("Dataset prêt : %s (%s)", dataset_id, DATASET_LOCATION)
    return dataset_id


def load_table(client: bigquery.Client, dataset_id: str, spec: LoadSpec) -> int:
    """Charge un fichier de staging vers sa table BigQuery.

    Retourne le nombre de lignes chargées, ou lève une exception si le
    fichier de staging est introuvable ou vide.
    """
    staging_path = STAGING_DIR / f"{spec.staging_file}.parquet"
    if not staging_path.exists():
        raise FileNotFoundError(
            f"Fichier de staging introuvable : {staging_path} "
            "(avez-vous lancé extract.py avant load.py ?)"
        )

    df = pd.read_parquet(staging_path)
    if df.empty:
        logger.warning("  -> %s : fichier vide, table non modifiée", spec.bq_table)
        return 0

    table_id = f"{dataset_id}.{spec.bq_table}"
    job_config = bigquery.LoadJobConfig(write_disposition="WRITE_TRUNCATE")
    job = client.load_table_from_dataframe(df, table_id, job_config=job_config)
    job.result()  # attend la fin du job et lève une exception BigQuery si échec

    logger.info("  -> %s : %d lignes chargées", spec.bq_table, len(df))
    return len(df)


def run_load(table_names: list[str] | None = None) -> dict[str, int]:
    """Exécute le chargement pour les tables demandées (toutes par défaut).

    Comme pour l'ingestion, une table en échec n'interrompt pas les autres :
    -1 signale un échec dans le résultat retourné.
    """
    client = get_client()
    dataset_id = ensure_dataset(client, os.getenv("BQ_DATASET"))

    specs = LOAD_SPECS
    if table_names:
        wanted = set(table_names)
        specs = [s for s in LOAD_SPECS if s.staging_file in wanted]
        unknown = wanted - {s.staging_file for s in LOAD_SPECS}
        if unknown:
            logger.warning("Tables inconnues ignorées : %s", ", ".join(sorted(unknown)))

    logger.info("Début du chargement (%d table(s))", len(specs))
    results: dict[str, int] = {}
    for spec in specs:
        try:
            results[spec.bq_table] = load_table(client, dataset_id, spec)
        except Exception:
            logger.exception("Échec du chargement pour '%s'", spec.bq_table)
            results[spec.bq_table] = -1

    n_ok = sum(1 for v in results.values() if v >= 0)
    n_ko = sum(1 for v in results.values() if v < 0)
    logger.info("Chargement terminé : %d réussi(s), %d en échec", n_ok, n_ko)
    return results


# ----------------------------------------------------------------------------
# Entrée en ligne de commande
# ----------------------------------------------------------------------------

def main() -> int:
    parser = argparse.ArgumentParser(description="Chargement staging -> BigQuery")
    parser.add_argument(
        "--tables",
        nargs="+",
        default=None,
        help="Sous-ensemble de tables à charger, par nom de staging "
             "(ex: mouvement_stock produit_fini). Par défaut : toutes.",
    )
    args = parser.parse_args()

    results = run_load(args.tables)
    has_failure = any(v < 0 for v in results.values())
    return 1 if has_failure else 0


if __name__ == "__main__":
    sys.exit(main())