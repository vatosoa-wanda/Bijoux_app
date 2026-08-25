<?php

namespace Database\Seeders;

use App\Models\TypeMouvement;
use Illuminate\Database\Seeder;

class TypeMouvementSeeder extends Seeder
{
    public function run(): void
    {
        TypeMouvement::insert([
            ['code' => 'ACHAT',      'libelle' => 'Achat fournisseur', 'sens' => 'ENTREE'],
            ['code' => 'CORRECTION_ENTREE', 'libelle' => 'Correction (entrée)', 'sens' => 'ENTREE'],
            ['code' => 'PRODUCTION', 'libelle' => 'Consommation production', 'sens' => 'SORTIE'],
            ['code' => 'PERTE',      'libelle' => 'Perte / rejet', 'sens' => 'SORTIE'],
            ['code' => 'CORRECTION_SORTIE', 'libelle' => 'Correction (sortie)', 'sens' => 'SORTIE'],
        ]);
    }
}