<?php

namespace Database\Seeders;

use App\Models\StatutProduction;
use Illuminate\Database\Seeder;

class StatutProductionSeeder extends Seeder
{
    public function run(): void
    {
        StatutProduction::insert([
            ['code' => 'EN_ATTENTE', 'libelle' => 'En attente', 'ordre' => 1],
            ['code' => 'EN_COURS',   'libelle' => 'En cours', 'ordre' => 2],
            ['code' => 'TERMINE',    'libelle' => 'Terminé', 'ordre' => 3],
        ]);
    }
}