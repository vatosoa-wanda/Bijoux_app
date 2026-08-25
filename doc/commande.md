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