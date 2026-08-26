<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrdreFabricationRequest;
use App\Http\Requests\UpdateStatutOrdreFabricationRequest;
use App\Http\Resources\OrdreFabricationResource;
use App\Models\OrdreFabrication;
use App\Models\StatutProduction;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class OrdreFabricationController extends Controller
{
    public function index(): JsonResponse
    {
        $ofs = OrdreFabrication::with(['bijou', 'statutProduction'])
            ->orderByDesc('created_at')
            ->get();

        return OrdreFabricationResource::collection($ofs)->response();
    }

    public function store(StoreOrdreFabricationRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Vérification de disponibilité via la fonction PL/pgSQL
        $dispo = DB::selectOne('SELECT fn_verifier_disponibilite(?, ?) AS ok', [
            $data['id_bijou'], $data['quantite_prevue'],
        ]);

        if (! $dispo->ok) {
            return response()->json([
                'message' => 'Stock insuffisant pour lancer cet ordre de fabrication.',
            ], 422);
        }

        $statutEnAttente = StatutProduction::where('code', 'EN_ATTENTE')->firstOrFail();

        $of = OrdreFabrication::create([
            ...$data,
            'id_statut_prod' => $statutEnAttente->id_statut_prod,
            'reference'      => 'OF-' . now()->format('Ymd') . '-' . str_pad((string) (OrdreFabrication::count() + 1), 4, '0', STR_PAD_LEFT),
        ]);

        return (new OrdreFabricationResource($of->load(['bijou', 'statutProduction'])))
            ->response()->setStatusCode(201);
    }

    public function show(OrdreFabrication $ordre_fabrication): JsonResponse
    {
        return (new OrdreFabricationResource($ordre_fabrication->load(['bijou', 'statutProduction'])))->response();
    }

    public function updateStatut(UpdateStatutOrdreFabricationRequest $request, OrdreFabrication $ordre_fabrication): JsonResponse
    {
        try {
            DB::transaction(function () use ($request, $ordre_fabrication) {
                $ordre_fabrication->update([
                    'id_statut_prod'    => $request->id_statut_prod,
                    'quantite_realisee' => $request->quantite_realisee ?? $ordre_fabrication->quantite_realisee,
                    'quantite_rejetee'  => $request->quantite_rejetee ?? $ordre_fabrication->quantite_rejetee,
                    'date_fin_reelle'   => $request->quantite_realisee ? now() : $ordre_fabrication->date_fin_reelle,
                ]);
            });

            return (new OrdreFabricationResource($ordre_fabrication->fresh(['bijou', 'statutProduction'])))->response();

        } catch (Throwable $e) {
            if (str_contains($e->getMessage(), 'Stock insuffisant')) {
                return response()->json([
                    'message' => 'Stock insuffisant pour clôturer cet ordre de fabrication avec cette quantité réalisée.',
                ], 422);
            }

            // TEMPORAIRE — à retirer une fois le bug identifié
            return response()->json([
                'message' => 'Erreur lors du changement de statut.',
                'debug' => $e->getMessage(),
            ], 500);
        }
    }
}