<?php

namespace Database\Seeders;

use App\Models\CategorieMatiere;
use Illuminate\Database\Seeder;

class CategorieMatiereSeeder extends Seeder
{
    public function run(): void
    {
        CategorieMatiere::insert([
            ['nom' => 'Pâte polymère', 'description' => 'Pâte à modeler polymère'],
            ['nom' => 'Perles', 'description' => 'Perles diverses'],
            ['nom' => 'Apprêts', 'description' => 'Fermoirs, crochets, anneaux'],
            ['nom' => 'Chaînes', 'description' => 'Chaînes et cordons'],
            ['nom' => 'Finitions', 'description' => 'Vernis, colles'],
        ]);
    }
}