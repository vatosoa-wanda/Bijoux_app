<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UniteMesure;

class TypeMouvementController extends Controller
{
    public function index()
    {
        return response()->json(['data' => UniteMesure::orderBy('libelle')->get()]);
    }
}