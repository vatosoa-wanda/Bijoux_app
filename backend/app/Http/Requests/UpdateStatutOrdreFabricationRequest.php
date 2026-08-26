<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStatutOrdreFabricationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'id_statut_prod'    => ['required', 'exists:statut_production,id_statut_prod'],
            'quantite_realisee' => ['required_if:code_cible,TERMINE', 'nullable', 'integer', 'min:0'],
            'quantite_rejetee'  => ['nullable', 'integer', 'min:0'],
        ];
    }
}