<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('type_mouvement', function (Blueprint $table) {
            $table->id('id_type_mvt');
            $table->string('code', 20)->unique();
            $table->string('libelle', 50);
        });

        // Le Schema Builder de Laravel ne connaît pas les types ENUM custom PostgreSQL,
        // on ajoute donc la colonne en SQL brut juste après la création de la table.
        DB::statement('ALTER TABLE type_mouvement ADD COLUMN sens sens_mouvement NOT NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('type_mouvement');
    }
};