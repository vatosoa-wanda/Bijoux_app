# Migration

php artisan make:migration create_postgres_enum_types
php artisan make:migration create_unite_mesure_table 
php artisan make:migration create_categorie_matiere_table
php artisan make:migration create_matiere_premiere_table
php artisan make:migration create_type_mouvement_table
php artisan make:migration create_mouvement_stock_table
php artisan make:migration create_type_bijou_table
php artisan make:migration create_collection_table
php artisan make:migration create_bijou_table
php artisan make:migration create_composition_bijou_table
php artisan make:migration create_parametre_table

# Modeles

php artisan make:model UniteMesure
php artisan make:model CategorieMatiere
php artisan make:model MatierePremiere
php artisan make:model TypeMouvement
php artisan make:model MouvementStock
php artisan make:model TypeBijou
php artisan make:model Collection
php artisan make:model Bijou
php artisan make:model CompositionBijou
php artisan make:model Parametre

# Migration triggers/vues - Fonctionnalite 1:
php artisan make:migration create_stock_triggers_and_views

# Seeders
php artisan make:seeder UniteMesureSeeder
php artisan make:seeder CategorieMatiereSeeder
php artisan make:seeder TypeMouvementSeeder
php artisan make:seeder ParametreSeeder
php artisan make:seeder MatierePremiereSeeder

# Execution finale
php artisan migrate:fresh --seed

# Controllers
php artisan make:controller Api/MatierePremiereController --api
php artisan make:controller Api/MouvementStockController
php artisan make:request StoreMatierePremiereRequest
php artisan make:request UpdateMatierePremiereRequest
php artisan make:request StoreMouvementStockRequest
php artisan make:resource MatierePremiereResource
php artisan make:resource MouvementStockResource

# Endpoint dispo
GET    /api/matieres
POST   /api/matieres
GET    /api/matieres/{id}
PUT    /api/matieres/{id}
DELETE /api/matieres/{id}
GET    /api/matieres/alertes
GET    /api/mouvements
POST   /api/mouvements


# Tests PHPUnit (Feature)
php artisan make:test StockTriggerTest

php artisan key:generate --env=testing

php artisan test --filter=StockTriggerTest


php artisan make:controller Api/CategorieMatiereController
php artisan make:controller Api/UniteMesureController
php artisan make:controller Api/TypeMouvementController


<!-- J2 -->
# Migrations restantes
php artisan make:migration create_type_defaut_table
php artisan make:migration create_statut_production_table
php artisan make:migration create_ordre_fabrication_table
php artisan make:migration create_consommation_of_table
php artisan make:migration create_produit_fini_table
php artisan make:migration create_controle_qualite_table
php artisan make:migration create_defaut_constate_table

# Migration triggers/Fonctions - Fonctionnalite 2:
php artisan make:migration create_production_functions_and_triggers

# Autres seeders
php artisan make:seeder TypeBijouSeeder
php artisan make:seeder StatutProductionSeeder
php artisan make:seeder TypeDefautSeeder
php artisan make:seeder BijouSeeder
php artisan make:seeder CompositionBijouSeeder

# Modeles Eloquent
php artisan make:model TypeDefaut
php artisan make:model StatutProduction
php artisan make:model OrdreFabrication
php artisan make:model ConsommationOf
php artisan make:model ProduitFini
php artisan make:model ControleQualite
php artisan make:model DefautConstate

# FormRequests
php artisan make:request StoreBijouRequest
php artisan make:request UpdateBijouRequest
php artisan make:request StoreCompositionBijouRequest
php artisan make:request StoreOrdreFabricationRequest
php artisan make:request UpdateStatutOrdreFabricationRequest

# API Resources
php artisan make:resource BijouResource
php artisan make:resource CompositionBijouResource
php artisan make:resource OrdreFabricationResource

# Controllers

php artisan make:controller Api/BijouController --api
php artisan make:controller Api/CompositionBijouController
php artisan make:controller Api/OrdreFabricationController


## Commande pour repartir d'une base propre
php artisan migrate:fresh --seed --drop-types --drop-views


# Test
php artisan make:test OrdreFabricationTest

php artisan test --filter=OrdreFabricationTest

<!-- PASS  Tests\Feature\OrdreFabricationTest
✓ creation of refusee si stock insuffisant
✓ creation of acceptee si stock suffisant
✓ cloture of decremente le stock et cree le produit fini
✓ le cout de revient est calcule correctement
✓ cloture of refusee si stock devenu insuffisant -->


php artisan make:controller Api/TypeBijouController
php artisan make:controller Api/StatutProductionController