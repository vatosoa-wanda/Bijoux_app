<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class StatistiquesController extends Controller
{
    public function tauxRejetParBijou(): JsonResponse
    {
        $data = DB::table('vue_taux_rejet_par_bijou')->get()->map(function ($row) {
            $row->taux_rejet_pct = $row->taux_rejet_pct !== null ? (float) $row->taux_rejet_pct : null;
            return $row;
        });

        return response()->json(['data' => $data]);
    }

    public function defautsParType(): JsonResponse
    {
        return response()->json(['data' => DB::table('vue_defauts_par_type')->get()]);
    }

    public function kpiDashboard(): JsonResponse
    {
        $kpi = DB::table('vue_kpi_dashboard')->first();

        if ($kpi) {
            $kpi->cout_moyen_bijou = $kpi->cout_moyen_bijou !== null ? (float) $kpi->cout_moyen_bijou : null;
        }

        return response()->json(['data' => $kpi]);
    }
}