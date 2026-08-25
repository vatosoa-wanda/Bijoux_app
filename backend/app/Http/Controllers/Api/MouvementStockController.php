<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMouvementStockRequest;
use App\Http\Resources\MouvementStockResource;
use App\Models\MouvementStock;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class MouvementStockController extends Controller
{
    public function index(): JsonResponse
    {
        $mouvements = MouvementStock::with(['matierePremiere', 'typeMouvement'])
            ->orderByDesc('date_mouvement')
            ->paginate(20);

        return MouvementStockResource::collection($mouvements)->response();
    }

    public function store(StoreMouvementStockRequest $request): JsonResponse
    {
        try {
            $mouvement = DB::transaction(function () use ($request) {
                // L'insertion déclenche le trigger PostgreSQL tr_mouvement_stock_apply
                // qui met à jour quantite_stock et lève une exception si le stock est insuffisant.
                return MouvementStock::create($request->validated());
            });

            return (new MouvementStockResource($mouvement->load(['matierePremiere', 'typeMouvement'])))
                ->response()
                ->setStatusCode(201);

        } catch (Throwable $e) {
            // Le trigger PostgreSQL renvoie un message via RAISE EXCEPTION,
            // remonté ici comme une erreur PDO générique.
            if (str_contains($e->getMessage(), 'Stock insuffisant')) {
                return response()->json([
                    'message' => 'Stock insuffisant pour effectuer ce mouvement.',
                ], 422);
            }

            return response()->json([
                'message' => 'Une erreur est survenue lors de l\'enregistrement du mouvement.',
            ], 500);
        }
    }
}