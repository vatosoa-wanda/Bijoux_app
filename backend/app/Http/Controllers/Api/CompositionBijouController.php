<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCompositionBijouRequest;
use App\Http\Resources\CompositionBijouResource;
use App\Models\Bijou;
use App\Models\CompositionBijou;
use Illuminate\Http\JsonResponse;

class CompositionBijouController extends Controller
{
    public function store(StoreCompositionBijouRequest $request, Bijou $bijou): JsonResponse
    {
        $composition = $bijou->compositions()->create($request->validated());

        return (new CompositionBijouResource($composition->load('matierePremiere')))
            ->response()->setStatusCode(201);
    }

    public function destroy(Bijou $bijou, CompositionBijou $composition): JsonResponse
    {
        $composition->delete();

        return response()->json(null, 204);
    }
}