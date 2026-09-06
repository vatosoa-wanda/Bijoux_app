-- ============================================================================
-- transform.sql — Étape Agrégation du pipeline data bijoux
--
-- Chaque bloc est précédé d'un marqueur "-- @name: <nom>" lu par transform.py
-- pour exécuter les requêtes une par une, dans l'ordre, et pouvoir les
-- relancer individuellement (--queries fact_stock_mensuel par exemple).
--
-- {dataset} est remplacé par transform.py avec l'id complet du dataset
-- (ex: bijoux-data-platform.analytics_bijoux) avant exécution.
-- ============================================================================


-- @name: fact_stock_mensuel
-- Entrées / sorties de matières premières agrégées par mois et par matière.
CREATE OR REPLACE TABLE `{dataset}.fact_stock_mensuel` AS
SELECT
  DATE_TRUNC(DATE(date_mouvement), MONTH)                          AS mois,
  id_matiere,
  SUM(CASE WHEN sens = 'ENTREE' THEN quantite ELSE 0 END)          AS total_entrees,
  SUM(CASE WHEN sens = 'SORTIE' THEN quantite ELSE 0 END)          AS total_sorties
FROM `{dataset}.raw_mouvement_stock`
GROUP BY mois, id_matiere;


-- @name: fact_qualite_mensuelle
-- Taux de rejet par bijou et par mois.
--
-- Point d'attention : raw_controle_qualite a été extraite avec un LEFT JOIN
-- sur defaut_constate (voir extract.py). Un contrôle avec 3 défauts donne
-- donc 3 lignes dans raw_controle_qualite, chacune répétant les colonnes
-- quantite_controlee / quantite_rejetee du contrôle. Sommer directement ces
-- colonnes sans dédoublonner triplerait les totaux. On déduplique donc
-- d'abord au grain "un contrôle = une ligne" avant d'agréger.
-- Autre point : raw_controle_qualite n'a pas id_bijou (seulement id_of), il
-- faut donc joindre raw_ordre_fabrication pour retrouver le bijou concerné.
CREATE OR REPLACE TABLE `{dataset}.fact_qualite_mensuelle` AS
WITH controle_dedup AS (
  SELECT DISTINCT
    id_controle,
    id_of,
    date_controle,
    quantite_controlee,
    quantite_rejetee
  FROM `{dataset}.raw_controle_qualite`
),
controle_avec_bijou AS (
  SELECT
    cd.date_controle,
    cd.quantite_controlee,
    cd.quantite_rejetee,
    ofa.id_bijou
  FROM controle_dedup cd
  JOIN `{dataset}.raw_ordre_fabrication` ofa ON ofa.id_of = cd.id_of
)
SELECT
  DATE_TRUNC(DATE(date_controle), MONTH)                                        AS mois,
  id_bijou,
  SUM(quantite_controlee)                                                       AS total_controle,
  SUM(quantite_rejetee)                                                         AS total_rejete,
  ROUND(SAFE_DIVIDE(SUM(quantite_rejetee), SUM(quantite_controlee)) * 100, 2)   AS taux_rejet_pct
FROM controle_avec_bijou
GROUP BY mois, id_bijou;


-- @name: fact_defauts_mensuels
-- Bonus : répartition des défauts par type et par mois. Contrairement à la
-- table précédente, ici on NE déduplique PAS : quantite_defaut est bien une
-- valeur par ligne de défaut, donc le grain "une ligne = un défaut" de
-- raw_controle_qualite est le bon pour cette agrégation.
CREATE OR REPLACE TABLE `{dataset}.fact_defauts_mensuels` AS
SELECT
  DATE_TRUNC(DATE(date_controle), MONTH) AS mois,
  defaut_code,
  defaut_libelle,
  SUM(quantite_defaut)                    AS total_defauts
FROM `{dataset}.raw_controle_qualite`
WHERE id_defaut IS NOT NULL
GROUP BY mois, defaut_code, defaut_libelle;


-- @name: fact_rentabilite_collection
-- Coût moyen, prix de vente moyen et marge par collection et par mois.
CREATE OR REPLACE TABLE `{dataset}.fact_rentabilite_collection` AS
SELECT
  DATE_TRUNC(DATE(pf.date_fabrication), MONTH)                                              AS mois,
  b.id_collection,
  COALESCE(col.nom, 'Sans collection')                                                      AS collection_nom,
  COUNT(pf.id_produit_fini)                                                                 AS nb_produits_fabriques,
  ROUND(AVG(pf.cout_revient), 2)                                                             AS cout_moyen,
  ROUND(AVG(pf.prix_vente), 2)                                                               AS prix_vente_moyen,
  ROUND(AVG(pf.prix_vente - pf.cout_revient), 2)                                             AS marge_moyenne,
  ROUND(SAFE_DIVIDE(SUM(pf.prix_vente - pf.cout_revient), NULLIF(SUM(pf.cout_revient), 0)) * 100, 2) AS marge_pct
FROM `{dataset}.raw_produit_fini` pf
JOIN `{dataset}.raw_bijou` b       ON b.id_bijou = pf.id_bijou
LEFT JOIN `{dataset}.raw_collection` col ON col.id_collection = b.id_collection
WHERE pf.date_fabrication IS NOT NULL
GROUP BY mois, b.id_collection, collection_nom;


-- @name: vue_dashboard
-- Vue finale : un indicateur clé par mois, prête à être branchée sur un
-- outil de BI (Looker Studio, Metabase). C'est une VIEW (pas une TABLE) :
-- elle se recalcule à chaque lecture à partir des tables de faits ci-dessus,
-- donc toujours à jour sans avoir besoin d'être rechargée séparément.
CREATE OR REPLACE VIEW `{dataset}.vue_dashboard` AS
WITH stock AS (
  SELECT
    mois,
    SUM(total_entrees) AS total_entrees,
    SUM(total_sorties) AS total_sorties
  FROM `{dataset}.fact_stock_mensuel`
  GROUP BY mois
),
qualite AS (
  SELECT
    mois,
    SUM(total_controle)                                                       AS total_controle,
    SUM(total_rejete)                                                         AS total_rejete,
    ROUND(SAFE_DIVIDE(SUM(total_rejete), SUM(total_controle)) * 100, 2)      AS taux_rejet_pct
  FROM `{dataset}.fact_qualite_mensuelle`
  GROUP BY mois
),
rentabilite AS (
  SELECT
    mois,
    ROUND(AVG(marge_moyenne), 2) AS marge_moyenne_globale,
    ROUND(AVG(marge_pct), 2)     AS marge_pct_globale
  FROM `{dataset}.fact_rentabilite_collection`
  GROUP BY mois
)
SELECT
  COALESCE(stock.mois, qualite.mois, rentabilite.mois) AS mois,
  stock.total_entrees,
  stock.total_sorties,
  qualite.total_controle,
  qualite.total_rejete,
  qualite.taux_rejet_pct,
  rentabilite.marge_moyenne_globale,
  rentabilite.marge_pct_globale
FROM stock
FULL OUTER JOIN qualite      ON qualite.mois = stock.mois
FULL OUTER JOIN rentabilite  ON rentabilite.mois = COALESCE(stock.mois, qualite.mois)
ORDER BY mois DESC;