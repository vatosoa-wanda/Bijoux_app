<?php

namespace Tests\Feature;

use App\Models\Bijou;
use App\Models\CategorieMatiere;
use App\Models\CompositionBijou;
use App\Models\MatierePremiere;
use App\Models\OrdreFabrication;
use App\Models\Parametre;
use App\Models\StatutProduction;
use App\Models\TypeBijou;
use App\Models\TypeMouvement;
use App\Models\UniteMesure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrdreFabricationTest extends TestCase
{
    use RefreshDatabase;

    protected Bijou $bijou;
    protected MatierePremiere $matiere;
    protected StatutProduction $statutEnAttente;
    protected StatutProduction $statutTermine;

    protected function setUp(): void
    {
        parent::setUp();

        $categorie = CategorieMatiere::create(['nom' => 'Test']);
        $unite = UniteMesure::create(['code' => 'g', 'libelle' => 'Gramme']);

        $this->matiere = MatierePremiere::create([
            'id_categorie' => $categorie->id_categorie,
            'id_unite'     => $unite->id_unite,
            'nom'          => 'Pâte test',
            'quantite_stock' => 200,
            'seuil_alerte'   => 50,
            'prix_unitaire'  => 0.05,
        ]);

        $typeBijou = TypeBijou::create(['nom' => 'Bracelet']);

        $this->bijou = Bijou::create([
            'id_type_bijou' => $typeBijou->id_type_bijou,
            'reference'     => 'BR-TEST-001',
            'nom'           => 'Bracelet Test',
            'temps_fabrication_minutes' => 30,
        ]);

        CompositionBijou::create([
            'id_bijou'  => $this->bijou->id_bijou,
            'id_matiere' => $this->matiere->id_matiere,
            'quantite_necessaire' => 20,
        ]);

        Parametre::insert([
            ['cle' => 'taux_horaire', 'valeur' => '12'],
            ['cle' => 'charges_indirectes_forfait', 'valeur' => '0.30'],
        ]);

        TypeMouvement::create(['code' => 'PRODUCTION', 'libelle' => 'Production', 'sens' => 'SORTIE']);

        $this->statutEnAttente = StatutProduction::create(['code' => 'EN_ATTENTE', 'libelle' => 'En attente', 'ordre' => 1]);
        $this->statutTermine  = StatutProduction::create(['code' => 'TERMINE', 'libelle' => 'Terminé', 'ordre' => 3]);
    }

    public function test_creation_of_refusee_si_stock_insuffisant(): void
    {
        // 50 bracelets x 20g = 1000g > 200g disponibles
        $response = $this->postJson('/api/ordres-fabrication', [
            'id_bijou' => $this->bijou->id_bijou,
            'quantite_prevue' => 50,
        ]);

        $response->assertStatus(422);
    }

    public function test_creation_of_acceptee_si_stock_suffisant(): void
    {
        // 5 bracelets x 20g = 100g <= 200g disponibles
        $response = $this->postJson('/api/ordres-fabrication', [
            'id_bijou' => $this->bijou->id_bijou,
            'quantite_prevue' => 5,
        ]);

        $response->assertStatus(201);
    }

    public function test_cloture_of_decremente_le_stock_et_cree_le_produit_fini(): void
    {
        $of = OrdreFabrication::create([
            'id_bijou'        => $this->bijou->id_bijou,
            'id_statut_prod'  => $this->statutEnAttente->id_statut_prod,
            'reference'       => 'OF-TEST-0001',
            'quantite_prevue' => 5,
        ]);

        $response = $this->patchJson("/api/ordres-fabrication/{$of->id_of}/statut", [
            'id_statut_prod'    => $this->statutTermine->id_statut_prod,
            'quantite_realisee' => 5,
        ]);

        // dump($response->json()); // TEMPORAIRE - à retirer après debug

        $response->assertStatus(200);

        // 5 bracelets x 20g = 100g consommés -> stock 200 - 100 = 100
        $this->assertEquals(100, $this->matiere->fresh()->quantite_stock);

        $this->assertDatabaseHas('produit_fini', [
            'id_of'           => $of->id_of,
            'quantite_stock'  => 5,
        ]);
    }

    public function test_le_cout_de_revient_est_calcule_correctement(): void
    {
        // matières : 20g x 0.05 = 1.00
        // main d'oeuvre : 30min/60 x 12 = 6.00
        // charges : 0.30
        // total attendu : 7.30
        $response = $this->getJson("/api/bijoux/{$this->bijou->id_bijou}/cout-revient");

        $response->assertStatus(200)
            ->assertJson(['cout_revient' => 7.30]);
    }

    public function test_cloture_of_refusee_si_stock_devenu_insuffisant(): void
    {
        $of = OrdreFabrication::create([
            'id_bijou'        => $this->bijou->id_bijou,
            'id_statut_prod'  => $this->statutEnAttente->id_statut_prod,
            'reference'       => 'OF-TEST-0002',
            'quantite_prevue' => 5,
        ]);

        // quantite_realisee=20 x 20g=400g > 200g disponibles -> le trigger doit rejeter
        $response = $this->patchJson("/api/ordres-fabrication/{$of->id_of}/statut", [
            'id_statut_prod'    => $this->statutTermine->id_statut_prod,
            'quantite_realisee' => 20,
        ]);

        $response->assertStatus(422);
        $this->assertEquals(200, $this->matiere->fresh()->quantite_stock);
    }
}