<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMatierePremiereRequest;
use App\Http\Requests\UpdateMatierePremiereRequest;
use App\Http\Resources\MatierePremiereResource;
use App\Models\MatierePremiere;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class MatierePremiereController extends Controller
{
    public function index(): JsonResponse
    {
        $matieres = MatierePremiere::with(['categorie', 'unite'])
            ->orderBy('nom')
            ->get();

        return MatierePremiereResource::collection($matieres)->response();
    }

    public function store(StoreMatierePremiereRequest $request): JsonResponse
    {
        $matiere = MatierePremiere::create($request->validated());

        return (new MatierePremiereResource($matiere->load(['categorie', 'unite'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(MatierePremiere $matiere_premiere): JsonResponse
    {
        return (new MatierePremiereResource($matiere_premiere->load(['categorie', 'unite'])))
            ->response();
    }

    public function update(UpdateMatierePremiereRequest $request, MatierePremiere $matiere_premiere): JsonResponse
    {
        $matiere_premiere->update($request->validated());

        return (new MatierePremiereResource($matiere_premiere->load(['categorie', 'unite'])))
            ->response();
    }

    public function destroy(MatierePremiere $matiere_premiere): JsonResponse
    {
        // Soft delete métier : on désactive plutôt que de supprimer (traçabilité des mouvements historiques)
        $matiere_premiere->update(['actif' => false]);

        return response()->json(null, 204);
    }

    public function alertes(): JsonResponse
    {
        $alertes = DB::table('vue_stock_alertes')->get();

        return response()->json(['data' => $alertes]);
    }
}