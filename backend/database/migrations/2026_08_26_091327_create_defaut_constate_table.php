<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('defaut_constate', function (Blueprint $table) {
            $table->id('id_defaut');
            $table->unsignedBigInteger('id_controle');
            $table->unsignedBigInteger('id_type_defaut');
            $table->integer('quantite');
            $table->string('photo_url', 255)->nullable();
            $table->text('commentaire')->nullable();

            $table->foreign('id_controle', 'fk_defaut_controle')
                ->references('id_controle')->on('controle_qualite')->cascadeOnDelete();
            $table->foreign('id_type_defaut', 'fk_defaut_type')
                ->references('id_type_defaut')->on('type_defaut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('defaut_constate');
    }
};