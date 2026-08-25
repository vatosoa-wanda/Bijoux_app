<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CategorieMatiere;

class CategorieMatiereController extends Controller
{
    public function index()
    {
        return response()->json(['data' => CategorieMatiere::orderBy('nom')->get()]);
    }
}