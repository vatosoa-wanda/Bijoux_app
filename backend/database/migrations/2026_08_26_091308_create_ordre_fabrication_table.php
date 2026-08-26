<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordre_fabrication', function (Blueprint $table) {
            $table->id('id_of');
            $table->unsignedBigInteger('id_bijou');
            $table->unsignedBigInteger('id_statut_prod');
            // FK vers "commande" volontairement omise : module Commandes hors périmètre du sprint.
            $table->unsignedBigInteger('id_commande')->nullable();
            $table->string('reference', 50)->unique();
            $table->integer('quantite_prevue');
            $table->integer('quantite_realisee')->default(0);
            $table->integer('quantite_rejetee')->default(0);
            $table->date('date_debut')->nullable();
            $table->date('date_fin_prevue')->nullable();
            $table->date('date_fin_reelle')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->foreign('id_bijou', 'fk_of_bijou')
                ->references('id_bijou')->on('bijou');
            $table->foreign('id_statut_prod', 'fk_of_statut')
                ->references('id_statut_prod')->on('statut_production');

            $table->index('id_bijou', 'idx_of_bijou');
            $table->index('id_statut_prod', 'idx_of_statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordre_fabrication');
    }
};