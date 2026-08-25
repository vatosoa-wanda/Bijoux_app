<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unite_mesure', function (Blueprint $table) {
            $table->id('id_unite');
            $table->string('code', 10)->unique();
            $table->string('libelle', 50);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unite_mesure');
    }
};