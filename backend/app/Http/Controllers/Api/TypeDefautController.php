<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TypeDefaut;

class TypeDefautController extends Controller
{
    public function index()
    {
        return response()->json(['data' => TypeDefaut::orderBy('libelle')->get()]);
    }
}