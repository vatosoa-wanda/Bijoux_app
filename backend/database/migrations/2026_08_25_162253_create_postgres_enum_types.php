<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Crée les types ENUM natifs PostgreSQL utilisés par plusieurs tables.
     * Ils doivent exister AVANT les migrations qui créent les colonnes les utilisant.
     */
    public function up(): void
    {
        DB::statement('DROP TYPE IF EXISTS sens_mouvement CASCADE');
        DB::statement("CREATE TYPE sens_mouvement AS ENUM ('ENTREE', 'SORTIE')");

        DB::statement('DROP TYPE IF EXISTS taille_bijou CASCADE');
        DB::statement("CREATE TYPE taille_bijou AS ENUM ('XS', 'S', 'M', 'L', 'XL')");

        DB::statement('DROP TYPE IF EXISTS complexite_bijou CASCADE');
        DB::statement("CREATE TYPE complexite_bijou AS ENUM ('Simple', 'Moyenne', 'Complexe')");

        DB::statement('DROP TYPE IF EXISTS statut_produit CASCADE');
        DB::statement("CREATE TYPE statut_produit AS ENUM ('EN_STOCK', 'RESERVE', 'VENDU')");
    }

    public function down(): void
    {
        DB::statement('DROP TYPE IF EXISTS sens_mouvement');
        DB::statement('DROP TYPE IF EXISTS taille_bijou');
        DB::statement('DROP TYPE IF EXISTS complexite_bijou');
        DB::statement('DROP TYPE IF EXISTS statut_produit');
    }
};