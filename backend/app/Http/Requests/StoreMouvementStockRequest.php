<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMouvementStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_matiere'         => ['required', 'exists:matiere_premiere,id_matiere'],
            'id_type_mvt'        => ['required', 'exists:type_mouvement,id_type_mvt'],
            'quantite'           => ['required', 'numeric', 'min:0.01'],
            'prix_total'         => ['nullable', 'numeric', 'min:0'],
            'reference_externe'  => ['nullable', 'string', 'max:100'],
            'commentaire'        => ['nullable', 'string'],
        ];
    }
}