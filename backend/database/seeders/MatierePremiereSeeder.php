<?php

namespace Database\Seeders;

use App\Models\MatierePremiere;
use Illuminate\Database\Seeder;

class MatierePremiereSeeder extends Seeder
{
    public function run(): void
    {
        MatierePremiere::insert([
            ['id_categorie' => 1, 'id_unite' => 1, 'nom' => 'Pâte rouge', 'couleur' => 'Rouge', 'quantite_stock' => 450, 'seuil_alerte' => 500, 'prix_unitaire' => 0.05],
            ['id_categorie' => 2, 'id_unite' => 2, 'nom' => 'Perle blanche', 'couleur' => 'Blanc', 'quantite_stock' => 85, 'seuil_alerte' => 100, 'prix_unitaire' => 0.05],
            ['id_categorie' => 3, 'id_unite' => 2, 'nom' => 'Fermoir', 'couleur' => null, 'quantite_stock' => 15, 'seuil_alerte' => 20, 'prix_unitaire' => 0.10],
            ['id_categorie' => 4, 'id_unite' => 3, 'nom' => 'Chaîne fine', 'couleur' => 'Doré', 'quantite_stock' => 200, 'seuil_alerte' => 50, 'prix_unitaire' => 0.08],
        ]);
    }
}