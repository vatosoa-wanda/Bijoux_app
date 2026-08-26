<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TypeBijou;

class TypeBijouController extends Controller
{
    public function index()
    {
        return response()->json(['data' => TypeBijou::orderBy('nom')->get()]);
    }
}