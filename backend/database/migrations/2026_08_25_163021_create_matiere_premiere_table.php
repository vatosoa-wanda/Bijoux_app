<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matiere_premiere', function (Blueprint $table) {
            $table->id('id_matiere');
            $table->unsignedBigInteger('id_categorie');
            $table->unsignedBigInteger('id_unite');
            $table->string('nom', 100);
            $table->string('couleur', 50)->nullable();
            $table->decimal('quantite_stock', 10, 2)->default(0);
            $table->decimal('seuil_alerte', 10, 2);
            $table->decimal('prix_unitaire', 10, 4);
            $table->date('date_peremption')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->foreign('id_categorie', 'fk_matiere_categorie')
                ->references('id_categorie')->on('categorie_matiere');
            $table->foreign('id_unite', 'fk_matiere_unite')
                ->references('id_unite')->on('unite_mesure');

            $table->index('id_categorie', 'idx_matiere_categorie');
            $table->index(['quantite_stock', 'seuil_alerte'], 'idx_matiere_stock');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matiere_premiere');
    }
};