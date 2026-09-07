"""
airflow_tasks.py — Couche d'intégration entre le pipeline existant et Airflow.

Airflow ne regarde jamais la valeur de retour d'une tâche pour décider si
elle a réussi ou échoué : il ne se base que sur les exceptions non
interceptées. Or extract.py/load.py renvoient un dict {nom: nb_lignes|-1}
et transform.py renvoie un bool — aucun des deux ne lève d'exception en cas
d'échec partiel (c'est fait exprès pour pipeline.py, qui lui inspecte ces
valeurs de retour).

Ce fichier fait le pont : il appelle les fonctions existantes SANS les
modifier, puis lève une exception si un échec est détecté, pour qu'Airflow
marque la tâche correspondante en rouge et déclenche ses retries.
"""

from extract import run_extraction
from load import run_load
from transform import run_transform


def extract_task() -> None:
    """Tâche Airflow : ingestion PostgreSQL -> staging/*.parquet."""
    results = run_extraction()
    failed = [name for name, count in results.items() if count < 0]
    if failed:
        raise RuntimeError(f"Échec de l'extraction pour : {', '.join(failed)}")


def load_task() -> None:
    """Tâche Airflow : staging/*.parquet -> BigQuery (tables raw_*)."""
    results = run_load()
    failed = [name for name, count in results.items() if count < 0]
    if failed:
        raise RuntimeError(f"Échec du chargement pour : {', '.join(failed)}")


def transform_task() -> None:
    """Tâche Airflow : raw_* -> tables de faits + vue_dashboard."""
    if not run_transform():
        raise RuntimeError("Échec de l'agrégation SQL (voir les logs de transform.py ci-dessus)")