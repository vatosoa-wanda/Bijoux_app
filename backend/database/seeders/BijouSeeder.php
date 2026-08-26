<?php

namespace Database\Seeders;

use App\Models\Bijou;
use Illuminate\Database\Seeder;

class BijouSeeder extends Seeder
{
    public function run(): void
    {
        Bijou::insert([
            [
                'id_type_bijou' => 1, 'reference' => 'BR-LUNA-001', 'nom' => 'Bracelet Luna',
                'taille' => 'M', 'complexite' => 'Moyenne', 'temps_fabrication_minutes' => 30,
            ],
        ]);
    }
}