<?php

namespace Tests\Feature;

use App\Models\CategorieMatiere;
use App\Models\MatierePremiere;
use App\Models\TypeMouvement;
use App\Models\UniteMesure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockTriggerTest extends TestCase
{
    use RefreshDatabase;

    protected MatierePremiere $matiere;
    protected TypeMouvement $typeEntree;
    protected TypeMouvement $typeSortie;

    protected function setUp(): void
    {
        parent::setUp();

        $categorie = CategorieMatiere::create(['nom' => 'Test', 'description' => null]);
        $unite = UniteMesure::create(['code' => 'g', 'libelle' => 'Gramme']);

        $this->matiere = MatierePremiere::create([
            'id_categorie'   => $categorie->id_categorie,
            'id_unite'       => $unite->id_unite,
            'nom'            => 'Pâte test',
            'quantite_stock' => 100,
            'seuil_alerte'   => 50,
            'prix_unitaire'  => 0.05,
        ]);

        $this->typeEntree = TypeMouvement::create(['code' => 'ACHAT', 'libelle' => 'Achat', 'sens' => 'ENTREE']);
        $this->typeSortie = TypeMouvement::create(['code' => 'PRODUCTION', 'libelle' => 'Production', 'sens' => 'SORTIE']);
    }

    public function test_un_mouvement_entree_augmente_le_stock(): void
    {
        $response = $this->postJson('/api/mouvements', [
            'id_matiere'  => $this->matiere->id_matiere,
            'id_type_mvt' => $this->typeEntree->id_type_mvt,
            'quantite'    => 50,
        ]);

        $response->assertStatus(201);

        $this->assertEquals(150, $this->matiere->fresh()->quantite_stock);
    }

    public function test_un_mouvement_sortie_diminue_le_stock(): void
    {
        $response = $this->postJson('/api/mouvements', [
            'id_matiere'  => $this->matiere->id_matiere,
            'id_type_mvt' => $this->typeSortie->id_type_mvt,
            'quantite'    => 30,
        ]);

        $response->assertStatus(201);

        $this->assertEquals(70, $this->matiere->fresh()->quantite_stock);
    }

    public function test_un_mouvement_sortie_superieur_au_stock_est_refuse(): void
    {
        $response = $this->postJson('/api/mouvements', [
            'id_matiere'  => $this->matiere->id_matiere,
            'id_type_mvt' => $this->typeSortie->id_type_mvt,
            'quantite'    => 500, // stock actuel = 100
        ]);

        $response->assertStatus(422);

        // Le stock ne doit pas avoir bougé
        $this->assertEquals(100, $this->matiere->fresh()->quantite_stock);
    }

    public function test_la_vue_stock_alertes_retourne_les_matieres_sous_seuil(): void
    {
        // On fait descendre le stock sous le seuil (50)
        $this->postJson('/api/mouvements', [
            'id_matiere'  => $this->matiere->id_matiere,
            'id_type_mvt' => $this->typeSortie->id_type_mvt,
            'quantite'    => 60, // 100 - 60 = 40 <= seuil 50
        ]);

        $response = $this->getJson('/api/matieres/alertes');

        $response->assertStatus(200)
            ->assertJsonFragment(['id_matiere' => $this->matiere->id_matiere]);
    }
}