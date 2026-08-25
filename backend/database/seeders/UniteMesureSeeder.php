<?php

namespace Database\Seeders;

use App\Models\UniteMesure;
use Illuminate\Database\Seeder;

class UniteMesureSeeder extends Seeder
{
    public function run(): void
    {
        UniteMesure::insert([
            ['code' => 'g',  'libelle' => 'Gramme'],
            ['code' => 'u',  'libelle' => 'Unité'],
            ['code' => 'cm', 'libelle' => 'Centimètre'],
            ['code' => 'ml', 'libelle' => 'Millilitre'],
        ]);
    }
}