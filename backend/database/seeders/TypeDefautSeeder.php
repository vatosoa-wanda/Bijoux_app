<?php

namespace Database\Seeders;

use App\Models\TypeDefaut;
use Illuminate\Database\Seeder;

class TypeDefautSeeder extends Seeder
{
    public function run(): void
    {
        TypeDefaut::insert([
            ['code' => 'FISSURE', 'libelle' => 'Fissure'],
            ['code' => 'BRULURE', 'libelle' => 'Brûlure de cuisson'],
            ['code' => 'DEFAUT_FINITION', 'libelle' => 'Défaut de finition'],
            ['code' => 'PROBLEME_ASSEMBLAGE', 'libelle' => 'Problème d\'assemblage'],
        ]);
    }
}