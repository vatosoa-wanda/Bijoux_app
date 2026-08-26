<?php

namespace Database\Seeders;

use App\Models\TypeBijou;
use Illuminate\Database\Seeder;

class TypeBijouSeeder extends Seeder
{
    public function run(): void
    {
        TypeBijou::insert([
            ['nom' => 'Bracelet', 'description' => null],
            ['nom' => 'Collier', 'description' => null],
            ['nom' => 'Boucles d\'oreilles', 'description' => null],
        ]);
    }
}