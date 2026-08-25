<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bijou', function (Blueprint $table) {
            $table->id('id_bijou');
            $table->unsignedBigInteger('id_type_bijou');
            $table->unsignedBigInteger('id_collection')->nullable();
            $table->string('reference', 50)->unique();
            $table->string('nom', 100);
            $table->integer('temps_fabrication_minutes');
            $table->string('photo_url', 255)->nullable();
            $table->text('description')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->foreign('id_type_bijou', 'fk_bijou_type')
                ->references('id_type_bijou')->on('type_bijou');
            $table->foreign('id_collection', 'fk_bijou_collection')
                ->references('id_collection')->on('collection');

            $table->index('id_type_bijou', 'idx_bijou_type');
            $table->index('id_collection', 'idx_bijou_collection');
        });

        // Colonnes ENUM custom ajoutées en SQL brut (non supportées nativement par le Schema Builder)
        DB::statement("ALTER TABLE bijou ADD COLUMN taille taille_bijou DEFAULT 'M'");
        DB::statement("ALTER TABLE bijou ADD COLUMN complexite complexite_bijou DEFAULT 'Moyenne'");
    }

    public function down(): void
    {
        Schema::dropIfExists('bijou');
    }
};