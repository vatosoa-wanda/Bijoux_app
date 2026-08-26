<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreControleQualiteRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'id_of'               => ['required', 'exists:ordre_fabrication,id_of'],
            'quantite_controlee'  => ['required', 'integer', 'min:1'],
            'quantite_validee'    => ['required', 'integer', 'min:0'],
            'quantite_rejetee'    => ['required', 'integer', 'min:0'],
            'commentaire'         => ['nullable', 'string'],
            'defauts'                       => ['nullable', 'array'],
            'defauts.*.id_type_defaut'      => ['required_with:defauts', 'exists:type_defaut,id_type_defaut'],
            'defauts.*.quantite'            => ['required_with:defauts', 'integer', 'min:1'],
            'defauts.*.commentaire'         => ['nullable', 'string'],
            'defauts.*.photo_url'           => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->quantite_validee + $this->quantite_rejetee > $this->quantite_controlee) {
                $validator->errors()->add(
                    'quantite_controlee',
                    'La somme des quantités validées et rejetées ne peut pas dépasser la quantité contrôlée.'
                );
            }
        });
    }
}