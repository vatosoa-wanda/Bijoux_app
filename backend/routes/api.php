<?php

use App\Http\Controllers\Api\MatierePremiereController;
use App\Http\Controllers\Api\MouvementStockController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CategorieMatiereController;
use App\Http\Controllers\Api\UniteMesureController;
use App\Http\Controllers\Api\TypeMouvementController;

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