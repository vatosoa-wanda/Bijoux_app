<?php

use App\Http\Controllers\Api\MatierePremiereController;
use App\Http\Controllers\Api\MouvementStockController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CategorieMatiereController;
use App\Http\Controllers\Api\UniteMesureController;
use App\Http\Controllers\Api\TypeMouvementController;

use App\Http\Controllers\Api\BijouController;
use App\Http\Controllers\Api\CompositionBijouController;
use App\Http\Controllers\Api\OrdreFabricationController;

Route::get('bijoux/{bijou}/cout-revient', [BijouController::class, 'coutRevient']);

Route::apiResource('bijoux', BijouController::class);

Route::post('bijoux/{bijou}/compositions', [CompositionBijouController::class, 'store']);
Route::delete('bijoux/{bijou}/compositions/{composition}', [CompositionBijouController::class, 'destroy']);

Route::apiResource('ordres-fabrication', OrdreFabricationController::class)
    ->only(['index', 'store', 'show']);
Route::patch('ordres-fabrication/{ordre_fabrication}/statut', [OrdreFabricationController::class, 'updateStatut']);

// J1
Route::get('categories-matiere', [CategorieMatiereController::class, 'index']);
Route::get('unites-mesure', [UniteMesureController::class, 'index']);
Route::get('types-mouvement', [TypeMouvementController::class, 'index']);


Route::prefix('matieres')->group(function () {
    Route::get('alertes', [MatierePremiereController::class, 'alertes']);
});

Route::apiResource('matieres', MatierePremiereController::class)
    ->parameters(['matieres' => 'matiere_premiere']);

Route::apiResource('mouvements', MouvementStockController::class)
    ->only(['index', 'store']);