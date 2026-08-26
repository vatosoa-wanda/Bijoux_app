<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consommation_of', function (Blueprint $table) {
            $table->id('id_consommation');
            $table->unsignedBigInteger('id_of');
            $table->unsignedBigInteger('id_matiere');
            $table->decimal('quantite_consommee', 10, 2);
            $table->timestamp('date_consommation')->useCurrent();

            $table->foreign('id_of', 'fk_consommation_of')
                ->references('id_of')->on('ordre_fabrication')->cascadeOnDelete();
            $table->foreign('id_matiere', 'fk_consommation_matiere')
                ->references('id_matiere')->on('matiere_premiere');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consommation_of');
    }
};