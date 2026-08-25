<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMatierePremiereRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_categorie'     => ['sometimes', 'required', 'exists:categorie_matiere,id_categorie'],
            'id_unite'         => ['sometimes', 'required', 'exists:unite_mesure,id_unite'],
            'nom'              => ['sometimes', 'required', 'string', 'max:100'],
            'couleur'          => ['nullable', 'string', 'max:50'],
            'seuil_alerte'     => ['sometimes', 'required', 'numeric', 'min:0'],
            'prix_unitaire'    => ['sometimes', 'required', 'numeric', 'min:0'],
            'date_peremption'  => ['nullable', 'date'],
            'actif'            => ['boolean'],
        ];
    }
}