<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreControleQualiteRequest;
use App\Http\Resources\ControleQualiteResource;
use App\Models\ControleQualite;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ControleQualiteController extends Controller
{
    public function index(): JsonResponse
    {
        $controles = ControleQualite::with(['ordreFabrication', 'defauts.typeDefaut'])
            ->orderByDesc('date_controle')
            ->get();

        return ControleQualiteResource::collection($controles)->response();
    }

    public function store(StoreControleQualiteRequest $request): JsonResponse
    {
        $controle = DB::transaction(function () use ($request) {
            // L'insertion déclenche le trigger tr_controle_qualite_rejet
            // qui incrémente automatiquement ordre_fabrication.quantite_rejetee
            $controle = ControleQualite::create($request->safe()->except('defauts'));

            foreach ($request->input('defauts', []) as $defaut) {
                $controle->defauts()->create($defaut);
            }

            return $controle;
        });

        return (new ControleQualiteResource($controle->load(['ordreFabrication', 'defauts.typeDefaut'])))
            ->response()->setStatusCode(201);
    }
}