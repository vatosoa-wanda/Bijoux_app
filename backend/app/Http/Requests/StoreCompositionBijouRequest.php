<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompositionBijouRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'id_matiere'          => ['required', 'exists:matiere_premiere,id_matiere'],
            'quantite_necessaire' => ['required', 'numeric', 'min:0.01'],
        ];
    }
}