<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBijouRequest;
use App\Http\Requests\UpdateBijouRequest;
use App\Http\Resources\BijouResource;
use App\Models\Bijou;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class BijouController extends Controller
{
    public function index(): JsonResponse
    {
        $bijoux = Bijou::with(['typeBijou', 'compositions.matierePremiere'])
            ->orderBy('nom')
            ->get();

        return BijouResource::collection($bijoux)->response();
    }

    public function store(StoreBijouRequest $request): JsonResponse
    {
        $bijou = Bijou::create($request->validated());

        return (new BijouResource($bijou))->response()->setStatusCode(201);
    }

    public function show(Bijou $bijou): JsonResponse
    {
        return (new BijouResource($bijou->load(['typeBijou', 'compositions.matierePremiere'])))->response();
    }

    public function update(UpdateBijouRequest $request, Bijou $bijou): JsonResponse
    {
        $bijou->update($request->validated());

        return (new BijouResource($bijou))->response();
    }

    public function destroy(Bijou $bijou): JsonResponse
    {
        $bijou->update(['actif' => false]);

        return response()->json(null, 204);
    }

    public function coutRevient(Bijou $bijou): JsonResponse
    {
        $result = DB::selectOne('SELECT fn_cout_revient(?) AS cout', [$bijou->id_bijou]);

        return response()->json([
            'id_bijou'     => $bijou->id_bijou,
            'nom'          => $bijou->nom,
            'cout_revient' => (float) $result->cout,
        ]);
    }
}