<?php

use App\Http\Controllers\Api\MatierePremiereController;
use App\Http\Controllers\Api\MouvementStockController;
use Illuminate\Support\Facades\Route;

Route::prefix('matieres')->group(function () {
    Route::get('alertes', [MatierePremiereController::class, 'alertes']);
});

Route::apiResource('matieres', MatierePremiereController::class)
    ->parameters(['matieres' => 'matiere_premiere']);

Route::apiResource('mouvements', MouvementStockController::class)
    ->only(['index', 'store']);