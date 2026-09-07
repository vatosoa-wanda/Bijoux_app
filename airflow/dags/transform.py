"""
transform.py — Étape Agrégation du pipeline data bijoux.

Rôle : lire transform.sql, découper les requêtes par marqueur `-- @name:`,
remplacer {dataset} par l'id complet du dataset, et exécuter chaque requête
dans BigQuery pour produire les tables de faits + la vue dashboard.

Contrairement à extract.py / load.py (où chaque table est indépendante),
ici l'ordre compte : vue_dashboard lit les tables de faits créées juste
avant. On s'arrête donc à la première erreur (fail-fast) plutôt que de
continuer sur les requêtes suivantes.

Usage :
    python transform.py                              # exécute tout, dans l'ordre du fichier
    python transform.py --queries fact_stock_mensuel  # une seule requête
"""

from __future__ import annotations

import argparse
import logging
import os
import re
import sys
from pathlib import Path

from google.api_core.exceptions import NotFound

from load import ensure_dataset, get_client

SQL_FILE = Path(__file__).parent / "transform.sql"

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s | %(levelname)-8s | %(message)s",
    datefmt="%Y-%m-%d %H:%M:%S",
)
logger = logging.getLogger("transform")

# Découpe le fichier sur les lignes "-- @name: xxx" et capture le nom.
NAME_MARKER = re.compile(r"^-- @name:\s*(\w+)\s*$", re.MULTILINE)


def load_queries(dataset_id: str) -> list[tuple[str, str]]:
    """Parse transform.sql en liste ordonnée de (nom, requête prête à exécuter)."""
    if not SQL_FILE.exists():
        raise FileNotFoundError(f"Fichier introuvable : {SQL_FILE}")

    text = SQL_FILE.read_text(encoding="utf-8")
    chunks = NAME_MARKER.split(text)
    # re.split avec groupe capturant renvoie : [avant, nom1, corps1, nom2, corps2, ...]
    queries: list[tuple[str, str]] = []
    for i in range(1, len(chunks), 2):
        name = chunks[i].strip()
        body = chunks[i + 1].strip()
        if body:
            queries.append((name, body.format(dataset=dataset_id)))
    return queries


def run_query(client, name: str, sql: str, dataset_id: str) -> None:
    """Exécute une requête et logge un indicateur de résultat adapté au type d'objet créé."""
    job = client.query(sql)
    job.result()  # attend la fin du job, lève une exception BigQuery si échec

    # Une vue n'a pas de num_rows exploitable ; on ne tente de compter que
    # pour les objets qui sont des tables physiques.
    try:
        table = client.get_table(f"{dataset_id}.{name}")
        if table.table_type == "TABLE":
            logger.info("  -> %s : table créée (%d lignes)", name, table.num_rows)
        else:
            logger.info("  -> %s : vue créée avec succès", name)
    except NotFound:
        logger.info("  -> %s : exécuté avec succès", name)


def run_transform(query_names: list[str] | None = None) -> bool:
    """Exécute les requêtes demandées (toutes par défaut), dans l'ordre du fichier.

    Retourne True si tout s'est bien passé, False dès la première erreur.
    """
    client = get_client()
    dataset_id = ensure_dataset(client, os.getenv("BQ_DATASET"))

    queries = load_queries(dataset_id)
    if query_names:
        wanted = set(query_names)
        queries = [(n, sql) for n, sql in queries if n in wanted]
        unknown = wanted - {n for n, _ in queries}
        if unknown:
            logger.warning("Requêtes inconnues ignorées : %s", ", ".join(sorted(unknown)))

    logger.info("Début de l'agrégation (%d requête(s))", len(queries))
    for name, sql in queries:
        try:
            run_query(client, name, sql, dataset_id)
        except Exception:
            logger.exception(
                "Échec sur '%s' — arrêt (les étapes suivantes peuvent en dépendre)", name
            )
            return False

    logger.info("Agrégation terminée avec succès (%d requête(s))", len(queries))
    return True


def main() -> int:
    parser = argparse.ArgumentParser(description="Agrégation staging BigQuery -> tables de faits")
    parser.add_argument(
        "--queries",
        nargs="+",
        default=None,
        help="Sous-ensemble de requêtes à exécuter, par nom "
             "(ex: fact_stock_mensuel vue_dashboard). Par défaut : toutes, dans l'ordre.",
    )
    args = parser.parse_args()

    success = run_transform(args.queries)
    return 0 if success else 1


if __name__ == "__main__":
    sys.exit(main())