<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrdreFabricationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'id_bijou'          => ['required', 'exists:bijou,id_bijou'],
            'quantite_prevue'   => ['required', 'integer', 'min:1'],
            'date_debut'        => ['nullable', 'date'],
            'date_fin_prevue'   => ['nullable', 'date', 'after_or_equal:date_debut'],
            'notes'             => ['nullable', 'string'],
        ];
    }
}