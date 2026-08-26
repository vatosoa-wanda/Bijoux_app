<?php

namespace Tests\Feature;

use App\Models\Bijou;
use App\Models\CategorieMatiere;
use App\Models\MatierePremiere;
use App\Models\OrdreFabrication;
use App\Models\Parametre;
use App\Models\StatutProduction;
use App\Models\TypeBijou;
use App\Models\TypeDefaut;
use App\Models\UniteMesure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ControleQualiteTest extends TestCase
{
    use RefreshDatabase;

    protected OrdreFabrication $of;
    protected TypeDefaut $typeDefaut;

    protected function setUp(): void
    {
        parent::setUp();

        $categorie = CategorieMatiere::create(['nom' => 'Test']);
        $unite = UniteMesure::create(['code' => 'g', 'libelle' => 'Gramme']);
        MatierePremiere::create([
            'id_categorie' => $categorie->id_categorie,
            'id_unite'     => $unite->id_unite,
            'nom'          => 'Pâte test',
            'quantite_stock' => 500,
            'seuil_alerte'   => 50,
            'prix_unitaire'  => 0.05,
        ]);

        $typeBijou = TypeBijou::create(['nom' => 'Bracelet']);
        $bijou = Bijou::create([
            'id_type_bijou' => $typeBijou->id_type_bijou,
            'reference'     => 'BR-TEST-001',
            'nom'           => 'Bracelet Test',
            'temps_fabrication_minutes' => 30,
        ]);

        Parametre::insert([
            ['cle' => 'taux_horaire', 'valeur' => '12'],
            ['cle' => 'charges_indirectes_forfait', 'valeur' => '0.30'],
        ]);

        $statutEnAttente = StatutProduction::create(['code' => 'EN_ATTENTE', 'libelle' => 'En attente', 'ordre' => 1]);
        StatutProduction::create(['code' => 'EN_COURS', 'libelle' => 'En cours', 'ordre' => 2]);
        StatutProduction::create(['code' => 'TERMINE', 'libelle' => 'Terminé', 'ordre' => 3]);

        $this->of = OrdreFabrication::create([
            'id_bijou'        => $bijou->id_bijou,
            'id_statut_prod'  => $statutEnAttente->id_statut_prod,
            'reference'       => 'OF-TEST-0001',
            'quantite_prevue' => 5,
            'quantite_realisee' => 5,
        ]);

        $this->typeDefaut = TypeDefaut::create(['code' => 'FISSURE', 'libelle' => 'Fissure']);
    }

    public function test_creation_controle_qualite_incremente_les_rejets_de_of(): void
    {
        $response = $this->postJson('/api/controles-qualite', [
            'id_of'              => $this->of->id_of,
            'quantite_controlee' => 5,
            'quantite_validee'   => 4,
            'quantite_rejetee'   => 1,
            'defauts' => [
                ['id_type_defaut' => $this->typeDefaut->id_type_defaut, 'quantite' => 1, 'commentaire' => 'Fissure visible'],
            ],
        ]);

        $response->assertStatus(201);

        $this->assertEquals(1, $this->of->fresh()->quantite_rejetee);

        $this->assertDatabaseHas('defaut_constate', [
            'id_type_defaut' => $this->typeDefaut->id_type_defaut,
            'quantite'       => 1,
        ]);
    }

    public function test_validation_refuse_si_somme_incoherente(): void
    {
        $response = $this->postJson('/api/controles-qualite', [
            'id_of'              => $this->of->id_of,
            'quantite_controlee' => 5,
            'quantite_validee'   => 4,
            'quantite_rejetee'   => 3, // 4 + 3 > 5
        ]);

        $response->assertStatus(422);
        $this->assertEquals(0, $this->of->fresh()->quantite_rejetee);
    }

    public function test_vue_taux_rejet_par_bijou_calcule_le_bon_pourcentage(): void
    {
        $this->postJson('/api/controles-qualite', [
            'id_of'              => $this->of->id_of,
            'quantite_controlee' => 5,
            'quantite_validee'   => 4,
            'quantite_rejetee'   => 1,
        ]);

        $response = $this->getJson('/api/statistiques/taux-rejet');

        $response->assertStatus(200)
            ->assertJsonFragment(['taux_rejet_pct' => 20]); // maintenant un vrai float grâce au cast dans le contrôleur
    }

    public function test_dashboard_kpi_renvoie_les_indicateurs(): void
    {
        $response = $this->getJson('/api/dashboard/kpi');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'bijoux_en_cours',
                    'ordres_termines',
                    'total_rejetes',
                    'cout_moyen_bijou',
                    'produits_en_stock',
                ],
            ]);
    }
}