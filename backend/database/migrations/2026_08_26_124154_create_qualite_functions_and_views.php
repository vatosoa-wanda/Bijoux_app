<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Trigger : à l'insertion d'un contrôle qualité, incrémente les rejets de l'OF
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION fn_incrementer_rejet()
            RETURNS TRIGGER AS $$
            BEGIN
                UPDATE ordre_fabrication
                SET quantite_rejetee = quantite_rejetee + NEW.quantite_rejetee
                WHERE id_of = NEW.id_of;

                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        SQL);

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER tr_controle_qualite_rejet
                AFTER INSERT ON controle_qualite
                FOR EACH ROW EXECUTE FUNCTION fn_incrementer_rejet();
        SQL);

        // Vue : taux de rejet agrégé par bijou
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE VIEW vue_taux_rejet_par_bijou AS
            SELECT
                b.id_bijou,
                b.nom,
                COALESCE(SUM(cq.quantite_controlee), 0) AS total_controle,
                COALESCE(SUM(cq.quantite_rejetee), 0) AS total_rejete,
                ROUND(
                    100.0 * COALESCE(SUM(cq.quantite_rejetee), 0)
                    / NULLIF(SUM(cq.quantite_controlee), 0), 2
                ) AS taux_rejet_pct
            FROM bijou b
            LEFT JOIN ordre_fabrication of2 ON of2.id_bijou = b.id_bijou
            LEFT JOIN controle_qualite cq ON cq.id_of = of2.id_of
            GROUP BY b.id_bijou, b.nom;
        SQL);

        // Vue : répartition des défauts par type
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE VIEW vue_defauts_par_type AS
            SELECT
                td.id_type_defaut,
                td.libelle,
                COALESCE(SUM(dc.quantite), 0) AS total_quantite
            FROM type_defaut td
            LEFT JOIN defaut_constate dc ON dc.id_type_defaut = td.id_type_defaut
            GROUP BY td.id_type_defaut, td.libelle
            ORDER BY total_quantite DESC;
        SQL);

        // Vue : KPI dashboard
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE VIEW vue_kpi_dashboard AS
            SELECT
                (SELECT COUNT(*) FROM ordre_fabrication of2
                    JOIN statut_production sp ON sp.id_statut_prod = of2.id_statut_prod
                    WHERE sp.code = 'EN_COURS') AS bijoux_en_cours,
                (SELECT COUNT(*) FROM ordre_fabrication of2
                    JOIN statut_production sp ON sp.id_statut_prod = of2.id_statut_prod
                    WHERE sp.code = 'TERMINE') AS ordres_termines,
                (SELECT COALESCE(SUM(quantite_rejetee), 0) FROM ordre_fabrication) AS total_rejetes,
                (SELECT ROUND(AVG(fn_cout_revient(id_bijou)), 2) FROM bijou WHERE actif) AS cout_moyen_bijou,
                (SELECT COALESCE(SUM(quantite_stock), 0) FROM produit_fini WHERE statut = 'EN_STOCK') AS produits_en_stock;
        SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP VIEW IF EXISTS vue_kpi_dashboard');
        DB::unprepared('DROP VIEW IF EXISTS vue_defauts_par_type');
        DB::unprepared('DROP VIEW IF EXISTS vue_taux_rejet_par_bijou');
        DB::unprepared('DROP TRIGGER IF EXISTS tr_controle_qualite_rejet ON controle_qualite');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_incrementer_rejet');
    }
};