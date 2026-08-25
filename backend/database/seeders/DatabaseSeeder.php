<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UniteMesureSeeder::class,
            CategorieMatiereSeeder::class,
            TypeMouvementSeeder::class,
            ParametreSeeder::class,
            MatierePremiereSeeder::class,
        ]);
    }
}