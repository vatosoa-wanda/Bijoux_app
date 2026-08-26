<?php

namespace Database\Seeders;

use App\Models\CompositionBijou;
use Illuminate\Database\Seeder;

class CompositionBijouSeeder extends Seeder
{
    public function run(): void
    {
        // Bracelet Luna (id_bijou = 1) : pâte rouge (id 1), perle blanche (id 2), fermoir (id 3)
        CompositionBijou::insert([
            ['id_bijou' => 1, 'id_matiere' => 1, 'quantite_necessaire' => 10],
            ['id_bijou' => 1, 'id_matiere' => 2, 'quantite_necessaire' => 10],
            ['id_bijou' => 1, 'id_matiere' => 3, 'quantite_necessaire' => 1],
        ]);
    }
}