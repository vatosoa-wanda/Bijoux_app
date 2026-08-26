<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('type_defaut', function (Blueprint $table) {
            $table->id('id_type_defaut');
            $table->string('code', 30)->unique();
            $table->string('libelle', 100);
            $table->text('description')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('type_defaut');
    }
};