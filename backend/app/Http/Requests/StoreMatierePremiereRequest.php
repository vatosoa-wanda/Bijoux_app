<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMatierePremiereRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_categorie'     => ['required', 'exists:categorie_matiere,id_categorie'],
            'id_unite'         => ['required', 'exists:unite_mesure,id_unite'],
            'nom'              => ['required', 'string', 'max:100'],
            'couleur'          => ['nullable', 'string', 'max:50'],
            'quantite_stock'   => ['nullable', 'numeric', 'min:0'],
            'seuil_alerte'     => ['required', 'numeric', 'min:0'],
            'prix_unitaire'    => ['required', 'numeric', 'min:0'],
            'date_peremption'  => ['nullable', 'date'],
            'actif'            => ['boolean'],
        ];
    }
}