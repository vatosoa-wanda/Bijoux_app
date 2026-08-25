<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collection', function (Blueprint $table) {
            $table->id('id_collection');
            $table->string('nom', 100);
            $table->string('saison', 50)->nullable();
            $table->integer('annee')->nullable();
            $table->text('description')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamp('created_at')->useCurrent();
        });

        DB::statement('ALTER TABLE collection ADD CONSTRAINT collection_annee_check CHECK (annee >= 1900 AND annee <= 2100)');
    }

    public function down(): void
    {
        Schema::dropIfExists('collection');
    }
};