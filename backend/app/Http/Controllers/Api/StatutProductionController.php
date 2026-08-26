<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StatutProduction;

class StatutProductionController extends Controller
{
    public function index()
    {
        return response()->json(['data' => StatutProduction::orderBy('ordre')->get()]);
    }
}