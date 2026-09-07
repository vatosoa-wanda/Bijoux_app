"""
extract.py — Étape Ingestion du pipeline data bijoux.

Rôle : se connecter à PostgreSQL (base transactionnelle), extraire les tables
nécessaires à l'analyse (avec leurs jointures de référence), et les déposer
en zone de staging (fichiers .parquet) avant chargement dans BigQuery.

Usage :
    python extract.py                # extrait toutes les tables
    python extract.py --tables mouvement_stock produit_fini   # extrait un sous-ensemble
"""

from __future__ import annotations

import argparse
import logging
import sys
from dataclasses import dataclass
from datetime import datetime, timezone
from pathlib import Path

import pandas as pd
from dotenv import load_dotenv
from sqlalchemy import create_engine
from sqlalchemy.engine import Engine
import os

# ----------------------------------------------------------------------------
# Configuration
# ----------------------------------------------------------------------------

STAGING_DIR = Path(__file__).parent / "staging"

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s | %(levelname)-8s | %(message)s",
    datefmt="%Y-%m-%d %H:%M:%S",
)
logger = logging.getLogger("extract")


@dataclass(frozen=True)
class TableSpec:
    """Définit une extraction : nom de sortie + requête SQL source."""
    name: str
    query: str


# Une requête par table à extraire. Les jointures ramènent le contexte
# nécessaire à l'agrégation (libellés, statuts) sans dupliquer toute la
# base : on reste sur le strict nécessaire défini dans le todo.
TABLE_SPECS: list[TableSpec] = [
    TableSpec(
        name="mouvement_stock",
        query="""
            SELECT
                ms.id_mouvement,
                ms.id_matiere,
                mp.nom            AS matiere_nom,
                mp.id_categorie,
                ms.id_type_mvt,
                tm.code           AS type_mouvement_code,
                tm.sens,
                ms.quantite,
                ms.prix_total,
                ms.reference_externe,
                ms.commentaire,
                ms.date_mouvement
            FROM mouvement_stock ms
            JOIN matiere_premiere mp ON mp.id_matiere = ms.id_matiere
            JOIN type_mouvement   tm ON tm.id_type_mvt = ms.id_type_mvt
        """,
    ),
    TableSpec(
        name="ordre_fabrication",
        query="""
            SELECT
                ofa.id_of,
                ofa.id_bijou,
                b.nom             AS bijou_nom,
                b.reference       AS bijou_reference,
                ofa.id_statut_prod,
                sp.code           AS statut_code,
                sp.libelle        AS statut_libelle,
                ofa.id_commande,
                ofa.reference,
                ofa.quantite_prevue,
                ofa.quantite_realisee,
                ofa.quantite_rejetee,
                ofa.date_debut,
                ofa.date_fin_prevue,
                ofa.date_fin_reelle
            FROM ordre_fabrication ofa
            JOIN bijou             b  ON b.id_bijou = ofa.id_bijou
            JOIN statut_production sp ON sp.id_statut_prod = ofa.id_statut_prod
        """,
    ),
    TableSpec(
        name="controle_qualite",
        query="""
            SELECT
                cq.id_controle,
                cq.id_of,
                cq.date_controle,
                cq.quantite_controlee,
                cq.quantite_validee,
                cq.quantite_rejetee,
                cq.commentaire     AS commentaire_controle,
                dc.id_defaut,
                dc.id_type_defaut,
                td.code            AS defaut_code,
                td.libelle         AS defaut_libelle,
                dc.quantite        AS quantite_defaut,
                dc.commentaire     AS commentaire_defaut
            FROM controle_qualite cq
            LEFT JOIN defaut_constate dc ON dc.id_controle = cq.id_controle
            LEFT JOIN type_defaut     td ON td.id_type_defaut = dc.id_type_defaut
        """,
    ),
    TableSpec(
        name="produit_fini",
        query="SELECT * FROM produit_fini",
    ),
    TableSpec(
        name="bijou",
        query="SELECT * FROM bijou",
    ),
    TableSpec(
        name="collection",
        query="SELECT * FROM collection",
    ),
]


# ----------------------------------------------------------------------------
# Logique d'extraction
# ----------------------------------------------------------------------------

def get_engine() -> Engine:
    """Construit l'engine SQLAlchemy à partir des variables d'environnement."""
    load_dotenv()
    required = ["PGHOST", "PGPORT", "PGDATABASE", "PGUSER", "PGPASSWORD"]
    missing = [var for var in required if not os.getenv(var)]
    if missing:
        raise RuntimeError(
            f"Variables d'environnement manquantes dans .env : {', '.join(missing)}"
        )

    url = (
        f"postgresql+psycopg2://{os.getenv('PGUSER')}:{os.getenv('PGPASSWORD')}"
        f"@{os.getenv('PGHOST')}:{os.getenv('PGPORT')}/{os.getenv('PGDATABASE')}"
    )
    # pool_pre_ping évite de garder une connexion morte entre deux runs du cron
    return create_engine(url, pool_pre_ping=True)


def extract_table(engine: Engine, spec: TableSpec) -> int:
    """Extrait une table, ajoute la colonne de traçabilité, écrit en parquet.

    Retourne le nombre de lignes extraites (0 si la table est vide).
    """
    df = pd.read_sql(spec.query, engine)
    df["date_extraction"] = datetime.now(timezone.utc)

    output_path = STAGING_DIR / f"{spec.name}.parquet"
    df.to_parquet(output_path, index=False)

    logger.info("  -> %s : %d lignes -> %s", spec.name, len(df), output_path.name)
    return len(df)


def run_extraction(table_names: list[str] | None = None) -> dict[str, int]:
    """Exécute l'extraction pour les tables demandées (toutes par défaut).

    Une table en échec n'interrompt pas les autres : le résultat contient
    -1 pour les tables en erreur, afin que l'appelant (pipeline.py) décide
    de la suite (retry, alerte, arrêt...).
    """
    STAGING_DIR.mkdir(parents=True, exist_ok=True)
    engine = get_engine()

    specs = TABLE_SPECS
    if table_names:
        wanted = set(table_names)
        specs = [s for s in TABLE_SPECS if s.name in wanted]
        unknown = wanted - {s.name for s in TABLE_SPECS}
        if unknown:
            logger.warning("Tables inconnues ignorées : %s", ", ".join(sorted(unknown)))

    logger.info("Début de l'ingestion (%d table(s))", len(specs))
    results: dict[str, int] = {}
    for spec in specs:
        try:
            results[spec.name] = extract_table(engine, spec)
        except Exception:
            logger.exception("Échec de l'extraction pour '%s'", spec.name)
            results[spec.name] = -1

    engine.dispose()

    n_ok = sum(1 for v in results.values() if v >= 0)
    n_ko = sum(1 for v in results.values() if v < 0)
    logger.info("Ingestion terminée : %d réussie(s), %d en échec", n_ok, n_ko)
    return results


# ----------------------------------------------------------------------------
# Entrée en ligne de commande
# ----------------------------------------------------------------------------

def main() -> int:
    parser = argparse.ArgumentParser(description="Ingestion PostgreSQL -> staging")
    parser.add_argument(
        "--tables",
        nargs="+",
        default=None,
        help="Sous-ensemble de tables à extraire (par défaut : toutes)",
    )
    args = parser.parse_args()

    results = run_extraction(args.tables)
    has_failure = any(v < 0 for v in results.values())
    return 1 if has_failure else 0


if __name__ == "__main__":
    sys.exit(main())