<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION fn_appliquer_mouvement_stock()
            RETURNS TRIGGER AS $$
            DECLARE
                v_sens sens_mouvement;
            BEGIN
                SELECT sens INTO v_sens FROM type_mouvement WHERE id_type_mvt = NEW.id_type_mvt;

                IF v_sens = 'ENTREE' THEN
                    UPDATE matiere_premiere
                    SET quantite_stock = quantite_stock + NEW.quantite
                    WHERE id_matiere = NEW.id_matiere;
                ELSE
                    IF (SELECT quantite_stock FROM matiere_premiere WHERE id_matiere = NEW.id_matiere) < NEW.quantite THEN
                        RAISE EXCEPTION 'Stock insuffisant pour la matière %', NEW.id_matiere;
                    END IF;
                    UPDATE matiere_premiere
                    SET quantite_stock = quantite_stock - NEW.quantite
                    WHERE id_matiere = NEW.id_matiere;
                END IF;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        SQL);

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER tr_mouvement_stock_apply
                AFTER INSERT ON mouvement_stock
                FOR EACH ROW EXECUTE FUNCTION fn_appliquer_mouvement_stock();
        SQL);

        DB::unprepared(<<<'SQL'
            CREATE VIEW vue_stock_alertes AS
            SELECT id_matiere, nom, quantite_stock, seuil_alerte
            FROM matiere_premiere
            WHERE quantite_stock <= seuil_alerte AND actif = TRUE;
        SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP VIEW IF EXISTS vue_stock_alertes');
        DB::unprepared('DROP TRIGGER IF EXISTS tr_mouvement_stock_apply ON mouvement_stock');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_appliquer_mouvement_stock');
    }
};