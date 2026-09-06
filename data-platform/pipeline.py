"""
pipeline.py — Orchestration du pipeline data bijoux.

Enchaîne les 3 étapes dans l'ordre, en s'arrêtant à la première qui échoue
(une étape lit ce que la précédente a produit, ça n'a aucun sens de
continuer sur des données partielles) :

    1. extract.py   : PostgreSQL -> staging/*.parquet
    2. load.py       : staging/*.parquet -> BigQuery (tables raw_*)
    3. transform.py  : tables raw_* -> tables de faits + vue_dashboard

Usage :
    python pipeline.py
"""

from __future__ import annotations

import logging
import sys
import time

from extract import run_extraction
from load import run_load
from transform import run_transform

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s | %(levelname)-8s | %(message)s",
    datefmt="%Y-%m-%d %H:%M:%S",
)
logger = logging.getLogger("pipeline")


def _has_failures(results: dict[str, int]) -> bool:
    """Un résultat négatif (-1) signale l'échec d'une table (voir extract.py/load.py)."""
    return any(v < 0 for v in results.values())


def run_stage(step_number: int, step_name: str, hint_on_error: str, func, *args) -> bool:
    """Exécute une étape du pipeline, chronomètre, logue, et catche les erreurs.

    `hint_on_error` donne un message d'aide adapté au contexte (Postgres,
    BigQuery...) affiché en plus du détail technique de l'exception, pour
    qu'un échec en pleine nuit (via cron) ne demande pas de relire le code
    pour comprendre où regarder.
    """
    logger.info("Étape %d/3 : %s...", step_number, step_name)
    start = time.perf_counter()
    try:
        result = func(*args)
    except Exception:
        elapsed = time.perf_counter() - start
        logger.exception(
            "Étape %d/3 (%s) : échec après %.1fs. %s",
            step_number, step_name, elapsed, hint_on_error,
        )
        return False

    elapsed = time.perf_counter() - start

    # extract.py et load.py retournent un dict {nom: nb_lignes|-1} ;
    # transform.py retourne directement un bool. On gère les deux formats
    # sans dupliquer la logique de détection d'échec dans chaque appelant.
    if isinstance(result, dict) and _has_failures(result):
        logger.error(
            "Étape %d/3 (%s) : terminée avec des échecs partiels en %.1fs. %s",
            step_number, step_name, elapsed, hint_on_error,
        )
        return False
    if isinstance(result, bool) and not result:
        logger.error(
            "Étape %d/3 (%s) : échec en %.1fs. %s",
            step_number, step_name, elapsed, hint_on_error,
        )
        return False

    logger.info("Étape %d/3 (%s) : terminée avec succès en %.1fs", step_number, step_name, elapsed)
    return True


def main() -> int:
    pipeline_start = time.perf_counter()
    logger.info("=== Démarrage du pipeline data bijoux ===")

    ok = run_stage(
        1, "ingestion PostgreSQL",
        "Vérifiez que PostgreSQL est démarré et que les identifiants du .env sont corrects.",
        run_extraction,
    )
    if not ok:
        logger.error("Pipeline arrêté après l'étape 1 (ingestion).")
        return 1

    ok = run_stage(
        2, "chargement BigQuery",
        "Vérifiez la connexion à BigQuery (clé de service account, GCP_PROJECT_ID, BQ_DATASET).",
        run_load,
    )
    if not ok:
        logger.error("Pipeline arrêté après l'étape 2 (chargement).")
        return 1

    ok = run_stage(
        3, "agrégation SQL",
        "Vérifiez transform.sql et les droits BigQuery du compte de service.",
        run_transform,
    )
    if not ok:
        logger.error("Pipeline arrêté après l'étape 3 (agrégation).")
        return 1

    total_elapsed = time.perf_counter() - pipeline_start
    logger.info("=== Pipeline terminé avec succès en %.1fs ===", total_elapsed)
    return 0


if __name__ == "__main__":
    sys.exit(main())