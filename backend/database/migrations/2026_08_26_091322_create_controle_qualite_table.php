<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('controle_qualite', function (Blueprint $table) {
            $table->id('id_controle');
            $table->unsignedBigInteger('id_of');
            $table->timestamp('date_controle')->useCurrent();
            $table->integer('quantite_controlee');
            $table->integer('quantite_validee');
            $table->integer('quantite_rejetee');
            $table->text('commentaire')->nullable();

            $table->foreign('id_of', 'fk_controle_of')
                ->references('id_of')->on('ordre_fabrication');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('controle_qualite');
    }
};