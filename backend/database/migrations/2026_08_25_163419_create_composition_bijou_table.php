<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('composition_bijou', function (Blueprint $table) {
            $table->id('id_composition');
            $table->unsignedBigInteger('id_bijou');
            $table->unsignedBigInteger('id_matiere');
            $table->decimal('quantite_necessaire', 10, 2);

            $table->foreign('id_bijou', 'fk_composition_bijou')
                ->references('id_bijou')->on('bijou')->cascadeOnDelete();
            $table->foreign('id_matiere', 'fk_composition_matiere')
                ->references('id_matiere')->on('matiere_premiere');

            $table->unique(['id_bijou', 'id_matiere'], 'unique_bijou_matiere');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('composition_bijou');
    }
};