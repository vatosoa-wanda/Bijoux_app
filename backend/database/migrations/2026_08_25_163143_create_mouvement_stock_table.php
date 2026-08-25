<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mouvement_stock', function (Blueprint $table) {
            $table->id('id_mouvement');
            $table->unsignedBigInteger('id_matiere');
            $table->unsignedBigInteger('id_type_mvt');
            $table->decimal('quantite', 10, 2);
            $table->decimal('prix_total', 10, 2)->nullable();
            $table->string('reference_externe', 100)->nullable();
            $table->text('commentaire')->nullable();
            $table->timestamp('date_mouvement')->useCurrent();

            $table->foreign('id_matiere', 'fk_mouvement_matiere')
                ->references('id_matiere')->on('matiere_premiere');
            $table->foreign('id_type_mvt', 'fk_mouvement_type')
                ->references('id_type_mvt')->on('type_mouvement');

            $table->index('date_mouvement', 'idx_mouvement_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mouvement_stock');
    }
};