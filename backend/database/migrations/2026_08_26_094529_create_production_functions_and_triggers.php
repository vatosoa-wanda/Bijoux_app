<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Supprime les anciennes signatures (INT) qui traînent d'un run précédent
        DB::unprepared('DROP FUNCTION IF EXISTS fn_cout_revient(INT)');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_verifier_disponibilite(INT, INT)');

        // Fonction : calcul du coût de revient d'un bijou
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION fn_cout_revient(p_id_bijou BIGINT)
            RETURNS DECIMAL(10,2) AS $$
            DECLARE
                v_cout_matieres DECIMAL(10,2);
                v_temps_min INT;
                v_taux_horaire DECIMAL(10,2);
                v_charges DECIMAL(10,2);
            BEGIN
                SELECT COALESCE(SUM(cb.quantite_necessaire * mp.prix_unitaire), 0)
                INTO v_cout_matieres
                FROM composition_bijou cb
                JOIN matiere_premiere mp ON mp.id_matiere = cb.id_matiere
                WHERE cb.id_bijou = p_id_bijou;

                SELECT temps_fabrication_minutes INTO v_temps_min FROM bijou WHERE id_bijou = p_id_bijou;
                SELECT valeur::DECIMAL INTO v_taux_horaire FROM parametre WHERE cle = 'taux_horaire';
                SELECT valeur::DECIMAL INTO v_charges FROM parametre WHERE cle = 'charges_indirectes_forfait';

                RETURN v_cout_matieres + (v_temps_min / 60.0 * v_taux_horaire) + COALESCE(v_charges, 0);
            END;
            $$ LANGUAGE plpgsql;
        SQL);

        // Fonction : vérifie que le stock suffit pour produire p_quantite unités de p_id_bijou
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION fn_verifier_disponibilite(p_id_bijou BIGINT, p_quantite INT)
            RETURNS BOOLEAN AS $$
            DECLARE
                v_manque INT;
            BEGIN
                SELECT COUNT(*) INTO v_manque
                FROM composition_bijou cb
                JOIN matiere_premiere mp ON mp.id_matiere = cb.id_matiere
                WHERE cb.id_bijou = p_id_bijou
                  AND mp.quantite_stock < (cb.quantite_necessaire * p_quantite);

                RETURN v_manque = 0;
            END;
            $$ LANGUAGE plpgsql;
        SQL);

        // Fonction + trigger : clôture d'un OF -> consommation matières + création produit fini
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION fn_cloturer_of()
            RETURNS TRIGGER AS $$
            DECLARE
                r RECORD;
                v_id_type_sortie INT;
                v_code_statut VARCHAR;
            BEGIN
                SELECT code INTO v_code_statut FROM statut_production WHERE id_statut_prod = NEW.id_statut_prod;

                IF NEW.id_statut_prod <> OLD.id_statut_prod AND v_code_statut = 'TERMINE' THEN

                    SELECT id_type_mvt INTO v_id_type_sortie FROM type_mouvement WHERE code = 'PRODUCTION';

                    FOR r IN
                        SELECT id_matiere, quantite_necessaire * NEW.quantite_realisee AS qte
                        FROM composition_bijou WHERE id_bijou = NEW.id_bijou
                    LOOP
                        INSERT INTO consommation_of(id_of, id_matiere, quantite_consommee)
                        VALUES (NEW.id_of, r.id_matiere, r.qte);

                        INSERT INTO mouvement_stock(id_matiere, id_type_mvt, quantite, reference_externe)
                        VALUES (r.id_matiere, v_id_type_sortie, r.qte, NEW.reference);
                    END LOOP;

                    INSERT INTO produit_fini(id_bijou, id_of, reference, quantite_stock, cout_revient, date_fabrication)
                    VALUES (NEW.id_bijou, NEW.id_of, NEW.reference || '-PF', NEW.quantite_realisee,
                            fn_cout_revient(NEW.id_bijou), CURRENT_DATE);
                END IF;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        SQL);

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER tr_of_cloture
                AFTER UPDATE ON ordre_fabrication
                FOR EACH ROW EXECUTE FUNCTION fn_cloturer_of();
        SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS tr_of_cloture ON ordre_fabrication');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_cloturer_of');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_verifier_disponibilite');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_cout_revient');
    }
};