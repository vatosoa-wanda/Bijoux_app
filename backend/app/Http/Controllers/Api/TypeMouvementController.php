<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TypeMouvement;

class TypeMouvementController extends Controller
{
    public function index()
    {
        return response()->json(['data' => TypeMouvement::orderBy('libelle')->get()]);
    }
}