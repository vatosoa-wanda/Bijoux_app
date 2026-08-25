<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parametre', function (Blueprint $table) {
            $table->id('id_parametre');
            $table->string('cle', 50)->unique();
            $table->string('valeur', 255);
            $table->text('description')->nullable();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parametre');
    }
};