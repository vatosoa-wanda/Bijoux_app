<?php

namespace Database\Seeders;

use App\Models\Parametre;
use Illuminate\Database\Seeder;

class ParametreSeeder extends Seeder
{
    public function run(): void
    {
        Parametre::insert([
            ['cle' => 'taux_horaire', 'valeur' => '12', 'description' => 'Taux horaire de main-d\'œuvre (€/h)'],
            ['cle' => 'charges_indirectes_forfait', 'valeur' => '0.30', 'description' => 'Charges indirectes forfaitaires par bijou (€)'],
            ['cle' => 'marge_defaut', 'valeur' => '2.5', 'description' => 'Coefficient de marge par défaut'],
        ]);
    }
}