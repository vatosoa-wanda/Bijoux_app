<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produit_fini', function (Blueprint $table) {
            $table->id('id_produit_fini');
            $table->unsignedBigInteger('id_bijou');
            $table->unsignedBigInteger('id_of')->nullable();
            $table->string('reference', 50)->unique();
            $table->integer('quantite_stock')->default(0);
            $table->decimal('cout_revient', 10, 2)->nullable();
            $table->decimal('prix_vente', 10, 2)->nullable();
            $table->date('date_fabrication')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('id_bijou', 'fk_produit_bijou')
                ->references('id_bijou')->on('bijou');
            $table->foreign('id_of', 'fk_produit_of')
                ->references('id_of')->on('ordre_fabrication');
        });

        DB::statement("ALTER TABLE produit_fini ADD COLUMN statut statut_produit DEFAULT 'EN_STOCK'");
    }

    public function down(): void
    {
        Schema::dropIfExists('produit_fini');
    }
};